<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GusClient
{
    /**
     * Search business info by Polish NIP.
     * Tries the MF White List API first, then falls back to official GUS BIR.
     *
     * @param string $nip
     * @return array|null [company_name, street, postal_code, city, nip]
     */
    public function searchByNip(string $nip): ?array
    {
        $nip = preg_replace('/\D/', '', $nip);
        if (strlen($nip) !== 10) {
            return null;
        }

        $whiteListError = null;
        $gusBirError = null;

        // 1. Try Ministry of Finance White List API (Free, no credentials needed)
        try {
            $date = now()->format('Y-m-d');
            $response = Http::timeout(3)
                ->get("https://wl-api.mf.gov.pl/api/search/nip/{$nip}", [
                    'date' => $date,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $subject = data_get($data, 'result.subject');

                if ($subject && !empty($subject['name'])) {
                    $addressStr = $subject['workingAddress'] ?? $subject['residenceAddress'] ?? '';
                    $parsedAddress = $this->parseAddressString($addressStr);

                    return [
                        'success' => true,
                        'source' => 'mf_whitelist',
                        'company_name' => $subject['name'],
                        'nip' => $nip,
                        'street' => $parsedAddress['street'] ?? '',
                        'postal_code' => $parsedAddress['postal_code'] ?? '',
                        'city' => $parsedAddress['city'] ?? '',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('GusClient WhiteList API error: ' . $e->getMessage());
            $whiteListError = $e;
        }

        // 2. Fallback to official GUS BIR SOAP API
        try {
            $result = $this->searchViaGusBir($nip);
            if ($result) {
                return $result;
            }
        } catch (\Exception $e) {
            Log::error('GusClient BIR SOAP API error: ' . $e->getMessage());
            $gusBirError = $e;
        }

        // If both failed and we got exceptions (like timeouts/network issues), return fallback status
        if ($whiteListError || $gusBirError) {
            return [
                'success' => false,
                'status' => 'timeout_fallback',
                'message' => 'API GUS/MF jest chwilowo niedostępne. Wprowadź dane firmy ręcznie.',
            ];
        }

        return null;
    }

    /**
     * Parse the address returned as a single string (e.g. "ul. Marszałkowska 10/12, 00-001 Warszawa")
     */
    private function parseAddressString(string $address): array
    {
        $address = trim($address);
        if (empty($address)) {
            return ['street' => '', 'postal_code' => '', 'city' => ''];
        }

        // Matches typical Polish addresses: "Street Name 12/34, 00-001 City Name"
        if (preg_match('/^(.*?),\s*(\d{2}-\d{3})\s+(.*)$/u', $address, $matches)) {
            return [
                'street' => trim($matches[1]),
                'postal_code' => trim($matches[2]),
                'city' => trim($matches[3]),
            ];
        }

        return [
            'street' => $address,
            'postal_code' => '',
            'city' => '',
        ];
    }

    /**
     * Query official GUS BIR SOAP API.
     */
    private function searchViaGusBir(string $nip): ?array
    {
        $apiKey = config('services.gus.api_key', 'abcde12345yzwx987lmn'); // Public test key
        $isProduction = config('services.gus.production', false);
        $url = $isProduction
            ? 'https://wyszukiwarkaregon.bir.gov.pl/ws/UslugaBIRzewnPubl.svc'
            : 'https://wyszukiwarkaregon.test.bir.gov.pl/ws/UslugaBIRzewnPubl.svc';

        try {
            // Step A: Login to get Session ID (sid)
            $loginSoapXml = '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:ns="http://CIS/BIR/PUBL/2014/07">
               <soap:Header/>
               <soap:Body>
                  <ns:Zaloguj>
                     <ns:pKluczProgramu>' . htmlspecialchars($apiKey) . '</ns:pKluczProgramu>
                  </ns:Zaloguj>
               </soap:Body>
            </soap:Envelope>';

            $loginResponse = Http::timeout(3)->withHeaders([
                'Content-Type' => 'application/soap+xml; charset=utf-8',
            ])->send('POST', $url, [
                'body' => $loginSoapXml,
            ]);

            if (!$loginResponse->successful()) {
                return null;
            }

            preg_match('/<ZalogujResult>(.*?)<\/ZalogujResult>/', $loginResponse->body(), $sidMatches);
            $sid = $sidMatches[1] ?? null;

            if (empty($sid)) {
                return null;
            }

            // Step B: Fetch details using sid
            $searchSoapXml = '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:ns="http://CIS/BIR/PUBL/2014/07" xmlns:dat="http://CIS/BIR/PUBL/2014/07/DataContract">
               <soap:Header/>
               <soap:Body>
                  <ns:DaneSzukajPodmioty>
                     <ns:pParametryWyszukiwania>
                        <dat:Nip>' . htmlspecialchars($nip) . '</dat:Nip>
                     </ns:pParametryWyszukiwania>
                  </ns:DaneSzukajPodmioty>
               </soap:Body>
            </soap:Envelope>';

            $detailsResponse = Http::timeout(3)->withHeaders([
                'Content-Type' => 'application/soap+xml; charset=utf-8',
                'sid' => $sid,
            ])->send('POST', $url, [
                'body' => $searchSoapXml,
            ]);

            if (!$detailsResponse->successful()) {
                return null;
            }

            // The SOAP response contains XML encoded inside <DaneSzukajPodmiotyResult>
            preg_match('/<DaneSzukajPodmiotyResult>(.*?)<\/DaneSzukajPodmiotyResult>/s', $detailsResponse->body(), $resultMatches);
            $xmlEncoded = $resultMatches[1] ?? '';

            if (empty($xmlEncoded)) {
                return null;
            }

            $xmlStr = html_entity_decode($xmlEncoded, ENT_QUOTES | ENT_XML1, 'UTF-8');
            
            // Parse XML response
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlStr);
            if ($xml === false || empty($xml->dane)) {
                return null;
            }

            $dane = $xml->dane;

            if (isset($dane->ErrorCode)) {
                return null;
            }

            $companyName = (string) ($dane->Nazwa ?? '');
            $city = (string) ($dane->Miejscowosc ?? '');
            $postalCode = (string) ($dane->KodPocztowy ?? '');
            
            $street = (string) ($dane->Ulica ?? '');
            $building = (string) ($dane->NrNieruchomosci ?? '');
            $flat = (string) ($dane->NrLokalu ?? '');
            
            $streetAddress = $street;
            if ($building !== '') {
                $streetAddress .= ' ' . $building;
                if ($flat !== '') {
                    $streetAddress .= '/' . $flat;
                }
            }

            if (empty($companyName)) {
                return null;
            }

            return [
                'success' => true,
                'source' => 'gus_bir',
                'company_name' => $companyName,
                'nip' => $nip,
                'street' => trim($streetAddress),
                'postal_code' => $postalCode,
                'city' => $city,
            ];
        } catch (\Exception $e) {
            Log::error('GusClient BIR SOAP API error: ' . $e->getMessage());
        }

        return null;
    }
}

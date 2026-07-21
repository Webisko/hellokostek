<?php

namespace App\Domain\Commerce\Logistics;

use App\Domain\Operations\IntegrationLogService;
use App\Models\Order;
use App\Support\StoreSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OrlenPaczkaService
{
    private const INTEGRATION = 'orlen_paczka';

    public function __construct(
        private readonly IntegrationLogService $integrationLogService,
        private readonly StoreSettings $storeSettings
    ) {
    }

    /**
     * Create a shipment in ORLEN Paczka and download the label.
     */
    public function generateLabel(Order $order, string $packageSize): array
    {
        $config = config('services.orlen_paczka');
        $partnerId = $config['partner_id'] ?? '';
        $partnerKey = $config['partner_key'] ?? '';
        $sandbox = (bool) ($config['sandbox'] ?? true);

        if (empty($partnerId) || empty($partnerKey)) {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'shipment_creation_skipped',
                status: 'warning',
                order: $order,
                errorMessage: 'Brak konfiguracji Partner ID lub Partner Key dla ORLEN Paczka.'
            );
            throw new \Exception('ORLEN Paczka integration is not fully configured.');
        }

        $wsdl = $sandbox
            ? 'https://api-test.orlenpaczka.pl/WebServicePwR/WebServicePwR.asmx?WSDL'
            : 'https://api.orlenpaczka.pl/WebServicePwRProd/WebServicePwR.asmx?wsdl';

        $destinationCode = data_get($order->metadata, 'delivery_point.id');
        if (empty($destinationCode)) {
            throw new \Exception('Brak identyfikatora punktu odbioru (ORLEN Paczka) dla tego zamówienia.');
        }

        // Prepare Recipient Details
        $shippingAddress = $order->shipping_address ?? $order->billing_address ?? [];
        $receiverPhone = $this->formatPhone($order->customer_phone);
        $receiverAddress = $this->parseAddressFields($shippingAddress);

        // Prepare Sender Details
        $senderPayload = $this->getSenderPayload();

        $params = [
            'PartnerID' => $partnerId,
            'PartnerKey' => $partnerKey,
            'DestinationCode' => trim($destinationCode),
            'AlternativeDestinationCode' => '',
            'BoxSize' => strtoupper($packageSize), // S, M, L
            'PackValue' => '0',
            'CashOnDelivery' => '0',
            'AmountCashOnDelivery' => '0',
            'Insurance' => '0',
            'EMail' => $order->customer_email,
            'FirstName' => $order->customer_first_name,
            'LastName' => $order->customer_last_name,
            'CompanyName' => $order->billing_company_name ?: '',
            'StreetName' => $receiverAddress['street'],
            'BuildingNumber' => $receiverAddress['building_number'],
            'FlatNumber' => $receiverAddress['flat_number'] ?: '',
            'City' => $shippingAddress['city'] ?? '',
            'PostCode' => str_replace('-', '', $shippingAddress['postal_code'] ?? $shippingAddress['postcode'] ?? ''),
            'PhoneNumber' => $receiverPhone,
            'SenderEMail' => $senderPayload['email'],
            'SenderFirstName' => $senderPayload['first_name'],
            'SenderLastName' => $senderPayload['last_name'],
            'SenderCompanyName' => $senderPayload['company_name'],
            'SenderStreetName' => $senderPayload['street'],
            'SenderBuildingNumber' => $senderPayload['building_number'],
            'SenderFlatNumber' => $senderPayload['flat_number'] ?: '',
            'SenderCity' => $senderPayload['city'],
            'SenderPostCode' => str_replace('-', '', $senderPayload['post_code']),
            'SenderPhoneNumber' => $senderPayload['phone'],
            'SenderOrders' => $order->number,
            'ReturnDestinationCode' => '',
            'ReturnEMail' => '',
        ];

        try {
            // Orlen Paczka requires SOAP version 1.2
            $client = new \SoapClient($wsdl, [
                'soap_version' => SOAP_1_2,
                'trace' => 1,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ]);

            $response = $client->GenerateLabelBusinessPack($params);

            if (isset($response->GenerateLabelBusinessPackResult)) {
                $result = $response->GenerateLabelBusinessPackResult;

                // Checking for error structure or empty tracking code
                if (empty($result->PackCode)) {
                    $errorMsg = $result->ErrorMessage ?? 'Nieznany błąd API ORLEN Paczka.';
                    throw new \Exception('Błąd API ORLEN Paczka: ' . $errorMsg);
                }

                $trackingNumber = $result->PackCode;
                $labelBase64 = $result->LabelData;

                if (empty($labelBase64)) {
                    throw new \Exception("Paczka {$trackingNumber} została utworzona, ale nie otrzymano danych etykiety.");
                }

                $pdfContent = base64_decode($labelBase64);
                $pdfPath = "orlen/labels/{$trackingNumber}.pdf";
                Storage::put($pdfPath, $pdfContent);

                // Record Log
                $this->integrationLogService->record(
                    integration: self::INTEGRATION,
                    event: 'shipment_created',
                    status: 'success',
                    order: $order,
                    direction: 'outgoing',
                    externalReference: $trackingNumber,
                    requestPayload: $this->maskSensitiveData($params),
                    responsePayload: [
                        'PackCode' => $result->PackCode,
                        'LabelData' => '[BASE64_DATA_MASKED]',
                    ]
                );

                return [
                    'success' => true,
                    'tracking_number' => $trackingNumber,
                    'label_path' => $pdfPath,
                ];
            } else {
                throw new \Exception('Brak oczekiwanego wyniku w odpowiedzi SOAP ORLEN Paczka.');
            }
        } catch (\SoapFault $e) {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'shipment_creation_failed',
                status: 'error',
                order: $order,
                direction: 'outgoing',
                requestPayload: $this->maskSensitiveData($params),
                errorMessage: 'SOAP Error: ' . $e->getMessage()
            );
            throw new \Exception('Błąd SOAP ORLEN Paczka: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'shipment_creation_failed',
                status: 'error',
                order: $order,
                direction: 'outgoing',
                requestPayload: isset($params) ? $this->maskSensitiveData($params) : null,
                errorMessage: $e->getMessage()
            );
            throw $e;
        }
    }

    /**
     * Parse address line into street, building, and flat fields.
     */
    private function parseAddressFields(array $address): array
    {
        $line1 = trim($address['line_1'] ?? $address['line1'] ?? '');
        
        $street = $line1;
        $building = '1';
        $flat = '';

        if (preg_match('/^(.*?)\s*(\d+[a-zA-Z]?)(?:\s*(?:\/|lok\.|lokal)\s*(\d+))?$/ui', $line1, $matches)) {
            $street = trim($matches[1]);
            $building = trim($matches[2]);
            $flat = trim($matches[3] ?? '');
        }

        return [
            'street' => $street ?: 'Przykładowa',
            'building_number' => $building ?: '1',
            'flat_number' => $flat,
        ];
    }

    /**
     * Get sender payload from config or store settings.
     */
    private function getSenderPayload(): array
    {
        $config = config('services.orlen_paczka');
        
        $email = $config['sender_email'] ?: $this->storeSettings->supportEmail();
        $phone = $this->formatPhone($config['sender_phone'] ?: data_get($this->storeSettings->model()->metadata, 'phone'));
        $name = $config['sender_name'] ?: $this->storeSettings->storeName();
        $company = $config['sender_company'] ?: $this->storeSettings->storeName();

        $metadata = $this->storeSettings->model()->metadata ?? [];
        
        $streetLine = $config['sender_street'] ?: ($metadata['address_street'] ?? 'Przykładowa 1');
        $streetParsed = $this->parseAddressFields(['line_1' => $streetLine]);

        $city = $config['sender_city'] ?: ($metadata['address_city'] ?? 'Warszawa');
        $postcode = $config['sender_postcode'] ?: ($metadata['address_postal_code'] ?? '00-001');

        return [
            'company_name' => $company,
            'first_name' => 'Sklep',
            'last_name' => 'Wysyłki',
            'email' => $email,
            'phone' => $phone,
            'street' => $streetParsed['street'],
            'building_number' => $streetParsed['building_number'],
            'flat_number' => $streetParsed['flat_number'] ?: '',
            'city' => $city,
            'post_code' => $postcode,
        ];
    }

    /**
     * Standardize phone format.
     */
    private function formatPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        
        if (strlen($digits) === 9) {
            return $digits;
        }
        
        if (strlen($digits) === 11 && str_starts_with($digits, '48')) {
            return substr($digits, 2);
        }

        return substr($digits, -9) ?: '999999999';
    }

    /**
     * Mask PartnerKey in request payload log.
     */
    private function maskSensitiveData(array $params): array
    {
        if (isset($params['PartnerKey'])) {
            $params['PartnerKey'] = '********';
        }
        return $params;
    }
}

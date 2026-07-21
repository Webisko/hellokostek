<?php

namespace Tests\Feature\Api;

use App\Support\GusClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class B2bGusControllerTest extends TestCase
{
    use RefreshDatabase;
    public function test_fails_with_invalid_nip(): void
    {
        $response = $this->getJson(route('api.b2b.gus', ['nip' => '1234']));

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_can_search_by_nip_via_mf_whitelist(): void
    {
        // Mock Ministry of Finance WhiteList API response
        Http::fake([
            'wl-api.mf.gov.pl/api/search/nip/*' => Http::response([
                'result' => [
                    'subject' => [
                        'name' => 'ACME SP. Z O.O.',
                        'workingAddress' => 'ul. Marszałkowska 10/12, 00-001 Warszawa',
                        'nip' => '1234567890',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson(route('api.b2b.gus', ['nip' => '1234567890']));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.company_name', 'ACME SP. Z O.O.');
        $response->assertJsonPath('data.street', 'ul. Marszałkowska 10/12');
        $response->assertJsonPath('data.postal_code', '00-001');
        $response->assertJsonPath('data.city', 'Warszawa');
    }

    public function test_falls_back_to_official_gus_bir_soap_on_whitelist_failure(): void
    {
        // Mock WhiteList failure, but mock GUS BIR SOAP success
        Http::fake([
            'wl-api.mf.gov.pl/api/search/nip/*' => Http::response([], 500),
            'wyszukiwarkaregon.test.bir.gov.pl/ws/UslugaBIRzewnPubl.svc' => Http::sequence()
                // Session ID login response
                ->push('<soap:Envelope><soap:Body><ZalogujResult>mock_session_id_123</ZalogujResult></soap:Body></soap:Envelope>', 200, [
                    'Content-Type' => 'application/soap+xml; charset=utf-8'
                ])
                // Search result XML response
                ->push('<soap:Envelope><soap:Body><DaneSzukajPodmiotyResult>&lt;root&gt;&lt;dane&gt;&lt;Nazwa&gt;GUS BIR COMPANY&lt;/Nazwa&gt;&lt;Miejscowosc&gt;Kraków&lt;/Miejscowosc&gt;&lt;KodPocztowy&gt;30-002&lt;/KodPocztowy&gt;&lt;Ulica&gt;Floriańska&lt;/Ulica&gt;&lt;NrNieruchomosci&gt;5&lt;/NrNieruchomosci&gt;&lt;/dane&gt;&lt;/root&gt;</DaneSzukajPodmiotyResult></soap:Body></soap:Envelope>', 200, [
                    'Content-Type' => 'application/soap+xml; charset=utf-8'
                ])
        ]);

        $response = $this->getJson(route('api.b2b.gus', ['nip' => '1234567890']));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.company_name', 'GUS BIR COMPANY');
        $response->assertJsonPath('data.street', 'Floriańska 5');
        $response->assertJsonPath('data.postal_code', '30-002');
        $response->assertJsonPath('data.city', 'Kraków');
    }

    public function test_returns_404_if_not_found_in_both_databases(): void
    {
        Http::fake([
            'wl-api.mf.gov.pl/api/search/nip/*' => Http::response([
                'result' => [
                    'subject' => null,
                ],
            ], 200),
            'wyszukiwarkaregon.test.bir.gov.pl/ws/UslugaBIRzewnPubl.svc' => Http::sequence()
                ->push('<soap:Envelope><soap:Body><ZalogujResult>mock_session_id_123</ZalogujResult></soap:Body></soap:Envelope>', 200)
                ->push('<soap:Envelope><soap:Body><DaneSzukajPodmiotyResult>&lt;root&gt;&lt;dane&gt;&lt;ErrorCode&gt;4&lt;/ErrorCode&gt;&lt;ErrorMessagePl&gt;Nie znaleziono podmiotu&lt;/ErrorMessagePl&gt;&lt;/dane&gt;&lt;/root&gt;</DaneSzukajPodmiotyResult></soap:Body></soap:Envelope>', 200)
        ]);

        $response = $this->getJson(route('api.b2b.gus', ['nip' => '1234567890']));

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
    }

    public function test_returns_timeout_fallback_on_api_timeout(): void
    {
        Http::fake([
            'wl-api.mf.gov.pl/api/search/nip/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException("Connection timed out after 3000ms");
            },
            'wyszukiwarkaregon.test.bir.gov.pl/ws/UslugaBIRzewnPubl.svc' => function () {
                throw new \Illuminate\Http\Client\ConnectionException("Connection timed out after 3000ms");
            },
        ]);

        $response = $this->getJson(route('api.b2b.gus', ['nip' => '1234567890']));

        $response->assertStatus(200);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('status', 'timeout_fallback');
        $response->assertJsonPath('message', 'API GUS/MF jest chwilowo niedostępne. Wprowadź dane firmy ręcznie.');
    }
}

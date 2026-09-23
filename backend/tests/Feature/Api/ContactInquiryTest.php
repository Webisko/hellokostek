<?php

namespace Tests\Feature\Api;

use App\Models\ContactInquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_inquiry_can_be_submitted_successfully(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->postJson(route('api.inquiries.store'), [
            'name' => 'Jan Kowalski',
            'email' => 'jan@kowalski.pl',
            'phone' => '123456789',
            'subject' => 'Pytanie o ofertę',
            'message' => 'Dzień dobry, chciałbym zapytać o ofertę.',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('contact_inquiries', [
            'name' => 'Jan Kowalski',
            'email' => 'jan@kowalski.pl',
            'subject' => 'Pytanie o ofertę',
            'message' => 'Dzień dobry, chciałbym zapytać o ofertę.',
            'status' => 'new',
        ]);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContactInquiryAdminMail::class, function ($mail) {
            return $mail->inquiry->email === 'jan@kowalski.pl';
        });
    }

    public function test_contact_inquiry_can_save_complex_payload_from_multistep_forms(): void
    {
        $response = $this->postJson(route('api.inquiries.store'), [
            'name' => 'Tomasz Nowak',
            'email' => 'tomasz@nowak.pl',
            'message' => 'Zapytanie o brief.',
            // Niestandardowe, zaawansowane pola z formularza wielokrokowego
            'project_type' => 'Aplikacja Mobilna',
            'budget_range' => '20k - 50k PLN',
            'expected_deadline' => '3 miesiÄ…ce',
            'features' => ['dark_mode', 'notifications', 'payments'],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $inquiry = ContactInquiry::query()->where('email', 'tomasz@nowak.pl')->first();
        $this->assertNotNull($inquiry);
        $this->assertNotNull($inquiry->payload);
        
        // Weryfikacja dynamicznego payloadu
        $this->assertEquals('Aplikacja Mobilna', $inquiry->payload['project_type']);
        $this->assertEquals('20k - 50k PLN', $inquiry->payload['budget_range']);
        $this->assertEquals('dark_mode', $inquiry->payload['features'][0]);
    }

    public function test_contact_inquiry_validation_fails_on_missing_fields(): void
    {
        $response = $this->postJson(route('api.inquiries.store'), [
            'name' => '',
            'email' => 'invalid-email',
            'message' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'message']);
    }
}

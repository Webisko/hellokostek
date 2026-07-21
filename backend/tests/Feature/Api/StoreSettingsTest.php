<?php

namespace Tests\Feature\Api;

use App\Models\StoreSetting;
use App\Support\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_settings_endpoint_returns_public_configuration_correctly(): void
    {
        StoreSetting::query()->delete();

        StoreSetting::create([
            'store_name' => 'Test Shop Name',
            'currency' => 'EUR',
            'free_shipping_threshold' => 15000,
            'allow_guest_checkout' => false,
            'cookie_banner_enabled' => true,
            'google_tag_manager_id' => 'GTM-ABCDEF',
            'google_analytics_id' => 'G-XYZ123',
            'facebook_pixel_id' => '987654321',
            'cookie_banner_title' => 'Napiszemy tu zgody',
            'cookie_banner_description' => 'Opis zgody z linkiem',
            'custom_head_scripts' => '<script src="umami.js"></script>',
        ]);

        $response = $this->getJson('/api/store/settings');

        $response->assertOk()
            ->assertExactJson([
                'store_name' => 'Test Shop Name',
                'currency' => 'EUR',
                'free_shipping_threshold' => 15000,
                'allow_guest_checkout' => false,
                'announcement_enabled' => false,
                'announcement_text' => null,
                'cookie_banner_enabled' => true,
                'google_tag_manager_id' => 'GTM-ABCDEF',
                'google_analytics_id' => 'G-XYZ123',
                'facebook_pixel_id' => '987654321',
                'cookie_banner_title' => 'Napiszemy tu zgody',
                'cookie_banner_description' => 'Opis zgody z linkiem',
                'custom_head_scripts' => '<script src="umami.js"></script>',
                'global_noindex' => false,
                'maintenance_mode_enabled' => false,
                'maintenance_mode_message' => null,
            ]);
    }

    public function test_store_settings_endpoint_returns_defaults_when_empty(): void
    {
        StoreSetting::query()->delete();
        
        $response = $this->getJson('/api/store/settings');

        $response->assertOk()
            ->assertJsonStructure([
                'store_name',
                'currency',
                'free_shipping_threshold',
                'allow_guest_checkout',
                'cookie_banner_enabled',
                'google_tag_manager_id',
                'google_analytics_id',
                'facebook_pixel_id',
                'cookie_banner_title',
                'cookie_banner_description',
                'custom_head_scripts',
                'announcement_enabled',
                'announcement_text',
            ])
            ->assertJson([
                'cookie_banner_enabled' => false,
                'google_tag_manager_id' => null,
                'google_analytics_id' => null,
                'facebook_pixel_id' => null,
                'cookie_banner_title' => 'Szanujemy Twoją prywatność',
                'custom_head_scripts' => null,
            ]);
    }

    public function test_admin_path_falls_back_to_default_when_metadata_is_blank(): void
    {
        StoreSetting::query()->delete();

        StoreSetting::create([
            'metadata' => ['admin_path' => ''],
        ]);

        $this->assertSame('admin', app(StoreSettings::class)->adminPath());
    }
}

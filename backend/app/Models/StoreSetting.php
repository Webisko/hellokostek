<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function () {
            app(\App\Support\StoreSettings::class)->purgeCache();
        });

        static::deleted(function () {
            app(\App\Support\StoreSettings::class)->purgeCache();
        });
    }

    protected $fillable = [
        'store_name',
        'currency',
        'exchange_rates',
        'free_shipping_threshold',
        'wholesale_minimum_regular_price_multiplier',
        'allow_guest_checkout',
        'cod_only_method',
        'support_email',
        'admin_notification_email',
        'order_notification_email',
        'mail_from_name',
        'mail_from_address',
        'shipping_methods',
        'shipping_zones',
        'integrations',
        'seo',
        'metadata',
        'product_reviews_enabled',
        'general_reviews_enabled',
        'general_reviews_source',
        'cookie_banner_enabled',
        'google_tag_manager_id',
        'google_analytics_id',
        'facebook_pixel_id',
        'cookie_banner_title',
        'cookie_banner_description',
        'custom_head_scripts',
        'announcement_enabled',
        'announcement_text',
        'global_noindex',
        'maintenance_mode_enabled',
        'maintenance_mode_allowed_ips',
        'maintenance_mode_message',
        'eu_import_flat_duty_enabled',
    ];

    protected function casts(): array
    {
        return [
            'free_shipping_threshold' => 'integer',
            'wholesale_minimum_regular_price_multiplier' => 'float',
            'allow_guest_checkout' => 'boolean',
            'exchange_rates' => 'array',
            'shipping_methods' => 'array',
            'shipping_zones' => 'array',
            'integrations' => 'array',
            'seo' => 'array',
            'metadata' => 'array',
            'product_reviews_enabled' => 'boolean',
            'general_reviews_enabled' => 'boolean',
            'general_reviews_source' => 'string',
            'cookie_banner_enabled' => 'boolean',
            'announcement_enabled' => 'boolean',
            'global_noindex' => 'boolean',
            'maintenance_mode_enabled' => 'boolean',
            'eu_import_flat_duty_enabled' => 'boolean',
        ];
    }
}
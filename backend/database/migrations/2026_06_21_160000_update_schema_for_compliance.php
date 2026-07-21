<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Zmiana nullability user_id w order_returns
        Schema::table('order_returns', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        // 2. Dodanie kolumny is_privileged_entrepreneur do orders
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_privileged_entrepreneur')->default(false)->after('wants_invoice');
        });

        // 3. Dodanie product_variant_id do product_price_histories
        Schema::table('product_price_histories', function (Blueprint $table) {
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();
            
            $table->index(['product_variant_id', 'recorded_at'], 'idx_variant_recorded_at');
        });

        // 4. Dodanie wymogów GPSR i produktów cyfrowych do products
        Schema::table('products', function (Blueprint $table) {
            $table->string('hs_code')->nullable()->after('sku');
            $table->boolean('is_shipped_from_outside_eu')->default(false)->after('hs_code');

            // Dane GPSR
            $table->string('gpsr_manufacturer_name')->nullable()->after('is_noindex');
            $table->string('gpsr_manufacturer_address')->nullable()->after('gpsr_manufacturer_name');
            $table->string('gpsr_manufacturer_email')->nullable()->after('gpsr_manufacturer_address');
            $table->string('gpsr_responsible_name')->nullable()->after('gpsr_manufacturer_email');
            $table->string('gpsr_responsible_address')->nullable()->after('gpsr_responsible_name');
            $table->string('gpsr_responsible_email')->nullable()->after('gpsr_responsible_address');
            $table->text('gpsr_safety_warnings')->nullable()->after('gpsr_responsible_email');
            $table->string('gpsr_document_path')->nullable()->after('gpsr_safety_warnings');

            // Dane kompatybilności cyfrowej
            $table->string('digital_compatibility')->nullable()->after('gpsr_document_path');
            $table->string('digital_interoperability')->nullable()->after('digital_compatibility');
            $table->string('digital_drm')->nullable()->after('digital_interoperability');
            $table->string('digital_updates_info')->nullable()->after('digital_drm');
        });

        // 5. Dodanie opcji cła importowego do store_settings
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('eu_import_flat_duty_enabled')->default(false)->after('general_reviews_source');
        });
    }

    public function down(): void
    {
        // Cofanie zmian w store_settings
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn('eu_import_flat_duty_enabled');
        });

        // Cofanie zmian w products
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'hs_code',
                'is_shipped_from_outside_eu',
                'gpsr_manufacturer_name',
                'gpsr_manufacturer_address',
                'gpsr_manufacturer_email',
                'gpsr_responsible_name',
                'gpsr_responsible_address',
                'gpsr_responsible_email',
                'gpsr_safety_warnings',
                'gpsr_document_path',
                'digital_compatibility',
                'digital_interoperability',
                'digital_drm',
                'digital_updates_info',
            ]);
        });

        // Cofanie zmian w product_price_histories
        Schema::table('product_price_histories', function (Blueprint $table) {
            $table->dropIndex('idx_variant_recorded_at');
            $table->dropColumn('product_variant_id');
        });

        // Cofanie zmian w orders
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('is_privileged_entrepreneur');
        });

        // Cofanie nullability user_id w order_returns
        Schema::table('order_returns', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};

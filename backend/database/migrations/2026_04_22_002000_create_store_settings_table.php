<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->default('Generic Shop');
            $table->char('currency', 3)->default('PLN');
            $table->unsignedInteger('free_shipping_threshold')->default(25000);
            $table->decimal('wholesale_minimum_regular_price_multiplier', 6, 4)->default(0.7000);
            $table->string('cod_only_method')->nullable();
            $table->string('support_email')->nullable();
            $table->string('admin_notification_email')->nullable();
            $table->string('order_notification_email')->nullable();
            $table->string('mail_from_name')->nullable();
            $table->string('mail_from_address')->nullable();
            $table->json('shipping_methods')->nullable();
            $table->json('integrations')->nullable();
            $table->json('seo')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
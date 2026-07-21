<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('segment', 32)->default('regular');
            $table->string('phone')->nullable();
            $table->unsignedInteger('completed_orders_count')->default(0);
            $table->timestamp('marketing_consent_at')->nullable();
            $table->timestamp('last_order_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['segment', 'completed_orders_count']);
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->string('discount_type', 32)->default('percentage');
            $table->unsignedInteger('value');
            $table->char('currency', 3)->default('PLN');
            $table->unsignedInteger('minimum_subtotal_amount')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('customer_profiles');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_name', 120);
            $table->string('event_id', 191);
            $table->string('deduplication_key', 191)->unique();
            $table->timestamp('occurred_at')->nullable();
            $table->string('environment', 32);
            $table->string('hostname', 191);
            $table->string('pathname', 255);
            $table->string('page_type', 120);
            $table->string('referrer_host', 191)->nullable();
            $table->string('utm_source', 191)->nullable();
            $table->string('utm_medium', 191)->nullable();
            $table->string('utm_campaign', 191)->nullable();
            $table->string('utm_content', 191)->nullable();
            $table->string('utm_term', 191)->nullable();
            $table->string('visit_id', 191)->nullable();
            $table->string('pageview_id', 191)->nullable();
            $table->string('currency', 8)->nullable();
            $table->decimal('value', 10, 2)->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index(['environment', 'event_name', 'occurred_at']);
            $table->index(['page_type', 'occurred_at']);
            $table->index(['pathname', 'occurred_at']);
            $table->index(['referrer_host', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
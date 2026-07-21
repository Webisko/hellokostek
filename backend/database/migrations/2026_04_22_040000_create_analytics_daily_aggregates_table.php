<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_daily_aggregates', function (Blueprint $table) {
            $table->id();
            $table->date('aggregate_date');
            $table->string('environment', 32);
            $table->string('report_key', 64);
            $table->string('dimension', 64)->default('');
            $table->string('dimension_value', 191)->default('');
            $table->unsignedBigInteger('value')->default(0);
            $table->timestamps();

            $table->unique(['aggregate_date', 'environment', 'report_key', 'dimension', 'dimension_value'], 'analytics_daily_aggregates_unique');
            $table->index(['report_key', 'aggregate_date']);
            $table->index(['environment', 'aggregate_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_aggregates');
    }
};
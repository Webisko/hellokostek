<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('product_reviews_enabled')->default(true);
            $table->boolean('general_reviews_enabled')->default(true);
            $table->string('general_reviews_source')->default('both');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'product_reviews_enabled',
                'general_reviews_enabled',
                'general_reviews_source',
            ]);
        });
    }
};

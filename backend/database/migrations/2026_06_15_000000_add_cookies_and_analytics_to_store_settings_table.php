<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('cookie_banner_enabled')->default(false)->after('general_reviews_source');
            $table->string('google_tag_manager_id')->nullable()->after('cookie_banner_enabled');
            $table->string('google_analytics_id')->nullable()->after('google_tag_manager_id');
            $table->string('facebook_pixel_id')->nullable()->after('google_analytics_id');
            $table->string('cookie_banner_title')->nullable()->after('facebook_pixel_id');
            $table->text('cookie_banner_description')->nullable()->after('cookie_banner_title');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'cookie_banner_enabled',
                'google_tag_manager_id',
                'google_analytics_id',
                'facebook_pixel_id',
                'cookie_banner_title',
                'cookie_banner_description',
            ]);
        });
    }
};

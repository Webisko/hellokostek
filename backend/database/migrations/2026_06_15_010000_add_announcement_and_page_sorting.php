<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('announcement_enabled')->default(false)->after('cookie_banner_description');
            $table->text('announcement_text')->nullable()->after('announcement_enabled');
        });

        Schema::table('content_pages', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('seo_description');
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['announcement_enabled', 'announcement_text']);
        });

        Schema::table('content_pages', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};

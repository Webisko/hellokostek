<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('global_noindex')->default(false);
            $table->boolean('maintenance_mode_enabled')->default(false);
            $table->text('maintenance_mode_allowed_ips')->nullable();
            $table->text('maintenance_mode_message')->nullable();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_noindex')->default(false);
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->boolean('is_noindex')->default(false);
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->boolean('is_noindex')->default(false);
        });

        Schema::table('content_pages', function (Blueprint $table) {
            $table->boolean('is_noindex')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'global_noindex',
                'maintenance_mode_enabled',
                'maintenance_mode_allowed_ips',
                'maintenance_mode_message'
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_noindex');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('is_noindex');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn('is_noindex');
        });

        Schema::table('content_pages', function (Blueprint $table) {
            $table->dropColumn('is_noindex');
        });
    }
};

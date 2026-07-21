<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('featured_image_path')->nullable()->after('description');
        });

        Schema::table('content_pages', function (Blueprint $table) {
            $table->string('hero_image_path')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('content_pages', function (Blueprint $table) {
            $table->dropColumn('hero_image_path');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('featured_image_path');
        });
    }
};
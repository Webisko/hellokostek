<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('name')->change();
            $table->json('short_description')->nullable()->change();
            $table->json('description')->nullable()->change();
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->json('name')->change();
            $table->json('description')->nullable()->change();
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->json('title')->change();
            $table->json('excerpt')->nullable()->change();
            $table->json('content')->nullable()->change();
        });

        Schema::table('content_pages', function (Blueprint $table) {
            $table->json('title')->change();
            $table->json('excerpt')->nullable()->change();
            $table->json('content')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Reverting JSON back to string/text is not strictly necessary for SQLite, but we can define standard down rules.
        Schema::table('products', function (Blueprint $table) {
            $table->string('name')->change();
            $table->text('short_description')->nullable()->change();
            $table->text('description')->nullable()->change();
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->string('name')->change();
            $table->text('description')->nullable()->change();
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('title')->change();
            $table->text('excerpt')->nullable()->change();
            $table->text('content')->nullable()->change();
        });

        Schema::table('content_pages', function (Blueprint $table) {
            $table->string('title')->change();
            $table->text('excerpt')->nullable()->change();
            $table->text('content')->nullable()->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Helper function to extract plain text string from JSON translatable value
        $extractString = function ($value): ?string {
            if ($value === null || $value === '') {
                return null;
            }

            if (is_array($value)) {
                return $value['pl'] ?? reset($value) ?? null;
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded['pl'] ?? reset($decoded) ?? null;
                }
                return $value;
            }

            return (string) $value;
        };

        // 1. Convert products data
        if (Schema::hasTable('products')) {
            $products = DB::table('products')->get();
            foreach ($products as $product) {
                DB::table('products')->where('id', $product->id)->update([
                    'name' => $extractString($product->name) ?? 'Produkt bez nazwy',
                    'short_description' => $extractString($product->short_description),
                    'description' => $extractString($product->description),
                ]);
            }

            Schema::table('products', function (Blueprint $table) {
                $table->string('name')->change();
                $table->text('short_description')->nullable()->change();
                $table->text('description')->nullable()->change();
            });
        }

        // 2. Convert product_categories data
        if (Schema::hasTable('product_categories')) {
            $categories = DB::table('product_categories')->get();
            foreach ($categories as $cat) {
                DB::table('product_categories')->where('id', $cat->id)->update([
                    'name' => $extractString($cat->name) ?? 'Kategoria',
                    'description' => $extractString($cat->description),
                ]);
            }

            Schema::table('product_categories', function (Blueprint $table) {
                $table->string('name')->change();
                $table->text('description')->nullable()->change();
            });
        }

        // 3. Convert content_pages data
        if (Schema::hasTable('content_pages')) {
            $pages = DB::table('content_pages')->get();
            foreach ($pages as $page) {
                DB::table('content_pages')->where('id', $page->id)->update([
                    'title' => $extractString($page->title) ?? 'Strona',
                    'excerpt' => $extractString($page->excerpt),
                    'content' => $extractString($page->content),
                ]);
            }

            Schema::table('content_pages', function (Blueprint $table) {
                $table->string('title')->change();
                $table->text('excerpt')->nullable()->change();
                $table->text('content')->nullable()->change();
            });
        }

        // 4. Convert gallery_artworks data
        if (Schema::hasTable('gallery_artworks')) {
            $artworks = DB::table('gallery_artworks')->get();
            foreach ($artworks as $art) {
                DB::table('gallery_artworks')->where('id', $art->id)->update([
                    'title' => $extractString($art->title) ?? 'Obraz',
                ]);
            }

            Schema::table('gallery_artworks', function (Blueprint $table) {
                $table->string('title')->change();
            });
        }
    }

    public function down(): void
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

        Schema::table('content_pages', function (Blueprint $table) {
            $table->json('title')->change();
            $table->json('excerpt')->nullable()->change();
            $table->json('content')->nullable()->change();
        });

        Schema::table('gallery_artworks', function (Blueprint $table) {
            $table->json('title')->change();
        });
    }
};

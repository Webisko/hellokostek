<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'sort_order')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('description');
                $table->index(['is_active', 'sort_order']);
            });

            // Initialize sort_order with existing id order
            DB::statement('UPDATE products SET sort_order = id WHERE sort_order = 0');
        }

        if (Schema::hasTable('product_reviews') && !Schema::hasColumn('product_reviews', 'sort_order')) {
            Schema::table('product_reviews', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('comment');
                $table->index(['sort_order']);
            });

            // Initialize sort_order with existing id order
            DB::statement('UPDATE product_reviews SET sort_order = id WHERE sort_order = 0');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'sort_order')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['is_active', 'sort_order']);
                $table->dropColumn('sort_order');
            });
        }

        if (Schema::hasTable('product_reviews') && Schema::hasColumn('product_reviews', 'sort_order')) {
            Schema::table('product_reviews', function (Blueprint $table) {
                $table->dropIndex(['sort_order']);
                $table->dropColumn('sort_order');
            });
        }
    }
};

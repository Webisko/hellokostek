<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('product_bundle_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->nullOnDelete();

            // Drop old unique constraint
            $table->dropUnique('bundle_items_unique');

            // Add new unique constraint including variant
            $table->unique(['bundle_product_id', 'product_id', 'product_variant_id'], 'bundle_items_unique');
        });
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::table('product_bundle_items', function (Blueprint $table) {
            $table->dropUnique('bundle_items_unique');
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');

            $table->unique(['bundle_product_id', 'product_id'], 'bundle_items_unique');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('regular_price_amount');
            $table->unsignedInteger('sale_price_amount')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['product_id', 'recorded_at']);
        });

        // Seed initial price histories for existing products
        $products = DB::table('products')->get();
        foreach ($products as $product) {
            DB::table('product_price_histories')->insert([
                'product_id' => $product->id,
                'regular_price_amount' => $product->regular_price_amount,
                'sale_price_amount' => $product->sale_price_amount,
                'recorded_at' => $product->updated_at ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_histories');
    }
};

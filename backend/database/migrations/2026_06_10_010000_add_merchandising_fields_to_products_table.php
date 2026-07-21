<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_new')->default(false)->after('is_purchasable');
            $table->boolean('is_bestseller')->default(false)->after('is_new');
            $table->boolean('is_recommended')->default(false)->after('is_bestseller');
            $table->boolean('is_promoted')->default(false)->after('is_recommended');
            $table->boolean('is_seasonal')->default(false)->after('is_promoted');
            $table->boolean('is_clearance')->default(false)->after('is_seasonal');
            $table->boolean('show_on_homepage')->default(false)->after('is_clearance');
            $table->boolean('show_in_bestsellers')->default(false)->after('show_on_homepage');
            $table->boolean('show_in_new_arrivals')->default(false)->after('show_in_bestsellers');
            $table->boolean('show_in_recommended')->default(false)->after('show_in_new_arrivals');
            $table->json('manual_tags')->nullable()->after('metadata');

            $table->index(['show_on_homepage', 'is_active']);
            $table->index(['is_bestseller', 'is_active']);
            $table->index(['is_new', 'is_active']);
            $table->index(['is_recommended', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['show_on_homepage', 'is_active']);
            $table->dropIndex(['is_bestseller', 'is_active']);
            $table->dropIndex(['is_new', 'is_active']);
            $table->dropIndex(['is_recommended', 'is_active']);

            $table->dropColumn([
                'is_new',
                'is_bestseller',
                'is_recommended',
                'is_promoted',
                'is_seasonal',
                'is_clearance',
                'show_on_homepage',
                'show_in_bestsellers',
                'show_in_new_arrivals',
                'show_in_recommended',
                'manual_tags',
            ]);
        });
    }
};
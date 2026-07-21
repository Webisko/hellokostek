<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('product_reviews', 'status')) {
            Schema::table('product_reviews', function (Blueprint $table) {
                $table->string('status')->default('publiczny')->after('comment');
            });

            // Set status based on is_approved
            DB::table('product_reviews')
                ->where('is_approved', false)
                ->update(['status' => 'szkic']);

            DB::table('product_reviews')
                ->where('is_approved', true)
                ->update(['status' => 'publiczny']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_reviews', 'status')) {
            Schema::table('product_reviews', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};

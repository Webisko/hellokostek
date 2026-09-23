<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_artworks', function (Blueprint $table) {
            $table->string('format', 255)->nullable()->after('technique');
            $table->json('metadata')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('gallery_artworks', function (Blueprint $table) {
            $table->dropColumn(['format', 'metadata']);
        });
    }
};

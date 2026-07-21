<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'is_ai_generated')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_ai_generated')->default(false)->after('is_noindex');
                $table->text('ai_disclosure_text')->nullable()->after('is_ai_generated');
            });
        }

        if (Schema::hasTable('blog_posts') && !Schema::hasColumn('blog_posts', 'is_ai_generated')) {
            Schema::table('blog_posts', function (Blueprint $table) {
                $table->boolean('is_ai_generated')->default(false)->after('is_noindex');
                $table->text('ai_disclosure_text')->nullable()->after('is_ai_generated');
            });
        }

        if (Schema::hasTable('courses') && !Schema::hasColumn('courses', 'is_ai_generated')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->boolean('is_ai_generated')->default(false)->after('is_noindex');
                $table->text('ai_disclosure_text')->nullable()->after('is_ai_generated');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'is_ai_generated')) {
                    $table->dropColumn(['is_ai_generated', 'ai_disclosure_text']);
                }
            });
        }

        if (Schema::hasTable('blog_posts')) {
            Schema::table('blog_posts', function (Blueprint $table) {
                if (Schema::hasColumn('blog_posts', 'is_ai_generated')) {
                    $table->dropColumn(['is_ai_generated', 'ai_disclosure_text']);
                }
            });
        }

        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                if (Schema::hasColumn('courses', 'is_ai_generated')) {
                    $table->dropColumn(['is_ai_generated', 'ai_disclosure_text']);
                }
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->string('status', 30)->default('pending')->after('source');
            $table->string('double_opt_in_token', 100)->nullable()->after('status');
            $table->string('double_opt_in_ip', 45)->nullable()->after('double_opt_in_token');
            $table->timestamp('double_opt_in_confirmed_at')->nullable()->after('double_opt_in_ip');

            $table->index('status');
            $table->index('double_opt_in_token');
        });

        // Sync existing active/inactive records
        DB::table('newsletter_subscribers')
            ->where('is_active', true)
            ->update(['status' => 'active', 'double_opt_in_confirmed_at' => DB::raw('consented_at')]);

        DB::table('newsletter_subscribers')
            ->where('is_active', false)
            ->whereNotNull('unsubscribed_at')
            ->update(['status' => 'unsubscribed']);
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['double_opt_in_token']);
            $table->dropColumn(['status', 'double_opt_in_token', 'double_opt_in_ip', 'double_opt_in_confirmed_at']);
        });
    }
};

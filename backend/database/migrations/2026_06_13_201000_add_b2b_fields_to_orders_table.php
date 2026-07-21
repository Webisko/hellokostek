<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('billing_company_name')->nullable()->after('customer_phone');
            $table->string('billing_nip')->nullable()->after('billing_company_name');
            $table->boolean('wants_invoice')->default(false)->after('billing_nip');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['billing_company_name', 'billing_nip', 'wants_invoice']);
        });
    }
};

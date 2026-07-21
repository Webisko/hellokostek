<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaire_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('questionnaire_key', 120)->default('mushroom-matcher');
            $table->string('name');
            $table->string('email');
            $table->string('source', 120)->nullable();
            $table->boolean('consented_to_marketing')->default(false);
            $table->timestamp('consented_at')->nullable();
            $table->json('answers');
            $table->json('recommended_products');
            $table->string('coupon_code', 64)->nullable();
            $table->string('result_email_status', 32)->default('pending');
            $table->string('admin_notification_status', 32)->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['questionnaire_key', 'created_at']);
            $table->index(['email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaire_submissions');
    }
};
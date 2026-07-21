<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_fulfillment_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type', 64);
            $table->string('status', 32)->default('pending');
            $table->string('title');
            $table->text('instructions');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['action_type', 'status']);
            $table->unique(['order_item_id', 'action_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_fulfillment_actions');
    }
};
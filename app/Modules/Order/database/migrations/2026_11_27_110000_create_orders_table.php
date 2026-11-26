<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            return;
        }

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->string('status', 20);
            $table->unsignedBigInteger('total_amount');
            $table->char('currency', 3);
            $table->char('idempotency_key', 36);
            $table->json('shipping_address');
            $table->string('note', 500)->nullable();
            $table->string('cancel_reason', 30)->nullable();
            $table->timestamps();
            $table->timestamp('cancelled_at')->nullable();
            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

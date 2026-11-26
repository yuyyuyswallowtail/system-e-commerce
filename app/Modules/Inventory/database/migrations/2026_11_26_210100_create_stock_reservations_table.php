<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_reservations')) {
            return;
        }

        Schema::create('stock_reservations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->char('order_uuid', 36)->index();
            $table->char('product_uuid', 36);
            $table->unsignedInteger('quantity');
            $table->string('status', 20);
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->unique(['order_uuid', 'product_uuid']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cart_items')) {
            return;
        }

        Schema::create('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->char('product_uuid', 36);
            $table->unsignedInteger('quantity');
            $table->bigInteger('unit_price_amount');
            $table->char('currency', 3);
            $table->timestamps();
            $table->unique(['cart_id', 'product_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};

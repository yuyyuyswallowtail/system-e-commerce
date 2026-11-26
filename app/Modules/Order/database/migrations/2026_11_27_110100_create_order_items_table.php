<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_items')) {
            return;
        }

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->char('product_uuid', 36);
            $table->string('product_name', 255);
            $table->string('sku', 64);
            $table->unsignedBigInteger('unit_price_amount');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total_amount');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

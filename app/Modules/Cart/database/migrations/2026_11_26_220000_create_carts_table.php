<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('carts')) {
            return;
        }

        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->string('status', 20);
            // Equals user_id while the cart is active and NULL otherwise: one active cart per user.
            $table->unsignedBigInteger('active_key')->nullable()->unique();
            $table->timestamps();
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};

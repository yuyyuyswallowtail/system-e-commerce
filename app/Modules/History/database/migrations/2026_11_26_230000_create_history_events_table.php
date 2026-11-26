<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('history_events')) {
            return;
        }

        Schema::create('history_events', function (Blueprint $table): void {
            $table->id();
            $table->char('event_uuid', 36)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('actor_id')->nullable()->constrained('users');
            $table->string('subject_type', 20);
            $table->char('subject_uuid', 36)->index();
            $table->string('action', 30);
            $table->json('details')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();
            $table->index(['user_id', 'occurred_at']);
            $table->index(['subject_type', 'action']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history_events');
    }
};

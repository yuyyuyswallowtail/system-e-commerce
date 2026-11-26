<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'uuid')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->char('uuid', 36)->nullable()->after('id');
            });
        }

        DB::table('users')->whereNull('uuid')->orderBy('id')->get(['id'])->each(function (object $row): void {
            DB::table('users')->where('id', $row->id)->update(['uuid' => Uuid::uuid4()->toString()]);
        });

        $uuid = collect(Schema::getColumns('users'))->firstWhere('name', 'uuid');

        if ($uuid['nullable']) {
            Schema::table('users', function (Blueprint $table): void {
                $table->char('uuid', 36)->nullable(false)->change();
            });
        }

        if (! Schema::hasIndex('users', 'uq_users_uuid')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('uuid', 'uq_users_uuid');
            });
        }

        if (! Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('status', 20)->default(config('shop.default_user_status'))->after('password');
            });
        }

        if (! Schema::hasIndex('users', 'idx_users_status')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index('status', 'idx_users_status');
            });
        }

        if (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('users', 'uq_users_uuid')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('uq_users_uuid');
            });
        }

        if (Schema::hasIndex('users', 'idx_users_status')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex('idx_users_status');
            });
        }

        foreach (['uuid', 'status', 'deleted_at'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};

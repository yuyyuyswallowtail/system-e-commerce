<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('runs the inventory migrations twice without error', function () {
    $this->artisan('migrate')->assertExitCode(0);
    $this->artisan('migrate')->assertExitCode(0);

    foreach (glob(app_path('Modules/Inventory/database/migrations/*.php')) as $file) {
        $migration = require $file;
        $migration->up();
    }

    expect(Schema::hasTable('stock_items'))->toBeTrue()
        ->and(Schema::hasTable('stock_reservations'))->toBeTrue()
        ->and(Schema::hasTable('stock_movements'))->toBeTrue();
});

it('has the exact columns', function () {
    expect(Schema::hasColumns('stock_items', ['product_uuid', 'quantity_on_hand', 'quantity_reserved', 'low_stock_threshold', 'created_at', 'updated_at']))->toBeTrue()
        ->and(Schema::hasColumns('stock_reservations', ['id', 'uuid', 'order_uuid', 'product_uuid', 'quantity', 'status', 'expires_at', 'created_at', 'updated_at']))->toBeTrue()
        ->and(Schema::hasColumns('stock_movements', ['id', 'product_uuid', 'delta', 'reason', 'reference', 'actor_id', 'created_at']))->toBeTrue();

    $columns = collect(Schema::getColumns('stock_items'))->keyBy('name');

    expect($columns['product_uuid']['type'])->toBe('char(36)');
});

it('has no foreign key to products', function () {
    $tables = ['stock_items', 'stock_reservations', 'stock_movements'];

    foreach ($tables as $table) {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            expect($foreignKey['foreign_table'])->not->toBe('products');
        }
    }
});

<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\IdentityKit;

/**
 * @return array<string, mixed>
 */
function cartSchemaRow(int $userId, ?int $activeKey, string $status = 'active'): array
{
    return [
        'uuid' => Str::uuid()->toString(),
        'user_id' => $userId,
        'status' => $status,
        'active_key' => $activeKey,
        'created_at' => now(),
        'updated_at' => now(),
    ];
}

it('runs the cart migrations twice without error', function () {
    $this->artisan('migrate')->assertExitCode(0);
    $this->artisan('migrate')->assertExitCode(0);

    foreach (glob(app_path('Modules/Cart/database/migrations/*.php')) as $file) {
        $migration = require $file;
        $migration->up();
    }

    expect(Schema::hasTable('carts'))->toBeTrue()
        ->and(Schema::hasTable('cart_items'))->toBeTrue();
});

it('has the exact columns', function () {
    expect(Schema::hasColumns('carts', ['id', 'uuid', 'user_id', 'status', 'active_key', 'created_at', 'updated_at']))->toBeTrue()
        ->and(Schema::hasColumns('cart_items', ['id', 'cart_id', 'product_uuid', 'quantity', 'unit_price_amount', 'currency', 'created_at', 'updated_at']))->toBeTrue();
});

it('has foreign keys only to users and carts, cascading from carts to items', function () {
    $cartKeys = collect(Schema::getForeignKeys('carts'))->pluck('foreign_table')->all();
    $itemKeys = Schema::getForeignKeys('cart_items');

    expect($cartKeys)->toBe(['users'])
        ->and($itemKeys)->toHaveCount(1)
        ->and($itemKeys[0]['foreign_table'])->toBe('carts')
        ->and(strtolower((string) $itemKeys[0]['on_delete']))->toBe('cascade');
});

it('allows only one active cart per user through the unique active key', function () {
    $user = IdentityKit::user();
    DB::table('carts')->insert(cartSchemaRow($user->id, $user->id));

    expect(fn () => DB::table('carts')->insert(cartSchemaRow($user->id, $user->id)))->toThrow(QueryException::class);

    DB::table('carts')->insert(cartSchemaRow($user->id, null, 'converted'));
    DB::table('carts')->insert(cartSchemaRow($user->id, null, 'converted'));

    expect(DB::table('carts')->where('user_id', $user->id)->count())->toBe(3);
});

it('keeps one line per product in a cart', function () {
    $user = IdentityKit::user();
    $cartId = DB::table('carts')->insertGetId(cartSchemaRow($user->id, $user->id));
    $productUuid = Str::uuid()->toString();
    $line = [
        'cart_id' => $cartId, 'product_uuid' => $productUuid, 'quantity' => 1,
        'unit_price_amount' => 100, 'currency' => 'USD', 'created_at' => now(), 'updated_at' => now(),
    ];

    DB::table('cart_items')->insert($line);

    expect(fn () => DB::table('cart_items')->insert($line))->toThrow(QueryException::class);
});

it('deletes the lines when the cart is deleted', function () {
    $user = IdentityKit::user();
    $cartId = DB::table('carts')->insertGetId(cartSchemaRow($user->id, $user->id));
    DB::table('cart_items')->insert([
        'cart_id' => $cartId, 'product_uuid' => Str::uuid()->toString(), 'quantity' => 1,
        'unit_price_amount' => 100, 'currency' => 'USD', 'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('carts')->where('id', $cartId)->delete();

    expect(DB::table('cart_items')->count())->toBe(0);
});

# DATABASE

## 1. Environment
MariaDB native, `127.0.0.1:3306`, database `system_e_commerce_db`, charset utf8mb4, engine InnoDB. Tests use `system_e_commerce_test` (never the main DB). Credentials only in the untracked `.env`. The `mysql` CLI may be missing: use `php artisan db:show` and `scripts/db-create-test.php`.

## 2. Ownership rules
1. A table belongs to exactly one module. Only that module reads or writes it.
2. Table names are unique and match `docs/MODULES.md` exactly.
3. No cross-module foreign keys, relations or joins. Store the other entity's `uuid` as `CHAR(36)`. Exception: FK to `users.id`.
4. FKs inside one module are recommended.
5. Never alter another module's table, even to "fix" it. Raise it in `docs/DECISIONS.md`.

## 3. Migrations
- Live in `app/Modules/<Module>/database/migrations/`, created with `php artisan make:migration <name> --path=app/Modules/<Module>/database/migrations`. The provider calls `loadMigrationsFrom`.
- Never edit an applied migration; add a new one. `down()` must work.
- Order by timestamp: a migration that references `users` comes after the users migration.
- No `migrate:fresh`, `migrate:reset`, `db:wipe` on the main DB. Tests migrate the test DB through the test runner.
- Seeders are idempotent (`updateOrCreate` / `firstOrCreate`), data from config.

## 4. Naming
| Thing | Rule | Example |
|---|---|---|
| Table | snake_case plural | `cart_items` |
| Column | snake_case | `created_at` |
| Primary key | `id BIGINT UNSIGNED AUTO_INCREMENT`; user-facing entities also `uuid CHAR(36) UNIQUE` used in URLs (`getRouteKeyName()` returns `uuid`) | `orders.uuid` |
| Reference to another module | `<entity>_uuid` | `product_uuid` |
| Reference to users | `<role>_id` | `user_id`, `created_by` |
| Unique index | `uq_<table>_<cols>` | `uq_products_sku` |
| Index | `idx_<table>_<cols>` | `idx_orders_user_id` |

## 5. Types
| Data | Type |
|---|---|
| UUID | `CHAR(36)` v4. Use `HasVersion4Uuids` if the framework provides it, otherwise set `Str::uuid()` in `creating`. `HasUuids` default is v7: do not use it for `uuid` |
| Enum-like (status, type, reason) | `VARCHAR(n)` validated against `config/shop.php`. No SQL `ENUM`, no PHP enums for configurable lists |
| Money | `BIGINT` minor units plus `currency CHAR(3)` |
| Timestamps | `timestamps()` in UTC; soft delete `deleted_at` where listed |
| JSON | `JSON` (never secrets) |

## 6. Soft delete
Only where `docs/MODULES.md` lists `deleted_at`. Unique columns stay unique including soft-deleted rows. Lists hide deleted by default; admin lists support `deleted=without|with|only`.

## 7. Access from PHP
- Eloquent or query builder with bindings only. No `DB::raw` or `whereRaw` with user input. Sort and filter columns come from a whitelist (`config/tables.php`).
- Escape `LIKE` wildcards (`%`, `_`, `\`) from user input (`App\Support\Sql::escapeLike`).
- Multi-row changes, counters and anything on the required path run in `DB::transaction`. Stock-like rows use `lockForUpdate()` ordered by key to avoid deadlocks.
- Mass assignment: explicit `$fillable`, never `$guarded = []`. Hide secrets with `$hidden`.
- No N+1: eager load inside the module; assert with `Model::preventLazyLoading()` outside production.

## 8. Retention
Time-based cleanup is an artisan command per module (manual or scheduled), retention from config. History and audit are append-only; prune only if the prompt says so.

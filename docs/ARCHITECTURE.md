# ARCHITECTURE

## 1. Shape
One Laravel 13 application. Eleven modules under `app/Modules/`. One shared MariaDB database. Livewire full-page components render both storefront and admin panel inside AdminLTE 3. No HTTP between modules: they call Contracts in-process and react to domain events.

```
browser -> routes (web) -> Livewire page -> module Service -> Eloquent -> MariaDB
                                   |             |
                                   |             +-> Contract of another module (required path)
                                   +-> domain event -> queued listener (best-effort path)
```

## 2. Module layout
```
app/Models/User.php                shared kernel (auth, notifiable, FK target)
app/Modules/<Module>/
  Contracts/        interfaces and readonly DTOs other modules may use
  Models/
  Services/         business rules and transactions, implement Contracts
  Events/           domain events
  Listeners/        only where the module reacts to others (History, Notification, Order)
  Livewire/Admin/   admin pages
  Livewire/Shop/    public and member pages
  Policies/
  Console/          artisan commands
  Providers/<Module>ServiceProvider.php   bindings, routes, views, migrations, livewire, commands
  routes.php
  resources/views/  namespace <module>:: (e.g. catalog::admin.products.index)
  database/migrations/  database/factories/  database/seeders/
tests/Feature/<Module>/   tests/Unit/<Module>/
app/Support/        cross-cutting helpers (Money, HasTableQuery trait, Config helpers)
config/shop.php  config/permissions.php  config/tables.php  config/history.php
```
Provider classes are listed in `bootstrap/providers.php`. Migrations load from the module folder.

## 3. Hard boundaries
1. A module owns its tables. Only that module's classes read or write them.
2. No Eloquent relation, join or SQL foreign key across modules. Reference another module's entity by `uuid` (`CHAR(36)`). Exception: `users.id` (shared kernel).
3. Cross-module data comes from the owner's Contract (DTOs, never Eloquent models).
4. A module's Livewire views never call another module's Service directly; they call their own module Service, which calls the Contract.
5. Dashboard reads only `StatsProvider` Contracts.
6. Each module's tests run alone with fakes bound to the Contracts it needs.

## 4. Identity and access flow
1. Fortify handles login, register, logout. Session driver `database`. Login rate limit per email + IP from config.
2. Middleware `EnsureUserActive` runs on every authenticated route: deleted user, `suspended` or `banned` status logs the session out and redirects to login.
3. Roles and permissions are Spatie. `Gate::before` grants everything to `super_admin`. Routes use `can:<permission>`; Livewire actions call `$this->authorize('<permission>')`.
4. Own-resource checks (order owner, own comment) live in Policies.
5. Permission catalog and role mapping are config only (`config/permissions.php`), seeded idempotently.

## 5. Communication kinds
| Kind | Mechanism | Failure means |
|---|---|---|
| Required for correctness (stock reserve, payment create, order status on payment) | Contract call inside one `DB::transaction` | Exception, whole transaction rolled back, user sees a clean error |
| Enrichment (stock badge on catalog, dashboard blocks) | Contract call wrapped by the caller | Value `null`, UI shows "unavailable", page still renders |
| Best-effort side effect (history, notification) | Domain event, queued listener (`ShouldQueue`, `afterCommit`) | Logged, retried by queue, request unaffected |

## 6. Checkout flow (D-M004)
```
CheckoutService::place(user, shipping, note, idempotencyKey)       [Order module]
 1. replay: order for (user, idempotencyKey) exists -> return it
 2. Cart contract: active cart items (empty -> validation error)
 3. Catalog contract: lookup(uuids) -> active, not deleted, current price (cart price is ignored)
 4. cap: user has MAX_OPEN_ORDERS_PER_USER awaiting_payment orders -> conflict
 5. DB::transaction
      insert order (awaiting_payment) + items with price snapshot
      Inventory contract: reserve(order_uuid, items)   all-or-nothing, row locks, TTL
      Payment contract: create(order_uuid, user, amount, currency)   idempotent by order_uuid
 6. after commit: OrderPlaced, PaymentCreated, StockReserved events (best-effort)
Payment result (Payment module calls OrderPaymentHandler, implemented by Order, same transaction):
  paid    : order=paid, Inventory commit, Cart clear, OrderPaid
  failed or expired : order=cancelled, Inventory release, OrderCancelled
  refunded: order=cancelled (reason refunded), no restock (manual stock adjust)
Owner cancel (only awaiting_payment): order=cancelled, Payment cancelByOrder, Inventory release
```
A failure anywhere in step 5 rolls back order, reservation and payment together. No compensation code, no callback retry script.
Reservations expire after a config TTL; `inventory:release-expired` (scheduled every minute) releases them. `payments:expire` expires pending payments and calls the same handler.

## 7. Dependency matrix (caller -> Contracts it uses)
| Module | Uses |
|---|---|
| Identity | none |
| Catalog | Inventory (availability, optional enrichment) |
| Inventory | none |
| Cart | Catalog, Inventory |
| Payment | OrderPaymentHandler (defined in Payment, implemented in Order) |
| Order | Cart, Catalog, Inventory, Payment |
| History | listens to Identity, Inventory, Payment, Order, Content, Comment events |
| Notification | listens to Order, Payment events |
| Content | none |
| Comment | Content (ArticleDirectory), Catalog (ProductCatalog) |
| Dashboard | StatsProvider of Order, Payment, Catalog, Inventory, Content, Comment |
Payment <-> Order is the only two-way link and is resolved by the `OrderPaymentHandler` Contract. No other cycles.

## 8. Build order
Foundation, Identity, Catalog, Inventory, Cart, Payment + Order, History + Notification, Content, Comment, Dashboard, Finalize.

## 9. Runtime
`php artisan serve` (port 8000), `php artisan queue:work` for queued listeners, `php artisan schedule:work` for expiry commands. Local only, bind 127.0.0.1.

# MODULES

Source of truth for tables, pages, permissions, contracts and events. Names are exact. Namespaces `App\Modules\<Module>\...`. Route names use dots (`admin.products.index`). All pages are Livewire full-page components unless stated.

Statuses, enums and limits are in `config/shop.php` (keys shown in brackets). Permission catalog and roles are in `config/permissions.php` and are seeded once by prompt 01 for ALL modules (D-M003).

## Summary
| Module | Tables | Contract(s) exposed | Events |
|---|---|---|---|
| Identity | users, roles, permissions, model_has_roles, model_has_permissions, role_has_permissions, user_follows, sessions, activity_log, password_reset_tokens | none | UserRegistered, UserStatusChanged |
| Catalog | products, product_categories | ProductCatalog, CatalogStats | ProductChanged |
| Inventory | stock_items, stock_reservations, stock_movements | Inventory, InventoryStats | StockAdjusted, StockReserved, StockCommitted, StockReleased |
| Cart | carts, cart_items | CartReader | CartCleared |
| Payment | payments, payment_events | Payments, OrderPaymentHandler (defined here), PaymentStats | PaymentCreated, PaymentPaid, PaymentFailed, PaymentExpired, PaymentCancelled, PaymentRefunded |
| Order | orders, order_items | OrderStats | OrderPlaced, OrderPaid, OrderCancelled, OrderStatusChanged |
| History | history_events | none | none |
| Notification | notifications (Laravel database notifications) | none | none |
| Content | articles | ArticleDirectory, ContentStats | ArticlePublished |
| Comment | comments | CommentStats | CommentPosted |
| Dashboard | none | none | none |
Spatie tables come from the package migrations. `jobs`, `cache`, `sessions` come from the Laravel skeleton.

## Identity
- Users: `users(id, uuid unique, name, email unique, password, status, remember_token, created_at, updated_at, deleted_at)`. Status [`user_statuses`]: active, suspended, banned. Soft delete. `follows`: `user_follows(id, follower_id FK users, followed_id FK users, created_at, unique(follower_id, followed_id))`, self-follow rejected.
- Roles seeded: `super_admin` (all), `admin` (all but `permissions.manage`), `member` (none). System roles are immutable [`system_roles`]. An actor may only assign roles whose permissions are a subset of their own.
- Permissions (identity): users.read, users.create, users.update, users.delete, users.restore, users.purge, users.status, users.roles, roles.read, roles.manage, permissions.read, permissions.manage, audit.read, sessions.read, sessions.revoke, presence.read.
- Permissions (other modules, seeded here): products.read, products.create, products.update, products.delete, products.restore, categories.manage, stock.read, stock.adjust, carts.read, payments.read, payments.refund, orders.read, orders.manage, history.read, articles.read, articles.create, articles.update, articles.publish, articles.delete, articles.restore, comments.moderate, dashboard.read.
- Public pages: GET /login, GET /register (Fortify actions, AdminLTE auth views), POST /logout.
- Member pages (login only): GET /me (profile + password), GET /users/{user:uuid} (card, follow/unfollow).
- Admin pages: GET /admin/users (users.read), /admin/users/create (users.create), /admin/users/{user}/edit (users.update; status needs users.status, roles need users.roles; delete users.delete; restore users.restore; purge users.purge), /admin/roles (roles.read; edit needs roles.manage), /admin/permissions (permissions.read; assign needs permissions.manage), /admin/audit (audit.read), /admin/sessions (sessions.read; revoke needs sessions.revoke; online list needs presence.read).
- Rules: password policy from config; update screens never set password or status except the dedicated actions; login lockout per sha256(email)+ip from config; generic login error; presence = sessions with `last_activity` within [`presence_window_seconds`].

## Catalog
- `product_categories(id, uuid unique, name, slug unique, parent_id self FK null, created_at, updated_at, deleted_at)`.
- `products(id, uuid unique, sku unique, name, slug unique, description, price_amount BIGINT, currency CHAR(3), category_id FK null, status, image_path null, created_by FK users, updated_by FK users, created_at, updated_at, deleted_at)`. Status [`product_statuses`]: draft, active, inactive.
- Public pages: GET / and GET /products (active only, search, category, sort), GET /products/{product:uuid} (detail + comments thread slot).
- Admin pages: GET /admin/products (products.read, `deleted=with|only`), /admin/products/create (products.create), /admin/products/{product}/edit (products.update; delete products.delete; restore products.restore), /admin/categories (categories.manage).
- Contract `ProductCatalog`: `lookup(array $uuids): array<string, ProductSnapshot>` (uuid, name, sku, priceAmount, currency, status, deleted; size cap [`lookup_max_uuids`]), `exists(array $uuids): array<string,bool>`. `CatalogStats`: `stats(): array{total,active,inactive,draft,deleted,categories}`.

## Inventory
- `stock_items(product_uuid CHAR(36) PK, quantity_on_hand INT UNSIGNED, quantity_reserved INT UNSIGNED, low_stock_threshold INT UNSIGNED NULL, created_at, updated_at)`.
- `stock_reservations(id, uuid unique, order_uuid, product_uuid, quantity, status, expires_at, created_at, updated_at, index order_uuid)`. Status [`reservation_statuses`]: reserved, committed, released, expired.
- `stock_movements(id, product_uuid, delta INT, reason, reference null, actor_id FK users null, created_at)`. Reasons [`adjust_reasons`]: restock, correction, damage, return, order_commit.
- Available = on_hand - reserved, never negative. Invariant reserved <= on_hand enforced in the Service inside transactions with `lockForUpdate` ordered by product_uuid.
- Admin pages: GET /admin/stock (stock.read), /admin/stock/{productUuid} (stock.read; movements list; adjust needs stock.adjust).
- Contract `Inventory`: `availability(array $productUuids): array<string,int>` (unknown = 0, cap [`availability_max_uuids`]); `reserve(string $orderUuid, array $items): void` (all-or-nothing, idempotent by order_uuid, duplicate product = validation error, insufficient = `InsufficientStockException` with fields product_uuid and available; expires_at = now + [`reservation_ttl_seconds`]); `commit(string $orderUuid): void`; `release(string $orderUuid): void`. `InventoryStats`: `{items, low_stock, out_of_stock}` (low = available <= threshold or [`low_stock_default_threshold`] and > 0).
- Command `inventory:release-expired` (scheduled every minute): reserved rows past expires_at become expired and stock is freed.

## Cart
- `carts(id, uuid unique, user_id FK users, status, active_key BIGINT UNSIGNED NULL unique, created_at, updated_at)`: `active_key = user_id` while active, NULL otherwise (one active cart per user). Status [`cart_statuses`]: active, converted, abandoned.
- `cart_items(id, cart_id FK cascade, product_uuid, quantity, unit_price_amount BIGINT, currency CHAR(3), created_at, updated_at, unique(cart_id, product_uuid))`.
- Pages (login only): GET /cart (add via product pages, update quantity, remove, clear). Admin: GET /admin/carts (carts.read).
- Add and update check `ProductCatalog` (active, not deleted) and `Inventory::availability`. Price is a snapshot; Order re-prices at checkout. Quantity max [`cart_max_quantity`], max distinct items [`cart_max_items`].
- Contract `CartReader`: `activeItems(int $userId): array<CartLine>` (productUuid, quantity, unitPriceAmount, currency), `clear(int $userId): void`.

## Payment
- `payments(id, uuid unique, order_uuid unique, user_id FK users, amount BIGINT, currency CHAR(3), status, provider, provider_reference, expires_at, paid_at null, refunded_at null, refund_reason null, created_at, updated_at)`. Status [`payment_statuses`]: pending, paid, failed, expired, cancelled, refunded. Provider [`payment_provider`] default `simulated`, reference `sim_` + random hex. Amount > 0.
- `payment_events(id, payment_id FK cascade, event, details JSON, created_at)`.
- Pages: owner action on the order detail page: Pay, and Fail only when [`payment_simulate_failure`] is true (policy: owner only, status pending). Admin: GET /admin/payments (payments.read), refund action (payments.refund, only from paid).
- Contract `Payments`: `create(string $orderUuid, int $userId, int $amount, string $currency): PaymentDto` (idempotent by order_uuid: same amount and currency returns the existing one, otherwise conflict), `cancelByOrder(string $orderUuid): void` (pending to cancelled), `byOrder(string $orderUuid): ?PaymentDto`. `PaymentStats`: `{total, by_status, paid_amount, currency}`.
- Contract `OrderPaymentHandler` (defined in Payment, implemented in Order): `handle(string $orderUuid, string $result): void` with result in paid, failed, expired, refunded. Called inside the Payment transaction.
- Command `payments:expire` (scheduled every minute): pending past expires_at become expired and call the handler.

## Order
- `orders(id, uuid unique, user_id FK users, status, total_amount BIGINT, currency CHAR(3), idempotency_key CHAR(36), shipping_address JSON, note null, cancel_reason null, created_at, updated_at, cancelled_at null, unique(user_id, idempotency_key))`. Status [`order_statuses`]: awaiting_payment, paid, processing, shipped, completed, cancelled. Transition map [`order_transitions`]; manual transitions only paid->processing, processing->shipped, shipped->completed; terminal: completed, cancelled; anything else is a conflict. Cap [`max_open_orders_per_user`].
- `order_items(id, order_id FK cascade, product_uuid, product_name, sku, unit_price_amount BIGINT, quantity, line_total_amount BIGINT)`.
- Member pages (login only): GET /checkout (form holds a locked idempotency key generated on mount), GET /orders (own list), GET /orders/{order:uuid} (owner or orders.read; pay, cancel while awaiting_payment).
- Admin pages: GET /admin/orders (orders.read), /admin/orders/{order} (orders.read; status change needs orders.manage).
- `OrderStats`: `{total, by_status, revenue_amount, currency}` (revenue = paid, processing, shipped, completed).

## History
- `history_events(id, event_uuid unique, user_id FK users null, actor_id FK users null, subject_type, subject_uuid, action, details JSON, occurred_at, created_at)`. `user_id` = the user the event concerns (used by `/me/history`). Subject types and actions in `config/history.php`. Append-only: no update or delete anywhere.
- Queued listeners (idempotent by `event_uuid` carried on the event) write rows for events listed in `config/history.php`.
- Pages: GET /me/history (login only), GET /admin/history (history.read).

## Notification
- Uses Laravel database notifications (`notifications` table, `Notifiable` on User). Notification classes live in `app/Modules/Notification/Notifications/`: OrderPlacedNotification, OrderPaidNotification, OrderCancelledNotification, OrderStatusChangedNotification, PaymentFailedNotification, PaymentRefundedNotification. Types list in `config/shop.php` [`notification_types`].
- Queued listeners on OrderPlaced, OrderPaid, OrderCancelled, OrderStatusChanged, PaymentFailed, PaymentRefunded.
- Pages (login only): GET /notifications (list, mark read, mark all read, delete), unread count badge in the AdminLTE navbar.

## Content
- `articles(id, uuid unique, title, slug unique, summary, body, cover_path null, status, author_id FK users, published_at null, created_by FK users, updated_by FK users, created_at, updated_at, deleted_at)`. Status [`article_statuses`]: draft, published, archived. Body is Markdown rendered with `Str::markdown` using `html_input => strip` and `allow_unsafe_links => false`.
- Public pages: GET /articles (published), GET /articles/{article:slug} (+ comments thread slot).
- Admin pages: GET /admin/articles (articles.read), /admin/articles/create (articles.create), /admin/articles/{article}/edit (articles.update; publish and unpublish need articles.publish; delete articles.delete; restore articles.restore).
- Contract `ArticleDirectory`: `exists(array $uuids): array<string,bool>`. `ContentStats`: `{total, published, draft, archived, deleted}`.

## Comment
- `comments(id, uuid unique, entity_type, entity_uuid, user_id FK users, parent_id self FK null, body, status, created_at, updated_at, deleted_at)`. Entity types [`comment_entity_types`]: article, product. Status [`comment_statuses`]: visible, hidden. One reply level only. Body max [`comment_max_length`]. Flood limit per user [`comment_flood_limit`] via `RateLimiter`.
- Livewire component `comment::thread` (props `entityType`, `entityUuid`, both `#[Locked]`) is embedded in product and article pages. Posting verifies the target through `ProductCatalog::exists` or `ArticleDirectory::exists`. Author can edit and delete own comments (Policy).
- Admin pages: GET /admin/comments (comments.moderate; hide/show, delete). `CommentStats`: `{total, visible, hidden, deleted}`.

## Dashboard
- No tables. GET /admin/dashboard (dashboard.read) is the AdminLTE home for staff. Blocks: sales {orders, payments}, stock {stock, catalog}, content {articles, comments}. Each block is the provider array unchanged or `null` when its provider throws; failed block names are shown in a warning alert.
- Cache with Laravel `Cache` per provider, TTL [`stats_cache_ttl`] (0 disables), never cache failures.
- `StatsProvider` Contract: `name(): string`, `stats(): array`; each module binds its provider with the tag `module.stats`.

## Inventory build notes (prompt 03)
- Events use subject type `stock`: StockAdjusted carries the product uuid, StockReserved, StockCommitted and StockReleased carry the order uuid.
- `InventoryService` also exposes `adjust(productUuid, delta, reason, actorId): StockItem` and `expire(?array $productUuids): int` (not part of the `Inventory` contract). The `order_commit` reason is rejected by manual adjust.
- Config: `shop.inventory.reservation_max_items`, `shop.inventory.commit_reason`, `shop.inventory.statuses`. Table lists `stock` and `movements` are in `config/tables.php`.
- `stock_reservations` has unique(order_uuid, product_uuid) and index(status, expires_at).
- Admin pages build notes: routes `admin.stock.index` and `admin.stock.show` (productUuid must be a uuid; 404 unless the product has stock or exists in the Catalog). Adjust action requires `stock.adjust`; `order_commit` is not offered as a manual reason.
- Catalog enrichment: the Livewire component `inventory.availability-badge` (prop `productUuid`, locked) is embedded in `catalog/product-list` and `catalog/product-show` views. It calls `Inventory::availability` and renders nothing when the call throws.

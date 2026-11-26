# MEMORY (handoff file)

Shared brain between sessions, accounts and models. Keep it short, current and factual. Update it at the end of every stage.

Last updated: 2026-11-26, prompt 08 (comment) stage 2 done.

## 1. How to use
- Start of session: read this file, run the verify commands in section 3, fix any mismatch here first.
- End of session: update section 2 (status), add one entry to section 5, set section 4 (next step).
- Keep only facts you can prove with a command or a commit. Mark guesses as `(unverified)`.
- Never write secrets here.

## 2. Status
Legend: not-started, in-progress, done (STAGE CHECK green and pushed), blocked.
| # | Prompt | Module | Branch | Status | Last commit message |
|---|---|---|---|---|---|
| 00 | 00-foundation.md (2 stages) | app skeleton, packages, layout, support | feat/00-foundation | done | feat(foundation): add config catalog, support classes, and layout wiring |
| 01 | 01-identity.md (3 stages) | Identity | feat/01-identity | done | feat(identity): add admin users, roles, permissions, audit, and sessions |
| 02 | 02-catalog.md (3 stages) | Catalog | feat/02-catalog | done | feat(catalog): add public product pages |
| 03 | 03-inventory.md (3 stages) | Inventory | feat/03-inventory | done | feat(inventory): show availability on public product pages |
| 04 | 04-cart.md (2 stages) | Cart | feat/04-cart | done | feat(cart): add cart pages and admin cart list |
| 05 | 05-checkout.md (4 stages) | Payment + Order | feat/05-checkout | done | feat(order): add scheduled expiry and checkout journey test |
| 06 | 06-history-notification.md (2 stages) | History, Notification | feat/06-history-notification | done | feat(notification): add order and payment notifications |
| 07 | 07-content.md (2 stages) | Content | feat/07-content | done | feat(content): add public and admin article pages |
| 08 | 08-comment.md (2 stages) | Comment | feat/08-comment | done | feat(comment): add page embeds and moderation |
| 09 | 09-dashboard.md (2 stages) | Dashboard | feat/09-dashboard | done | feat(dashboard): add admin dashboard page |
| 10 | 10-finalize.md (2 stages) | journey test, README, hardening | feat/10-finalize | in-progress | test: add journey tests and authorization matrix |
Prompt files `NN-<name>.md` live in `prompts/` and are git-ignored. Branches are chained: each prompt branches from the previous prompt branch; the owner merges `feat/finalize` into `main` at the end. They may be missing on a fresh clone: ask the owner.
Run order: 00, 01, 02, 03, 04, 05, 06, 07, 08, 09, 10. Build providers before callers.

## 3. Verify commands (copy-paste from repo root)
```
git rev-parse --abbrev-ref HEAD ; git status --porcelain ; git log --oneline -10
git branch -a
ls -1 app/Modules
php artisan route:list --except-vendor
php artisan test
```

## 4. Next step

Prompt 10 stage 1 done on branch feat/10-finalize. Next: prompt 10 stage 2 (hardening review with fixes, README.md rewrite, sync docs/MODULES.md and docs/MEMORY.md, mark all prompts done).

## 5. Session log (newest first)
Format: `date | account/model | what was done | commits | result | blockers`
- 2026-11-26 | claude | prompt 10 stage 1: journey tests (customer, admin, content, failure journeys), authorization matrix tests (auth and can middleware on every route, no duplicate route names, admin GET routes as guest, member and super_admin), DemoDataSeeder (idempotent, leaves users alone) and DatabaseSeeder wiring, Pest RefreshDatabase for Journeys (full suite 753 passed, skipped 0) | test: add journey tests and authorization matrix | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 09 stage 1: DashboardService (forBlock sales, stock, content, summary; per-provider try/catch with warnings and logged class only; Laravel Cache per provider with shop.stats_cache_ttl, 0 disables, failures never cached; refresh), DashboardServiceProvider using the module.stats tag, config shop.dashboard, FakeStatsProvider, tests (red 12 failed, green 17 passed, skipped 0) | feat(dashboard): add stats aggregation service and cache | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 09 stage 2: Dashboard Livewire page at GET /admin/dashboard (dashboard.read, small boxes with Money, orders by status, warning alert, refresh action, error state), StaffAwareLoginResponse (staff with dashboard.read to /admin/dashboard, others to /), menu entry, tests | feat(dashboard): add admin dashboard page | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 08 stage 2: comment::thread embedded in the product and article detail views (inside the single Livewire root; a failing CommentService renders the generic message and the host page stays 200), admin CommentIndex at /admin/comments (comments.moderate; filters status, entity_type, deleted; hide, show, delete; actions re-authorize), routes in the Comment module, Comments menu entry, tests for embeds and admin pages (guest redirect, member and non-permitted 403, moderator 200), obsolete catalog test about the missing comments slot replaced (full suite 709 passed, skipped 0) | feat(comment): add page embeds and moderation | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 08 stage 1: comments migration (entity_uuid without FK, self FK parent_id, soft delete, index on entity_type, entity_uuid, status), Comment model (uuid, activitylog whitelist), CommentService (post with target check, one reply level, length, flood limit; editOwn, deleteOwn, hide, show, deleteByModerator), CommentPolicy, CommentPosted, CommentStats (module.stats tag), CommentServiceProvider, comment::thread Livewire component with locked props and escaped bodies, config tables.comments, tests (full suite 694 passed, skipped 0) | feat(comment): add schema, comment service, and thread component | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 07 stage 1: articles migration, Article model (uuid, unique slug incl. soft-deleted, activitylog without body), ArticlePresenter (sanitized markdown), ArticleService (create, update, publish, unpublish, archive, delete, restore, uploadCover), ArticleDirectory contract and service, ContentStats, ArticlePublished, ContentServiceProvider, config shop.article and tables.articles, tests (red 32 failed, green 32 passed, skipped 0) | feat(content): add schema, article service, and article directory contract | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 07 stage 2: public pages /articles and /articles/{slug} (published only, staff preview), admin list and form with publish, unpublish, archive, delete, restore, cover upload and sanitized markdown preview, menu entry, tests | feat(content): add public and admin article pages | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 06 stage 2: notifications table migration (Laravel database notifications), six notification classes on a shared base (type from shop.notification_types, title, body, data with order uuid, amount, currency, event_uuid), NotifyUser listener (idempotent by event_uuid, missing user skipped and logged, failures logged), NotificationServiceProvider queueing the listener for existing classes only, config/notification.php (events, tries, poll_seconds default 0), NotificationList (/notifications, unread filter, mark read, mark all read, delete, other ids 404) and UnreadBadge Livewire component in the storefront navbar, menu entry, layout probe test made safe for the real table (full suite 562 passed, skipped 0) | feat(notification): add order and payment notifications | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 06 stage 1: history_events migration, append-only HistoryEvent model (update and delete throw), RecordHistory listener (insertOrIgnore on event_uuid, secret keys stripped, failures logged), HistoryServiceProvider registering config events that exist and queueing the listener with failures logged, config/history.php and tables.history, MyHistory (/me/history) and HistoryIndex (/admin/history, history.read) pages, menu entries, tests (history suite 35 passed, full suite 542 passed, skipped 0) | feat(history): add append-only history events and pages | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 05 stage 4: scheduled inventory:release-expired and payments:expire every minute with withoutOverlapping in routes/console.php, schedule tests, expiry-through-command test, sequential race tests (last unit, idempotent replay, double pay, pay after cancel, cancel after paid), register to paid journey test, doc notes (green 507 passed, skipped 0) | feat(order): add scheduled expiry and checkout journey test | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 05 stage 3: member pages CheckoutPage (form object, locked idempotency key, domain errors mapped to fields or flash), OrderList (status filter), OrderShow (pay, fail only with the config flag, cancel while awaiting_payment), admin OrderIndex (search, status, date range), OrderDetail (status change needs orders.manage), PaymentIndex (status filter, refund needs payments.refund), routes with can middleware, menu entries, checkout route name, views, tests (green 499 passed, skipped 0) | feat(order): add checkout, order, and payment pages | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 05 stage 2: orders and order_items migrations, Order and OrderItem models, CheckoutService (replay, cart, catalog re-price, open-orders cap, one transaction with reserve and payment create), OrderPaymentHandlerImpl bound to the Payment contract, OrderService (cancelByOwner, changeStatus, filtered lists), OrderPolicy, PaymentPolicy, four order events, OrderStats, OrderServiceProvider, config lists, tests (green 464 passed, skipped 0) | feat(order): add schema, checkout transaction, and payment handler | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 05 stage 1: payments and payment_events migrations, Payment and PaymentEvent models (uuid, activitylog), Payments and OrderPaymentHandler contracts, PaymentDto, PaymentService (create, byOrder, cancelByOrder, pay, fail, expirePending, refund), six payment events, PaymentStats, payments:expire command, PaymentServiceProvider, config shop.payment and tables.payments, tests (red 31 failed, green 31 passed, skipped 0) | feat(payment): add schema, payment service, and contracts | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 04 stage 2: /cart page (CartPage), cart.add-button on public product list and detail, admin carts list (CartIndex, carts.read), menu entries, tests (red 23 failed, green 59 cart tests passed, skipped 0) | feat(cart): add cart pages and admin cart list | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 04 stage 1: carts and cart_items migrations (active_key unique), Cart and CartItem models, CartService (getOrCreateActive, add, setQuantity, remove, clear, activeItems), CartReader contract with CartLine DTO, CartCleared event, CartServiceProvider, config lists, tests (red 34 failed 2 passed, green 36 passed, skipped 0) | feat(cart): add schema, cart service, and cart reader contract | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 03 stage 3: Livewire child component inventory.availability-badge (Inventory contract only, any exception renders no badge) embedded in the Catalog public list and detail views, tests (present, low, out, contract throws) | feat(inventory): show availability on public product pages | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 03 stage 2: admin StockIndex (list, low-stock filter, product names via ProductCatalog::lookup with uuid fallback) and StockShow (movements, adjust form gated by stock.adjust), routes with can middleware, menu entry, views, tests (green 328 passed, skipped 0) | feat(inventory): add admin stock pages | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 03 stage 1: stock_items, stock_reservations, stock_movements migrations, models, Inventory contract, InventoryService (reserve, commit, release, adjust, expire), InsufficientStockException, four events, inventory:release-expired command, InventoryStats, InventoryServiceProvider, config lists, tests (green, skipped 0) | feat(inventory): add schema, reservation service, and inventory contract | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 02 final audit: moved hardcoded product status roles and public sortable fields into config/shop.php, added config test | fix(catalog): read status roles from config | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 02 stage 3: public ProductList (/ and /products, active only, search, category filter, name/price sort, pagination) and ProductShow (/products/{product:uuid}, 404 for draft/inactive/deleted, staff with products.read preview) on storefront layout, welcome route moved to Catalog, tests (green 283 passed, skipped 0) | feat(catalog): add public product pages | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 02 stage 2: admin ProductIndex, ProductForm (create, update, status, image upload, delete, restore), CategoryIndex, catalog routes with can middleware, AdminLTE menu entries with can, views, tests (green 95 catalog tests passed, skipped 0) | feat(catalog): add admin products and categories | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 02 stage 1: catalog migrations, Product and Category models (uuid, slug, activitylog), ProductService, CategoryService, ProductCatalog contract and service, CatalogStats, ProductChanged, CatalogServiceProvider, factories, config lists, tests (green 222 passed, skipped 0) | feat(catalog): add schema, services, and product catalog contract | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 01 stage 3: admin users (list, form, status, roles, delete/restore/purge), roles, permissions, audit, sessions with online tab, menu entries with can, tests (green 175 passed, skipped 0) | feat(identity): add admin users, roles, permissions, audit, and sessions | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 01 stage 2: Fortify wiring (register via UserService, authenticateUsing rejects non-active, sha256 login limiter from config), AdminLTE auth views, /me profile and password, /users/{user:uuid} card with follow, tests (green 123 passed, skipped 0) | feat(identity): add authentication, profile, and follow pages | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 01 stage 1: users migration, user_follows, User model, seeders, UserService, FollowService, RoleService, SessionAdminService, events, EnsureUserActive, Gate::before, config/tables.php lists, tests (green 104 passed, skipped 0) | feat(identity): add schema, services, and seeders | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 00 final audit: added authenticated storefront layout test (user name, unread badge, logout form); no other gaps found | fix(foundation): test the authenticated storefront navigation | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 00 stage 2: config catalog, support classes (Money, Sql, exceptions, DomainEvent, StatsProvider, HasTableQuery), AppServiceProvider strict mode, AdminLTE config, layouts::app and layouts::storefront, tests (red 32 failed 10 passed, green 42 passed, skipped 0) | feat(foundation): add config catalog, support classes, and layout wiring | STAGE CHECK green | none
- 2026-11-26 | claude | prompt 00 stage 1: Laravel 13 skeleton, packages (versions in section 8), .env keys, .gitignore, scripts/db-create-test.php, phpunit.xml, Pest smoke test | chore: install laravel 13 with livewire, adminlte, fortify and spatie packages | STAGE CHECK green | none
- docs session | wrote AGENTS.md, docs/ and prompts 00 to 10 for the monolith | none yet | prompts ready, nothing built | none

## 6. Known pitfalls (carried over from the microservice build, do not repeat)
- Nested folders by mistake. Commits on `main`.
- Regex without anchors. `LIMIT` or ids as strings.
- Permission codes that never match a seeded permission.
- Raw exception messages shown to the user.
- Fat Livewire components with SQL and business rules inside.
- Stubs counted as done. Declaring "complete" with no tests.

## 7. Open blockers
None recorded.

## 8. Installed versions (prompt 00, 2026-11-26)
- PHP 8.5.13, Composer version 2.11.3
- jeroennoten/laravel-adminlte 3.16.0
- laravel/fortify 1.44.0
- laravel/framework 13.44.1
- laravel/pail 1.3.0
- laravel/pint 1.32.3
- laravel/tinker 3.2.0
- livewire/livewire 4.4.10
- pestphp/pest 4.7.8
- pestphp/pest-plugin-laravel 4.1.0
- spatie/laravel-activitylog 5.1.5
- spatie/laravel-permission 8.5.0
- Difference from docs/STACK.md section 3: an unpinned composer require pulled jeroennoten/laravel-adminlte 4.x (AdminLTE 4); it was re-pinned to ^3.15 and resolved to 3.16.0.
- adminlte:install --force (basic) published assets and config; the published lang/vendor/adminlte folder was removed because lang/ is outside the allowed paths (the package ships its own translations).
- fortify:install also published the two-factor and passkeys migrations (database/migrations); they are not run yet.
- Spatie permission and activitylog migrations are published but not run yet; prompt 01 owns the migrate step.

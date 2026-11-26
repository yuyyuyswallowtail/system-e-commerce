# MEMORY (handoff file)

Shared brain between sessions, accounts and models. Keep it short, current and factual. Update it at the end of every stage.

Last updated: 2026-11-26, prompt 02 (catalog) done, all 3 stages, final audit applied.

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
| 03 | 03-inventory.md (3 stages) | Inventory | feat/inventory | not-started | |
| 04 | 04-cart.md (2 stages) | Cart | feat/cart | not-started | |
| 05 | 05-checkout.md (4 stages) | Payment + Order | feat/checkout | not-started | |
| 06 | 06-history-notification.md (2 stages) | History, Notification | feat/history-notification | not-started | |
| 07 | 07-content.md (2 stages) | Content | feat/content | not-started | |
| 08 | 08-comment.md (2 stages) | Comment | feat/comment | not-started | |
| 09 | 09-dashboard.md (2 stages) | Dashboard | feat/dashboard | not-started | |
| 10 | 10-finalize.md (2 stages) | journey test, README, hardening | feat/finalize | not-started | |
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
Run prompt 03 (inventory, branch feat/03-inventory from feat/02-catalog).

## 5. Session log (newest first)
Format: `date | account/model | what was done | commits | result | blockers`
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

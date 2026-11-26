# DECISIONS

Status: `accepted` = fixed by the owner. `proposed` = default chosen when writing these docs; agents follow it until the owner edits the row (and the matching prompt). To change one, edit its row and note the date. Do not delete rows.

| ID | Status | Decision | Affects |
|---|---|---|---|
| D-M001 | accepted | Monolith with Laravel, Livewire, AdminLTE 3 and other Laravel-ecosystem packages | all |
| D-M002 | proposed | Modular monolith under `app/Modules/<Module>` with Contracts and events; no cross-module relations or FKs except `users.id` (`App\Models\User` is shared kernel) | all |
| D-M003 | proposed | One config catalog `config/permissions.php` seeded once by prompt 01 for all modules; codes keep the names from the microservice design | all |
| D-M004 | proposed | Checkout is one DB transaction in Order calling Cart, Catalog, Inventory, Payment contracts; payment result calls `OrderPaymentHandler` in the same transaction; no callback retry script | order, payment, inventory, cart |
| D-M005 | proposed | Side effects (history, notifications) are domain events with queued `afterCommit` listeners on the `database` queue | history, notification, all |
| D-M006 | proposed | Auth with Laravel Fortify (headless) plus AdminLTE auth views; Laravel database sessions replace auth-service sessions; sessions admin and presence read the `sessions` table; no password pepper | identity |
| D-M007 | proposed | Spatie laravel-permission for RBAC, spatie laravel-activitylog replaces per-service audit tables | identity, all |
| D-M008 | proposed | Notifications use Laravel's built-in database notifications (no custom table, no `dedupe_key`; listeners are idempotent per event) | notification |
| D-M009 | proposed | UI only in v1: Livewire pages, no REST API, no Node/Vite, assets from the AdminLTE package | all |
| D-M010 | proposed | Sanctum REST API is a possible later stage and needs its own decision row | future |
| D-M011 | proposed | Money is `BIGINT` minor units plus `currency CHAR(3)`; default currency in `config/shop.php` | catalog, cart, order, payment |
| D-M012 | proposed | Statuses and enums are config arrays validated with `Rule::in`, not PHP enums or SQL ENUM | all |
| D-M013 | proposed | Public URLs use `uuid` (v4) route keys; internal joins use `id` | all |
| D-M014 | proposed | Payment v1 uses a simulated provider; failure simulation button only when `payment_simulate_failure` is true | payment |
| D-M015 | proposed | Tests use Pest on a dedicated MariaDB test database | all |
| D-M016 | proposed | Dashboard has no database: `StatsProvider` contracts plus Laravel cache | dashboard |
| D-M017 | proposed | Comments thread is a reusable Livewire component embedded in product and article pages | comment, catalog, content |
| D-M018 | proposed | Public pages use a storefront layout built on the AdminLTE package assets; staff and member pages use the AdminLTE page layout | all |
| D-M019 | accepted | Prompt branches are chained (feat/foundation, feat/identity, ... feat/finalize), each created from the previous one; the owner merges feat/finalize into main with --ff-only after prompt 10. Prompt files live in prompts/ and are git-ignored | all |

## How to add a decision
Append a row with the next ID, status `proposed` or `open`, one sentence, and the modules it affects. If it changes a contract, update `docs/MODULES.md` in the same commit.

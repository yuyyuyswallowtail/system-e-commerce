# system-e-commerce-laravel

A modular monolith e-commerce application: public storefront (catalog, cart, checkout, orders, notifications, articles with comments) and an AdminLTE staff area (users, roles, catalog, stock, orders, payments, content, moderation, dashboard).

## Stack

| Part | Package | Version |
|---|---|---|
| Language | PHP | ^8.3 (built on 8.5) |
| Framework | laravel/framework | 13.x |
| UI components | livewire/livewire | 4.x |
| Admin theme | jeroennoten/laravel-adminlte (AdminLTE 3) | 3.16 |
| Roles and permissions | spatie/laravel-permission | 8.x |
| Audit log | spatie/laravel-activitylog | 5.x |
| Auth scaffolding | laravel/fortify | 1.x |
| Tests | pestphp/pest | 4.x |
| Database | MariaDB | |

## Modules

| Module | Responsibility |
|---|---|
| Identity | users, roles, permissions, audit, sessions, profile, follows |
| Catalog | products and categories, public product pages |
| Inventory | stock, reservations, movements, expiry command |
| Cart | one active cart per user, admin cart list |
| Payment | simulated payments, refunds, expiry command |
| Order | checkout, orders, status changes |
| History | per-user and admin event history (queued listeners) |
| Notification | database notifications on order and payment events |
| Content | articles with Markdown, publish workflow |
| Comment | comment threads on products and articles, moderation |
| Dashboard | admin statistics from module stats providers |

Modules talk to each other only through Contracts, DTOs and Events. Details are in docs/MODULES.md.

## Install

Run these commands from the repository root. After the copy, edit .env: set the DB_* values and SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD.

    composer install
    cp .env.example .env
    php artisan key:generate
    php scripts/db-create-test.php
    php artisan migrate
    php artisan db:seed
    php artisan storage:link

The seed step creates the roles and permissions and the admin account from the SEED_ADMIN_* values. In the local environment it also creates idempotent demo data (categories, products with stock, one article). The seeders never print passwords and never change existing users.

## Run

    php artisan serve --host=127.0.0.1 --port=8000
    php artisan queue:work
    php artisan schedule:work

The queue worker handles the history and notification listeners. The scheduler runs inventory:release-expired and payments:expire every minute.

## Tests

    php artisan test

The suite includes journey tests (customer, admin, content, failure paths), an authorization matrix over every route, and a check that every permission code used in code exists in config/permissions.php.

## Documentation

All design documents live in docs/: ARCHITECTURE.md, MODULES.md, STACK.md, DATABASE.md, UI.md, CODE_STYLE.md, SECURITY.md, TESTING.md, DECISIONS.md and MEMORY.md (handoff status between sessions).

## Conventions

- No hardcoded statuses, limits, TTLs, page sizes, currencies or permission codes: they live in config/*.php.
- Money is stored as integer minor units plus a currency code.
- Every route has a can: or auth middleware, and every Livewire action authorizes again.
- Controllers and Livewire components stay thin; rules and transactions live in module Services.
- Tests are written first with Pest; no skipped tests.
- Commits follow type(scope): message.

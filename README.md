# system-e-commerce

A microservices-based e-commerce platform built with native PHP and MySQL/MariaDB. No frameworks, no external libraries.

## Stack

- PHP 8.x (native, no framework)
- MySQL/MariaDB (shared database)

## Folder Structure

```
services/
  gateway-service/    - API gateway: routing, load balancing, request forwarding
  auth-service/       - Authentication: login, registration, token management
  user-service/       - User management: profiles, accounts, preferences
  dashboard-service/  - Dashboard: aggregated data, analytics views
  article-service/    - Articles: content creation, publishing, categorization
  comment-service/    - Comments: user comments, moderation, threading
  notification-service/ - Notifications: email, in-app alerts, preferences
  order-service/      - Orders: order lifecycle, status tracking
  cart-service/       - Shopping cart: items, quantities, persistence
  history-service/    - History: user activity, order history, browsing
  payment-service/    - Payments: payment processing, refunds, methods
  product-service/    - Products: catalog, pricing, descriptions
  stock-service/      - Stock: inventory levels, reservations, alerts
shared/               - Shared utilities, common helpers
docs/                 - Project documentation
scripts/              - Utility scripts (database checks, migrations)
infra/                - Infrastructure configuration
tests/                - Integration and standalone tests
```

## Conventions

- Service names follow `<name>-service` format.
- One shared database; table names are unique per service (prefixed by service name).
- All values including database credentials live only in the local `.env` file.

## How to Start

1. Copy `.env.example` to `.env` and fill every value.
2. Install MariaDB on the host.
3. Run `php scripts/check-db.php` to verify the connection.
4. Run `php tests/check-db-test.php` to run database tests.

## Database

MariaDB on the host at `127.0.0.1:3306`, database `system_e_commerce_db`, shared by all services. All connection values and the password are stored only in the local `.env` file and are never committed.

## Checks

```bash
php scripts/check-db.php
php tests/check-db-test.php
```

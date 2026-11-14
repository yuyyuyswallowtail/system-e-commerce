# System E-Commerce

A native PHP + MySQL/MariaDB microservices e-commerce platform. No frameworks, no libraries, no Composer.

## Stack

PHP 8.x, MySQL/MariaDB, PDO. One database per service.

## Services

- **gateway-service** — Central request router and load balancer
- **auth-service** — Authentication and session management
- **user-service** — User profile and account data
- **dashboard-service** — Admin and user dashboard aggregations
- **article-service** — Content articles and blog posts
- **comment-service** — User comments and moderation
- **notification-service** — Email, SMS, and in-app notifications
- **order-service** — Order lifecycle and status tracking
- **cart-service** — Shopping cart operations
- **history-service** — User activity and purchase history
- **payment-service** — Payment processing and refunds
- **product-service** — Product catalog and details
- **stock-service** — Inventory levels and stock reservations

## Conventions

- Service folders: `<name>-service`
- One database per service
- `declare(strict_types=1);` on every PHP file
- PDO with prepared statements only

## How to Start

1. Copy `.env.example` to `.env` and fill every value
2. Set up MariaDB (port 3306)
3. Create each service database
4. Run migrations in each service's `database/migrations/`

## Database

MariaDB in Docker container (root), port 3306, database `system_e_commerce_db`. All connection values and the password are stored only in the local untracked `.env` file.

## Checks

```
php scripts/check-db.php
php tests/check-db-test.php
```

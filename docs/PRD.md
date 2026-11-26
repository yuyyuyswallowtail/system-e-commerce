# PRD: system-e-commerce-laravel

Status: v1 draft. Monolith rewrite of the 13-service PHP design. Stack fixed by the owner: Laravel, Livewire, AdminLTE 3 and other Laravel-ecosystem packages.

## 1. Goal
An online shop (storefront + admin panel) as one Laravel application with clear module boundaries, buildable one module at a time on a single developer machine with limited resources.

## 2. Non-goals
- No SPA, no Inertia, no Node build (AdminLTE assets come from the package, Livewire ships its own JS).
- No public REST API in v1 (Sanctum can be added later, see D-M010).
- No real payment provider (simulated provider only).
- No Docker, CI, message broker, multi-tenant, multi-database.

## 3. Actors
| Actor | Description | Access |
|---|---|---|
| Guest | Not logged in | Public catalog, articles, login, register |
| Member | Role `member`, no permissions | Own cart, orders, profile, notifications, comments |
| Admin | Role `admin`, all permissions except `permissions.manage` | Catalog, stock, orders, payments, content, users |
| Super admin | Role `super_admin`, all permissions | Everything incl. permission management |

## 4. Functional requirements by module
| Module | Requirements |
|---|---|
| Identity | Register, login, logout, lockout, profile, password change, users, roles, permissions (RBAC), follow graph, audit log, sessions and presence admin |
| Catalog | Products and categories, public browse, admin write, soft delete |
| Inventory | Stock per product, reserve/commit/release with TTL, adjustments with reason, low-stock |
| Cart | One active cart per user, add/update/remove, price snapshot |
| Payment | Payment per order, simulated pay/fail, expiry, refund, idempotent |
| Order | Checkout from cart, status machine, cancel, admin list and status change |
| History | Append-only event log, `/me/history`, admin view |
| Notification | In-app notifications (Laravel database notifications), unread count, read/delete |
| Content | Articles draft/published/archived, public read, admin write |
| Comment | Comments on articles and products, moderation, flood limit |
| Dashboard | Read-only aggregates from all modules |

## 5. Cross-cutting requirements
- Every mutation is audited (spatie/laravel-activitylog, causer = acting user).
- Every list supports search, filters, sort, pagination with whitelists from config.
- Money is integer minor units plus currency code.
- Required-path failures roll back the whole DB transaction. Best-effort side effects never fail the request.
- Every route and every Livewire action is authorized.

## 6. Core user journeys
1. Register, log in, browse products, add to cart, checkout, pay, receive notification, see order history.
2. Admin creates product, sets stock, sees order, refunds payment, sees dashboard.
3. Admin publishes article, member comments, admin moderates comment.

## 7. Success criteria
- Each prompt stage passes its STAGE CHECK with `Skipped: 0`.
- App runs with `php artisan serve` plus `php artisan queue:work` on a constrained machine.
- The full checkout journey passes as a feature test.
- Switching AI account or model never needs re-explaining: `AGENTS.md` and `docs/` are enough.

## 8. Open questions
None. Defaults in `docs/DECISIONS.md` marked `proposed` are followed until the owner edits them.

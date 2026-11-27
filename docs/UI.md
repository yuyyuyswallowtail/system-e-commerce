# UI (AdminLTE 3 + Livewire 4)

## 1. Layouts
- All authenticated pages and all admin pages use the AdminLTE page layout from `jeroennoten/laravel-adminlte`.
- Public pages (catalog, articles, login, register) use the same package assets with a top-nav style layout (`app/View/Components` or `resources/views/layouts/storefront.blade.php` extending the package master view). Look up the package docs for the supported way; record the choice in `docs/DECISIONS.md`.
- Livewire full-page components must be wired to the layout the way Livewire 4 documents it for `@extends`-style layouts (look it up; do not guess attribute names). Configure once in `config/livewire.php` or per component, and test with a rendering test.
- Do not use `@vite`. Assets come from `public/vendor/` published by the package.

## 2. Menu
`config/adminlte.php` `menu` is built from the module catalog. Each item has `can` set to its permission (or `login` only items without it). Sections: Shop (Products, Cart, My orders, Notifications, Articles), Management (Products, Categories, Stock, Carts, Orders, Payments, Articles, Comments, History), Identity (Users, Roles, Permissions, Audit, Sessions), Dashboard. Use package menu filters or `can` so a member never sees staff entries. The notification badge uses the unread count.

## 3. Component conventions
- Page components in `Livewire/Admin` and `Livewire/Shop`. One responsibility each. Business rules in module Services.
- Public properties: forms as Livewire form objects; ids and trusted values `#[Locked]`. Validate with rules built from config. Authorize in the mount and in every action.
- Lists use `App\Support\Livewire\HasTableQuery`: `search`, `filters`, `sortField`, `sortDirection`, `perPage` (capped), pagination via `WithPagination`. Allowed sort and filter fields come from `config/tables.php`. Unknown values reset to defaults, they never reach SQL.
- Destructive actions use `wire:confirm`. Long actions show `wire:loading`. Disable submit buttons while loading.
- Flash messages use the session and `<x-adminlte-alert>`. Validation errors render next to fields. Never show exception messages; use generic text and log the details.
- AdminLTE plugins that need jQuery init (select2, datepicker) are wrapped in `wire:ignore`. Prefer native inputs. Do not mix Bootstrap JS dropdown state with re-rendered DOM.
- Money displays through `App\Support\Money::format($amount, $currency)`. Dates display in `config('shop.display_timezone')`.
- Empty states and error states exist for every list.

## 4. Forms and uploads
Validate mime and size from config, store on the `public` disk with random file names, delete the old file on replace. Show images with `Storage::url`. Run `php artisan storage:link` once.

## 5. Accessibility and copy
Labels for every input, buttons with text, English copy only, short messages.

## 6. Design system
Colors, type, spacing, radius, elevation and component rules live in `docs/DESIGN.md`. The only stylesheet is `public/css/theme.css`, status badge variants and icon names are in `config/ui.php`, and the stat card is `<x-stat-card>`. Views never use inline styles or hex colors.

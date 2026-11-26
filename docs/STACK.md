# STACK

Fixed by the owner: Laravel, Livewire, AdminLTE 3, and other Laravel-ecosystem packages. Facts below were looked up in Nov 2026; re-verify at prompt 00 and record the installed versions in `docs/MEMORY.md`.

## 1. Verified facts
- Laravel 13 (released 2026-03-17) requires PHP 8.3 to 8.5. Check `php -v` first.
- Livewire 4.2.0 added Laravel 13 support. Livewire 4 supports single-file components and keeps class-based components working.
- `jeroennoten/laravel-adminlte` (latest listed v3.15.0) bundles AdminLTE 3.2 (Bootstrap 4 + jQuery) and requires `laravel/framework >=8.0`. AdminLTE 4 is a different package: do not use it.
- `spatie/laravel-permission` requires PHP ^8.3 and Laravel ^12 or ^13.
- `spatie/laravel-activitylog` ^5 appears in Laravel 13 projects (verify with composer).
- `laravel/fortify` ^1.3x is used with Laravel 13 (verify with composer).

## 2. Package allow-list
| Package | Use |
|---|---|
| laravel/framework ^13 | core |
| livewire/livewire ^4.2 | UI components |
| jeroennoten/laravel-adminlte ^3.15 | AdminLTE 3 layout, menu, components |
| laravel/fortify | login, register, logout, rate limiting (headless) |
| spatie/laravel-permission | roles and permissions |
| spatie/laravel-activitylog | audit |
| pestphp/pest + pest-plugin-laravel (dev) | tests |
| laravel/pint (dev) | style |
| laravel/tinker, laravel/pail (dev) | local tooling |
Anything else needs a `docs/DECISIONS.md` row plus a compatibility check. Candidates the owner may approve later: spatie/laravel-medialibrary (images), maatwebsite/excel (exports), laravel/sanctum (API).

## 3. Install commands (prompt 00, from repo root with docs already present)
```
composer create-project laravel/laravel:^13.0 /tmp/sec-skeleton
cp -an /tmp/sec-skeleton/. .
composer require livewire/livewire jeroennoten/laravel-adminlte laravel/fortify spatie/laravel-permission spatie/laravel-activitylog
composer require --dev pestphp/pest pestphp/pest-plugin-laravel laravel/pint
php artisan adminlte:install
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan vendor:publish --tag=livewire:config
```
Every command above must be checked with `--help` or the package docs first. If a command or tag differs, use the documented one and write the difference in `docs/MEMORY.md`. `cp -an` never overwrites existing repo files (AGENTS.md, docs/).

## 4. Environment
- PHP 8.3+, Composer 2, MariaDB native on `127.0.0.1:3306`, database `system_e_commerce_db`, test database `system_e_commerce_test`. Laravel driver `mariadb` (verify it exists in `config/database.php`, else `mysql`).
- `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database` (skeleton defaults, verify).
- No Node. The skeleton's `package.json`, `vite.config.js`, `resources/js`, `resources/css` stay untouched and unused. Never use `@vite`.
- App serves with `php artisan serve --host=127.0.0.1 --port=8000`.

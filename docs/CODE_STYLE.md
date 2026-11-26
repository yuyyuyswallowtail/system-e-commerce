# CODE STYLE

## 1. Toolchain
PHP 8.3+, Laravel 13, Pint with the default Laravel preset (`vendor/bin/pint`). `php -l` on every file. Pest for tests.

## 2. Files and namespaces
- One class per file, file name equals class name. `declare(strict_types=1);` in every file you create.
- Namespaces follow folders under `App\Modules\<Module>\...`.
- Module `Contracts/` holds interfaces and readonly DTOs. Other modules import only from there (and Events).

## 3. Naming
| Thing | Style |
|---|---|
| Class | PascalCase by role (`CheckoutService`, `ProductSnapshot`) |
| Method, variable | camelCase |
| Constant | UPPER_SNAKE_CASE |
| Config keys, DB columns, JSON | snake_case |
| Route names | dot notation, `admin.` prefix for staff pages |
| Livewire components | `Livewire/Admin/Products/Index.php` style, alias `<module>::admin.products.index` |

## 4. Layers
- Livewire component or controller: read input, validate shape, authorize, call one Service method, set UI state. No queries, no business rules.
- Service: business rules, transactions, calls to Contracts, dispatches events after commit. Does not know Livewire or HTTP.
- Model: relations, casts, scopes, `$fillable`. No business workflows.
- Listener: thin, calls a Service or writes its own module table.

## 5. Errors
- Throw domain exceptions from `App\Modules\<Module>\Exceptions` (or `App\Support\Exceptions`): `DomainValidationException` (carries fields), `ConflictException`, `InsufficientStockException`, `NotFoundException`. The UI layer maps them to validation errors or flash messages.
- Never swallow exceptions with empty catch. Never use `@`. Log with context, never log secrets or bodies.

## 6. Data
- Typed parameters and returns everywhere. Prefer readonly DTOs over arrays across module borders.
- Money integer minor units. No floats for money.
- No `DB::raw`, `dd`, `dump`, `var_dump`, `env()` outside config files.
- Time via `Illuminate\Support\Carbon` and `Carbon::setTestNow` in tests, not `time()` deep in services.

## 7. No hardcoding
Statuses, limits, TTLs, per-page, currency, patterns, permission codes, transition maps: `config/*.php` only.

## 8. No stubs, no dead code
Remove `TODO`, `FIXME`, commented-out code, unused imports and methods before commit.

## 9. Comments
Comment the why, not the what. No banner comments, no restating the method name, no emoji. English only: code, comments, messages, README, commits.

## 10. Git
- Branch `feat/<name>`, commit with the exact stage message from the prompt. Prefixes `feat(<module>):`, `docs(<module>):`, `fix(<module>):`, `chore:`.
- No co-author or tool-credit lines.
- Never commit `.env`, secrets, `vendor/`, `node_modules/`, logs, `storage/` runtime files.
- Stage only paths the prompt allows. Check `git diff --cached --name-only` before commit.

## 11. Shell and files (for agents)
Create files with `cat > path <<'EOF'` heredocs (quoted delimiter) or `php artisan make:*`. Commands must be copy-paste ready. Work from repo root and never `cd`.

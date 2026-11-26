# TESTING

## 1. Principles
- Pest on top of Laravel's test runner: `php artisan test`. TDD is mandatory: for each unit of work write the test first, see it fail, implement until green. Report red and green counts per module.
- Last line of every run must show no failures and no skipped tests. `Skipped: 0` always. A test file with zero tests is a failure.
- Never claim done without a green run pasted as raw output.

## 2. Test database
Tests run on `system_e_commerce_test` (set in `phpunit.xml` / `.env.testing`, never the main DB). Create it once: `php scripts/db-create-test.php` (reads `.env` through PDO, prints `[OK]` or `[FAIL] <short message>`, never prints credentials). Use `RefreshDatabase` or `DatabaseTransactions`. Lock behavior is tested on MariaDB, not SQLite.

## 3. Layers
| Layer | Notes |
|---|---|
| Unit | Services with fakes bound to Contracts, `Carbon::setTestNow`. Fakes must pass the same behavior tests as the real service |
| Feature (HTTP) | Routes, middleware, auth, `can:` per route |
| Livewire | `Livewire::test(Component::class)`: render, validation, actions, authorization, locked properties |
| Database | Migrations run, tables and columns exist, uniques, cascade, factories valid |
| Journey | One end-to-end test per core journey in `tests/Feature/Journeys` |

## 4. Every page must be tested for
Guest redirect to login (or public 200), member 403 on staff pages, permitted staff 200, rendering with data and empty state, each action's happy path and failure path, validation messages, soft-delete visibility rules, no raw exception text.

## 5. Coverage checklist per module
Config keys used, each Service rule including failure paths, each Contract method (real and fake), each event dispatched with after-commit semantics, each listener (idempotent), each policy (owner and non-owner), each list (every filter, sort whitelist, escaping, per-page cap), each command, README or docs updated.

## 6. Stage check (end of every stage, paste raw output)
```
find app bootstrap config database routes tests -name '*.php' -print0 | xargs -0 -n1 php -l
vendor/bin/pint --test
php artisan test
php artisan route:list --except-vendor
grep -rnE 'TODO|FIXME|\bstub\b|\bdd\(|\bdump\(|var_dump\(|\$GLOBALS|DB::raw\(|whereRaw\(' app routes database resources/views
git status --porcelain | grep -vE '<allowed paths from the prompt>'
git check-ignore .env
git ls-files | grep -E '(^|/)\.env$'
```
Expected: no `php -l` errors; Pint passes; all tests pass with `Skipped: 0`; route list works; the grep prints nothing; the status grep prints nothing; `check-ignore` prints `.env`; the last `git ls-files` prints nothing.
Then:
```
git add <allowed paths from the prompt> docs/MEMORY.md
git diff --cached --name-only | grep -vE '<allowed paths regex>'
git diff --cached | grep -nE '^\+.*(APP_KEY|DB_PASSWORD|SEED_ADMIN_PASSWORD)=.+'
git commit -m "<the stage message>"
git push -u origin feat/<name>
```
Both `grep` commands must print nothing before you commit.

## 7. Flaky or slow tests
Fix the cause. No arbitrary sleeps, no skips. Use `Carbon::setTestNow` and `Queue::fake()` / `Event::fake()` where the queue is not the subject.

# SECURITY

## 1. Secrets
- Real values live only in the untracked `.env`. `.env.example` is committed with every key blank except non-secret defaults (`APP_ENV`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`).
- Never cat, echo, print, log or commit: `APP_KEY`, `DB_PASSWORD`, `SEED_ADMIN_PASSWORD`, tokens, hashes. `php artisan key:generate --show` is forbidden.
- Verify only with `git check-ignore .env` (prints `.env`) and `git ls-files | grep -E '(^|/)\.env$'` (prints nothing).
- Seeder admin credentials come from `SEED_ADMIN_EMAIL` and `SEED_ADMIN_PASSWORD` in `.env`. Empty password means the seeder refuses to create the admin.

## 2. Network and runtime
Bind 127.0.0.1. `APP_DEBUG=false` outside local. Never expose `php artisan serve` publicly.

## 3. Authentication
- Fortify with the database session driver. Regenerate the session on login, invalidate on logout.
- Password hashing via the `hashed` cast and `config/hashing.php`. Policy via `Password::defaults()` from config. Rehash when needed.
- Login lockout per sha256(email)+IP with the Fortify rate limiter, limits from config. Generic error for any credential failure.
- `EnsureUserActive` logs out deleted, suspended and banned users on the next request.

## 4. Authorization
- Spatie roles and permissions. `Gate::before` for `super_admin`. Routes use `can:<code>`; Livewire actions call `authorize`.
- No privilege escalation: an actor may assign only roles whose permissions are a subset of their own. System roles are immutable.
- Own-resource access (order owner, comment author) lives in Policies and is tested for the other-user case (expect 403 or 404).

## 5. Livewire hardening
- Public properties are user input. `#[Locked]` for ids and context. Never accept price, status, role, user id from a property without re-reading from the DB.
- Validate every action input again. Rate-limit expensive or abusable actions (`RateLimiter`).
- Do not expose models with secrets as public properties; use DTOs or arrays of allowed fields.

## 6. Input and output
- Blade `{{ }}` only. `{!! !!}` is forbidden except for the Markdown helper output that strips HTML.
- Eloquent or bindings only. Escape LIKE wildcards. Sort and filter columns from a whitelist.
- Mass assignment: explicit `$fillable`.
- Uploads: validate mime and size, random names, never trust the client file name.
- Never render raw exception text. Generic message, details in the log.

## 7. Logging and audit
Audit with activitylog on every mutation (causer = acting user, properties never contain password, tokens or hashes). Logs never contain secrets or request bodies.

## 8. Idempotency and races
Checkout uses a locked idempotency key and a unique (user_id, idempotency_key) index. Stock and payment changes use transactions and `lockForUpdate`. Double submit must not double-reserve or double-charge.

## 9. Git hygiene checks (run before every commit)
```
git diff --cached --name-only | grep -E '(^|/)\.env$'                       # prints nothing
git diff --cached | grep -nE '^\+.*(APP_KEY|DB_PASSWORD|SEED_ADMIN_PASSWORD)=.+'   # prints nothing
git ls-files | grep -E '(^|/)\.env$'                                         # prints nothing
```

## 10. If a secret leaks
Stop. Do not rewrite history yourself. Tell the owner to rotate the secret, then decide how to clean history. Log it in `docs/MEMORY.md` without the value.

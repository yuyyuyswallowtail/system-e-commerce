# AGENTS.md

Entry point for every AI agent or developer working on `system-e-commerce-laravel`.
Sessions may run on different accounts or models with no shared chat history. This repo is the only memory. Read it before acting.

## Project in one paragraph
E-commerce as a modular monolith: Laravel 13 + Livewire 4 + AdminLTE 3 (`jeroennoten/laravel-adminlte`). One app, one MariaDB database `system_e_commerce_db`, 11 modules under `app/Modules/`. Modules talk only through Contracts and domain events, never through each other's models. Server-rendered UI, no SPA, no Node build. Everything is written in English.

## Session start (mandatory, in this order)
1. Read `docs/MEMORY.md` (current state, what to do next).
2. Read `docs/ARCHITECTURE.md`, `docs/MODULES.md`, `docs/STACK.md`.
3. Read the docs the task needs: `docs/DATABASE.md`, `docs/UI.md`, `docs/CODE_STYLE.md`, `docs/SECURITY.md`, `docs/TESTING.md`, `docs/DECISIONS.md`.
4. Run the state check:
```
git rev-parse --show-toplevel ; git rev-parse --abbrev-ref HEAD ; git status --porcelain ; git log --oneline -5
```
5. If the task came from a prompt file (`NN-<name>.md` in `prompts/`, git-ignored), follow it exactly. It overrides these docs only where it says so.

## Session end (mandatory)
1. Run the STAGE CHECK (`docs/TESTING.md` section 6). Never claim done without green raw output.
2. Update `docs/MEMORY.md`: status table, one session-log entry, next step. Commit it with the stage.
3. If you made a design choice not in the docs, add it to `docs/DECISIONS.md`.

## Golden rules
1. The stack is fixed in `docs/STACK.md`. Add a package only with a `docs/DECISIONS.md` row and a compatibility check against Laravel 13 (look it up). No Docker, no CI, no Node/npm/Vite, no Filament, Jetstream, Breeze or Inertia.
2. Work from the repo root. Never `cd`. One branch per prompt: `feat/<name>`, chained (each starts from the previous prompt branch; the owner merges `feat/finalize` into `main` at the end). Never commit on `main`, except the BOOTSTRAP docs sync in prompt 00.
3. Never run `rm -rf`, `git clean`, `reset`, `restore`, `stash`, `rebase`, `merge`, `amend`, `checkout -- <path>`. Never run `migrate:fresh`, `migrate:reset`, `db:wipe` against the main database (only with `--env=testing`).
4. Never print, cat, echo or commit `.env` content or any secret. `php artisan key:generate --show` is forbidden.
5. Module boundaries: a module never imports another module's Models, Livewire components or Services. Only its `Contracts`, DTOs and Events. No cross-module Eloquent relations, joins or foreign keys. `App\Models\User` is the shared kernel and may be used everywhere.
6. No hardcoding: statuses, enums, limits, TTLs, per-page, currency, permission codes, transition maps live in `config/*.php`.
7. Money is integer minor units plus `currency CHAR(3)`. Never floats.
8. Authorization on every route (`can:` middleware) and again inside every Livewire action (`$this->authorize(...)`). Hiding a button is not security.
9. Livewire public properties are client-controlled. Mark ids and trusted values `#[Locked]`, never trust prices, status, roles from the client, re-validate in the action.
10. TDD with Pest: test first, see it fail, implement until green. `Skipped: 0`.
11. Livewire components and controllers stay thin. Business rules and transactions live in module Services.
12. Use exact names (tables, routes, permissions, events, config keys) from `docs/MODULES.md`. Do not invent others.
13. Do not change anything you were not asked to change. Ask or log it in `docs/DECISIONS.md`.
14. If unsure about a fact that can change (versions, package API, artisan flags), look it up instead of guessing. Label guesses as guesses.
15. No stubs. A method returning `[]` or a fixed value is NOT DONE.

## Working style for smaller or free-tier models
- Do one stage per session. Finish it, commit, push, update `docs/MEMORY.md`, stop.
- Prefer copy-paste shell commands. Create files with `cat > path <<'EOF'` heredocs.
- Keep answers short. Show raw command output as proof, not summaries.
- When context is running low, stop at a clean stage boundary and write the handoff in `docs/MEMORY.md`.

## Doc map
| File | Purpose |
|---|---|
| docs/MEMORY.md | Current state, handoff log, next step |
| docs/PRD.md | What we build and why |
| docs/ARCHITECTURE.md | Modular monolith shape, boundaries, flows |
| docs/MODULES.md | Per-module catalog: tables, pages, permissions, contracts, events |
| docs/STACK.md | Fixed stack, verified facts, install commands |
| docs/DATABASE.md | Shared-DB rules, naming, migrations, locking |
| docs/UI.md | AdminLTE and Livewire conventions, menu, tables |
| docs/CODE_STYLE.md | PHP and Laravel style, forbidden patterns |
| docs/SECURITY.md | Secrets, auth, authorization, Livewire hardening |
| docs/TESTING.md | Pest, layers, stage check |
| docs/DECISIONS.md | Decision log |
| docs/PROMPT-TEMPLATE.md | Template for writing a new module prompt |

Prompt files live in `prompts/` (git-ignored): `00` to `10`. Run order and status are in `docs/MEMORY.md`.

# MEMORY (handoff file)

Shared brain between sessions, accounts and models. Keep it short, current and factual. Update it at the end of every stage.

Last updated: docs set and prompts 00 to 10 written. Nothing built yet.

## 1. How to use
- Start of session: read this file, run the verify commands in section 3, fix any mismatch here first.
- End of session: update section 2 (status), add one entry to section 5, set section 4 (next step).
- Keep only facts you can prove with a command or a commit. Mark guesses as `(unverified)`.
- Never write secrets here.

## 2. Status
Legend: not-started, in-progress, done (STAGE CHECK green and pushed), blocked.
| # | Prompt | Module | Branch | Status | Last commit message |
|---|---|---|---|---|---|
| 00 | 00-foundation.md (2 stages) | app skeleton, packages, layout, support | feat/foundation | not-started | |
| 01 | 01-identity.md (3 stages) | Identity | feat/identity | not-started | |
| 02 | 02-catalog.md (3 stages) | Catalog | feat/catalog | not-started | |
| 03 | 03-inventory.md (3 stages) | Inventory | feat/inventory | not-started | |
| 04 | 04-cart.md (2 stages) | Cart | feat/cart | not-started | |
| 05 | 05-checkout.md (4 stages) | Payment + Order | feat/checkout | not-started | |
| 06 | 06-history-notification.md (2 stages) | History, Notification | feat/history-notification | not-started | |
| 07 | 07-content.md (2 stages) | Content | feat/content | not-started | |
| 08 | 08-comment.md (2 stages) | Comment | feat/comment | not-started | |
| 09 | 09-dashboard.md (2 stages) | Dashboard | feat/dashboard | not-started | |
| 10 | 10-finalize.md (2 stages) | journey test, README, hardening | feat/finalize | not-started | |
Prompt files `NN-<name>.md` live in `prompts/` and are git-ignored. Branches are chained: each prompt branches from the previous prompt branch; the owner merges `feat/finalize` into `main` at the end. They may be missing on a fresh clone: ask the owner.
Run order: 00, 01, 02, 03, 04, 05, 06, 07, 08, 09, 10. Build providers before callers.

## 3. Verify commands (copy-paste from repo root)
```
git rev-parse --abbrev-ref HEAD ; git status --porcelain ; git log --oneline -10
git branch -a
ls -1 app/Modules
php artisan route:list --except-vendor
php artisan test
```

## 4. Next step
Run prompt 00 (`feat/foundation`). One prompt per session; after each stage update the status table.

## 5. Session log (newest first)
Format: `date | account/model | what was done | commits | result | blockers`
- docs session | wrote AGENTS.md, docs/ and prompts 00 to 10 for the monolith | none yet | prompts ready, nothing built | none

## 6. Known pitfalls (carried over from the microservice build, do not repeat)
- Nested folders by mistake. Commits on `main`.
- Regex without anchors. `LIMIT` or ids as strings.
- Permission codes that never match a seeded permission.
- Raw exception messages shown to the user.
- Fat Livewire components with SQL and business rules inside.
- Stubs counted as done. Declaring "complete" with no tests.

## 7. Open blockers
None recorded.

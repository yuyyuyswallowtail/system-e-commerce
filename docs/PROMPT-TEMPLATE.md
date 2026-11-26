# PROMPT TEMPLATE for a new module

Prompts 00 to 10 already exist. Use this only for a new module. Save as `NN-<name>.md` in `prompts/` (git-ignored). Fill every `<...>`. Take tables, pages, permissions, contracts and events from `docs/MODULES.md` (add the module there first). Keep the prompt self-contained.

---BEGIN TEMPLATE---
<PREAMBLE: copy the first block of any existing prompt (role, reading list, stages rule)>
<RULES: copy the RULES block of any existing prompt>

SPEC
- Tables (exact columns, indexes, uniques): <from docs/MODULES.md>
- Config keys to add to config/shop.php: <list>
- Contracts exposed and used: <list with signatures>
- Events: <list>
- Pages and permissions: <route -> component -> auth -> permission>
- Behavior: <state machines, transactions, idempotency, audit>

STAGES
1. Schema and domain: migrations, models, factories, config, Service, Contracts, events, provider. Message: "feat(<module>): add schema and domain"
2. UI and features: Livewire pages, policies, routes, menu entries, commands. Message: "feat(<module>): add <pages>"
3. (optional) Docs and cleanup: update docs/MODULES.md and docs/MEMORY.md. Message: "docs(<module>): update docs"

<POST: pre-flight, stage check, final output blocks copied from any existing prompt>
---END TEMPLATE---

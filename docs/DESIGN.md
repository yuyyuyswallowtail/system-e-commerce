# DESIGN

Visual identity of the storefront, the authentication screens and the back office. Behavior is frozen: only Blade views, `public/css/theme.css`, `config/ui.php`, demo seed content and tests change.

## 1. Concept
A small shop with a calm storefront and a dense but quiet back office. Warm neutrals carry the structure, one deep green accent marks the single primary action of a view, and status colors appear only where a state needs attention. Hierarchy comes from type weight, spacing and borders, never from gradients, glow or decoration.

## 2. Where it lives
- `public/css/theme.css`: the only stylesheet, linked by both layouts after the AdminLTE CSS. Every color, space, radius, shadow and font size is a custom property in `:root`, defined once.
- `config/ui.php`: status to badge variant maps, stat tones and icon names. A unit test checks that every status in `config/shop.php` has a variant.
- `resources/views/components/stat-card.blade.php`: the neutral stat card that replaces the AdminLTE small-box.
- No new package, no Node, no CDN, no remote font or image at runtime. Google Fonts is switched off in `config/adminlte.php`.

## 3. Color tokens
Neutral scale (9 steps): `--neutral-1` #fafaf8, `--neutral-2` #f3f3f0, `--neutral-3` #e7e7e2, `--neutral-4` #d4d4cd, `--neutral-5` #8d8d85, `--neutral-6` #6f6f68, `--neutral-7` #55554f, `--neutral-8` #34342f, `--neutral-9` #1a1a18.

Roles: `--page-bg` (neutral-2), `--surface` (white), `--text` (neutral-9), `--text-muted` (neutral-7), `--text-placeholder` (neutral-6), `--border` (decorative), `--border-strong`, `--border-control` (form controls and UI borders, at least 3:1).

Accent: `--accent` #1d6b5c, `--accent-hover` #155244, `--accent-subtle` #e3f0ec, `--on-accent` white. It is not Bootstrap blue and not an AdminLTE default.

Status, each as text on its own tint: success (`--success-fg`, `--success-bg`), warning, danger (plus `--danger-solid` for the destructive button), info, neutral.

### Contrast ratios (computed from the tokens, WCAG 2.x AA)
Body text needs 4.5, large text, UI borders and focus rings need 3.0. `tests/Unit/Ui/ContrastTest.php` enforces every row.

| Foreground | Background | Colors | Ratio | Required |
|---|---|---|---|---|
| `--text` | `--surface` | #1a1a18 on #ffffff | 17.43 | 4.5 |
| `--text` | `--page-bg` | #1a1a18 on #f3f3f0 | 15.68 | 4.5 |
| `--text-muted` | `--surface` | #55554f on #ffffff | 7.50 | 4.5 |
| `--text-muted` | `--page-bg` | #55554f on #f3f3f0 | 6.75 | 4.5 |
| `--text-placeholder` | `--surface` | #6f6f68 on #ffffff | 5.06 | 4.5 |
| `--accent` | `--surface` | #1d6b5c on #ffffff | 6.34 | 4.5 |
| `--accent` | `--page-bg` | #1d6b5c on #f3f3f0 | 5.71 | 4.5 |
| `--accent` | `--accent-subtle` | #1d6b5c on #e3f0ec | 5.42 | 4.5 |
| `--on-accent` | `--accent` | #ffffff on #1d6b5c | 6.34 | 4.5 |
| `--on-accent` | `--accent-hover` | #ffffff on #155244 | 9.04 | 4.5 |
| `--on-danger` | `--danger-solid` | #ffffff on #b42318 | 6.57 | 4.5 |
| `--on-danger` | `--danger-solid-hover` | #ffffff on #8f1c13 | 8.96 | 4.5 |
| `--success-fg` | `--success-bg` | #1b6e37 on #e4f3e8 | 5.49 | 4.5 |
| `--warning-fg` | `--warning-bg` | #7a4f00 on #fdf1d3 | 6.35 | 4.5 |
| `--danger-fg` | `--danger-bg` | #a8271c on #fbe9e7 | 6.03 | 4.5 |
| `--info-fg` | `--info-bg` | #245b78 on #e3eef4 | 6.26 | 4.5 |
| `--neutral-fg` | `--neutral-bg` | #55554f on #e7e7e2 | 6.05 | 4.5 |
| `--sidebar-text` | `--sidebar-bg` | #d4d4cd on #1a1a18 | 11.70 | 4.5 |
| `--sidebar-heading` | `--sidebar-bg` | #8d8d85 on #1a1a18 | 5.21 | 4.5 |
| `--sidebar-text-active` | `--sidebar-active-bg` | #ffffff on #1d6b5c | 6.34 | 4.5 |
| `--border-control` | `--surface` | #8d8d85 on #ffffff | 3.34 | 3.0 |
| `--focus-ring` | `--surface` | #1d6b5c on #ffffff | 6.34 | 3.0 |
| `--focus-ring` | `--page-bg` | #1d6b5c on #f3f3f0 | 5.71 | 3.0 |
| `--focus-ring-inverse` | `--sidebar-bg` | #9fd8c7 on #1a1a18 | 10.89 | 3.0 |
| `--neutral-6` | `--surface` | #6f6f68 on #ffffff | 5.06 | 3.0 |

## 4. Type
System font stack (`--font-sans`), fixed scale in rem: 12, 14, 16, 20, 24, 32 px (`--text-xs` to `--text-2xl`). Admin screens use 14 px body, the storefront 16 px. Weights 400, 500, 700. Headings use tight tracking (`--tracking-tight`) and line height 1.25, body 1.5. Numbers in tables, badges, pagination and stat cards use `font-variant-numeric: tabular-nums`; money is right-aligned (`.money`). Long text keeps a 72ch measure (`.measure`).

## 5. Space, radius, elevation, density
- Spacing on a 4 px grid: `--space-1` 4, 2 8, 3 12, 4 16, 5 24, 6 32, 7 48.
- One radius: `--radius` 6 px.
- At most two elevations: `--shadow-1` and `--shadow-2` (menus and modals). Cards use borders, not shadows.
- Density: comfortable tables (12 px by 16 px cell padding), 40 px controls on desktop, 44 px touch targets under 768 px.

## 6. Component rules
- One primary action per view (`.btn-primary`, filled accent). Secondary actions are outlined or neutral, destructive ones use `.btn-danger` or `.btn-outline-danger` and always carry `wire:confirm` that names the consequence.
- Stat card: neutral by default; a tone (success, warning, danger, info) only when the number carries meaning.
- Badges: `badge badge-{variant}` where the variant comes from `config/ui.php`. Never color by guesswork in a view.
- Alerts: tinted background, 4 px status bar on the left, text in the status foreground.
- Forms: visible label for every control, validation message next to the field in `--danger-fg`, invalid border in `--danger-solid`.
- Tables: quiet header, optional `.table-sticky`, `.table-stack` with `data-label` cells under 768 px.
- Focus: a 2 px `:focus-visible` ring, never removed; the sidebar uses `--focus-ring-inverse`.
- Motion: transitions of `--duration` (120 ms) at most, switched off under `prefers-reduced-motion`.
- Banned: purple to blue gradients, glassmorphism, decorative blobs, emoji icons, neon glow, inline `style=`, hex colors in views, `!important` outside the reduced-motion rule.
- Copy: English, sentence case, specific. No "Success!", no "Oops", no lorem.

## 7. Review checklist (scripted)
`tests/Feature/Ui/AdminPagesTest.php` and `StorefrontPagesTest.php` assert one h1, lang, viewport, labels, alt text and accessible names on every page. `tests/Unit/Ui/ViewRulesTest.php` enforces confirmations on destructive actions, no inline styles, hex colors, lorem or small-box in views, and at most 15 commented `!important` in the stylesheet. `ContrastTest` enforces the token contrast ratios.

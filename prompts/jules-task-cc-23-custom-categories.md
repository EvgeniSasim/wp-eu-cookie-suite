# CC-23 Custom consent categories

```markdown
Task CC-23 — User-defined consent categories (extend hardcoded model without breaking defaults).

Depends on CC-19 merged.

Should land **before CC-21** (consent log must store arbitrary category keys).

Read first: `docs/product-spec.md`, `AGENTS.md`.

## Problem

`Categories::get_all()` returns four fixed slugs (`necessary`, `preferences`, `statistics`, `marketing`). Filter `wpeu_consent_categories` exists for developers, but **site owners have no admin UI** to add categories (e.g. `social`, `embeds`, `affiliate`).

Hardcoded references elsewhere:
- `ScriptBlocker.php` bootstrap JS category list
- `Banner.php` WP Consent mapping
- `GoogleConsentMode.php` only reads `statistics` / `marketing` (must use integration_map for custom)

`wpeu_cs_user_has_consent( $slug )` already works for any slug via `wpeu_{slug}` cookies — keep that.

## Product rules (v1)

### Built-in categories (always present, slug immutable)

| Slug | Required | Can disable in banner |
|------|----------|------------------------|
| `necessary` | yes | no (always on, read-only) |
| `preferences` | no | yes |
| `statistics` | no | yes |
| `marketing` | no | yes |

Built-in labels/descriptions remain editable via existing `banner_texts[{locale}][{slug}_label]`.

### Custom categories (owner-defined)

Stored in `wpeu_cs_settings['custom_categories']`:

    [
      'social' => [
        'label'           => 'Social Media',
        'description'     => 'Cookies for embedded social widgets.',
        'integration_map' => 'marketing', // required: preferences|statistics|marketing
      ],
    ]

Rules:
- Slug: `sanitize_key`, 2–32 chars, `[a-z0-9_-]`, no collision with built-in slugs
- **Max 5** custom categories
- Cannot delete built-in; can remove custom (confirm; clear from `enabled_categories`)
- Custom categories appear in banner preferences modal with toggle

### Integration map (required per custom category)

Dropdown **「Counts as (for blocking integrations)」**: `preferences` | `statistics` | `marketing`

Used for:
- WP Consent API bridge (`wp_set_consent`) — map custom slug → WP type when accepted
- Google Consent Mode — OR logic with built-in via `integration_map`
- Script/iframe blocking — direct `wpeu_cs_user_has_consent('social')`

Do **not** invent new GCM or WP Consent API types.

## Requirements

### 1. `Categories.php` refactor

- `get_builtin()`, `get_custom()`, `get_all()` merge + `apply_filters( 'wpeu_consent_categories', ... )`
- `is_valid_slug()`, `get_integration_map( $slug )`, `get_enabled_for_banner()`

### 2. Admin UI — Banner tab section 「Categories」

- Table built-in + custom rows; Add category form; Remove custom (nonce)
- Extend `enabled_categories[]` checkboxes dynamically

### 3. Frontend `Banner.php`

- Build categories/sections from `Categories::get_enabled_for_banner()`
- `syncWpeuCookies`: loop all category keys from config
- WP Consent + GCM use `integration_map`

### 4. `ScriptBlocker.php` bootstrap

Replace hardcoded category array with PHP-generated list from `Categories::get_all()` keys.

### 5. `GoogleConsentMode.php`

Compute GCM from all accepted categories via `integration_map` helper.

### 6. Cookie inventory & scanner

Category `<select>` from `Categories::get_all()`.

### 7. Import / export

`SettingsTransfer` — include `custom_categories`, validate on import.

### 8. Tests (PHPUnit)

- `get_all()` includes custom from settings
- Invalid slug rejected; built-in slug cannot be added as custom
- `get_integration_map()` and GCM helper for custom category mapped to marketing

### 9. Docs

Add `docs/custom-categories.md` (when to use, integration_map, limit 5).

## Out of scope

- IAB TCF, removing built-in slugs, unlimited categories

Branch: `jules/cc-23-custom-categories`
One PR.
```

# CC-16 Dynamic languages (no hardcoded locales)

```markdown
Task CC-16 — Dynamic multilingual banner & policy texts without code changes per language.

Depends on CC-15 merged.

## Problem
`BannerTexts::get_locales()` is hardcoded to `en` and `de`. Adding French, Dutch, etc. requires editing PHP. Clients must manage all languages from admin only.

## Requirements

### 1. Dynamic locale discovery
Replace hardcoded `get_locales()` with logic that merges (unique, sorted):
- Languages from **Polylang** (`pll_languages_list` or equivalent) when active
- Languages from **WPML** (`icl_get_languages` or filter) when active
- Locales already present in `wpeu_cs_settings['banner_texts']` or `policy_texts`
- Fallback: site locale (`substr(get_locale(), 0, 2)`) + `en`

Expose filter `wpeu_cs_locales` for final list: `array<string code, string label>`.

### 2. Admin UI — Add language
On Banner and Tools tabs:
- Subsubsub shows all discovered locales (not only en/de)
- **Add language** form: locale code (e.g. `fr`, `nl`, `pl` — 2–5 chars, sanitize_key), display name
- On add: create empty `banner_texts[code]` and `policy_texts[code]` entries prefilled from English defaults (copy, not translate)
- **Remove language** button (with confirm) — removes that locale from settings only, not from Polylang

No PHP file edits required to support a new language.

### 3. Frontend locale detection
Keep existing `get_active_locale()` (Polylang → WPML → WP locale → filter).
If detected locale has no saved texts, fall back to English defaults (current behavior).
If Polylang URL is `/fr/` but `fr` not in admin yet, show EN defaults until client adds `fr` in admin.

### 4. Default strings
Refactor `get_defaults($locale)`:
- Known built-in defaults: `en`, `de` (keep current strings)
- Any other locale: return English defaults (client edits in admin)
- Do NOT require adding new locale to a PHP array

Policy template: generic English template for unknown locales; DE template only for `de`.

### 5. Import/export
JSON export/import must preserve arbitrary locale keys in `banner_texts` and `policy_texts` (already mostly works — verify).

### 6. Tests
PHPUnit:
- `get_locales()` includes Polylang languages when mocked/filter applied
- Adding locale via sanitizer persists new key
- `get_strings('fr')` returns EN defaults when no FR saved

## Out of scope
- Auto-translation (DeepL, etc.)
- .pot file generation (separate task)

One PR.
```

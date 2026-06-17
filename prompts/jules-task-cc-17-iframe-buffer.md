# CC-17 Iframe blocking via output buffer

```markdown
Task CC-17 — Block/replace embedded iframes site-wide (not only `the_content`).

Depends on CC-16 merged.

## Problem

`IframePlaceholder` only filters `the_content`. On real WordPress sites, iframes often appear **outside** post content:

- Theme templates (`get_template_part`, header/footer partials)
- Page builders (Elementor, WPBakery) stored in post meta
- Widgets / block widgets in sidebars
- Plugin shortcodes rendered in custom locations
- ACF field output in templates

Those iframes load third-party trackers (YouTube, Google Maps, Vimeo) **before consent** unless the full HTML output is processed.

## Requirements

### 1. Unify iframe processing

Extract iframe detection + placeholder logic from `IframePlaceholder` into a shared helper (e.g. `IframeProcessor` class or static methods).

### 2. Output buffer integration

In `ScriptBlocker::process_output()` (same buffer as scripts):

- After script processing, run iframe regex replace (YouTube, Vimeo, Google Maps patterns from `ScriptRegistry`)
- If user lacks consent for iframe category → output placeholder HTML (reuse existing placeholder markup/CSS)
- If consent granted → leave iframe unchanged

Respect `enabled_integrations['iframe_placeholder']` and per-service toggles in `enabled_services`.

Start the output buffer when iframe placeholder is enabled, even if script blocker is off.

### 3. Keep `the_content` filter

Avoid double-processing: delegate `the_content` filter to the shared processor or make processor idempotent.

### 4. Tests (PHPUnit)

HTML fixture tests (no theme paths):

- YouTube iframe → placeholder when marketing consent missing
- Google Maps iframe → placeholder when marketing consent missing
- Vimeo iframe → placeholder when marketing consent missing
- Consent granted → iframe unchanged
- Disabled service/integration → unchanged
- Idempotency: already-placeholdered HTML not broken

### 5. Acceptance (any WordPress site)

1. Embed Maps iframe in a theme template (not in post content)
2. Reject marketing → placeholder shown, no third-party load
3. Accept marketing → iframe loads

## Out of scope

- Client/theme-specific file paths or hardcoded template names
- Copying GPL code from third-party plugins

## Reference (in-repo only)

- `includes/Integrations/IframePlaceholder.php`
- `includes/Frontend/ScriptBlocker.php`
- `includes/Frontend/ScriptRegistry.php`

One PR.
```

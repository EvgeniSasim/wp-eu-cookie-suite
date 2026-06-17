# CC-17 Iframe blocking via output buffer

```markdown
Task CC-17 — Block/replace iframes outside `the_content` (BSB map_block, ACF components).

Depends on CC-16 merged (or CC-15 if CC-16 not ready — can run parallel).

## Problem
`IframePlaceholder` only filters `the_content`. BSB theme renders Google Maps via `get_template_part('components/map_block')` with raw `<iframe src="maps.google.com/...">` — loads without consent.

## Requirements

### 1. Unify iframe processing
Extract iframe logic from `IframePlaceholder` into shared helper (e.g. `IframeProcessor` or static methods on `IframePlaceholder`).

### 2. Output buffer integration
In `ScriptBlocker::process_output()` (same buffer as scripts):
- After script processing, run iframe regex replace (YouTube, Vimeo, Google Maps patterns from `ScriptRegistry`)
- If user lacks consent for iframe category → output placeholder HTML (reuse existing placeholder markup/CSS)
- If consent granted → leave iframe unchanged

Respect `enabled_integrations['iframe_placeholder']` and per-service toggles in `enabled_services`.

### 3. Keep `the_content` filter
Avoid double-processing: either remove `the_content` filter when buffer is active, or make processor idempotent (skip already-placeholdered content).

### 4. BSB verification case
Must block iframe in:
`wp-content/themes/BSB/components/map_block.php` pattern:
`src="https://maps.google.com/maps?q=..."`

Category: `marketing` (matches registry `google-maps`).

### 5. Tests
PHPUnit for iframe matching helper (maps URL → category, placeholder when no consent mock).

## Reference
Old addon: `bsb-complianz-cookie-blocker/includes/class-script-registry.php` — maps patterns.

One PR.
```

# CC-18 GA cookie guard & script hardening

```markdown
Task CC-18 — GA/_ga cookie cleanup and enqueued-script fallback blocking.

Depends on CC-17 merged.

## Problem

Output buffer blocking is not enough on all sites:

- `_ga` / `_gid` / `_ga_*` cookies may persist after reject/revoke
- Google Site Kit and other plugins enqueue `google_gtagjs` via `wp_enqueue_script` — can bypass buffer if printed before buffer starts
- Maps/ads scripts may slip through incomplete registry patterns

## Requirements

### 1. New integration class `GoogleAnalyticsGuard`

Original implementation (do not copy GPL code from third-party plugins):

- **`send_headers`**: expire GA cookies server-side when statistics consent not granted
- **`wp_footer` priority 1**: lightweight JS to delete `_ga`, `_gid`, `_ga_*`, `_gat*` on deny/revoke
- Listen to **`wpeu-consent-updated`** and **`wpeu-consent-revoked`** (future CC-21)
- **`script_loader_tag` filter**: when no statistics consent, rewrite handles containing `googletagmanager.com/gtag/js` or handle `google_gtagjs` → `type="text/plain" data-category="statistics"` (compatible with existing blocker)
- **`wp_enqueue_scripts` priority 9999**: dequeue `google_gtagjs`, Site Kit consent scripts when no consent

Settings toggle: `enabled_integrations['ga_cookie_guard']` (default **on**).

### 2. Extend `ScriptRegistry` patterns

Add/common harden patterns (category per service definition):

- `googletagmanager.com/gtag/js`, `gtag('config'`, `google_gtagjs`
- `googlesitekit`, `google-analytics.com/analytics.js`
- `maps.gstatic.com`, `maps.googleapis.com`
- `doubleclick.net`, `googleadservices.com`
- Optional: `cdn.amcharts.com`, `interactive-geo-maps` when plugin detected

Keep registry data-driven; no site-specific URLs.

### 3. Google Site Kit integration

`GoogleSiteKit` must respect `enabled_integrations['google_site_kit']`:

- Admin checkbox on Integrations tab (default on when Site Kit active)
- Do not break Site Kit consent mode hooks from CC-08

### 4. Admin UI

Integrations tab:

- Checkbox **「Google Analytics cookie guard」** (statistics category)
- Short help text: clears GA cookies when consent withdrawn

### 5. Tests (PHPUnit)

- GA cookie name matcher (`_ga`, `_ga_XXXXX`, `_gid`)
- `should_block_script()` / guard logic returns true without statistics consent
- Registry includes gtag URL pattern

### 6. Acceptance (any WordPress site)

1. Install Site Kit or add gtag snippet via theme
2. Reject statistics → no `_ga` cookie after page load + reload
3. Accept statistics → `_ga` may appear
4. Revoke (when CC-21 landed) or Reject flow → cookies cleared again

## Out of scope

- Meta Pixel / Facebook cookie guard (separate future task)
- Server-side GA4 Measurement Protocol blocking
- References to client-specific plugins or theme paths

## Reference (in-repo only)

- `includes/Integrations/GoogleSiteKit.php`
- `includes/Frontend/ScriptRegistry.php`
- `includes/Frontend/ScriptBlocker.php`

One PR.
```

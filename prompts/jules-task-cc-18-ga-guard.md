# CC-18 GA cookie guard & script hardening

```markdown
Task CC-18 — GA/_ga protection and script_loader_tag fallback (parity with BSB Complianz addon).

Depends on CC-17 merged.

## Problem
New plugin blocks tags but does not clear `_ga`/`_gid`/`_ga_*` when consent denied/revoked. Site Kit gtag may slip through if output buffer misses enqueued scripts.

## Requirements

### 1. New integration class `GoogleAnalyticsGuard`
Port behavior from `bsb-complianz-cookie-blocker/includes/class-google-analytics-guard.php` (rewrite, do not copy GPL verbatim):

- `send_headers`: clear GA cookies server-side when no statistics consent
- `wp_footer` priority 1: client script to delete `_ga`, `_gid`, `_ga_*`, `_gat*` on deny/revoke
- Listen to `wpeu-consent-updated` event (not `cmplz_*`)
- `script_loader_tag` filter: neutralize `google_gtagjs` and `googletagmanager.com/gtag/js` → `type="text/plain" data-category="statistics"` when no consent
- `wp_enqueue_scripts` priority 9999: dequeue `google_gtagjs`, `googlesitekit-consent-mode` without consent

### 2. Extend ScriptRegistry patterns
Add missing patterns from old BSB registry:
- `googletagmanager.com/gtag/js`, `gtag('config'`, `google_gtagjs`, `googlesitekit`
- `maps.gstatic.com`, `maps.googleapis.com`
- `doubleclick.net`, `googleadservices.com` (marketing)
- `cdn.amcharts.com`, `interactive-geo-maps` when plugin active

### 3. Google Site Kit toggle
`GoogleSiteKit` integration must respect `enabled_integrations['google_site_kit']` (add admin checkbox, default true when Site Kit active).

### 4. Admin
Integrations tab: checkbox "Google Analytics cookie guard" (default on).

### 5. Tests
PHPUnit: cookie name detection, should_block when no consent cookie set.

## Reference paths
- Old: `BSB_/wp-content/plugins/bsb-complianz-cookie-blocker/includes/class-google-analytics-guard.php`
- New: `includes/Integrations/GoogleSiteKit.php`, `Frontend/ScriptRegistry.php`

One PR.
```

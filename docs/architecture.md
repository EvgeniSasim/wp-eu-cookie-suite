# Architecture

## Bootstrap

`WPEU\CookieSuite\Plugin` — singleton, регистрирует хуки на `plugins_loaded`.

## Consent storage

- Cookies: `wpeu_consent` (JSON) + category flags `wpeu_statistics`, `wpeu_marketing`, …
- Совместимость: опционально дублировать `cmplz_*` при миграции с Complianz (фаза позже)

## Script blocker

1. `template_redirect` → start output buffer если не admin/ajax/cron
2. `ob_callback`: заменить `<script src="…">` на placeholder если категория не разрешена
3. Known patterns в `ScriptRegistry` (фильтр `wpeu_known_script_tags`)
4. Iframes: `data-wpeu-category` + lazy load после consent event

## Frontend events

CookieConsent `onConsent` / `onChange` → `wp_set_consent()` + `document.dispatchEvent('wpeu-consent-updated')`

## Scanner

- `WP_Cron` + manual AJAX
- Queue URL → fetch → extract cookies from headers + HTML script src
- Store in `wp_wpeu_cookies` custom table

## Admin

- Capability: `manage_options`
- REST namespace: `wpeu-cookie-suite/v1` для scanner progress и banner preview

## Build

```bash
cd wp-eu-cookie-suite
npm install
npm run build   # cookieconsent + admin bundle
```

## Security

- Nonce на все AJAX/REST
- Sanitize regex patterns (no eval)
- Scanner only for users with `manage_options`, rate limit

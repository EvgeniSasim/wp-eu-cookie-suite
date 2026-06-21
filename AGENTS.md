# AGENTS.md — Privaro Cookie Consent Banner

## Product

WordPress-плагин согласия на cookies для Европы (GDPR / ePrivacy / TTDSG).

Замена связки Complianz + кастомных надстроек одним плагином:
- UI: **CookieConsent v3** (vanilla-cookieconsent)
- Compliance: opt-in по умолчанию для EU, блокировка до согласия, WP Consent API, Google Consent Mode v2
- Admin: настройки баннера, языки, реестр cookies, сканер

Плагин: **`privaro-cookie-consent-banner/`** · GitHub repo: `EvgeniSasim/wp-eu-cookie-suite`

## Stack

- **PHP 8.1+**, WordPress 6.4+
- **Frontend**: vanilla-cookieconsent 3.x (npm → bundle в `assets/`)
- **Admin**: WP Settings API
- **Tests**: PHPUnit, Playwright (E2E)

## Conventions

- Один PR Jules = одна задача из `prompts/jules-task-cc-*.md`
- Минимальный diff, без scope creep
- PSR-4: namespace `WPEU\CookieSuite\`
- Текстовый домен: `privaro-cookie-consent-banner`
- Не копировать GPL-код Complianz дословно — только идеи и поведение
- CookieConsent — MIT, подключать как vendor/bundle

## Jules workflow

1. Cursor мержит предыдущий PR в `main`
2. `JULES_TASK=jules-task-cc-NN-….md python3 scripts/jules_create_sessions.py`
3. Jules открывает PR → Cursor ревью → merge → следующая задача

Трекер: `docs/jules-sessions.md`

## Reference (read-only)

При сравнении с другими CMP (например Complianz free) — только поведение и идеи, **не** копировать GPL-код и **не** включать Complianz как зависимость. Никаких client/theme-specific путей в коде плагина.

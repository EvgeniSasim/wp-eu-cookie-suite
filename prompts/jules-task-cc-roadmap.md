# Jules roadmap — WP EU Cookie Suite

| | |
|--|--|
| Спецификация | [docs/product-spec.md](../docs/product-spec.md) |
| Репозиторий | `sources/github/EvgeniSasim/wp-eu-cookie-suite` · `main` |
| Правило | **1 сессия = 1 PR = 1 файл** `jules-task-cc-*.md` |
| Scope | **Любой WordPress-сайт** — без client/theme-specific путей (см. `AGENTS.md`) |

## Порядок

| # | Файл | Ветка PR |
|---|------|----------|
| 01 | jules-task-cc-01-scaffold.md | `jules/cc-01-scaffold` |
| 02 | jules-task-cc-02-admin-shell.md | `jules/cc-02-admin` |
| 03 | jules-task-cc-03-cookieconsent-frontend.md | `jules/cc-03-banner` |
| 04 | jules-task-cc-04-categories-options.md | `jules/cc-04-categories` |
| 05 | jules-task-cc-05-script-blocker.md | `jules/cc-05-blocker` |
| 06 | jules-task-cc-06-script-registry.md | `jules/cc-06-registry` |
| 07 | jules-task-cc-07-wp-consent-api.md | `jules/cc-07-wp-consent` |
| 08 | jules-task-cc-08-google-consent-mode.md | `jules/cc-08-gcm` |
| 09 | jules-task-cc-09-cookie-scanner.md | `jules/cc-09-scanner` |
| 10 | jules-task-cc-10-cookie-inventory.md | `jules/cc-10-inventory` |
| 11 | jules-task-cc-11-multilingual.md | `jules/cc-11-i18n` |
| 12 | jules-task-cc-12-banner-customizer.md | `jules/cc-12-customizer` |
| 13 | jules-task-cc-13-legal-shortcodes.md | `jules/cc-13-legal` |
| 14 | jules-task-cc-14-integrations.md | `jules/cc-14-integrations` |
| 15 | jules-task-cc-15-tests.md | `jules/cc-15-tests` |

## Фаза 2 — production & wordpress.org

| # | Файл | Ветка PR | Приоритет |
|---|------|----------|-----------|
| 16 | jules-task-cc-16-dynamic-languages.md | `jules/cc-16-languages` | P0 — языки без правок кода |
| 17 | jules-task-cc-17-iframe-buffer.md | `jules/cc-17-iframe-buffer` | P0 — iframe blocking site-wide (output buffer) |
| 18 | jules-task-cc-18-ga-guard.md | `jules/cc-18-ga-guard` | P0 — _ga cookies, gtag fallback |
| 19 | jules-task-cc-19-consent-ux.md | `jules/cc-19-consent-ux` | P1 — cookie settings link, toggles |
| 20 | jules-task-cc-20-ci.md | `jules/cc-20-ci` | P1 — CI + preview regression |
| 23 | jules-task-cc-23-phpunit-coverage.md | `jules/cc-23-phpunit` | P1 — scanner/log/sanitize/uninstall tests |
| 24 | jules-task-cc-24-playwright-e2e.md | `jules/cc-24-playwright` | P1 — Playwright E2E admin + frontend |

## Запуск

```bash
cd /Users/evgenii/Desktop/wp-eu-cookie-suite
set -a && source ~/business/.env && set +a
export JULES_SOURCE=sources/github/EvgeniSasim/wp-eu-cookie-suite
export JULES_BRANCH=main

JULES_TASK=jules-task-cc-01-scaffold.md python3 scripts/jules_create_sessions.py
```

После merge PR → следующая задача. Трекер: [docs/jules-sessions.md](../docs/jules-sessions.md).

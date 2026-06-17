# Jules sessions — WP EU Cookie Suite

Репозиторий: `EvgeniSasim/wp-eu-cookie-suite`  
Source: `sources/github/EvgeniSasim/wp-eu-cookie-suite`  
Branch: `main`

| # | Task file | Session ID | PR | Status |
|---|-----------|------------|-----|--------|
| 0 | scaffold (Cursor) | — | — | done (initial commit) |
| 1 | jules-task-cc-01-scaffold.md | [9071563733185945188](https://jules.google.com/session/9071563733185945188) | [#1](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/1) | merged |
| 2 | jules-task-cc-02-admin-shell.md | [6993865298522113828](https://jules.google.com/session/6993865298522113828) | [#2](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/2) | merged |
| 3 | jules-task-cc-03-cookieconsent-frontend.md | [3101285059705518251](https://jules.google.com/session/3101285059705518251) | — (manual merge) | merged |
| 4 | jules-task-cc-04-categories-options.md | [17778466866209283685](https://jules.google.com/session/17778466866209283685) | | in progress |
| 5 | jules-task-cc-05-script-blocker.md | | | pending |
| 6 | jules-task-cc-06-script-registry.md | | | pending |
| 7 | jules-task-cc-07-wp-consent-api.md | | | pending |
| 8 | jules-task-cc-08-google-consent-mode.md | | | pending |
| 9 | jules-task-cc-09-cookie-scanner.md | | | pending |
| 10 | jules-task-cc-10-cookie-inventory.md | | | pending |
| 11 | jules-task-cc-11-multilingual.md | | | pending |
| 12 | jules-task-cc-12-banner-customizer.md | | | pending |
| 13 | jules-task-cc-13-legal-shortcodes.md | | | pending |
| 14 | jules-task-cc-14-integrations.md | | | pending |
| 15 | jules-task-cc-15-tests.md | | | pending |

## Команды

```bash
cd /Users/evgenii/Desktop/wp-eu-cookie-suite
export JULES_SOURCE=sources/github/EvgeniSasim/wp-eu-cookie-suite
export JULES_BRANCH=main
JULES_TASK=jules-task-cc-01-scaffold.md python3 scripts/jules_create_sessions.py
```

## Ревью-чеклист (Cursor)

- [ ] Один PR = одна задача, без лишнего scope
- [ ] PSR-4, `wp-eu-cookie-suite` text domain
- [ ] Нет секретов в коде
- [ ] PHPCS-friendly (escaping, nonces)
- [ ] Соответствие north-star для этой фазы

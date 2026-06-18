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
| 4 | jules-task-cc-04-categories-options.md | [17778466866209283685](https://jules.google.com/session/17778466866209283685) | [#3](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/3) | merged |
| 5 | jules-task-cc-05-script-blocker.md | [18367515720455886313](https://jules.google.com/session/18367515720455886313) | [#4](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/4) | merged |
| 6 | jules-task-cc-06-script-registry.md | [9673288998111504244](https://jules.google.com/session/9673288998111504244) | [#5](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/5) | merged |
| 7 | jules-task-cc-07-wp-consent-api.md | [3819341942527512964](https://jules.google.com/session/3819341942527512964) | [#6](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/6) | merged |
| 8 | jules-task-cc-08-google-consent-mode.md | [6702638541194870689](https://jules.google.com/session/6702638541194870689) | — (manual, Jules blocked) | merged |
| 9 | jules-task-cc-09-cookie-scanner.md | [5158379038047431724](https://jules.google.com/session/5158379038047431724) | [#7](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/7) | merged |
| 10 | jules-task-cc-10-cookie-inventory.md | [16281992910030111432](https://jules.google.com/session/16281992910030111432) | [#9](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/9) | merged |
| 11 | jules-task-cc-11-multilingual.md | [15758632919316872286](https://jules.google.com/session/15758632919316872286) | [#10](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/10) | merged |
| 12 | jules-task-cc-12-banner-customizer.md | [9423596550467606091](https://jules.google.com/session/9423596550467606091) | [#11](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/11) | merged |
| 13 | jules-task-cc-13-legal-shortcodes.md | [10804725820254881360](https://jules.google.com/session/10804725820254881360) | [#12](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/12) | merged |
| 14 | jules-task-cc-14-integrations.md | [14941124310403673538](https://jules.google.com/session/14941124310403673538) | [#13](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/13) | merged |
| 15 | jules-task-cc-15-tests.md | [11065622560920599686](https://jules.google.com/session/11065622560920599686) | [#15](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/15) (Cursor) | merged — Jules [#14](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/14) duplicate, session superseded |

## Фаза 2 (production & wordpress.org)

| # | Task file | Session ID | PR | Status |
|---|-----------|------------|-----|--------|
| 16 | jules-task-cc-16-dynamic-languages.md | [7316212288513132930](https://jules.google.com/session/7316212288513132930) | [#16](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/16) | merged |
| 16.1 | jules-task-cc-16-1-admin-preview-ui.md | | | pending (Cursor hotfix in stash) |
| 17 | jules-task-cc-17-iframe-buffer.md | [14920897654020325449](https://jules.google.com/session/14920897654020325449) | [#17](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/17) | merged |
| 18 | jules-task-cc-18-ga-guard.md | — | [#18](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/18) | merged (Cursor) |
| 19 | jules-task-cc-19-consent-ux.md | [13352903253669476636](https://jules.google.com/session/13352903253669476636) (duplicate) | [#19](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/19) | merged (Cursor) |
| 23 | jules-task-cc-23-custom-categories.md | [2413275180526383754](https://jules.google.com/session/2413275180526383754) (superseded) | — | merged (Cursor) |
| 20 | jules-task-cc-20-ci.md | [1120338809836275782](https://jules.google.com/session/1120338809836275782) | [#21](https://github.com/EvgeniSasim/wp-eu-cookie-suite/pull/21) | merged |
| 21 | jules-task-cc-21-consent-records.md | | | pending (after CC-23) |
| 22 | jules-task-cc-22-wordpress-org-release.md | | | pending |

## Команды

```bash
cd /Users/evgenii/Desktop/wp-eu-cookie-suite
export JULES_SOURCE=sources/github/EvgeniSasim/wp-eu-cookie-suite
export JULES_BRANCH=main
JULES_TASK=jules-task-cc-01-scaffold.md python3 scripts/jules_create_sessions.py
```

## Ревью-чеклист (Cursor) — roadmap complete

- [x] Один PR = одна задача, без лишнего scope
- [x] PSR-4, `wp-eu-cookie-suite` text domain
- [x] Нет секретов в коде
- [x] PHPCS-friendly (escaping, nonces)
- [x] Соответствие product-spec для этой фазы
- [x] PHPUnit + import/export JSON (CC-15)
- [x] Hotfix: parse error в ScriptBlocker.php (CC-14 regression)

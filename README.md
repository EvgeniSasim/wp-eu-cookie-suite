# WP EU Cookie Suite

WordPress-плагин cookie consent для EU/DE (GDPR, ePrivacy, TTDSG): баннер на [vanilla-cookieconsent](https://cookieconsent.orestbida.com/), блокировка скриптов, сканер cookies, мультиязычность, WP Consent API.

## Разработка

| Роль | Кто |
|------|-----|
| Архитектура, ревью, постановка задач | Cursor (Auto) |
| Код | [Jules](https://jules.google.com) |

### Jules

```bash
export JULES_SOURCE=sources/github/EvgeniSasim/wp-eu-cookie-suite
export JULES_BRANCH=main
JULES_TASK=jules-task-cc-01-scaffold.md python3 scripts/jules_create_sessions.py
```

Токен: `JULES_API_KEY` из `~/business/.env`.

Roadmap: [prompts/jules-task-cc-roadmap.md](prompts/jules-task-cc-roadmap.md)  
North Star: [docs/north-star-spec.md](docs/north-star-spec.md)  
Трекер сессий: [docs/jules-sessions.md](docs/jules-sessions.md)

## Установка (dev)

Скопируйте `wp-eu-cookie-suite/` в `wp-content/plugins/` или симлинк:

```bash
ln -s "$(pwd)/wp-eu-cookie-suite" /path/to/wp-content/plugins/wp-eu-cookie-suite
```

## Лицензия

GPL-2.0-or-later

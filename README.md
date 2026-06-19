# EU Cookie Consent Suite

WordPress-плагин cookie consent для EU/DE (GDPR, ePrivacy, TTDSG): баннер на [vanilla-cookieconsent](https://cookieconsent.orestbida.com/), блокировка скриптов, сканер cookies, мультиязычность, WP Consent API.

**Changelog:** [CHANGELOG.md](CHANGELOG.md) · **Breaking changes:** [BREAKING_CHANGES.md](BREAKING_CHANGES.md)

## Возможности

- Баннер и модал настроек (CookieConsent v3)
- Категории: necessary, preferences, statistics, marketing
- Блокировка скриптов и iframe-placeholder (YouTube, Vimeo, Maps)
- Google Consent Mode v2 + интеграция с Google Site Kit
- WP Consent API (polyfill, если плагин не установлен)
- Сканер cookies и инвентарь в БД
- EN/DE тексты баннера и политики
- Экспорт/импорт настроек (JSON) и CSV инвентаря

## Установка

Скопируйте `eu-cookie-consent-suite/` в `wp-content/plugins/` или симлинк:

```bash
ln -s "$(pwd)/eu-cookie-consent-suite" /path/to/wp-content/plugins/eu-cookie-consent-suite
```

Активируйте плагин в админке WordPress. При первой активации создаётся таблица `wp_wpeu_cookies` и настройки по умолчанию.

### Build (опционально)

```bash
cd wp-eu-cookie-suite
npm install
npm run build
```

Собранные assets (`cookieconsent.bundle.js`, `cookieconsent.bundle.css`) уже в репозитории — Node.js на сервере не обязателен.

### Тесты

```bash
composer install
bin/install-wp-tests.sh wordpress_test root '' localhost latest
vendor/bin/phpunit
```

Требуется MySQL с тестовой БД. Переменная `WP_TESTS_DIR` переопределяет путь к WordPress test suite.

## Compliance

Плагин предоставляет технические средства для cookie compliance (баннер, блокировка, документация), но **не является юридической консультацией**. Ответственность за тексты политик и соответствие законодательству лежит на владельце сайта.

Рекомендуется:

1. Опубликовать privacy policy и cookie policy (шорткод `[wpeu_cookie_policy]`)
2. Включить EU mode (opt-in) для посетителей из ЕС
3. Проверить блокировку GA/GTM/Pixel до согласия
4. Вести актуальный cookie inventory (сканер + ручные правки)

## Cookie settings link (footer)

Добавьте шорткод **`[wpeu_manage_consent]`** в футер (виджет HTML, блок или шаблон темы), чтобы посетители могли снова открыть настройки cookies:

```
[wpeu_manage_consent]
[wpeu_manage_consent style="button" label="Cookie settings"]
```

Атрибут `style` — `link` (по умолчанию) или `button`. Текст по умолчанию берётся из строк баннера (`manage_consent_label`) или атрибута `label`.

## Миграция с Complianz (ручной чеклист)

Автоматической миграции нет — перенос настроек выполняется вручную:

1. Установите WP EU Cookie Suite на staging, **не удаляйте** Complianz до проверки
2. Сопоставьте категории: Functional → Necessary, Statistics → Statistics, Marketing → Marketing
3. Скопируйте custom script rules из Complianz в **Integrations → Custom block rules**
4. Настройте URL privacy/cookie policy в табе Banner
5. Замените шорткоды политики на `[wpeu_cookie_policy]` / `[wpeu_cookie_declaration]`
6. Запустите Scanner, импортируйте результаты в inventory
7. Проверьте баннер, GCM, Site Kit, формы (reCAPTCHA → marketing)
8. После успешной проверки деактивируйте Complianz

Экспорт/импорт JSON в табе **Tools** переносит настройки между инстансами WP EU Cookie Suite (не из Complianz).

## Google Consent Mode

При включении в **Integrations** плагин выставляет default `denied` для Google tags и обновляет после выбора в баннере. Совместим с Google Site Kit через `googlesitekit_analytics-4_tag_block_on_consent`.

## Author

**Evgenii Sasim**
- [Instagram](https://www.instagram.com/evgenii.sasim/)
- [GitHub](https://github.com/EvgeniSasim)

## Разработка

| Роль | Кто |
|------|-----|
| Архитектура, ревью, постановка задач | Cursor (Auto) |
| Код | [Jules](https://jules.google.com) |

Roadmap: [prompts/jules-task-cc-roadmap.md](prompts/jules-task-cc-roadmap.md)  
Спецификация: [docs/product-spec.md](docs/product-spec.md)  
Трекер сессий: [docs/jules-sessions.md](docs/jules-sessions.md)

## Лицензия

GPL-2.0-or-later

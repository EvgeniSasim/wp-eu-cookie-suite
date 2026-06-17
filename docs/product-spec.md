# Спецификация продукта — WP EU Cookie Suite

## Цель

Публичный WordPress-плагин для **любых сайтов**: юридически корректный cookie consent в EU/EEA/UK без Complianz Premium. Распространение через wordpress.org; без привязки к конкретному клиенту, теме или агентству.

## Обязательные возможности (MVP → v1)

### 1. Баннер (CookieConsent v3)

- Opt-in по умолчанию для EU; «Reject all» не хуже «Accept all»
- Категории: `necessary`, `preferences`, `statistics`, `marketing`
- Позиция, тема (light/dark), кнопки, ссылки на Privacy / Cookie Policy
- **Мультиязычность**: строки из админки + интеграция Polylang / WPML (фильтры)
- Geo: EU/EEA/UK (опция «строгий режим для всех» для простоты v1)

### 2. Блокировка скриптов

- Output buffer до согласия (как Complianz cookie blocker)
- Реестр известных сервисов: GA4, GTM, Meta Pixel, Hotjar, Clarity, Maps, YouTube, Vimeo
- Кастомные URL/regex в админке
- Placeholder для iframe (YouTube, Maps)
- ACF options / theme injections — хук `wp_head` с отложенной выдачей

### 3. WP Consent API + Google Consent Mode v2

- `wp_has_consent()`, `wp_set_consent()` совместимость
- Default denied для ad_storage, analytics_storage, ad_user_data, ad_personalization
- Update после выбора в баннере

### 4. Админка

- Dashboard: статус (blocker on, consent API, последний скан)
- Banner: внешний вид + тексты по языкам
- Cookies: таблица inventory (имя, домен, категория, описание, срок)
- Scanner: запуск + прогресс
- Integrations: Site Kit, CF7, популярные плагины (тогглы)
- Import/Export JSON настроек (мультисайт)

### 5. Сканер cookies

- Обход главной + sitemap / выбранных URL (WP HTTP API)
- Парсинг Set-Cookie из ответов + эвристики по известным именам (`_ga`, `_fbp`, …)
- Ручное редактирование и привязка к категории
- Шорткод `[wpeu_cookie_table]` для политики

### 6. Юридические хелперы (не юрист!)

- Шаблоны текстов DE/EN (редактируемые)
- Лог согласий: опционально, local DB (без Premium Complianz-фич в v1)
- Документация для DPO: что плагин делает / не делает

## Не в scope v1

- Records of consent с GeoIP A/B (Complianz Premium)
- Полная замена юриста / автогенерация Impressum
- CMP certification IAB TCF (отдельная фаза если нужно)

## Архитектура (кратко)

```
wp-eu-cookie-suite/
├── wp-eu-cookie-suite.php      # bootstrap
├── includes/
│   ├── Plugin.php
│   ├── Admin/
│   ├── Frontend/
│   │   ├── Banner.php          # CookieConsent config
│   │   └── ScriptBlocker.php
│   ├── Consent/
│   │   ├── WpConsentBridge.php
│   │   └── GoogleConsentMode.php
│   ├── Scanner/
│   └── Integrations/
└── assets/                     # built JS/CSS
```

## Критерии приёмки v1

1. На чистом WP + произвольная тема: без согласия нет `_ga`, после Accept statistics — есть
2. GTM/gtag не выполняется до statistics consent
3. Баннер DE + EN переключается с Polylang
4. Сканер находит `_ga` после тестовой страницы с GA snippet
5. Export/import переносит настройки на второй инстанс

## Задачи Jules

См. `prompts/jules-task-cc-roadmap.md` — сессии по одному PR.

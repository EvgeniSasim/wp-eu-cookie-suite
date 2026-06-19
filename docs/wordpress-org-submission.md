# WordPress.org Submission Guide

Этот документ содержит чеклист и инструкции для первичной отправки плагина в официальный репозиторий WordPress.org.

## Подготовка аккаунта
- [ ] Зарегистрируйте аккаунт на [WordPress.org](https://login.wordpress.org/register), если его еще нет.
- [ ] Username должен совпадать с `evgeniisasim` (указан в `Contributors` в `readme.txt`).

## Чеклист перед отправкой
- [ ] Проверьте, что версия в `eu-cookie-consent-suite.php` совпадает со `Stable tag` в `readme.txt` (сейчас `1.0.0`).
- [ ] Убедитесь, что `readme.txt` проходит валидацию в [Readme Validator](https://wordpress.org/plugins/developers/readme-validator/).
- [ ] Убедитесь, что в коде нет секретов, API ключей или ссылок на тестовые стенды.
- [ ] Проверьте наличие `THIRD-PARTY.md` с кредитами библиотек.
- [ ] Убедитесь, что `uninstall.php` корректно удаляет все данные (таблицы и опции).

## Процесс отправки
1. Сгенерируйте ZIP-архив плагина с помощью скрипта:
   ```bash
   bash scripts/build-release.sh
   ```
2. Перейдите на страницу [Add your plugin](https://wordpress.org/plugins/add/).
3. Загрузите файл `build/eu-cookie-consent-suite.zip`.
4. Дождитесь ручного ревью от команды WordPress.org (обычно занимает 1-2 недели).

## Работа с SVN (после аппрува)
После одобрения вам будет предоставлен доступ к SVN-репозиторию.

### Структура SVN:
- `/trunk`: Основная ветка разработки (копия содержимого папки `eu-cookie-consent-suite`).
- `/tags/1.0.0`: Копия trunk для релиза 1.0.0.
- `/assets`: Иконки, баннеры и скриншоты (из папки `wordpress-org/assets`).

### Инструкции по загрузке:
1. Сделайте checkout репозитория:
   ```bash
   svn co https://plugins.svn.wordpress.org/eu-cookie-consent-suite/ my-plugin-svn
   ```
2. Скопируйте файлы плагина в `trunk`.
3. Скопируйте маркетинговые ассеты в `assets`.
4. Создайте тег:
   ```bash
   svn cp trunk tags/1.0.0
   ```
5. Закоммитьте изменения:
   ```bash
   svn ci -m "Initial release 1.0.0"
   ```

## Важные правила
- Используйте Text Domain `eu-cookie-consent-suite` для всех строк перевода.
- Не включайте `node_modules` или `vendor` в итоговый ZIP (скрипт `build-release.sh` делает это автоматически).
- Лицензия должна быть GPLv2 или выше.

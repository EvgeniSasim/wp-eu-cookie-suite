# GitHub + Jules setup (one-time)

## 1. Создать репозиторий и запушить

Локально уже есть коммит в `/Users/evgenii/Desktop/wp-eu-cookie-suite`.

```bash
cd /Users/evgenii/Desktop/wp-eu-cookie-suite
gh auth login   # если ещё не залогинен
gh repo create EvgeniSasim/wp-eu-cookie-suite --private --source=. --remote=origin --push
```

Без `gh` — вручную на github.com: **New repository** `wp-eu-cookie-suite`, затем:

```bash
git remote add origin git@github.com:EvgeniSasim/wp-eu-cookie-suite.git
git push -u origin main
```

## 2. Подключить Jules GitHub App

1. [jules.google.com](https://jules.google.com) → **Connect GitHub**
2. Выдать доступ репозиторию `EvgeniSasim/wp-eu-cookie-suite`
3. Проверка:

```bash
set -a && source ~/business/.env && set +a
curl -sS -H "x-goog-api-key: $JULES_API_KEY" \
  "https://jules.googleapis.com/v1alpha/sources" | grep wp-eu-cookie-suite
```

## 3. Запустить первую задачу Jules

```bash
cd /Users/evgenii/Desktop/wp-eu-cookie-suite
export JULES_SOURCE=sources/github/EvgeniSasim/wp-eu-cookie-suite
export JULES_BRANCH=main
JULES_TASK=jules-task-cc-01-scaffold.md python3 scripts/jules_create_sessions.py
```

## 4. Цикл Cursor (ревью)

1. Jules открывает PR из ветки `jules/cc-01-scaffold`
2. Cursor ревью → merge в `main`
3. Обновить `docs/jules-sessions.md` (session ID, PR URL, status)
4. `JULES_TASK=jules-task-cc-02-admin-shell.md python3 scripts/jules_create_sessions.py`

Повторять до CC-15.

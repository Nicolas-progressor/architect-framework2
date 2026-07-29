# Руководство по публикации на Packagist

## 1. Архитектура пакетов

Монорепозиторий содержит несколько composer-пакетов:

| Пакет | Директория | Статус |
|-------|-----------|--------|
| `architect/framework` | корень (monorepo) | **Главный пакет** |
| `architect/contracts` | `contracts/` | Внешняя зависимость |
| `axiom/orm` | `axiom/` | Внешняя зависимость |
| `architect/blueprint` | `blueprint/` | Внешняя зависимость |
| `architect/queue` | `architect/queue/` | Бандл (bundled) |
| `architect/http-client` | `architect/http-client/` | Бандл (bundled) |
| `architect/auth-system` | `architect/auth-system/` | Бандл (bundled) |
| `architect/blueprint-auth` | `architect/blueprint-auth/` | Бандл (bundled) |
| `architect/blueprint-helpers` | `architect/BlueprintHelpers/` | Бандл (bundled) |
| `architect/blueprint-forms` | `architect/blueprint-forms/` | Бандл (bundled) |

**Внешние зависимости** — отдельные репозитории, опубликованные на Packagist.  
**Бандлы** — встроены в монорепозиторий, публикуются как часть `architect/framework`.

---

## 2. Подготовка внешних пакетов

Каждый пакет из `contracts/`, `axiom/`, `blueprint/` — это отдельный GitHub-репозиторий, который нужно опубликовать на Packagist.

### 2.1. Проверка composer.json

Убедитесь, что в `composer.json` каждого пакета заполнены:

```json
{
    "name": "architect/contracts",
    "description": "...",
    "type": "library",
    "license": "MIT",
    "authors": [...],
    "require": {
        "php": ">=8.2"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

Выровняйте версию PHP до `>=8.2` во всех пакетах (в корне уже `>=8.2`).
Убедитесь, что `require-dev` использует те же версии, что и корень:

```json
"require-dev": {
    "phpunit/phpunit": "^13.0",
    "phpstan/phpstan": "^2.1"
}
```

### 2.2. Создание GitHub-релиза

Для каждого пакета:

```bash
# Переключиться на репозиторий пакета
cd contracts/
git tag v2.0.0
git push origin v2.0.0
```

Теги должны соответствовать [SemVer](https://semver.org/):
- `v2.0.0` — первый стабильный релиз
- `v2.0.1` — баг-фиксы
- `v2.1.0` — новые возможности (обратно совместимые)
- `v3.0.0` — Breaking changes

### 2.3. Регистрация на Packagist

1. Зайдите на https://packagist.org/packages/submit
2. Введите URL GitHub-репозитория (например, `https://github.com/Nicolas-progressor/architect-contracts`)
3. Нажмите **Check** → **Submit**
4. Packagist импортирует теги как версии

### 2.4. Webhook авто-обновления

В GitHub-репозитории:
1. **Settings** → **Webhooks** → **Add webhook**
2. Payload URL: `https://packagist.org/api/github?username=Nicolas-progressor`
3. Content type: `application/json`
4. Secret: API-токен из Packagist (https://packagist.org/profile/)
5. Trigger: **Just the push event**

Либо настройте авто-обновление через GitHub Actions:

```yaml
# .github/workflows/update-packagist.yml
name: Update Packagist

on:
  push:
    tags:
      - '*'

jobs:
  update:
    runs-on: ubuntu-latest
    steps:
      - run: |
          curl -XPOST -d '{"repository":{"url":"https://github.com/Nicolas-progressor/architect-contracts"}}' \
            -H "Content-Type: application/json" \
            "https://packagist.org/api/github?username=Nicolas-progressor&apiToken=${{ secrets.PACKAGIST_TOKEN }}"
```

---

## 3. Публикация основного пакета `architect/framework`

### 3.1. Переключить `path` → `vcs` репозитории

Перед публикацией на Packagist замените `path` репозитории на `vcs` в корневом `composer.json`:

```json
"repositories": [
    {"type": "vcs", "url": "https://github.com/Nicolas-progressor/architect-contracts"},
    {"type": "vcs", "url": "https://github.com/Nicolas-progressor/axiom-orm"},
    {"type": "vcs", "url": "https://github.com/Nicolas-progressor/architect-blueprint"}
]
```

Если пакеты опубликованы на Packagist, VCS-репозитории не нужны — Composer найдёт их в реестре.  
В этом случае удалите секцию `repositories` целиком (кроме `{"type": "path", ...}` для локальной разработки — их можно оставить, Composer игнорирует path-репозитории на Packagist).

> **Важно**: Все пакеты в `require` с `*@dev` должны иметь стабильные теги, чтобы Packagist мог их разрешить. Либо используйте минимальную стабильность: `"minimum-stability": "dev"` (уже установлено).

### 3.2. Версия

Удалите поле `version` из `composer.json` (уже сделано). Версия берётся из git-тега:

```bash
git tag v2.0.0
git push origin v2.0.0
```

### 3.3. Регистрация на Packagist

1. https://packagist.org/packages/submit
2. URL: `https://github.com/Nicolas-progressor/architect-framework2`
3. **Check** → **Submit**

### 3.4. Packagist и path-репозитории

Packagist игнорирует `"type": "path"` репозитории. Для публикации:
- Либо удалите их из `composer.json`
- Либо оставьте — они не влияют на Packagist-установку

В текущем `composer.json` path-репозитории перечисляют все локальные пакеты. Для релиза на Packagist **не нужно их удалять** — Packagist их просто проигнорирует. Но убедитесь, что для `architect/queue`, `architect/auth-system` и других бандлов не требуется path-репозиторий — они должны разрешаться через **replace** (см. ниже).

---

## 4. Проблема: бандлы и path-репозитории на Packagist

Пакеты `architect/queue`, `architect/auth-system` и другие бандлы **не опубликованы** на Packagist. Они существуют только в монорепозитории. На Packagist Composer не сможет их найти.

**Решение**: вернуть секцию `replace` в корневой `composer.json`:

```json
"replace": {
    "architect/queue": "self.version",
    "architect/auth-system": "self.version",
    "architect/blueprint-auth": "self.version",
    "architect/blueprint-helpers": "self.version",
    "architect/blueprint-forms": "self.version"
}
```

С `replace` Composer понимает, что эти пакеты предоставляются `architect/framework`, и не будет искать их на Packagist.

### 4.1. Автозагрузка бандлов при `replace`

При использовании `replace` автозагрузка из composer.json бандлов **не регистрируется**. Нужно добавить их PSR-4 префиксы в корневой `autoload`:

```json
"autoload": {
    "psr-4": {
        "Architect\\": "architect/",
        "Architect\\Queue\\": "architect/queue/src/",
        "Architect\\AuthSystem\\": "architect/auth-system/src/",
        "Architect\\Auth\\": "architect/auth-system/src/",
        "Architect\\BlueprintAuth\\": "architect/blueprint-auth/src/",
        "Architect\\BlueprintHelpers\\": "architect/BlueprintHelpers/",
        "Architect\\BlueprintForms\\": "architect/blueprint-forms/src/",
        "Architect\\Console\\": "architect/Services/Console/",
        "Architect\\HttpClient\\": "architect/http-client/src/",
        "App\\Bundle\\": "src/Bundle/"
    },
    "classmap": [
        "app/apps/",
        "app/modules/"
    ],
    "files": [
        "architect/Support/Aliases.php"
    ]
}
```

---

## 5. Итоговый `composer.json` для публикации на Packagist

Объединяя всё выше:

```json
{
    "name": "architect/framework",
    "description": "Architect RED 2 - PHP фреймворк",
    "type": "project",
    "license": "MIT",
    "require": {
        "php": ">=8.2",
        "architect/contracts": "*@dev",
        "axiom/orm": "*@dev",
        "architect/blueprint": "*@dev",
        "architect/queue": "*@dev",
        "architect/http-client": "*@dev",
        "architect/auth-system": "*@dev",
        "architect/blueprint-auth": "*@dev",
        "psr/container": "^2.0",
        "psr/http-message": "^1.1",
        "psr/http-factory": "^1.0",
        "psr/http-server-middleware": "^1.0",
        "psr/http-server-handler": "^1.0",
        "psr/log": "^1.1",
        "psr/simple-cache": "^3.0",
        "psr/cache": "^3.0",
        "nyholm/psr7": "^1.8"
    },
    "require-dev": {
        "phpunit/phpunit": "^13.0",
        "friendsofphp/php-cs-fixer": "^3.65",
        "phpstan/phpstan": "^2.1"
    },
    "replace": {
        "architect/queue": "self.version",
        "architect/auth-system": "self.version",
        "architect/blueprint-auth": "self.version",
        "architect/blueprint-helpers": "self.version",
        "architect/blueprint-forms": "self.version",
        "axiom/migration": "self.version",
        "axiom/many-to-many": "self.version",
        "axiom/cache": "self.version",
        "axiom/entity": "self.version"
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "autoload": {
        "psr-4": {
            "Architect\\": "architect/",
            "Architect\\Queue\\": "architect/queue/src/",
            "Architect\\AuthSystem\\": "architect/auth-system/src/",
            "Architect\\Auth\\": "architect/auth-system/src/",
            "Architect\\BlueprintAuth\\": "architect/blueprint-auth/src/",
            "Architect\\BlueprintHelpers\\": "architect/BlueprintHelpers/",
            "Architect\\BlueprintForms\\": "architect/blueprint-forms/src/",
            "Architect\\Console\\": "architect/Services/Console/",
            "Architect\\HttpClient\\": "architect/http-client/src/",
            "App\\Bundle\\": "src/Bundle/"
        },
        "classmap": ["app/apps/", "app/modules/"],
        "files": ["architect/Support/Aliases.php"]
    },
    "autoload-dev": {
        "psr-4": { "Tests\\": "tests/" }
    },
    "bin": ["bin/arc"],
    "scripts": {
        "post-install-cmd": [
            "@php bin/arc cache:clear --all 2>/dev/null || true",
            "@php architect/Support/ServiceProviders/cache-providers.php",
            "@php architect/Support/Bundle/cache-bundles.php"
        ],
        "post-update-cmd": [
            "@php bin/arc optimize:autoload 2>/dev/null || true",
            "@php architect/Support/ServiceProviders/cache-providers.php",
            "@php architect/Support/Bundle/cache-bundles.php"
        ],
        "package:discover": [
            "@php architect/Support/ServiceProviders/cache-providers.php",
            "@php architect/Support/Bundle/cache-bundles.php"
        ]
    }
}
```

---

## 6. Установка конечным пользователем

### Через `composer create-project` (рекомендуется)

```bash
composer create-project architect/framework2 my-project --stability=dev
```

### Через `composer require` (в существующий проект)

```bash
composer require architect/framework *@dev
```

### После установки

```bash
cd my-project

# Настройка окружения
cp .env.example .env
php bin/arc key:generate

# Кеширование провайдеров и бандлов
php bin/arc optimize:autoload
php architect/Support/ServiceProviders/cache-providers.php
php architect/Support/Bundle/cache-bundles.php

# Права на storage
chmod -R 775 storage/ cache/
```

---

## 7. Разработка в монорепозитории (локально)

Текущая конфигурация с `path` репозиториями — **только для разработки**. При установке через Packagist эти репозитории игнорируются.

Для переключения между режимами:
- **Разработка**: `path` репозитории + `replace` не нужен
- **Релиз на Packagist**: `replace` + эксплицитный autoload для бандлов

Можно держать оба режима в одном `composer.json`, но проще иметь две конфигурации и переключаться через скрипт или CI:

```bash
# scripts/switch-dev.sh — переключить на разработку
composer config repositories.contracts '{"type": "path", "url": "./contracts"}'
composer config --unset replace

# scripts/switch-release.sh — переключить на релиз
composer config repositories --unset
composer config replace '{"architect/queue": "self.version", ...}'
```

---

## 8. CI/CD публикация

Пример GitHub Actions для авто-публикации на Packagist при создании тега:

```yaml
name: Publish to Packagist

on:
  push:
    tags:
      - 'v*'

jobs:
  publish:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Switch to release mode
        run: |
          # Удалить path-репозитории, добавить replace + автозагрузку
          sed -i '/"type": "path"/,+1d' composer.json

      - name: Notify Packagist
        run: |
          curl -XPOST -d '{"repository":{"url":"https://github.com/Nicolas-progressor/architect-framework2"}}' \
            -H "Content-Type: application/json" \
            "https://packagist.org/api/github?username=Nicolas-progressor&apiToken=${{ secrets.PACKAGIST_TOKEN }}"
```

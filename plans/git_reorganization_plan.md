# План Git-реорганизации: 7 репозиториев

**Дата:** 25 июля 2026  
**Стратегия:** Полная реорганизация  
**Оценка:** ~10 дней

---

## 1. Целевая структура

### 1.1 Карта репозиториев

| # | Репозиторий | Описание | Тип | Пакеты |
|---|------------|----------|-----|--------|
| 1 | `architect/framework` | Ядро фреймворка | Main | framework, app/, src/, tests/, docs/ |
| 2 | `architect/contracts` | Контракты интерфейсов | Solo | container, application, router, service-provider |
| 3 | `axiom/orm` | ORM экосистема | Monorepo | orm, entity, migration, many-to-many, cache |
| 4 | `architect/blueprint` | Шаблонизатор | Monorepo | blueprint, blueprint-forms, blueprint-helpers |
| 5 | `architect/http-client` | HTTP клиент | Solo | http-client |
| 6 | `architect/auth-system` | Аутентификация | Solo | auth-system |
| 7 | `architect/queue` | Система очередей | Solo | queue |
| 8 | `architect/blueprint-auth` | Glue: Blueprint + Auth | Solo | blueprint-auth |

---

## 2. Разрыв циклических зависимостей

### 2.1 Текущая проблема
```
architect/framework ──require──► architect/auth-system
architect/auth-system ──require──► architect/framework  ⚠️ ЦИКЛ

architect/framework ──require──► architect/queue
architect/queue ──require──► architect/framework  ⚠️ ЦИКЛ
```

### 2.2 Решение: `architect/contracts`

```
architect/contracts (НОВЫЙ)
├── src/
│   ├── ContainerInterface.php      # DI контейнер
│   ├── ApplicationInterface.php    # Приложение
│   ├── RouterInterface.php         # Маршрутизатор
│   ├── ServiceContainerInterface.php
│   └── ServiceProviderInterface.php # Сервис-провайдер
├── composer.json
└── tests/
```

После рефакторинга:
```
architect/framework ──require──► architect/contracts
architect/auth-system ──require──► architect/contracts  ✅
architect/queue ──require──► architect/contracts  ✅
```

---

## 3. Шаги реализации

### Фаза 1: Подготовка (1 день)

| # | Задача | Время |
|---|--------|-------|
| 1.1 | Создать `architect/contracts` пакет | 2 часа |
| 1.2 | Определить контракты (ContainerInterface, ApplicationInterface, RouterInterface) | 2 часа |
| 1.3 | Написать тесты для контрактов | 2 часа |

### Фаза 2: Рефакторинг (3 дня)

| # | Задача | Время |
|---|--------|-------|
| 2.1 | `architect/framework` → реализует контракты из contracts | 1 день |
| 2.2 | `architect/auth-system` → зависит от contracts вместо framework | 0.5 дня |
| 2.3 | `architect/queue` → зависит от contracts вместо framework | 0.5 дня |
| 2.4 | `architect/http-client` → проверить независимость | 0.5 дня |
| 2.5 | Исправить все breaking changes | 0.5 дня |

### Фаза 3: Создание репозиториев (4 дня)

| # | Репозиторий | Задачи | Время |
|---|------------|--------|-------|
| 3.1 | `axiom/orm` | git init, переместить пакеты, настроить composer.json, README, LICENSE | 1 день |
| 3.2 | `architect/blueprint` | git init, переместить пакеты, настроить composer.json, README, LICENSE | 1 день |
| 3.3 | `architect/http-client` | git init, переместить пакеты, настроить composer.json | 0.5 дня |
| 3.4 | `architect/auth-system` | git init, переместить пакеты, настроить composer.json | 0.5 дня |
| 3.5 | `architect/queue` | git init, переместить пакеты, настроить composer.json | 0.5 дня |
| 3.6 | `architect/blueprint-auth` | git init, переместить пакеты, настроить composer.json | 0.5 дня |

### Фаза 4: Обновление главного репо (1 день)

| # | Задача | Время |
|---|--------|-------|
| 4.1 | Обновить `architect/framework/composer.json` (VCS repos вместо path) | 2 часа |
| 4.2 | Настроить `.gitignore` | 1 час |
| 4.3 | Инициализировать git в корне | 30 мин |
| 4.4 | Первый commit | 30 мин |
| 4.5 | Проверить `composer install` | 1 час |

### Фаза 5: CI/CD (1 день)

| # | Задача | Время |
|---|--------|-------|
| 5.1 | Настроить GitHub Actions для каждого репо | 1 день |

---

## 4. Структура каждого репозитория

### 4.1 `architect/framework`
```
architect/framework/
├── app/                          # Код приложений
├── src/                          # Ядро фреймворка
├── architect/                    # Оставшиеся модули (если не вынесены)
├── tests/
├── docs/, techdocs/, examples/
├── bootstrap/
├── htdocs/
├── database/, migrations/
├── docker/
├── bin/
├── .github/workflows/ci.yml
├── .gitignore
├── .env.example
├── composer.json
├── phpunit.xml
├── phpstan.neon
├── .php-cs-fixer.dist.php
├── README.md
├── LICENSE
└── CHANGELOG.md
```

### 4.2 `architect/contracts`
```
architect/contracts/
├── src/
│   ├── ContainerInterface.php
│   ├── ApplicationInterface.php
│   ├── RouterInterface.php
│   ├── ServiceContainerInterface.php
│   └── ServiceProviderInterface.php
├── tests/
├── composer.json
├── phpunit.xml
├── .github/workflows/ci.yml
├── README.md
└── LICENSE
```

### 4.3 `axiom/orm` (monorepo)
```
axiom/orm/
├── src/                          # axiom/orm core
├── modules/
│   ├── entity/                   # axiom/entity
│   ├── migration/                # axiom/migration
│   ├── many-to-many/             # axiom/many-to-many
│   └── cache/                    # axiom/cache
├── tests/
├── composer.json
├── phpunit.xml
├── phpstan.neon
├── .github/workflows/ci.yml
├── README.md
└── LICENSE
```

### 4.4 `architect/blueprint` (monorepo)
```
architect/blueprint/
├── src/                          # architect/blueprint core
├── extensions/
│   ├── forms/                    # architect/blueprint-forms
│   └── helpers/                  # architect/blueprint-helpers
├── tests/
├── composer.json
├── phpunit.xml
├── phpstan.neon
├── .github/workflows/ci.yml
├── README.md
└── LICENSE
```

### 4.5 `architect/http-client`
```
architect/http-client/
├── src/
├── tests/
├── composer.json
├── phpunit.xml
├── .github/workflows/ci.yml
├── README.md
└── LICENSE
```

### 4.6 `architect/auth-system`
```
architect/auth-system/
├── src/
├── tests/
├── composer.json
├── phpunit.xml
├── .github/workflows/ci.yml
├── README.md
└── LICENSE
```

### 4.7 `architect/queue`
```
architect/queue/
├── src/
├── tests/
├── composer.json
├── phpunit.xml
├── .github/workflows/ci.yml
├── README.md
└── LICENSE
```

### 4.8 `architect/blueprint-auth`
```
architect/blueprint-auth/
├── src/
├── composer.json
├── .github/workflows/ci.yml
├── README.md
└── LICENSE
```

---

## 5. composer.json для главного репо (после реорганизации)

```json
{
    "name": "architect/framework",
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/architect/contracts"
        },
        {
            "type": "vcs",
            "url": "https://github.com/axiom/orm"
        },
        {
            "type": "vcs",
            "url": "https://github.com/architect/blueprint"
        },
        {
            "type": "vcs",
            "url": "https://github.com/architect/http-client"
        },
        {
            "type": "vcs",
            "url": "https://github.com/architect/auth-system"
        },
        {
            "type": "vcs",
            "url": "https://github.com/architect/queue"
        },
        {
            "type": "vcs",
            "url": "https://github.com/architect/blueprint-auth"
        }
    ],
    "require": {
        "architect/contracts": "^1.0",
        "axiom/orm": "^1.0",
        "axiom/entity": "^1.0",
        "axiom/migration": "^1.0",
        "architect/blueprint": "^1.0",
        "architect/auth-system": "^1.0",
        "architect/blueprint-auth": "^1.0",
        "architect/blueprint-forms": "^1.0",
        "architect/http-client": "^1.0",
        "architect/blueprint-helpers": "^1.0",
        "architect/queue": "^1.0"
    }
}
```

---

## 6. .gitignore (для главного репо)

```gitignore
# Dependencies
/vendor/
node_modules/

# IDE
.idea/
.vscode/
nbproject/

# Environment
.env

# Cache
/cache/
/bootstrap/cache/
*.cache

# Logs
*.log

# OS
.DS_Store
Thumbs.db

# Docker
docker/*.sqlite

# Test artifacts
coverage.xml
.phpunit.result.cache
.phpstan.cache

# Temporary
storage/*
!storage/.gitkeep
*.tmp
*.swp
```

---

## 7. Риски и митигация

| Риск | Вероятность | Влияние | Митигация |
|------|-------------|---------|-----------|
| Breaking changes при рефакторинге контрактов | Высокая | Высокое | Поэтапное тестирование, feature flags |
| Потеря git history при переносе | Средняя | Среднее | Использовать `git subtree` или `git filter-branch` |
| Composer не находит пакеты | Средняя | Среднее | Тестировать `composer install` после каждого шага |
| CI/CD ломается | Низкая | Среднее | Запускать локально перед пушем |
| Зависимости не резолвятся | Средняя | Высокое | Проверять `composer validate` для каждого пакета |

---

## 8. Чек-лист перед запуском

- [ ] Все файлы скопированы в новые директории
- [ ] `composer.json` каждого пакета обновлён
- [ ] Циклические зависимости разорваны (contracts)
- [ ] `composer install` работает в каждом репо
- [ ] `phpunit.xml` настроен в каждом репо
- [ ] `.gitignore` настроен
- [ ] `LICENSE` добавлен
- [ ] `README.md` добавлен
- [ ] GitHub Actions workflow добавлен
- [ ] Все тесты проходят
- [ ] Нет breaking changes в публичном API

---

## 9. Итог

| Метрика | Значение |
|---------|----------|
| Всего репозиториев | 8 |
| Монорепо | 2 (axiom/orm, architect/blueprint) |
| Solo репо | 6 |
| Время на реализацию | ~10 дней |
| Новые пакеты | 1 (architect/contracts) |

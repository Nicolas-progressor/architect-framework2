# План решения: CI/CD для Architect Framework 2.0

**Проблема:** GAP-02 — Нет CI/CD конфигурации, нет автоматической проверки качества  
**Дата:** 25 июля 2026  
**Приоритет:** Критический

---

## 1. Текущее состояние

- Проект: PHP 8+ фреймворк (Composer, PHPUnit)
- Тесты: PHPUnit + 10 ручных test-*.php скриптов
- Линтеры/статический анализ: отсутствуют
- Docker: docker-compose.yml с Nginx + PHP-FPM
- Git: .gitignore настроен

---

## 2. Цели CI/CD

### 2.1 Что автоматизировать
| Этап | Описание |
|------|----------|
| **Lint** | PHP-CS-Fixer (стандарт кода) |
| **Static Analysis** | PHPStan (статический анализ типов) |
| **Unit Tests** | PHPUnit с покрытием кода |
| **Integration Tests** | Docker-окружение (MySQL, PostgreSQL, Redis) |
| **Build** | Composer install, кеширование зависимостей |
| **Security Audit** | Composer audit (уязвимости в зависимостях) |

### 2.2 Когда запускать
| Триггер | Действия |
|---------|----------|
| Push в main/master | lint, static analysis, tests, build |
| Pull Request | lint, static analysis, tests, build, coverage report |
| Tag (release) | полный pipeline + Docker build + publish |

---

## 3. Структура pipeline

```
┌─────────────────────────────────────────────────────┐
│                    CI Pipeline                       │
├──────────┬──────────┬──────────┬──────────┬─────────┤
│  Lint    │  Static  │  Tests   │  Build   │ Security│
│  (fixer) │ Analysis │ (PHPUnit)│ (Docker) │  Audit  │
│          │ (PHPStan)│          │          │         │
└──────────┴──────────┴──────────┴──────────┴─────────┘
```

---

## 4. Файлы для создания

### 4.1 `.github/workflows/ci.yml`
Основной workflow GitHub Actions.

### 4.2 `.php-cs-fixer.dist.php`
Конфигурация PHP-CS-Fixer.

### 4.3 `phpstan.neon`
Конфигурация PHPStan.

### 4.4 Обновить `composer.json`
Добавить dev-зависимости: php-cs-fixer, phpstan.

### 4.5 Обновить `phpunit.xml`
Настроить coverage и logging.

---

## 5. Детали реализации

### 5.1 GitHub Actions Workflow (`.github/workflows/ci.yml`)

```yaml
name: CI Pipeline

on:
  push:
    branches: [main, master]
  pull_request:
    branches: [main, master]

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: php-cs-fixer
      - run: php-cs-fixer fix --dry-run --diff

  static-analysis:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install --no-progress
      - run: vendor/bin/phpstan analyse

  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: test_db
        ports: ['3306:3306']
      redis:
        image: redis:7
        ports: ['6379:6379']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: xdebug
      - run: composer install --no-progress
      - run: vendor/bin/phpunit --coverage-clover=coverage.xml
      - uses: codecov/codecov-action@v4
        with:
          file: coverage.xml

  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install --no-progress
      - run: composer audit
```

### 5.2 PHP-CS-Fixer (`.php-cs-fixer.dist.php`)

```php
<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/architect')
    ->in(__DIR__ . '/axiom')
    ->in(__DIR__ . '/blueprint')
    ->in(__DIR__ . '/app')
    ->exclude('vendor')
    ->exclude('cache')
    ->exclude('storage');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS2.0' => true,
        '@PHP82Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
        'declare_strict_types' => false,
    ])
    ->setFinder($finder);
```

### 5.3 PHPStan (`phpstan.neon`)

```neon
parameters:
    paths:
        - src
        - architect
        - axiom
        - blueprint
        - app
    level: 5
    excludePaths:
        - vendor
        - cache
        - storage
        - tests
    treatPhpDocTypesAsCertain: false
    reportUnmatchedIgnoredErrors: false
```

### 5.4 Dev-зависимости в `composer.json`

```json
{
    "require-dev": {
        "friendsofphp/php-cs-fixer": "^3.65",
        "phpstan/phpstan": "^2.1"
    }
}
```

---

## 6. Интеграция с существующей структурой

| Компонент | Совместимость | Примечание |
|-----------|---------------|------------|
| PHPUnit 13 | ✅ | Уже установлен |
| Composer | ✅ | autoload настроен |
| Docker | ✅ | docker-compose.yml для integration tests |
| app/config/*.json | ✅ | Конфиги не влияют на CI |
| app/logs/ | ⚠️ | Исключить из lint/analysis |
| cache/ | ⚠️ | Исключить из lint/analysis |
| vendor/ | ⚠️ | Исключить, кешировать |

---

## 7. Этапы внедрения

| # | Задача | Время | Зависимости |
|---|--------|-------|-------------|
| 1 | Создать `.github/workflows/` | 0.5 дня | — |
| 2 | Создать `.php-cs-fixer.dist.php` | 0.5 дня | — |
| 3 | Создать `phpstan.neon` | 0.5 дня | — |
| 4 | Обновить `composer.json` (dev-deps) | 0.5 дня | — |
| 5 | Настроить `phpunit.xml` (coverage) | 0.5 дня | — |
| 6 | Запустить локально и исправить ошибки | 2 дня | 1-5 |
| 7 | Протестировать pipeline на GitHub | 0.5 дня | 6 |
| **Итого** | | **5 дней** | |

---

## 8. Метрики качества (после внедрения)

| Метрика | Целевое значение |
|---------|------------------|
| PHP-CS-Fixer | 0 ошибок |
| PHPStan level | 5+ (без ошибок) |
| PHPUnit coverage | > 30% (цель: > 60%) |
| Composer audit | 0 критических |
| Build time | < 5 минут |

---

## 9. Риски

| Риск | Вероятность | Влияние | Митигация |
|------|-------------|---------|-----------|
| Много ошибок PHPStan на старте | Высокая | Среднее | Начать с level 3, постепенно повышать |
| Время сборки > 5 мин | Средняя | Низкое | Кеширование Composer, matrix builds |
| CI ломается из-за env зависимостей | Средняя | Среднее | Использовать env vars, моки для БД |
| Файлы логов попадают в lint | Низкая | Низкое | Исключить в .php-cs-fixer.dist.php |

---

## 10. Следующие шаги

1. Создать `.github/workflows/` директорию
2. Создать `cicd_pipeline.yml`
3. Создать `.php-cs-fixer.dist.php`
4. Создать `phpstan.neon`
5. Добавить dev-зависимости в `composer.json`
6. Запустить `composer php-cs-fixer fix` локально
7. Запустить `vendor/bin/phpstan analyse` локально
8. Запустить `vendor/bin/phpunit --coverage-html=coverage` локально
9. Исправить все ошибки
10. Запушить и проверить GitHub Actions

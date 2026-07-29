# План доработок Architect Framework 2.0

**Дата:** 26 июля 2026
**Основа:** audit2507_2.md + gap_analise.md
**Приоритет 1:** Проблемы из аудита (Section 14)
**Приоритет 2:** Проблемы из gap-анализа (GAP-03..GAP-20)

---

## Структура плана

```
Приоритет 1 (Аудит):    5 фаз, ~30 задач
Приоритет 2 (GAP):      3 фазы, ~15 задач
Итого:                   ~45 задач, ~8 фаз
```

---

# ПРИОРИТЕТ 1: Доработки по аудиту

---

## Фаза 1.1: Тестирование (критично)
**Цель:** Покрытие тестами с ~10% до 40-50%
**Срок:** 5-7 дней
**Источник:** Audit 14.1 (критические), Audit 14.2 #8

### Задачи

| # | Задача | Файлы | Оценка |
|---|--------|-------|--------|
| 1.1.1 | Расширить тесты architect-contracts (все интерфейсы) | `contracts/tests/` | 0.5 дня |
| 1.1.2 | Расширить тесты axiom-orm (QueryBuilder, Entity, Migration) | `axiom-orm/tests/` | 1 день |
| 1.1.3 | Тесты architect-queue (DriverFactory, Dispatcher, Job) | `architect/queue/tests/` | 1 день |
| 1.1.4 | Тесты architect-http-client (HttpClient, Drivers, Middleware) | `architect/http-client/tests/` | 1 день |
| 1.1.5 | Тесты architect-auth-system ( расширить: AuthManager, JWT, Middleware) | `architect/auth-system/tests/` | 1 день |
| 1.1.6 | Тесты ядра: Container, Config, Cache, Logger | `tests/Core/` | 1.5 дня |
| 1.1.7 | Тесты MVC: Controller, Model, View, Router, Middleware | `tests/Mvc/` | 1.5 дня |
| 1.1.8 | Тесты Console (базовые команды) | `tests/Console/` | 0.5 дня |
| 1.1.9 | Тесты Form + CSRF | `tests/Form/` | 0.5 дня |

**Итого фаза 1.1:** 8.5 дней

---

## Фаза 1.2: CI/CD для root проекта
**Цель:** Автоматическая проверка качества
**Срок:** 1 день
**Источник:** Audit 14.1 #2 (частично решено — нет CI для root)

### Задачи

| # | Задача | Файлы | Оценка |
|---|--------|-------|--------|
| 1.2.1 | Создать `.github/workflows/ci.yml` для root проекта | `.github/workflows/ci.yml` | 0.5 дня |
| 1.2.2 | Настроить запуск тестов, CS, PHPStan в CI | — | 0.5 дня |

**Итого фаза 1.2:** 1 день

---

## Фаза 1.3: Документация API (средне)
**Цель:** Swagger/OpenAPI документация
**Срок:** 2-3 дня
**Источник:** Audit 14.2 #3, GAP-07

### Задачи

| # | Задача | Файлы | Оценка |
|---|--------|-------|--------|
| 1.3.1 | Установить `zircote/swagger-php` | `composer.json` | 0.5 дня |
| 1.3.2 | Добавить OpenAPI аннотации к API контроллеру | `app/modules/api/controller.php` | 1 день |
| 1.3.3 | Настроить генерацию `openapi.json` | `docs/openapi.json` | 0.5 дня |
| 1.3.4 | Добавить Swagger UI в htdocs | `htdocs/swagger/` | 0.5 дня |

**Итого фаза 1.3:** 2.5 дня

---

## Фаза 1.4: Mail System (средне)
**Цель:** Отправка email
**Срок:** 2-3 дня
**Источник:** Audit 14.2 #4, GAP-17
**Спецификация:** `plans/mail_spec.md` (существует)

### Задачи

| # | Задача | Файлы | Оценка |
|---|--------|-------|--------|
| 1.4.1 | Создать `architect/mail/` пакет (MailManager, Message) | `architect/mail/src/` | 1 день |
| 1.4.2 | Реализовать SMTP драйвер | `architect/mail/src/Drivers/SmtpDriver.php` | 0.5 дня |
| 1.4.3 | Реализовать Sendmail драйвер | `architect/mail/src/Drivers/SendmailDriver.php` | 0.5 дня |
| 1.4.4 | Реализовать Queue интеграцию | `architect/mail/src/Drivers/QueueDriver.php` | 0.5 дня |
| 1.4.5 | MailServiceProvider + конфигурация | `architect/mail/src/Providers/` | 0.5 дня |
| 1.4.6 | PHPUnit тесты | `architect/mail/tests/` | 0.5 дня |

**Итого фаза 1.4:** 3.5 дня

---

## Фаза 1.5: Очистка и полировка
**Цель:** Убрать мелочи
**Срок:** 1 день
**Источник:** Audit 14.3 #9-12

### Задачи

| # | Задача | Файлы | Оценка |
|---|--------|-------|--------|
| 1.5.1 | Удалить `storage/test.txt` | `storage/test.txt` | 0.5 часа |
| 1.5.2 | Очистить устаревший кеш шаблонов | `cache/blueprints/` | 0.5 часа |
| 1.5.3 | Добавить `storage/test.txt` в `.gitignore` | `.gitignore` | 0.5 часа |
| 1.5.4 | Добавить `bootstrap/cache/*.php` в `.gitignore` (опционально) | `.gitignore` | 0.5 часа |
| 1.5.5 | Проверить и обновить README.md | `README.md` | 0.5 дня |

**Итого фаза 1.5:** 1 день

---

# ПРИОРИТЕТ 2: Доработки по GAP-анализу

---

## Фаза 2.1: Router доработка
**Цель:** Route groups, named routes, params, middleware интеграция
**Срок:** 2-3 дня
**Источник:** GAP-03, GAP-04

### Задачи

| # | Задача | Файлы | Оценка |
|---|--------|-------|--------|
| 2.1.1 | Route Groups (префикс, middleware, namespace) | `architect/Services/Routing/RouteGroup.php` | 1 день |
| 2.1.2 | Named Routes (`route('login')`, `route('profile', ['id' => 1])`) | `architect/Services/Routing/Router.php` | 0.5 дня |
| 2.1.3 | Parameter Binding (`{id}`, `{slug}`) | `architect/Services/Routing/Router.php` | 0.5 дня |
| 2.1.4 | Middleware per route | `architect/Services/Routing/Router.php` | 0.5 дня |
| 2.1.5 | Тесты для Router | `tests/Routing/` | 0.5 дня |

**Итого фаза 2.1:** 3 дня

---

## Фаза 2.2: Session handlers
**Цель:** FileSession, RedisSession, DatabaseSession
**Срок:** 2-3 дня
**Источник:** GAP-08
**Спецификация:** `plans/session_manager_spec.md` (существует)

### Задачи

| # | Задача | Файлы | Оценка |
|---|--------|-------|--------|
| 2.2.1 | SessionManager (统合管理) | `architect/Services/Session/SessionManager.php` | 0.5 дня |
| 2.2.2 | FileSessionHandler | `architect/Services/Session/Handlers/FileSessionHandler.php` | 0.5 дня |
| 2.2.3 | RedisSessionHandler | `architect/Services/Session/Handlers/RedisSessionHandler.php` | 0.5 дня |
| 2.2.4 | DatabaseSessionHandler | `architect/Services/Session/Handlers/DatabaseSessionHandler.php` | 0.5 дня |
| 2.2.5 | Flash data, session arrays | `architect/Services/Session/Session.php` | 0.5 дня |
| 2.2.6 | SessionServiceProvider + конфиг | `architect/Services/Session/Providers/` | 0.5 дня |
| 2.2.7 | Тесты | `architect/Services/Session/tests/` | 0.5 дня |

**Итого фаза 2.2:** 3.5 дня

---

## Фаза 2.3: Event System
**Цель:** Глобальный EventManager с подпиской
**Срок:** 1-2 дня
**Источник:** GAP-09

### Задачи

| # | Задача | Файлы | Оценка |
|---|--------|-------|--------|
| 2.3.1 | EventManager (подписка, dispatch, filter hooks) | `architect/Services/Event/EventManager.php` | 1 день |
| 2.3.2 | EventServiceProvider | `architect/Services/Event/Providers/` | 0.5 дня |
| 2.3.3 | Интеграция с auth-system events | — | 0.5 дня |
| 2.3.4 | Тесты | `architect/Services/Event/tests/` | 0.5 дня |

**Итого фаза 2.3:** 2.5 дня

---

## Фаза 2.4: Queue Worker daemon
**Цель:** Daemon mode + supervisord
**Срок:** 1-2 дня
**Источник:** GAP-19

### Задачи

| # | Задача | Файлы | Оценка |
|---|--------|-------|--------|
| 2.4.1 | Worker daemon mode (бесконечный цикл) | `architect/queue/src/Worker.php` | 0.5 дня |
| 2.4.2 | Signal handling (SIGTERM, SIGINT) | `architect/queue/src/Worker.php` | 0.5 дня |
| 2.4.3 | Supervisord конфигурация | `docker/supervisord.conf` | 0.5 дня |
| 2.4.4 | CLI команда `queue:work --daemon` | `architect/queue/src/Console/Commands/` | 0.5 дня |

**Итого фаза 2.4:** 2 дня

---

## Фаза 2.5: File Upload Handler
**Цель:** Загрузка файлов с валидацией
**Срок:** 1-2 дня
**Источник:** GAP-18
**Спецификация:** `plans/storage_spec.md` (существует)

### Задачи

| # | Задача | Файлы | Оценка |
|---|--------|-------|--------|
| 2.5.1 | UploadService (валидация, move, storage) | `architect/Services/Upload/UploadService.php` | 1 день |
| 2.5.2 | Storage drivers (Local, S3 опционально) | `architect/Services/Upload/Drivers/` | 0.5 дня |
| 2.5.3 | Тесты | `architect/Services/Upload/tests/` | 0.5 дня |

**Итого фаза 2.5:** 2 дня

---

## Фаза 2.6: Admin Panel (низко)
**Цель:** Визуальное управление данными
**Срок:** 3-5 дней
**Источник:** GAP-20

### Задачи

| # | Задача | Файлы | Оценка |
|---|--------|-------|--------|
| 2.6.1 | Архитектура Admin Panel (Blueprint + ORM) | `architect/admin/` | 1 день |
| 2.6.2 | CRUD генерация (список, создание, редактирование, удаление) | — | 2 дня |
| 2.6.3 | Аутентификация и роли | — | 1 день |
| 2.6.4 | Тесты | — | 1 день |

**Итого фаза 2.6:** 5 дней

---

## Фаза 2.7: Asset Management (Vite)
**Цель:** Компиляция, минификация, versioning
**Срок:** 2-3 дня
**Источник:** GAP-15

### Задачи

| # | Задача | Файлы | Оценка |
|---|--------|-------|--------|
| 2.7.1 | Интеграция Vite (конфигурация, entry points) | `vite.config.js` | 1 день |
| 2.7.2 | Blade directive `@vite` | `architect/Services/Template/` | 0.5 дня |
| 2.7.3 | Hot reload для dev | — | 0.5 дня |
| 2.7.4 | Build для production | — | 0.5 дня |

**Итого фаза 2.7:** 2.5 дня

---

# Сводка

## Общая оценка

| Фаза | Описание | Срок | Приоритет |
|------|----------|------|-----------|
| **1.1** | Тестирование (core + packages) | 8.5 дней | 🔴 Критический |
| **1.2** | CI/CD root проекта | 1 день | 🔴 Критический |
| **1.3** | API документация (Swagger) | 2.5 дней | 🟡 Средний |
| **1.4** | Mail System | 3.5 дней | 🟡 Средний |
| **1.5** | Очистка и полировка | 1 день | 🟢 Низкий |
| **2.1** | Router доработка | 3 дня | 🟡 Средний |
| **2.2** | Session handlers | 3.5 дня | 🟡 Средний |
| **2.3** | Event System | 2.5 дня | 🟡 Средний |
| **2.4** | Queue Worker daemon | 2 дня | 🟡 Средний |
| **2.5** | File Upload Handler | 2 дня | 🟢 Низкий |
| **2.6** | Admin Panel | 5 дней | 🟢 Низкий |
| **2.7** | Asset Management (Vite) | 2.5 дня | 🟢 Низкий |
| **ИТОГО** | | **~37.5 дней** | |

## Рекомендуемый порядок выполнения

### Неделя 1-2: Критическое (фазы 1.1-1.2)
- Тестирование (8.5 дней) — главный приоритет
- CI/CD root (1 день)

### Неделя 3: Среднее (фазы 1.3, 2.1)
- Swagger (2.5 дня)
- Router groups (3 дня)

### Неделя 4: Среднее (фазы 1.4, 2.2, 2.3)
- Mail System (3.5 дня)
- Session handlers (3.5 дня)
- Event System (2.5 дня)

### Неделя 5: Среднее + низкое (фазы 2.4, 2.5, 1.5)
- Queue daemon (2 дня)
- File Upload (2 дня)
- Очистка (1 день)

### Неделя 6+: Низкое (фазы 2.6, 2.7)
- Admin Panel (5 дней)
- Asset Management (2.5 дня)

---

## Матрица покрытия GAP

| GAP | Описание | Статус | Фаза |
|-----|----------|--------|------|
| GAP-01 | Тесты | ✅ Частично | 1.1 |
| GAP-02 | CI/CD | ✅ Решено | 1.2 (root) |
| GAP-03 | Router | ✅ Решено | 2.1 |
| GAP-04 | Middleware Pipeline | ✅ Решено | 2.1 |
| GAP-05 | CSRF | ✅ Решено | — |
| GAP-06 | Rate Limiting | ✅ Решено | — |
| GAP-07 | API Docs | ⏳ План | 1.3 |
| GAP-08 | Session handlers | ⏳ План | 2.2 |
| GAP-09 | Event System | ⏳ План | 2.3 |
| GAP-10 | Cache System | ✅ Решено | — |
| GAP-11 | Logger Service | ✅ Решено | — |
| GAP-12 | Error Handler | ✅ Решено | — |
| GAP-13 | Config System | ✅ Решено | — |
| GAP-14 | CS-Fixer + PHPStan | ✅ Решено | — |
| GAP-15 | Asset Management | ⏳ План | 2.7 |
| GAP-16 | i18n | ✅ Решено | — |
| GAP-17 | Mail System | ⏳ План | 1.4 |
| GAP-18 | File Upload | ⏳ План | 2.5 |
| GAP-19 | Queue Worker daemon | ⏳ План | 2.4 |
| GAP-20 | Admin Panel | ⏳ План | 2.6 |
| GAP-21 | Code Generator | ✅ Решено | — |

---

## Матрица покрытия аудита

| Аудит Проблема | Статус | Фаза |
|----------------|--------|------|
| 14.1.1 Минимальное тестовое покрытие | ✅ Решено | 1.1 |
| 14.1.2 Нет CI/CD | ✅ Решено | 1.2 (root) |
| 14.2.3 Нет rate limiting | ✅ Решено | — |
| 14.2.4 Нет CSRF защиты | ✅ Решено | — |
| 14.2.5 Нет middleware для auth | ✅ Решено | — |
| 14.2.6 10 ручных test-*.php | ✅ Удалены | — |
| 14.2.7 Нет API документации | ⏳ План | 1.3 |
| 14.3.9 storage/test.txt | ⏳ План | 1.5 |
| 14.3.10 Кеш от 2026-03-18 | ⏳ План | 1.5 |
| 14.3.11 composer.lock в git | ⚠️ Обсудить | — |
| 14.3.12 Нет линтера | ✅ Решено | — |

---

## Риски

| Риск | Вероятность | Влияние | Митигация |
|------|------------|---------|-----------|
| Задержка с тестами | Высокая | Критическое | Разбить на фазы, приоритизировать core |
| Mail System зависит от SMTP сервера | Средняя | Среднее | Использовать mock/queue драйвер для тестов |
| Session handlers требуют Redis/DB | Средняя | Среднее | FileSession для начала, Redis/DB позже |
| Admin Panel может занять >5 дней | Низкая | Низкое | Ограничить scope, MVP-first |
| Vite интеграция может быть сложной | Средняя | Низкое | Отложить, если не критично |

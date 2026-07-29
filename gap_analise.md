
# Анализ недостающих компонентов и план доработок
**Дата анализа:** 25 июля 2026
**Обновлено:** 26 июля 2026
**Проект:** Architect Framework 2.0

---

## 1. Методология

Анализ проведён на основе полного аудита системы (audit2507_2.md). Каждый компонент оценен по зрелости:

| Уровень | Описание |
|---------|----------|
| 🟢 Готово | Компонент реализован, протестирован, документирован |
| 🟡 Частично | Компонент есть, но неполный или недотестирован |
| 🔴 Отсутствует | Компонент не реализован |

---

## 2. Матрица зрелости компонентов

### 2.1 Ядро фреймворка (architect/Core + architect/Services)

| Компонент | Код | Тесты | Документация | Статус |
|-----------|-----|-------|--------------|--------|
| Container (DI) | 🟢 | 🟡 (9 assertions в contracts) | 🟢 (docs + techdocs) | 🟢 |
| Framework | 🟢 | 🔴 | 🟢 | 🟡 |
| Statement | 🟢 | 🔴 | 🔴 | 🟡 |
| Environment | 🟢 | 🔴 | 🟢 | 🟡 |
| Bundle System | 🟢 | 🟡 | 🟢 (docs/bundles.md) | 🟢 |
| Config System | 🟢 | 🔴 | 🟢 (docs + techdocs) | 🟡 |
| Router | 🟢 | 🔴 | 🟢 (docs/routing.md) | 🟡 |
| Middleware Pipeline | 🟢 | 🔴 | 🟡 | 🟡 |
| HTTP Request/Response | 🟢 | 🔴 | 🟢 | 🟡 |
| Session | 🟡 (NativeSession) | 🔴 | 🔴 | 🟡 |
| Console | 🟢 (20+ commands) | 🔴 | 🟢 (docs/console.md) | 🟡 |
| MVC (Controller/Model/View) | 🟢 | 🔴 | 🟢 (docs/controllers.md, models.md, views.md) | 🟡 |
| Template Engine | 🟢 (Blueprint + PHP renderers) | 🔴 | 🟢 | 🟡 |

### 2.2 Сервисы (architect/Services/)

| Сервис | Код | Тесты | Документация | Статус |
|--------|-----|-------|--------------|--------|
| Cache (file, array, redis) | 🟢 | 🔴 | 🟢 (docs/caching.md) | 🟡 |
| Logger (5 каналов) | 🟢 | 🔴 | 🟢 (docs/logging.md) | 🟡 |
| Errors | 🟢 | 🔴 | 🟢 (docs/errors.md) | 🟡 |
| Forms + CSRF | 🟢 | 🔴 | 🟢 (docs/forms.md) | 🟡 |
| Performance Monitor | 🟢 | 🔴 | 🟢 (docs/performance.md) | 🟡 |
| Debug Toolbar (11 tabs) | 🟢 | 🔴 | 🟢 (docs/debugging.md) | 🟡 |
| I18n (5 языков) | 🟢 | 🔴 | 🟢 (docs/i18n.md) | 🟡 |
| Routing (JSON loader) | 🟢 | 🔴 | 🟢 (docs/routing.md) | 🟡 |
| Database | 🟢 | 🔴 | 🟢 (docs/database.md) | 🟡 |
| App Management | 🟢 | 🔴 | 🟡 | 🟡 |

### 2.3 Бандлы (architect/)

| Бандл | Код | Тесты | Документация | Статус |
|-------|-----|-------|--------------|--------|
| Auth (RBAC + OAuth2 + JWT) | 🟢 | 🟡 (2 tests) | 🟢 (docs/auth.md) | 🟢 |
| Blueprint Templates | 🟢 | 🟡 (config tests) | 🟢 | 🟢 |
| Blueprint Forms | 🟢 | 🔴 | 🟢 | 🟡 |
| Blueprint Helpers | 🟢 | 🔴 | 🟢 (docs/helpers.md) | 🟡 |
| HTTP Client | 🟢 | 🟡 (config tests) | 🟢 (docs/http-client.md) | 🟡 |
| Queue (7 drivers) | 🟢 | 🟡 (config tests) | 🟡 | 🟡 |
| Validation (15 rules) | 🟢 | 🔴 | 🟡 | 🟡 |

### 2.4 Axiom ORM

| Пакет | Код | Тесты | Документация | Статус |
|-------|-----|-------|--------------|--------|
| axiom/orm | 🟢 | 🟡 (19 assertions) | 🟢 | 🟢 |
| axiom/entity | 🟢 | 🟡 (включено в orm) | 🟢 | 🟡 |
| axiom/migration | 🟢 | 🔴 | 🟡 (docs/migrations.md) | 🟡 |

### 2.5 Инфраструктура

| Компонент | Код | Тесты | Документация | Статус |
|-----------|-----|-------|--------------|--------|
| Docker (4 services) | 🟢 | 🔴 | 🟡 (docs/installation.md) | 🟢 |
| CI/CD (7 repos) | 🟢 | — | — | 🟢 |
| PHP-CS-Fixer | 🟢 | — | — | 🟢 |
| PHPStan | 🟢 | — | — | 🟢 |
| PHPUnit | 🟢 | 🟢 | — | 🟢 |

---

## 3. Детальный анализ пробелов

### 3.1 ✅ УСТРАНЕНО

#### GAP-01: Нет базовых PHPUnit тестов для ядра
**Статус:** ✅ Частично решено
**Текущее состояние:** 30+ assertions в architect-contracts (9), axiom-orm (19), auth-system (2 теста)
**Осталось:** Нет тестов для ядра (MVC, Cache, Logger, Router, Console, Form, Errors)

#### GAP-02: Нет CI/CD
**Статус:** ✅ Решено
**Текущее состояние:** GitHub Actions для 7 репозиториев (тесты, CS, PHPStan)
**Осталось:** CI для root проекта не настроен

#### GAP-05: Нет CSRF защиты
**Статус:** ✅ Решено
**Текущее состояние:** `CSRFTokenManager` + `CsrfAdapter` middleware + `EscaperTrait`

#### GAP-06: Нет rate limiting
**Статус:** ✅ Решено
**Текущее состояние:** `RateLimitMiddleware`

#### GAP-10: Нет Cache System
**Статус:** ✅ Решено
**Текущее состояние:** `CacheManager`, `CacheOrchestrator`, 3 драйвера (Array, File, Redis)

#### GAP-11: Нет Logging System
**Статус:** ✅ Решено
**Текущее состояние:** `Logger`, `ChannelLogger`, `FileLogWriter`, 5 каналов

#### GAP-12: Нет Error/Exception Handler
**Статус:** ✅ Решено
**Текущее состояние:** `Errors`, `ErrorView`, `ExceptionView`, `FullErrorView`, `NotFoundView`

#### GAP-13: Нет Config System
**Статус:** ✅ Решено
**Текущее состояние:** `ConfigRepository`, `ConfigLoader`, `ConfigCache`, `ConfigPathResolver`

#### GAP-14: Нет PHP-CS-Fixer / PHPStan
**Статус:** ✅ Решено
**Текущее состояние:** `.php-cs-fixer.dist.php` + `phpstan.neon` во всех репозиториях

#### GAP-16: Нет国际化 (i18n)
**Статус:** ✅ Решено
**Текущее состояние:** `Language`, `LanguageDetector`, `FileTranslationLoader`, 5 языков

#### GAP-21: Нет Scaffold/Code Generator
**Статус:** ✅ Решено
**Текущее состояние:** CLI команды: make:app, make:controller, make:model, make:migration, make:module, make:route, make:view

---

### 3.2 ⚠️ ЧАСТИЧНО РЕШЕНО

#### GAP-03: Router
**Статус:** ⚠️ Частично решено
**Текущее состояние:** `Router`, `JsonRouteLoader`, `ModuleResolver`, `RouteCache`, `HttpRequest`
**Не хватает:** Route groups, middleware в маршрутах, named routes, parameters binding

#### GAP-04: Middleware Pipeline
**Статус:** ⚠️ Частично решено
**Текущее состояние:** `MiddlewareStack`, `MiddlewareDispatcher`, `MiddlewareResolver`, `AuthAdapter`, `CsrfAdapter`, `RateLimitMiddleware`
**Не хватает:** Интеграция middleware в маршруты, глобальные middleware

#### GAP-08: Session handlers
**Статус:** ⚠️ Частично решено
**Текущее состояние:** `NativeSession` (Form), `SessionStorage` (Auth), `SessionStorage` (Performance)
**Не хватает:** Полноценные драйверы: FileSession, RedisSession, DatabaseSession

#### GAP-09: Event System
**Статус:** ⚠️ Частично решено
**Текущее состояние:** `AuthEventDispatcher` в auth-system, `SimpleEventDispatcher` в queue
**Не хватает:** Глобальный EventManager с подпиской

#### GAP-19: Queue Worker daemon mode
**Статус:** ⚠️ Частично решено
**Текущее состояние:** `Worker` класс, CLI команда `queue:work`
**Не хватает:** Daemon mode, supervisord/systemd конфигурация

---

### 3.3 🔴 НЕ РЕШЕНО

#### GAP-07: Нет API документации (Swagger/OpenAPI)
**Приоритет:** Средний
**Влияние:** Сложность интеграции с фронтендом
**Решение:** Интеграция с swagger-php, аннотации

#### GAP-17: Нет Mail System
**Приоритет:** Средний
**Влияние:** Нет отправки email (регистрация, уведомления)
**Решение:** MailManager с SMTP, Sendmail, Queue драйверами

#### GAP-18: Нет File Upload Handler
**Приоритет:** Низкий
**Влияние:** Нет загрузки файлов
**Решение:** UploadService с валидацией, storage drivers (local, S3)

#### GAP-20: Нет ORM Query Builder UI / Admin Panel
**Приоритет:** Низкий
**Влияние:** Нет визуального управления данными
**Решение:** Админ-панель (Blueprint + ORM)

---

## 4. План доработок

### Фаза 1: Тестирование (1 неделя)
**Цель:** Довести тестовое покрытие до 40-50%

| # | Задача | GAP | Приоритет | Оценка |
|---|--------|-----|-----------|--------|
| 1.1 | PHPUnit тесты для architect-contracts (расширить) | GAP-01 | Критический | 1 день |
| 1.2 | PHPUnit тесты для axiom-orm (расширить) | GAP-01 | Критический | 1 день |
| 1.3 | PHPUnit тесты для architect-queue | GAP-01 | Высокий | 1 день |
| 1.4 | PHPUnit тесты для architect-http-client | GAP-01 | Высокий | 1 день |
| 1.5 | PHPUnit тесты для architect-blueprint-auth | GAP-01 | Средний | 0.5 дня |
| 1.6 | Тесты для ядра (Container, Config, Cache, Logger) | GAP-01 | Критический | 2 дня |
| 1.7 | Тесты для MVC (Controller, Model, View, Router) | GAP-01 | Критический | 2 дня |
| 1.8 | Тесты для Console (базовые команды) | GAP-01 | Средний | 1 день |
| **Итого Фаза 1** | | | | **9.5 дней** |

### Фаза 2: Доработка компонентов (1 неделя)
**Цель:** Довести Router, Middleware, Session до production-ready

| # | Задача | GAP | Приоритет | Оценка |
|---|--------|-----|-----------|--------|
| 2.1 | Доработать Router: route groups, named routes, params | GAP-03 | Высокий | 2 дня |
| 2.2 | Middleware: интеграция в маршруты, глобальные middleware | GAP-04 | Высокий | 2 дня |
| 2.3 | Session: FileSession, RedisSession, DatabaseSession | GAP-08 | Высокий | 2 дня |
| 2.4 | Event System: глобальный EventManager | GAP-09 | Средний | 1 день |
| 2.5 | Queue Worker: daemon mode | GAP-19 | Средний | 1 день |
| **Итого Фаза 2** | | | | **8 дней** |

### Фаза 3: Расширенные возможности (1 неделя)
**Цель:** Добавить недостающие компоненты

| # | Задача | GAP | Приоритет | Оценка |
|---|--------|-----|-----------|--------|
| 3.1 | Mail System (SMTP, Queue) | GAP-17 | Средний | 2 дня |
| 3.2 | File Upload Handler | GAP-18 | Низкий | 1 день |
| 3.3 | API документация (Swagger/OpenAPI) | GAP-07 | Средний | 2 дня |
| 3.4 | CI для root проекта | — | Средний | 0.5 дня |
| **Итого Фаза 3** | | | | **5.5 дней** |

### Фаза 4: Полировка (3-5 дней)
**Цель:** Финальная шлифовка

| # | Задача | GAP | Приоритет | Оценка |
|---|--------|-----|-----------|--------|
| 4.1 | Asset Management (Vite) | — | Низкий | 2 дня |
| 4.2 | Админ-панель | GAP-20 | Низкий | 3 дня |
| 4.3 | Финальная документация (API + Guides) | — | Средний | 1 день |
| 4.4 | Очистить мусор (storage/test.txt) | — | Низкий | 0.5 дня |
| **Итого Фаза 4** | | | | **6.5 дней** |

---

## 5. Сводка по приоритетам

### ✅ Устранено (11 GAPs)
1. **GAP-01** — PHPUnit тесты (частично: 30+ assertions)
2. **GAP-02** — CI/CD (7 репозиториев с GitHub Actions)
3. **GAP-05** — CSRF Protection (CSRFTokenManager + CsrfAdapter)
4. **GAP-06** — Rate Limiting (RateLimitMiddleware)
5. **GAP-10** — Cache System (3 драйвера)
6. **GAP-11** — Logger Service (5 каналов)
7. **GAP-12** — Error Handler (Errors + views)
8. **GAP-13** — Config System (ConfigRepository + cache)
9. **GAP-14** — PHP-CS-Fixer + PHPStan
10. **GAP-16** — i18n (5 языков)
11. **GAP-21** — Code Generator (make:* команды)

### ⚠️ Частично решено (5 GAPs)
12. **GAP-03** — Router (есть, но нет groups/named routes)
13. **GAP-04** — Middleware Pipeline (есть, но нет route integration)
14. **GAP-08** — Session handlers (NativeSession, но нет драйверов)
15. **GAP-09** — Event System (локальный, нет глобального)
16. **GAP-19** — Queue Worker (есть, но нет daemon mode)

### 🔴 Не решено (4 GAPs)
17. **GAP-07** — API Documentation (Swagger/OpenAPI)
18. **GAP-17** — Mail System
19. **GAP-18** — File Upload Handler
20. **GAP-20** — Admin Panel

---

## 6. Итоговая оценка

| Метрика | Было (25.07) | Стало (26.07) |
|---------|-------------|---------------|
| Всего пробелов | 21 | 20 |
| Устранено | 0 | 11 |
| Частично решено | 0 | 5 |
| Не решено | 21 | 4 |
| Общая оценка зрелости | **40%** | **~65%** |
| Ожидаемый срок доработки | **~6-7 недель** | **~3-4 недели** |

**Рекомендация:** Сфокусироваться на Фазе 1 (тесты) — это критический пробел. Фаза 2 (доработка компонентов) — после стабилизации тестов. Фазы 3-4 — по мере необходимости.

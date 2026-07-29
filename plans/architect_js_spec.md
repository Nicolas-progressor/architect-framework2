# architect.js — Lightweight SPA Framework

**Версия:** 1.0.0 (план)
**Дата:** 29 июля 2026
**Статус:** Спецификация

---

## 1. Философия

### 1.1 Зачем

PHP-фреймворк Architect предоставляет единую архитектуру для серверной части:
Container, Router, EventManager, HttpClient, Template Engine (Blueprint).

architect.js переносит ту же философию на клиентскую сторону:
- Те же концепции (DI, события, маршрутизация)
- Те же названия классов и методов
- Та же модульная структура
- 0 зависимостей — vanilla JS, ~600 строк

### 1.2 Принципы

| Принцип | Описание |
|---------|----------|
| **Zero deps** | Никаких npm/node_modules. Чистый ES6+ |
| **Mirror API** | Методы называются как в PHP-аналогах |
| **Progressive** | Работает как SPA, но не ломает обычную навигацию |
| **Hosting-agnostic** | Не требует Node.js на продакшене |

### 1.3 Соответствие PHP-архитектуре

| PHP (Architect) | JS (architect.js) | Назначение |
|-----------------|-------------------|------------|
| `Container` | `Architect.container` | DI-контейнер, регистрация сервисов |
| `Router` + `RouteGroup` | `Architect.router` | History API маршрутизация |
| `EventManager` | `Architect.event` | События + фильтры (hooks) |
| `HttpClient` | `Architect.http` | fetch-клиент с middleware |
| `Template (Blueprint)` | `Architect.component` | Веб-компоненты |
| `ServiceProvider` | `Architect.provider` | Провайдеры инициализации |
| `Config` | `Architect.config` | Конфигурация приложения |

---

## 2. Модули

### 2.1 Core (`core.js`)

**DI-контейнер.** Полная копия PHP ContainerInterface.

```js
// Регистрация
Architect.container.set('user.service', new UserService());
Architect.container.singleton('api.client', ApiClient);

// Получение
const users = Architect.container.get('user.service');
const api = Architect.container.get('api.client');

// Проверка
if (Architect.container.has('auth')) { /* ... */ }
```

**API:**

| Метод | Сигнатура | Описание |
|-------|-----------|----------|
| `set` | `(id, instance)` | Регистрация готового экземпляра |
| `singleton` | `(id, factory)` | Регистрация фабрики (lazy, 1 экз.) |
| `factory` | `(id, factory)` | Регистрация фабрики (каждый раз новый) |
| `get` | `(id) => any` | Получение сервиса |
| `has` | `(id) => boolean` | Проверка регистрации |
| `remove` | `(id)` | Удаление сервиса |

---

### 2.2 Router (`router.js`)

**Клиентская маршрутизация.** Использует History API. Поддерживает вложенные группы, middleware, параметры URL.

```js
// Определение маршрутов
Architect.router.route('/', 'home');
Architect.router.route('/users', 'users.index');
Architect.router.route('/users/{id}', 'users.show');
Architect.router.route('/users/{id}/edit', 'users.edit');

// Группы с middleware
Architect.router.group('/admin', { middleware: ['auth'] }, () => {
    Architect.router.route('/', 'admin.dashboard');
    Architect.router.route('/users', 'admin.users');
});

// Named routes
const url = Architect.router.route('users.show', { id: 5 });
// => '/users/5'

// Lazy loading компонентов
Architect.router.route('/settings', {
    component: () => import('/assets/architect/pages/settings.js')
});
```

**API:**

| Метод | Сигнатура | Описание |
|-------|-----------|----------|
| `route` | `(path, name\|options, ?options)` | Определение маршрута |
| `group` | `(prefix, options, callback)` | Группа маршрутов |
| `navigate` | `(url)` | Переход по URL |
| `resolve` | `(name, params?) => string` | Генерация URL по имени |
| `current` | `() => Route` | Текущий маршрут |
| `beforeEach` | `(guard)` | Глобальный guard (middleware) |
| `afterEach` | `(hook)` | Хук после перехода |

**Middleware guard:**
```js
Architect.router.beforeEach((to, from) => {
    if (to.requiresAuth && !Architect.container.get('auth').isLoggedIn()) {
        return '/login'; // redirect
    }
});
```

**События:**
```js
Architect.event.on('router.navigate', (data) => { /* ... */ });
Architect.event.on('router.error', (err) => { /* ... */ });
```

---

### 2.3 Event Manager (`state.js`)

**События + реактивное состояние.** Копия PHP EventManager с добавлением реактивности.

```js
// Подписка на события
Architect.event.on('user.loggedIn', (payload) => {
    console.log('User logged in:', payload);
});

// Генерация события
Architect.event.emit('user.loggedIn', { id: 1, name: 'Иван' });

// Wildcards
Architect.event.on('user.*', (payload) => { /* все user.* события */ });

// Разовая подписка
Architect.event.once('app.ready', () => { /* выполнится 1 раз */ });
```

**Реактивное состояние (Store):**
```js
// Определение сторов
Architect.store('auth', {
    user: null,
    token: null,
});

// Использование
Architect.store('auth').user = { id: 1, name: 'Иван' };
// Автоматически уведомляет подписчиков

// Подписка на изменения
Architect.store.on('auth.user', (newVal, oldVal) => {
    console.log('User changed:', newVal);
});

// Компонент подписывается автоматически:
Architect.component('user-profile', {
    store: ['auth.user'],
    render() {
        return `<h1>${this.store.auth.user.name}</h1>`;
    }
});
```

**Фильтры (hooks):**
```js
Architect.event.filter('format.date', (value) => {
    return new Date(value).toLocaleDateString('ru-RU');
});

// В шаблоне:
// {{ '2026-07-29' | date }} → "29.07.2026"
```

**API (Event):**

| Метод | Сигнатура | Описание |
|-------|-----------|----------|
| `on` | `(event, callback, priority?)` | Подписка |
| `once` | `(event, callback)` | Разовая подписка |
| `off` | `(event, callback?)` | Отписка |
| `emit` | `(event, payload?)` | Генерация события |
| `filter` | `(hook, value, ...args)` | Применение фильтров |
| `addFilter` | `(hook, callback, priority?)` | Регистрация фильтра |

**API (Store):**

| Метод | Сигнатура | Описание |
|-------|-----------|----------|
| `Architect.store` | `(name, initial?) => Store` | Получить/создать стор |
| `store.set` | `(key, value)` | Установить значение |
| `store.get` | `(key) => any` | Получить значение |
| `store.on` | `(key, callback)` | Подписка на изменение ключа |
| `store.off` | `(key, callback?)` | Отписка |

---

### 2.4 HTTP Client (`http.js`)

**fetch-клиент.** Копия architect-http-client с middleware pipeline.

```js
// GET
const users = await Architect.http.get('/api/users');

// POST с телом
const user = await Architect.http.post('/api/users', {
    name: 'Иван',
    email: 'ivan@example.com',
});

// С параметрами
Architect.http.get('/api/users', {
    params: { page: 2, limit: 20 },
    headers: { 'X-CSRF': csrfToken },
});

// Middleware
Architect.http.use(async (request, next) => {
    request.headers['Authorization'] = 'Bearer ' + token;
    const response = await next(request);
    if (response.status === 401) {
        Architect.event.emit('auth.unauthorized');
    }
    return response;
});

// Response middleware (трансформация)
Architect.http.useResponse(async (response) => {
    if (response.headers.get('content-type')?.includes('json')) {
        return response.json();
    }
    return response.text();
});
```

**API:**

| Метод | Сигнатура | Описание |
|-------|-----------|----------|
| `get` | `(url, options?)` | GET-запрос |
| `post` | `(url, body?, options?)` | POST-запрос |
| `put` | `(url, body?, options?)` | PUT-запрос |
| `patch` | `(url, body?, options?)` | PATCH-запрос |
| `delete` | `(url, options?)` | DELETE-запрос |
| `use` | `(middleware)` | Добавление request middleware |
| `useResponse` | `(middleware)` | Добавление response middleware |

---

### 2.5 Component Engine (`component.js`)

**Веб-компоненты.** Использует Custom Elements v1. Поддерживает шаблонизацию, реактивность, жизненный цикл.

```js
// Регистрация компонента
Architect.component('user-card', {
    // Локальное состояние
    data() {
        return { expanded: false };
    },

    // Реактивные подписки
    store: ['auth.user'],

    // Шаблон (HTML или функция)
    template: `
        <div class="card">
            <h3>{{ user.name }}</h3>
            <p>{{ user.email }}</p>
            <button @click="toggle">Подробнее</button>
            <div x-show="expanded">
                <p>Телефон: {{ user.phone }}</p>
            </div>
        </div>
    `,

    // Жизненный цикл
    mounted() { console.log('Component added to DOM'); },
    updated() { console.log('Component re-rendered'); },
    destroyed() { console.log('Component removed'); },

    // Методы
    toggle() {
        this.data.expanded = !this.data.expanded;
    }
});
```

**Использование в HTML:**
```html
<user-card></user-card>
<user-card user-id="5"></user-card>
```

**Атрибуты и свойства:**
```js
Architect.component('product-list', {
    props: {
        category: { type: String, default: 'all' },
        limit: { type: Number, default: 10 },
    },

    template: `<div class="products">...</div>`,

    // Хук при изменении props
    watch: {
        category(newVal) {
            this.loadProducts();
        }
    }
});
```

**Жизненный цикл:**

| Хук | Когда |
|-----|-------|
| `beforeCreate` | До инициализации компонента |
| `created` | После инициализации, до рендера |
| `mounted` | Компонент добавлен в DOM |
| `updated` | Компонент перерисован |
| `destroyed` | Компонент удалён из DOM |

**Шаблонизация:**

| Синтаксис | Описание |
|-----------|----------|
| `{{ variable }}` | Вывод значения (escape) |
| `{{{ html }}}` | Вывод без escape |
| `@click="method"` | Обработчик события |
| `x-show="expr"` | Условный рендер |
| `x-if="expr"` | Условное монтирование |
| `x-for="item of items"` | Цикл |
| `:class="expr"` | Динамический класс |
| `:style="expr"` | Динамические стили |
| `ref="name"` | Ссылка на элемент |

---

### 2.6 Application (`app.js`)

**Точка входа.** Инициализация и координация модулей.

```js
// app.js — единая точка входа

Architect.boot({
    // Регистрация провайдеров
    providers: [
        AuthProvider,
        ApiProvider,
        RouterProvider,
    ],

    // Конфигурация
    config: {
        router: {
            mode: 'history', // или 'hash'
            base: '/',
        },
        http: {
            baseUrl: '/api',
            csrf: true,
        },
        component: {
            prefix: 'a-', // префикс для Custom Elements
        },
    },

    // Корневой компонент
    root: '#app',
});
```

**Провайдеры:**
```js
// Провайдер — аналог ServiceProvider в PHP
const AuthProvider = {
    register() {
        Architect.container.singleton('auth', () => new AuthService());
    },
    boot() {
        const auth = Architect.container.get('auth');
        auth.restore(); // восстановить сессию из localStorage
    }
};
```

---

## 3. Интеграция с PHP Architect

### 3.1 Подключение

```php
// В шаблоне (Blueprint):
{{ Helper_Assets::js('/assets/architect/core.js') }}
{{ Helper_Assets::js('/assets/architect/router.js') }}
{{ Helper_Assets::js('/assets/architect/state.js') }}
{{ Helper_Assets::js('/assets/architect/http.js') }}
{{ Helper_Assets::js('/assets/architect/component.js') }}
{{ Helper_Assets::js('/assets/architect/app.js') }}
```

Или минифицированный бандл:
```php
{{ Helper_Assets::js('/assets/architect/architect.min.js') }}
```

### 3.2 Передача данных с сервера

```php
// PHP контроллер передаёт данные в JS:
$this->ext['js_config'] = json_encode([
    'user'    => Auth::user(),
    'csrf'    => Csrf::token(),
    'routes'  => Router::getNamedRoutes(),
    'locale'  => 'ru',
]);
```

```js
// JS получает:
Architect.boot({
    config: window.ARCHITECT_CONFIG,
});
```

### 3.3 API эндпоинты

architect.js ожидает от PHP стандартный JSON API:

```
GET    /api/users        → список
POST   /api/users        → создать
GET    /api/users/{id}   → один
PUT    /api/users/{id}   → обновить
DELETE /api/users/{id}   → удалить
```

Формат ответа:
```json
{
    "data": { ... },
    "meta": {
        "page": 1,
        "total": 50
    },
    "errors": null
}
```

### 3.4 Режимы работы

**Режим 1 — SPA:**
- Одна HTML-страница (layout), всё через History API
- architect.js полностью управляет навигацией
- PHP только API

**Режим 2 — Progressive (гибрид):**
- PHP рендерит первую страницу как обычно
- architect.js перехватывает клики по ссылкам, подгружает контент через fetch
- Если JS отключён — работает обычная навигация

**Режим 3 — Микро-фронтенды:**
- Часть страницы — SPA-виджет (например, админка)
- Остальное — обычные PHP-страницы

---

## 4. Структура файлов

```
htdocs/assets/architect/
├── core.js              # DI-контейнер
├── router.js            # History API маршрутизация
├── state.js             # События + Store
├── http.js              # fetch-клиент
├── component.js         # Веб-компоненты
├── app.js               # Точка входа + boot
├── architect.min.js     # Минифицированный бандл
├── pages/               # Lazy-loaded страницы
│   ├── home.js
│   ├── users.js
│   └── settings.js
└── components/          # Переиспользуемые компоненты
    ├── user-card.js
    └── pagination.js
```

---

## 5. Размер и метрики

| Модуль | Строк (оценка) |
|--------|---------------|
| core.js | ~80 |
| router.js | ~150 |
| state.js | ~120 |
| http.js | ~100 |
| component.js | ~200 |
| app.js | ~30 |
| **Итого** | **~680** |
| Минифицированный | ~8 KB gzip |

---

## 6. Фазы реализации

| Фаза | Модуль | Оценка |
|------|--------|--------|
| 1 | core.js (DI-контейнер) | 0.5 дня |
| 2 | state.js (Event + Store) | 0.5 дня |
| 3 | http.js (fetch-клиент) | 0.5 дня |
| 4 | router.js (History API) | 1 день |
| 5 | component.js (веб-компоненты) | 1.5 дня |
| 6 | app.js + интеграция с PHP | 0.5 дня |
| 7 | Тесты (JS unit) | 1 день |
| 8 | Документация + примеры | 0.5 дня |
| **Итого** | | **6 дней** |

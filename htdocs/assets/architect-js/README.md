# architect.js

**Vanilla JS SPA framework** — 0 dependencies, ~600 lines, ES5-compatible.

## Philosophy

Mirrors the PHP Architect framework on the client side:

| PHP Architect | architect.js |
|--------------|--------------|
| `Container` | `Architect.Container` |
| `EventManager` | `Architect.State.EventManager` |
| `Router` + `RouteGroup` | `Architect.Router` |
| `HttpClient` | `Architect.Http` |
| Template Engine | `Architect.Component` (Web Components) |

## Installation

### Option 1: Standalone (any project)

```html
<script src="/assets/architect-js/src/core.js"></script>
<script src="/assets/architect-js/src/state.js"></script>
<script src="/assets/architect-js/src/http.js"></script>
<script src="/assets/architect-js/src/router.js"></script>
<script src="/assets/architect-js/src/component.js"></script>
<script src="/assets/architect-js/src/app.js"></script>
<script>
    var app = new Architect.App();
    app.boot({
        config: {
            router: { mode: 'history' },
            apiBaseUrl: '/api',
        },
        providers: [ /* ... */ ],
    });
</script>
```

### Option 2: PHP Architect integration

Install the optional service provider:

```bash
composer require architect/architect-js
```

Then in `config/app.php`:
```php
'providers' => [
    Architect\Services\ArchitectJs\Providers\ArchitectJsServiceProvider::class,
],
```

## Modules

### Container (`core.js`)

```js
var container = new Architect.Container();
container.set('api', apiService);
container.singleton('db', function (c) { return new Database(); });
container.get('api'); // => apiService
```

### Event Bus + Store (`state.js`)

```js
var events = new Architect.State.EventManager();
events.on('user.login', function (payload) { /* ... */ });
events.emit('user.login', { id: 1 });

var store = Architect.State.createStore('auth', { user: null });
store.set('user', { name: 'John' });
store.get('user'); // => { name: 'John' }
store.on('user', function (newVal, oldVal) { /* ... */ });
```

### Router (`router.js`)

```js
var router = new Architect.Router();
router.route('/', 'home');
router.route('/users/{id}', 'users.show');
router.group('/admin', { middleware: ['auth'] }, function (r) {
    r.route('/', 'admin.dashboard');
});
router.beforeEach(function (to, from) { /* guard */ });
router.start();
```

### HTTP Client (`http.js`)

```js
var http = new Architect.Http();
http.configure({ baseUrl: '/api' });
http.use(function (req, next) { /* request middleware */ });
http.get('/users', { params: { page: 1 } });
http.post('/users', { name: 'John' });
```

### Web Components (`component.js`)

```js
Architect.Component.register('user-card', {
    data: function () { return { expanded: false }; },
    template: '<div><h3>{{ data.name }}</h3><button @click="toggle">Toggle</button></div>',
    toggle: function () { this._data.expanded = !this._data.expanded; this._render(); }
});
```
```html
<user-card></user-card>
```

## Browser Support

IE 10+ (ES5). All modern browsers. No polyfills required for basic functionality.

## Build

No build step needed. Just serve the `src/` files.

For production, concatenate and minify:

```bash
cat src/core.js src/state.js src/http.js src/router.js src/component.js src/app.js > dist/architect.js
# then minify with your tool of choice
```

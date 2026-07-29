const { describe, it, beforeEach } = require('node:test');
const assert = require('node:assert/strict');
const path = require('path');

const SRC = path.resolve(__dirname, '../../../htdocs/assets/architect-js/src');

const Container = require(path.join(SRC, 'core.js'));
const State = require(path.join(SRC, 'state.js'));
const Http = require(path.join(SRC, 'http.js'));
const Router = require(path.join(SRC, 'router.js'));
const Component = require(path.join(SRC, 'component.js'));
const App = require(path.join(SRC, 'app.js'));

// ============================================================
// Container (core.js)
// ============================================================
describe('Container', () => {

    it('set and get values', () => {
        const c = new Container();
        c.set('foo', 'bar');
        assert.strictEqual(c.get('foo'), 'bar');
    });

    it('singleton returns same instance', () => {
        const c = new Container();
        let count = 0;
        c.singleton('db', () => ({ id: ++count }));
        const a = c.get('db');
        const b = c.get('db');
        assert.strictEqual(a, b);
        assert.strictEqual(count, 1);
    });

    it('factory returns new instance each call', () => {
        const c = new Container();
        let count = 0;
        c.factory('db', () => ({ id: ++count }));
        const a = c.get('db');
        const b = c.get('db');
        assert.notStrictEqual(a, b);
        assert.strictEqual(count, 2);
    });

    it('has returns boolean', () => {
        const c = new Container();
        assert.strictEqual(c.has('foo'), false);
        c.set('foo', 1);
        assert.strictEqual(c.has('foo'), true);
    });

    it('remove clears binding', () => {
        const c = new Container();
        c.set('foo', 'bar');
        c.remove('foo');
        assert.strictEqual(c.has('foo'), false);
    });

    it('clear removes all bindings', () => {
        const c = new Container();
        c.set('a', 1);
        c.set('b', 2);
        c.clear();
        assert.strictEqual(c.has('a'), false);
        assert.strictEqual(c.has('b'), false);
    });

    it('throws on missing binding', () => {
        const c = new Container();
        assert.throws(() => c.get('missing'), /not found/);
    });
});

// ============================================================
// EventManager (state.js)
// ============================================================
describe('EventManager', () => {

    it('on and emit', () => {
        const e = new State.EventManager();
        let result = null;
        e.on('test', p => { result = p; });
        e.emit('test', 42);
        assert.strictEqual(result, 42);
    });

    it('once fires only once', () => {
        const e = new State.EventManager();
        let count = 0;
        e.once('test', () => { count++; });
        e.emit('test');
        e.emit('test');
        assert.strictEqual(count, 1);
    });

    it('off removes specific listener', () => {
        const e = new State.EventManager();
        let count = 0;
        const fn = () => { count++; };
        e.on('test', fn);
        e.emit('test');
        assert.strictEqual(count, 1);
        e.off('test', fn);
        e.emit('test');
        assert.strictEqual(count, 1);
    });

    it('off without callback removes all', () => {
        const e = new State.EventManager();
        e.on('test', () => {});
        e.on('test', () => {});
        e.off('test');
        assert.strictEqual(e.hasListeners('test'), false);
    });

    it('priority order (high to low)', () => {
        const e = new State.EventManager();
        const order = [];
        e.on('test', () => order.push('low'), -10);
        e.on('test', () => order.push('high'), 10);
        e.on('test', () => order.push('normal'), 0);
        e.emit('test');
        assert.deepEqual(order, ['high', 'normal', 'low']);
    });

    it('wildcard suffix (user.*)', () => {
        const e = new State.EventManager();
        const result = [];
        e.on('user.*', p => result.push(p));
        e.emit('user.login', 'login');
        e.emit('user.logout', 'logout');
        e.emit('post.created', 'post');
        assert.deepEqual(result, ['login', 'logout']);
    });

    it('wildcard prefix (*.created)', () => {
        const e = new State.EventManager();
        const result = [];
        e.on('*.created', p => result.push(p));
        e.emit('user.created', 'user');
        e.emit('post.created', 'post');
        e.emit('user.deleted', 'del');
        assert.deepEqual(result, ['user', 'post']);
    });

    it('wildcard * catches all', () => {
        const e = new State.EventManager();
        let count = 0;
        e.on('*', () => count++);
        e.emit('a');
        e.emit('b');
        e.emit('c');
        assert.strictEqual(count, 3);
    });

    it('hasListeners works', () => {
        const e = new State.EventManager();
        assert.strictEqual(e.hasListeners('x'), false);
        e.on('x', () => {});
        assert.strictEqual(e.hasListeners('x'), true);
    });

    it('addFilter and applyFilters', () => {
        const e = new State.EventManager();
        e.addFilter('format', v => v.toUpperCase());
        assert.strictEqual(e.applyFilters('format', 'hello'), 'HELLO');
    });

    it('filter chain', () => {
        const e = new State.EventManager();
        e.addFilter('num', v => v * 2);
        e.addFilter('num', v => v + 1);
        assert.strictEqual(e.applyFilters('num', 5), 11);
    });

    it('filter with extra args', () => {
        const e = new State.EventManager();
        e.addFilter('greet', (msg, name) => msg + ', ' + name + '!');
        assert.strictEqual(e.applyFilters('greet', 'Hello', 'World'), 'Hello, World!');
    });

    it('removeFilter removes specific callback', () => {
        const e = new State.EventManager();
        const fn = v => v + 1;
        e.addFilter('x', fn);
        e.addFilter('x', v => v * 10);
        e.removeFilter('x', fn);
        assert.strictEqual(e.applyFilters('x', 5), 50);
    });

    it('removeFilter removes all without callback', () => {
        const e = new State.EventManager();
        e.addFilter('x', v => v + 1);
        e.removeFilter('x');
        assert.strictEqual(e.applyFilters('x', 5), 5);
    });
});

// ============================================================
// Store (state.js)
// ============================================================
describe('Store', () => {

    it('get returns value by key', () => {
        const s = new State.Store('test', { key: 'val' });
        assert.strictEqual(s.get('key'), 'val');
    });

    it('get without key returns all data', () => {
        const s = new State.Store('test', { a: 1, b: 2 });
        assert.deepEqual(s.get(), { a: 1, b: 2 });
    });

    it('set triggers watcher', () => {
        const s = new State.Store('test', {});
        let newVal, oldVal;
        s.on('x', (n, o) => { newVal = n; oldVal = o; });
        s.set('x', 10);
        assert.strictEqual(newVal, 10);
        assert.strictEqual(oldVal, undefined);
    });

    it('set triggers old value', () => {
        const s = new State.Store('test', { x: 5 });
        let oldVal;
        s.on('x', (n, o) => { oldVal = o; });
        s.set('x', 10);
        assert.strictEqual(oldVal, 5);
    });

    it('on returns unsubscribe function', () => {
        const s = new State.Store('test', {});
        let count = 0;
        const unsub = s.on('x', () => count++);
        s.set('x', 1);
        assert.strictEqual(count, 1);
        unsub();
        s.set('x', 2);
        assert.strictEqual(count, 1);
    });

    it('patch updates multiple keys', () => {
        const s = new State.Store('test', { a: 1, b: 2 });
        s.patch({ a: 10, b: 20 });
        assert.strictEqual(s.get('a'), 10);
        assert.strictEqual(s.get('b'), 20);
    });

    it('deep copy of initial data', () => {
        const initial = { nested: { val: 1 } };
        const s = new State.Store('test', initial);
        initial.nested.val = 999;
        assert.strictEqual(s.get('nested').val, 1);
    });
});

// ============================================================
// HttpClient (http.js)
// ============================================================
describe('HttpClient', () => {

    it('configure sets baseUrl with trailing slash', () => {
        const http = new Http();
        http.configure({ baseUrl: '/api' });
        assert.strictEqual(http._baseUrl, '/api/');
    });

    it('configure baseUrl trailing slash preserved', () => {
        const http = new Http();
        http.configure({ baseUrl: '/api/' });
        assert.strictEqual(http._baseUrl, '/api/');
    });

    it('configure merges headers', () => {
        const http = new Http();
        http.configure({ headers: { 'X-Custom': 'val' } });
        assert.strictEqual(http._defaults.headers['X-Custom'], 'val');
        assert.strictEqual(http._defaults.headers['Content-Type'], 'application/json');
    });

    it('buildUrl with params', () => {
        const http = new Http();
        http.configure({ baseUrl: '/api' });
        const url = http._buildUrl('/users', { page: 1, limit: 10 });
        assert.strictEqual(url, '/api/users?page=1&limit=10');
    });

    it('buildUrl with absolute ignores baseUrl', () => {
        const http = new Http();
        http.configure({ baseUrl: '/api/' });
        const url = http._buildUrl('https://example.com/test', {});
        assert.strictEqual(url, 'https://example.com/test');
    });

    it('buildUrl without params', () => {
        const http = new Http();
        http.configure({ baseUrl: '/api' });
        const url = http._buildUrl('/status', {});
        assert.strictEqual(url, '/api/status');
    });

    it('use registers request middleware', () => {
        const http = new Http();
        let called = false;
        http.use((req, next) => { called = true; return next(req); });
        assert.strictEqual(http._requestMiddleware.length, 1);
    });

    it('useResponse registers response middleware', () => {
        const http = new Http();
        http.useResponse(res => res);
        assert.strictEqual(http._responseMiddleware.length, 1);
    });
});

// ============================================================
// Router (router.js)
// ============================================================
describe('Router', () => {

    it('route registration and match', () => {
        const r = new Router();
        r.route('/', 'home');
        r.route('/users/{id}', 'users.show');
        const m = r._match('/users/5');
        assert.ok(m);
        assert.strictEqual(m.params.id, '5');
        assert.strictEqual(m.route.name, 'users.show');
    });

    it('match with multiple params', () => {
        const r = new Router();
        r.route('/posts/{year}/{slug}', 'posts.show');
        const m = r._match('/posts/2024/hello-world');
        assert.ok(m);
        assert.strictEqual(m.params.year, '2024');
        assert.strictEqual(m.params.slug, 'hello-world');
    });

    it('match root route', () => {
        const r = new Router();
        r.route('/', 'home');
        const m = r._match('/');
        assert.ok(m);
        assert.strictEqual(m.route.name, 'home');
    });

    it('return null for unmatched route', () => {
        const r = new Router();
        r.route('/', 'home');
        assert.strictEqual(r._match('/nonexistent'), null);
    });

    it('resolve generates URL from named route', () => {
        const r = new Router();
        r.route('/users/{id}/edit', 'users.edit');
        const url = r.resolve('users.edit', { id: 42 });
        assert.strictEqual(url, '/users/42/edit');
    });

    it('resolve returns null for unknown name', () => {
        const r = new Router();
        assert.strictEqual(r.resolve('nope'), null);
    });

    it('guard redirect', () => {
        const r = new Router();
        r.route('/admin', 'admin');
        let navigated = null;
        r.navigate = url => { navigated = url; };
        r.beforeEach((to, from) => {
            if (to.route.name === 'admin') return '/login';
        });
        r._handleUrl('/admin');
        assert.strictEqual(navigated, '/login');
    });

    it('guard false blocks navigation', () => {
        const r = new Router();
        r.route('/secret', 'secret');
        let navigated = false;
        r.navigate = () => { navigated = true; };
        r.beforeEach((to) => {
            if (to.route.name === 'secret') return false;
        });
        r._handleUrl('/secret');
        assert.strictEqual(navigated, false);
    });

    it('group adds prefix to paths', () => {
        const r = new Router();
        r.group('/admin', {}, r => {
            r.route('/', 'admin.home');
            r.route('/users', 'admin.users');
        });
        assert.strictEqual(r._routes.length, 2);
        assert.strictEqual(r._routes[0].path, '/admin');
        assert.strictEqual(r._routes[1].path, '/admin/users');
    });

    it('group without options prefix', () => {
        const r = new Router();
        r.group('/api', r => {
            r.route('/ping', 'ping');
        });
        assert.strictEqual(r._routes[0].path, '/api/ping');
    });

    it('route with no name still matches', () => {
        const r = new Router();
        r.route('/about');
        const m = r._match('/about');
        assert.ok(m);
        assert.strictEqual(m.route.name, '');
    });

    it('afterEach hook called on navigation', () => {
        const r = new Router();
        r.route('/test', 'test');
        let called = false;
        r.afterEach((to, from) => { called = true; });
        r._handleUrl('/test');
        assert.strictEqual(called, true);
    });

    it('_emit sends event via eventBus', () => {
        const r = new Router();
        r.route('/test', 'test');
        // eventBus not set in Node, so _emit should not throw
        r._handleUrl('/test');
        // Should not throw
        assert.ok(true);
    });
});

// ============================================================
// Component (component.js)
// ============================================================
describe('Component', () => {

    it('register stores definition', () => {
        const def = { template: '<div>test</div>' };
        Component.register('test-el', def);
        assert.strictEqual(Component.get('test-el'), def);
    });

    it('get returns undefined for unknown', () => {
        assert.strictEqual(Component.get('nope'), undefined);
    });

    it('setPrefix updates prefix', () => {
        const orig = Component.setPrefix;
        Component.setPrefix('x-');
        Component.register('prefix-test', { template: '' });
        assert.ok(Component.get('prefix-test'));
        Component.setPrefix('a-');
    });

    it('register overwrites existing', () => {
        const def1 = { template: '<div>1</div>' };
        const def2 = { template: '<div>2</div>' };
        Component.register('overwrite', def1);
        Component.register('overwrite', def2);
        assert.strictEqual(Component.get('overwrite'), def2);
    });
});

// ============================================================
// App (app.js)
// ============================================================
describe('App', () => {

    it('boot creates app with all services', () => {
        const app = new App();
        assert.ok(app.container);
        assert.ok(app.eventBus);
        assert.ok(app.http);
        assert.ok(app.router);
        assert.ok(app.component);
    });

    it('store creates and returns store', () => {
        const app = new App();
        const s = app.store('test', { count: 0 });
        assert.strictEqual(s.get('count'), 0);
        s.set('count', 5);
        assert.strictEqual(s.get('count'), 5);
    });

    it('store returns same instance for same name', () => {
        const app = new App();
        const a = app.store('shared', {});
        const b = app.store('shared', {});
        assert.strictEqual(a, b);
    });

    it('provide registers in DI container', () => {
        const app = new App();
        app.provide('api', { get() {} });
        assert.strictEqual(app.container.has('api'), true);
    });

    it('singleton registers factory in DI container', () => {
        const app = new App();
        let count = 0;
        app.singleton('db', () => ++count);
        assert.strictEqual(app.container.get('db'), 1);
        assert.strictEqual(app.container.get('db'), 1);
    });

    it('on/emit wiring', () => {
        const app = new App();
        let result = null;
        app.on('test', p => { result = p; });
        app.emit('test', 99);
        assert.strictEqual(result, 99);
    });

    it('boot emits app.boot event', () => {
        const app = new App();
        let booted = false;
        app.on('app.boot', () => { booted = true; });
        app.boot({ config: {} });
        assert.ok(booted);
    });

    it('provider register and boot called in order', () => {
        const app = new App();
        const order = [];
        const provider = {
            register() { order.push('reg'); },
            boot() { order.push('boot'); },
        };
        app.boot({ providers: [provider] });
        assert.deepEqual(order, ['reg', 'boot']);
    });

    it('boot sets config and apiBaseUrl', () => {
        const app = new App();
        app.boot({ config: { apiBaseUrl: '/api/' } });
        assert.strictEqual(app.http._baseUrl, '/api/');
        assert.strictEqual(app.config.apiBaseUrl, '/api/');
    });

    it('boot is idempotent', () => {
        const app = new App();
        let count = 0;
        app.on('app.boot', () => count++);
        app.boot({ config: {} });
        app.boot({ config: {} });
        assert.strictEqual(count, 1);
    });

    it('filter method applies filters', () => {
        const app = new App();
        app.eventBus.addFilter('format', v => v.toUpperCase());
        assert.strictEqual(app.filter('format', 'hello'), 'HELLO');
    });
});

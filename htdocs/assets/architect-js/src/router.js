;(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.Architect = root.Architect || {};
        root.Architect.Router = factory();
    }
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    function Router() {
        this._routes = [];
        this._named = {};
        this._groups = [];
        this._guards = [];
        this._afterHooks = [];
        this._current = null;
        this._mode = 'history';
        this._base = '';
        this._started = false;
    }

    Router.prototype.configure = function (opts) {
        if (opts.mode) this._mode = opts.mode;
        if (opts.base !== undefined) this._base = opts.base.replace(/\/+$/, '') + '/';
        return this;
    };

    Router.prototype.route = function (path, nameOrOpts, opts) {
        var name = '';
        var options = {};
        if (typeof nameOrOpts === 'string') {
            name = nameOrOpts;
            options = opts || {};
        } else {
            options = nameOrOpts || {};
        }

        this._routes.push({
            path: this._resolvePath(path),
            pattern: this._pathToRegExp(path),
            paramNames: this._extractParams(path),
            name: name || options.name || '',
            component: options.component || null,
            middleware: options.middleware || [],
            meta: options.meta || {},
        });

        if (name) this._named[name] = this._routes[this._routes.length - 1];
        return this;
    };

    Router.prototype.group = function (prefix, options, callback) {
        if (typeof options === 'function') {
            callback = options;
            options = {};
        }
        this._groups.push({ prefix: prefix, options: options || {} });
        if (typeof callback === 'function') callback(this);
        this._groups.pop();
        return this;
    };

    Router.prototype.resolve = function (name, params) {
        var route = this._named[name];
        if (!route) return null;
        var url = route.path;
        if (params) {
            for (var key in params) {
                if (params.hasOwnProperty(key)) {
                    url = url.replace('{' + key + '}', encodeURIComponent(params[key]));
                }
            }
        }
        return url;
    };

    Router.prototype.navigate = function (url, options) {
        options = options || {};
        url = url.replace(/\/+/g, '/').replace(/\/$/, '') || '/';
        if (typeof window === 'undefined') return this;
        if (this._mode === 'hash') {
            window.location.hash = '#' + url;
        } else {
            var fullPath = this._base.replace(/\/+$/, '') + url;
            history.pushState({ url: url }, '', fullPath);
            this._handleUrl(url);
        }
        return this;
    };

    Router.prototype.current = function () {
        return this._current;
    };

    Router.prototype.beforeEach = function (fn) {
        this._guards.push(fn);
        return this;
    };

    Router.prototype.afterEach = function (fn) {
        this._afterHooks.push(fn);
        return this;
    };

    Router.prototype.start = function () {
        if (this._started) return;
        this._started = true;

        if (typeof window === 'undefined') return this;

        var self = this;
        window.addEventListener('popstate', function () {
            self._handleUrl(self._getPath());
        });

        if (typeof document !== 'undefined') {
            document.addEventListener('click', function (e) {
                var link = e.target.closest('a[href]');
                if (!link) return;
                var href = link.getAttribute('href');
                if (self._isInternal(href)) {
                    e.preventDefault();
                    self.navigate(href);
                }
            });
        }

        this._handleUrl(this._getPath());
        return this;
    };

    Router.prototype._handleUrl = function (url) {
        url = '/' + url.replace(/^\/+/, '');
        var match = this._match(url);
        if (!match) {
            this._emit('router.error', { url: url, code: 404 });
            return;
        }
        var to = {
            url: url,
            params: match.params,
            route: match.route,
            meta: match.route.meta,
        };
        var from = this._current ? { url: this._current.url, params: this._current.params, route: this._current.route } : null;
        var self = this;

        this._runGuards(to, from, 0, function () {
            self._current = to;
            self._emit('router.navigate', to);
            if (match.route.component) self._loadComponent(match.route.component, to);
            for (var i = 0; i < self._afterHooks.length; i++) {
                self._afterHooks[i](to, from);
            }
        });
    };

    Router.prototype._runGuards = function (to, from, index, done) {
        if (index >= this._guards.length) { done(); return; }
        var result = this._guards[index](to, from);
        if (result === false) return;
        if (typeof result === 'string') { this.navigate(result); return; }
        this._runGuards(to, from, index + 1, done);
    };

    Router.prototype._loadComponent = function (component, route) {
        var self = this;
        if (typeof component === 'function') {
            var result = component();
            if (result && typeof result.then === 'function') {
                result.then(function (mod) {
                    var content = mod.default || mod;
                    self._render(content, route);
                });
            } else {
                self._render(result, route);
            }
        } else if (typeof component === 'string') {
            self._render(component, route);
        }
    };

    Router.prototype._render = function (content, route) {
        var root = document.querySelector('#app');
        if (!root) return;
        if (typeof content === 'function') {
            root.innerHTML = content(route);
        } else {
            root.innerHTML = content;
        }
        this._emit('router.rendered', route);
    };

    Router.prototype._match = function (url) {
        url = url.split('?')[0];
        for (var i = 0; i < this._routes.length; i++) {
            var route = this._routes[i];
            var m = url.match(route.pattern);
            if (m) {
                var params = {};
                for (var j = 0; j < route.paramNames.length; j++) {
                    params[route.paramNames[j]] = decodeURIComponent(m[j + 1]);
                }
                return { route: route, params: params };
            }
        }
        return null;
    };

    Router.prototype._resolvePath = function (path) {
        for (var i = 0; i < this._groups.length; i++) {
            path = this._groups[i].prefix + path;
        }
        return path.replace(/\/+/g, '/').replace(/\/$/, '') || '/';
    };

    Router.prototype._pathToRegExp = function (path) {
        return new RegExp('^' + path.replace(/\{(\w+)\}/g, '([^/]+)').replace(/\//g, '\\/') + '$');
    };

    Router.prototype._extractParams = function (path) {
        var params = [], re = /\{(\w+)\}/g, m;
        while ((m = re.exec(path)) !== null) params.push(m[1]);
        return params;
    };

    Router.prototype._getPath = function () {
        if (typeof window === 'undefined') return '/';
        if (this._mode === 'hash') return window.location.hash.replace(/^#/, '') || '/';
        var path = window.location.pathname;
        if (this._base) {
            path = path.replace(new RegExp('^' + this._base.replace(/\//g, '\\/')), '/');
        }
        return path || '/';
    };

    Router.prototype._isInternal = function (href) {
        if (!href) return false;
        if (href.indexOf('://') !== -1 || href.indexOf('//') === 0) return false;
        if (href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return false;
        if (href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) return false;
        return true;
    };

    Router.prototype._emit = function (event, data) {
        if (typeof root !== 'undefined' && root.Architect && root.Architect.eventBus) {
            root.Architect.eventBus.emit(event, data);
        }
    };

    return Router;
});

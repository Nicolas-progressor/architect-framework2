;(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.Architect = root.Architect || {};
        root.Architect.Component = factory();
    }
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    var root = typeof self !== 'undefined' ? self : (typeof global !== 'undefined' ? global : null);
    var registry = {};
    var prefix = 'a-';

    function setPrefix(p) { prefix = p; }

    function register(name, definition) {
        registry[name] = definition;
        if (root && root.customElements) {
            defineElement(name, definition);
        }
    }

    function get(name) {
        return registry[name];
    }

    function defineElement(name, def) {
        var tag = prefix + name;
        if (customElements.get(tag)) return;

        var template = def.template || '';
        var storeKeys = def.store || [];
        var initialData = typeof def.data === 'function' ? def.data() : (def.data || {});
        var propsDef = def.props || {};
        var watch = def.watch || {};
        var hooks = {
            beforeCreate: def.beforeCreate,
            created: def.created,
            mounted: def.mounted,
            updated: def.updated,
            destroyed: def.destroyed,
        };

        var methods = {};
        for (var key in def) {
            if (['template', 'store', 'data', 'props', 'watch',
                'beforeCreate', 'created', 'mounted', 'updated', 'destroyed',
                'render'].indexOf(key) === -1) {
                methods[key] = def[key];
            }
        }

        var proto = Object.create(HTMLElement.prototype);

        proto._data = JSON.parse(JSON.stringify(initialData));
        proto._unsubs = [];

        proto.connectedCallback = function () {
            this._props = {};
            for (var key in propsDef) {
                var attr = this.getAttribute(key);
                this._props[key] = attr !== null ? attr : (propsDef[key].default || '');
            }

            if (hooks.beforeCreate) hooks.beforeCreate.call(this);

            for (var key in methods) {
                this[key] = methods[key].bind(this);
            }

            if (hooks.created) hooks.created.call(this);

            this._render();

            var self = this;
            for (var i = 0; i < storeKeys.length; i++) {
                var parts = storeKeys[i].split('.');
                var storeName = parts[0];
                var storeKey = parts.slice(1).join('.');
                if (root.Architect && root.Architect.state) {
                    var store = root.Architect.state.createStore(storeName);
                    var unsub = store.on(storeKey || '*', function () {
                        self._render();
                    });
                    self._unsubs.push(unsub);
                }
            }

            if (hooks.mounted) hooks.mounted.call(this);
        };

        proto.disconnectedCallback = function () {
            for (var i = 0; i < this._unsubs.length; i++) this._unsubs[i]();
            this._unsubs = [];
            if (hooks.destroyed) hooks.destroyed.call(this);
        };

        proto._render = function () {
            var html = this._parse(template);
            this.innerHTML = html;
            this._bindEvents();
            if (hooks.updated) hooks.updated.call(this);
        };

        proto._parse = function (tmpl) {
            var self = this;

            var result = tmpl.replace(/\{\{(.+?)\}\}/g, function (_, expr) {
                return self._eval(expr.trim());
            });

            result = result.replace(/x-show="([^"]+)"/g, function (_, expr) {
                return self._eval(expr) ? '' : 'style="display:none"';
            });

            result = result.replace(/@(\w+)="([^"]+)"/g, function (_, ev, method) {
                return 'data-on-' + ev + '="' + method + '"';
            });

            result = result.replace(/:class="([^"]+)"/g, function (_, expr) {
                var val = self._eval(expr);
                if (typeof val === 'object') {
                    var cls = [];
                    for (var k in val) { if (val[k]) cls.push(k); }
                    return 'class="' + cls.join(' ') + '"';
                }
                return 'class="' + val + '"';
            });

            return result;
        };

        proto._eval = function (expr) {
            if (expr.indexOf('data.') === 0) {
                return this._deepGet(this._data, expr.slice(5));
            }
            if (expr.indexOf('props.') === 0) {
                return this._deepGet(this._props, expr.slice(6));
            }
            if (expr.indexOf('store.') === 0) {
                var parts = expr.slice(6).split('.');
                var sname = parts[0];
                var skey = parts.slice(1).join('.');
                if (root.Architect && root.Architect.state) {
                    var store = root.Architect.state.createStore(sname);
                    var val = store.get(skey);
                    return val !== undefined ? String(val) : '';
                }
                return '';
            }
            return expr;
        };

        proto._deepGet = function (obj, path) {
            var parts = path.split('.');
            var cur = obj;
            for (var i = 0; i < parts.length; i++) {
                if (cur == null) return '';
                cur = cur[parts[i]];
            }
            return cur == null ? '' : String(cur);
        };

        proto._bindEvents = function () {
            var self = this;
            var handlers = this.querySelectorAll('[data-on-click]');
            for (var i = 0; i < handlers.length; i++) {
                (function (el) {
                    var method = el.getAttribute('data-on-click');
                    el.addEventListener('click', function (e) {
                        if (typeof self[method] === 'function') self[method](e);
                    });
                    el.removeAttribute('data-on-click');
                })(handlers[i]);
            }
        };

        customElements.define(tag, proto);
    }

    return {
        register: register,
        get: get,
        setPrefix: setPrefix,
    };
});

;(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.Architect = root.Architect || {};
        root.Architect.State = factory();
    }
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    function EventManager() {
        this._listeners = {};
        this._patterns = {};
        this._filters = {};
        this._filterPatterns = {};
    }

    EventManager.prototype.on = function (event, callback, priority) {
        priority = priority || 0;
        var store = event.indexOf('*') !== -1 ? this._patterns : this._listeners;
        if (!store[event]) store[event] = [];
        store[event].push({ callback: callback, priority: priority });
        store[event].sort(function (a, b) { return b.priority - a.priority; });
        return this;
    };

    EventManager.prototype.once = function (event, callback) {
        var self = this;
        var wrapper = function (payload) {
            self.off(event, wrapper);
            callback(payload);
        };
        wrapper._once = true;
        return this.on(event, wrapper);
    };

    EventManager.prototype.off = function (event, callback) {
        var store = event.indexOf('*') !== -1 ? this._patterns : this._listeners;
        if (!store[event]) return this;
        if (callback) {
            store[event] = store[event].filter(function (item) {
                return item.callback !== callback;
            });
        } else {
            delete store[event];
        }
        return this;
    };

    EventManager.prototype.emit = function (event, payload) {
        var list = this._resolve(event);
        for (var i = 0; i < list.length; i++) {
            list[i].callback(payload);
        }
        return this;
    };

    EventManager.prototype.hasListeners = function (event) {
        if (this._listeners[event] && this._listeners[event].length) return true;
        for (var p in this._patterns) {
            if (this._match(p, event)) return true;
        }
        return false;
    };

    EventManager.prototype.addFilter = function (hook, callback, priority) {
        priority = priority || 10;
        var store = hook.indexOf('*') !== -1 ? this._filterPatterns : this._filters;
        if (!store[hook]) store[hook] = [];
        store[hook].push({ callback: callback, priority: priority });
        store[hook].sort(function (a, b) { return b.priority - a.priority; });
        return this;
    };

    EventManager.prototype.removeFilter = function (hook, callback) {
        var store = hook.indexOf('*') !== -1 ? this._filterPatterns : this._filters;
        if (!store[hook]) return this;
        if (callback) {
            store[hook] = store[hook].filter(function (item) {
                return item.callback !== callback;
            });
        } else {
            delete store[hook];
        }
        return this;
    };

    EventManager.prototype.applyFilters = function (hook, value) {
        var args = Array.prototype.slice.call(arguments, 2);
        var list = this._resolveFilters(hook);
        var result = value;
        for (var i = 0; i < list.length; i++) {
            result = list[i].callback.apply(null, [result].concat(args));
        }
        return result;
    };

    EventManager.prototype._resolve = function (event) {
        var result = [];
        if (this._listeners[event]) {
            result = result.concat(this._listeners[event]);
        }
        for (var p in this._patterns) {
            if (this._match(p, event)) {
                result = result.concat(this._patterns[p]);
            }
        }
        result.sort(function (a, b) { return b.priority - a.priority; });
        return result;
    };

    EventManager.prototype._resolveFilters = function (hook) {
        var result = [];
        if (this._filters[hook]) result = result.concat(this._filters[hook]);
        for (var p in this._filterPatterns) {
            if (this._match(p, hook)) {
                result = result.concat(this._filterPatterns[p]);
            }
        }
        result.sort(function (a, b) { return b.priority - a.priority; });
        return result;
    };

    EventManager.prototype._match = function (pattern, name) {
        if (pattern === '*') return true;
        if (pattern.charAt(pattern.length - 1) === '*') {
            return name.indexOf(pattern.slice(0, -1)) === 0;
        }
        if (pattern.charAt(0) === '*') {
            return name.indexOf(pattern.slice(1)) === name.length - pattern.slice(1).length;
        }
        return pattern === name;
    };

    function Store(name, initial) {
        this._name = name;
        this._data = JSON.parse(JSON.stringify(initial || {}));
        this._watchers = {};
    }

    Store.prototype.get = function (key) {
        if (!key) return this._data;
        return this._data[key];
    };

    Store.prototype.set = function (key, value) {
        var old = this._data[key];
        this._data[key] = value;
        this._notify(key, value, old);
        this._notify('*', { key: key, value: value, old: old });
    };

    Store.prototype.patch = function (data) {
        for (var key in data) {
            if (data.hasOwnProperty(key)) this.set(key, data[key]);
        }
    };

    Store.prototype.on = function (key, callback) {
        if (!this._watchers[key]) this._watchers[key] = [];
        this._watchers[key].push(callback);
        var self = this;
        return function () { self.off(key, callback); };
    };

    Store.prototype.off = function (key, callback) {
        if (!this._watchers[key]) return;
        if (callback) {
            this._watchers[key] = this._watchers[key].filter(function (fn) {
                return fn !== callback;
            });
        } else {
            delete this._watchers[key];
        }
    };

    Store.prototype._notify = function (key, value, old) {
        if (!this._watchers[key]) return;
        for (var i = 0; i < this._watchers[key].length; i++) {
            this._watchers[key][i](value, old);
        }
    };

    function createStore(name, initial) {
        return new Store(name, initial);
    }

    return {
        EventManager: EventManager,
        Store: Store,
        createStore: createStore,
    };
});

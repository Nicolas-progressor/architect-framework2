;(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define(['./core', './state', './http', './router', './component'], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory(
            require('./core'),
            require('./state'),
            require('./http'),
            require('./router'),
            require('./component')
        );
    } else {
        root.Architect = root.Architect || {};
        root.Architect.App = factory(
            root.Architect.Container,
            root.Architect.State,
            root.Architect.Http,
            root.Architect.Router,
            root.Architect.Component
        );
    }
})(typeof self !== 'undefined' ? self : this, function (Container, State, Http, Router, Component) {
    'use strict';

    var root = typeof self !== 'undefined' ? self : (typeof global !== 'undefined' ? global : null);

    function App() {
        this.container = new Container();
        this.eventBus = new State.EventManager();
        this.http = new Http();
        this.router = new Router();
        this.component = Component;
        this.config = {};
        this._storeManager = {};
        this._booted = false;
    }

    App.prototype.configure = function (options) {
        options = options || {};
        this.config = options.config || {};

        this.http.configure({
            baseUrl: this.config.apiBaseUrl || '',
        });

        if (this.config.router) {
            this.router.configure(this.config.router);
        }

        if (this.config.componentPrefix) {
            this.component.setPrefix(this.config.componentPrefix);
        }

        return this;
    };

    App.prototype.provide = function (id, instance) {
        this.container.set(id, instance);
        return this;
    };

    App.prototype.singleton = function (id, factory) {
        this.container.singleton(id, factory);
        return this;
    };

    App.prototype.boot = function (options) {
        if (this._booted) return;
        this._booted = true;
        options = options || {};

        this.configure(options);

        var providers = options.providers || [];
        for (var i = 0; i < providers.length; i++) {
            if (typeof providers[i].register === 'function') {
                providers[i].register(this);
            }
        }
        for (var j = 0; j < providers.length; j++) {
            if (typeof providers[j].boot === 'function') {
                providers[j].boot(this);
            }
        }

        var self = this;
        if (root.Architect) {
            root.Architect.eventBus = this.eventBus;
            root.Architect.container = this.container;
            root.Architect.state = State;
        }

        this.eventBus.emit('app.boot', { config: this.config });

        if (this.config.router !== false) {
            this.router.start();
        }

        this.eventBus.emit('app.ready', { root: options.root || '#app' });

        return this;
    };

    App.prototype.store = function (name, initial) {
        if (!this._storeManager[name]) {
            this._storeManager[name] = new State.Store(name, initial);
        }
        return this._storeManager[name];
    };

    App.prototype.on = function (event, callback, priority) {
        this.eventBus.on(event, callback, priority);
        return this;
    };

    App.prototype.emit = function (event, payload) {
        this.eventBus.emit(event, payload);
        return this;
    };

    App.prototype.filter = function (hook, value) {
        var args = Array.prototype.slice.call(arguments, 1);
        return this.eventBus.applyFilters.apply(this.eventBus, [hook].concat(args));
    };

    return App;
});

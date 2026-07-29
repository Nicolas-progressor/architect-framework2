;(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.Architect = root.Architect || {};
        root.Architect.Container = factory();
    }
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    function Container() {
        this._bindings = {};
        this._instances = {};
    }

    Container.prototype.set = function (id, instance) {
        this._bindings[id] = { type: 'value', value: instance };
        return this;
    };

    Container.prototype.singleton = function (id, factory) {
        this._bindings[id] = { type: 'singleton', factory: factory };
        delete this._instances[id];
        return this;
    };

    Container.prototype.factory = function (id, factory) {
        this._bindings[id] = { type: 'factory', factory: factory };
        return this;
    };

    Container.prototype.get = function (id) {
        var binding = this._bindings[id];
        if (!binding) throw new Error('Container: "' + id + '" not found');
        if (binding.type === 'value') return binding.value;
        if (binding.type === 'singleton') {
            if (!(id in this._instances)) {
                this._instances[id] = binding.factory(this);
            }
            return this._instances[id];
        }
        return binding.factory(this);
    };

    Container.prototype.has = function (id) {
        return id in this._bindings;
    };

    Container.prototype.remove = function (id) {
        delete this._bindings[id];
        delete this._instances[id];
        return this;
    };

    Container.prototype.clear = function () {
        this._bindings = {};
        this._instances = {};
    };

    return Container;
});

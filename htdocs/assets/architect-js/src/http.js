;(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.Architect = root.Architect || {};
        root.Architect.Http = factory();
    }
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    function HttpClient() {
        this._requestMiddleware = [];
        this._responseMiddleware = [];
        this._baseUrl = '';
        this._defaults = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        };
    }

    HttpClient.prototype.configure = function (options) {
        if (options.baseUrl) this._baseUrl = options.baseUrl.replace(/\/+$/, '') + '/';
        if (options.headers) {
            for (var k in options.headers) {
                if (options.headers.hasOwnProperty(k)) {
                    this._defaults.headers[k] = options.headers[k];
                }
            }
        }
        return this;
    };

    HttpClient.prototype.use = function (fn) {
        this._requestMiddleware.push(fn);
        return this;
    };

    HttpClient.prototype.useResponse = function (fn) {
        this._responseMiddleware.push(fn);
        return this;
    };

    HttpClient.prototype.get = function (url, options) {
        return this._request('GET', url, null, options || {});
    };

    HttpClient.prototype.post = function (url, body, options) {
        return this._request('POST', url, body, options || {});
    };

    HttpClient.prototype.put = function (url, body, options) {
        return this._request('PUT', url, body, options || {});
    };

    HttpClient.prototype.patch = function (url, body, options) {
        return this._request('PATCH', url, body, options || {});
    };

    HttpClient.prototype.delete = function (url, options) {
        return this._request('DELETE', url, null, options || {});
    };

    HttpClient.prototype._request = function (method, url, body, options) {
        var self = this;
        var fullUrl = this._buildUrl(url, options.params);
        var headers = {};

        for (var k in this._defaults.headers) {
            if (this._defaults.headers.hasOwnProperty(k)) headers[k] = this._defaults.headers[k];
        }
        if (options.headers) {
            for (var h in options.headers) {
                if (options.headers.hasOwnProperty(h)) headers[h] = options.headers[h];
            }
        }

        var request = {
            method: method,
            url: fullUrl,
            headers: headers,
            body: body ? JSON.stringify(body) : null,
        };

        return this._runRequestMiddleware(request, 0).then(function (finalReq) {
            var fetchOpts = {
                method: finalReq.method,
                headers: finalReq.headers,
            };
            if (finalReq.body) fetchOpts.body = finalReq.body;

            return fetch(finalReq.url, fetchOpts);
        }).then(function (response) {
            return self._runResponseMiddleware(response, 0);
        }).then(function (result) {
            if (result instanceof Response) {
                var ct = result.headers.get('content-type') || '';
                if (ct.indexOf('json') !== -1) return result.json();
                return result.text();
            }
            return result;
        });
    };

    HttpClient.prototype._runRequestMiddleware = function (req, index) {
        var self = this;
        if (index >= this._requestMiddleware.length) return Promise.resolve(req);
        return Promise.resolve(this._requestMiddleware[index](req, function (newReq) {
            return self._runRequestMiddleware(newReq || req, index + 1);
        }));
    };

    HttpClient.prototype._runResponseMiddleware = function (res, index) {
        var self = this;
        if (index >= this._responseMiddleware.length) return Promise.resolve(res);
        return Promise.resolve(this._responseMiddleware[index](res, function (newRes) {
            return self._runResponseMiddleware(newRes || res, index + 1);
        }));
    };

    HttpClient.prototype._buildUrl = function (url, params) {
        var fullUrl = url.indexOf('://') !== -1 ? url : this._baseUrl + url.replace(/^\//, '');
        if (params) {
            var parts = [];
            for (var k in params) {
                if (params.hasOwnProperty(k)) {
                    parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(params[k]));
                }
            }
            if (parts.length) {
                fullUrl += (fullUrl.indexOf('?') !== -1 ? '&' : '?') + parts.join('&');
            }
        }
        return fullUrl;
    };

    return HttpClient;
});

'use strict';

/** Minimal router: add('GET', '/api/projects/:id', handler) and match(method, path). */
class Router {
  constructor() {
    this.routes = [];
  }

  add(method, pattern, handler) {
    const keys = [];
    const regex = new RegExp(
      '^' + pattern.replace(/:([A-Za-z]+)/g, (_, k) => (keys.push(k), '([^/]+)')) + '/?$'
    );
    this.routes.push({ method, regex, keys, handler });
  }

  match(method, pathname) {
    let pathMatched = false;
    for (const r of this.routes) {
      const m = r.regex.exec(pathname);
      if (!m) continue;
      pathMatched = true;
      if (r.method !== method) continue;
      const params = {};
      r.keys.forEach((k, i) => (params[k] = decodeURIComponent(m[i + 1])));
      return { handler: r.handler, params };
    }
    return { handler: null, params: {}, pathMatched };
  }
}

module.exports = { Router };

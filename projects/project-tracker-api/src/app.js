'use strict';

const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const { Store } = require('./store');
const { Router } = require('./router');
const { validate, projectSpec, taskSpec, PROJECT_STATUS } = require('./validate');

const MAX_BODY = 100 * 1024; // 100 KB
const PUBLIC_DIR = path.join(__dirname, '..', 'public');
const MIME = { '.html': 'text/html; charset=utf-8', '.css': 'text/css; charset=utf-8', '.js': 'text/javascript; charset=utf-8', '.svg': 'image/svg+xml' };

class HttpError extends Error {
  constructor(status, code, message, details) {
    super(message);
    this.status = status;
    this.code = code;
    this.details = details;
  }
}

function send(res, status, payload, extraHeaders = {}) {
  const body = payload === undefined ? '' : JSON.stringify(payload);
  res.writeHead(status, {
    'Content-Type': 'application/json; charset=utf-8',
    'Content-Length': Buffer.byteLength(body),
    'X-Content-Type-Options': 'nosniff',
    'Cache-Control': 'no-store',
    ...extraHeaders,
  });
  res.end(body);
}

function readJson(req) {
  return new Promise((resolve, reject) => {
    const type = (req.headers['content-type'] || '').split(';')[0].trim();
    if (type !== 'application/json') {
      return reject(new HttpError(415, 'unsupported_media_type', 'Send the body as application/json.'));
    }
    let size = 0;
    const chunks = [];
    req.on('data', (c) => {
      size += c.length;
      if (size > MAX_BODY) {
        reject(new HttpError(413, 'payload_too_large', 'Request body is larger than 100 KB.'));
        req.destroy();
        return;
      }
      chunks.push(c);
    });
    req.on('end', () => {
      try {
        resolve(JSON.parse(Buffer.concat(chunks).toString('utf8') || 'null'));
      } catch {
        reject(new HttpError(400, 'invalid_json', 'The body is not valid JSON.'));
      }
    });
    req.on('error', reject);
  });
}

function intParam(value, name) {
  if (!/^\d+$/.test(value)) throw new HttpError(400, 'invalid_id', `${name} must be a whole number.`);
  return Number(value);
}

function createApp({ dataFile = null, log = false, corsOrigin = '*' } = {}) {
  const store = new Store(dataFile);
  const router = new Router();

  const projectOr404 = (id) => {
    const p = store.getProject(id);
    if (!p) throw new HttpError(404, 'not_found', `Project ${id} does not exist.`);
    return p;
  };
  const checked = ({ value, errors }) => {
    if (errors.length) throw new HttpError(422, 'validation_failed', 'Some fields are not valid.', errors);
    return value;
  };

  router.add('GET', '/api/health', async () => ({ status: 200, body: { status: 'ok', uptimeSeconds: Math.round(process.uptime()) } }));

  router.add('GET', '/api/projects', async ({ query }) => {
    const status = query.get('status');
    if (status && !PROJECT_STATUS.includes(status)) {
      throw new HttpError(400, 'invalid_query', `status must be one of: ${PROJECT_STATUS.join(', ')}.`);
    }
    const page = Math.max(1, parseInt(query.get('page') || '1', 10) || 1);
    const limit = Math.min(50, Math.max(1, parseInt(query.get('limit') || '10', 10) || 10));
    return { status: 200, body: store.listProjects({ status, q: query.get('q'), page, limit }) };
  });

  router.add('POST', '/api/projects', async ({ req }) => {
    const fields = checked(validate(await readJson(req), projectSpec));
    const project = store.createProject(fields);
    return { status: 201, body: project, headers: { Location: `/api/projects/${project.id}` } };
  });

  router.add('GET', '/api/projects/:id', async ({ params }) => {
    const p = projectOr404(intParam(params.id, 'id'));
    return { status: 200, body: p };
  });

  router.add('PATCH', '/api/projects/:id', async ({ req, params }) => {
    const id = intParam(params.id, 'id');
    projectOr404(id);
    const fields = checked(validate(await readJson(req), projectSpec, { partial: true }));
    return { status: 200, body: store.updateProject(id, fields) };
  });

  router.add('DELETE', '/api/projects/:id', async ({ params }) => {
    const id = intParam(params.id, 'id');
    if (!store.deleteProject(id)) throw new HttpError(404, 'not_found', `Project ${id} does not exist.`);
    return { status: 204 };
  });

  router.add('GET', '/api/projects/:id/summary', async ({ params }) => {
    const id = intParam(params.id, 'id');
    projectOr404(id);
    return { status: 200, body: store.summary(id) };
  });

  router.add('GET', '/api/projects/:id/tasks', async ({ params, query }) => {
    const p = projectOr404(intParam(params.id, 'id'));
    const status = query.get('status');
    return { status: 200, body: { items: status ? p.tasks.filter((t) => t.status === status) : p.tasks } };
  });

  router.add('POST', '/api/projects/:id/tasks', async ({ req, params }) => {
    const id = intParam(params.id, 'id');
    projectOr404(id);
    const fields = checked(validate(await readJson(req), taskSpec));
    return { status: 201, body: store.createTask(id, fields) };
  });

  router.add('PATCH', '/api/projects/:id/tasks/:taskId', async ({ req, params }) => {
    const id = intParam(params.id, 'id');
    const taskId = intParam(params.taskId, 'taskId');
    projectOr404(id);
    const fields = checked(validate(await readJson(req), taskSpec, { partial: true }));
    const task = store.updateTask(id, taskId, fields);
    if (!task) throw new HttpError(404, 'not_found', `Task ${taskId} does not exist in project ${id}.`);
    return { status: 200, body: task };
  });

  router.add('DELETE', '/api/projects/:id/tasks/:taskId', async ({ params }) => {
    const id = intParam(params.id, 'id');
    const taskId = intParam(params.taskId, 'taskId');
    projectOr404(id);
    if (!store.deleteTask(id, taskId)) throw new HttpError(404, 'not_found', `Task ${taskId} does not exist in project ${id}.`);
    return { status: 204 };
  });

  function serveStatic(pathname, res) {
    let rel;
    try {
      rel = pathname === '/' ? 'index.html' : decodeURIComponent(pathname).replace(/^\/+/, '');
    } catch {
      return false;
    }
    const file = path.normalize(path.join(PUBLIC_DIR, rel));
    // Block path traversal: the resolved file must stay inside the public folder.
    if (!file.startsWith(PUBLIC_DIR + path.sep) || !fs.existsSync(file) || !fs.statSync(file).isFile()) return false;
    const body = fs.readFileSync(file);
    res.writeHead(200, {
      'Content-Type': MIME[path.extname(file)] || 'application/octet-stream',
      'Content-Length': body.length,
      'X-Content-Type-Options': 'nosniff',
    });
    res.end(body);
    return true;
  }

  const server = http.createServer(async (req, res) => {
    const started = Date.now();
    const url = new URL(req.url, 'http://localhost');
    const cors = corsOrigin
      ? { 'Access-Control-Allow-Origin': corsOrigin, 'Access-Control-Allow-Methods': 'GET,POST,PATCH,DELETE,OPTIONS', 'Access-Control-Allow-Headers': 'Content-Type' }
      : {};
    if (log) res.on('finish', () => console.log(`${req.method} ${url.pathname} ${res.statusCode} ${Date.now() - started}ms`));

    try {
      if (req.method === 'OPTIONS') return send(res, 204, undefined, cors);

      if (!url.pathname.startsWith('/api/')) {
        if (req.method === 'GET' && serveStatic(url.pathname, res)) return;
        throw new HttpError(404, 'not_found', 'No such page.');
      }

      const { handler, params, pathMatched } = router.match(req.method, url.pathname);
      if (!handler) {
        if (pathMatched) throw new HttpError(405, 'method_not_allowed', `${req.method} is not allowed on this path.`);
        throw new HttpError(404, 'not_found', 'No such endpoint.');
      }
      const result = await handler({ req, params, query: url.searchParams });
      send(res, result.status, result.body, { ...cors, ...(result.headers || {}) });
    } catch (err) {
      if (err instanceof HttpError) {
        send(res, err.status, { error: { code: err.code, message: err.message, ...(err.details ? { details: err.details } : {}) } }, cors);
      } else {
        console.error(err);
        send(res, 500, { error: { code: 'internal_error', message: 'Something went wrong on the server.' } }, cors);
      }
    }
  });

  return { server, store };
}

module.exports = { createApp };

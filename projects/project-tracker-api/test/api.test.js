'use strict';

const { test, before, after, describe } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { createApp } = require('../src/app');

let server, base;

before(async () => {
  ({ server } = createApp());
  await new Promise((r) => server.listen(0, r));
  base = `http://localhost:${server.address().port}`;
});
after(() => new Promise((r) => server.close(r)));

const json = (method, url, body) =>
  fetch(base + url, { method, headers: { 'Content-Type': 'application/json' }, body: body === undefined ? undefined : JSON.stringify(body) });

describe('health and routing', () => {
  test('GET /api/health returns ok', async () => {
    const res = await fetch(base + '/api/health');
    assert.equal(res.status, 200);
    assert.equal((await res.json()).status, 'ok');
  });
  test('unknown endpoint returns 404 JSON', async () => {
    const res = await fetch(base + '/api/nope');
    assert.equal(res.status, 404);
    assert.equal((await res.json()).error.code, 'not_found');
  });
  test('wrong method on a known path returns 405', async () => {
    const res = await json('PUT', '/api/projects');
    assert.equal(res.status, 405);
  });
  test('path traversal on static files is blocked', async () => {
    const res = await fetch(base + '/..%2fpackage.json');
    assert.equal(res.status, 404);
  });
});

describe('projects', () => {
  test('create, read, update and delete a project', async () => {
    let res = await json('POST', '/api/projects', { name: 'Website relaunch', client: 'Acme', dueDate: '2030-01-31' });
    assert.equal(res.status, 201);
    const created = await res.json();
    assert.equal(created.status, 'planned');
    assert.equal(res.headers.get('location'), `/api/projects/${created.id}`);

    res = await fetch(`${base}/api/projects/${created.id}`);
    assert.equal((await res.json()).name, 'Website relaunch');

    res = await json('PATCH', `/api/projects/${created.id}`, { status: 'active' });
    assert.equal(res.status, 200);
    assert.equal((await res.json()).status, 'active');

    res = await fetch(`${base}/api/projects/${created.id}`, { method: 'DELETE' });
    assert.equal(res.status, 204);
    res = await fetch(`${base}/api/projects/${created.id}`);
    assert.equal(res.status, 404);
  });

  test('validation errors name each bad field', async () => {
    const res = await json('POST', '/api/projects', { name: 'A', status: 'finished', dueDate: '2030-02-31', extra: 1 });
    assert.equal(res.status, 422);
    const { error } = await res.json();
    const fields = error.details.map((d) => d.field).sort();
    assert.deepEqual(fields, ['dueDate', 'extra', 'name', 'status']);
  });

  test('rejects invalid JSON, wrong content type and oversized bodies', async () => {
    let res = await fetch(base + '/api/projects', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{bad' });
    assert.equal(res.status, 400);
    res = await fetch(base + '/api/projects', { method: 'POST', headers: { 'Content-Type': 'text/plain' }, body: 'hi' });
    assert.equal(res.status, 415);
    res = await json('POST', '/api/projects', { name: 'x'.repeat(200 * 1024) }).catch((e) => e);
    // Either the server answers 413 or it closes the connection before the upload ends.
    assert.ok(res instanceof Error || res.status === 413);
  });

  test('non-numeric id returns 400', async () => {
    const res = await fetch(base + '/api/projects/abc');
    assert.equal(res.status, 400);
  });

  test('list supports status filter, search and paging', async () => {
    for (const [name, status] of [['Alpha site', 'active'], ['Beta app', 'active'], ['Gamma audit', 'done']]) {
      await json('POST', '/api/projects', { name, status });
    }
    let body = await (await fetch(base + '/api/projects?status=done&limit=50')).json();
    assert.ok(body.items.every((p) => p.status === 'done'));
    body = await (await fetch(base + '/api/projects?q=beta')).json();
    assert.equal(body.items.length, 1);
    body = await (await fetch(base + '/api/projects?limit=1&page=2')).json();
    assert.equal(body.items.length, 1);
    assert.ok(body.total >= 3);
    const bad = await fetch(base + '/api/projects?status=nope');
    assert.equal(bad.status, 400);
  });
});

describe('tasks and summary', () => {
  test('task lifecycle and summary numbers', async () => {
    const project = await (await json('POST', '/api/projects', { name: 'Summary check' })).json();
    const pid = project.id;

    const t1 = await (await json('POST', `/api/projects/${pid}/tasks`, { title: 'Plan', status: 'done' })).json();
    await json('POST', `/api/projects/${pid}/tasks`, { title: 'Build', status: 'doing', dueDate: '2000-01-01' });
    await json('POST', `/api/projects/${pid}/tasks`, { title: 'Ship' });

    let summary = await (await fetch(`${base}/api/projects/${pid}/summary`)).json();
    assert.deepEqual(summary.counts, { todo: 1, doing: 1, done: 1 });
    assert.equal(summary.overdue, 1);
    assert.equal(summary.percentComplete, 33);

    const patch = await json('PATCH', `/api/projects/${pid}/tasks/${t1.id}`, { status: 'todo' });
    assert.equal(patch.status, 200);
    summary = await (await fetch(`${base}/api/projects/${pid}/summary`)).json();
    assert.equal(summary.percentComplete, 0);

    const del = await fetch(`${base}/api/projects/${pid}/tasks/${t1.id}`, { method: 'DELETE' });
    assert.equal(del.status, 204);
    const list = await (await fetch(`${base}/api/projects/${pid}/tasks`)).json();
    assert.equal(list.items.length, 2);
  });

  test('task on a missing project returns 404', async () => {
    const res = await json('POST', '/api/projects/999999/tasks', { title: 'Orphan' });
    assert.equal(res.status, 404);
  });

  test('empty PATCH is rejected', async () => {
    const project = await (await json('POST', '/api/projects', { name: 'Patch test' })).json();
    const res = await json('PATCH', `/api/projects/${project.id}`, {});
    assert.equal(res.status, 422);
  });
});

describe('persistence', () => {
  test('data survives a restart when a data file is used', async () => {
    const file = path.join(fs.mkdtempSync(path.join(os.tmpdir(), 'pt-')), 'db.json');
    const first = createApp({ dataFile: file });
    await new Promise((r) => first.server.listen(0, r));
    const url1 = `http://localhost:${first.server.address().port}`;
    await fetch(url1 + '/api/projects', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ name: 'Persisted' }) });
    await new Promise((r) => first.server.close(r));

    const second = createApp({ dataFile: file });
    await new Promise((r) => second.server.listen(0, r));
    const body = await (await fetch(`http://localhost:${second.server.address().port}/api/projects`)).json();
    await new Promise((r) => second.server.close(r));
    assert.equal(body.items[0].name, 'Persisted');
  });
});

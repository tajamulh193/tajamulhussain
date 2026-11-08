# Project Tracker API

A small REST API for projects and tasks, written in plain Node.js with **no dependencies**. Built as a work sample to show API design, validation, error handling and tests.

## Run it

```bash
node server.js          # http://localhost:3000, data saved to data/db.json
npm test                # 13 tests using the built-in node:test runner
```

Requires Node 18 or newer. Open `http://localhost:3000` for a small web page that uses the API.
A `Dockerfile` is included; set `PORT` and `DATA_FILE` to change the port and data location.

## Endpoints

| Method | Path | What it does |
| --- | --- | --- |
| GET | `/api/health` | Health check |
| GET | `/api/projects?status=&q=&page=&limit=` | List projects with filter, search and paging |
| POST | `/api/projects` | Create a project |
| GET / PATCH / DELETE | `/api/projects/:id` | Read, update or delete a project |
| GET | `/api/projects/:id/summary` | Task counts, overdue count and percent complete |
| GET / POST | `/api/projects/:id/tasks` | List or create tasks |
| PATCH / DELETE | `/api/projects/:id/tasks/:taskId` | Update or delete a task |

```bash
curl -X POST localhost:3000/api/projects \
  -H "Content-Type: application/json" \
  -d '{"name":"Website relaunch","client":"Acme","dueDate":"2030-01-31"}'
```

## Design notes

- **Validation:** every field is checked (type, length, allowed values, real calendar dates). Unknown fields are rejected. Errors return status `422` with one entry per bad field.
- **Consistent errors:** all failures are JSON, `{ "error": { "code", "message", "details" } }`, with the right status: 400, 404, 405, 413, 415, 422, 500.
- **Safety:** request bodies are limited to 100 KB, the content type must be JSON, static files cannot escape the `public` folder, and security headers are set.
- **Persistence:** data is kept in memory and saved to a JSON file after each change, written to a temporary file and renamed so a crash cannot corrupt it.
- **Shutdown:** the server finishes open requests when it receives SIGINT or SIGTERM.

## Tests

`test/api.test.js` starts the server on a random port and covers routing, CRUD, validation, paging, task summary maths, bad input and restart persistence.

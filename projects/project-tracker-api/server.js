'use strict';

const path = require('node:path');
const { createApp } = require('./src/app');

const port = Number(process.env.PORT) || 3000;
const dataFile = process.env.DATA_FILE || path.join(__dirname, 'data', 'db.json');

const { server } = createApp({ dataFile, log: true });
server.listen(port, () => console.log(`Project Tracker API listening on http://localhost:${port}`));

// Finish open requests before exiting when the host asks the process to stop.
for (const signal of ['SIGINT', 'SIGTERM']) {
  process.on(signal, () => {
    console.log(`${signal} received, shutting down.`);
    server.close(() => process.exit(0));
    setTimeout(() => process.exit(1), 5000).unref();
  });
}

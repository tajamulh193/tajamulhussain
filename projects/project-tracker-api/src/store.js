'use strict';

const fs = require('node:fs');
const path = require('node:path');

/**
 * Small data store. Everything lives in memory; if a file path is given the
 * data is loaded from it at start and written back after every change. The
 * write goes to a temporary file first and is then renamed, so a crash in the
 * middle of a save can never leave a half-written database.
 */
class Store {
  constructor(file) {
    this.file = file || null;
    this.data = { nextProjectId: 1, nextTaskId: 1, projects: [] };
    if (this.file && fs.existsSync(this.file)) {
      this.data = JSON.parse(fs.readFileSync(this.file, 'utf8'));
    }
  }

  save() {
    if (!this.file) return;
    fs.mkdirSync(path.dirname(this.file), { recursive: true });
    const tmp = this.file + '.tmp';
    fs.writeFileSync(tmp, JSON.stringify(this.data, null, 2));
    fs.renameSync(tmp, this.file);
  }

  listProjects({ status, q, page = 1, limit = 10 } = {}) {
    let items = this.data.projects;
    if (status) items = items.filter((p) => p.status === status);
    if (q) {
      const needle = q.toLowerCase();
      items = items.filter((p) => `${p.name} ${p.client || ''}`.toLowerCase().includes(needle));
    }
    const total = items.length;
    const start = (page - 1) * limit;
    return { items: items.slice(start, start + limit).map(stripTasks), total, page, limit };
  }

  getProject(id) {
    return this.data.projects.find((p) => p.id === id) || null;
  }

  createProject(fields) {
    const now = new Date().toISOString();
    const project = {
      id: this.data.nextProjectId++,
      name: fields.name,
      client: fields.client || null,
      status: fields.status || 'planned',
      dueDate: fields.dueDate || null,
      createdAt: now,
      updatedAt: now,
      tasks: [],
    };
    this.data.projects.push(project);
    this.save();
    return stripTasks(project);
  }

  updateProject(id, fields) {
    const project = this.getProject(id);
    if (!project) return null;
    Object.assign(project, fields, { updatedAt: new Date().toISOString() });
    this.save();
    return stripTasks(project);
  }

  deleteProject(id) {
    const i = this.data.projects.findIndex((p) => p.id === id);
    if (i === -1) return false;
    this.data.projects.splice(i, 1);
    this.save();
    return true;
  }

  createTask(projectId, fields) {
    const project = this.getProject(projectId);
    if (!project) return null;
    const task = {
      id: this.data.nextTaskId++,
      title: fields.title,
      assignee: fields.assignee || null,
      status: fields.status || 'todo',
      dueDate: fields.dueDate || null,
      createdAt: new Date().toISOString(),
    };
    project.tasks.push(task);
    project.updatedAt = task.createdAt;
    this.save();
    return task;
  }

  updateTask(projectId, taskId, fields) {
    const project = this.getProject(projectId);
    const task = project && project.tasks.find((t) => t.id === taskId);
    if (!task) return null;
    Object.assign(task, fields);
    project.updatedAt = new Date().toISOString();
    this.save();
    return task;
  }

  deleteTask(projectId, taskId) {
    const project = this.getProject(projectId);
    if (!project) return false;
    const i = project.tasks.findIndex((t) => t.id === taskId);
    if (i === -1) return false;
    project.tasks.splice(i, 1);
    this.save();
    return true;
  }

  summary(projectId, today = new Date().toISOString().slice(0, 10)) {
    const project = this.getProject(projectId);
    if (!project) return null;
    const counts = { todo: 0, doing: 0, done: 0 };
    let overdue = 0;
    for (const t of project.tasks) {
      counts[t.status] += 1;
      if (t.status !== 'done' && t.dueDate && t.dueDate < today) overdue += 1;
    }
    const total = project.tasks.length;
    return {
      projectId,
      total,
      counts,
      overdue,
      percentComplete: total ? Math.round((counts.done / total) * 100) : 0,
    };
  }
}

function stripTasks(project) {
  const { tasks, ...rest } = project;
  return { ...rest, taskCount: tasks.length };
}

module.exports = { Store };

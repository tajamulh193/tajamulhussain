'use strict';

const PROJECT_STATUS = ['planned', 'active', 'on-hold', 'done'];
const TASK_STATUS = ['todo', 'doing', 'done'];
const DATE_RE = /^\d{4}-\d{2}-\d{2}$/;

function isRealDate(value) {
  if (!DATE_RE.test(value)) return false;
  const d = new Date(value + 'T00:00:00Z');
  return !Number.isNaN(d.getTime()) && d.toISOString().slice(0, 10) === value;
}

function isPlainObject(v) {
  return v !== null && typeof v === 'object' && !Array.isArray(v);
}

/**
 * Validate a body against a field spec.
 * spec: { field: { type, required, min, max, oneOf, date } }
 * partial: true for PATCH (all fields optional, at least one required).
 * Returns { value, errors } where value contains only known, trimmed fields.
 */
function validate(body, spec, { partial = false } = {}) {
  const errors = [];
  const value = {};

  if (!isPlainObject(body)) {
    return { value, errors: [{ field: '(body)', message: 'Send a JSON object.' }] };
  }

  for (const key of Object.keys(body)) {
    if (!(key in spec)) errors.push({ field: key, message: 'Unknown field.' });
  }

  for (const [field, rule] of Object.entries(spec)) {
    let v = body[field];
    const missing = v === undefined || v === null || v === '';

    if (missing) {
      if (rule.required && !partial) errors.push({ field, message: 'This field is required.' });
      if (v === null && !rule.required && field in body) value[field] = null; // allow clearing optional fields
      continue;
    }

    if (typeof v !== 'string') {
      errors.push({ field, message: 'Must be a string.' });
      continue;
    }
    v = v.trim();

    if (rule.min && v.length < rule.min) errors.push({ field, message: `Must be at least ${rule.min} characters.` });
    else if (rule.max && v.length > rule.max) errors.push({ field, message: `Must be at most ${rule.max} characters.` });
    else if (rule.oneOf && !rule.oneOf.includes(v)) errors.push({ field, message: `Must be one of: ${rule.oneOf.join(', ')}.` });
    else if (rule.date && !isRealDate(v)) errors.push({ field, message: 'Must be a real date in YYYY-MM-DD format.' });
    else value[field] = v;
  }

  if (partial && errors.length === 0 && Object.keys(value).length === 0) {
    errors.push({ field: '(body)', message: 'Send at least one field to change.' });
  }
  return { value, errors };
}

const projectSpec = {
  name: { required: true, min: 2, max: 80 },
  client: { max: 80 },
  status: { oneOf: PROJECT_STATUS },
  dueDate: { date: true },
};

const taskSpec = {
  title: { required: true, min: 2, max: 120 },
  assignee: { max: 60 },
  status: { oneOf: TASK_STATUS },
  dueDate: { date: true },
};

module.exports = { validate, projectSpec, taskSpec, PROJECT_STATUS, TASK_STATUS };

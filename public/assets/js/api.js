/**
 * api.js — fetch wrapper that attaches the CSRF token and normalizes
 * JSON error handling for the admin/back-office UI.
 */

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export class ApiError extends Error {
  constructor(message, status, errors = {}) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
  }
}

export async function api(path, { method = 'GET', data = null, headers = {} } = {}) {
  const options = {
    method,
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': getCsrfToken(),
      ...headers,
    },
    credentials: 'same-origin',
  };

  if (data !== null) {
    options.headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(data);
  }

  const response = await fetch(path, options);
  const contentType = response.headers.get('content-type') ?? '';
  const payload = contentType.includes('application/json') ? await response.json() : await response.text();

  if (!response.ok) {
    const message = (payload && payload.message) || `Request failed with status ${response.status}`;
    throw new ApiError(message, response.status, (payload && payload.errors) || {});
  }

  return payload;
}

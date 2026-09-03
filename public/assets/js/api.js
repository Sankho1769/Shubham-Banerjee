/**
 * BOARDING · api.js
 * Thin wrapper around fetch() for calling /api/* endpoints.
 */

const Api = (() => {
  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  async function request(method, url, body = null) {
    const headers = { 'X-CSRF-Token': csrfToken() };
    const opts = { method, headers, credentials: 'same-origin' };

    if (body !== null) {
      headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }

    const res = await fetch(url, opts);
    let data;
    try {
      data = await res.json();
    } catch {
      data = { success: false, message: 'Unexpected server response.' };
    }
    return { status: res.status, ...data };
  }

  return {
    get: (url) => request('GET', url),
    post: (url, body) => request('POST', url, body),
  };
})();

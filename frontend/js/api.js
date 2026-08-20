/* =========================================================
   FRESH SCENTS — API Helper
   Badilisha API_BASE endapo backend haiko folda ile ile.
========================================================= */
const API_BASE = '../backend/api';

async function apiGet(endpoint, params = {}) {
  const url = new URL(`${API_BASE}/${endpoint}`, window.location.href);
  Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== '') url.searchParams.set(k, v); });
  const res = await fetch(url, { credentials: 'include' });
  return res.json();
}

async function apiPost(endpoint, data = {}, action = '') {
  const url = new URL(`${API_BASE}/${endpoint}`, window.location.href);
  if (action) url.searchParams.set('action', action);
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data),
  });
  return res.json();
}

function money(n) {
  return Number(n || 0).toLocaleString('en-US') + ' TZS';
}

function fdate(dt) {
  if (!dt) return '-';
  const d = new Date(dt.replace(' ', 'T'));
  return d.toLocaleDateString('sw-TZ', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' ' + d.toLocaleTimeString('sw-TZ', { hour: '2-digit', minute: '2-digit' });
}

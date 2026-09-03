/**
 * BOARDING · countdown.js
 * Client-side countdown math only. The server supplies target_datetime (UTC);
 * this file never asks PHP to recompute a countdown every second.
 */

const Countdown = (() => {
  const registry = new Map(); // element id -> { targetMs, onComplete, format }

  function diffParts(targetMs) {
    const now = Date.now();
    let deltaSeconds = Math.max(0, Math.floor((targetMs - now) / 1000));

    const days = Math.floor(deltaSeconds / 86400);
    deltaSeconds -= days * 86400;
    const hours = Math.floor(deltaSeconds / 3600);
    deltaSeconds -= hours * 3600;
    const minutes = Math.floor(deltaSeconds / 60);
    const seconds = deltaSeconds - minutes * 60;

    return { days, hours, minutes, seconds, completed: targetMs - now <= 0 };
  }

  function pad(n) {
    return String(n).padStart(2, '0');
  }

  function render(el, parts, format) {
    const units = [];
    if (format === 'dhms') units.push(['days', parts.days], ['hours', pad(parts.hours)], ['minutes', pad(parts.minutes)], ['seconds', pad(parts.seconds)]);
    else if (format === 'hm') units.push(['hours', parts.days * 24 + parts.hours], ['minutes', pad(parts.minutes)]);
    else if (format === 'm') units.push(['minutes', parts.days * 1440 + parts.hours * 60 + parts.minutes]);
    else units.push(['seconds', parts.days * 86400 + parts.hours * 3600 + parts.minutes * 60 + parts.seconds]);

    el.innerHTML = units.map(([label, value]) => `
      <div class="countdown__unit">
        <div class="countdown__value">${value}</div>
        <div class="countdown__label">${label}</div>
      </div>
    `).join('');
  }

  function tick() {
    for (const [id, entry] of registry) {
      const el = document.getElementById(id);
      if (!el) { registry.delete(id); continue; }

      const parts = diffParts(entry.targetMs);
      render(el, parts, entry.format);

      if (parts.completed && !entry.firedComplete) {
        entry.firedComplete = true;
        if (typeof entry.onComplete === 'function') entry.onComplete(el);
      }
    }
  }

  function mount(elementId, targetIso, format = 'dhms', onComplete = null) {
    const targetMs = new Date(targetIso).getTime();
    registry.set(elementId, { targetMs, format, onComplete, firedComplete: false });
    const el = document.getElementById(elementId);
    if (el) render(el, diffParts(targetMs), format);
  }

  function unmount(elementId) {
    registry.delete(elementId);
  }

  let intervalHandle = null;
  function start() {
    if (intervalHandle) return;
    intervalHandle = setInterval(tick, 1000);
  }

  start();

  return { mount, unmount, diffParts };
})();

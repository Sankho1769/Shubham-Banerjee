/**
 * BOARDING · reminders.js
 * Wires the dashboard's per-card action buttons to the API.
 */

document.addEventListener('click', async (event) => {
  const btn = event.target.closest('[data-action]');
  if (!btn) return;

  const action = btn.dataset.action;
  const id = btn.dataset.id;

  if (action === 'delete') {
    if (!confirm('Move this reminder to Trash?')) return;
    const res = await Api.post('/api/reminders/delete.php', { id });
    if (res.success) {
      btn.closest('.ticket-card').remove();
    } else {
      alert(res.message || 'Could not delete this reminder.');
    }
  }

  if (action === 'duplicate') {
    const res = await Api.post('/api/reminders/duplicate.php', { id });
    if (res.success) {
      window.location.reload();
    } else {
      alert(res.message || 'Could not duplicate this reminder.');
    }
  }

  if (action === 'edit') {
    alert('Edit modal — coming in the next build phase.');
  }
});

<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Helpers\Csrf;
use App\Middleware\AuthMiddleware;
use App\Repositories\UserRepository;
use App\Services\ReminderService;

$userId = AuthMiddleware::requireWeb();
$user = (new UserRepository())->findById($userId);
if ($user === null) {
    // Session pointed at a user that no longer exists.
    require __DIR__ . '/logout.php';
    exit;
}

$reminderService = new ReminderService();
$systemCountdown = $reminderService->systemCountdown();
$reminders = $reminderService->listForUser($userId, 1, 20, []);

$csrfToken = Csrf::token();

$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

function formatTarget(string $utcDatetime, string $timezone): string
{
    $dt = new DateTime($utcDatetime, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone($timezone));
    return $dt->format('d M Y, H:i');
}

function toIso(string $utcDatetime): string
{
    $dt = new DateTime($utcDatetime, new DateTimeZone('UTC'));
    return $dt->format(DateTime::ATOM);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
<title>Dashboard · BOARDING</title>
<link rel="stylesheet" href="/assets/css/boarding.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="brand"><span class="brand-dot"></span>BOARDING</div>
    <nav class="nav-group">
      <a class="nav-link active" href="/dashboard.php">Dashboard</a>
      <a class="nav-link" href="/calendar.php">Calendar</a>
      <a class="nav-link" href="/timeline.php">Timeline</a>
      <a class="nav-link" href="/templates.php">Templates</a>
      <a class="nav-link" href="/shared.php">Shared</a>
      <a class="nav-link" href="/settings.php">Settings</a>
    </nav>
    <div style="margin-top:auto; display:flex; flex-direction:column; gap:4px;">
      <a class="nav-link" href="/settings.php">Profile</a>
      <a class="nav-link" href="/logout.php">Logout</a>
    </div>
  </aside>

  <main class="main-content">
    <header style="margin-bottom: var(--space-6);">
      <div style="color:var(--text-secondary); font-size:0.9rem;"><?= htmlspecialchars($greeting) ?>, <?= htmlspecialchars($user['name']) ?></div>
      <h1 style="margin:4px 0 0; font-size:1.75rem;">Ready for your next departure?</h1>
    </header>

    <?php if ($systemCountdown): ?>
    <section class="ticket-card is-system" style="margin-bottom: var(--space-6);">
      <div class="ticket-card__notch top"></div>
      <div class="ticket-card__notch bottom"></div>
      <div class="ticket-card__stub-edge"></div>
      <div class="ticket-card__body">
        <div class="ticket-card__main">
          <div class="ticket-card__eyebrow">System · Permanent</div>
          <h2 class="ticket-card__title"><?= htmlspecialchars($systemCountdown['title']) ?></h2>
          <div class="ticket-card__meta"><?= htmlspecialchars(formatTarget($systemCountdown['target_datetime'], $systemCountdown['timezone'])) ?> (<?= htmlspecialchars($systemCountdown['timezone']) ?>)</div>
          <div class="countdown" id="cd-system"></div>
          <button class="btn btn--ghost" onclick="openFullscreen('<?= htmlspecialchars($systemCountdown['id']) ?>')">Fullscreen</button>
        </div>
        <div class="ticket-card__stub">BOARDING</div>
      </div>
    </section>
    <script>Countdown.mount('cd-system', <?= json_encode(toIso($systemCountdown['target_datetime'])) ?>, 'dhms');</script>
    <?php endif; ?>

    <h3 style="color:var(--text-secondary); font-size:0.9rem; letter-spacing:0.08em; text-transform:uppercase; margin-bottom: var(--space-4);">Upcoming</h3>

    <?php if (empty($reminders)): ?>
      <div class="empty-state">
        <div class="empty-state__title">YOUR JOURNEY STARTS HERE</div>
        <p>Create your first reminder and watch the countdown begin.</p>
        <button class="btn btn--primary" onclick="alert('Create reminder modal — coming in the next build phase.')">+ Create Reminder</button>
      </div>
    <?php else: ?>
      <div style="display:flex; flex-direction:column; gap: var(--space-4);">
        <?php foreach ($reminders as $r): ?>
        <div class="ticket-card">
          <div class="ticket-card__notch top"></div>
          <div class="ticket-card__notch bottom"></div>
          <div class="ticket-card__stub-edge"></div>
          <div class="ticket-card__body">
            <div class="ticket-card__main">
              <div class="ticket-card__eyebrow"><?= htmlspecialchars(ucfirst($r['priority'])) ?> priority</div>
              <h2 class="ticket-card__title"><?= htmlspecialchars($r['title']) ?></h2>
              <div class="ticket-card__meta"><?= htmlspecialchars(formatTarget($r['target_datetime'], $r['timezone'])) ?></div>
              <div class="countdown countdown--compact" id="cd-<?= htmlspecialchars($r['id']) ?>"></div>
              <div style="display:flex; gap:8px; margin-top:8px;">
                <button class="btn btn--ghost" data-action="edit" data-id="<?= htmlspecialchars($r['id']) ?>">Edit</button>
                <button class="btn btn--ghost" data-action="duplicate" data-id="<?= htmlspecialchars($r['id']) ?>">Duplicate</button>
                <button class="btn btn--danger" data-action="delete" data-id="<?= htmlspecialchars($r['id']) ?>">Delete</button>
              </div>
            </div>
            <div class="ticket-card__stub">BOARDING</div>
          </div>
        </div>
        <script>Countdown.mount('cd-<?= htmlspecialchars($r['id']) ?>', <?= json_encode(toIso($r['target_datetime'])) ?>, '<?= htmlspecialchars($r['display_format']) ?>');</script>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</div>

<script src="/assets/js/countdown.js"></script>
<script src="/assets/js/api.js"></script>
<script src="/assets/js/reminders.js"></script>
<script>
function openFullscreen(id) {
  window.location.href = '/fullscreen.php?id=' + encodeURIComponent(id);
}
</script>
</body>
</html>

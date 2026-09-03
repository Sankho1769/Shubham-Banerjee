<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Helpers\Csrf;
use App\Helpers\RateLimiter;
use App\Middleware\SessionMiddleware;
use App\Services\AuthService;

if (!empty($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif (!RateLimiter::attempt('register:ip:' . RateLimiter::clientIp(), 5, 3600)) {
        $errors[] = 'Too many attempts. Please try again later.';
    } else {
        $result = (new AuthService())->register(
            $_POST['name'] ?? '',
            $_POST['email'] ?? '',
            $_POST['password'] ?? '',
            $_POST['confirm_password'] ?? ''
        );

        if ($result['ok']) {
            $_SESSION['user_id'] = $result['user']['id'];
            SessionMiddleware::regenerate();
            header('Location: /dashboard.php');
            exit;
        }

        $errors[] = $result['message'];
    }
}

$csrfToken = Csrf::token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
<title>Create your account · BOARDING</title>
<link rel="stylesheet" href="/assets/css/boarding.css">
</head>
<body>
<div class="auth-shell">
  <div class="auth-card">
    <div class="auth-brand">
      <div class="auth-brand__mark">BOARDING</div>
      <div class="auth-brand__tagline">Your next departure.</div>
    </div>

    <?php foreach ($errors as $error): ?>
      <div class="form-error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <form method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

      <div class="field">
        <label for="name">Name</label>
        <input class="input" type="text" id="name" name="name" maxlength="120" required
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
      </div>

      <div class="field">
        <label for="email">Email</label>
        <input class="input" type="email" id="email" name="email" maxlength="190" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input class="input" type="password" id="password" name="password" minlength="10" required
               autocomplete="new-password">
      </div>

      <div class="field">
        <label for="confirm_password">Confirm password</label>
        <input class="input" type="password" id="confirm_password" name="confirm_password" minlength="10" required
               autocomplete="new-password">
      </div>

      <button class="btn btn--primary" type="submit" style="width:100%;">Create account</button>
    </form>

    <div class="auth-footer">
      Already boarding? <a href="/login.php">Sign in</a>
    </div>
  </div>
</div>
</body>
</html>

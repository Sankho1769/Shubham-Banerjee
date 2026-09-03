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
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif (
        !RateLimiter::attempt('login:ip:' . RateLimiter::clientIp(), 5, 60) ||
        !RateLimiter::attempt('login:email:' . $email, 5, 60)
    ) {
        $errors[] = 'Too many attempts. Please wait a minute and try again.';
    } else {
        $result = (new AuthService())->attempt($email, $_POST['password'] ?? '');

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
<title>Sign in · BOARDING</title>
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
        <label for="email">Email</label>
        <input class="input" type="email" id="email" name="email" required autofocus
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input class="input" type="password" id="password" name="password" required autocomplete="current-password">
      </div>

      <button class="btn btn--primary" type="submit" style="width:100%;">Sign in</button>
    </form>

    <div class="auth-footer">
      New here? <a href="/register.php">Create an account</a>
      &nbsp;·&nbsp;
      <a href="/forgot-password.php">Forgot password?</a>
    </div>
  </div>
</div>
</body>
</html>

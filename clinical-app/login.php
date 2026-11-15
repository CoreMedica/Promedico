<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

clinical_start_session();

if (clinical_is_logged_in()) {
    clinical_redirect('/clinical/dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clinical_verify_csrf_or_fail();

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (clinical_login($email, $password)) {
        clinical_rotate_csrf_token();

        clinical_redirect('/clinical/dashboard.php');
    }

    $error = 'Invalid email address or password.';
}

$reason = (string) ($_GET['reason'] ?? '');

header('X-Robots-Tag: noindex, nofollow, noarchive');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
?>
<!doctype html>
<html lang="en-GB">

<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Sign in | Promedico Clinical</title>

    <link rel="stylesheet" href="/clinical/assets/clinical.css">
</head>

<body>
    <main class="clinical-login">
        <section class="clinical-login-card" aria-labelledby="login-title">
            <div class="clinical-login-card__brand">
                <p class="clinical-eyebrow">Staff-only system</p>
                <h1 id="login-title" class="clinical-login-card__title">
                    Promedico Clinical Notes
                </h1>
                <p class="clinical-login-card__subtitle">
                    Sign in to access patient treatment notes and clinical history.
                </p>
            </div>

            <?php if ($reason === 'timeout'): ?>
                <div class="clinical-alert clinical-alert--warning" role="alert">
                    <p>Your session expired due to inactivity. Please sign in again.</p>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="clinical-alert clinical-alert--danger" role="alert">
                    <p><?= clinical_escape($error) ?></p>
                </div>
            <?php endif; ?>

            <form class="clinical-form" method="post" action="/clinical/login.php" novalidate>
                <?= clinical_csrf_field(); ?>

                <div class="clinical-form-field">
                    <label class="clinical-label" for="email">Email address</label>
                    <input
                        class="clinical-input"
                        type="email"
                        id="email"
                        name="email"
                        value="<?= clinical_escape($email) ?>"
                        autocomplete="username"
                        required>
                </div>

                <div class="clinical-form-field">
                    <label class="clinical-label" for="password">Password</label>
                    <input
                        class="clinical-input"
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required>
                </div>

                <div class="clinical-button-row">
                    <button class="clinical-button" type="submit">
                        Sign in
                    </button>
                </div>
            </form>
        </section>
    </main>
</body>

</html>
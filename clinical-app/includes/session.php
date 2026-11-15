<?php

declare(strict_types=1);

/**
 * Promedico Clinical App Session Handling
 * Path: public/clinical/includes/session.php
 *
 * Centralised secure session configuration for the clinical app.
 */

const CLINICAL_SESSION_TIMEOUT_SECONDS = 1800; // 30 minutes

function clinical_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }

    return false;
}

function clinical_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    $secure = clinical_is_https();

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/clinical',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_name('PROMEDICO_CLINICAL_SESSION');
    session_start();
}

function clinical_regenerate_session(): void
{
    clinical_start_session();
    session_regenerate_id(true);
}

function clinical_destroy_session(): void
{
    clinical_start_session();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}

function clinical_touch_session_activity(): void
{
    clinical_start_session();

    $_SESSION['last_activity'] = time();
}

function clinical_session_has_timed_out(): bool
{
    clinical_start_session();

    $lastActivity = $_SESSION['last_activity'] ?? null;

    if (!is_int($lastActivity)) {
        return false;
    }

    return (time() - $lastActivity) > CLINICAL_SESSION_TIMEOUT_SECONDS;
}

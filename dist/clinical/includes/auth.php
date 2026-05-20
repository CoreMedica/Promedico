<?php

declare(strict_types=1);

/**
 * Promedico Clinical App Authentication Helpers
 * Path: public/clinical/includes/auth.php
 *
 * Thin procedural wrapper around AuthService/session helpers.
 */

require_once __DIR__ . '/../app/bootstrap.php';

function clinical_auth(): AuthService
{
    return clinical_auth_service();
}

function clinical_is_logged_in(): bool
{
    return clinical_auth()->isLoggedIn();
}

function clinical_current_user(): ?array
{
    return clinical_auth()->currentUser();
}

function clinical_require_login(): void
{
    clinical_start_session();

    if (!clinical_is_logged_in()) {
        clinical_redirect_to_login();
    }

    if (clinical_session_has_timed_out()) {
        clinical_logout();
        clinical_redirect_to_login('timeout');
    }

    clinical_touch_session_activity();
}

function clinical_redirect_to_login(string $reason = ''): never
{
    $location = '/clinical/login.php';

    if ($reason !== '') {
        $location .= '?reason=' . urlencode($reason);
    }

    header('Location: ' . $location);
    exit;
}

function clinical_login(string $email, string $password): bool
{
    return clinical_auth()->login($email, $password);
}

function clinical_logout(): void
{
    clinical_auth()->logout();
}

function clinical_user_has_role(string $role): bool
{
    return clinical_auth()->hasRole($role);
}

function clinical_require_admin(): void
{
    clinical_require_login();

    if (!clinical_user_has_role('admin')) {
        http_response_code(403);
        exit('Access denied.');
    }
}

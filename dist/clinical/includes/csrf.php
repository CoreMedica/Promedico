<?php

declare(strict_types=1);

/**
 * Promedico Clinical App CSRF Protection
 * Path: public/clinical/includes/csrf.php
 */

require_once __DIR__ . '/session.php';

function clinical_csrf_token(): string
{
    clinical_start_session();

    if (
        empty($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token'])
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function clinical_csrf_field(): string
{
    $token = htmlspecialchars(clinical_csrf_token(), ENT_QUOTES, 'UTF-8');

    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function clinical_verify_csrf_token(?string $submittedToken): bool
{
    clinical_start_session();

    if (
        $submittedToken === null ||
        $submittedToken === '' ||
        empty($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token'])
    ) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $submittedToken);
}

function clinical_verify_csrf_or_fail(): void
{
    $submittedToken = isset($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : null;

    if (!clinical_verify_csrf_token($submittedToken)) {
        http_response_code(403);
        exit('Invalid security token. Please go back, refresh the page, and try again.');
    }
}

function clinical_rotate_csrf_token(): void
{
    clinical_start_session();

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

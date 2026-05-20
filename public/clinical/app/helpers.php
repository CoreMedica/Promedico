<?php

declare(strict_types=1);

/**
 * Promedico Clinical App Helpers
 * Path: public/clinical/app/helpers.php
 *
 * Shared presentation and utility helpers.
 * Keep these generic. Do not put database queries, auth logic,
 * or clinical workflow rules in this file.
 */

function clinical_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function clinical_display(?string $value, string $fallback = 'Not recorded'): string
{
    $value = trim((string) $value);

    if ($value === '') {
        return '<span class="clinical-muted">' . clinical_escape($fallback) . '</span>';
    }

    return clinical_escape($value);
}

function clinical_format_date(?string $date, string $fallback = 'Not recorded'): string
{
    if ($date === null || trim($date) === '') {
        return '<span class="clinical-muted">' . clinical_escape($fallback) . '</span>';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '<span class="clinical-muted">' . clinical_escape($fallback) . '</span>';
    }

    return clinical_escape(date('d/m/Y', $timestamp));
}

function clinical_format_datetime(?string $datetime, string $fallback = 'Not recorded'): string
{
    if ($datetime === null || trim($datetime) === '') {
        return '<span class="clinical-muted">' . clinical_escape($fallback) . '</span>';
    }

    $timestamp = strtotime($datetime);

    if ($timestamp === false) {
        return '<span class="clinical-muted">' . clinical_escape($fallback) . '</span>';
    }

    return clinical_escape(date('d/m/Y H:i', $timestamp));
}

function clinical_format_time(?string $time, string $fallback = 'Not recorded'): string
{
    if ($time === null || trim($time) === '') {
        return '<span class="clinical-muted">' . clinical_escape($fallback) . '</span>';
    }

    $timestamp = strtotime($time);

    if ($timestamp === false) {
        return '<span class="clinical-muted">' . clinical_escape($fallback) . '</span>';
    }

    return clinical_escape(date('H:i', $timestamp));
}

function clinical_label_from_enum(?string $value, string $fallback = 'Not recorded'): string
{
    $value = trim((string) $value);

    if ($value === '') {
        return $fallback;
    }

    return ucwords(str_replace('_', ' ', $value));
}

function clinical_display_enum(?string $value, string $fallback = 'Not recorded'): string
{
    $label = clinical_label_from_enum($value, $fallback);

    if ($label === $fallback) {
        return '<span class="clinical-muted">' . clinical_escape($fallback) . '</span>';
    }

    return clinical_escape($label);
}

function clinical_bool_label(bool|int|string|null $value): string
{
    return ((int) $value === 1) ? 'Yes' : 'No';
}

function clinical_bool_badge(bool|int|string|null $value): string
{
    if ((int) $value === 1) {
        return '<span class="clinical-badge clinical-badge--success">Yes</span>';
    }

    return '<span class="clinical-badge">No</span>';
}

function clinical_redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function clinical_current_url_without_query(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');

    $parts = explode('?', $uri, 2);

    return $parts[0] ?: '/clinical/dashboard.php';
}

function clinical_get_int(string $key): ?int
{
    $value = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);

    if ($value === false || $value === null) {
        return null;
    }

    return $value;
}

function clinical_post_string(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function clinical_nullable_string(string $value): ?string
{
    $value = trim($value);

    return $value === '' ? null : $value;
}

function clinical_normalise_email(string $email): ?string
{
    $email = strtolower(trim($email));

    return $email === '' ? null : $email;
}

function clinical_normalise_postcode(string $postcode): ?string
{
    $postcode = strtoupper(trim($postcode));
    $postcode = preg_replace('/\s+/', ' ', $postcode);

    return $postcode === '' ? null : $postcode;
}

function clinical_valid_date_or_null(string $date): ?string
{
    $date = trim($date);

    if ($date === '') {
        return null;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $date);

    if (!$dt || $dt->format('Y-m-d') !== $date) {
        return null;
    }

    return $date;
}

function clinical_active_nav(string $key, string $activeNav): string
{
    return $key === $activeNav ? ' is-active" aria-current="page' : '';
}

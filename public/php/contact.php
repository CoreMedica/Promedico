<?php

declare(strict_types=1);

/**
 * Promedico Wellness Group contact form handler.
 * Static Astro frontend posts here via /php/contact.php.
 *
 * Anti-spam phase 1:
 * - Honeypot field
 * - Minimum time-to-submit check
 * - IP-based rate limiting
 * - Basic content scoring
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /contact?status=invalid-method#contact-form');
    exit;
}

// ─────────────────────────────────────────────
// CONFIG
// ─────────────────────────────────────────────

$to = 'reception@promedico.co.uk';
$siteName = 'Promedico Wellness Group';
$subjectPrefix = 'Website enquiry';

$minimumSubmitSeconds = 5;
$rateLimitWindowSeconds = 15 * 60;
$rateLimitMaxInWindow = 3;
$rateLimitDaySeconds = 24 * 60 * 60;
$rateLimitMaxPerDay = 10;

// Assumes live structure:
// /php/contact.php
// /private/rate-limit-contact.json
$privateDir = __DIR__ . '/../../private';
$rateLimitFile = $privateDir . '/rate-limit-contact.json';

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────

function clean_text(string $value): string
{
    $value = trim($value);
    $value = strip_tags($value);
    return preg_replace('/[\r\n]+/', ' ', $value) ?? '';
}

function clean_multiline(string $value): string
{
    $value = trim($value);
    $value = strip_tags($value);
    return preg_replace("/\r\n|\r/", "\n", $value) ?? '';
}

function redirect_with_status(string $status): void
{
    header('Location: /contact?status=' . rawurlencode($status) . '#contact-form');
    exit;
}

function silent_discard(): void
{
    header('Location: /contact?status=success#contact-form');
    exit;
}

function get_client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function ensure_private_dir(string $privateDir): bool
{
    if (is_dir($privateDir)) {
        return true;
    }

    return mkdir($privateDir, 0750, true);
}

function csrf_token_valid(): bool
{
    $cookieToken = (string)($_COOKIE['csrf_token'] ?? '');
    $postToken = (string)($_POST['csrf_token'] ?? '');

    if ($cookieToken === '' || $postToken === '' || !hash_equals($cookieToken, $postToken)) {
        return false;
    }

    return true;
}

function rate_limit_exceeded(
    string $file,
    string $ip,
    int $windowSeconds,
    int $maxInWindow,
    int $daySeconds,
    int $maxPerDay
): bool {
    $now = time();
    $ipHash = hash('sha256', $ip);

    $handle = fopen($file, 'c+');

    if ($handle === false) {
        // Fail open rather than blocking genuine patients if the file cannot be opened.
        return false;
    }

    flock($handle, LOCK_EX);

    $contents = stream_get_contents($handle);
    $records = [];

    if (is_string($contents) && trim($contents) !== '') {
        $decoded = json_decode($contents, true);
        if (is_array($decoded)) {
            $records = $decoded;
        }
    }

    // Remove stale entries older than one day.
    foreach ($records as $hash => $timestamps) {
        if (!is_array($timestamps)) {
            unset($records[$hash]);
            continue;
        }

        $records[$hash] = array_values(array_filter(
            $timestamps,
            static fn($timestamp) => is_int($timestamp) && $timestamp >= ($now - $daySeconds)
        ));

        if ($records[$hash] === []) {
            unset($records[$hash]);
        }
    }

    $records[$ipHash] = $records[$ipHash] ?? [];
    $recentWindow = array_filter(
        $records[$ipHash],
        static fn($timestamp) => $timestamp >= ($now - $windowSeconds)
    );

    $recentDay = array_filter(
        $records[$ipHash],
        static fn($timestamp) => $timestamp >= ($now - $daySeconds)
    );

    $exceeded = count($recentWindow) >= $maxInWindow || count($recentDay) >= $maxPerDay;

    if (!$exceeded) {
        $records[$ipHash][] = $now;
    }

    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($records, JSON_PRETTY_PRINT));
    fflush($handle);

    flock($handle, LOCK_UN);
    fclose($handle);

    return $exceeded;
}

function spam_score(string $name, string $email, string $phone, string $message): int
{
    $score = 0;
    $combined = $name . ' ' . $email . ' ' . $phone . ' ' . $message;

    if (preg_match_all('/https?:\/\//i', $combined) >= 1) {
        $score += 3;
    }

    if (preg_match_all('/www\./i', $combined) >= 1) {
        $score += 2;
    }

    if (preg_match('/casino|crypto|bitcoin|loan|viagra|cialis|seo|backlink|guest post|rank higher|web design|marketing agency|forex|investment/i', $combined)) {
        $score += 3;
    }

    if (preg_match('/<a\s|<\/a>|<script|<\/script>/i', $combined)) {
        $score += 4;
    }

    if (mb_strlen($message) < 10) {
        $score += 1;
    }

    if (preg_match('/(.)\1{8,}/u', $combined)) {
        $score += 2;
    }

    if (preg_match('/[\p{Cyrillic}\p{Han}]/u', $combined)) {
        $score += 2;
    }

    if (substr_count(strtolower($combined), '@') > 2) {
        $score += 1;
    }

    return $score;
}


// ─────────────────────────────────────────────
// INPUTS
// ─────────────────────────────────────────────

$name = clean_text((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = clean_text((string)($_POST['phone'] ?? ''));
$service = clean_text((string)($_POST['service'] ?? ''));
$message = clean_multiline((string)($_POST['message'] ?? ''));
$consent = clean_text((string)($_POST['consent'] ?? ''));
$formName = clean_text((string)($_POST['form_name'] ?? ''));

$website = clean_text((string)($_POST['website'] ?? ''));
$formLoadedAt = (int)($_POST['form_loaded_at'] ?? 0);

if (!csrf_token_valid()) {
    redirect_with_status('invalid-form');
}
// ─────────────────────────────────────────────
// SILENT ANTI-SPAM CHECKS
// ─────────────────────────────────────────────

if ($website !== '') {
    silent_discard();
}

$now = time();

if ($formLoadedAt <= 0 || ($now - $formLoadedAt) < $minimumSubmitSeconds) {
    silent_discard();
}

if (!ensure_private_dir($privateDir)) {
    // Do not block genuine form users if rate-limit storage cannot be created.
    // The rest of the checks still apply.
} else {
    $ip = get_client_ip();

    if (rate_limit_exceeded(
        $rateLimitFile,
        $ip,
        $rateLimitWindowSeconds,
        $rateLimitMaxInWindow,
        $rateLimitDaySeconds,
        $rateLimitMaxPerDay
    )) {
        silent_discard();
    }
}

if (spam_score($name, $email, $phone, $message) >= 4) {
    silent_discard();
}

// ─────────────────────────────────────────────
// VALIDATION
// ─────────────────────────────────────────────

if ($formName !== 'contact') {
    redirect_with_status('invalid-form');
}

if ($name === '' || $email === '' || $message === '' || $consent !== 'yes') {
    redirect_with_status('missing-fields');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_status('invalid-email');
}

if (mb_strlen($name) > 120 || mb_strlen($email) > 180 || mb_strlen($phone) > 40) {
    redirect_with_status('invalid-length');
}

if (mb_strlen($message) > 3000) {
    redirect_with_status('message-too-long');
}

$allowedServices = [
    'portsmouth-clinic',
    'portsmouth-home',
    'southampton-clinic',
    'southampton-home',
    'not-sure',
    ''
];

if (!in_array($service, $allowedServices, true)) {
    redirect_with_status('invalid-service');
}

// ─────────────────────────────────────────────
// EMAIL BODY
// ─────────────────────────────────────────────

$serviceLabel = match ($service) {
    'portsmouth-clinic' => 'Portsmouth clinic appointment',
    'portsmouth-home' => 'Portsmouth home visit',
    'southampton-clinic' => 'Southampton clinic appointment',
    'southampton-home' => 'Southampton home visit',
    'not-sure' => 'Not sure',
    default => 'Not selected',
};

$emailSubject = $subjectPrefix . ' from ' . $name;

$body = <<<EOT
New website enquiry from {$siteName}

Name:
{$name}

Email:
{$email}

Phone:
{$phone}

Service area:
{$serviceLabel}

Message:
{$message}

Consent:
{$consent}

Submitted from:
{$_SERVER['HTTP_HOST']}

IP address:
{$_SERVER['REMOTE_ADDR']}
EOT;

$host = $_SERVER['HTTP_HOST'] ?? 'promedico.co.uk';
$host = preg_replace('/[^a-zA-Z0-9.-]/', '', $host) ?: 'promedico.co.uk';

$headers = [
    'From: ' . $siteName . ' <no-reply@' . $host . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . phpversion(),
];

// ─────────────────────────────────────────────
// SEND
// ─────────────────────────────────────────────

$sent = mail($to, $emailSubject, $body, implode("\r\n", $headers));

if (!$sent) {
    redirect_with_status('send-failed');
}

redirect_with_status('success');

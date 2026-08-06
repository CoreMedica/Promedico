<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /contact?status=invalid-method#contact-form');
    exit;
}

function clean_input(string $value): string
{
    return trim(str_replace(["\r", "\n"], ' ', strip_tags($value)));
}

function clean_multiline(string $value): string
{
    $value = trim(strip_tags($value));
    return preg_replace("/\r\n|\r/", "\n", $value) ?? '';
}

function redirect_with_status(string $status): void
{
    header('Location: /contact?status=' . rawurlencode($status) . '#contact-form');
    exit;
}

function silent_discard(): void
{
    redirect_with_status('success');
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

function spam_score(string $name, string $email, string $phone, string $postcode, string $message): int
{
    $score = 0;
    $combined = implode(' ', [$name, $email, $phone, $postcode, $message]);

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

    if ($message !== '' && mb_strlen($message) < 10) {
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

$minimumSubmitSeconds = 5;
$rateLimitWindowSeconds = 15 * 60;
$rateLimitMaxInWindow = 3;
$rateLimitDaySeconds = 24 * 60 * 60;
$rateLimitMaxPerDay = 10;
$privateDir = __DIR__ . '/../../private';
$rateLimitFile = $privateDir . '/rate-limit-home-visit.json';

$formName = $_POST['form_name'] ?? '';

if ($formName !== 'home_visit_request') {
    redirect_with_status('invalid-form');
}

if (!csrf_token_valid()) {
    redirect_with_status('invalid-form');
}

$name = clean_input((string)($_POST['name'] ?? ''));
$email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
$phone = clean_input((string)($_POST['phone'] ?? ''));
$area = clean_input((string)($_POST['area'] ?? ''));
$postcode = clean_input((string)($_POST['postcode'] ?? ''));
$message = clean_multiline((string)($_POST['message'] ?? ''));
$consent = (string)($_POST['consent'] ?? '');
$website = clean_input((string)($_POST['website'] ?? ''));
$formLoadedAt = (int)($_POST['form_loaded_at'] ?? 0);

$allowedAreas = ['portsmouth', 'southampton'];

if ($website !== '') {
    silent_discard();
}

$now = time();

if ($formLoadedAt <= 0 || ($now - $formLoadedAt) < $minimumSubmitSeconds) {
    silent_discard();
}

if (ensure_private_dir($privateDir)) {
    if (rate_limit_exceeded(
        $rateLimitFile,
        get_client_ip(),
        $rateLimitWindowSeconds,
        $rateLimitMaxInWindow,
        $rateLimitDaySeconds,
        $rateLimitMaxPerDay
    )) {
        silent_discard();
    }
}

if (spam_score($name, $email, $phone, $postcode, $message) >= 4) {
    silent_discard();
}

if (
    $name === '' ||
    $email === '' ||
    $phone === '' ||
    $area === '' ||
    $postcode === '' ||
    $consent !== 'yes'
) {
    redirect_with_status('missing-fields');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_status('invalid-email');
}

if (!in_array($area, $allowedAreas, true)) {
    redirect_with_status('invalid-service');
}

if (
    mb_strlen($name) > 120 ||
    mb_strlen($email) > 180 ||
    mb_strlen($phone) > 40 ||
    mb_strlen($postcode) > 20 ||
    mb_strlen($message) > 2000
) {
    redirect_with_status('invalid-length');
}

$areaLabel = match ($area) {
    'portsmouth' => 'Portsmouth home visit',
    'southampton' => 'Southampton home visit',
    default => 'Not selected',
};

$to = 'reception@promedico.co.uk';
$cc = 'promedicoltd@outlook.com';
$subject = 'New home visit request - Promedico Wellness Group';

$body = "New home visit request\n\n";
$body .= "Name: {$name}\n";
$body .= "Email: {$email}\n";
$body .= "Phone: {$phone}\n";
$body .= "Area: {$areaLabel}\n";
$body .= "Postcode: {$postcode}\n\n";
$body .= "Message:\n{$message}\n\n";
$body .= "Consent: {$consent}\n";
$body .= "Submitted from: {$_SERVER['HTTP_HOST']}\n";
$body .= "IP address: {$_SERVER['REMOTE_ADDR']}\n";

$headers = [];
$headers[] = 'From: Promedico Website <no-reply@promedico.co.uk>';
$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
$headers[] = 'Cc: ' . $cc;
$headers[] = 'Content-Type: text/plain; charset=UTF-8';

$sent = mail($to, $subject, $body, implode("\r\n", $headers));

if (!$sent) {
    redirect_with_status('send-failed');
}

redirect_with_status('success');

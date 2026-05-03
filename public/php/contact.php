<?php

declare(strict_types=1);

/**
 * Promedico Wellness Group contact form handler.
 * Static Astro frontend posts here via /php/contact.php.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /contact?status=invalid-method');
    exit;
}

// ─────────────────────────────────────────────
// CONFIG
// ─────────────────────────────────────────────

$to = 'reception@coremedica.co.uk'; // TODO: replace with final Promedico mailbox if different
$siteName = 'Promedico Wellness Group';
$subjectPrefix = 'Website enquiry';

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

// ─────────────────────────────────────────────
// INPUTS
// ─────────────────────────────────────────────

$name = clean_text($_POST['name'] ?? '');
$email = trim((string)($_POST['email'] ?? ''));
$phone = clean_text($_POST['phone'] ?? '');
$service = clean_text($_POST['service'] ?? '');
$message = clean_multiline($_POST['message'] ?? '');
$consent = clean_text($_POST['consent'] ?? '');
$formName = clean_text($_POST['form_name'] ?? '');

// Basic honeypot support if added later
$website = clean_text($_POST['website'] ?? '');

if ($website !== '') {
    redirect_with_status('success');
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

$allowedServices = ['', 'portsmouth', 'southampton', 'not-sure'];

if (!in_array($service, $allowedServices, true)) {
    redirect_with_status('invalid-service');
}

// ─────────────────────────────────────────────
// EMAIL BODY
// ─────────────────────────────────────────────

$serviceLabel = match ($service) {
    'portsmouth' => 'Portsmouth',
    'southampton' => 'Southampton',
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

$headers = [
    'From: ' . $siteName . ' <no-reply@' . $_SERVER['HTTP_HOST'] . '>',
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

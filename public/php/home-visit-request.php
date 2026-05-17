<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /contact?status=invalid-method');
    exit;
}

function clean_input(string $value): string
{
    return trim(str_replace(["\r", "\n"], ' ', strip_tags($value)));
}

$formName = $_POST['form_name'] ?? '';

if ($formName !== 'home_visit_request') {
    header('Location: /contact?status=invalid-form');
    exit;
}

$name = clean_input((string)($_POST['name'] ?? ''));
$email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
$phone = clean_input((string)($_POST['phone'] ?? ''));
$area = clean_input((string)($_POST['area'] ?? ''));
$postcode = clean_input((string)($_POST['postcode'] ?? ''));
$message = trim(strip_tags((string)($_POST['message'] ?? '')));
$consent = (string)($_POST['consent'] ?? '');

$allowedAreas = ['portsmouth', 'southampton'];

if (
    $name === '' ||
    $email === '' ||
    $phone === '' ||
    $area === '' ||
    $postcode === '' ||
    $consent !== 'yes'
) {
    header('Location: /contact?status=missing-fields');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /contact?status=invalid-email');
    exit;
}

if (!in_array($area, $allowedAreas, true)) {
    header('Location: /contact?status=invalid-service');
    exit;
}

if (
    mb_strlen($name) > 120 ||
    mb_strlen($phone) > 40 ||
    mb_strlen($postcode) > 20 ||
    mb_strlen($message) > 2000
) {
    header('Location: /contact?status=invalid-length');
    exit;
}

$to = 'YOUR_EMAIL_ADDRESS_HERE';
$subject = 'New home visit request - Promedico Wellness Group';

$body = "New home visit request\n\n";
$body .= "Name: {$name}\n";
$body .= "Email: {$email}\n";
$body .= "Phone: {$phone}\n";
$body .= "Area: {$area}\n";
$body .= "Postcode: {$postcode}\n\n";
$body .= "Message:\n{$message}\n\n";
$body .= "Consent: {$consent}\n";

$headers = [];
$headers[] = 'From: Promedico Website <no-reply@promedico.co.uk>';
$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';

$sent = mail($to, $subject, $body, implode("\r\n", $headers));

if (!$sent) {
    header('Location: /contact?status=send-failed');
    exit;
}

header('Location: /contact?status=success');
exit;

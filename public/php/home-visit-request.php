<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$postcode = trim($_POST['postcode'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $phone === '') {
    http_response_code(400);
    echo 'Missing required fields.';
    exit;
}

$to = 'david.cadwallader@outlook.com';
$cc = 'jacquelinemallen@hotmail.com';
$subject = 'Home Visit Request';
$body = "Name: $name\nEmail: $email\nPhone: $phone\nPostcode: $postcode\nMessage: $message\n";
$headers = "From: noreply@promedico.co.uk\r\nReply-To: $email\r\n";
$headers .= "Cc: $cc\r\n";

mail($to, $subject, $body, $headers);

header('Location: /thank-you');
exit;

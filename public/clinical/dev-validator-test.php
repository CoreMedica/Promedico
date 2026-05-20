<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$validator = clinical_patient_validator();

$errors = $validator->validate([
    'first_name' => '',
    'last_name' => '',
    'date_of_birth' => 'not-a-date',
    'email' => 'bad-email',
]);

echo '<pre>';
print_r($errors);
echo '</pre>';

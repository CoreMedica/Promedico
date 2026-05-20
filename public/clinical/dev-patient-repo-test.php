<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

clinical_require_login();

$repo = clinical_patient_repository();

echo '<pre>';

echo "Active patients:\n";
print_r($repo->searchActivePatients('', 10));

echo "\nTotal active patients:\n";
print_r($repo->countActivePatients());

echo '</pre>';

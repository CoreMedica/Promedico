<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

clinical_require_login();

$service = clinical_patient_service();

echo '<pre>';

echo "Empty form:\n";
print_r($service->emptyForm());

echo "\nActive patients:\n";
print_r($service->searchPatients('', 10));

echo "\nActive patient count:\n";
print_r($service->countActivePatients());

echo '</pre>';

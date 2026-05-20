<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

clinical_require_login();

$currentUser = clinical_current_user();

if ($currentUser === null) {
    clinical_redirect_to_login();
}

$auditService = clinical_audit_service();

$auditService->record(
    userId: (int) $currentUser['id'],
    action: 'dev_audit_test',
    entityType: 'system',
    entityId: null
);

echo 'Audit test recorded.';

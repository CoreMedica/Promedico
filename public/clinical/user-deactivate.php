<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

clinical_require_login();
clinical_require_admin(); // Only admins can deactivate users

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

clinical_verify_csrf_or_fail();

$currentUser = clinical_current_user();

if ($currentUser === null) {
    clinical_redirect_to_login();
}

$userId = clinical_get_int('id');

if ($userId === null || $userId < 1) {
    http_response_code(400);
    exit('Invalid user ID.');
}

// Prevent users from deactivating themselves
if ((int) $userId === (int) $currentUser['id']) {
    http_response_code(400);
    exit('You cannot deactivate your own account.');
}

$userService = clinical_user_service();

$deactivated = $userService->deactivateUser(
    userId: $userId,
    currentUserId: (int) $currentUser['id']
);

if (!$deactivated) {
    http_response_code(404);
    exit('User not found or already inactive.');
}

clinical_rotate_csrf_token();

clinical_redirect('/clinical/user-view.php?id=' . $userId . '&deactivated=1');

<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

clinical_require_login();
clinical_require_admin(); // Only admins can view users

$pageTitle = 'View User';
$activeNav = 'users';

$currentUser = clinical_current_user();

if ($currentUser === null) {
    clinical_redirect_to_login();
}

$userId = clinical_get_int('id');

if ($userId === null || $userId < 1) {
    http_response_code(400);
    exit('Invalid user ID.');
}

$userService = clinical_user_service();

$user = $userService->getUserForView(
    userId: $userId,
    currentUserId: (int) $currentUser['id']
);

if ($user === null) {
    http_response_code(404);
    exit('User not found.');
}

$showCreatedMessage = isset($_GET['created']) && $_GET['created'] === '1';
$showUpdatedMessage = isset($_GET['updated']) && $_GET['updated'] === '1';
$showDeactivatedMessage = isset($_GET['deactivated']) && $_GET['deactivated'] === '1';

require_once __DIR__ . '/includes/header.php';
?>

<div class="clinical-container clinical-stack clinical-stack--xl">
    <section class="clinical-page-header">
        <div class="clinical-page-header__content">
            <p class="clinical-eyebrow">User management</p>
            <h1 class="clinical-title"><?= clinical_escape($user['name']) ?></h1>
            <p class="clinical-subtitle">
                View user account details and access history.
            </p>
        </div>

        <div class="clinical-button-row">
            <a class="clinical-button" href="/clinical/user-edit.php?id=<?= (int) $userId ?>">
                Edit user
            </a>

            <a class="clinical-button clinical-button--secondary" href="/clinical/users.php">
                Back to users
            </a>
        </div>
    </section>

    <?php if ($showCreatedMessage): ?>
        <div class="clinical-alert clinical-alert--success" role="status">
            <p><strong>User created successfully.</strong></p>
        </div>
    <?php endif; ?>

    <?php if ($showUpdatedMessage): ?>
        <div class="clinical-alert clinical-alert--success" role="status">
            <p><strong>User updated successfully.</strong></p>
        </div>
    <?php endif; ?>

    <?php if ($showDeactivatedMessage): ?>
        <div class="clinical-alert clinical-alert--warning" role="status">
            <p><strong>User deactivated successfully.</strong></p>
        </div>
    <?php endif; ?>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">User details</h2>
        </div>

        <div class="clinical-card__body">
            <div class="clinical-detail-grid">
                <div class="clinical-detail-field">
                    <dt class="clinical-detail-label">Name</dt>
                    <dd class="clinical-detail-value">
                        <?= clinical_escape($user['name']) ?>
                    </dd>
                </div>

                <div class="clinical-detail-field">
                    <dt class="clinical-detail-label">Email</dt>
                    <dd class="clinical-detail-value">
                        <?= clinical_escape($user['email']) ?>
                    </dd>
                </div>

                <div class="clinical-detail-field">
                    <dt class="clinical-detail-label">Role</dt>
                    <dd class="clinical-detail-value">
                        <?= clinical_display_enum($user['role']) ?>
                    </dd>
                </div>

                <div class="clinical-detail-field">
                    <dt class="clinical-detail-label">Status</dt>
                    <dd class="clinical-detail-value">
                        <?php if ((int) $user['is_active'] === 1): ?>
                            <span class="clinical-badge clinical-badge--success">Active</span>
                        <?php else: ?>
                            <span class="clinical-badge clinical-badge--danger">Inactive</span>
                        <?php endif; ?>
                    </dd>
                </div>

                <div class="clinical-detail-field">
                    <dt class="clinical-detail-label">Last login</dt>
                    <dd class="clinical-detail-value">
                        <?= clinical_format_datetime($user['last_login_at']) ?>
                    </dd>
                </div>

                <div class="clinical-detail-field">
                    <dt class="clinical-detail-label">Created</dt>
                    <dd class="clinical-detail-value">
                        <?= clinical_format_datetime($user['created_at']) ?>
                        <?php if (!empty($user['created_by_name'])): ?>
                            by <?= clinical_escape($user['created_by_name']) ?>
                        <?php endif; ?>
                    </dd>
                </div>

                <div class="clinical-detail-field">
                    <dt class="clinical-detail-label">Last updated</dt>
                    <dd class="clinical-detail-value">
                        <?= clinical_format_datetime($user['updated_at']) ?>
                        <?php if (!empty($user['updated_by_name'])): ?>
                            by <?= clinical_escape($user['updated_by_name']) ?>
                        <?php endif; ?>
                    </dd>
                </div>

                <div class="clinical-detail-field">
                    <dt class="clinical-detail-label">User ID</dt>
                    <dd class="clinical-detail-value">
                        <?= clinical_escape((string) $user['id']) ?>
                    </dd>

                </div>
            </div>
        </div>
    </section>

    <?php if ((int) $user['is_active'] === 1): ?>
        <section class="clinical-card">
            <div class="clinical-card__header">
                <h2 class="clinical-card__title">Deactivate user</h2>
            </div>

            <div class="clinical-card__body">
                <p>
                    Deactivating this user will prevent them from signing in to the clinical system.
                    This action can be reversed by editing the user and setting them to active.
                </p>

                <form method="post" action="/clinical/user-deactivate.php" style="margin-top: 1rem;">
                    <?= clinical_csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $userId ?>">

                    <div class="clinical-button-row">
                        <button
                            class="clinical-button clinical-button--danger"
                            type="submit"
                            onclick="return confirm('Are you sure you want to deactivate this user?')">
                            Deactivate user
                        </button>
                    </div>
                </form>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
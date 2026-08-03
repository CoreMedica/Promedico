<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

clinical_require_login();
clinical_require_admin(); // Only admins can manage users

$pageTitle = 'Users';
$activeNav = 'users';

$userService = clinical_user_service();

$showInactive = isset($_GET['show_inactive']) && $_GET['show_inactive'] === '1';

$users = $userService->listUsers(activeOnly: !$showInactive);

require_once __DIR__ . '/includes/header.php';
?>

<div class="clinical-container clinical-stack clinical-stack--xl">
    <section class="clinical-page-header">
        <div class="clinical-page-header__content">
            <p class="clinical-eyebrow">User management</p>
            <h1 class="clinical-title">Users</h1>
            <p class="clinical-subtitle">
                Manage system users and their access levels. Only active users can sign in to the clinical system.
            </p>
        </div>

        <div class="clinical-button-row">
            <a class="clinical-button" href="/clinical/user-new.php">Add user</a>
        </div>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">User list</h2>
            <p class="clinical-card__subtitle">
                <?= $showInactive ? 'Showing all users (active and inactive).' : 'Showing active users only.' ?>
            </p>
        </div>

        <div class="clinical-card__body">
            <div class="clinical-button-row" style="margin-bottom: 1.5rem;">
                <?php if ($showInactive): ?>
                    <a class="clinical-button clinical-button--secondary" href="/clinical/users.php">
                        Show active only
                    </a>
                <?php else: ?>
                    <a class="clinical-button clinical-button--secondary" href="/clinical/users.php?show_inactive=1">
                        Show all users
                    </a>
                <?php endif; ?>
            </div>

            <?php if (count($users) === 0): ?>
                <div class="clinical-empty">
                    <h3 class="clinical-empty__title">No users found</h3>
                    <p class="clinical-empty__text">
                        There are no <?= $showInactive ? '' : 'active ' ?>users in the system.
                    </p>
                </div>
            <?php else: ?>
                <div class="clinical-table-wrap">
                    <table class="clinical-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last login</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?= clinical_escape($user['name']) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= clinical_escape($user['email']) ?>
                                    </td>

                                    <td>
                                        <?= clinical_display_enum($user['role']) ?>
                                    </td>

                                    <td>
                                        <?php if ((int) $user['is_active'] === 1): ?>
                                            <span class="clinical-badge clinical-badge--success">Active</span>
                                        <?php else: ?>
                                            <span class="clinical-badge clinical-badge--danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= clinical_format_datetime($user['last_login_at']) ?>
                                    </td>

                                    <td>
                                        <?= clinical_format_date($user['created_at']) ?>
                                    </td>

                                    <td>
                                        <div class="clinical-table__actions">
                                            <a
                                                class="clinical-button clinical-button--small"
                                                href="/clinical/user-view.php?id=<?= (int) $user['id'] ?>">
                                                View
                                            </a>

                                            <a
                                                class="clinical-button clinical-button--secondary clinical-button--small"
                                                href="/clinical/user-edit.php?id=<?= (int) $user['id'] ?>">
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
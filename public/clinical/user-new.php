<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

clinical_require_login();
clinical_require_admin(); // Only admins can create users

$pageTitle = 'New User';
$activeNav = 'users';

$currentUser = clinical_current_user();

if ($currentUser === null) {
    clinical_redirect_to_login();
}

$userService = clinical_user_service();

$errors = [];
$form = $userService->emptyForm();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clinical_verify_csrf_or_fail();

    $result = $userService->createUser(
        input: $_POST,
        currentUserId: (int) $currentUser['id']
    );

    if ($result['success'] === true) {
        clinical_rotate_csrf_token();

        clinical_redirect('/clinical/user-view.php?id=' . (int) $result['user_id'] . '&created=1');
    }

    $errors = $result['errors'];
    $form = $result['form'];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="clinical-container clinical-stack clinical-stack--xl">
    <section class="clinical-page-header">
        <div class="clinical-page-header__content">
            <p class="clinical-eyebrow">User management</p>
            <h1 class="clinical-title">New user</h1>
            <p class="clinical-subtitle">
                Create a new user account for the clinical system. Users must be activated to sign in.
            </p>
        </div>

        <div class="clinical-button-row">
            <a class="clinical-button clinical-button--secondary" href="/clinical/users.php">
                Back to users
            </a>
        </div>
    </section>

    <?php if (count($errors) > 0): ?>
        <div class="clinical-alert clinical-alert--danger" role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?= clinical_escape($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">User details</h2>
        </div>

        <div class="clinical-card__body">
            <form class="clinical-form" method="post" action="/clinical/user-new.php">
                <?= clinical_csrf_field() ?>

                <div class="clinical-form-grid">
                    <div class="clinical-form-field">
                        <label class="clinical-label" for="name">
                            Full name <abbr title="Required">*</abbr>
                        </label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="name"
                            name="name"
                            value="<?= clinical_escape($form['name']) ?>"
                            required>
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="email">
                            Email address <abbr title="Required">*</abbr>
                        </label>
                        <input
                            class="clinical-input"
                            type="email"
                            id="email"
                            name="email"
                            value="<?= clinical_escape($form['email']) ?>"
                            required>
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="role">
                            Role <abbr title="Required">*</abbr>
                        </label>
                        <select
                            class="clinical-input"
                            id="role"
                            name="role"
                            required>
                            <option value="clinician" <?= $form['role'] === 'clinician' ? 'selected' : '' ?>>
                                Clinician
                            </option>
                            <option value="admin" <?= $form['role'] === 'admin' ? 'selected' : '' ?>>
                                Admin
                            </option>
                        </select>
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="is_active">
                            Active status <abbr title="Required">*</abbr>
                        </label>
                        <select
                            class="clinical-input"
                            id="is_active"
                            name="is_active"
                            required>
                            <option value="1" <?= $form['is_active'] === '1' ? 'selected' : '' ?>>
                                Active (can sign in)
                            </option>
                            <option value="0" <?= $form['is_active'] === '0' ? 'selected' : '' ?>>
                                Inactive (cannot sign in)
                            </option>
                        </select>
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="password">
                            Password <abbr title="Required">*</abbr>
                        </label>
                        <input
                            class="clinical-input"
                            type="password"
                            id="password"
                            name="password"
                            required
                            minlength="8">
                        <p class="clinical-field-hint">
                            Minimum 8 characters
                        </p>
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="password_confirm">
                            Confirm password <abbr title="Required">*</abbr>
                        </label>
                        <input
                            class="clinical-input"
                            type="password"
                            id="password_confirm"
                            name="password_confirm"
                            required
                            minlength="8">
                    </div>
                </div>

                <div class="clinical-button-row" style="margin-top: 2rem;">
                    <button class="clinical-button" type="submit">
                        Create user
                    </button>

                    <a class="clinical-button clinical-button--secondary" href="/clinical/users.php">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
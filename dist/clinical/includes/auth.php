<?php

declare(strict_types=1);

/**
 * Promedico Clinical App Authentication
 * Path: public/clinical/includes/auth.php
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

function clinical_is_logged_in(): bool
{
    clinical_start_session();

    return isset(
        $_SESSION['user_id'],
        $_SESSION['user_name'],
        $_SESSION['user_email'],
        $_SESSION['user_role']
    );
}

function clinical_current_user(): ?array
{
    clinical_start_session();

    if (!clinical_is_logged_in()) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['user_id'],
        'name' => (string) $_SESSION['user_name'],
        'email' => (string) $_SESSION['user_email'],
        'role' => (string) $_SESSION['user_role'],
    ];
}

function clinical_require_login(): void
{
    clinical_start_session();

    if (!clinical_is_logged_in()) {
        clinical_redirect_to_login();
    }

    if (clinical_session_has_timed_out()) {
        clinical_logout();
        clinical_redirect_to_login('timeout');
    }

    clinical_touch_session_activity();
}

function clinical_redirect_to_login(string $reason = ''): void
{
    $location = '/clinical/login.php';

    if ($reason !== '') {
        $location .= '?reason=' . urlencode($reason);
    }

    header('Location: ' . $location);
    exit;
}

function clinical_login(string $email, string $password): bool
{
    clinical_start_session();

    $email = trim(strtolower($email));

    if ($email === '' || $password === '') {
        return false;
    }

    $pdo = clinical_db();

    $stmt = $pdo->prepare(
        'SELECT id, name, email, password_hash, role, is_active
         FROM users
         WHERE email = :email
         LIMIT 1'
    );

    $stmt->execute([
        'email' => $email,
    ]);

    $user = $stmt->fetch();

    if (!$user) {
        clinical_record_failed_login($email);
        return false;
    }

    $userId = (int) $user['id'];

    if ((int) $user['is_active'] !== 1) {
        clinical_record_failed_login($email, $userId);
        return false;
    }

    if (!password_verify($password, (string) $user['password_hash'])) {
        clinical_record_failed_login($email, $userId);
        return false;
    }

    clinical_regenerate_session();

    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = (string) $user['name'];
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['user_role'] = (string) $user['role'];
    $_SESSION['last_activity'] = time();

    $update = $pdo->prepare(
        'UPDATE users
         SET last_login_at = NOW()
         WHERE id = :id'
    );

    $update->execute([
        'id' => $userId,
    ]);

    clinical_audit(
        userId: $userId,
        action: 'login_success',
        entityType: 'user',
        entityId: $userId
    );

    return true;
}

function clinical_logout(): void
{
    clinical_start_session();

    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

    if ($userId !== null) {
        clinical_audit(
            userId: $userId,
            action: 'logout',
            entityType: 'user',
            entityId: $userId
        );
    }

    clinical_destroy_session();
}

function clinical_record_failed_login(string $email, ?int $userId = null): void
{
    clinical_audit(
        userId: $userId,
        action: 'login_failed',
        entityType: 'user',
        entityId: $userId,
        extra: $email
    );
}

function clinical_audit(
    ?int $userId,
    string $action,
    ?string $entityType = null,
    ?int $entityId = null,
    ?string $extra = null
): void {
    try {
        $pdo = clinical_db();

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if ($userAgent !== null) {
            $userAgent = substr($userAgent, 0, 255);
        }

        $auditAction = $extra === null ? $action : $action . ':' . $extra;

        $stmt = $pdo->prepare(
            'INSERT INTO audit_log (
                user_id,
                action,
                entity_type,
                entity_id,
                ip_address,
                user_agent
             ) VALUES (
                :user_id,
                :action,
                :entity_type,
                :entity_id,
                :ip_address,
                :user_agent
             )'
        );

        $stmt->execute([
            'user_id' => $userId,
            'action' => $auditAction,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    } catch (Throwable $e) {
        /**
         * Do not break login/logout because audit logging failed.
         * Later, add file-based emergency logging if required.
         */
    }
}

function clinical_user_has_role(string $role): bool
{
    $user = clinical_current_user();

    return $user !== null && $user['role'] === $role;
}

function clinical_require_admin(): void
{
    clinical_require_login();

    if (!clinical_user_has_role('admin')) {
        http_response_code(403);
        exit('Access denied.');
    }
}

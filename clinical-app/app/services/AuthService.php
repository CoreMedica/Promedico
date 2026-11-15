<?php

declare(strict_types=1);

/**
 * Promedico Clinical App Auth Service
 * Path: public/clinical/app/services/AuthService.php
 *
 * Handles authentication workflow.
 * Session lifecycle still lives in includes/session.php.
 */

final class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AuditService $auditService
    ) {}

    public function login(string $email, string $password): bool
    {
        clinical_start_session();

        $email = strtolower(trim($email));

        if ($email === '' || $password === '') {
            $this->auditService->recordLoginFailed(null);
            return false;
        }

        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            $this->auditService->recordLoginFailed(null);
            return false;
        }

        $userId = (int) $user['id'];

        if ((int) $user['is_active'] !== 1) {
            $this->auditService->recordLoginFailed($userId);
            return false;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            $this->auditService->recordLoginFailed($userId);
            return false;
        }

        clinical_regenerate_session();

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = (string) $user['name'];
        $_SESSION['user_email'] = (string) $user['email'];
        $_SESSION['user_role'] = (string) $user['role'];
        $_SESSION['last_activity'] = time();

        $this->userRepository->updateLastLoginAt($userId);

        $this->auditService->recordLoginSuccess($userId);

        return true;
    }

    public function logout(): void
    {
        clinical_start_session();

        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        if ($userId !== null) {
            $this->auditService->recordLogout($userId);
        }

        clinical_destroy_session();
    }

    public function currentUser(): ?array
    {
        clinical_start_session();

        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => (int) $_SESSION['user_id'],
            'name' => (string) $_SESSION['user_name'],
            'email' => (string) $_SESSION['user_email'],
            'role' => (string) $_SESSION['user_role'],
        ];
    }

    public function isLoggedIn(): bool
    {
        clinical_start_session();

        return isset(
            $_SESSION['user_id'],
            $_SESSION['user_name'],
            $_SESSION['user_email'],
            $_SESSION['user_role']
        );
    }

    public function hasRole(string $role): bool
    {
        $user = $this->currentUser();

        return $user !== null && $user['role'] === $role;
    }
}

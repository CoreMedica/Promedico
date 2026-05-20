<?php

declare(strict_types=1);

/**
 * Promedico Clinical App User Repository
 * Path: public/clinical/app/repositories/UserRepository.php
 *
 * Responsible only for users table database access.
 */

final class UserRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT
                id,
                name,
                email,
                password_hash,
                role,
                is_active,
                last_login_at,
                created_at,
                updated_at
             FROM users
             WHERE email = :email
             LIMIT 1'
        );

        $stmt->execute([
            'email' => $email,
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                id,
                name,
                email,
                role,
                is_active,
                last_login_at,
                created_at,
                updated_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $userId,
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function updateLastLoginAt(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET last_login_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $userId,
        ]);
    }

    public function listActiveUsers(): array
    {
        $stmt = $this->pdo->query(
            'SELECT
                id,
                name,
                email,
                role,
                is_active,
                last_login_at,
                created_at,
                updated_at
             FROM users
             WHERE is_active = 1
             ORDER BY name ASC, email ASC'
        );

        return $stmt->fetchAll();
    }
}

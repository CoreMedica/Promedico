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

    // User Managemtnent Functions

    // Add to existing UserRepository class

    public function listAllUsers(bool $activeOnly = false): array
    {
        $sql = '
        SELECT
            id,
            name,
            email,
            role,
            is_active,
            last_login_at,
            created_at,
            updated_at
        FROM users
    ';

        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }

        $sql .= ' ORDER BY name ASC, email ASC';

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll();
    }

    public function findByIdWithCreator(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
            u.*,
            created_user.name AS created_by_name,
            updated_user.name AS updated_by_name
         FROM users u
         LEFT JOIN users created_user ON created_user.id = u.created_by
         LEFT JOIN users updated_user ON updated_user.id = u.updated_by
         WHERE u.id = :id
         LIMIT 1'
        );

        $stmt->execute([
            'id' => $userId,
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $email = strtolower(trim($email));

        if ($excludeUserId === null) {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) AS total
             FROM users
             WHERE email = :email'
            );

            $stmt->execute(['email' => $email]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) AS total
             FROM users
             WHERE email = :email
               AND id != :exclude_id'
            );

            $stmt->execute([
                'email' => $email,
                'exclude_id' => $excludeUserId,
            ]);
        }

        $row = $stmt->fetch();

        return ((int) ($row['total'] ?? 0)) > 0;
    }

    public function create(array $data, int $createdBy): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (
            name,
            email,
            password_hash,
            role,
            is_active,
            created_by
         ) VALUES (
            :name,
            :email,
            :password_hash,
            :role,
            :is_active,
            :created_by
         )'
        );

        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'role' => $data['role'],
            'is_active' => $data['is_active'],
            'created_by' => $createdBy,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $userId, array $data, int $updatedBy): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
         SET
            name = :name,
            email = :email,
            role = :role,
            is_active = :is_active,
            updated_by = :updated_by
         WHERE id = :id'
        );

        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $data['is_active'],
            'updated_by' => $updatedBy,
            'id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function updatePassword(int $userId, string $passwordHash, int $updatedBy): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
         SET
            password_hash = :password_hash,
            updated_by = :updated_by
         WHERE id = :id'
        );

        $stmt->execute([
            'password_hash' => $passwordHash,
            'updated_by' => $updatedBy,
            'id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function deactivate(int $userId, int $updatedBy): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
         SET
            is_active = 0,
            updated_by = :updated_by
         WHERE id = :id
           AND is_active = 1'
        );

        $stmt->execute([
            'updated_by' => $updatedBy,
            'id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function countActiveUsers(): int
    {
        $stmt = $this->pdo->query(
            'SELECT COUNT(*) AS total
         FROM users
         WHERE is_active = 1'
        );

        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }
}

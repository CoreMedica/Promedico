<?php

declare(strict_types=1);

/**
 * Promedico Clinical App User Service
 * Path: public/clinical/app/services/UserService.php
 *
 * Responsible for user workflow/business logic.
 * No HTML rendering and no redirects here.
 */

final class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserValidator $userValidator,
        private readonly AuditService $auditService
    ) {}

    public function emptyForm(): array
    {
        return [
            'name' => '',
            'email' => '',
            'role' => 'clinician',
            'is_active' => '1',
            'password' => '',
            'password_confirm' => '',
        ];
    }

    public function userToForm(array $user): array
    {
        return [
            'name' => (string) ($user['name'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'role' => (string) ($user['role'] ?? 'clinician'),
            'is_active' => (string) ($user['is_active'] ?? '1'),
            'password' => '',
            'password_confirm' => '',
        ];
    }

    public function listUsers(bool $activeOnly = false): array
    {
        return $this->userRepository->listAllUsers($activeOnly);
    }

    public function getUserForView(int $userId, int $currentUserId): ?array
    {
        $user = $this->userRepository->findByIdWithCreator($userId);

        if ($user === null) {
            return null;
        }

        $this->auditService->recordUserViewed(
            targetUserId: $userId,
            userId: $currentUserId
        );

        return $user;
    }

    public function getUserForEdit(int $userId): ?array
    {
        return $this->userRepository->findById($userId);
    }

    public function createUser(array $input, int $currentUserId): array
    {
        $form = $this->inputToForm($input);
        $errors = $this->userValidator->validate($form, isEdit: false);

        // Check if email already exists
        if ($errors === [] && $this->userRepository->emailExists($form['email'])) {
            $errors[] = 'Email address is already in use.';
        }

        if (count($errors) > 0) {
            return [
                'success' => false,
                'user_id' => null,
                'errors' => $errors,
                'form' => $form,
            ];
        }

        $normalised = $this->normaliseUserData($form);

        $userId = $this->userRepository->create(
            data: $normalised,
            createdBy: $currentUserId
        );

        $this->auditService->recordUserCreated(
            targetUserId: $userId,
            userId: $currentUserId
        );

        return [
            'success' => true,
            'user_id' => $userId,
            'errors' => [],
            'form' => $this->emptyForm(),
        ];
    }

    public function updateUser(int $userId, array $input, int $currentUserId): array
    {
        $existingUser = $this->userRepository->findById($userId);

        if ($existingUser === null) {
            return [
                'success' => false,
                'not_found' => true,
                'errors' => ['User not found.'],
                'form' => $this->inputToForm($input),
            ];
        }

        $form = $this->inputToForm($input);
        $errors = $this->userValidator->validate($form, isEdit: true);

        // Check if email already exists for another user
        if ($errors === [] && $this->userRepository->emailExists($form['email'], $userId)) {
            $errors[] = 'Email address is already in use by another user.';
        }

        if (count($errors) > 0) {
            return [
                'success' => false,
                'not_found' => false,
                'errors' => $errors,
                'form' => $form,
            ];
        }

        $normalised = $this->normaliseUserData($form);

        $this->userRepository->update(
            userId: $userId,
            data: $normalised,
            updatedBy: $currentUserId
        );

        // Update password if provided
        if ($form['password'] !== '') {
            $passwordHash = password_hash($form['password'], PASSWORD_DEFAULT);
            $this->userRepository->updatePassword(
                userId: $userId,
                passwordHash: $passwordHash,
                updatedBy: $currentUserId
            );
        }

        $this->auditService->recordUserUpdated(
            targetUserId: $userId,
            userId: $currentUserId
        );

        return [
            'success' => true,
            'not_found' => false,
            'errors' => [],
            'form' => $form,
        ];
    }

    public function deactivateUser(int $userId, int $currentUserId): bool
    {
        $deactivated = $this->userRepository->deactivate(
            userId: $userId,
            updatedBy: $currentUserId
        );

        if ($deactivated) {
            $this->auditService->recordUserDeactivated(
                targetUserId: $userId,
                userId: $currentUserId
            );
        }

        return $deactivated;
    }

    public function countActiveUsers(): int
    {
        return $this->userRepository->countActiveUsers();
    }

    private function inputToForm(array $input): array
    {
        $form = $this->emptyForm();

        foreach ($form as $key => $_) {
            if ($key === 'password' || $key === 'password_confirm') {
                $form[$key] = (string) ($input[$key] ?? '');
            } else {
                $form[$key] = trim((string) ($input[$key] ?? ''));
            }
        }

        return $form;
    }

    private function normaliseUserData(array $form): array
    {
        $data = [
            'name' => trim((string) ($form['name'] ?? '')),
            'email' => strtolower(trim((string) ($form['email'] ?? ''))),
            'role' => trim((string) ($form['role'] ?? 'clinician')),
            'is_active' => (int) ($form['is_active'] ?? 1),
        ];

        // Only include password_hash if password is provided
        if (isset($form['password']) && $form['password'] !== '') {
            $data['password_hash'] = password_hash($form['password'], PASSWORD_DEFAULT);
        }

        return $data;
    }
}

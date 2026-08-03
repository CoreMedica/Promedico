<?php

declare(strict_types=1);

/**
 * Promedico Clinical App User Validator
 * Path: public/clinical/app/validators/UserValidator.php
 *
 * Responsible only for validating user form input.
 * Do not put SQL, redirects, audit logging, or rendering logic here.
 */

final class UserValidator
{
    public function validate(array $input, bool $isEdit = false): array
    {
        $errors = [];

        $name = trim((string) ($input['name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $role = trim((string) ($input['role'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $passwordConfirm = (string) ($input['password_confirm'] ?? '');
        $isActive = $input['is_active'] ?? '1';

        // Name validation
        if ($name === '') {
            $errors[] = 'Name is required.';
        } elseif (mb_strlen($name) > 255) {
            $errors[] = 'Name must be 255 characters or fewer.';
        }

        // Email validation
        if ($email === '') {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email address is not valid.';
        } elseif (mb_strlen($email) > 190) {
            $errors[] = 'Email address must be 190 characters or fewer.';
        }

        // Role validation
        if ($role === '') {
            $errors[] = 'Role is required.';
        } elseif (!in_array($role, ['admin', 'clinician'], true)) {
            $errors[] = 'Role must be either admin or clinician.';
        }

        // Password validation (only required for new users)
        if (!$isEdit) {
            if ($password === '') {
                $errors[] = 'Password is required.';
            } elseif (mb_strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            } elseif ($password !== $passwordConfirm) {
                $errors[] = 'Password and confirmation do not match.';
            }
        } else {
            // Password optional on edit, but if provided must be valid
            if ($password !== '') {
                if (mb_strlen($password) < 8) {
                    $errors[] = 'Password must be at least 8 characters.';
                } elseif ($password !== $passwordConfirm) {
                    $errors[] = 'Password and confirmation do not match.';
                }
            }
        }

        // Is active validation
        if (!in_array($isActive, ['0', '1', 0, 1], true)) {
            $errors[] = 'Active status must be Yes or No.';
        }

        return $errors;
    }

    public function isValid(array $input, bool $isEdit = false): bool
    {
        return count($this->validate($input, $isEdit)) === 0;
    }
}

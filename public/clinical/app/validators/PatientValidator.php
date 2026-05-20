<?php

declare(strict_types=1);

/**
 * Promedico Clinical App Patient Validator
 * Path: public/clinical/app/validators/PatientValidator.php
 *
 * Responsible only for validating patient form input.
 * Do not put SQL, redirects, audit logging, or rendering logic here.
 */

final class PatientValidator
{
    public function validate(array $input): array
    {
        $errors = [];

        $firstName = trim((string) ($input['first_name'] ?? ''));
        $lastName = trim((string) ($input['last_name'] ?? ''));
        $dateOfBirth = trim((string) ($input['date_of_birth'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $postcode = trim((string) ($input['postcode'] ?? ''));

        if ($firstName === '') {
            $errors[] = 'First name is required.';
        }

        if ($lastName === '') {
            $errors[] = 'Last name is required.';
        }

        if ($dateOfBirth !== '' && !$this->isValidDate($dateOfBirth)) {
            $errors[] = 'Date of birth must be a valid date.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email address is not valid.';
        }

        if ($phone !== '' && mb_strlen($phone) > 40) {
            $errors[] = 'Phone number is too long.';
        }

        if ($postcode !== '' && mb_strlen($postcode) > 20) {
            $errors[] = 'Postcode is too long.';
        }

        $maxLengths = [
            'first_name' => ['First name', 100],
            'last_name' => ['Last name', 100],
            'email' => ['Email address', 190],
            'address_line_1' => ['Address line 1', 190],
            'address_line_2' => ['Address line 2', 190],
            'town' => ['Town/city', 120],
            'county' => ['County', 120],
        ];

        foreach ($maxLengths as $field => [$label, $maxLength]) {
            $value = trim((string) ($input[$field] ?? ''));

            if ($value !== '' && mb_strlen($value) > $maxLength) {
                $errors[] = $label . ' must be ' . $maxLength . ' characters or fewer.';
            }
        }

        return $errors;
    }

    public function isValid(array $input): bool
    {
        return count($this->validate($input)) === 0;
    }

    private function isValidDate(string $date): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $date);

        return $dt !== false && $dt->format('Y-m-d') === $date;
    }
}

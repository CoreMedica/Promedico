<?php

declare(strict_types=1);

/**
 * Promedico Clinical App Patient Repository
 * Path: public/clinical/app/repositories/PatientRepository.php
 *
 * Responsible only for patient database access.
 * No validation, redirects, HTML rendering, or clinical workflow logic here.
 */

final class PatientRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function searchActivePatients(string $search = '', int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));
        $search = trim($search);

        $sql = '
            SELECT
                id,
                first_name,
                last_name,
                date_of_birth,
                phone,
                email,
                postcode,
                created_at
            FROM patients
            WHERE is_active = 1
        ';

        $params = [];

        if ($search !== '') {
            $sql .= '
                AND (
                    first_name LIKE :search
                    OR last_name LIKE :search
                    OR CONCAT(first_name, " ", last_name) LIKE :search_full_name
                    OR phone LIKE :search_phone
                    OR email LIKE :search_email
                    OR postcode LIKE :search_postcode
                )
            ';

            $like = '%' . $search . '%';

            $params = [
                'search' => $like,
                'search_full_name' => $like,
                'search_phone' => $like,
                'search_email' => $like,
                'search_postcode' => $like,
            ];
        }

        $sql .= '
            ORDER BY last_name ASC, first_name ASC
            LIMIT :limit_value
        ';

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->bindValue('limit_value', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findActiveById(int $patientId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                p.*,
                created_user.name AS created_by_name,
                updated_user.name AS updated_by_name
             FROM patients p
             LEFT JOIN users created_user ON created_user.id = p.created_by
             LEFT JOIN users updated_user ON updated_user.id = p.updated_by
             WHERE p.id = :id
               AND p.is_active = 1
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $patientId,
        ]);

        $patient = $stmt->fetch();

        return $patient ?: null;
    }

    public function create(array $data, int $createdBy): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO patients (
                first_name,
                last_name,
                date_of_birth,
                phone,
                email,
                address_line_1,
                address_line_2,
                town,
                county,
                postcode,
                relevant_medical_notes,
                created_by
             ) VALUES (
                :first_name,
                :last_name,
                :date_of_birth,
                :phone,
                :email,
                :address_line_1,
                :address_line_2,
                :town,
                :county,
                :postcode,
                :relevant_medical_notes,
                :created_by
             )'
        );

        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'date_of_birth' => $data['date_of_birth'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'address_line_1' => $data['address_line_1'],
            'address_line_2' => $data['address_line_2'],
            'town' => $data['town'],
            'county' => $data['county'],
            'postcode' => $data['postcode'],
            'relevant_medical_notes' => $data['relevant_medical_notes'],
            'created_by' => $createdBy,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $patientId, array $data, int $updatedBy): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE patients
             SET
                first_name = :first_name,
                last_name = :last_name,
                date_of_birth = :date_of_birth,
                phone = :phone,
                email = :email,
                address_line_1 = :address_line_1,
                address_line_2 = :address_line_2,
                town = :town,
                county = :county,
                postcode = :postcode,
                relevant_medical_notes = :relevant_medical_notes,
                updated_by = :updated_by
             WHERE id = :id
               AND is_active = 1'
        );

        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'date_of_birth' => $data['date_of_birth'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'address_line_1' => $data['address_line_1'],
            'address_line_2' => $data['address_line_2'],
            'town' => $data['town'],
            'county' => $data['county'],
            'postcode' => $data['postcode'],
            'relevant_medical_notes' => $data['relevant_medical_notes'],
            'updated_by' => $updatedBy,
            'id' => $patientId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function findPossibleMatches(array $data, int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));

        $lastName = trim((string) ($data['last_name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $postcode = trim((string) ($data['postcode'] ?? ''));
        $dateOfBirth = $data['date_of_birth'] ?? null;

        if (
            $lastName === '' &&
            $phone === '' &&
            $email === '' &&
            $postcode === '' &&
            $dateOfBirth === null
        ) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT
                id,
                first_name,
                last_name,
                date_of_birth,
                phone,
                email,
                postcode
             FROM patients
             WHERE is_active = 1
               AND (
                    (:last_name_check <> "" AND last_name LIKE :last_name_like)
                    OR (:phone_check <> "" AND phone = :phone_value)
                    OR (:email_check <> "" AND email = :email_value)
                    OR (:postcode_check <> "" AND postcode = :postcode_value)
                    OR (:dob_check IS NOT NULL AND date_of_birth = :dob_value)
               )
             ORDER BY last_name ASC, first_name ASC
             LIMIT :limit_value'
        );

        $stmt->bindValue('last_name_check', $lastName, PDO::PARAM_STR);
        $stmt->bindValue('last_name_like', '%' . $lastName . '%', PDO::PARAM_STR);

        $stmt->bindValue('phone_check', $phone, PDO::PARAM_STR);
        $stmt->bindValue('phone_value', $phone, PDO::PARAM_STR);

        $stmt->bindValue('email_check', $email, PDO::PARAM_STR);
        $stmt->bindValue('email_value', $email, PDO::PARAM_STR);

        $stmt->bindValue('postcode_check', $postcode, PDO::PARAM_STR);
        $stmt->bindValue('postcode_value', $postcode, PDO::PARAM_STR);

        if ($dateOfBirth === null) {
            $stmt->bindValue('dob_check', null, PDO::PARAM_NULL);
            $stmt->bindValue('dob_value', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue('dob_check', (string) $dateOfBirth, PDO::PARAM_STR);
            $stmt->bindValue('dob_value', (string) $dateOfBirth, PDO::PARAM_STR);
        }

        $stmt->bindValue('limit_value', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function deactivate(int $patientId, int $updatedBy): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE patients
             SET
                is_active = 0,
                updated_by = :updated_by
             WHERE id = :id
               AND is_active = 1'
        );

        $stmt->execute([
            'updated_by' => $updatedBy,
            'id' => $patientId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function countActivePatients(): int
    {
        $stmt = $this->pdo->query(
            'SELECT COUNT(*) AS total
             FROM patients
             WHERE is_active = 1'
        );

        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }
}

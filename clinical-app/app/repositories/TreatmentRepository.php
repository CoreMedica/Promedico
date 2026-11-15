<?php

declare(strict_types=1);

/**
 * Promedico Clinical App Treatment Repository
 * Path: public/clinical/app/repositories/TreatmentRepository.php
 *
 * Responsible only for treatment and treatment addendum database access.
 */

final class TreatmentRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function createTreatment(array $data, int $createdBy): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO treatments (
                patient_id,
                practitioner_id,
                fresha_appointment_reference,
                treatment_date,
                treatment_time,
                location_type,
                location_name,
                treatment_type,
                consent_confirmed,
                contraindications_checked,
                left_ear_findings,
                right_ear_findings,
                procedure_notes,
                outcome,
                aftercare_given,
                follow_up_required,
                follow_up_notes,
                is_locked,
                created_by
             ) VALUES (
                :patient_id,
                :practitioner_id,
                :fresha_appointment_reference,
                :treatment_date,
                :treatment_time,
                :location_type,
                :location_name,
                :treatment_type,
                :consent_confirmed,
                :contraindications_checked,
                :left_ear_findings,
                :right_ear_findings,
                :procedure_notes,
                :outcome,
                :aftercare_given,
                :follow_up_required,
                :follow_up_notes,
                1,
                :created_by
             )'
        );

        $stmt->execute([
            'patient_id' => $data['patient_id'],
            'practitioner_id' => $data['practitioner_id'],
            'fresha_appointment_reference' => $data['fresha_appointment_reference'],
            'treatment_date' => $data['treatment_date'],
            'treatment_time' => $data['treatment_time'],
            'location_type' => $data['location_type'],
            'location_name' => $data['location_name'],
            'treatment_type' => $data['treatment_type'],
            'consent_confirmed' => $data['consent_confirmed'],
            'contraindications_checked' => $data['contraindications_checked'],
            'left_ear_findings' => $data['left_ear_findings'],
            'right_ear_findings' => $data['right_ear_findings'],
            'procedure_notes' => $data['procedure_notes'],
            'outcome' => $data['outcome'],
            'aftercare_given' => $data['aftercare_given'],
            'follow_up_required' => $data['follow_up_required'],
            'follow_up_notes' => $data['follow_up_notes'],
            'created_by' => $createdBy,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findTreatmentById(int $treatmentId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                t.*,

                p.first_name AS patient_first_name,
                p.last_name AS patient_last_name,
                p.date_of_birth AS patient_date_of_birth,
                p.phone AS patient_phone,
                p.email AS patient_email,
                p.postcode AS patient_postcode,

                practitioner.name AS practitioner_name,
                creator.name AS created_by_name
             FROM treatments t
             INNER JOIN patients p ON p.id = t.patient_id
             LEFT JOIN users practitioner ON practitioner.id = t.practitioner_id
             LEFT JOIN users creator ON creator.id = t.created_by
             WHERE t.id = :id
               AND p.is_active = 1
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $treatmentId,
        ]);

        $treatment = $stmt->fetch();

        return $treatment ?: null;
    }

    public function findTreatmentsByPatientId(int $patientId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                t.id,
                t.patient_id,
                t.treatment_date,
                t.treatment_time,
                t.location_type,
                t.location_name,
                t.treatment_type,
                t.follow_up_required,
                t.created_at,
                u.name AS practitioner_name
             FROM treatments t
             LEFT JOIN users u ON u.id = t.practitioner_id
             WHERE t.patient_id = :patient_id
             ORDER BY t.treatment_date DESC, t.treatment_time DESC, t.id DESC'
        );

        $stmt->execute([
            'patient_id' => $patientId,
        ]);

        return $stmt->fetchAll();
    }

    public function createAddendum(
        int $treatmentId,
        int $userId,
        string $reason,
        string $addendumText
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO treatment_addenda (
                treatment_id,
                user_id,
                reason,
                addendum_text
             ) VALUES (
                :treatment_id,
                :user_id,
                :reason,
                :addendum_text
             )'
        );

        $stmt->execute([
            'treatment_id' => $treatmentId,
            'user_id' => $userId,
            'reason' => $reason,
            'addendum_text' => $addendumText,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findAddendaForTreatment(int $treatmentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                a.id,
                a.treatment_id,
                a.user_id,
                a.reason,
                a.addendum_text,
                a.created_at,
                u.name AS user_name
             FROM treatment_addenda a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.treatment_id = :treatment_id
             ORDER BY a.created_at ASC, a.id ASC'
        );

        $stmt->execute([
            'treatment_id' => $treatmentId,
        ]);

        return $stmt->fetchAll();
    }

    public function countTreatments(): int
    {
        $stmt = $this->pdo->query(
            'SELECT COUNT(*) AS total
             FROM treatments'
        );

        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public function countOutstandingFollowUps(): int
    {
        $stmt = $this->pdo->query(
            'SELECT COUNT(*) AS total
         FROM treatments
         WHERE follow_up_required = 1
           AND follow_up_completed_at IS NULL'
        );

        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public function latestTreatments(int $limit = 10): array
    {
        $limit = max(1, min($limit, 100));

        $stmt = $this->pdo->prepare(
            'SELECT
                t.id,
                t.patient_id,
                t.treatment_date,
                t.treatment_time,
                t.location_type,
                t.treatment_type,
                t.follow_up_required,
                t.created_at,
                p.first_name AS patient_first_name,
                p.last_name AS patient_last_name,
                u.name AS practitioner_name
             FROM treatments t
             INNER JOIN patients p ON p.id = t.patient_id
             LEFT JOIN users u ON u.id = t.practitioner_id
             WHERE p.is_active = 1
             ORDER BY t.created_at DESC, t.id DESC
             LIMIT :limit_value'
        );

        $stmt->bindValue('limit_value', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findOutstandingFollowUps(int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));

        $stmt = $this->pdo->prepare(
            'SELECT
            t.id,
            t.patient_id,
            t.treatment_date,
            t.treatment_time,
            t.treatment_type,
            t.location_type,
            t.location_name,
            t.follow_up_notes,
            t.created_at,

            p.first_name AS patient_first_name,
            p.last_name AS patient_last_name,
            p.date_of_birth AS patient_date_of_birth,
            p.phone AS patient_phone,
            p.email AS patient_email,
            p.postcode AS patient_postcode,

            practitioner.name AS practitioner_name
         FROM treatments t
         INNER JOIN patients p ON p.id = t.patient_id
         LEFT JOIN users practitioner ON practitioner.id = t.practitioner_id
         WHERE t.follow_up_required = 1
           AND t.follow_up_completed_at IS NULL
           AND p.is_active = 1
         ORDER BY t.treatment_date ASC, t.treatment_time ASC, t.id ASC
         LIMIT :limit_value'
        );

        $stmt->bindValue('limit_value', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findCompletedFollowUps(int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));

        $stmt = $this->pdo->prepare(
            'SELECT
            t.id,
            t.patient_id,
            t.treatment_date,
            t.treatment_time,
            t.treatment_type,
            t.follow_up_notes,
            t.follow_up_completed_at,
            t.follow_up_completion_notes,

            p.first_name AS patient_first_name,
            p.last_name AS patient_last_name,
            p.date_of_birth AS patient_date_of_birth,
            p.phone AS patient_phone,
            p.email AS patient_email,
            p.postcode AS patient_postcode,

            completed_user.name AS follow_up_completed_by_name
         FROM treatments t
         INNER JOIN patients p ON p.id = t.patient_id
         LEFT JOIN users completed_user ON completed_user.id = t.follow_up_completed_by
         WHERE t.follow_up_required = 1
           AND t.follow_up_completed_at IS NOT NULL
           AND p.is_active = 1
         ORDER BY t.follow_up_completed_at DESC, t.id DESC
         LIMIT :limit_value'
        );

        $stmt->bindValue('limit_value', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function completeFollowUp(
        int $treatmentId,
        int $userId,
        ?string $completionNotes
    ): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE treatments
         SET
            follow_up_completed_at = NOW(),
            follow_up_completed_by = :follow_up_completed_by,
            follow_up_completion_notes = :follow_up_completion_notes
         WHERE id = :id
           AND follow_up_required = 1
           AND follow_up_completed_at IS NULL'
        );

        $stmt->execute([
            'follow_up_completed_by' => $userId,
            'follow_up_completion_notes' => $completionNotes,
            'id' => $treatmentId,
        ]);

        return $stmt->rowCount() > 0;
    }
}

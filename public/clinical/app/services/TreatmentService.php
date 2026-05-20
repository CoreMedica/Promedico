<?php

declare(strict_types=1);

/**
 * Promedico Clinical App Treatment Service
 * Path: public/clinical/app/services/TreatmentService.php
 *
 * Responsible for treatment workflow/business logic.
 * No HTML rendering and no redirects here.
 */

final class TreatmentService
{
    public function __construct(
        private readonly TreatmentRepository $treatmentRepository,
        private readonly PatientRepository $patientRepository,
        private readonly TreatmentValidator $treatmentValidator,
        private readonly AuditService $auditService
    ) {}

    public function emptyForm(?int $patientId = null): array
    {
        return [
            'patient_id' => $patientId !== null ? (string) $patientId : '',
            'fresha_appointment_reference' => '',
            'treatment_date' => date('Y-m-d'),
            'treatment_time' => '',
            'location_type' => 'clinic',
            'location_name' => '',
            'treatment_type' => 'microsuction',
            'consent_confirmed' => '',
            'contraindications_checked' => '',
            'left_ear_findings' => '',
            'right_ear_findings' => '',
            'procedure_notes' => '',
            'outcome' => '',
            'aftercare_given' => '',
            'follow_up_required' => '',
            'follow_up_notes' => '',
        ];
    }

    public function createTreatment(array $input, int $userId): array
    {
        $form = $this->inputToForm($input);
        $errors = $this->treatmentValidator->validate($input);

        $patientId = (int) ($form['patient_id'] ?? 0);

        if ($patientId > 0) {
            $patient = $this->patientRepository->findActiveById($patientId);

            if ($patient === null) {
                $errors[] = 'Selected patient was not found.';
            }
        }

        if (count($errors) > 0) {
            return [
                'success' => false,
                'treatment_id' => null,
                'patient_id' => $patientId > 0 ? $patientId : null,
                'errors' => $errors,
                'form' => $form,
            ];
        }

        $normalised = $this->normaliseTreatmentData($form, $userId);

        $treatmentId = $this->treatmentRepository->createTreatment(
            data: $normalised,
            createdBy: $userId
        );

        $this->auditService->recordTreatmentCreated(
            treatmentId: $treatmentId,
            userId: $userId
        );

        return [
            'success' => true,
            'treatment_id' => $treatmentId,
            'patient_id' => $patientId,
            'errors' => [],
            'form' => $this->emptyForm($patientId),
        ];
    }

    public function getTreatmentForView(int $treatmentId, int $userId): ?array
    {
        $treatment = $this->treatmentRepository->findTreatmentById($treatmentId);

        if ($treatment === null) {
            return null;
        }

        $this->auditService->recordTreatmentViewed(
            treatmentId: $treatmentId,
            userId: $userId
        );

        return $treatment;
    }

    public function getAddendaForTreatment(int $treatmentId): array
    {
        return $this->treatmentRepository->findAddendaForTreatment($treatmentId);
    }

    public function listTreatmentsForPatient(int $patientId): array
    {
        return $this->treatmentRepository->findTreatmentsByPatientId($patientId);
    }

    public function createAddendum(int $treatmentId, array $input, int $userId): array
    {
        $treatment = $this->treatmentRepository->findTreatmentById($treatmentId);

        if ($treatment === null) {
            return [
                'success' => false,
                'not_found' => true,
                'addendum_id' => null,
                'errors' => ['Treatment record not found.'],
                'form' => $this->addendumInputToForm($input),
            ];
        }

        $form = $this->addendumInputToForm($input);
        $errors = $this->treatmentValidator->validateAddendum($form);

        if (count($errors) > 0) {
            return [
                'success' => false,
                'not_found' => false,
                'addendum_id' => null,
                'errors' => $errors,
                'form' => $form,
            ];
        }

        $addendumId = $this->treatmentRepository->createAddendum(
            treatmentId: $treatmentId,
            userId: $userId,
            reason: trim($form['reason']),
            addendumText: trim($form['addendum_text'])
        );

        $this->auditService->recordAddendumCreated(
            addendumId: $addendumId,
            userId: $userId
        );

        return [
            'success' => true,
            'not_found' => false,
            'addendum_id' => $addendumId,
            'errors' => [],
            'form' => $this->emptyAddendumForm(),
        ];
    }

    public function emptyAddendumForm(): array
    {
        return [
            'reason' => '',
            'addendum_text' => '',
        ];
    }

    public function countTreatments(): int
    {
        return $this->treatmentRepository->countTreatments();
    }

    public function countOutstandingFollowUps(): int
    {
        return $this->treatmentRepository->countOutstandingFollowUps();
    }

    public function latestTreatments(int $limit = 10): array
    {
        return $this->treatmentRepository->latestTreatments($limit);
    }

    private function inputToForm(array $input): array
    {
        $form = $this->emptyForm();

        foreach ($form as $key => $_) {
            if (in_array($key, ['consent_confirmed', 'contraindications_checked', 'follow_up_required'], true)) {
                $form[$key] = isset($input[$key]) ? '1' : '';
                continue;
            }

            $form[$key] = trim((string) ($input[$key] ?? ''));
        }

        return $form;
    }

    private function addendumInputToForm(array $input): array
    {
        return [
            'reason' => trim((string) ($input['reason'] ?? '')),
            'addendum_text' => trim((string) ($input['addendum_text'] ?? '')),
        ];
    }

    private function normaliseTreatmentData(array $form, int $userId): array
    {
        $treatmentTime = clinical_nullable_string((string) ($form['treatment_time'] ?? ''));

        return [
            'patient_id' => (int) $form['patient_id'],
            'practitioner_id' => $userId,

            'fresha_appointment_reference' => clinical_nullable_string(
                (string) ($form['fresha_appointment_reference'] ?? '')
            ),

            'treatment_date' => clinical_valid_date_or_null(
                (string) ($form['treatment_date'] ?? '')
            ),

            'treatment_time' => $treatmentTime,

            'location_type' => trim((string) ($form['location_type'] ?? 'clinic')),
            'location_name' => clinical_nullable_string((string) ($form['location_name'] ?? '')),

            'treatment_type' => trim((string) ($form['treatment_type'] ?? 'microsuction')),

            'consent_confirmed' => (int) (($form['consent_confirmed'] ?? '') === '1'),
            'contraindications_checked' => (int) (($form['contraindications_checked'] ?? '') === '1'),

            'left_ear_findings' => clinical_nullable_string((string) ($form['left_ear_findings'] ?? '')),
            'right_ear_findings' => clinical_nullable_string((string) ($form['right_ear_findings'] ?? '')),
            'procedure_notes' => clinical_nullable_string((string) ($form['procedure_notes'] ?? '')),
            'outcome' => clinical_nullable_string((string) ($form['outcome'] ?? '')),
            'aftercare_given' => clinical_nullable_string((string) ($form['aftercare_given'] ?? '')),

            'follow_up_required' => (int) (($form['follow_up_required'] ?? '') === '1'),
            'follow_up_notes' => clinical_nullable_string((string) ($form['follow_up_notes'] ?? '')),
        ];
    }
}

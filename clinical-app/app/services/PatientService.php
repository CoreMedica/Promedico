<?php

declare(strict_types=1);

/**
 * Promedico Clinical App Patient Service
 * Path: public/clinical/app/services/PatientService.php
 *
 * Responsible for patient workflow/business logic.
 * No HTML rendering and no redirects here.
 */

final class PatientService
{
    public function __construct(
        private readonly PatientRepository $patientRepository,
        private readonly PatientValidator $patientValidator,
        private readonly AuditService $auditService
    ) {}

    public function emptyForm(): array
    {
        return [
            'first_name' => '',
            'last_name' => '',
            'date_of_birth' => '',
            'phone' => '',
            'email' => '',
            'address_line_1' => '',
            'address_line_2' => '',
            'town' => '',
            'county' => '',
            'postcode' => '',
            'relevant_medical_notes' => '',
        ];
    }

    public function patientToForm(array $patient): array
    {
        $form = $this->emptyForm();

        foreach ($form as $key => $_) {
            $form[$key] = (string) ($patient[$key] ?? '');
        }

        return $form;
    }

    public function searchPatients(string $search, int $limit = 100): array
    {
        return $this->patientRepository->searchActivePatients(
            search: trim($search),
            limit: $limit
        );
    }

    public function getPatientForView(int $patientId, int $userId): ?array
    {
        $patient = $this->patientRepository->findActiveById($patientId);

        if ($patient === null) {
            return null;
        }

        $this->auditService->recordPatientViewed(
            patientId: $patientId,
            userId: $userId
        );

        return $patient;
    }

    public function getPatientForEdit(int $patientId): ?array
    {
        return $this->patientRepository->findActiveById($patientId);
    }

    public function createPatient(array $input, int $userId): array
    {
        $form = $this->inputToForm($input);
        $errors = $this->patientValidator->validate($form);
        $normalised = $this->normalisePatientData($form);

        $possibleMatches = $this->patientRepository->findPossibleMatches($normalised);

        if (count($errors) > 0) {
            return [
                'success' => false,
                'patient_id' => null,
                'errors' => $errors,
                'form' => $form,
                'possible_matches' => $possibleMatches,
            ];
        }

        $patientId = $this->patientRepository->create(
            data: $normalised,
            createdBy: $userId
        );

        $this->auditService->recordPatientCreated(
            patientId: $patientId,
            userId: $userId
        );

        return [
            'success' => true,
            'patient_id' => $patientId,
            'errors' => [],
            'form' => $this->emptyForm(),
            'possible_matches' => [],
        ];
    }

    public function updatePatient(int $patientId, array $input, int $userId): array
    {
        $existingPatient = $this->patientRepository->findActiveById($patientId);

        if ($existingPatient === null) {
            return [
                'success' => false,
                'not_found' => true,
                'errors' => ['Patient not found.'],
                'form' => $this->inputToForm($input),
            ];
        }

        $form = $this->inputToForm($input);
        $errors = $this->patientValidator->validate($form);
        $normalised = $this->normalisePatientData($form);

        if (count($errors) > 0) {
            return [
                'success' => false,
                'not_found' => false,
                'errors' => $errors,
                'form' => $form,
            ];
        }

        $this->patientRepository->update(
            patientId: $patientId,
            data: $normalised,
            updatedBy: $userId
        );

        $this->auditService->recordPatientUpdated(
            patientId: $patientId,
            userId: $userId
        );

        return [
            'success' => true,
            'not_found' => false,
            'errors' => [],
            'form' => $form,
        ];
    }

    public function deactivatePatient(int $patientId, int $userId): bool
    {
        $deactivated = $this->patientRepository->deactivate(
            patientId: $patientId,
            updatedBy: $userId
        );

        if ($deactivated) {
            $this->auditService->recordPatientDeactivated(
                patientId: $patientId,
                userId: $userId
            );
        }

        return $deactivated;
    }

    public function countActivePatients(): int
    {
        return $this->patientRepository->countActivePatients();
    }

    private function inputToForm(array $input): array
    {
        $form = $this->emptyForm();

        foreach ($form as $key => $_) {
            $form[$key] = trim((string) ($input[$key] ?? ''));
        }

        return $form;
    }

    private function normalisePatientData(array $form): array
    {
        $dateOfBirth = clinical_valid_date_or_null((string) ($form['date_of_birth'] ?? ''));

        return [
            'first_name' => trim((string) ($form['first_name'] ?? '')),
            'last_name' => trim((string) ($form['last_name'] ?? '')),
            'date_of_birth' => $dateOfBirth,

            'phone' => clinical_nullable_string((string) ($form['phone'] ?? '')),
            'email' => clinical_normalise_email((string) ($form['email'] ?? '')),

            'address_line_1' => clinical_nullable_string((string) ($form['address_line_1'] ?? '')),
            'address_line_2' => clinical_nullable_string((string) ($form['address_line_2'] ?? '')),
            'town' => clinical_nullable_string((string) ($form['town'] ?? '')),
            'county' => clinical_nullable_string((string) ($form['county'] ?? '')),
            'postcode' => clinical_normalise_postcode((string) ($form['postcode'] ?? '')),

            'relevant_medical_notes' => clinical_nullable_string((string) ($form['relevant_medical_notes'] ?? '')),
        ];
    }
}

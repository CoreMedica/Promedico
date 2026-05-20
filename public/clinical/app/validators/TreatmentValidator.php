<?php

declare(strict_types=1);

/**
 * Promedico Clinical App Treatment Validator
 * Path: public/clinical/app/validators/TreatmentValidator.php
 *
 * Responsible only for validating treatment form input.
 */

final class TreatmentValidator
{
    private const VALID_LOCATION_TYPES = [
        'clinic',
        'home_visit',
        'other',
    ];

    private const VALID_TREATMENT_TYPES = [
        'microsuction',
        'irrigation',
        'manual_removal',
        'combined',
        'assessment_only',
        'other',
    ];

    public function validate(array $input): array
    {
        $errors = [];

        $patientId = (int) ($input['patient_id'] ?? 0);
        $treatmentDate = trim((string) ($input['treatment_date'] ?? ''));
        $treatmentTime = trim((string) ($input['treatment_time'] ?? ''));
        $locationType = trim((string) ($input['location_type'] ?? ''));
        $locationName = trim((string) ($input['location_name'] ?? ''));
        $treatmentType = trim((string) ($input['treatment_type'] ?? ''));

        $consentConfirmed = isset($input['consent_confirmed']) ? 1 : 0;
        $contraindicationsChecked = isset($input['contraindications_checked']) ? 1 : 0;

        if ($patientId < 1) {
            $errors[] = 'A valid patient must be selected.';
        }

        if ($treatmentDate === '') {
            $errors[] = 'Treatment date is required.';
        } elseif (!$this->isValidDate($treatmentDate)) {
            $errors[] = 'Treatment date must be a valid date.';
        }

        if ($treatmentTime !== '' && !$this->isValidTime($treatmentTime)) {
            $errors[] = 'Treatment time must be a valid time.';
        }

        if ($locationType === '') {
            $errors[] = 'Location type is required.';
        } elseif (!in_array($locationType, self::VALID_LOCATION_TYPES, true)) {
            $errors[] = 'Location type is not valid.';
        }

        if ($treatmentType === '') {
            $errors[] = 'Treatment type is required.';
        } elseif (!in_array($treatmentType, self::VALID_TREATMENT_TYPES, true)) {
            $errors[] = 'Treatment type is not valid.';
        }

        if ($consentConfirmed !== 1) {
            $errors[] = 'Consent must be confirmed before saving a treatment note.';
        }

        if ($contraindicationsChecked !== 1) {
            $errors[] = 'Contraindications must be checked before saving a treatment note.';
        }

        $maxLengths = [
            'fresha_appointment_reference' => ['Fresha appointment reference', 120],
            'location_name' => ['Location name', 190],
        ];

        foreach ($maxLengths as $field => [$label, $maxLength]) {
            $value = trim((string) ($input[$field] ?? ''));

            if ($value !== '' && mb_strlen($value) > $maxLength) {
                $errors[] = $label . ' must be ' . $maxLength . ' characters or fewer.';
            }
        }

        $textFields = [
            'left_ear_findings' => 'Left ear findings',
            'right_ear_findings' => 'Right ear findings',
            'procedure_notes' => 'Procedure notes',
            'outcome' => 'Outcome',
            'aftercare_given' => 'Aftercare given',
            'follow_up_notes' => 'Follow-up notes',
        ];

        foreach ($textFields as $field => $label) {
            $value = trim((string) ($input[$field] ?? ''));

            if ($value !== '' && mb_strlen($value) > 10000) {
                $errors[] = $label . ' is too long.';
            }
        }

        /*
         * Treatment notes should contain some clinical content.
         * Do not allow a record that only has date/type/checkboxes.
         */
        if (
            trim((string) ($input['left_ear_findings'] ?? '')) === '' &&
            trim((string) ($input['right_ear_findings'] ?? '')) === '' &&
            trim((string) ($input['procedure_notes'] ?? '')) === '' &&
            trim((string) ($input['outcome'] ?? '')) === ''
        ) {
            $errors[] = 'At least one findings, procedure, or outcome field must be completed.';
        }

        /*
         * If follow-up is ticked, require follow-up notes.
         */
        if (
            isset($input['follow_up_required']) &&
            trim((string) ($input['follow_up_notes'] ?? '')) === ''
        ) {
            $errors[] = 'Follow-up notes are required when follow-up is marked as required.';
        }

        return $errors;
    }

    public function validateAddendum(array $input): array
    {
        $errors = [];

        $reason = trim((string) ($input['reason'] ?? ''));
        $addendumText = trim((string) ($input['addendum_text'] ?? ''));

        if ($reason === '') {
            $errors[] = 'Reason for addendum is required.';
        }

        if ($addendumText === '') {
            $errors[] = 'Addendum text is required.';
        }

        if ($reason !== '' && mb_strlen($reason) > 255) {
            $errors[] = 'Reason must be 255 characters or fewer.';
        }

        if ($addendumText !== '' && mb_strlen($addendumText) > 10000) {
            $errors[] = 'Addendum text is too long.';
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

    private function isValidTime(string $time): bool
    {
        $dt = DateTime::createFromFormat('H:i', $time);

        return $dt !== false && $dt->format('H:i') === $time;
    }
}

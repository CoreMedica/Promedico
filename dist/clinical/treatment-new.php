<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

clinical_require_login();

$pageTitle = 'New Treatment Note';
$activeNav = 'new-treatment';

$currentUser = clinical_current_user();

if ($currentUser === null) {
    clinical_redirect_to_login();
}

$patientId = clinical_get_int('patient_id');

if ($patientId === null || $patientId < 1) {
    http_response_code(400);
    exit('A valid patient ID is required.');
}

$patientService = clinical_patient_service();
$treatmentService = clinical_treatment_service();

$patient = $patientService->getPatientForEdit($patientId);

if ($patient === null) {
    http_response_code(404);
    exit('Patient not found.');
}

$errors = [];
$form = $treatmentService->emptyForm($patientId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clinical_verify_csrf_or_fail();

    $result = $treatmentService->createTreatment(
        input: $_POST,
        userId: (int) $currentUser['id']
    );

    if ($result['success'] === true) {
        clinical_rotate_csrf_token();

        clinical_redirect('/clinical/treatment-view.php?id=' . (int) $result['treatment_id'] . '&created=1');
    }

    $errors = $result['errors'];
    $form = $result['form'];
}

$patientName = $patient['first_name'] . ' ' . $patient['last_name'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="clinical-container clinical-stack clinical-stack--xl">
    <section class="clinical-page-header">
        <div class="clinical-page-header__content">
            <p class="clinical-eyebrow">Treatment notes</p>
            <h1 class="clinical-title">New treatment note</h1>
            <p class="clinical-subtitle">
                Record a locked treatment note for <?= clinical_escape($patientName) ?>.
                Corrections after saving should be made by addendum, not by editing the original note.
            </p>
        </div>

        <div class="clinical-button-row">
            <a class="clinical-button clinical-button--secondary" href="/clinical/patient-view.php?id=<?= (int) $patientId ?>">
                Back to patient
            </a>
        </div>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">Patient</h2>
            <p class="clinical-card__subtitle">
                Confirm you are adding the note to the correct patient record.
            </p>
        </div>

        <div class="clinical-card__body">
            <dl class="clinical-definition-list">
                <dt>Name</dt>
                <dd><?= clinical_escape($patientName) ?></dd>

                <dt>Date of birth</dt>
                <dd><?= clinical_format_date($patient['date_of_birth']) ?></dd>

                <dt>Phone</dt>
                <dd><?= clinical_display($patient['phone']) ?></dd>

                <dt>Postcode</dt>
                <dd><?= clinical_display($patient['postcode']) ?></dd>
            </dl>
        </div>
    </section>

    <?php if (count($errors) > 0): ?>
        <div class="clinical-alert clinical-alert--danger" role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?= clinical_escape($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="clinical-alert clinical-alert--warning" role="status">
        <p><strong>Clinical record warning:</strong> treatment notes are locked once saved.</p>
        <p>Check the patient, date, consent, findings, procedure and outcome before submitting.</p>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">Treatment details</h2>
            <p class="clinical-card__subtitle">
                Complete the fields relevant to the assessment or procedure performed.
            </p>
        </div>

        <div class="clinical-card__body">
            <form class="clinical-form" method="post" action="/clinical/treatment-new.php?patient_id=<?= (int) $patientId ?>" novalidate>
                <?= clinical_csrf_field(); ?>

                <input type="hidden" name="patient_id" value="<?= (int) $patientId ?>">

                <div class="clinical-form-grid">
                    <div class="clinical-form-field">
                        <label class="clinical-label" for="treatment_date">Treatment date</label>
                        <input
                            class="clinical-input"
                            type="date"
                            id="treatment_date"
                            name="treatment_date"
                            value="<?= clinical_escape($form['treatment_date']) ?>"
                            required>
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="treatment_time">Treatment time</label>
                        <input
                            class="clinical-input"
                            type="time"
                            id="treatment_time"
                            name="treatment_time"
                            value="<?= clinical_escape($form['treatment_time']) ?>">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="location_type">Location type</label>
                        <select class="clinical-select" id="location_type" name="location_type" required>
                            <option value="clinic" <?= $form['location_type'] === 'clinic' ? 'selected' : '' ?>>Clinic</option>
                            <option value="home_visit" <?= $form['location_type'] === 'home_visit' ? 'selected' : '' ?>>Home visit</option>
                            <option value="other" <?= $form['location_type'] === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="location_name">Location name</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="location_name"
                            name="location_name"
                            value="<?= clinical_escape($form['location_name']) ?>"
                            placeholder="e.g. Portsmouth clinic, patient home">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="treatment_type">Treatment type</label>
                        <select class="clinical-select" id="treatment_type" name="treatment_type" required>
                            <option value="microsuction" <?= $form['treatment_type'] === 'microsuction' ? 'selected' : '' ?>>Microsuction</option>
                            <option value="irrigation" <?= $form['treatment_type'] === 'irrigation' ? 'selected' : '' ?>>Irrigation</option>
                            <option value="manual_removal" <?= $form['treatment_type'] === 'manual_removal' ? 'selected' : '' ?>>Manual removal</option>
                            <option value="combined" <?= $form['treatment_type'] === 'combined' ? 'selected' : '' ?>>Combined</option>
                            <option value="assessment_only" <?= $form['treatment_type'] === 'assessment_only' ? 'selected' : '' ?>>Assessment only</option>
                            <option value="other" <?= $form['treatment_type'] === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="fresha_appointment_reference">Fresha appointment reference</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="fresha_appointment_reference"
                            name="fresha_appointment_reference"
                            value="<?= clinical_escape($form['fresha_appointment_reference']) ?>"
                            placeholder="Optional">
                    </div>

                    <div class="clinical-form-field clinical-form-field--full">
                        <div class="clinical-checkbox-group">
                            <label class="clinical-check">
                                <input
                                    type="checkbox"
                                    name="consent_confirmed"
                                    value="1"
                                    <?= $form['consent_confirmed'] === '1' ? 'checked' : '' ?>>
                                Consent confirmed
                            </label>

                            <label class="clinical-check">
                                <input
                                    type="checkbox"
                                    name="contraindications_checked"
                                    value="1"
                                    <?= $form['contraindications_checked'] === '1' ? 'checked' : '' ?>>
                                Contraindications checked
                            </label>
                        </div>
                    </div>

                    <div class="clinical-form-field clinical-form-field--full">
                        <label class="clinical-label" for="left_ear_findings">Left ear findings</label>
                        <textarea
                            class="clinical-textarea"
                            id="left_ear_findings"
                            name="left_ear_findings"
                            rows="5"><?= clinical_escape($form['left_ear_findings']) ?></textarea>
                    </div>

                    <div class="clinical-form-field clinical-form-field--full">
                        <label class="clinical-label" for="right_ear_findings">Right ear findings</label>
                        <textarea
                            class="clinical-textarea"
                            id="right_ear_findings"
                            name="right_ear_findings"
                            rows="5"><?= clinical_escape($form['right_ear_findings']) ?></textarea>
                    </div>

                    <div class="clinical-form-field clinical-form-field--full">
                        <label class="clinical-label" for="procedure_notes">Procedure notes</label>
                        <textarea
                            class="clinical-textarea"
                            id="procedure_notes"
                            name="procedure_notes"
                            rows="6"><?= clinical_escape($form['procedure_notes']) ?></textarea>
                    </div>

                    <div class="clinical-form-field clinical-form-field--full">
                        <label class="clinical-label" for="outcome">Outcome</label>
                        <textarea
                            class="clinical-textarea"
                            id="outcome"
                            name="outcome"
                            rows="4"><?= clinical_escape($form['outcome']) ?></textarea>
                    </div>

                    <div class="clinical-form-field clinical-form-field--full">
                        <label class="clinical-label" for="aftercare_given">Aftercare given</label>
                        <textarea
                            class="clinical-textarea"
                            id="aftercare_given"
                            name="aftercare_given"
                            rows="4"><?= clinical_escape($form['aftercare_given']) ?></textarea>
                    </div>

                    <div class="clinical-form-field clinical-form-field--full">
                        <label class="clinical-check">
                            <input
                                type="checkbox"
                                name="follow_up_required"
                                value="1"
                                <?= $form['follow_up_required'] === '1' ? 'checked' : '' ?>>
                            Follow-up required
                        </label>
                    </div>

                    <div class="clinical-form-field clinical-form-field--full">
                        <label class="clinical-label" for="follow_up_notes">Follow-up notes</label>
                        <textarea
                            class="clinical-textarea"
                            id="follow_up_notes"
                            name="follow_up_notes"
                            rows="4"><?= clinical_escape($form['follow_up_notes']) ?></textarea>
                    </div>
                </div>

                <div class="clinical-button-row">
                    <button class="clinical-button" type="submit">Save locked treatment note</button>

                    <a class="clinical-button clinical-button--secondary" href="/clinical/patient-view.php?id=<?= (int) $patientId ?>">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
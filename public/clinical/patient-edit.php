<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

clinical_require_login();

$pageTitle = 'Edit Patient';
$activeNav = 'patients';

$currentUser = clinical_current_user();

if ($currentUser === null) {
    clinical_redirect_to_login();
}

$patientId = clinical_get_int('id');

if ($patientId === null || $patientId < 1) {
    http_response_code(400);
    exit('Invalid patient ID.');
}

$patientService = clinical_patient_service();

$patient = $patientService->getPatientForEdit($patientId);

if ($patient === null) {
    http_response_code(404);
    exit('Patient not found.');
}

$errors = [];
$form = $patientService->patientToForm($patient);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clinical_verify_csrf_or_fail();

    $result = $patientService->updatePatient(
        patientId: $patientId,
        input: $_POST,
        userId: (int) $currentUser['id']
    );

    if ($result['success'] === true) {
        clinical_rotate_csrf_token();

        clinical_redirect('/clinical/patient-view.php?id=' . $patientId . '&updated=1');
    }

    if (($result['not_found'] ?? false) === true) {
        http_response_code(404);
        exit('Patient not found.');
    }

    $errors = $result['errors'];
    $form = $result['form'];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="clinical-container clinical-stack clinical-stack--xl">
    <section class="clinical-page-header">
        <div class="clinical-page-header__content">
            <p class="clinical-eyebrow">Patient records</p>
            <h1 class="clinical-title">Edit patient</h1>
            <p class="clinical-subtitle">
                Update patient demographic and contact details. Treatment notes are not edited here.
            </p>
        </div>

        <div class="clinical-button-row">
            <a class="clinical-button clinical-button--secondary" href="/clinical/patient-view.php?id=<?= (int) $patientId ?>">
                Back to patient
            </a>
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
        <p><strong>Scope warning:</strong> this page is for correcting patient details only.</p>
        <p>Treatment records should remain locked. Corrections to treatment notes should be handled by addenda.</p>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">Patient details</h2>
            <p class="clinical-card__subtitle">
                Keep details accurate for identification, follow-up, and safe treatment.
            </p>
        </div>

        <div class="clinical-card__body">
            <form class="clinical-form" method="post" action="/clinical/patient-edit.php?id=<?= (int) $patientId ?>" novalidate>
                <?= clinical_csrf_field(); ?>

                <div class="clinical-form-grid">
                    <div class="clinical-form-field">
                        <label class="clinical-label" for="first_name">First name</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="first_name"
                            name="first_name"
                            value="<?= clinical_escape($form['first_name']) ?>"
                            autocomplete="given-name"
                            required>
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="last_name">Last name</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="last_name"
                            name="last_name"
                            value="<?= clinical_escape($form['last_name']) ?>"
                            autocomplete="family-name"
                            required>
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="date_of_birth">Date of birth</label>
                        <input
                            class="clinical-input"
                            type="date"
                            id="date_of_birth"
                            name="date_of_birth"
                            value="<?= clinical_escape($form['date_of_birth']) ?>">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="phone">Phone</label>
                        <input
                            class="clinical-input"
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?= clinical_escape($form['phone']) ?>"
                            autocomplete="tel">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="email">Email</label>
                        <input
                            class="clinical-input"
                            type="email"
                            id="email"
                            name="email"
                            value="<?= clinical_escape($form['email']) ?>"
                            autocomplete="email">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="postcode">Postcode</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="postcode"
                            name="postcode"
                            value="<?= clinical_escape($form['postcode']) ?>"
                            autocomplete="postal-code">
                    </div>

                    <div class="clinical-form-field clinical-form-field--full">
                        <label class="clinical-label" for="address_line_1">Address line 1</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="address_line_1"
                            name="address_line_1"
                            value="<?= clinical_escape($form['address_line_1']) ?>"
                            autocomplete="address-line1">
                    </div>

                    <div class="clinical-form-field clinical-form-field--full">
                        <label class="clinical-label" for="address_line_2">Address line 2</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="address_line_2"
                            name="address_line_2"
                            value="<?= clinical_escape($form['address_line_2']) ?>"
                            autocomplete="address-line2">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="town">Town/city</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="town"
                            name="town"
                            value="<?= clinical_escape($form['town']) ?>"
                            autocomplete="address-level2">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="county">County</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="county"
                            name="county"
                            value="<?= clinical_escape($form['county']) ?>"
                            autocomplete="address-level1">
                    </div>

                    <div class="clinical-form-field clinical-form-field--full">
                        <label class="clinical-label" for="relevant_medical_notes">
                            Relevant medical notes
                            <span class="clinical-label__hint">(only what is needed for safe treatment)</span>
                        </label>
                        <textarea
                            class="clinical-textarea"
                            id="relevant_medical_notes"
                            name="relevant_medical_notes"
                            rows="5"><?= clinical_escape($form['relevant_medical_notes']) ?></textarea>
                    </div>
                </div>

                <div class="clinical-button-row">
                    <button class="clinical-button" type="submit">Save changes</button>
                    <a class="clinical-button clinical-button--secondary" href="/clinical/patient-view.php?id=<?= (int) $patientId ?>">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
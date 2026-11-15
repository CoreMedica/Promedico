<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

clinical_require_login();

$pageTitle = 'New Patient';
$activeNav = 'new-patient';

$currentUser = clinical_current_user();

if ($currentUser === null) {
    clinical_redirect_to_login();
}

$patientService = clinical_patient_service();

$errors = [];
$possibleMatches = [];
$form = $patientService->emptyForm();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clinical_verify_csrf_or_fail();

    $result = $patientService->createPatient(
        input: $_POST,
        userId: (int) $currentUser['id']
    );

    if ($result['success'] === true) {
        clinical_rotate_csrf_token();

        clinical_redirect('/clinical/patient-view.php?id=' . (int) $result['patient_id'] . '&created=1');
    }

    $errors = $result['errors'];
    $possibleMatches = $result['possible_matches'];
    $form = $result['form'];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="clinical-container clinical-stack clinical-stack--xl">
    <section class="clinical-page-header">
        <div class="clinical-page-header__content">
            <p class="clinical-eyebrow">Patient records</p>
            <h1 class="clinical-title">New patient</h1>
            <p class="clinical-subtitle">
                Create a patient record before adding treatment notes. Search existing patients first where possible to avoid duplicates.
            </p>
        </div>

        <div class="clinical-button-row">
            <a class="clinical-button clinical-button--secondary" href="/clinical/patients.php">
                Back to patients
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

    <?php if (count($possibleMatches) > 0): ?>
        <section class="clinical-alert clinical-alert--warning" role="status">
            <p><strong>Possible existing patient records found.</strong></p>
            <p>
                Check these before creating a duplicate. If this is definitely a different patient, continue saving.
            </p>

            <div class="clinical-table-wrap" style="margin-top: 1rem;">
                <table class="clinical-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>DOB</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Postcode</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($possibleMatches as $match): ?>
                            <tr>
                                <td>
                                    <?= clinical_escape($match['last_name'] . ', ' . $match['first_name']) ?>
                                </td>

                                <td>
                                    <?= clinical_format_date($match['date_of_birth']) ?>
                                </td>

                                <td>
                                    <?= clinical_display($match['phone']) ?>
                                </td>

                                <td>
                                    <?= clinical_display($match['email']) ?>
                                </td>

                                <td>
                                    <?= clinical_display($match['postcode']) ?>
                                </td>

                                <td>
                                    <a class="clinical-button clinical-button--small" href="/clinical/patient-view.php?id=<?= (int) $match['id'] ?>">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">Patient details</h2>
            <p class="clinical-card__subtitle">
                Record only information required for safe treatment and follow-up.
            </p>
        </div>

        <div class="clinical-card__body">
            <form class="clinical-form" method="post" action="/clinical/patient-new.php" novalidate>
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
                    <button class="clinical-button" type="submit">Save patient</button>
                    <a class="clinical-button clinical-button--secondary" href="/clinical/patients.php">Cancel</a>
                </div>
            </form>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

clinical_require_login();

$pageTitle = 'Add Treatment Addendum';
$activeNav = 'patients';

$currentUser = clinical_current_user();

if ($currentUser === null) {
    clinical_redirect_to_login();
}

$treatmentId = clinical_get_int('treatment_id');

if ($treatmentId === null || $treatmentId < 1) {
    http_response_code(400);
    exit('Invalid treatment ID.');
}

$treatmentService = clinical_treatment_service();

$treatment = $treatmentService->getTreatmentForView(
    treatmentId: $treatmentId,
    userId: (int) $currentUser['id']
);

if ($treatment === null) {
    http_response_code(404);
    exit('Treatment record not found.');
}

$errors = [];
$form = $treatmentService->emptyAddendumForm();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clinical_verify_csrf_or_fail();

    $result = $treatmentService->createAddendum(
        treatmentId: $treatmentId,
        input: $_POST,
        userId: (int) $currentUser['id']
    );

    if ($result['success'] === true) {
        clinical_rotate_csrf_token();

        clinical_redirect('/clinical/treatment-view.php?id=' . $treatmentId . '&addendum_created=1');
    }

    if (($result['not_found'] ?? false) === true) {
        http_response_code(404);
        exit('Treatment record not found.');
    }

    $errors = $result['errors'];
    $form = $result['form'];
}

$patientName = $treatment['patient_first_name'] . ' ' . $treatment['patient_last_name'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="clinical-container clinical-stack clinical-stack--xl">
    <section class="clinical-page-header">
        <div class="clinical-page-header__content">
            <p class="clinical-eyebrow">Treatment addendum</p>
            <h1 class="clinical-title">Add addendum</h1>
            <p class="clinical-subtitle">
                Add a correction, clarification, or additional note to the locked treatment record for
                <?= clinical_escape($patientName) ?>.
            </p>
        </div>

        <div class="clinical-button-row">
            <a class="clinical-button clinical-button--secondary" href="/clinical/treatment-view.php?id=<?= (int) $treatmentId ?>">
                Back to treatment note
            </a>
        </div>
    </section>

    <section class="clinical-alert clinical-alert--warning" role="status">
        <p><strong>The original treatment note will not be edited.</strong></p>
        <p>
            This addendum will be permanently attached to the treatment record with your user account and timestamp.
        </p>
    </section>

    <?php if (count($errors) > 0): ?>
        <div class="clinical-alert clinical-alert--danger" role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?= clinical_escape($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="clinical-patient-summary">
        <article class="clinical-card">
            <div class="clinical-card__header">
                <h2 class="clinical-card__title">Patient</h2>
                <p class="clinical-card__subtitle">
                    Confirm the addendum is being added to the correct patient record.
                </p>
            </div>

            <div class="clinical-card__body">
                <dl class="clinical-definition-list">
                    <dt>Name</dt>
                    <dd><?= clinical_escape($patientName) ?></dd>

                    <dt>Date of birth</dt>
                    <dd><?= clinical_format_date($treatment['patient_date_of_birth']) ?></dd>

                    <dt>Phone</dt>
                    <dd><?= clinical_display($treatment['patient_phone']) ?></dd>

                    <dt>Postcode</dt>
                    <dd><?= clinical_display($treatment['patient_postcode']) ?></dd>
                </dl>
            </div>
        </article>

        <article class="clinical-card">
            <div class="clinical-card__header">
                <h2 class="clinical-card__title">Original treatment</h2>
                <p class="clinical-card__subtitle">
                    Summary of the locked record this addendum will attach to.
                </p>
            </div>

            <div class="clinical-card__body">
                <dl class="clinical-definition-list">
                    <dt>Treatment date</dt>
                    <dd><?= clinical_format_date($treatment['treatment_date']) ?></dd>

                    <dt>Treatment time</dt>
                    <dd><?= clinical_format_time($treatment['treatment_time']) ?></dd>

                    <dt>Treatment type</dt>
                    <dd><?= clinical_display_enum($treatment['treatment_type']) ?></dd>

                    <dt>Location</dt>
                    <dd>
                        <?= clinical_display_enum($treatment['location_type']) ?>
                        <?php if (!empty($treatment['location_name'])): ?>
                            — <?= clinical_escape($treatment['location_name']) ?>
                        <?php endif; ?>
                    </dd>

                    <dt>Practitioner</dt>
                    <dd><?= clinical_display($treatment['practitioner_name']) ?></dd>

                    <dt>Created</dt>
                    <dd><?= clinical_format_datetime($treatment['created_at']) ?></dd>
                </dl>
            </div>
        </article>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">Addendum details</h2>
            <p class="clinical-card__subtitle">
                Be specific. Do not duplicate the original note unless clarification is required.
            </p>
        </div>

        <div class="clinical-card__body">
            <form class="clinical-form" method="post" action="/clinical/treatment-addendum.php?treatment_id=<?= (int) $treatmentId ?>" novalidate>
                <?= clinical_csrf_field(); ?>

                <div class="clinical-form-grid">
                    <div class="clinical-form-field clinical-form-field--full">
                        <label class="clinical-label" for="reason">Reason for addendum</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="reason"
                            name="reason"
                            value="<?= clinical_escape($form['reason']) ?>"
                            placeholder="e.g. Correction, clarification, follow-up information"
                            required>
                    </div>

                    <div class="clinical-form-field clinical-form-field--full">
                        <label class="clinical-label" for="addendum_text">Addendum text</label>
                        <textarea
                            class="clinical-textarea"
                            id="addendum_text"
                            name="addendum_text"
                            rows="8"
                            required><?= clinical_escape($form['addendum_text']) ?></textarea>
                    </div>
                </div>

                <div class="clinical-button-row">
                    <button class="clinical-button" type="submit">
                        Save addendum
                    </button>

                    <a class="clinical-button clinical-button--secondary" href="/clinical/treatment-view.php?id=<?= (int) $treatmentId ?>">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

clinical_require_login();

$pageTitle = 'Complete Follow-up';
$activeNav = 'follow-ups';

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
$form = [
    'completion_notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clinical_verify_csrf_or_fail();

    $result = $treatmentService->completeFollowUp(
        treatmentId: $treatmentId,
        input: $_POST,
        userId: (int) $currentUser['id']
    );

    if ($result['success'] === true) {
        clinical_rotate_csrf_token();

        clinical_redirect('/clinical/follow-ups.php?completed=1');
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
            <p class="clinical-eyebrow">Follow-up</p>
            <h1 class="clinical-title">Complete follow-up</h1>
            <p class="clinical-subtitle">
                Mark the follow-up action as completed for <?= clinical_escape($patientName) ?>.
            </p>
        </div>

        <div class="clinical-button-row">
            <a class="clinical-button clinical-button--secondary" href="/clinical/follow-ups.php">
                Back to follow-ups
            </a>
        </div>
    </section>

    <?php if (!empty($treatment['follow_up_completed_at'])): ?>
        <section class="clinical-alert clinical-alert--info" role="status">
            <p><strong>This follow-up has already been completed.</strong></p>
            <p>
                Completed: <?= clinical_format_datetime($treatment['follow_up_completed_at']) ?>
            </p>
        </section>
    <?php endif; ?>

    <?php if ((int) $treatment['follow_up_required'] !== 1): ?>
        <section class="clinical-alert clinical-alert--warning" role="status">
            <p>This treatment was not marked as requiring follow-up.</p>
        </section>
    <?php endif; ?>

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
            </div>

            <div class="clinical-card__body">
                <dl class="clinical-definition-list">
                    <dt>Name</dt>
                    <dd><?= clinical_escape($patientName) ?></dd>

                    <dt>Date of birth</dt>
                    <dd><?= clinical_format_date($treatment['patient_date_of_birth']) ?></dd>

                    <dt>Phone</dt>
                    <dd><?= clinical_display($treatment['patient_phone']) ?></dd>

                    <dt>Email</dt>
                    <dd><?= clinical_display($treatment['patient_email']) ?></dd>
                </dl>
            </div>
        </article>

        <article class="clinical-card">
            <div class="clinical-card__header">
                <h2 class="clinical-card__title">Original follow-up requirement</h2>
            </div>

            <div class="clinical-card__body clinical-stack">
                <dl class="clinical-definition-list">
                    <dt>Treatment date</dt>
                    <dd><?= clinical_format_date($treatment['treatment_date']) ?></dd>

                    <dt>Treatment type</dt>
                    <dd><?= clinical_display_enum($treatment['treatment_type']) ?></dd>

                    <dt>Practitioner</dt>
                    <dd><?= clinical_display($treatment['practitioner_name']) ?></dd>
                </dl>

                <div>
                    <h3 class="clinical-record-section__title">Follow-up notes</h3>
                    <div class="clinical-note-box">
                        <?= clinical_display($treatment['follow_up_notes']) ?>
                    </div>
                </div>
            </div>
        </article>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">Completion details</h2>
            <p class="clinical-card__subtitle">
                Record what was done. This does not edit the original treatment note.
            </p>
        </div>

        <div class="clinical-card__body">
            <form class="clinical-form" method="post" action="/clinical/follow-up-complete.php?treatment_id=<?= (int) $treatmentId ?>">
                <?= clinical_csrf_field(); ?>

                <div class="clinical-form-field">
                    <label class="clinical-label" for="completion_notes">Completion notes</label>
                    <textarea
                        class="clinical-textarea"
                        id="completion_notes"
                        name="completion_notes"
                        rows="6"
                        placeholder="e.g. Called patient, symptoms resolved, advised to rebook if symptoms return."><?= clinical_escape($form['completion_notes']) ?></textarea>
                </div>

                <div class="clinical-button-row">
                    <?php if ((int) $treatment['follow_up_required'] === 1 && empty($treatment['follow_up_completed_at'])): ?>
                        <button class="clinical-button" type="submit">
                            Mark follow-up completed
                        </button>
                    <?php endif; ?>

                    <a class="clinical-button clinical-button--secondary" href="/clinical/treatment-view.php?id=<?= (int) $treatmentId ?>">
                        View treatment note
                    </a>

                    <a class="clinical-button clinical-button--secondary" href="/clinical/follow-ups.php">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
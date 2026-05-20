<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

clinical_require_login();

$pageTitle = 'Treatment Note';
$activeNav = 'patients';

$currentUser = clinical_current_user();

if ($currentUser === null) {
    clinical_redirect_to_login();
}

$treatmentId = clinical_get_int('id');

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

$addenda = $treatmentService->getAddendaForTreatment($treatmentId);

$created = (string) ($_GET['created'] ?? '') === '1';
$addendumCreated = (string) ($_GET['addendum_created'] ?? '') === '1';

$patientName = $treatment['patient_first_name'] . ' ' . $treatment['patient_last_name'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="clinical-container clinical-stack clinical-stack--xl">
    <?php if ($created): ?>
        <div class="clinical-alert clinical-alert--success" role="status">
            <p>Treatment note created successfully.</p>
        </div>
    <?php endif; ?>

    <?php if ($addendumCreated): ?>
        <div class="clinical-alert clinical-alert--success" role="status">
            <p>Addendum added successfully.</p>
        </div>
    <?php endif; ?>

    <section class="clinical-page-header">
        <div class="clinical-page-header__content">
            <p class="clinical-eyebrow">Locked treatment note</p>
            <h1 class="clinical-title">
                <?= clinical_escape(clinical_label_from_enum($treatment['treatment_type'])) ?>
                · <?= clinical_format_date($treatment['treatment_date']) ?>
            </h1>
            <p class="clinical-subtitle">
                Read-only clinical record for <?= clinical_escape($patientName) ?>.
                Corrections or clarifications should be recorded as addenda.
            </p>
        </div>

        <div class="clinical-button-row">
            <a class="clinical-button" href="/clinical/treatment-addendum.php?treatment_id=<?= (int) $treatment['id'] ?>">
                Add addendum
            </a>

            <a class="clinical-button clinical-button--secondary" href="/clinical/patient-view.php?id=<?= (int) $treatment['patient_id'] ?>">
                Back to patient
            </a>
        </div>
    </section>

    <section class="clinical-alert clinical-alert--info" role="status">
        <p><strong>This treatment note is locked.</strong></p>
        <p>The original record should not be edited. Use addenda for corrections, clarification, or additional context.</p>
    </section>

    <section class="clinical-patient-summary">
        <article class="clinical-card">
            <div class="clinical-card__header">
                <h2 class="clinical-card__title">Patient</h2>
                <p class="clinical-card__subtitle">
                    Patient linked to this treatment note.
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

                    <dt>Email</dt>
                    <dd><?= clinical_display($treatment['patient_email']) ?></dd>

                    <dt>Postcode</dt>
                    <dd><?= clinical_display($treatment['patient_postcode']) ?></dd>
                </dl>
            </div>
        </article>

        <article class="clinical-card">
            <div class="clinical-card__header">
                <h2 class="clinical-card__title">Treatment metadata</h2>
                <p class="clinical-card__subtitle">
                    Appointment and record context.
                </p>
            </div>

            <div class="clinical-card__body">
                <dl class="clinical-definition-list">
                    <dt>Treatment date</dt>
                    <dd><?= clinical_format_date($treatment['treatment_date']) ?></dd>

                    <dt>Treatment time</dt>
                    <dd><?= clinical_format_time($treatment['treatment_time']) ?></dd>

                    <dt>Location type</dt>
                    <dd><?= clinical_display_enum($treatment['location_type']) ?></dd>

                    <dt>Location name</dt>
                    <dd><?= clinical_display($treatment['location_name']) ?></dd>

                    <dt>Treatment type</dt>
                    <dd><?= clinical_display_enum($treatment['treatment_type']) ?></dd>

                    <dt>Fresha reference</dt>
                    <dd><?= clinical_display($treatment['fresha_appointment_reference']) ?></dd>

                    <dt>Practitioner</dt>
                    <dd><?= clinical_display($treatment['practitioner_name']) ?></dd>

                    <dt>Created by</dt>
                    <dd><?= clinical_display($treatment['created_by_name']) ?></dd>

                    <dt>Created</dt>
                    <dd><?= clinical_format_datetime($treatment['created_at']) ?></dd>

                    <dt>Locked</dt>
                    <dd><?= clinical_bool_badge($treatment['is_locked']) ?></dd>
                </dl>
            </div>
        </article>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">Clinical checks</h2>
            <p class="clinical-card__subtitle">
                Consent and contraindication checks recorded at the time of treatment.
            </p>
        </div>

        <div class="clinical-card__body">
            <dl class="clinical-definition-list">
                <dt>Consent confirmed</dt>
                <dd><?= clinical_bool_badge($treatment['consent_confirmed']) ?></dd>

                <dt>Contraindications checked</dt>
                <dd><?= clinical_bool_badge($treatment['contraindications_checked']) ?></dd>

                <dt>Follow-up required</dt>
                <dd><?= clinical_bool_badge($treatment['follow_up_required']) ?></dd>
            </dl>
        </div>
    </section>

    <section class="clinical-record">
        <article class="clinical-record-section">
            <h2 class="clinical-record-section__title">Left ear findings</h2>
            <div class="clinical-note-box"><?= clinical_display($treatment['left_ear_findings']) ?></div>
        </article>

        <article class="clinical-record-section">
            <h2 class="clinical-record-section__title">Right ear findings</h2>
            <div class="clinical-note-box"><?= clinical_display($treatment['right_ear_findings']) ?></div>
        </article>

        <article class="clinical-record-section">
            <h2 class="clinical-record-section__title">Procedure notes</h2>
            <div class="clinical-note-box"><?= clinical_display($treatment['procedure_notes']) ?></div>
        </article>

        <article class="clinical-record-section">
            <h2 class="clinical-record-section__title">Outcome</h2>
            <div class="clinical-note-box"><?= clinical_display($treatment['outcome']) ?></div>
        </article>

        <article class="clinical-record-section">
            <h2 class="clinical-record-section__title">Aftercare given</h2>
            <div class="clinical-note-box"><?= clinical_display($treatment['aftercare_given']) ?></div>
        </article>

        <article class="clinical-record-section">
            <h2 class="clinical-record-section__title">Follow-up notes</h2>
            <div class="clinical-note-box"><?= clinical_display($treatment['follow_up_notes']) ?></div>
        </article>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">Addenda</h2>
            <p class="clinical-card__subtitle">
                Corrections, clarifications, or additional context added after the original note was saved.
            </p>
        </div>

        <div class="clinical-card__body">
            <?php if (count($addenda) === 0): ?>
                <div class="clinical-empty">
                    <h3 class="clinical-empty__title">No addenda recorded</h3>
                    <p class="clinical-empty__text">
                        If the treatment note needs clarification or correction, add an addendum rather than editing the original note.
                    </p>

                    <div class="clinical-button-row" style="justify-content: center; margin-top: 1rem;">
                        <a class="clinical-button" href="/clinical/treatment-addendum.php?treatment_id=<?= (int) $treatment['id'] ?>">
                            Add addendum
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="clinical-stack">
                    <?php foreach ($addenda as $addendum): ?>
                        <article class="clinical-addendum">
                            <p class="clinical-addendum__meta">
                                <?= clinical_format_datetime($addendum['created_at']) ?>
                                · <?= clinical_display($addendum['user_name']) ?>
                            </p>

                            <p><strong>Reason:</strong> <?= clinical_escape($addendum['reason']) ?></p>

                            <div class="clinical-note-box">
                                <?= clinical_escape($addendum['addendum_text']) ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

clinical_require_login();

$pageTitle = 'Patient Record';
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

$patient = $patientService->getPatientForView(
    patientId: $patientId,
    userId: (int) $currentUser['id']
);

if ($patient === null) {
    http_response_code(404);
    exit('Patient not found.');
}

$pdo = clinical_pdo();

$treatmentsStmt = $pdo->prepare(
    'SELECT
        t.id,
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

$treatmentsStmt->execute([
    'patient_id' => $patientId,
]);

$treatments = $treatmentsStmt->fetchAll();

$created = (string) ($_GET['created'] ?? '') === '1';
$updated = (string) ($_GET['updated'] ?? '') === '1';

$patientName = $patient['first_name'] . ' ' . $patient['last_name'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="clinical-container clinical-stack clinical-stack--xl">
    <?php if ($created): ?>
        <div class="clinical-alert clinical-alert--success" role="status">
            <p>Patient record created successfully.</p>
        </div>
    <?php endif; ?>

    <?php if ($updated): ?>
        <div class="clinical-alert clinical-alert--success" role="status">
            <p>Patient record updated successfully.</p>
        </div>
    <?php endif; ?>

    <section class="clinical-page-header">
        <div class="clinical-page-header__content">
            <p class="clinical-eyebrow">Patient record</p>
            <h1 class="clinical-title"><?= clinical_escape($patientName) ?></h1>
            <p class="clinical-subtitle">
                Patient details and treatment history.
            </p>
        </div>

        <div class="clinical-button-row">
            <a class="clinical-button" href="/clinical/treatment-new.php?patient_id=<?= (int) $patient['id'] ?>">
                Add treatment note
            </a>

            <a class="clinical-button clinical-button--secondary" href="/clinical/patient-edit.php?id=<?= (int) $patient['id'] ?>">
                Edit patient
            </a>

            <a class="clinical-button clinical-button--secondary" href="/clinical/patients.php">
                Back to patients
            </a>
        </div>
    </section>

    <section class="clinical-patient-summary">
        <article class="clinical-card">
            <div class="clinical-card__header">
                <h2 class="clinical-card__title">Patient details</h2>
                <p class="clinical-card__subtitle">
                    Core identity and contact details.
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

                    <dt>Email</dt>
                    <dd><?= clinical_display($patient['email']) ?></dd>

                    <dt>Postcode</dt>
                    <dd><?= clinical_display($patient['postcode']) ?></dd>
                </dl>
            </div>
        </article>

        <article class="clinical-card">
            <div class="clinical-card__header">
                <h2 class="clinical-card__title">Address and relevant notes</h2>
                <p class="clinical-card__subtitle">
                    Only record information relevant to care, treatment, or follow-up.
                </p>
            </div>

            <div class="clinical-card__body clinical-stack">
                <div>
                    <h3 class="clinical-record-section__title">Address</h3>

                    <p>
                        <?= clinical_display($patient['address_line_1']) ?><br>

                        <?php if (!empty($patient['address_line_2'])): ?>
                            <?= clinical_escape($patient['address_line_2']) ?><br>
                        <?php endif; ?>

                        <?= clinical_display($patient['town']) ?><br>
                        <?= clinical_display($patient['county']) ?><br>
                        <?= clinical_display($patient['postcode']) ?>
                    </p>
                </div>

                <div>
                    <h3 class="clinical-record-section__title">Relevant medical notes</h3>

                    <?php if (!empty($patient['relevant_medical_notes'])): ?>
                        <div class="clinical-note-box">
                            <?= clinical_escape($patient['relevant_medical_notes']) ?>
                        </div>
                    <?php else: ?>
                        <p class="clinical-muted">No relevant medical notes recorded.</p>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">Record metadata</h2>
            <p class="clinical-card__subtitle">
                Internal audit context for this patient record.
            </p>
        </div>

        <div class="clinical-card__body">
            <dl class="clinical-definition-list">
                <dt>Created</dt>
                <dd><?= clinical_format_datetime($patient['created_at']) ?></dd>

                <dt>Created by</dt>
                <dd><?= clinical_display($patient['created_by_name']) ?></dd>

                <dt>Last updated</dt>
                <dd><?= clinical_format_datetime($patient['updated_at']) ?></dd>

                <dt>Updated by</dt>
                <dd><?= clinical_display($patient['updated_by_name']) ?></dd>
            </dl>
        </div>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">Treatment history</h2>
            <p class="clinical-card__subtitle">
                Treatment notes are listed newest first.
            </p>
        </div>

        <div class="clinical-card__body">
            <?php if (count($treatments) === 0): ?>
                <div class="clinical-empty">
                    <h3 class="clinical-empty__title">No treatment notes yet</h3>
                    <p class="clinical-empty__text">
                        Add the first treatment note after the patient has been assessed or treated.
                    </p>

                    <div class="clinical-button-row" style="justify-content: center; margin-top: 1rem;">
                        <a class="clinical-button" href="/clinical/treatment-new.php?patient_id=<?= (int) $patient['id'] ?>">
                            Add treatment note
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <ul class="clinical-timeline">
                    <?php foreach ($treatments as $treatment): ?>
                        <li class="clinical-timeline__item">
                            <p class="clinical-timeline__meta">
                                <?= clinical_format_date($treatment['treatment_date']) ?>

                                <?php if (!empty($treatment['treatment_time'])): ?>
                                    · <?= clinical_format_time($treatment['treatment_time']) ?>
                                <?php endif; ?>

                                · <?= clinical_escape(clinical_label_from_enum($treatment['treatment_type'])) ?>
                            </p>

                            <h3 class="clinical-timeline__title">
                                <?= clinical_escape(clinical_label_from_enum($treatment['location_type'])) ?>

                                <?php if (!empty($treatment['location_name'])): ?>
                                    — <?= clinical_escape($treatment['location_name']) ?>
                                <?php endif; ?>
                            </h3>

                            <div class="clinical-timeline__body">
                                <p>
                                    Practitioner:
                                    <?= $treatment['practitioner_name'] ? clinical_escape($treatment['practitioner_name']) : '<span class="clinical-muted">Not recorded</span>' ?>
                                </p>

                                <?php if ((int) $treatment['follow_up_required'] === 1): ?>
                                    <p>
                                        <span class="clinical-badge clinical-badge--warning">Follow-up required</span>
                                    </p>
                                <?php endif; ?>

                                <div class="clinical-button-row">
                                    <a
                                        class="clinical-button clinical-button--small"
                                        href="/clinical/treatment-view.php?id=<?= (int) $treatment['id'] ?>">
                                        View treatment
                                    </a>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
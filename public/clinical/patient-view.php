<?php

declare(strict_types=1);

$pageTitle = 'Patient Record';
$activeNav = 'patients';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';

$currentUser = clinical_current_user();

if ($currentUser === null) {
    clinical_redirect_to_login();
}

$patientId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$patientId || $patientId < 1) {
    http_response_code(400);
    exit('Invalid patient ID.');
}

$pdo = clinical_db();

$stmt = $pdo->prepare(
    'SELECT
        p.*,
        created_user.name AS created_by_name,
        updated_user.name AS updated_by_name
     FROM patients p
     LEFT JOIN users created_user ON created_user.id = p.created_by
     LEFT JOIN users updated_user ON updated_user.id = p.updated_by
     WHERE p.id = :id
       AND p.is_active = 1
     LIMIT 1'
);

$stmt->execute([
    'id' => $patientId,
]);

$patient = $stmt->fetch();

if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

clinical_audit(
    userId: (int) $currentUser['id'],
    action: 'patient_viewed',
    entityType: 'patient',
    entityId: (int) $patientId
);

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

function patient_display(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '<span class="clinical-muted">Not recorded</span>';
    }

    return clinical_escape($value);
}

function patient_display_date(?string $date): string
{
    if ($date === null || $date === '') {
        return '<span class="clinical-muted">Not recorded</span>';
    }

    return clinical_escape(date('d/m/Y', strtotime($date)));
}

function patient_display_datetime(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return '<span class="clinical-muted">Not recorded</span>';
    }

    return clinical_escape(date('d/m/Y H:i', strtotime($datetime)));
}

function patient_label_from_enum(?string $value): string
{
    if ($value === null || $value === '') {
        return 'Not recorded';
    }

    return ucwords(str_replace('_', ' ', $value));
}

$patientName = $patient['first_name'] . ' ' . $patient['last_name'];
?>

<div class="clinical-container clinical-stack clinical-stack--xl">
    <?php if ($created): ?>
        <div class="clinical-alert clinical-alert--success" role="status">
            <p>Patient record created successfully.</p>
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
                    <dd><?= patient_display_date($patient['date_of_birth']) ?></dd>

                    <dt>Phone</dt>
                    <dd><?= patient_display($patient['phone']) ?></dd>

                    <dt>Email</dt>
                    <dd><?= patient_display($patient['email']) ?></dd>

                    <dt>Postcode</dt>
                    <dd><?= patient_display($patient['postcode']) ?></dd>
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
                        <?= patient_display($patient['address_line_1']) ?><br>
                        <?php if (!empty($patient['address_line_2'])): ?>
                            <?= clinical_escape($patient['address_line_2']) ?><br>
                        <?php endif; ?>
                        <?= patient_display($patient['town']) ?><br>
                        <?= patient_display($patient['county']) ?><br>
                        <?= patient_display($patient['postcode']) ?>
                    </p>
                </div>

                <div>
                    <h3 class="clinical-record-section__title">Relevant medical notes</h3>

                    <?php if (!empty($patient['relevant_medical_notes'])): ?>
                        <div class="clinical-note-box"><?= clinical_escape($patient['relevant_medical_notes']) ?></div>
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
                <dd><?= patient_display_datetime($patient['created_at']) ?></dd>

                <dt>Created by</dt>
                <dd><?= patient_display($patient['created_by_name']) ?></dd>

                <dt>Last updated</dt>
                <dd><?= patient_display_datetime($patient['updated_at']) ?></dd>

                <dt>Updated by</dt>
                <dd><?= patient_display($patient['updated_by_name']) ?></dd>
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
                                <?= clinical_escape(date('d/m/Y', strtotime($treatment['treatment_date']))) ?>
                                <?php if (!empty($treatment['treatment_time'])): ?>
                                    · <?= clinical_escape(substr($treatment['treatment_time'], 0, 5)) ?>
                                <?php endif; ?>
                                · <?= clinical_escape(patient_label_from_enum($treatment['treatment_type'])) ?>
                            </p>

                            <h3 class="clinical-timeline__title">
                                <?= clinical_escape(patient_label_from_enum($treatment['location_type'])) ?>
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
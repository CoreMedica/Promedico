<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

clinical_require_login();

$pageTitle = 'Follow-ups';
$activeNav = 'follow-ups';

$treatmentService = clinical_treatment_service();

$completed = (string) ($_GET['completed'] ?? '') === '1';
$view = (string) ($_GET['view'] ?? 'outstanding');

$outstandingFollowUps = $treatmentService->listOutstandingFollowUps(200);
$completedFollowUps = $view === 'completed'
    ? $treatmentService->listCompletedFollowUps(200)
    : [];

require_once __DIR__ . '/includes/header.php';
?>

<div class="clinical-container clinical-stack clinical-stack--xl">
    <?php if ($completed): ?>
        <div class="clinical-alert clinical-alert--success" role="status">
            <p>Follow-up marked as completed.</p>
        </div>
    <?php endif; ?>

    <section class="clinical-page-header">
        <div class="clinical-page-header__content">
            <p class="clinical-eyebrow">Clinical workflow</p>
            <h1 class="clinical-title">Follow-ups</h1>
            <p class="clinical-subtitle">
                Manage treatment notes where follow-up has been marked as required.
            </p>
        </div>

        <div class="clinical-button-row">
            <a class="clinical-button" href="/clinical/follow-ups.php">
                Outstanding
            </a>

            <a class="clinical-button clinical-button--secondary" href="/clinical/follow-ups.php?view=completed">
                Completed
            </a>
        </div>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">
                Outstanding follow-ups
            </h2>
            <p class="clinical-card__subtitle">
                These require action. Oldest treatment dates are shown first.
            </p>
        </div>

        <div class="clinical-card__body">
            <?php if (count($outstandingFollowUps) === 0): ?>
                <div class="clinical-empty">
                    <h3 class="clinical-empty__title">No outstanding follow-ups</h3>
                    <p class="clinical-empty__text">
                        Treatment notes marked for follow-up will appear here until completed.
                    </p>
                </div>
            <?php else: ?>
                <div class="clinical-table-wrap">
                    <table class="clinical-table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Treatment date</th>
                                <th>Treatment</th>
                                <th>Follow-up notes</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($outstandingFollowUps as $followUp): ?>
                                <?php
                                $patientName = $followUp['patient_last_name'] . ', ' . $followUp['patient_first_name'];
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= clinical_escape($patientName) ?></strong><br>
                                        <span class="clinical-muted">
                                            DOB: <?= clinical_format_date($followUp['patient_date_of_birth']) ?>
                                        </span><br>
                                        <span class="clinical-muted">
                                            Postcode: <?= clinical_display($followUp['patient_postcode']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= clinical_format_date($followUp['treatment_date']) ?>

                                        <?php if (!empty($followUp['treatment_time'])): ?>
                                            <br>
                                            <span class="clinical-muted">
                                                <?= clinical_format_time($followUp['treatment_time']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= clinical_display_enum($followUp['treatment_type']) ?><br>
                                        <span class="clinical-muted">
                                            <?= clinical_display_enum($followUp['location_type']) ?>
                                        </span>

                                        <?php if (!empty($followUp['location_name'])): ?>
                                            <br>
                                            <span class="clinical-muted">
                                                <?= clinical_escape($followUp['location_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= clinical_display($followUp['follow_up_notes']) ?>
                                    </td>

                                    <td>
                                        <?= clinical_display($followUp['patient_phone']) ?><br>
                                        <?= clinical_display($followUp['patient_email']) ?>
                                    </td>

                                    <td>
                                        <div class="clinical-table__actions">
                                            <a
                                                class="clinical-button clinical-button--small"
                                                href="/clinical/follow-up-complete.php?treatment_id=<?= (int) $followUp['id'] ?>">
                                                Complete
                                            </a>

                                            <a
                                                class="clinical-button clinical-button--secondary clinical-button--small"
                                                href="/clinical/treatment-view.php?id=<?= (int) $followUp['id'] ?>">
                                                View note
                                            </a>

                                            <a
                                                class="clinical-button clinical-button--secondary clinical-button--small"
                                                href="/clinical/patient-view.php?id=<?= (int) $followUp['patient_id'] ?>">
                                                Patient
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($view === 'completed'): ?>
        <section class="clinical-card">
            <div class="clinical-card__header">
                <h2 class="clinical-card__title">
                    Completed follow-ups
                </h2>
                <p class="clinical-card__subtitle">
                    Latest completed follow-ups, newest first.
                </p>
            </div>

            <div class="clinical-card__body">
                <?php if (count($completedFollowUps) === 0): ?>
                    <div class="clinical-empty">
                        <h3 class="clinical-empty__title">No completed follow-ups</h3>
                        <p class="clinical-empty__text">
                            Completed follow-up actions will appear here.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="clinical-table-wrap">
                        <table class="clinical-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Treatment date</th>
                                    <th>Completed</th>
                                    <th>Completion notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($completedFollowUps as $followUp): ?>
                                    <?php
                                    $patientName = $followUp['patient_last_name'] . ', ' . $followUp['patient_first_name'];
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?= clinical_escape($patientName) ?></strong><br>
                                            <span class="clinical-muted">
                                                DOB: <?= clinical_format_date($followUp['patient_date_of_birth']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= clinical_format_date($followUp['treatment_date']) ?>
                                        </td>

                                        <td>
                                            <?= clinical_format_datetime($followUp['follow_up_completed_at']) ?><br>
                                            <span class="clinical-muted">
                                                <?= clinical_display($followUp['follow_up_completed_by_name']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= clinical_display($followUp['follow_up_completion_notes']) ?>
                                        </td>

                                        <td>
                                            <div class="clinical-table__actions">
                                                <a
                                                    class="clinical-button clinical-button--small"
                                                    href="/clinical/treatment-view.php?id=<?= (int) $followUp['id'] ?>">
                                                    View note
                                                </a>

                                                <a
                                                    class="clinical-button clinical-button--secondary clinical-button--small"
                                                    href="/clinical/patient-view.php?id=<?= (int) $followUp['patient_id'] ?>">
                                                    Patient
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
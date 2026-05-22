<?php

declare(strict_types=1);

// This file is part of the Promedico clinical notes system.

require_once __DIR__ . '/includes/auth.php';

clinical_require_login();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

$patientService = clinical_patient_service();
$treatmentService = clinical_treatment_service();

$totalPatients = $patientService->countActivePatients();
$totalTreatments = $treatmentService->countTreatments();
$totalFollowUps = $treatmentService->countOutstandingFollowUps();
$latestTreatments = $treatmentService->latestTreatments(8);

require_once __DIR__ . '/includes/header.php';
?>

<div class="clinical-container clinical-stack clinical-stack--xl">
    <section class="clinical-page-header">
        <div class="clinical-page-header__content">
            <p class="clinical-eyebrow">Clinical notes</p>
            <h1 class="clinical-title">Dashboard</h1>
            <p class="clinical-subtitle">
                Staff-only treatment notes and patient history for Promedico ear care appointments.
            </p>
        </div>

        <div class="clinical-button-row">
            <a class="clinical-button" href="/clinical/patient-new.php">Add patient</a>
            <a class="clinical-button clinical-button--secondary" href="/clinical/patients.php">Find patient</a>
        </div>
    </section>

    <section class="clinical-dashboard-grid" aria-label="Clinical overview">
        <article class="clinical-stat">
            <p class="clinical-stat__label">Patients</p>
            <p class="clinical-stat__value"><?= (int) $totalPatients ?></p>
            <p class="clinical-stat__note">Active patient records</p>
        </article>

        <article class="clinical-stat">
            <p class="clinical-stat__label">Treatment notes</p>
            <p class="clinical-stat__value"><?= (int) $totalTreatments ?></p>
            <p class="clinical-stat__note">Total saved treatments</p>
        </article>

        <article class="clinical-stat">
            <p class="clinical-stat__label">Follow-ups</p>
            <p class="clinical-stat__value"><?= (int) $totalFollowUps ?></p>
            <p class="clinical-stat__note">Outstanding follow-up actions</p>
        </article>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">Recent treatment notes</h2>
            <p class="clinical-card__subtitle">
                Latest saved treatment records, newest first.
            </p>
        </div>

        <div class="clinical-card__body">
            <?php if (count($latestTreatments) === 0): ?>
                <div class="clinical-empty">
                    <h3 class="clinical-empty__title">No treatment notes yet</h3>
                    <p class="clinical-empty__text">
                        Once treatment notes are added, the latest records will appear here.
                    </p>

                    <div class="clinical-button-row" style="justify-content: center; margin-top: 1rem;">
                        <a class="clinical-button" href="/clinical/patients.php">
                            Find patient
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="clinical-table-wrap">
                    <table class="clinical-table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Treatment date</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Practitioner</th>
                                <th>Follow-up</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($latestTreatments as $treatment): ?>
                                <?php
                                $patientName = $treatment['patient_last_name'] . ', ' . $treatment['patient_first_name'];
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= clinical_escape($patientName) ?></strong>
                                    </td>

                                    <td>
                                        <?= clinical_format_date($treatment['treatment_date']) ?>

                                        <?php if (!empty($treatment['treatment_time'])): ?>
                                            <br>
                                            <span class="clinical-muted">
                                                <?= clinical_format_time($treatment['treatment_time']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= clinical_display_enum($treatment['treatment_type']) ?>
                                    </td>

                                    <td>
                                        <?= clinical_display_enum($treatment['location_type']) ?>
                                    </td>

                                    <td>
                                        <?= clinical_display($treatment['practitioner_name']) ?>
                                    </td>

                                    <td>
                                        <?php if ((int) $treatment['follow_up_required'] === 1): ?>
                                            <span class="clinical-badge clinical-badge--warning">Required</span>
                                        <?php else: ?>
                                            <span class="clinical-badge">No</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="clinical-table__actions">
                                            <a
                                                class="clinical-button clinical-button--small"
                                                href="/clinical/treatment-view.php?id=<?= (int) $treatment['id'] ?>">
                                                View note
                                            </a>

                                            <a
                                                class="clinical-button clinical-button--secondary clinical-button--small"
                                                href="/clinical/patient-view.php?id=<?= (int) $treatment['patient_id'] ?>">
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
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php

declare(strict_types=1);

// This file is part of the Promedico clinical notes system.

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

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
            <a class="clinical-button clinical-button--secondary" href="/clinical/treatment-new.php">New treatment note</a>
        </div>
    </section>

    <section class="clinical-dashboard-grid" aria-label="Clinical overview">
        <article class="clinical-stat">
            <p class="clinical-stat__label">Patients</p>
            <p class="clinical-stat__value">0</p>
            <p class="clinical-stat__note">Total patient records</p>
        </article>

        <article class="clinical-stat">
            <p class="clinical-stat__label">Treatment notes</p>
            <p class="clinical-stat__value">0</p>
            <p class="clinical-stat__note">Total saved treatments</p>
        </article>

        <article class="clinical-stat">
            <p class="clinical-stat__label">Follow-ups</p>
            <p class="clinical-stat__value">0</p>
            <p class="clinical-stat__note">Outstanding follow-up actions</p>
        </article>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">Recent activity</h2>
            <p class="clinical-card__subtitle">
                Recent treatment notes and patient updates will appear here.
            </p>
        </div>

        <div class="clinical-card__body">
            <div class="clinical-empty">
                <h3 class="clinical-empty__title">No activity yet</h3>
                <p class="clinical-empty__text">
                    Once patients and treatment notes are added, the latest activity will appear here.
                </p>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
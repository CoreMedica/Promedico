<?php

/**
 * Promedico Clinical App Header
 * Path: public/clinical/includes/header.php
 *
 * Expected optional variables set by each page before including:
 *
 * $pageTitle = 'Dashboard';
 * $activeNav = 'dashboard';
 * $bodyClass = '';
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

clinical_require_login();

$pageTitle = $pageTitle ?? 'Clinical Notes';
$activeNav = $activeNav ?? '';
$bodyClass = $bodyClass ?? '';

$currentUser = clinical_current_user();

if ($currentUser === null) {
    clinical_redirect_to_login();
}

header('X-Robots-Tag: noindex, nofollow, noarchive');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');



$documentTitle = clinical_escape($pageTitle) . ' | Promedico Clinical';
$currentUserName = clinical_escape($currentUser['name']);
$currentUserRole = clinical_escape(ucfirst($currentUser['role']));
?>
<!doctype html>
<html lang="en-GB">

<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= $documentTitle ?></title>

    <link rel="stylesheet" href="/clinical/assets/clinical.css">
</head>

<body class="clinical-app <?= clinical_escape($bodyClass) ?>">
    <header class="clinical-header">
        <div class="clinical-header__inner">
            <a class="clinical-brand" href="/clinical/dashboard.php" aria-label="Promedico Clinical dashboard">
                <span class="clinical-brand__mark" aria-hidden="true">P</span>

                <span class="clinical-brand__text">
                    <span class="clinical-brand__name">Promedico</span>
                    <span class="clinical-brand__label">Clinical notes</span>
                </span>
            </a>

            <div class="clinical-header__actions">
                <span class="clinical-user">
                    Signed in as <?= $currentUserName ?> <span aria-hidden="true">·</span> <?= $currentUserRole ?>
                </span>

                <a class="clinical-button clinical-button--secondary clinical-button--small" href="/clinical/logout.php">
                    Sign out
                </a>
            </div>
        </div>
    </header>

    <nav class="clinical-nav" aria-label="Clinical navigation">
        <div class="clinical-nav__inner">
            <a class="clinical-nav__link<?= clinical_active_nav('dashboard', $activeNav) ?>" href="/clinical/dashboard.php">
                Dashboard
            </a>

            <a class="clinical-nav__link<?= clinical_active_nav('patients', $activeNav) ?>" href="/clinical/patients.php">
                Patients
            </a>

            <a class="clinical-nav__link<?= clinical_active_nav('new-patient', $activeNav) ?>" href="/clinical/patient-new.php">
                New patient
            </a>

            <a class="clinical-nav__link<?= clinical_active_nav('new-treatment', $activeNav) ?>" href="/clinical/treatment-new.php">
                New treatment note
            </a>

            <?php if ($currentUser['role'] === 'admin'): ?>
                <a class="clinical-nav__link<?= clinical_active_nav('audit', $activeNav) ?>" href="/clinical/audit.php">
                    Audit log
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="clinical-main">
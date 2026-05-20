<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

clinical_require_login();

$pageTitle = 'Patients';
$activeNav = 'patients';

$patientService = clinical_patient_service();

$search = trim((string) ($_GET['q'] ?? ''));

$patients = $patientService->searchPatients(
    search: $search,
    limit: 100
);

require_once __DIR__ . '/includes/header.php';
?>

<div class="clinical-container clinical-stack clinical-stack--xl">
    <section class="clinical-page-header">
        <div class="clinical-page-header__content">
            <p class="clinical-eyebrow">Patient records</p>
            <h1 class="clinical-title">Patients</h1>
            <p class="clinical-subtitle">
                Search existing patient records before creating a new patient. This helps avoid duplicate records and fragmented treatment history.
            </p>
        </div>

        <div class="clinical-button-row">
            <a class="clinical-button" href="/clinical/patient-new.php">Add patient</a>
        </div>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">Find a patient</h2>
            <p class="clinical-card__subtitle">
                Search by name, phone number, email address, or postcode.
            </p>
        </div>

        <div class="clinical-card__body">
            <form class="clinical-form" method="get" action="/clinical/patients.php">
                <div class="clinical-form-grid">
                    <div class="clinical-form-field">
                        <label class="clinical-label" for="q">Search</label>
                        <input
                            class="clinical-input"
                            type="search"
                            id="q"
                            name="q"
                            value="<?= clinical_escape($search) ?>"
                            placeholder="e.g. Smith, 07700, PO1">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" aria-hidden="true">&nbsp;</label>

                        <div class="clinical-button-row">
                            <button class="clinical-button" type="submit">Search</button>

                            <?php if ($search !== ''): ?>
                                <a class="clinical-button clinical-button--secondary" href="/clinical/patients.php">
                                    Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">
                <?= $search === '' ? 'Patient list' : 'Search results' ?>
            </h2>

            <p class="clinical-card__subtitle">
                Showing up to 100 active patient records.
            </p>
        </div>

        <div class="clinical-card__body">
            <?php if (count($patients) === 0): ?>
                <div class="clinical-empty">
                    <h3 class="clinical-empty__title">No patients found</h3>
                    <p class="clinical-empty__text">
                        Try a different search term, or add a new patient record if this is a new patient.
                    </p>

                    <div class="clinical-button-row" style="justify-content: center; margin-top: 1rem;">
                        <a class="clinical-button" href="/clinical/patient-new.php">Add patient</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="clinical-table-wrap">
                    <table class="clinical-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Date of birth</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Postcode</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?= clinical_escape($patient['last_name'] . ', ' . $patient['first_name']) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= clinical_format_date($patient['date_of_birth']) ?>
                                    </td>

                                    <td>
                                        <?= clinical_display($patient['phone']) ?>
                                    </td>

                                    <td>
                                        <?= clinical_display($patient['email']) ?>
                                    </td>

                                    <td>
                                        <?= clinical_display($patient['postcode']) ?>
                                    </td>

                                    <td>
                                        <?= clinical_format_date($patient['created_at']) ?>
                                    </td>

                                    <td>
                                        <div class="clinical-table__actions">
                                            <a
                                                class="clinical-button clinical-button--small"
                                                href="/clinical/patient-view.php?id=<?= (int) $patient['id'] ?>">
                                                View
                                            </a>

                                            <a
                                                class="clinical-button clinical-button--secondary clinical-button--small"
                                                href="/clinical/patient-edit.php?id=<?= (int) $patient['id'] ?>">
                                                Edit
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
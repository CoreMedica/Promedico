<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

clinical_require_admin();

$pageTitle = 'Audit Log';
$activeNav = 'audit';

$currentUser = clinical_current_user();

if ($currentUser === null) {
    clinical_redirect_to_login();
}

$auditService = clinical_audit_service();

$filters = [
    'user_id' => clinical_get_int('user_id'),
    'action' => trim((string) ($_GET['action'] ?? '')),
    'entity_type' => trim((string) ($_GET['entity_type'] ?? '')),
    'entity_id' => clinical_get_int('entity_id'),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];

$hasFilters = false;

foreach ($filters as $value) {
    if ($value !== null && $value !== '') {
        $hasFilters = true;
        break;
    }
}

$auditEntries = $hasFilters
    ? $auditService->search($filters, 200)
    : $auditService->latest(200);

$auditService->recordAuditLogViewed((int) $currentUser['id']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="clinical-container clinical-stack clinical-stack--xl">
    <section class="clinical-page-header">
        <div class="clinical-page-header__content">
            <p class="clinical-eyebrow">Governance</p>
            <h1 class="clinical-title">Audit log</h1>
            <p class="clinical-subtitle">
                Review staff access and key actions within the clinical notes system.
            </p>
        </div>

        <div class="clinical-button-row">
            <a class="clinical-button clinical-button--secondary" href="/clinical/audit.php">
                Clear filters
            </a>
        </div>
    </section>

    <section class="clinical-alert clinical-alert--info" role="status">
        <p><strong>Admin-only area.</strong></p>
        <p>
            This log records access and workflow events. It should not contain clinical note text.
        </p>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">Filter audit records</h2>
            <p class="clinical-card__subtitle">
                Search by user, action, entity type, entity ID, or date range.
            </p>
        </div>

        <div class="clinical-card__body">
            <form class="clinical-form" method="get" action="/clinical/audit.php">
                <div class="clinical-form-grid clinical-form-grid--three">
                    <div class="clinical-form-field">
                        <label class="clinical-label" for="user_id">User ID</label>
                        <input
                            class="clinical-input"
                            type="number"
                            min="1"
                            id="user_id"
                            name="user_id"
                            value="<?= $filters['user_id'] !== null ? (int) $filters['user_id'] : '' ?>">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="action">Action</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="action"
                            name="action"
                            value="<?= clinical_escape($filters['action']) ?>"
                            placeholder="e.g. patient_viewed">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="entity_type">Entity type</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="entity_type"
                            name="entity_type"
                            value="<?= clinical_escape($filters['entity_type']) ?>"
                            placeholder="e.g. patient, treatment">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="entity_id">Entity ID</label>
                        <input
                            class="clinical-input"
                            type="number"
                            min="1"
                            id="entity_id"
                            name="entity_id"
                            value="<?= $filters['entity_id'] !== null ? (int) $filters['entity_id'] : '' ?>">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="date_from">Date from</label>
                        <input
                            class="clinical-input"
                            type="date"
                            id="date_from"
                            name="date_from"
                            value="<?= clinical_escape($filters['date_from']) ?>">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="date_to">Date to</label>
                        <input
                            class="clinical-input"
                            type="date"
                            id="date_to"
                            name="date_to"
                            value="<?= clinical_escape($filters['date_to']) ?>">
                    </div>
                </div>

                <div class="clinical-button-row">
                    <button class="clinical-button" type="submit">
                        Filter audit log
                    </button>

                    <a class="clinical-button clinical-button--secondary" href="/clinical/audit.php">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </section>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">
                <?= $hasFilters ? 'Filtered audit records' : 'Latest audit records' ?>
            </h2>
            <p class="clinical-card__subtitle">
                Showing up to 200 records, newest first.
            </p>
        </div>

        <div class="clinical-card__body">
            <?php if (count($auditEntries) === 0): ?>
                <div class="clinical-empty">
                    <h3 class="clinical-empty__title">No audit records found</h3>
                    <p class="clinical-empty__text">
                        Try clearing the filters or widening the date range.
                    </p>
                </div>
            <?php else: ?>
                <div class="clinical-table-wrap">
                    <table class="clinical-table">
                        <thead>
                            <tr>
                                <th>Date/time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>IP address</th>
                                <th>User agent</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($auditEntries as $entry): ?>
                                <tr>
                                    <td>
                                        <?= clinical_format_datetime($entry['created_at']) ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($entry['user_name'])): ?>
                                            <strong><?= clinical_escape($entry['user_name']) ?></strong><br>
                                            <span class="clinical-muted">
                                                <?= clinical_escape($entry['user_email']) ?>
                                            </span><br>
                                            <span class="clinical-muted">
                                                ID: <?= (int) $entry['user_id'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="clinical-muted">Unknown / unauthenticated</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="clinical-badge clinical-badge--teal">
                                            <?= clinical_escape($entry['action']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if (!empty($entry['entity_type'])): ?>
                                            <?= clinical_escape($entry['entity_type']) ?>

                                            <?php if (!empty($entry['entity_id'])): ?>
                                                <br>
                                                <span class="clinical-muted">
                                                    ID: <?= (int) $entry['entity_id'] ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="clinical-muted">Not recorded</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= clinical_display($entry['ip_address']) ?>
                                    </td>

                                    <td>
                                        <span class="clinical-small">
                                            <?= clinical_display($entry['user_agent']) ?>
                                        </span>
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
<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/db.php';

clinical_require_login();

$pageTitle = 'New Patient';
$activeNav = 'new-patient';

$currentUser = clinical_current_user();

if ($currentUser === null) {
    clinical_redirect_to_login();
}

$errors = [];

$form = [
    'first_name' => '',
    'last_name' => '',
    'date_of_birth' => '',
    'phone' => '',
    'email' => '',
    'address_line_1' => '',
    'address_line_2' => '',
    'town' => '',
    'county' => '',
    'postcode' => '',
    'relevant_medical_notes' => '',
];

function patient_form_value(array $form, string $key): string
{
    return htmlspecialchars((string) ($form[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}

function patient_normalise_nullable_string(string $value): ?string
{
    $value = trim($value);

    return $value === '' ? null : $value;
}

function patient_normalise_postcode(string $postcode): ?string
{
    $postcode = strtoupper(trim($postcode));
    $postcode = preg_replace('/\s+/', ' ', $postcode);

    return $postcode === '' ? null : $postcode;
}

function patient_valid_date_or_null(string $date): ?string
{
    $date = trim($date);

    if ($date === '') {
        return null;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $date);

    if (!$dt || $dt->format('Y-m-d') !== $date) {
        return null;
    }

    return $date;
}

$possibleMatches = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clinical_verify_csrf_or_fail();

    foreach ($form as $key => $_) {
        $form[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    if ($form['first_name'] === '') {
        $errors[] = 'First name is required.';
    }

    if ($form['last_name'] === '') {
        $errors[] = 'Last name is required.';
    }

    $dateOfBirth = null;

    if ($form['date_of_birth'] !== '') {
        $dateOfBirth = patient_valid_date_or_null($form['date_of_birth']);

        if ($dateOfBirth === null) {
            $errors[] = 'Date of birth must be a valid date.';
        }
    }

    if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email address is not valid.';
    }

    $pdo = clinical_db();

    /*
     * Duplicate warning only. We do not block creation because families may share
     * phone/email/postcode, and older records may be incomplete.
     */
    if ($form['last_name'] !== '' || $form['phone'] !== '' || $form['email'] !== '' || $form['postcode'] !== '' || $dateOfBirth !== null) {
        $normalisedPostcode = patient_normalise_postcode($form['postcode']);
        $normalisedEmail = $form['email'] === '' ? '' : strtolower($form['email']);
        $normalisedPhone = trim($form['phone']);
        $normalisedLastName = trim($form['last_name']);

        $matchStmt = $pdo->prepare(
            'SELECT
            id,
            first_name,
            last_name,
            date_of_birth,
            phone,
            email,
            postcode
         FROM patients
         WHERE is_active = 1
           AND (
                (:last_name_check <> "" AND last_name LIKE :last_name_like)
                OR (:phone_check <> "" AND phone = :phone_value)
                OR (:email_check <> "" AND email = :email_value)
                OR (:postcode_check <> "" AND postcode = :postcode_value)
                OR (:dob_check IS NOT NULL AND date_of_birth = :dob_value)
           )
         ORDER BY last_name ASC, first_name ASC
         LIMIT 10'
        );

        $matchStmt->execute([
            'last_name_check' => $normalisedLastName,
            'last_name_like' => '%' . $normalisedLastName . '%',

            'phone_check' => $normalisedPhone,
            'phone_value' => $normalisedPhone,

            'email_check' => $normalisedEmail,
            'email_value' => $normalisedEmail,

            'postcode_check' => $normalisedPostcode ?? '',
            'postcode_value' => $normalisedPostcode ?? '',

            'dob_check' => $dateOfBirth,
            'dob_value' => $dateOfBirth,
        ]);

        $possibleMatches = $matchStmt->fetchAll();
    }

    if (count($errors) === 0) {
        $insert = $pdo->prepare(
            'INSERT INTO patients (
                first_name,
                last_name,
                date_of_birth,
                phone,
                email,
                address_line_1,
                address_line_2,
                town,
                county,
                postcode,
                relevant_medical_notes,
                created_by
             ) VALUES (
                :first_name,
                :last_name,
                :date_of_birth,
                :phone,
                :email,
                :address_line_1,
                :address_line_2,
                :town,
                :county,
                :postcode,
                :relevant_medical_notes,
                :created_by
             )'
        );

        $insert->execute([
            'first_name' => $form['first_name'],
            'last_name' => $form['last_name'],
            'date_of_birth' => $dateOfBirth,
            'phone' => patient_normalise_nullable_string($form['phone']),
            'email' => $form['email'] === '' ? null : strtolower($form['email']),
            'address_line_1' => patient_normalise_nullable_string($form['address_line_1']),
            'address_line_2' => patient_normalise_nullable_string($form['address_line_2']),
            'town' => patient_normalise_nullable_string($form['town']),
            'county' => patient_normalise_nullable_string($form['county']),
            'postcode' => patient_normalise_postcode($form['postcode']),
            'relevant_medical_notes' => patient_normalise_nullable_string($form['relevant_medical_notes']),
            'created_by' => (int) $currentUser['id'],
        ]);

        $patientId = (int) $pdo->lastInsertId();

        clinical_audit(
            userId: (int) $currentUser['id'],
            action: 'patient_created',
            entityType: 'patient',
            entityId: $patientId
        );

        clinical_rotate_csrf_token();

        header('Location: /clinical/patient-view.php?id=' . $patientId . '&created=1');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="clinical-container clinical-stack clinical-stack--xl">
    <section class="clinical-page-header">
        <div class="clinical-page-header__content">
            <p class="clinical-eyebrow">Patient records</p>
            <h1 class="clinical-title">New patient</h1>
            <p class="clinical-subtitle">
                Create a patient record before adding treatment notes. Search existing patients first where possible to avoid duplicates.
            </p>
        </div>

        <div class="clinical-button-row">
            <a class="clinical-button clinical-button--secondary" href="/clinical/patients.php">Back to patients</a>
        </div>
    </section>

    <?php if (count($errors) > 0): ?>
        <div class="clinical-alert clinical-alert--danger" role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (count($possibleMatches) > 0): ?>
        <section class="clinical-alert clinical-alert--warning" role="status">
            <p><strong>Possible existing patient records found.</strong></p>
            <p>
                Check these before creating a duplicate. If this is definitely a different patient, continue saving.
            </p>

            <div class="clinical-table-wrap" style="margin-top: 1rem;">
                <table class="clinical-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>DOB</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Postcode</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($possibleMatches as $match): ?>
                            <tr>
                                <td><?= htmlspecialchars($match['last_name'] . ', ' . $match['first_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= $match['date_of_birth'] ? htmlspecialchars(date('d/m/Y', strtotime($match['date_of_birth'])), ENT_QUOTES, 'UTF-8') : 'Not recorded' ?></td>
                                <td><?= htmlspecialchars((string) ($match['phone'] ?? 'Not recorded'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($match['email'] ?? 'Not recorded'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($match['postcode'] ?? 'Not recorded'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <a class="clinical-button clinical-button--small" href="/clinical/patient-view.php?id=<?= (int) $match['id'] ?>">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <section class="clinical-card">
        <div class="clinical-card__header">
            <h2 class="clinical-card__title">Patient details</h2>
            <p class="clinical-card__subtitle">
                Record only information required for safe treatment and follow-up.
            </p>
        </div>

        <div class="clinical-card__body">
            <form class="clinical-form" method="post" action="/clinical/patient-new.php" novalidate>
                <?= clinical_csrf_field(); ?>

                <div class="clinical-form-grid">
                    <div class="clinical-form-field">
                        <label class="clinical-label" for="first_name">First name</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="first_name"
                            name="first_name"
                            value="<?= patient_form_value($form, 'first_name') ?>"
                            autocomplete="given-name"
                            required>
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="last_name">Last name</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="last_name"
                            name="last_name"
                            value="<?= patient_form_value($form, 'last_name') ?>"
                            autocomplete="family-name"
                            required>
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="date_of_birth">Date of birth</label>
                        <input
                            class="clinical-input"
                            type="date"
                            id="date_of_birth"
                            name="date_of_birth"
                            value="<?= patient_form_value($form, 'date_of_birth') ?>">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="phone">Phone</label>
                        <input
                            class="clinical-input"
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?= patient_form_value($form, 'phone') ?>"
                            autocomplete="tel">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="email">Email</label>
                        <input
                            class="clinical-input"
                            type="email"
                            id="email"
                            name="email"
                            value="<?= patient_form_value($form, 'email') ?>"
                            autocomplete="email">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="postcode">Postcode</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="postcode"
                            name="postcode"
                            value="<?= patient_form_value($form, 'postcode') ?>"
                            autocomplete="postal-code">
                    </div>

                    <div class="clinical-form-field clinical-form-field--full">
                        <label class="clinical-label" for="address_line_1">Address line 1</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="address_line_1"
                            name="address_line_1"
                            value="<?= patient_form_value($form, 'address_line_1') ?>"
                            autocomplete="address-line1">
                    </div>

                    <div class="clinical-form-field clinical-form-field--full">
                        <label class="clinical-label" for="address_line_2">Address line 2</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="address_line_2"
                            name="address_line_2"
                            value="<?= patient_form_value($form, 'address_line_2') ?>"
                            autocomplete="address-line2">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="town">Town/city</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="town"
                            name="town"
                            value="<?= patient_form_value($form, 'town') ?>"
                            autocomplete="address-level2">
                    </div>

                    <div class="clinical-form-field">
                        <label class="clinical-label" for="county">County</label>
                        <input
                            class="clinical-input"
                            type="text"
                            id="county"
                            name="county"
                            value="<?= patient_form_value($form, 'county') ?>"
                            autocomplete="address-level1">
                    </div>

                    <div class="clinical-form-field clinical-form-field--full">
                        <label class="clinical-label" for="relevant_medical_notes">
                            Relevant medical notes
                            <span class="clinical-label__hint">(only what is needed for safe treatment)</span>
                        </label>
                        <textarea
                            class="clinical-textarea"
                            id="relevant_medical_notes"
                            name="relevant_medical_notes"
                            rows="5"><?= patient_form_value($form, 'relevant_medical_notes') ?></textarea>
                    </div>
                </div>

                <div class="clinical-button-row">
                    <button class="clinical-button" type="submit">Save patient</button>
                    <a class="clinical-button clinical-button--secondary" href="/clinical/patients.php">Cancel</a>
                </div>
            </form>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
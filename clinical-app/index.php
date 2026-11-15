<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (clinical_is_logged_in()) {
    clinical_redirect('/clinical/dashboard.php');
}

clinical_redirect('/clinical/login.php');

<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

clinical_logout();

clinical_redirect('/clinical/login.php');

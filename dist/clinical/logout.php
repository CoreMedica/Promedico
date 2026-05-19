<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

clinical_logout();

header('Location: /clinical/login.php');
exit;

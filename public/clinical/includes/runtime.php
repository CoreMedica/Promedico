<?php

declare(strict_types=1);

/**
 * Promedico Clinical Runtime Settings
 * Path: public/clinical/includes/runtime.php
 */

function clinical_is_local_environment(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';

    return str_contains($host, '.test')
        || str_contains($host, 'localhost')
        || str_contains($host, '127.0.0.1');
}

function clinical_configure_runtime(): void
{
    if (clinical_is_local_environment()) {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
        return;
    }

    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

clinical_configure_runtime();

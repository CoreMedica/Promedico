<?php

declare(strict_types=1);

/**
 * Promedico Clinical App Config
 *
 * This file must not be inside public/clinical.
 */

return [
    'db' => [
        'host' => '127.0.0.1',
        'database' => 'dbs15691086',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'to' => 'reception@promedico.co.uk',
        'site_name' => 'Promedico Wellness Group',
        'subject_prefix' => 'Website enquiry',
    ],
];

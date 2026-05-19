<?php

declare(strict_types=1);

/**
 * Database connection for Promedico Clinical App.
 */

function clinical_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $configPath = __DIR__ . '/../../../private/clinical-config.php';

    if (!file_exists($configPath)) {
        throw new RuntimeException('Clinical database config file not found.');
    }

    $config = require $configPath;

    $db = $config['db'];

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $db['host'],
        $db['database'],
        $db['charset']
    );

    $pdo = new PDO(
        $dsn,
        $db['username'],
        $db['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $pdo;
}

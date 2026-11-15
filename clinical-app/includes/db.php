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

    $configPath = __DIR__ . '/../../php/config/database.php';

    if (!file_exists($configPath)) {
        clinical_fail_securely('Clinical database configuration is unavailable.');
    }

    $config = require $configPath;

    foreach (['host', 'database', 'username', 'charset'] as $key) {
        if (!isset($config[$key]) || $config[$key] === '') {
            clinical_fail_securely('Clinical database configuration is incomplete.');
        }
    }

    // Password can be empty for local development
    if (!isset($config['password'])) {
        clinical_fail_securely('Clinical database configuration is incomplete.');
    }


    $port = isset($config['port']) ? (int) $config['port'] : 3306;

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $port,
        $config['database'],
        $config['charset']
    );

    try {
        $pdo = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (Throwable $e) {
        clinical_fail_securely('Clinical database connection failed.', $e);
    }

    return $pdo;
}

function clinical_fail_securely(string $publicMessage, ?Throwable $exception = null): never
{
    error_log($exception ? $exception->getMessage() : $publicMessage);

    http_response_code(500);

    if (function_exists('clinical_is_local_environment') && clinical_is_local_environment()) {
        echo '<pre>';
        echo htmlspecialchars($publicMessage, ENT_QUOTES, 'UTF-8');

        if ($exception !== null) {
            echo "\n\n";
            echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
        }

        echo '</pre>';
        exit;
    }

    exit('A system error occurred. Please try again later.');
}

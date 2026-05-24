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
        clinical_fail_securely('Clinical database configuration is unavailable.');
    }

    $config = require $configPath;

    if (
        !is_array($config) ||
        empty($config['db']) ||
        !is_array($config['db'])
    ) {
        clinical_fail_securely('Clinical database configuration is invalid.');
    }

    $db = $config['db'];

    foreach (['host', 'database', 'username', 'password', 'charset'] as $key) {
        if (!array_key_exists($key, $db)) {
            clinical_fail_securely('Clinical database configuration is incomplete.');
        }
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $db['host'],
        $db['database'],
        $db['charset']
    );

    try {
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
    } catch (Throwable $e) {
        clinical_fail_securely('Clinical database connection failed.', $e);
    }

    return $pdo;
}

function clinical_fail_securely(string $publicMessage, ?Throwable $exception = null): never
{
    if ($exception !== null) {
        error_log($exception->getMessage());
    } else {
        error_log($publicMessage);
    }

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

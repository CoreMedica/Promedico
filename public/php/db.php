<?php

function getDatabaseConnection(): mysqli
{
    $config = require __DIR__ . '/config/database.php';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $connection = new mysqli(
        $config['host'],
        $config['username'],
        $config['password'],
        $config['database'],
        $config['port']
    );

    $connection->set_charset($config['charset']);

    return $connection;
}

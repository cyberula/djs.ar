<?php
declare(strict_types=1);

use PDO;
use PDOException;

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_PORT', 3306);
    define('DB_NAME', 'monsqcgn_djsar');
    define('DB_USER', 'monsqcgn_iberula');
    define('DB_PASS', 'Espora1740.');
}

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Error de conexion a la base de datos.');
}


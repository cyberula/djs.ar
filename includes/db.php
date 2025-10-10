<?php
declare(strict_types=1);

use PDO;
use PDOException;

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_PORT', 3306);
    define('DB_NAME', 'monsqcgn_djsar');
    define('DB_USER', 'monsqcgn_ciberula');
    define('DB_PASS', 'Espora1740.');
}

// reCAPTCHA configuration
// Replace these with your real site and secret keys from Google reCAPTCHA admin console.
if (!defined('RECAPTCHA_SITE_KEY')) {
    define('RECAPTCHA_SITE_KEY', '6Lflw-QrAAAAAEtfqLABut-Hjex879esDfhK6KfC');
}

if (!defined('RECAPTCHA_SECRET')) {
    define('RECAPTCHA_SECRET', '6Lflw-QrAAAAAFjzDKfVK_0PLpQQPHfQ3aTdi8kF');
}

// reCAPTCHA v3 minimum acceptable score (0.0 - 1.0). Increase to be stricter.
if (!defined('RECAPTCHA_MIN_SCORE')) {
    define('RECAPTCHA_MIN_SCORE', 0.5);
}

// Optional: path to a log file where reCAPTCHA verification attempts are appended as JSON lines.
// Useful for debugging failed verifications during testing. Ensure the webserver user can write
// to the parent folder or change this to a writable path outside the repository.
if (!defined('RECAPTCHA_LOG_PATH')) {
    define('RECAPTCHA_LOG_PATH', __DIR__ . '/../logs/recaptcha.log');
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



<?php
use PDO;
use PDOException;

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

const ADMIN_SESSION_KEY = 'admin_user_id';
const ADMIN_SESSION_DATA_KEY = 'admin_user_data';

function admin_is_logged_in(): bool
{
    ensure_session();
    return isset($_SESSION[ADMIN_SESSION_KEY]) && is_int($_SESSION[ADMIN_SESSION_KEY]);
}

function admin_current_user(PDO $pdo): ?array
{
    ensure_session();
    if (!admin_is_logged_in()) {
        return null;
    }

    if (isset($_SESSION[ADMIN_SESSION_DATA_KEY]) && is_array($_SESSION[ADMIN_SESSION_DATA_KEY])) {
        return $_SESSION[ADMIN_SESSION_DATA_KEY];
    }

    $stmt = $pdo->prepare('SELECT id, email, name FROM admin_users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION[ADMIN_SESSION_KEY]]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($admin === null) {
        admin_logout();
        return null;
    }

    $_SESSION[ADMIN_SESSION_DATA_KEY] = $admin;
    return $admin;
}

function admin_login(PDO $pdo, string $email, string $password): bool
{
    ensure_session();
    $stmt = $pdo->prepare('SELECT id, email, name, password_hash FROM admin_users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => strtolower($email)]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($password, (string)$admin['password_hash'])) {
        return false;
    }

    if (function_exists('password_needs_rehash') && password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
        try {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE admin_users SET password_hash = :hash WHERE id = :id');
            $update->execute([':hash' => $newHash, ':id' => (int)$admin['id']]);
        } catch (PDOException $exception) {
            // Silently ignore hash update failures.
        }
    }

    $_SESSION[ADMIN_SESSION_KEY] = (int)$admin['id'];
    $_SESSION[ADMIN_SESSION_DATA_KEY] = [
        'id' => (int)$admin['id'],
        'email' => $admin['email'],
        'name' => $admin['name'],
    ];

    regenerate_session_id();

    return true;
}

function admin_logout(): void
{
    ensure_session();
    unset($_SESSION[ADMIN_SESSION_KEY], $_SESSION[ADMIN_SESSION_DATA_KEY]);
    regenerate_session_id();
}

function admin_require_login(PDO $pdo): array
{
    $admin = admin_current_user($pdo);
    if ($admin !== null) {
        return $admin;
    }

    $redirectTo = $_SERVER['REQUEST_URI'] ?? '/admin/index.php';
    header('Location: /admin/login.php?redirect=' . urlencode($redirectTo));
    exit;
}

function regenerate_session_id(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

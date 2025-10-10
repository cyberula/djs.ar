<?php
declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
ensure_session();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/admin.php';

$adminUser = admin_require_login($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/index.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$token = (string)($_POST['csrf_token'] ?? '');

if (!validate_csrf_token($token) || $id <= 0) {
    header('Location: /admin/index.php?status=error');
    exit;
}

$stmt = $pdo->prepare('SELECT image_path FROM djs WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    header('Location: /admin/index.php?status=missing');
    exit;
}

$pdo->beginTransaction();
try {
    $delete = $pdo->prepare('DELETE FROM djs WHERE id = :id LIMIT 1');
    $delete->execute([':id' => $id]);
    $pdo->commit();
} catch (Throwable $throwable) {
    $pdo->rollBack();
    header('Location: /admin/index.php?status=error');
    exit;
}

$imagePath = $record['image_path'] ?? '';
if ($imagePath) {
    $cleanPath = ltrim($imagePath, '/\\');
    $fullPath = __DIR__ . '/../' . $cleanPath;
    $uploadsDir = realpath(__DIR__ . '/../uploads');
    $targetReal = realpath($fullPath);
    if ($uploadsDir !== false && $targetReal !== false && str_starts_with($targetReal, $uploadsDir)) {
        @unlink($targetReal);
    }
}

header('Location: /admin/index.php?status=deleted');
exit;

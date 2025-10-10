<?php
declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
ensure_session();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/admin.php';

if (admin_is_logged_in()) {
    admin_logout();
}

header('Location: /admin/login.php');
exit;

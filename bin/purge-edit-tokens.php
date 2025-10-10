<?php
declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';

purge_stale_edit_tokens($pdo);


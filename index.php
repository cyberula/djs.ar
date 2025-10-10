<?php
declare(strict_types=1);

$hostHeader = $_SERVER['HTTP_HOST'] ?? 'djs.ar';
[$hostOnly] = explode(':', strtolower($hostHeader), 2);

if ($hostOnly === 'djs.ar' || $hostOnly === 'www.djs.ar') {
    require __DIR__ . '/directory.php';
    exit;
}

$segments = explode('.', $hostOnly);
$slug = preg_replace('~[^a-z0-9-]+~', '', $segments[0] ?? '');

if ($slug === '' || $slug === 'www' || $slug === 'djs') {
    require __DIR__ . '/directory.php';
    exit;
}

require __DIR__ . '/profile.php';




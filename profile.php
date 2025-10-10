<?php
declare(strict_types=1);

use PDO;

require __DIR__ . '/includes/helpers.php';
ensure_session();

require __DIR__ . '/includes/db.php';

$incomingSlug = $slug ?? ($_GET['slug'] ?? '');
$cleanSlug = sanitize_slug($incomingSlug);

if ($cleanSlug === '') {
    http_response_code(404);
    $pageTitle = 'DJ no encontrado - djs.ar';
    require __DIR__ . '/templates/header.php';
    ?>
    <section class="empty-state">
        <h1>DJ no encontrado</h1>
        <p>No pudimos encontrar este perfil.</p>
    </section>
    <?php
    require __DIR__ . '/templates/footer.php';
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM djs WHERE slug = :slug LIMIT 1');
$stmt->execute([':slug' => $cleanSlug]);
$dj = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dj) {
    http_response_code(404);
    $pageTitle = 'DJ no encontrado - djs.ar';
    require __DIR__ . '/templates/header.php';
    ?>
    <section class="empty-state">
        <h1>DJ no encontrado</h1>
        <p>Quiza el perfil fue eliminado o nunca existio.</p>
        <a class="button" href="/">Volver al directorio</a>
    </section>
    <?php
    require __DIR__ . '/templates/footer.php';
    exit;
}

$pageTitle = $dj['name'] . ' - djs.ar';
$hideMainNav = true;
require __DIR__ . '/templates/header.php';
require __DIR__ . '/templates/profile.php';
require __DIR__ . '/templates/footer.php';



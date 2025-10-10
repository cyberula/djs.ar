<?php
declare(strict_types=1);

require __DIR__ . '/includes/helpers.php';
ensure_session();
require __DIR__ . '/includes/db.php';

purge_stale_edit_tokens($pdo);

$tokenParam = trim((string)($_GET['token'] ?? ''));

if ($tokenParam === '') {
    http_response_code(400);
    $pageTitle = 'Enlace invalido - djs.ar';
    require __DIR__ . '/templates/header.php';
    ?>
    <section class="empty-state">
        <h1>Enlace invalido</h1>
        <p>El enlace para editar el perfil no es valido o falta el token.</p>
        <a class="button" href="https://djs.ar/request-edit-link.php">Pedir un nuevo enlace</a>
    </section>
    <?php
    require __DIR__ . '/templates/footer.php';
    exit;
}

$validation = validate_edit_token($pdo, $tokenParam);

if ($validation === null) {
    http_response_code(410);
    $pageTitle = 'Enlace expirado - djs.ar';
    require __DIR__ . '/templates/header.php';
    ?>
    <section class="empty-state">
        <h1>Enlace caducado</h1>
        <p>El enlace ya fue usado o expiro. Pedilo de nuevo para continuar.</p>
        <a class="button" href="https://djs.ar/request-edit-link.php">Pedir un nuevo enlace</a>
    </section>
    <?php
    require __DIR__ . '/templates/footer.php';
    exit;
}

$tokenData = $validation['token'];
$djData = $validation['dj'];

$pdo->beginTransaction();

try {
    $updated = consume_edit_token($pdo, (int)$tokenData['id']);
    if (!$updated) {
        $pdo->rollBack();
        http_response_code(410);
        $pageTitle = 'Enlace caducado - djs.ar';
        require __DIR__ . '/templates/header.php';
        ?>
        <section class="empty-state">
            <h1>Enlace caducado</h1>
            <p>El enlace ya fue usado o expiro. Pedilo de nuevo para continuar.</p>
            <a class="button" href="https://djs.ar/request-edit-link.php">Pedir un nuevo enlace</a>
        </section>
        <?php
        require __DIR__ . '/templates/footer.php';
        exit;
    }

    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    http_response_code(500);
    $pageTitle = 'Error al validar enlace - djs.ar';
    require __DIR__ . '/templates/header.php';
    ?>
    <section class="empty-state">
        <h1>No pudimos validar el enlace</h1>
        <p>Intenta pedir un nuevo enlace. Si el problema sigue, escribinos a hola@djs.ar.</p>
        <a class="button" href="https://djs.ar/request-edit-link.php">Volver</a>
    </section>
    <?php
    require __DIR__ . '/templates/footer.php';
    exit;
}

$_SESSION['dj_edit'] = [
    'dj_id' => (int)$djData['id'],
    'started_at' => time(),
    'expires_at' => time() + 3600,
];

header('Location: /profile-edit-form.php');
exit;

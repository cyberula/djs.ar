<?php
declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
ensure_session();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/admin.php';

$redirectTarget = '/admin/index.php';
if (!empty($_GET['redirect']) && is_string($_GET['redirect'])) {
    $candidate = trim($_GET['redirect']);
    // Avoid using str_starts_with here because it may not exist on PHP < 8.
    // Use strpos check which is compatible across PHP versions.
    if ($candidate !== '' && strpos($candidate, '://') === false && strpos($candidate, '/') === 0) {
        $redirectTarget = $candidate;
    }
}

if (admin_is_logged_in()) {
    header('Location: ' . $redirectTarget);
    exit;
}

$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(sanitize_text($_POST['email'] ?? '', 120, false));
    $password = (string)($_POST['password'] ?? '');
    $token = (string)($_POST['csrf_token'] ?? '');

    if (!validate_csrf_token($token)) {
        $errors[] = 'Token invalido. Recarga la pagina.';
    }

    if ($email === '' || $password === '') {
        $errors[] = 'Ingresa email y clave.';
    }

    if (empty($errors) && !admin_login($pdo, $email, $password)) {
        $errors[] = 'Credenciales invalidas.';
    }

    if (empty($errors)) {
        header('Location: ' . $redirectTarget);
        exit;
    }
}

$pageTitle = 'Ingresar al panel';
$adminUser = null;
$csrfToken = create_csrf_token();

require __DIR__ . '/../templates/admin_header.php';
?>
<section class="admin-card admin-login">
    <h1>Panel djs.ar</h1>
    <?php if (!empty($errors)): ?>
        <div class="admin-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= e($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" class="admin-form" autocomplete="off">
        <div class="admin-field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autocomplete="username" value="<?= e($email) ?>">
        </div>
        <div class="admin-field">
            <label for="password">Clave</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <button type="submit" class="admin-button">Ingresar</button>
    </form>
</section>
<?php
require __DIR__ . '/../templates/admin_footer.php';
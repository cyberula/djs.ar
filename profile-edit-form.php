<?php
declare(strict_types=1);

require __DIR__ . '/includes/helpers.php';
ensure_session();
require __DIR__ . '/includes/db.php';

$editSession = $_SESSION['dj_edit'] ?? null;
$redirectToRequest = function (string $status = ''): void {
    $location = '/request-edit-link.php';
    if ($status !== '') {
        $location .= '?status=' . rawurlencode($status);
    }
    header('Location: ' . $location);
    exit;
};

if (!$editSession || empty($editSession['dj_id'])) {
    $redirectToRequest('');
}

if (!empty($editSession['expires_at']) && time() > (int)$editSession['expires_at']) {
    unset($_SESSION['dj_edit']);
    $redirectToRequest('expired');
}

$djId = (int)$editSession['dj_id'];

$stmt = $pdo->prepare('SELECT * FROM djs WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $djId]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    unset($_SESSION['dj_edit']);
    $redirectToRequest('missing');
}

$existingImagePath = $record['image_path'] ?? null;
$pageTitle = 'Editar perfil - djs.ar';

$errors = [];
$imageError = null;
$status = $_GET['status'] ?? '';
$successNotice = $status === 'saved';

$formData = [
    'name' => $record['name'] ?? '',
    'genre' => $record['genre'] ?? '',
    'location_city' => $record['location_city'] ?? '',
    'location_province' => $record['location_province'] ?? '',
    'bio' => $record['bio'] ?? '',
    'technical_rider' => $record['technical_rider'] ?? '',
    'press_kit_url' => $record['press_kit_url'] ?? '',
    'contact_email' => $record['contact_email'] ?? '',
    'sc_url' => $record['sc_url'] ?? '',
    'yt_url' => $record['yt_url'] ?? '',
    'ig_url' => $record['ig_url'] ?? '',
    'image_path' => $record['image_path'] ?? '',
];

$deletedSuccessfully = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_profile']) && $_POST['delete_profile'] === '1') {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!validate_csrf_token($token)) {
        $errors[] = 'Token invalido. Recarga la pagina.';
        http_response_code(400);
    } else {
        $pdo->beginTransaction();
        try {
            $deleteProfileStmt = $pdo->prepare('DELETE FROM djs WHERE id = :id LIMIT 1');
            $deleteProfileStmt->execute([':id' => $djId]);

            if ($deleteProfileStmt->rowCount() === 0) {
                throw new RuntimeException('El perfil no existe.');
            }

            $deleteTokensStmt = $pdo->prepare('DELETE FROM dj_edit_tokens WHERE dj_id = :dj_id');
            $deleteTokensStmt->execute([':dj_id' => $djId]);

            $pdo->commit();
            $deletedSuccessfully = true;
        } catch (Throwable $exception) {
            $pdo->rollBack();
            $errors[] = 'No pudimos eliminar el perfil. Intenta de nuevo.';
        }

        if ($deletedSuccessfully) {
            if ($existingImagePath && strpos($existingImagePath, 'uploads/') === 0) {
                $fullPath = __DIR__ . '/' . ltrim($existingImagePath, '/');
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }

            unset($_SESSION['dj_edit']);
            $pageTitle = 'Perfil eliminado - djs.ar';
            require __DIR__ . '/templates/header.php';
            ?>
            <section class="empty-state">
                <h1>Perfil eliminado</h1>
                <p>Borramos este perfil del directorio. Si queres volver a aparecer, podes crear uno nuevo cuando quieras.</p>
                <a class="button" href="/">Ir al directorio</a>
            </section>
            <?php
            require __DIR__ . '/templates/footer.php';
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_profile'])) {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!validate_csrf_token($token)) {
        $errors[] = 'Token invalido. Recarga la pagina.';
    }

    $formData['name'] = sanitize_text($_POST['name'] ?? '', 120);
    $rawGenreInput = $_POST['genre'] ?? '';
    $formData['genre'] = normalize_genre_list($rawGenreInput);
    $formData['location_city'] = sanitize_text($_POST['location_city'] ?? '', 120);
    $formData['location_province'] = sanitize_text($_POST['location_province'] ?? '', 120);
    $formData['bio'] = sanitize_long_text($_POST['bio'] ?? '', 1200);
    $formData['technical_rider'] = sanitize_long_text($_POST['technical_rider'] ?? '', 2000);
    $rawPressKit = trim((string)($_POST['press_kit_url'] ?? ''));
    $formData['press_kit_url'] = sanitize_text($rawPressKit, 255, false);
    $normalizedPressKit = $formData['press_kit_url'] === '' ? null : normalize_url($formData['press_kit_url']);
    $formData['contact_email'] = sanitize_text($_POST['contact_email'] ?? '', 255, false);
    $rawScInput = $_POST['sc_url'] ?? '';
    $rawYtInput = $_POST['yt_url'] ?? '';
    $normalizedYtUrl = normalize_youtube_url($rawYtInput);
    $ytId = null;
    $formData['ig_url'] = normalize_instagram_url($_POST['ig_url'] ?? '');
    $removeImage = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';

    if ($formData['name'] === '') {
        $errors[] = 'El nombre es obligatorio.';
    }

    if ($formData['genre'] === '') {
        $errors[] = 'Los generos son obligatorios.';
    }

    if ($formData['bio'] !== '' && mb_strlen($formData['bio']) > 1200) {
        $errors[] = 'La bio no puede superar los 1200 caracteres.';
        $formData['bio'] = mb_substr($formData['bio'], 0, 1200);
    }

    if ($formData['technical_rider'] !== '' && mb_strlen($formData['technical_rider']) > 2000) {
        $errors[] = 'El rider tecnico no puede superar los 2000 caracteres.';
        $formData['technical_rider'] = mb_substr($formData['technical_rider'], 0, 2000);
    }

    if ($formData['press_kit_url'] !== '' && $normalizedPressKit === null) {
        $errors[] = 'Ingresa un enlace valido para el press kit.';
    } elseif ($normalizedPressKit !== null) {
        $formData['press_kit_url'] = $normalizedPressKit;
    } else {
        $formData['press_kit_url'] = '';
    }

    $scUrl = extract_soundcloud_url($rawScInput);
    if ($scUrl === null && trim((string)$rawScInput) !== '') {
        $errors[] = 'Ingresa un enlace valido de SoundCloud.';
    }
    $formData['sc_url'] = $scUrl;

    if ($normalizedYtUrl === null) {
        if (trim((string)$rawYtInput) !== '') {
            $errors[] = 'Ingresa un enlace valido de YouTube.';
        }
        $formData['yt_url'] = null;
    } else {
        $formData['yt_url'] = $normalizedYtUrl;
        $ytId = extract_youtube_id($normalizedYtUrl);
    }

    if ($formData['ig_url'] === null && trim((string)($_POST['ig_url'] ?? '')) !== '') {
        $errors[] = 'Ingresa un enlace valido de Instagram.';
    }

    if (!empty($formData['contact_email'])) {
        if (!filter_var($formData['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingresa un email de contacto valido.';
        } else {
            $formData['contact_email'] = strtolower($formData['contact_email']);
        }
    } else {
        $formData['contact_email'] = '';
    }

    $newImagePath = $existingImagePath;
    $oldImageToRemove = null;

    if (!empty($_FILES['image']['name'])) {
        try {
            $newImagePath = process_profile_image($_FILES['image'], (string)$record['slug']);
            if ($existingImagePath && $existingImagePath !== $newImagePath) {
                $oldImageToRemove = $existingImagePath;
            }
        } catch (RuntimeException $exception) {
            $imageError = $exception->getMessage();
            $errors[] = $imageError;
        }
    } elseif ($removeImage && $existingImagePath) {
        $newImagePath = null;
        $oldImageToRemove = $existingImagePath;
    }

    if (empty($errors)) {
        $pdo->beginTransaction();

        try {
            $scEmbed = $scUrl ? build_soundcloud_embed($scUrl) : null;
            $ytEmbed = $ytId ? build_youtube_embed($ytId) : null;

            $stmt = $pdo->prepare(
                'UPDATE djs SET
                    name = :name,
                    genre = :genre,
                    location_city = :city,
                    location_province = :province,
                    bio = :bio,
                    technical_rider = :technical_rider,
                    press_kit_url = :press_kit_url,
                    contact_email = :contact_email,
                    sc_url = :sc_url,
                    yt_url = :yt_url,
                    ig_url = :ig_url,
                    sc_embed = :sc_embed,
                    yt_embed = :yt_embed,
                    image_path = :image_path
                 WHERE id = :id'
            );

            $stmt->execute([
                ':name' => $formData['name'],
                ':genre' => $formData['genre'],
                ':city' => $formData['location_city'] ?: null,
                ':province' => $formData['location_province'] ?: null,
                ':bio' => $formData['bio'] ?: null,
                ':technical_rider' => $formData['technical_rider'] ?: null,
                ':press_kit_url' => $formData['press_kit_url'] !== '' ? $formData['press_kit_url'] : null,
                ':contact_email' => $formData['contact_email'] ?: null,
                ':sc_url' => $formData['sc_url'],
                ':yt_url' => $formData['yt_url'],
                ':ig_url' => $formData['ig_url'],
                ':sc_embed' => $scEmbed,
                ':yt_embed' => $ytEmbed,
                ':image_path' => $newImagePath,
                ':id' => $djId,
            ]);

            $pdo->commit();

            if ($oldImageToRemove && strpos($oldImageToRemove, 'uploads/') === 0) {
                $fullPath = __DIR__ . '/' . ltrim($oldImageToRemove, '/');
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }

            $_SESSION['dj_edit']['expires_at'] = time() + 3600;

            $redirectUrl = '/profile-edit-form.php?status=saved';
            header('Location: ' . $redirectUrl);
            exit;
        } catch (Throwable $exception) {
            $pdo->rollBack();
            $errors[] = 'No pudimos guardar los cambios. Intenta de nuevo.';
            http_response_code(500);
        }
    } else {
        $formData['image_path'] = $newImagePath;
        http_response_code(422);
    }
}

$csrfToken = create_csrf_token();

require __DIR__ . '/templates/header.php';
require __DIR__ . '/templates/profile-edit-form.php';
require __DIR__ . '/templates/footer.php';

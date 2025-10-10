<?php
declare(strict_types=1);

use PDO;
use RuntimeException;
use Throwable;

require __DIR__ . '/../includes/helpers.php';
ensure_session();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/admin.php';

$adminUser = admin_require_login($pdo);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
}

if ($id <= 0) {
    header('Location: /admin/index.php?status=missing');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM djs WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    header('Location: /admin/index.php?status=missing');
    exit;
}

$supportsUpdatedAt = table_has_column($pdo, 'djs', 'updated_at');

$errors = [];
$imageError = null;
$formData = [
    'id' => $record['id'],
    'slug' => $record['slug'] ?? '',
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!validate_csrf_token($token)) {
        $errors[] = 'Token invalido. Recarga la pagina.';
    }

    $rawSlugInput = (string)($_POST['slug'] ?? '');
    $formData['slug'] = sanitize_slug($rawSlugInput);
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

    if ($rawSlugInput === '') {
        $errors[] = 'El subdominio es obligatorio.';
    } elseif ($formData['slug'] === '') {
        $errors[] = 'El subdominio solo puede incluir letras, numeros y guiones medios.';
    } elseif (strlen($formData['slug']) < 3) {
        $errors[] = 'El subdominio debe tener al menos 3 caracteres.';
    } elseif (strlen($formData['slug']) > 63) {
        $errors[] = 'El subdominio no puede superar los 63 caracteres.';
    }

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

    $slugCheck = $pdo->prepare('SELECT COUNT(*) FROM djs WHERE slug = :slug AND id <> :id');
    $slugCheck->execute([
        ':slug' => $formData['slug'],
        ':id' => $id,
    ]);
    if ((int)$slugCheck->fetchColumn() > 0) {
        $errors[] = 'Ese subdominio ya esta en uso.';
    }

    $newImagePath = null;
    if (!empty($_FILES['image']['name'])) {
        try {
            $imageSlug = $formData['slug'] !== '' ? $formData['slug'] : slugify($formData['name'] ?: 'dj');
            $newImagePath = process_profile_image($_FILES['image'], $imageSlug);
        } catch (RuntimeException $exception) {
            $imageError = $exception->getMessage();
            $errors[] = $imageError;
        }
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $scEmbed = $scUrl ? build_soundcloud_embed($scUrl) : null;
            $ytEmbed = $ytId ? build_youtube_embed($ytId) : null;

            $updateParts = [
                'slug = :slug',
                'name = :name',
                'genre = :genre',
                'location_city = :city',
                'location_province = :province',
                'bio = :bio',
                'technical_rider = :technical_rider',
                'press_kit_url = :press_kit_url',
                'contact_email = :contact_email',
                'sc_url = :sc_url',
                'yt_url = :yt_url',
                'ig_url = :ig_url',
                'sc_embed = :sc_embed',
                'yt_embed = :yt_embed',
                'image_path = :image_path',
            ];

            if ($supportsUpdatedAt) {
                $updateParts[] = 'updated_at = NOW()';
            }

            $update = $pdo->prepare(
                'UPDATE djs SET ' . implode(', ', $updateParts) . ' WHERE id = :id'
            );

            $finalImagePath = $formData['image_path'];
            if ($newImagePath !== null) {
                $finalImagePath = $newImagePath;
            } elseif ($removeImage) {
                $finalImagePath = null;
            }

            $update->execute([
                ':slug' => $formData['slug'],
                ':name' => $formData['name'],
                ':genre' => $formData['genre'],
                ':city' => $formData['location_city'] ?: null,
                ':province' => $formData['location_province'] ?: null,
                ':bio' => $formData['bio'] ?: null,
                ':technical_rider' => $formData['technical_rider'] ?: null,
                ':press_kit_url' => $formData['press_kit_url'] ?: null,
                ':contact_email' => $formData['contact_email'] ?: null,
                ':sc_url' => $scUrl,
                ':yt_url' => $formData['yt_url'],
                ':ig_url' => $formData['ig_url'],
                ':sc_embed' => $scEmbed,
                ':yt_embed' => $ytEmbed,
                ':image_path' => $finalImagePath,
                ':id' => $id,
            ]);

            $pdo->commit();
        } catch (Throwable $throwable) {
            $pdo->rollBack();
            $errors[] = 'No se pudo guardar el perfil.';
        }

        if (empty($errors)) {
            if ($newImagePath !== null && !empty($formData['image_path'])) {
                $oldPath = __DIR__ . '/../' . ltrim((string)$formData['image_path'], '/\\');
                $oldReal = realpath($oldPath);
                $uploadsDir = realpath(__DIR__ . '/../uploads');
                if ($uploadsDir !== false && $oldReal !== false && str_starts_with($oldReal, $uploadsDir)) {
                    @unlink($oldReal);
                }
            }

            if ($removeImage && !empty($formData['image_path']) && $newImagePath === null) {
                $oldPath = __DIR__ . '/../' . ltrim((string)$formData['image_path'], '/\\');
                $oldReal = realpath($oldPath);
                $uploadsDir = realpath(__DIR__ . '/../uploads');
                if ($uploadsDir !== false && $oldReal !== false && str_starts_with($oldReal, $uploadsDir)) {
                    @unlink($oldReal);
                }
            }

            header('Location: /admin/index.php?status=saved');
            exit;
        }
    }
}

$pageTitle = 'Editar · ' . ($formData['name'] ?? '');
$csrfToken = create_csrf_token();

require __DIR__ . '/../templates/admin_header.php';
?>
<section class="admin-card">
    <h1 style="margin-top:0; text-transform:uppercase; letter-spacing:0.18em; font-size:1.18rem;">Editar perfil</h1>

    <?php if (!empty($errors)): ?>
        <div class="admin-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= e($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="admin-form" novalidate>
        <input type="hidden" name="id" value="<?= e((string)$formData['id']) ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <div class="admin-form__grid">
            <div class="admin-field">
                <label for="name">Nombre artistico *</label>
                <input type="text" id="name" name="name" maxlength="120" required value="<?= e($formData['name']) ?>">
            </div>
            <div class="admin-field">
                <label for="slug">Subdominio *</label>
                <input type="text" id="slug" name="slug" maxlength="63" pattern="[A-Za-z0-9-]+" required value="<?= e($formData['slug']) ?>">
            </div>
            <div class="admin-field">
                <label for="genre">Generos *</label>
                <input type="text" id="genre" name="genre" maxlength="120" required value="<?= e($formData['genre']) ?>">
            </div>
            <div class="admin-field">
                <label for="location_province">Provincia</label>
                <?php $provinces = require __DIR__ . '/../includes/provinces.php'; $selectedProvince = $formData['location_province'] ?? ''; ?>
                <select id="location_province" name="location_province">
                    <option value=""<?= $selectedProvince === '' ? ' selected' : '' ?>>Provincia</option>
                    <?php foreach ($provinces as $prov): ?>
                        <option value="<?= e($prov) ?>"<?= $selectedProvince === $prov ? ' selected' : '' ?>><?= e($prov) ?></option>
                    <?php endforeach; ?>
                    <?php if ($selectedProvince !== '' && !in_array($selectedProvince, $provinces, true)): ?>
                        <option value="<?= e($selectedProvince) ?>" selected><?= e($selectedProvince) ?> (actual)</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="admin-field">
                <label for="location_city">Ciudad</label>
                <input type="text" id="location_city" name="location_city" maxlength="120" value="<?= e($formData['location_city']) ?>">
            </div>
            <div class="admin-field">
                <label for="contact_email">Email</label>
                <input type="email" id="contact_email" name="contact_email" value="<?= e($formData['contact_email']) ?>">
            </div>
            <div class="admin-field">
                <label for="press_kit_url">Press kit (URL)</label>
                <input type="url" id="press_kit_url" name="press_kit_url" value="<?= e($formData['press_kit_url']) ?>">
            </div>
            <div class="admin-field">
                <label for="sc_url">SoundCloud</label>
                <input type="url" id="sc_url" name="sc_url" value="<?= e($formData['sc_url']) ?>">
            </div>
            <div class="admin-field">
                <label for="yt_url">YouTube</label>
                <input type="url" id="yt_url" name="yt_url" value="<?= e($formData['yt_url']) ?>">
            </div>
            <div class="admin-field">
                <label for="ig_url">Instagram</label>
                <input type="url" id="ig_url" name="ig_url" value="<?= e((string)$formData['ig_url']) ?>">
            </div>
        </div>

        <div class="admin-field">
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" maxlength="1200" rows="6"><?= e($formData['bio']) ?></textarea>
        </div>

        <div class="admin-field">
            <label for="technical_rider">Rider tecnico</label>
            <textarea id="technical_rider" name="technical_rider" maxlength="2000" rows="6"><?= e($formData['technical_rider']) ?></textarea>
        </div>

        <div class="admin-field">
            <label for="image">Imagen de perfil (JPG/PNG/WebP)</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
            <?php if (!empty($formData['image_path'])): ?>
                <div style="margin-top:8px; display:flex; gap:12px; align-items:center;">
                    <img src="/<?= e(ltrim((string)$formData['image_path'], '/')) ?>" alt="Imagen actual" style="height:60px; border-radius:8px; border:1px solid rgba(255,255,255,0.12);">
                    <label style="display:flex; gap:6px; align-items:center; font-size:0.82rem;">
                        <input type="checkbox" name="remove_image" value="1">
                        Borrar imagen actual
                    </label>
                </div>
            <?php endif; ?>
            <?php if ($imageError): ?>
                <small><?= e($imageError) ?></small>
            <?php endif; ?>
        </div>

        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <button type="submit" class="admin-button">Guardar cambios</button>
            <a href="/admin/index.php" class="admin-button">Volver</a>
        </div>
    </form>
</section>
<?php
require __DIR__ . '/../templates/admin_footer.php';

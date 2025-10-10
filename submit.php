<?php
declare(strict_types=1);

use PDO;
use RuntimeException;
use Throwable;

require __DIR__ . '/includes/helpers.php';
ensure_session();

require __DIR__ . '/includes/db.php';

$errors = [];
$formData = [
    'slug' => '',
    'name' => '',
    'genre' => '',
    'location_city' => '',
    'location_province' => '',
    'bio' => '',
    'technical_rider' => '',
    'press_kit_url' => '',
    'contact_email' => '',
    'sc_url' => '',
    'yt_url' => '',
    'ig_url' => '',
];
$imageError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    if (!check_rate_limit('submit_form', 30)) {
        $errors[] = 'Espera unos segundos antes de enviar de nuevo.';
    }

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token invalido. Recarga la pagina e intenta de nuevo.';
    }

    if (!empty($_POST['website'])) {
        $errors[] = 'Enviar rechazado.';
    }

    // Verify reCAPTCHA if configured
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? null;
    if (defined('RECAPTCHA_SITE_KEY') && defined('RECAPTCHA_SECRET') && RECAPTCHA_SITE_KEY !== 'YOUR_RECAPTCHA_SITE_KEY' && RECAPTCHA_SECRET !== 'YOUR_RECAPTCHA_SECRET') {
        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? null;
        if (!verify_recaptcha($recaptchaResponse, $remoteIp, 'submit')) {
            $errors[] = 'Verificacion de reCAPTCHA fallida. Intenta de nuevo.';
        }
    }

    $hasSlugError = false;
    if (trim($rawSlugInput) === '') {
        $errors[] = 'El subdominio es obligatorio.';
        $hasSlugError = true;
    } elseif ($formData['slug'] === '') {
        $errors[] = 'El subdominio solo puede incluir letras, numeros y guiones medios.';
        $hasSlugError = true;
    } elseif (strlen($formData['slug']) < 3) {
        $errors[] = 'El subdominio debe tener al menos 3 caracteres.';
        $hasSlugError = true;
    } elseif (strlen($formData['slug']) > 63) {
        $errors[] = 'El subdominio no puede superar los 63 caracteres.';
        $hasSlugError = true;
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

    if ($rawPressKit !== '' && $formData['press_kit_url'] === null) {
        $errors[] = 'Ingresa un enlace valido para el press kit.';
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

    if (!$hasSlugError && slug_exists($pdo, $formData['slug'])) {
        $errors[] = 'Ese subdominio ya esta en uso. Elegi otro.';
    }

    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        try {
            $imageSlug = $formData['slug'] !== '' ? $formData['slug'] : slugify($formData['name'] ?: 'dj');
            $imagePath = process_profile_image($_FILES['image'], $imageSlug);
        } catch (RuntimeException $exception) {
            $imageError = $exception->getMessage();
            $errors[] = $imageError;
        }
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $slug = $formData['slug'];
            $scEmbed = $scUrl ? build_soundcloud_embed($scUrl) : null;
            $ytEmbed = $ytId ? build_youtube_embed($ytId) : null;

            $stmt = $pdo->prepare(
                'INSERT INTO djs (slug, name, genre, location_city, location_province, bio, technical_rider, press_kit_url, contact_email, sc_url, yt_url, ig_url, sc_embed, yt_embed, image_path)
                 VALUES (:slug, :name, :genre, :city, :province, :bio, :technical_rider, :press_kit_url, :contact_email, :sc_url, :yt_url, :ig_url, :sc_embed, :yt_embed, :image_path)'
            );

            $stmt->execute([
                ':slug' => $slug,
                ':name' => $formData['name'],
                ':genre' => $formData['genre'],
                ':city' => $formData['location_city'] ?: null,
                ':province' => $formData['location_province'] ?: null,
                ':bio' => $formData['bio'] ?: null,
                ':technical_rider' => $formData['technical_rider'] ?: null,
                ':press_kit_url' => $normalizedPressKit,
                ':contact_email' => $formData['contact_email'] ?: null,
                ':sc_url' => $scUrl,
                ':yt_url' => $formData['yt_url'],
                ':ig_url' => $formData['ig_url'],
                ':sc_embed' => $scEmbed,
                ':yt_embed' => $ytEmbed,
                ':image_path' => $imagePath,
            ]);

            $pdo->commit();
            $redirectHost = $slug . '.djs.ar';
            header('Location: https://' . $redirectHost . '/');
            exit;
        } catch (Throwable $throwable) {
            $pdo->rollBack();
            $errors[] = 'No pudimos guardar el perfil. Intenta de nuevo.';
        }
    } else {
        http_response_code(422);
    }
}

$pageTitle = 'Agregar DJ al directorio';
$csrfToken = create_csrf_token();

// Hide main navigation on the submit form page
$hideMainNav = true;
require __DIR__ . '/templates/header.php';
require __DIR__ . '/templates/submit-form.php';
require __DIR__ . '/templates/footer.php';



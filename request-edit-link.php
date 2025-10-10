<?php
declare(strict_types=1);

require __DIR__ . '/includes/helpers.php';
ensure_session();
require __DIR__ . '/includes/db.php';

purge_stale_edit_tokens($pdo);

$pageTitle = 'Editar tu perfil - djs.ar';

$status = $_GET['status'] ?? '';
$formData = [
    'slug' => sanitize_slug($_GET['slug'] ?? ''),
    'contact_email' => '',
];
$errors = [];
$successNotice = $status === 'sent';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawSlug = (string)($_POST['slug'] ?? '');
    $formData['slug'] = sanitize_slug($rawSlug);
    $rawEmail = sanitize_text($_POST['contact_email'] ?? '', 255, false);
    $formData['contact_email'] = strtolower($rawEmail);

    $honeypot = trim((string)($_POST['website'] ?? ''));
    if ($honeypot !== '') {
        // Silently act as if it succeeded.
        header('Location: /request-edit-link.php?status=sent');
        exit;
    }

    $token = (string)($_POST['csrf_token'] ?? '');
    if (!validate_csrf_token($token)) {
        $errors[] = 'Token invalido. Recarga la pagina.';
    }

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $rateKey = 'request_edit_' . ($formData['slug'] ?: 'none') . '_' . ($ipAddress !== '' ? $ipAddress : 'anonymous');
    if (!check_rate_limit($rateKey, 120)) {
        $errors[] = 'Espera un momento antes de pedir otro enlace.';
    }

    if ($formData['slug'] === '') {
        $errors[] = 'Ingresa el subdominio de tu perfil.';
    }

    if ($formData['contact_email'] === '' || !filter_var($formData['contact_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ingresa un email valido.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id, name, slug, contact_email FROM djs WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $formData['slug']]);
        $dj = $stmt->fetch(PDO::FETCH_ASSOC);

        $shouldSend = false;
        if ($dj && !empty($dj['contact_email'])) {
            $storedEmail = strtolower((string)$dj['contact_email']);
            if ($storedEmail === $formData['contact_email']) {
                $shouldSend = true;
            }
        }

        if ($shouldSend) {
            $tokenValue = create_edit_token($pdo, (int)$dj['id']);
            $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? 'djs.ar'));
            $hostParts = array_values(array_filter(explode('.', $host)));
            if (count($hostParts) >= 2) {
                $baseHost = implode('.', array_slice($hostParts, -2));
            } else {
                $baseHost = $host !== '' ? $host : 'djs.ar';
            }
            $editUrl = 'https://' . $baseHost . '/profile-edit.php?token=' . $tokenValue;

            $subject = 'Tu enlace para editar el perfil en djs.ar';
            $message = "Hola {$dj['name']},\n\n" .
                "Recibimos una solicitud para editar tu perfil en djs.ar.\n\n" .
                "Usa este enlace (solo funciona una vez y vence en 45 minutos):\n{$editUrl}\n\n" .
                "Si no pediste este enlace, podes ignorar este mensaje.\n\n" .
                "Equipo djs.ar";

            $headers = "From: djs.ar <hola@djs.ar>\r\n";
            $headers .= "Reply-To: hola@djs.ar\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            @mail($formData['contact_email'], $subject, $message, $headers);
        }

        $redirectUrl = '/request-edit-link.php?status=sent';
        if ($formData['slug'] !== '') {
            $redirectUrl .= '&slug=' . rawurlencode($formData['slug']);
        }
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        http_response_code(422);
    }
}

$csrfToken = create_csrf_token();

require __DIR__ . '/templates/header.php';
require __DIR__ . '/templates/request-edit-link.php';
require __DIR__ . '/templates/footer.php';

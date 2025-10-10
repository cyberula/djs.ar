<?php
declare(strict_types=1);

use PDO;
use RuntimeException;

function ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sanitize_text(?string $value, int $maxLength, bool $stripTags = true): string
{
    $value = trim((string)$value);
    if ($stripTags) {
        $value = strip_tags($value);
    }
    return mb_substr($value, 0, $maxLength);
}

function sanitize_long_text(?string $value, int $maxLength): string
{
    $value = (string)$value;
    $value = strip_tags($value);
    $value = preg_replace('~\r\n?~', "\n", $value);
    $value = trim($value);
    return mb_substr($value, 0, $maxLength);
}

function sanitize_slug(?string $value): string
{
    $value = strtolower((string)$value);
    $value = preg_replace('~[^a-z0-9-]+~', '', $value);
    return trim($value, '-');
}

function slugify(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'dj-' . substr(bin2hex(random_bytes(4)), 0, 8);
    }
    $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
    $transliterated = $transliterated !== false ? $transliterated : $value;
    $slug = preg_replace('~[^a-z0-9]+~i', '-', $transliterated);
    $slug = strtolower(trim($slug, '-'));

    return $slug !== '' ? $slug : 'dj-' . substr(bin2hex(random_bytes(4)), 0, 8);
}

function normalize_genre_list(?string $value, int $maxItems = 6): string
{
    $parts = preg_split('/[,;]+/', (string)$value) ?: [];
    $seen = [];
    $clean = [];

    foreach ($parts as $part) {
        $part = sanitize_text($part, 40);
        if ($part === '') {
            continue;
        }
        $key = strtolower($part);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $clean[] = $part;
        if (count($clean) >= $maxItems) {
            break;
        }
    }

    return implode(', ', $clean);
}

function split_genres(?string $value): array
{
    if ($value === null || trim($value) === '') {
        return [];
    }

    $parts = explode(',', $value);
    $result = [];

    foreach ($parts as $part) {
        $clean = sanitize_text($part, 40);
        if ($clean === '') {
            continue;
        }
        $result[] = $clean;
    }

    return $result;
}

function generate_unique_slug(PDO $pdo, string $name): string
{
    $base = slugify($name);
    $slug = $base;
    $counter = 1;

    while (slug_exists($pdo, $slug)) {
        $slug = $base . '-' . $counter;
        $counter++;
    }

    return $slug;
}

function slug_exists(PDO $pdo, string $slug): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM djs WHERE slug = :slug LIMIT 1');
    $stmt->execute([':slug' => $slug]);

    return (bool)$stmt->fetchColumn();
}

function normalize_url(?string $value, array $allowedHostSuffixes = []): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $value = strip_tags($value);
    if (preg_match('~src\s*=\s*"([^"]+)"~i', $value, $matches)) {
        $value = $matches[1];
    }

    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    if (!preg_match('~^https?://~i', $value)) {
        $value = 'https://' . ltrim($value, '/');
    }

    $parts = parse_url($value);
    if ($parts === false || empty($parts['host'])) {
        return null;
    }

    $host = strtolower($parts['host']);
    if ($allowedHostSuffixes) {
        $valid = false;
        foreach ($allowedHostSuffixes as $suffix) {
            $suffix = strtolower($suffix);
            if ($host === $suffix || str_ends_with($host, '.' . $suffix)) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            return null;
        }
    }

    $normalized = strtolower($parts['scheme']) . '://' . $host;
    if (!empty($parts['port'])) {
        $normalized .= ':' . $parts['port'];
    }
    if (!empty($parts['path'])) {
        $normalized .= preg_replace('~/+~', '/', $parts['path']);
    }
    if (!empty($parts['query'])) {
        $normalized .= '?' . $parts['query'];
    }
    if (!empty($parts['fragment'])) {
        $normalized .= '#' . $parts['fragment'];
    }

    return $normalized;
}

function normalize_instagram_url(?string $value): ?string
{
    $url = normalize_url($value, ['instagram.com']);
    if ($url === null) {
        return null;
    }
    return rtrim($url, '/');
}

function extract_soundcloud_url(?string $input): ?string
{
    $input = trim((string)$input);
    if ($input === '') {
        return null;
    }

    $url = normalize_url($input, ['soundcloud.com', 'snd.sc', 'w.soundcloud.com', 'api.soundcloud.com']);
    if ($url === null) {
        return null;
    }

    $host = parse_url($url, PHP_URL_HOST);
    if ($host === null) {
        return null;
    }

    $host = strtolower($host);
    if (!str_contains($host, 'soundcloud.com') && !str_contains($host, 'snd.sc')) {
        return null;
    }

    return $url;
}

function build_soundcloud_embed(string $url): string
{
    $encoded = rawurlencode($url);
    $src = 'https://w.soundcloud.com/player/?url=' . $encoded . '&color=%23ff5500&auto_play=false&hide_related=false&show_comments=true&show_user=true&show_reposts=false&visual=true';

    return '<iframe title="SoundCloud Player" width="100%" height="280" scrolling="no" frameborder="no" allow="autoplay" src="' . e($src) . '"></iframe>';
}

function normalize_youtube_url(?string $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $url = normalize_url($value, ['youtube.com', 'youtu.be']);
    if ($url === null) {
        return null;
    }

    $videoId = extract_youtube_id($url);
    if ($videoId !== null) {
        return build_youtube_watch_url($videoId);
    }

    $parts = parse_url($url);
    if ($parts === false || empty($parts['host'])) {
        return null;
    }

    $host = strtolower($parts['host']);
    if ($host === 'youtu.be') {
        // Short links without a valid video ID are not useful.
        return null;
    }

    if (!str_ends_with($host, 'youtube.com')) {
        return null;
    }

    $path = isset($parts['path']) ? preg_replace('~/+~', '/', $parts['path']) : '';
    $path = $path !== '' ? '/' . ltrim($path, '/') : '';

    if ($path !== '/') {
        $path = rtrim($path, '/');
    }

    if (str_starts_with($path, '/watch')) {
        // Watch URLs without a video id were already rejected above.
        return null;
    }

    $query = $parts['query'] ?? '';
    $fragment = $parts['fragment'] ?? '';

    $normalized = 'https://www.youtube.com';
    $normalized .= $path === '' ? '/' : $path;

    if ($query !== '') {
        $normalized .= '?' . $query;
    }

    if ($fragment !== '') {
        $normalized .= '#' . $fragment;
    }

    return $normalized;
}

function extract_youtube_id(?string $input): ?string
{
    $input = trim((string)$input);
    if ($input === '') {
        return null;
    }

    if (preg_match('~src\s*=\s*"([^"]+)"~i', $input, $matches)) {
        $input = $matches[1];
    }

    $input = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $url = normalize_url($input, ['youtube.com', 'youtu.be']);
    if ($url === null) {
        return null;
    }

    $parts = parse_url($url);
    if ($parts === false || empty($parts['host'])) {
        return null;
    }

    $host = strtolower($parts['host']);
    $path = $parts['path'] ?? '';
    $cleanPath = trim($path, '/');

    if ($host === 'youtu.be') {
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $cleanPath)) {
            return $cleanPath;
        }
        return null;
    }

    if (!str_ends_with($host, 'youtube.com')) {
        return null;
    }

    parse_str($parts['query'] ?? '', $queryParams);
    if (isset($queryParams['v']) && preg_match('/^[A-Za-z0-9_-]{11}$/', (string)$queryParams['v'])) {
        return (string)$queryParams['v'];
    }

    $segments = explode('/', $cleanPath);
    if (count($segments) >= 2) {
        $prefix = strtolower($segments[0]);
        if (in_array($prefix, ['embed', 'shorts', 'live'], true)) {
            $candidate = $segments[1];
            if (preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate)) {
                return $candidate;
            }
        }
    }

    return null;
}

function build_youtube_embed(string $videoId): string
{
    $src = 'https://www.youtube.com/embed/' . $videoId;

    return '<iframe title="YouTube player" width="100%" height="315" src="' . e($src) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>';
}

function build_youtube_watch_url(string $videoId): string
{
    return 'https://www.youtube.com/watch?v=' . $videoId;
}

function create_csrf_token(): string
{
    ensure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token(?string $token): bool
{
    ensure_session();
    $expected = $_SESSION['csrf_token'] ?? null;
    return $expected !== null && hash_equals($expected, (string)$token);
}

function check_rate_limit(string $key, int $seconds): bool
{
    ensure_session();
    $nextAllowed = $_SESSION['rate_limit'][$key] ?? 0;
    $now = time();

    if ($now < $nextAllowed) {
        return false;
    }

    $_SESSION['rate_limit'][$key] = $now + $seconds;
    return true;
}

function build_filter_query(array $params): string
{
    if (empty($params)) {
        return '';
    }
    return '?' . http_build_query($params);
}

function build_page_url(int $page, array $params): string
{
    $params = $params;
    $params['page'] = $page;
    return build_filter_query($params) ?: '?page=' . $page;
}

function process_profile_image(array $file, string $slug): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir la imagen.');
    }

    // We no longer hard-fail on >2MB; we'll try to compress instead.

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Formato de imagen no soportado. Usa JPG, PNG o WebP.');
    }

    $uploadsDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
        throw new RuntimeException('No se pudo crear la carpeta de uploads.');
    }

    $filenameBase = $slug . '-' . time();
    $relativePath = 'uploads/' . $filenameBase . '.webp';
    $targetPath = $uploadsDir . '/' . $filenameBase . '.webp';

    if (!function_exists('imagewebp') || !function_exists('imagecreatetruecolor')) {
        $relativePath = 'uploads/' . $filenameBase . '.' . $allowed[$mime];
        $targetPath = $uploadsDir . '/' . $filenameBase . '.' . $allowed[$mime];

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }

        return $relativePath;
    }

    switch ($mime) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $source = imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            $source = null;
    }

    if (!$source) {
        throw new RuntimeException('No se pudo procesar la imagen.');
    }

    $width = imagesx($source);
    $height = imagesy($source);
    $maxDimension = 1200; // allow a bit larger, compression will handle size
    $scale = min($maxDimension / max($width, 1), $maxDimension / max($height, 1), 1);

    if ($scale < 1) {
        $newWidth = (int)max(1, round($width * $scale));
        $newHeight = (int)max(1, round($height * $scale));
        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);
    } else {
        $canvas = $source;
    }

    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);

    // Try saving with a quality ladder if the file is large
    $qualities = [85, 80, 75, 70, 65, 60, 55];
    $saved = false;
    foreach ($qualities as $q) {
        if (imagewebp($canvas, $targetPath, $q)) {
            if (file_exists($targetPath) && filesize($targetPath) <= 2 * 1024 * 1024) {
                $saved = true;
                break;
            }
            // If too big, loop continues to try lower quality
        }
    }
    if (!$saved) {
        if ($canvas !== $source) {
            imagedestroy($canvas);
        }
        throw new RuntimeException('No se pudo comprimir/guardar la imagen en WebP.');
    }

    imagedestroy($canvas);

    return $relativePath;
}

function table_has_column(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column LIMIT 1'
    );
    $stmt->execute([':table' => $table, ':column' => $column]);

    return (bool)$stmt->fetchColumn();
}

function purge_stale_edit_tokens(PDO $pdo): void
{
    $pdo->exec(
        "DELETE FROM dj_edit_tokens
         WHERE expires_at < NOW()
            OR (consumed_at IS NOT NULL AND consumed_at < DATE_SUB(NOW(), INTERVAL 1 DAY))"
    );
}

function hash_edit_token(string $token): string
{
    return hash('sha256', $token);
}

function create_edit_token(PDO $pdo, int $djId): string
{
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash_edit_token($token);
    $expiresAt = (new DateTimeImmutable('+45 minutes'))->format('Y-m-d H:i:s');

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $ipBinary = null;
    if (!empty($ipAddress)) {
        $ipBinary = @inet_pton($ipAddress) ?: null;
    }

    $userAgent = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($userAgent === '') {
        $userAgent = null;
    } elseif (mb_strlen($userAgent) > 255) {
        $userAgent = mb_substr($userAgent, 0, 255);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO dj_edit_tokens (dj_id, token_hash, expires_at, ip_address, user_agent)
         VALUES (:dj_id, :token_hash, :expires_at, :ip_address, :user_agent)'
    );

    $stmt->bindValue(':dj_id', $djId, PDO::PARAM_INT);
    $stmt->bindValue(':token_hash', $tokenHash, PDO::PARAM_STR);
    $stmt->bindValue(':expires_at', $expiresAt, PDO::PARAM_STR);
    if ($ipBinary === null) {
        $stmt->bindValue(':ip_address', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':ip_address', $ipBinary, PDO::PARAM_STR);
    }
    if ($userAgent === null) {
        $stmt->bindValue(':user_agent', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
    }

    $stmt->execute();

    return $token;
}

function validate_edit_token(PDO $pdo, string $rawToken): ?array
{
    $token = trim($rawToken);
    if (!preg_match('/^[A-Fa-f0-9]{64}$/', $token)) {
        return null;
    }

    $tokenHash = hash_edit_token($token);

    $stmt = $pdo->prepare(
        'SELECT
            tokens.id AS token_id,
            tokens.dj_id AS token_dj_id,
            tokens.expires_at AS token_expires_at,
            tokens.consumed_at AS token_consumed_at,
            tokens.created_at AS token_created_at,
            tokens.user_agent AS token_user_agent,
            tokens.ip_address AS token_ip_address,
            djs.*
         FROM dj_edit_tokens AS tokens
         INNER JOIN djs ON tokens.dj_id = djs.id
         WHERE tokens.token_hash = :hash
         LIMIT 1'
    );
    $stmt->execute([':hash' => $tokenHash]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $tokenExpiresAt = $row['token_expires_at'] ?? null;
    $tokenConsumedAt = $row['token_consumed_at'] ?? null;

    if ($tokenExpiresAt === null || strtotime((string)$tokenExpiresAt) < time()) {
        return null;
    }

    if ($tokenConsumedAt !== null) {
        return null;
    }

    $tokenData = [
        'id' => (int)$row['token_id'],
        'dj_id' => (int)$row['token_dj_id'],
        'expires_at' => $tokenExpiresAt,
        'consumed_at' => $tokenConsumedAt,
        'created_at' => $row['token_created_at'] ?? null,
        'user_agent' => $row['token_user_agent'] ?? null,
        'ip_address' => isset($row['token_ip_address']) && $row['token_ip_address'] !== null
            ? inet_ntop($row['token_ip_address'])
            : null,
    ];

    $djData = $row;
    unset(
        $djData['token_id'],
        $djData['token_dj_id'],
        $djData['token_expires_at'],
        $djData['token_consumed_at'],
        $djData['token_created_at'],
        $djData['token_user_agent'],
        $djData['token_ip_address']
    );

    return [
        'token' => $tokenData,
        'dj' => $djData,
    ];
}

function consume_edit_token(PDO $pdo, int $tokenId): bool
{
    $stmt = $pdo->prepare(
        'UPDATE dj_edit_tokens
         SET consumed_at = NOW()
         WHERE id = :id AND consumed_at IS NULL'
    );
    $stmt->execute([':id' => $tokenId]);

    return $stmt->rowCount() > 0;
}


/**
 * Verify Google reCAPTCHA v2/v3 token server-side.
 * Returns true when verification succeeds and score/action (for v3) is acceptable.
 *
 * This function does a POST to Google's siteverify endpoint. It expects RECAPTCHA_SECRET
 * to be defined in configuration (`includes/db.php`).
 *
 * @param string|null $token The value of g-recaptcha-response from the client
 * @param string|null $remoteIp Optional remote IP address
 * @return bool
 */
function verify_recaptcha(?string $token, ?string $remoteIp = null, ?string $expectedAction = null): bool
{
    if (empty($token) || $token === '') {
        return false;
    }

    if (!defined('RECAPTCHA_SECRET') || RECAPTCHA_SECRET === 'YOUR_RECAPTCHA_SECRET') {
        // If not configured, fail closed to avoid silent bypass. You can change this behavior
        // during development by setting the constant to a real key.
        return false;
    }
    $url = 'https://www.google.com/recaptcha/api/siteverify';

    // Prefer cURL for reliability; fall back to file_get_contents if cURL isn't available
    $postFields = http_build_query([
        'secret' => RECAPTCHA_SECRET,
        'response' => $token,
        'remoteip' => $remoteIp,
    ]);

    $responseBody = false;

    if (function_exists('curl_version')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $responseBody = curl_exec($ch);
        curl_close($ch);
    } elseif (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => $postFields,
                'timeout' => 5,
            ],
        ]);
        $responseBody = @file_get_contents($url, false, $context);
    } else {
        // Can't make outbound HTTP requests
        return false;
    }

    if ($responseBody === false) {
        return false;
    }

    $json = json_decode($responseBody, true);
    if (!is_array($json) || empty($json['success'])) {
        // Log the failure for debugging if configured
        if (defined('RECAPTCHA_LOG_PATH')) {
            $log = [
                'ts' => date('c'),
                'success' => false,
                'error' => $json ?? $responseBody,
                'remoteip' => $remoteIp,
            ];
            @file_put_contents(RECAPTCHA_LOG_PATH, json_encode($log, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
        }
        return false;
    }

    // If Google returns a score (v3), enforce a minimum threshold
    if (isset($json['score'])) {
        $min = defined('RECAPTCHA_MIN_SCORE') ? RECAPTCHA_MIN_SCORE : 0.5;
        $score = (float)$json['score'];
        if ($score < $min) {
            if (defined('RECAPTCHA_LOG_PATH')) {
                $log = [
                    'ts' => date('c'),
                    'success' => true,
                    'score' => $score,
                    'min_score' => $min,
                    'action' => $json['action'] ?? null,
                    'remoteip' => $remoteIp,
                ];
                @file_put_contents(RECAPTCHA_LOG_PATH, json_encode($log, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
            }
            return false;
        }
    }

    // If an expected action is provided (v3), ensure it matches
    if ($expectedAction !== null && isset($json['action'])) {
        if (!hash_equals($expectedAction, (string)$json['action'])) {
            if (defined('RECAPTCHA_LOG_PATH')) {
                $log = [
                    'ts' => date('c'),
                    'success' => true,
                    'action' => $json['action'] ?? null,
                    'expected_action' => $expectedAction,
                    'remoteip' => $remoteIp,
                ];
                @file_put_contents(RECAPTCHA_LOG_PATH, json_encode($log, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
            }
            return false;
        }
    }

    // Log successful verification with score/action for auditing
    if (defined('RECAPTCHA_LOG_PATH')) {
        $log = [
            'ts' => date('c'),
            'success' => true,
            'score' => $json['score'] ?? null,
            'action' => $json['action'] ?? null,
            'remoteip' => $remoteIp,
        ];
        @file_put_contents(RECAPTCHA_LOG_PATH, json_encode($log, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    }

    return true;
}





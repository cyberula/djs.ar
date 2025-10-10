<?php
// normalize_utf8.php
// Uso: php normalize_utf8.php [ruta/al/archivo]
// Normaliza a Unicode NFC y guarda como UTF-8 sin BOM.

$path = $argv[1] ?? 'licencia.txt';
if (!file_exists($path)) {
    fwrite(STDERR, "Archivo no encontrado: $path\n");
    exit(1);
}

$raw = file_get_contents($path);
if ($raw === false) {
    fwrite(STDERR, "No se pudo leer el archivo\n");
    exit(2);
}

// Quitar BOM si existe
if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
    $raw = substr($raw, 3);
}

// Detectar codificación
$enc = false;
if (function_exists('mb_detect_encoding')) {
    $enc = mb_detect_encoding($raw, ['UTF-8','ISO-8859-1','Windows-1252','CP1252','ASCII'], true);
}
if ($enc === false) {
    // fallback conservador
    $enc = 'ISO-8859-1';
}

// Convertir a UTF-8 si es necesario
$utf = $raw;
if (strtoupper($enc) !== 'UTF-8') {
    if (function_exists('mb_convert_encoding')) {
        $utf = mb_convert_encoding($raw, 'UTF-8', $enc);
    } else {
        // intentar iconv
        $conv = @iconv($enc, 'UTF-8//TRANSLIT', $raw);
        if ($conv !== false) {
            $utf = $conv;
        }
    }
}

// Normalizar a NFC si está disponible
if (extension_loaded('intl') && class_exists('Normalizer')) {
    $norm = Normalizer::normalize($utf, Normalizer::FORM_C);
    if ($norm !== false) {
        $utf = $norm;
    }
}

// Escribir UTF-8 sin BOM
$result = file_put_contents($path, $utf);
if ($result === false) {
    fwrite(STDERR, "No se pudo escribir el archivo\n");
    exit(3);
}

fwrite(STDOUT, "Guardado: $path (convertido desde: $enc)\n");

// Mostrar primeros bytes hex para verificar BOM
$bytes = file_get_contents($path, false, null, 0, 3);
$hexArr = array_map(function($c){ return strtoupper(sprintf('%02X', ord($c))); }, str_split($bytes));
$hex = implode(' ', $hexArr);
fwrite(STDOUT, "Primeros bytes: $hex\n");

exit(0);

#!/usr/bin/env php
<?php
declare(strict_types=1);

// Normalize existing location_province values in the `djs` table.
// Usage:
//   php scripts/normalize_provinces.php       # dry run
//   php scripts/normalize_provinces.php --apply   # actually update rows

// Safety: dry-run by default. The script uses includes/db.php for DB connection.

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';

$argvStr = implode(' ', array_slice($GLOBALS['argv'], 1));
$apply = in_array('--apply', $GLOBALS['argv'], true);

// Load canonical province labels
$canonical = require __DIR__ . '/../includes/provinces.php';

// Build a normalized map from normalized form => canonical label
function normalize_key(string $s): string
{
    $s = trim($s);
    // Remove accents
    $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s;
    $s = mb_strtolower($s, 'UTF-8');
    // remove punctuation
    $s = preg_replace('/[^a-z0-9 ]+/', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    $s = trim($s);
    return $s;
}

$map = [];
foreach ($canonical as $label) {
    $map[normalize_key($label)] = $label;
}

// Add common aliases
$aliases = [
    'caba' => 'Ciudad Autónoma de Buenos Aires',
    'capital federal' => 'Ciudad Autónoma de Buenos Aires',
    'bs as' => 'Buenos Aires',
    'bs as.' => 'Buenos Aires',
    'bsas' => 'Buenos Aires',
    'buenos aires province' => 'Buenos Aires',
    'provincia de buenos aires' => 'Buenos Aires',
    'tierra del fuego y antartida e islas del atl' => 'Tierra del Fuego',
    'tierra del fuego' => 'Tierra del Fuego',
    'rio negro' => 'Río Negro',
    'rioja' => 'La Rioja',
];
foreach ($aliases as $k => $v) {
    $map[normalize_key($k)] = $v;
}

// Fetch rows that have a province value
$stmt = $pdo->prepare('SELECT id, location_province FROM djs WHERE location_province IS NOT NULL AND location_province <> ""');
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "No rows with a non-empty location_province found.\n";
    exit(0);
}

$changes = [];
foreach ($rows as $row) {
    $id = (int)$row['id'];
    $prov = (string)$row['location_province'];
    $norm = normalize_key($prov);
    if ($norm === '') {
        continue;
    }
    if (isset($map[$norm])) {
        $canonicalLabel = $map[$norm];
        if ($canonicalLabel !== $prov) {
            $changes[] = ['id' => $id, 'from' => $prov, 'to' => $canonicalLabel];
        }
    } else {
        // Try fuzzy match: startsWith canonical
        foreach ($map as $k => $label) {
            if ($k !== '' && str_starts_with($norm, $k)) {
                if ($label !== $prov) {
                    $changes[] = ['id' => $id, 'from' => $prov, 'to' => $label];
                }
                continue 2;
            }
        }
        // No mapping
        $changes[] = ['id' => $id, 'from' => $prov, 'to' => null];
    }
}

if (empty($changes)) {
    echo "No province values required normalization.\n";
    exit(0);
}

// Report
echo "Found " . count($changes) . " candidate changes:\n";
foreach ($changes as $c) {
    if ($c['to'] === null) {
        echo sprintf("  id=%d: '%s' -> [no mapping]\n", $c['id'], $c['from']);
    } else {
        echo sprintf("  id=%d: '%s' -> '%s'\n", $c['id'], $c['from'], $c['to']);
    }
}

if (!$apply) {
    echo "\nDry run only. To apply these changes run with --apply\n";
    exit(0);
}

// Apply changes
echo "\nApplying changes...\n";
$pdo->beginTransaction();
try {
    $update = $pdo->prepare('UPDATE djs SET location_province = :province WHERE id = :id');
    $applied = 0;
    foreach ($changes as $c) {
        if ($c['to'] === null) {
            // Skip unmapped entries; user may prefer manual handling
            continue;
        }
        $update->execute([':province' => $c['to'], ':id' => $c['id']]);
        $applied += $update->rowCount();
    }
    $pdo->commit();
    echo "Applied updates to $applied rows.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    echo "Error applying updates: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";

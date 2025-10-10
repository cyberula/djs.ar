<?php
declare(strict_types=1);

use PDO;
use PDOException;

require __DIR__ . '/../includes/helpers.php';
ensure_session();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/admin.php';

$adminUser = admin_require_login($pdo);

$search = sanitize_text($_GET['q'] ?? '', 120, false);
$params = [];
$where = '';

if ($search !== '') {
    $where = 'WHERE name LIKE :term OR slug LIKE :term';
    $params[':term'] = '%' . $search . '%';
}

$columnsBase = 'id, name, slug, genre, location_city, location_province, created_at';
$showUpdatedAt = table_has_column($pdo, 'djs', 'updated_at');
$selectColumns = $showUpdatedAt ? $columnsBase . ', updated_at' : $columnsBase;
$dbError = '';
try {
    $stmt = $pdo->prepare(
        'SELECT ' . $selectColumns . ' FROM djs '
        . $where .
        ' ORDER BY created_at DESC LIMIT 500'
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = count($rows);
} catch (PDOException $e) {
    error_log('admin/index.php DB error: ' . $e->getMessage());
    $rows = [];
    $total = 0;
    $dbError = 'Ocurrio un error al consultar la base de datos.';
}

$pageTitle = 'Panel - Perfiles';
$csrfToken = create_csrf_token();
$status = sanitize_text($_GET['status'] ?? '', 20, false);
$statusMessage = '';
$statusClass = '';

switch ($status) {
    case 'deleted':
        $statusMessage = 'Perfil eliminado correctamente.';
        $statusClass = 'admin-alert admin-alert--success';
        break;
    case 'saved':
        $statusMessage = 'Perfil actualizado.';
        $statusClass = 'admin-alert admin-alert--success';
        break;
    case 'created':
        $statusMessage = 'Perfil creado.';
        $statusClass = 'admin-alert admin-alert--success';
        break;
    case 'missing':
        $statusMessage = 'No encontramos ese perfil.';
        $statusClass = 'admin-alert';
        break;
    case 'error':
        $statusMessage = 'Ocurrio un error. Intenta de nuevo.';
        $statusClass = 'admin-alert';
        break;
    default:
        $statusMessage = '';
        $statusClass = '';
}

if ($dbError !== '') {
    $statusMessage = $dbError;
    $statusClass = 'admin-alert';
}

require __DIR__ . '/../templates/admin_header.php';
?>
<section class="admin-card">
    <header style="display:flex; flex-wrap:wrap; gap:18px; align-items:center; justify-content:space-between;">
        <div>
            <h1 style="margin:0; font-size:1.28rem; letter-spacing:0.18em; text-transform:uppercase;">Perfiles</h1>
            <p style="margin:6px 0 0; color:var(--admin-muted); letter-spacing:0.08em;"><?= e((string)$total) ?> resultados</p>
        </div>
        <form method="get" style="display:flex; gap:12px; align-items:center;">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar por nombre o slug" style="padding:10px 14px; border-radius:8px; border:1px solid rgba(255,255,255,0.08); background:rgba(8,6,16,0.9); color:var(--admin-text); min-width:220px;">
            <?php if ($search !== ''): ?>
                <a href="/admin/index.php" class="admin-button">Limpiar</a>
            <?php endif; ?>
            <button type="submit" class="admin-button">Buscar</button>
        </form>
    </header>

    <?php if ($statusMessage !== ''): ?>
        <div class="<?= e($statusClass) ?>">
            <?= e($statusMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($total === 0): ?>
        <div class="admin-empty">No hay perfiles que coincidan con la busqueda.</div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Slug</th>
                    <th>Genero</th>
                    <th>Ubicacion</th>
                    <th>Creado</th>
                    <?php if ($showUpdatedAt): ?>
                        <th>Actualizado</th>
                    <?php endif; ?>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                        $city = trim((string)($row['location_city'] ?? ''));
                        $province = trim((string)($row['location_province'] ?? ''));
                        $locationParts = array_filter([$city, $province], static fn(string $value): bool => $value !== '');
                        $location = implode(', ', $locationParts);
                    ?>
                    <tr>
                        <td><?= e((string)$row['id']) ?></td>
                        <td><?= e($row['name'] ?? '') ?></td>
                        <td><?= e($row['slug'] ?? '') ?></td>
                        <td><?= e($row['genre'] ?? '') ?></td>
                        <td><?= e($location) ?></td>
                        <td><?= e($row['created_at'] ?? '') ?></td>
                        <?php if ($showUpdatedAt): ?>
                            <td><?= e($row['updated_at'] ?? '') ?></td>
                        <?php endif; ?>
                        <td>
                            <div class="admin-actions">
                                <a class="admin-button" href="/admin/edit.php?id=<?= e((string)$row['id']) ?>">Editar</a>
                                <form method="post" action="/admin/delete.php" onsubmit="return confirm('Eliminar este perfil? Esta accion no se puede deshacer.');" style="margin:0;">
                                    <input type="hidden" name="id" value="<?= e((string)$row['id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <button type="submit" class="admin-button admin-button--danger">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php
require __DIR__ . '/../templates/admin_footer.php';

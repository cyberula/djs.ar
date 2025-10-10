<?php
declare(strict_types=1);

use PDO;

require __DIR__ . '/includes/helpers.php';
ensure_session();

require __DIR__ . '/includes/db.php';

$perPage = 200;
$page = max(1, (int)($_GET['page'] ?? 1));

$filters = [
    'q' => sanitize_text($_GET['q'] ?? '', 120),
    'genre' => sanitize_text($_GET['genre'] ?? '', 120),
    'location_province' => sanitize_text($_GET['location_province'] ?? '', 120),
    'location_city' => sanitize_text($_GET['location_city'] ?? '', 120),
];

$where = [];
$params = [];

if ($filters['q'] !== '') {
    $where[] = 'name LIKE :q';
    $params[':q'] = '%' . $filters['q'] . '%';
}
if ($filters['genre'] !== '') {
    $where[] = 'genre LIKE :genre';
    $params[':genre'] = '%' . $filters['genre'] . '%';
}
if ($filters['location_province'] !== '') {
    $where[] = 'location_province LIKE :prov';
    $params[':prov'] = '%' . $filters['location_province'] . '%';
}
if ($filters['location_city'] !== '') {
    $where[] = 'location_city LIKE :city';
    $params[':city'] = '%' . $filters['location_city'] . '%';
}

$sqlBase = 'FROM djs' . ($where ? ' WHERE ' . implode(' AND ', $where) : '');

$stmtCount = $pdo->prepare('SELECT COUNT(*) ' . $sqlBase);
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

$perPage = max($perPage, $total);
$totalPages = 1;
$page = 1;
$offset = 0;

$stmt = $pdo->prepare(
    'SELECT id, slug, name, genre, location_city, location_province, image_path ' .
    $sqlBase .
    ' ORDER BY created_at DESC LIMIT :lim OFFSET :off'
);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$djs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Directorio de DJs en Argentina';

require __DIR__ . '/templates/header.php';
require __DIR__ . '/templates/form.php';
?>
<section class="results">
    <header class="results__header">
        <p><?= $total === 1 ? '1 DJ encontrado' : e((string)$total) . ' DJs encontrados' ?></p>
    </header>

    <?php if (empty($djs)): ?>
        <p class="empty-state">No encontramos DJs con esos filtros. Proba ajustando tu busqueda.</p>
    <?php else: ?>
        <div class="card-grid" data-initial-count="16" data-rows-step="5">
            <?php foreach ($djs as $dj): ?>
                <?php require __DIR__ . '/templates/card.php'; ?>
            <?php endforeach; ?>
        </div>
        <button type="button" class="load-more" data-action="load-more">Mostrar mas</button>
    <?php endif; ?>
</section>
<?php if (!empty($djs)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const grid = document.querySelector('.card-grid');
    const button = document.querySelector('[data-action="load-more"]');
    if (!grid || !button) {
        return;
    }

    const cards = Array.from(grid.querySelectorAll('.card'));
    if (!cards.length) {
        button.classList.add('is-hidden');
        return;
    }

    const initial = parseInt(grid.dataset.initialCount || '15', 10);
    const rowsStep = parseInt(grid.dataset.rowsStep || '5', 10);
    let visibleCount = Math.min(cards.length, initial);

    const applyVisibility = () => {
        cards.forEach((card, index) => {
            card.classList.toggle('card--hidden', index >= visibleCount);
        });
        if (visibleCount >= cards.length) {
            button.classList.add('is-hidden');
        } else {
            button.classList.remove('is-hidden');
        }
    };

    const getColumns = () => {
        const styles = window.getComputedStyle(grid);
        const columns = styles.getPropertyValue('grid-template-columns');
        if (!columns) {
            return 1;
        }
        const count = columns.trim().split(' ').filter(Boolean).length;
        return count || 1;
    };

    const revealMore = () => {
        const cols = getColumns();
        visibleCount = Math.min(cards.length, visibleCount + rowsStep * cols);
        applyVisibility();
    };

    applyVisibility();

    if (cards.length <= visibleCount) {
        button.classList.add('is-hidden');
    }

    button.addEventListener('click', revealMore);
    window.addEventListener('resize', applyVisibility);
});
</script>
<?php endif; ?>
<?php
require __DIR__ . '/templates/footer.php';

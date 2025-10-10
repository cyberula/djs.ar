<?php
declare(strict_types=1);
?>
<section class="search">
    <header class="search__header">
        <h2>Buscá y bookeá un DJ</h2>
    </header>
    <form method="get" action="/" class="filter-form">
        <input type="text" name="q" placeholder="Buscar por nombre" value="<?= e($filters['q'] ?? '') ?>">
        <input type="text" name="genre" placeholder="Géneros" value="<?= e($filters['genre'] ?? '') ?>">
        <?php
        $provinces = require __DIR__ . '/../includes/provinces.php';
        $selectedProvince = $filters['location_province'] ?? '';
        ?>
        <select name="location_province">
            <option value=""<?= $selectedProvince === '' ? ' selected' : '' ?>>Provincia</option>
            <?php foreach ($provinces as $prov): ?>
                <option value="<?= e($prov) ?>"<?= $selectedProvince === $prov ? ' selected' : '' ?>><?= e($prov) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="location_city" placeholder="Ciudad" value="<?= e($filters['location_city'] ?? '') ?>">
        <button type="submit">Buscar</button>
        <?php if (!empty(array_filter($filters, static fn($value) => $value !== ''))): ?>
            <a href="/">Limpiar</a>
        <?php endif; ?>
    </form>
</section>


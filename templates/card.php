<?php
declare(strict_types=1);

$genres = split_genres($dj['genre'] ?? '');
$slug = trim((string)($dj['slug'] ?? ''));
$slugUrl = $slug !== '' ? 'https://' . $slug . '.djs.ar/' : '#';
$imagePath = $dj['image_path'] ?? '';
$hasImage = $imagePath !== '';

$locationParts = array_filter([
    $dj['location_city'] ?? '',
    $dj['location_province'] ?? '',
]);
$location = implode(', ', $locationParts);
?>
<article class="card">
    <a class="card__link" href="<?= e($slugUrl) ?>">
        <div class="card__media">
            <?php if ($hasImage): ?>
                <img
                    class="card__image"
                    src="/<?= e(ltrim($imagePath, '/')) ?>"
                    alt="Foto de <?= e($dj['name'] ?? '') ?>"
                >
            <?php else: ?>
                <span class="card__initials">
                    <?= e(strtoupper(mb_substr($dj['name'] ?? '', 0, 2))) ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="card__info">
            <h2 class="card__title"><?= e($dj['name'] ?? '') ?></h2>
            <?php if (!empty($genres)): ?>
                <div class="card__genre"><?= e(implode(' / ', $genres)) ?></div>
            <?php endif; ?>
            <?php if ($location !== ''): ?>
                <div class="card__location"><?= e($location) ?></div>
            <?php endif; ?>
        </div>
        <div class="card__bottom">
            <span class="card__slug"><?= e(strtoupper($slug)) ?>.DJS.AR</span>
        </div>
    </a>
</article>

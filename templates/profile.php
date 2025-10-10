<?php
declare(strict_types=1);
$profileGenres = split_genres($dj['genre'] ?? '');
$hasProfileLinks = !empty($dj['sc_url'])
    || !empty($dj['yt_url'])
    || !empty($dj['ig_url'])
    || !empty($dj['press_kit_url'])
    || !empty($dj['contact_email']);
?>
<article class="profile">
    <header class="profile__header">
        <?php if (!empty($dj['image_path'])): ?>
            <img class="profile__photo" src="/<?= e($dj['image_path']) ?>" alt="Foto de <?= e($dj['name']) ?>">
        <?php endif; ?>
        <div class="profile__meta">
            <h1><?= e($dj['name']) ?></h1>
            <?php if (!empty($profileGenres)): ?>
                <div class="profile__genres">
                    <?php foreach ($profileGenres as $profileGenre): ?>
                        <span class="badge"><?= e($profileGenre) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($dj['location_city']) || !empty($dj['location_province'])): ?>
                <p class="location"><?= e(trim(($dj['location_city'] ?? '') . ', ' . ($dj['location_province'] ?? ''), ', ')) ?></p>
            <?php endif; ?>
            <p class="profile__slug"><a href="https://<?= e($dj['slug']) ?>.djs.ar/" target="_blank" rel="noopener noreferrer"><?= e(strtoupper($dj['slug'])) ?>.DJS.AR</a></p>
        </div>
    </header>
    <?php if ($hasProfileLinks): ?>
        <section class="profile__links">
            <?php if (!empty($dj['sc_url'])): ?>
                <a href="<?= e($dj['sc_url']) ?>" target="_blank" rel="noopener noreferrer">SoundCloud</a>
            <?php endif; ?>
            <?php if (!empty($dj['yt_url'])): ?>
                <a href="<?= e($dj['yt_url']) ?>" target="_blank" rel="noopener noreferrer">YouTube</a>
            <?php endif; ?>
            <?php if (!empty($dj['ig_url'])): ?>
                <a href="<?= e($dj['ig_url']) ?>" target="_blank" rel="noopener noreferrer">Instagram</a>
            <?php endif; ?>
            <?php if (!empty($dj['press_kit_url'])): ?>
                <a href="<?= e($dj['press_kit_url']) ?>" target="_blank" rel="noopener noreferrer">Press kit</a>
            <?php endif; ?>
            <?php if (!empty($dj['contact_email'])): ?>
                <a href="mailto:<?= e($dj['contact_email']) ?>">Contacto</a>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if (!empty($dj['bio'])): ?>
        <section class="profile__section">
            <h2>Bio</h2>
            <p><?= nl2br(e($dj['bio'])) ?></p>
        </section>
    <?php endif; ?>

    <?php if (!empty($dj['technical_rider'])): ?>
        <section class="profile__section">
            <h2>Rider tecnico</h2>
            <p><?= nl2br(e($dj['technical_rider'])) ?></p>
        </section>
    <?php endif; ?>

    <?php if (!empty($dj['sc_embed']) || !empty($dj['yt_embed'])): ?>
        <section class="profile__embeds">
            <?php if (!empty($dj['sc_embed'])): ?>
                <div class="card"><?= $dj['sc_embed'] ?></div>
            <?php endif; ?>
            <?php if (!empty($dj['yt_embed'])): ?>
                <div class="card"><?= $dj['yt_embed'] ?></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <p class="profile__edit-hint">
        ¿Este perfil es tuyo?
        <a href="https://djs.ar/request-edit-link.php?slug=<?= e($dj['slug']) ?>">
            Pedí un enlace para editarlo
        </a>
    </p>
</article>

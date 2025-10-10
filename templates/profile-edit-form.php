<?php
declare(strict_types=1);
?>
<section class="submit-section">
    <h1>Actualizar tu perfil</h1>
    <p>Estas editando el perfil <strong><?= e($record['slug']) ?>.djs.ar</strong>.</p>

    <?php if (!empty($successNotice)): ?>
        <div class="success-message">
            Guardamos los cambios. Verifica el perfil publico para asegurarte de que todo se vea bien.
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
        <div class="form-group">
            <label for="name">Nombre artistico *</label>
            <input type="text" id="name" name="name" maxlength="120" required value="<?= e($formData['name']) ?>">
        </div>

        <div class="form-group">
            <label for="genre">Generos principales *</label>
            <input type="text" id="genre" name="genre" maxlength="120" required value="<?= e($formData['genre']) ?>">
        </div>

        <div class="form-group">
            <label for="location_province">Provincia</label>
            <?php $provinces = require __DIR__ . '/../includes/provinces.php'; $selectedProvince = $formData['location_province'] ?? ''; ?>
            <select id="location_province" name="location_province">
                <option value=""<?= $selectedProvince === '' ? ' selected' : '' ?>>Provincia</option>
                <?php foreach ($provinces as $prov): ?>
                    <option value="<?= e($prov) ?>"<?= $selectedProvince === $prov ? ' selected' : '' ?>><?= e($prov) ?></option>
                <?php endforeach; ?>
                <?php if ($selectedProvince !== '' && !in_array($selectedProvince, $provinces, true)): ?>
                    <option value="<?= e($selectedProvince) ?>" selected><?= e($selectedProvince) ?> (actual)</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="location_city">Ciudad</label>
            <input type="text" id="location_city" name="location_city" maxlength="120" value="<?= e($formData['location_city']) ?>">
        </div>

        <div class="form-group">
            <label for="bio">Bio (max. 1200 caracteres)</label>
            <textarea id="bio" name="bio" maxlength="1200" rows="6"><?= e($formData['bio']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="technical_rider">Rider tecnico (max. 2000 caracteres)</label>
            <textarea id="technical_rider" name="technical_rider" maxlength="2000" rows="6"><?= e($formData['technical_rider']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="press_kit_url">Press kit (URL)</label>
            <input type="url" id="press_kit_url" name="press_kit_url" value="<?= e($formData['press_kit_url'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="contact_email">Email de contacto</label>
            <input type="email" id="contact_email" name="contact_email" value="<?= e($formData['contact_email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="sc_url">SoundCloud (link o embed)</label>
            <input type="url" id="sc_url" name="sc_url" value="<?= e($formData['sc_url'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="yt_url">YouTube (link o embed)</label>
            <input type="url" id="yt_url" name="yt_url" value="<?= e($formData['yt_url'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="ig_url">Instagram</label>
            <input type="url" id="ig_url" name="ig_url" value="<?= e((string)($formData['ig_url'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="image">Foto de perfil (JPG/PNG/WebP, max. 2 MB)</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
            <?php if (!empty($formData['image_path'])): ?>
                <div class="current-image">
                    <img src="/<?= e(ltrim((string)$formData['image_path'], '/')) ?>" alt="Imagen actual" style="max-height:80px;border-radius:8px;">
                    <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem;">
                        <input type="checkbox" name="remove_image" value="1">
                        Borrar imagen actual
                    </label>
                </div>
            <?php endif; ?>
            <?php if ($imageError): ?>
                <small><?= e($imageError) ?></small>
            <?php endif; ?>
        </div>

        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <button type="submit" class="button">Guardar cambios</button>
        <a class="button button--secondary" href="https://<?= e($record['slug']) ?>.djs.ar/" target="_blank" rel="noopener noreferrer">
            Ver perfil publico
        </a>
    </form>

    <hr style="margin:32px 0; border:0; border-top:1px solid rgba(255,255,255,0.12);">

    <form method="post" class="delete-profile-form" onsubmit="return confirm('Seguro que queres eliminar este perfil? Esta accion no se puede deshacer.');">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="delete_profile" value="1">
        <button type="submit" class="button" style="background:#b3261e; border-color:#b3261e;">Eliminar perfil</button>
        <p style="margin-top:8px; font-size:0.9rem;">Esta accion borra el perfil del directorio y no se puede deshacer.</p>
    </form>
</section>

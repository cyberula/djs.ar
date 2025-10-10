<?php
declare(strict_types=1);
?>
<section class="submit-section">
    <h1>Suma tu perfil al directorio</h1>
    <p>Completa los datos para aparecer en djs.ar. Revisá que la informacion sea correcta antes de enviar.</p>

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
            <label for="slug">Subdominio *</label>
            <div class="input-with-suffix">
                <input type="text" id="slug" name="slug" maxlength="63" required pattern="[A-Za-z0-9-]+" title="Usa letras, numeros o guiones." value="<?= e($formData['slug']) ?>">
                <span class="input-suffix">.djs.ar</span>
            </div>
            <small>Va a verse como https://<?= e($formData['slug'] !== '' ? $formData['slug'] : 'tu-subdominio') ?>.djs.ar. Solo letras, numeros y guiones.</small>
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
            <small>Inclui equipamiento requerido, conexiones y notas tecnicas.</small>
        </div>

        <div class="form-group">
            <label for="press_kit_url">Press kit (URL)</label>
            <input type="url" id="press_kit_url" name="press_kit_url" value="<?= e($formData['press_kit_url'] ?? '') ?>">
            <small>Enlace a press kit, EPK o carpeta de prensa.</small>
        </div>

        <div class="form-group">
            <label for="contact_email">Email de contacto</label>
            <input type="email" id="contact_email" name="contact_email" value="<?= e($formData['contact_email'] ?? '') ?>">
            <small>Mail para bookings o preguntas.</small>
        </div>

        <div class="form-group">
            <label for="sc_url">SoundCloud (link o embed)</label>
            <input type="url" id="sc_url" name="sc_url" value="<?= e($formData['sc_url'] ?? '') ?>">
            <small>Ejemplo: https://soundcloud.com/usuario/mezcla</small>
        </div>

        <div class="form-group">
            <label for="yt_url">YouTube (link o embed)</label>
            <input type="url" id="yt_url" name="yt_url" value="<?= e($formData['yt_url'] ?? '') ?>">
            <small>Ejemplo: https://youtu.be/tuVideoID</small>
        </div>

        <div class="form-group">
            <label for="ig_url">Instagram</label>
            <input type="url" id="ig_url" name="ig_url" value="<?= e($formData['ig_url'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="image">Foto de perfil (JPG/PNG/WebP, max. 2 MB)</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
            <?php if ($imageError): ?>
                <small><?= e($imageError) ?></small>
            <?php endif; ?>
        </div>

        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" hidden>


        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY !== 'YOUR_RECAPTCHA_SITE_KEY'): ?>
            <div class="form-group">
                <div class="g-recaptcha" data-sitekey="<?= e(RECAPTCHA_SITE_KEY) ?>"></div>
            </div>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <?php endif; ?>

        <button type="submit" class="button">Publicar perfil</button>
    </form>
</section>

<?php
declare(strict_types=1);
?>
<section class="submit-section">
    <h1>Editar tu perfil</h1>
    <p>Pedinos un enlace especial para actualizar tu perfil. El enlace llega al email de contacto registrado.</p>

    <?php if (!empty($successNotice)): ?>
        <div class="success-message">
            Te enviamos un enlace si el email coincide con el perfil. Revisa tu casilla (y el spam).
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" class="request-edit-form" novalidate>
        <div class="form-group">
            <label for="slug">Subdominio del perfil *</label>
            <div class="input-with-suffix">
                <input
                    type="text"
                    id="slug"
                    name="slug"
                    maxlength="63"
                    required
                    pattern="[A-Za-z0-9-]+"
                    value="<?= e($formData['slug']) ?>">
                <span class="input-suffix">.djs.ar</span>
            </div>
            <small>Ejemplo: si tu perfil es https://mi-dj.djs.ar, ingresa <strong>mi-dj</strong>.</small>
        </div>

        <div class="form-group">
            <label for="contact_email">Email registrado *</label>
            <input
                type="email"
                id="contact_email"
                name="contact_email"
                maxlength="255"
                required
                value="<?= e($formData['contact_email']) ?>">
            <small>Solo enviamos el enlace si coincide con el email guardado en el perfil.</small>
        </div>

        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" hidden>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <button type="submit" class="button">Enviar enlace</button>
    </form>
</section>

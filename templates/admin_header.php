<?php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Panel admin djs.ar') ?></title>
    <style>
        :root {
            color-scheme: dark;
            --admin-bg: #090611;
            --admin-panel: #151021;
            --admin-border: rgba(255, 255, 255, 0.08);
            --admin-text: #f5f5ff;
            --admin-muted: #9fa0b8;
            --admin-accent: #ff2975;
            --admin-radius: 12px;
            --admin-shadow: 0 20px 44px rgba(6, 3, 12, 0.55);
            font-family: "Archivo", system-ui, sans-serif;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: radial-gradient(circle at top, rgba(64, 22, 82, 0.28), transparent 55%), var(--admin-bg);
            color: var(--admin-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        a {
            color: var(--admin-accent);
            text-decoration: none;
        }
        a:hover { opacity: 0.85; }
        header.admin-topbar {
            background: rgba(12, 8, 20, 0.92);
            border-bottom: 1px solid var(--admin-border);
            box-shadow: var(--admin-shadow);
        }
        .admin-topbar__inner {
            max-width: 1080px;
            margin: 0 auto;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }
        .admin-topbar__brand {
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--admin-text);
        }
        .admin-topbar__nav {
            display: flex;
            align-items: center;
            gap: 18px;
            font-size: 0.92rem;
            letter-spacing: 0.08em;
        }
        .admin-main {
            flex: 1;
            max-width: 1080px;
            width: 100%;
            margin: 0 auto;
            padding: 32px 24px 56px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .admin-card {
            background: var(--admin-panel);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius);
            padding: 24px;
            box-shadow: var(--admin-shadow);
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        .admin-table th,
        .admin-table td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 0.92rem;
        }
        .admin-table th {
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--admin-muted);
            font-size: 0.78rem;
        }
        .admin-table tbody tr:hover {
            background: rgba(255, 41, 117, 0.08);
        }
        .admin-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .admin-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid var(--admin-border);
            background: rgba(255, 41, 117, 0.14);
            color: var(--admin-text);
            text-transform: uppercase;
            letter-spacing: 0.14em;
            font-size: 0.72rem;
            font-weight: 700;
            transition: transform 0.15s ease, background 0.2s ease;
        }
        .admin-button:hover {
            transform: translateY(-1px);
            background: rgba(255, 41, 117, 0.2);
        }
        .admin-button--danger {
            border-color: rgba(255, 80, 80, 0.4);
            background: rgba(255, 80, 80, 0.18);
        }
        .admin-button--danger:hover {
            background: rgba(255, 80, 80, 0.28);
        }
        form.admin-form {
            display: grid;
            gap: 18px;
        }
        .admin-form__grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }
        .admin-field label {
            display: block;
            font-size: 0.82rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 8px;
            color: var(--admin-muted);
        }
        .admin-field input[type="text"],
        .admin-field input[type="email"],
        .admin-field input[type="url"],
        .admin-field textarea,
        .admin-field input[type="password"] {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(8, 6, 16, 0.9);
            color: var(--admin-text);
            font-size: 0.95rem;
        }
        .admin-field textarea {
            min-height: 140px;
            resize: vertical;
        }
        .admin-alert {
            padding: 14px 16px;
            border-radius: 10px;
            background: rgba(255, 80, 80, 0.14);
            border: 1px solid rgba(255, 80, 80, 0.35);
            color: #ffc7d1;
        }
        .admin-alert--success {
            background: rgba(76, 201, 240, 0.18);
            border-color: rgba(76, 201, 240, 0.4);
            color: #d5f5ff;
        }
        .admin-login {
            max-width: 420px;
            margin: 96px auto 0;
            width: 100%;
        }
        .admin-login h1 {
            margin-bottom: 18px;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            font-size: 1.05rem;
            text-align: center;
        }
        .admin-empty {
            text-align: center;
            padding: 36px;
            color: var(--admin-muted);
            letter-spacing: 0.08em;
        }
        footer.admin-footer {
            padding: 18px;
            text-align: center;
            font-size: 0.75rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--admin-muted);
        }
        @media (max-width: 640px) {
            .admin-topbar__inner {
                flex-direction: column;
                align-items: flex-start;
            }
            .admin-topbar__nav {
                width: 100%;
                justify-content: space-between;
            }
            .admin-actions {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
<header class="admin-topbar">
    <div class="admin-topbar__inner">
        <span class="admin-topbar__brand">djs.ar · admin</span>
        <nav class="admin-topbar__nav">
            <a href="/admin/index.php">Perfiles</a>
            <?php if (!empty($adminUser)): ?>
                <span><?= e($adminUser['email'] ?? '') ?></span>
                <a href="/admin/logout.php">Salir</a>
            <?php else: ?>
                <a href="/admin/login.php">Ingresar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="admin-main">

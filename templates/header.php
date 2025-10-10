<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?= e($pageTitle ?? 'djs.ar') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/png" href="/favicon.png">
    <style>
        :root {
            color-scheme: dark;
            --bg: #050308;
            --bg-alt: #0d0914;
            --bg-panel: rgba(18, 14, 24, 0.9);
            --accent: #ff2975;
            --accent-soft: rgba(255, 41, 117, 0.45);
            --text: #f6f6f9;
            --text-muted: #9c9cad;
            --outline: rgba(255, 41, 117, 0.35);
            --outline-soft: rgba(255, 41, 117, 0.18);
            --shadow: rgba(8, 4, 12, 0.7);
            --radius: 20px;
            --font-body: "Archivo", system-ui, sans-serif;
            --font-mono: "IBM Plex Mono", monospace;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background:
                linear-gradient(160deg, rgba(36, 12, 46, 0.55) 0%, rgba(8, 6, 12, 0.95) 60%, rgba(6, 4, 8, 1) 100%);
            color: var(--text);
            font-family: var(--font-body);
            line-height: 1.8;
            letter-spacing: 0.02em;
        }
        a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s ease, color 0.2s ease;
        }
        a:hover { opacity: 0.85; }
        .topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            backdrop-filter: blur(20px);
            background: linear-gradient(135deg, rgba(16, 9, 24, 0.95), rgba(8, 5, 12, 0.9));
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 18px 32px rgba(4, 3, 8, 0.6);
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }
        .topbar__inner {
            max-width: 1120px;
            margin: 0 auto;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        .topbar .main-nav {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        .topbar__logo {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: var(--text);
        }
        .topbar__logo img {
            height: 46px;
            width: auto;
            display: block;
        }
        .topbar__logo span {
            font-size: 0.72rem;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.65);
        }
        .main-nav__links {
            list-style: none;
            display: flex;
            gap: 32px;
            margin: 0;
            padding: 0;
            text-transform: uppercase;
            letter-spacing: 0.26em;
            font-size: 1rem;
        }
        .main-nav__links a {
            color: #E4327B;
            position: relative;
            padding-bottom: 2px;
            transition: color 0.2s ease;
        }
        .main-nav__links a::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 1px;
            background: transparent;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.2s ease;
        }
        .main-nav__links a:hover,
        .main-nav__links a:focus {
            color: var(--text);
        }
        .main-nav__links a:hover::after,
        .main-nav__links a:focus::after {
            transform: scaleX(1);
        }
        

        .main-nav__inner {
            max-width: 960px;
            margin: 0 auto;
            padding: 16px 24px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        main.main {
            max-width: 1120px;
            margin: 0 auto;
            padding: 72px 24px 72px;
        }
        section.search {
            margin-bottom: 34px;
            padding: 28px;
            border-radius: var(--radius);
            border: 1px solid var(--outline-soft);
            background: var(--bg-panel);
            box-shadow: 0 24px 48px var(--shadow);
        }
        .search__header {
            text-align: center;
            margin-bottom: 16px;
        }
        .search__header h2 {
            margin: 0;
            font-size: 1.32rem;
            text-transform: uppercase;
            letter-spacing: 0.28em;
            color: var(--text);
        }
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 18px;
        }
        .filter-form input,
        .filter-form select,
        .filter-form button {
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(10, 9, 14, 0.9);
            color: var(--text);
            font-size: 0.9rem;
            letter-spacing: 0.04em;
        }
        .filter-form input::placeholder {
            color: rgba(255, 255, 255, 0.42);
            text-transform: uppercase;
            letter-spacing: 0.18em;
        }
        .filter-form button {
            border: none;
            background: #E4327B;
            text-transform: uppercase;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 18px 34px rgba(255, 41, 117, 0.22);
        }
        .results__header {
            margin: 0 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 12px;
        }
        .results__header p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.84rem;
            letter-spacing: 0.12em;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
        }
        .card {
            border-radius: var(--radius);
            height: 100%;
        }
        .card__link {
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-height: 340px;
            height: 100%;
            padding: 24px;
            border-radius: var(--radius);
            position: relative;
            text-decoration: none;
            color: inherit;
            background: linear-gradient(160deg, rgba(16, 11, 22, 0.96), rgba(7, 5, 12, 0.92));
            border: 1px solid var(--outline-soft);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.02), 0 20px 36px rgba(4, 3, 9, 0.74);
            transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
        }
        .card__link:hover,
        .card__link:focus {
            transform: translateY(-6px);
            border-color: var(--outline);
            box-shadow: 0 26px 48px rgba(255, 41, 117, 0.22);
        }
        .card__link:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 4px;
        }
        .card__link::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: var(--radius);
            border: 1px solid transparent;
            pointer-events: none;
            transition: border-color 0.18s ease;
        }
        .card__link:hover::after,
        .card__link:focus::after {
            border-color: rgba(255, 41, 117, 0.35);
        }
        .card__media {
            width: 100%;
            aspect-ratio: 4 / 3;
            border-radius: calc(var(--radius) - 6px);
            border: 2px solid var(--outline);
            background: radial-gradient(circle, rgba(255, 61, 132, 0.16), rgba(10, 8, 14, 0.82));
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card__image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .card__initials {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.2em;
            color: var(--text);
        }
        .card__info {
            display: flex;
            flex-direction: column;
            gap: 8px;
            text-align: left;
        }
        .card__title {
            margin: 0;
            font-size: 1.26rem;
            font-weight: 700;
            letter-spacing: 0.12em;
        }
        .card__genre {
            font-size: 0.78rem;
            color: var(--text-muted);
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }
        .card__location {
            font-size: 0.86rem;
            color: rgba(255, 255, 255, 0.65);
            letter-spacing: 0.08em;
        }
        .card__bottom {
            margin-top: auto;
        }
        .card__slug {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.24em;
            color: rgba(255, 61, 132, 0.84);
        }
        .card--hidden { display: none !important; }
        .load-more {
            margin: 32px auto 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 28px;
            border-radius: 999px;
            border: 1px solid rgba(255, 41, 117, 0.32);
            background: rgba(12, 8, 16, 0.85);
            color: var(--text);
            font-size: 0.74rem;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            cursor: pointer;
            transition: transform 0.18s ease, border-color 0.18s ease;
        }
        .load-more:hover,
        .load-more:focus {
            transform: translateY(-3px);
            border-color: var(--outline);
        }
        .load-more:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 4px;
        }
        .load-more.is-hidden { display: none; }
        .submit-section {
            max-width: 760px;
            margin: 0 auto;
            background: linear-gradient(160deg, rgba(14, 10, 18, 0.92), rgba(7, 5, 10, 0.92));
            border-radius: calc(var(--radius) + 6px);
            border: 1px solid var(--outline-soft);
            box-shadow: 0 32px 64px rgba(4, 2, 8, 0.6);
            padding: 34px 36px;
        }
        .submit-section h1 {
            margin-top: 0;
            text-transform: uppercase;
            letter-spacing: 0.18em;
        }
        .submit-section p {
            color: var(--text-muted);
            letter-spacing: 0.06em;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 18px;
        }
        .form-group label {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-size: 0.72rem;
            color: rgba(255, 255, 255, 0.78);
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(8, 6, 12, 0.86);
            color: var(--text);
            font: inherit;
            resize: vertical;
        }

        /* Make select visually consistent with inputs and add a custom arrow */
        .form-group select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            /* leave some space for the custom arrow */
            padding-right: 44px;
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 12px 8px;
            /* custom arrow (white at 70% opacity to match muted text) */
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'><path fill='%23ffffff' fill-opacity='0.7' d='M6 8L0 0h12z'/></svg>");
        }

        /* Ensure selects in compact filter form also inherit the arrow and spacing */
        .filter-form select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            padding-right: 44px;
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 12px 8px;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'><path fill='%23ffffff' fill-opacity='0.7' d='M6 8L0 0h12z'/></svg>");
        }

        /* Focus state similar to inputs */
        .form-group select:focus,
        .filter-form select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 1px var(--accent-soft);
        }
        .input-with-suffix {
            display: flex;
            align-items: stretch;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            background: rgba(8, 6, 12, 0.86);
        }
        .input-with-suffix input {
            flex: 1;
            min-width: 0;
            padding: 14px 16px;
            background: transparent;
            border: none;
            border-radius: 14px 0 0 14px;
            color: inherit;
            font: inherit;
        }
        .input-with-suffix input:focus {
            outline: none;
        }
        .input-with-suffix .input-suffix {
            display: inline-flex;
            align-items: center;
            padding: 14px 18px;
            border-left: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 0 14px 14px 0;
            font-weight: 600;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            background: rgba(12, 8, 18, 0.92);
        }
        .input-with-suffix:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 1px var(--accent-soft);
        }
        .form-group small {
            color: rgba(255, 255, 255, 0.52);
            font-size: 0.75rem;
            letter-spacing: 0.08em;
        }
        .error-list {
            margin: 0 0 24px;
            padding: 18px 22px;
            border-radius: 16px;
            background: rgba(255, 41, 117, 0.16);
            border: 1px solid rgba(255, 41, 117, 0.4);
            list-style: none;
            display: grid;
            gap: 6px;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.12em;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 999px;
            padding: 14px 26px;
            border: none;linear-gradient(135deg, #E4327B)( #E4327B)
            background: );
            color: var(--text);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            cursor: pointer;
            box-shadow: 0 20px 40px rgba(255, 41, 117, 0.25);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .button:hover {
            transform: translateY(-4px);
            box-shadow: 0 28px 56px rgba(255, 41, 117, 0.32);
        }
        .profile {
            display: grid;
            gap: 26px;
        }
        .profile__header {
            display: flex;
            flex-wrap: wrap;
            gap: 28px;
            padding: 28px;
            border-radius: var(--radius);
            border: 1px solid var(--outline-soft);
            background: linear-gradient(160deg, rgba(12, 9, 16, 0.92), rgba(7, 6, 12, 0.88));
        }
        .profile__photo {
            width: 220px;
            height: 220px;
            border-radius: 30px;
            object-fit: cover;
            border: 2px solid var(--outline);
            box-shadow: 0 16px 32px rgba(2, 1, 5, 0.65);
        }
        .profile__meta h1 {
            margin: 0;
            font-size: 2.2rem;
            text-transform: uppercase;
            letter-spacing: 0.22em;
        }
        .profile__slug {
            margin: 10px 0 0;
            text-transform: uppercase;
            letter-spacing: 0.26em;
            font-size: 0.78rem;
        }
        .profile__slug a {
            color: rgba(255, 61, 132, 0.82);
        }
        .profile__genres {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .profile__section {
            padding: 24px 28px;
            background: var(--bg-panel);
            border-radius: var(--radius);
            border: 1px solid var(--outline-soft);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.02);
        }
        .profile__section h2 {
            margin-top: 0;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            font-size: 0.95rem;
        }
        .profile__section p {
            margin-bottom: 0;
            color: var(--text-muted);
            letter-spacing: 0.04em;
            white-space: pre-wrap;
        }
        .profile__embeds {
            display: grid;
            gap: 22px;
        }
        .profile__embeds .card {
            padding: 0;
            border-radius: var(--radius);
            overflow: hidden;
            border: none;
            background: transparent;
            box-shadow: none;
        }
        .profile__embeds iframe {
            display: block;
            width: 100%;
            border: none;
            min-height: 340px;`n            height: 100%;
        }
        .profile__links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 18px;
            margin-top: 6px;
        }
        .profile__links a {
            padding: 14px 28px;
            border-radius: 999px;
            border: 1px solid rgba(255, 61, 132, 0.35);
            background: rgba(18, 12, 24, 0.9);
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.22em;
            font-weight: 700;
            text-align: center;
            box-shadow: 0 16px 28px rgba(255, 41, 117, 0.18);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .profile__links a:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 36px rgba(255, 41, 117, 0.28);
        }
        footer.site-footer {
            margin-top: 96px;
            padding: 48px 24px;
            text-align: center;
            color: var(--text-muted);
            border-top: 1px solid var(--outline-soft);
            background: rgba(6, 4, 8, 0.85);
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-size: 0.74rem;
        }
        footer.site-footer a { color: var(--accent); }
        @media (max-width: 1023px) {
            .card-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 639px) {
            .card-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 720px) {
            .topbar__inner {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .intro-blurb__inner {
                padding: 24px 20px;
            }
            .main-nav__inner {
                max-width: 960px;
                margin: 0 auto;
                padding: 16px 24px;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .results__header {
                flex-direction: column;
                align-items: flex-start;
            }
            .profile__header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .profile__links {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<header class="topbar">
    <div class="topbar__inner">
        <a class="topbar__logo" href="https://djs.ar/">
            <img src="/logo.png" alt="djs.ar logo">
            <span>Directorio nacional de artistas </span>
        </a>
    </div>
    <?php if (empty($hideMainNav)): ?>
    <nav class="main-nav">
        <div class="main-nav__inner">
            <ul class="main-nav__links">
                <li><a href="/submit.php">CREÁ tu-press-kit.DJs.ar ACÁ</a></li>
            </ul>
        </div>
    </nav>
    <?php endif; ?>
</header>
<main class="main">





<?php
/**
 * Las Rutas del Vino - Landing page para bodegas
 * Página B2B: dirigida a bodegueros para inscribir su bodega
 * Precio: 10€ IVA incluido - pago directo a Stripe
 */

$page_title = "Las Rutas del Vino — Inscribe tu Bodega | Rutas Rurales";
$page_description = "¿Tienes una bodega y quieres que los enoturistas te encuentren? Únete a Las Rutas del Vino. Solo 10€ IVA incluido. Inscripción rápida en menos de 5 minutos.";
$page_canonical = "https://rutasrurales.io/rutas-del-vino/";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-MBP57VQM');</script>
    <!-- End Google Tag Manager -->

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $page_description; ?>">
    <title><?php echo $page_title; ?></title>
    <link rel="canonical" href="<?php echo $page_canonical; ?>">
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Playfair Display + Montserrat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ============================================
           VARIABLES & RESET
        ============================================ */
        :root {
            --wine:       #722F37;
            --wine-dark:  #4A1820;
            --wine-light: #9B4D57;
            --gold:       #C9A84C;
            --gold-light: #E8C97A;
            --cream:      #F5F0E8;
            --cream-dark: #EDE5D4;
            --ivory:      #FDFAF5;
            --green-vine: #2D5016;
            --text-dark:  #1A1A1A;
            --text-mid:   #4A4A4A;
            --text-light: #7A7A7A;
            --shadow-sm:  0 2px 8px rgba(114,47,55,0.12);
            --shadow-md:  0 8px 32px rgba(114,47,55,0.18);
            --shadow-lg:  0 20px 60px rgba(114,47,55,0.25);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { scroll-behavior: smooth; overflow-x: hidden; }

        body {
            font-family: 'Montserrat', sans-serif;
            color: var(--text-dark);
            background: var(--ivory);
            overflow-x: hidden;
        }

        /* ============================================
           NAVBAR
        ============================================ */
        .vino-nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 9999;
            background: rgba(26, 10, 12, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(201,168,76,0.2);
            padding: 0.75rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            transition: all 0.3s;
        }
        .vino-nav.scrolled {
            background: rgba(26, 10, 12, 0.98);
            padding: 0.5rem 2rem;
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            text-decoration: none;
        }
        .nav-brand img { height: 36px; width: auto; }
        .nav-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }
        .nav-brand-title {
            font-family: 'Playfair Display', serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--gold-light);
            letter-spacing: 0.02em;
        }
        .nav-brand-sub {
            font-size: 0.6rem;
            font-weight: 600;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 0.15em;
        }
        .nav-cta {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .nav-link {
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            transition: color 0.2s;
        }
        .nav-link:hover { color: var(--gold-light); }
        .btn-nav-inscribir {
            background: var(--gold);
            color: var(--wine-dark) !important;
            padding: 0.5rem 1.2rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            white-space: nowrap;
        }
        .btn-nav-inscribir:hover {
            background: var(--gold-light);
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(201,168,76,0.4);
        }

        /* ============================================
           HERO SECTION
        ============================================ */
        .hero {
            min-height: 100vh;
            background:
                linear-gradient(135deg,
                    rgba(74,24,32,0.88) 0%,
                    rgba(114,47,55,0.75) 40%,
                    rgba(45,80,22,0.65) 100%),
                url('https://images.unsplash.com/photo-1506377247377-2a5b3b417ebb?w=1600&q=80') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 8rem 2rem 4rem;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 120px;
            background: linear-gradient(to top, var(--ivory), transparent);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(201,168,76,0.2);
            border: 1px solid rgba(201,168,76,0.5);
            color: var(--gold-light);
            padding: 0.4rem 1.2rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
            animation: fadeInDown 0.6s ease;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.8rem, 6vw, 5.5rem);
            font-weight: 900;
            color: #fff;
            line-height: 1.1;
            margin-bottom: 0.5rem;
            animation: fadeInUp 0.7s ease;
        }
        .hero-title em {
            font-style: italic;
            color: var(--gold-light);
        }

        .hero-subtitle {
            font-size: clamp(1.1rem, 2.5vw, 1.5rem);
            color: rgba(255,255,255,0.85);
            font-weight: 400;
            margin-bottom: 2.5rem;
            max-width: 600px;
            animation: fadeInUp 0.8s ease;
        }

        .hero-hook-box {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(8px);
            border: 2px solid rgba(201,168,76,0.6);
            border-radius: 20px;
            padding: 2rem 3rem;
            max-width: 700px;
            margin: 0 auto 3rem;
            animation: fadeInUp 0.9s ease;
        }
        .hero-hook-eyebrow {
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--gold-light);
            margin-bottom: 0.8rem;
        }
        .hero-hook-headline {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.8rem, 4vw, 3rem);
            font-weight: 900;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 0.8rem;
        }
        .hero-hook-headline .missing {
            color: var(--gold-light);
            text-decoration: underline;
            text-decoration-color: rgba(201,168,76,0.5);
            text-underline-offset: 6px;
        }
        .hero-hook-desc {
            font-size: 0.95rem;
            color: rgba(255,255,255,0.8);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        .hero-price-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--gold);
            color: var(--wine-dark);
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        .hero-price-pill .price-strike {
            font-size: 0.85rem;
            opacity: 0.7;
            text-decoration: line-through;
            font-weight: 600;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-primary-wine {
            background: var(--gold);
            color: var(--wine-dark);
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(201,168,76,0.4);
        }
        .btn-primary-wine:hover {
            background: var(--gold-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(201,168,76,0.5);
        }
        .btn-secondary-wine {
            background: transparent;
            color: #fff;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            border: 2px solid rgba(255,255,255,0.4);
            cursor: pointer;
        }
        .btn-secondary-wine:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.7);
        }

        .hero-scroll-hint {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(255,255,255,0.5);
            font-size: 0.75rem;
            text-align: center;
            animation: bounce 2s infinite;
        }
        .hero-scroll-hint i { display: block; font-size: 1.2rem; margin-top: 0.3rem; }

        /* ============================================
           STATS BAR
        ============================================ */
        .stats-bar {
            background: var(--wine-dark);
            padding: 2rem;
            display: flex;
            justify-content: center;
            gap: 0;
            flex-wrap: wrap;
        }
        .stat-item {
            text-align: center;
            padding: 1rem 2.5rem;
            border-right: 1px solid rgba(201,168,76,0.2);
            flex: 1;
            min-width: 140px;
        }
        .stat-item:last-child { border-right: none; }
        .stat-number {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--gold-light);
            line-height: 1;
        }
        .stat-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: rgba(255,255,255,0.55);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: 0.3rem;
        }

        /* ============================================
           MAP SECTION
        ============================================ */
        .map-section {
            padding: 5rem 0 0;
            background: var(--cream);
        }
        .section-header {
            text-align: center;
            padding: 0 2rem 3rem;
            max-width: 800px;
            margin: 0 auto;
        }
        .section-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--wine);
            margin-bottom: 1rem;
        }
        .section-eyebrow::before,
        .section-eyebrow::after {
            content: '';
            display: block;
            width: 30px;
            height: 1px;
            background: var(--wine);
        }
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 700;
            color: var(--wine-dark);
            line-height: 1.2;
            margin-bottom: 1rem;
        }
        .section-desc {
            font-size: 1rem;
            color: var(--text-mid);
            line-height: 1.7;
        }

        .map-wrapper {
            display: grid;
            grid-template-columns: 1fr 380px;
            min-height: 600px;
            position: relative;
        }

        #vino-map {
            height: 100%;
            min-height: 600px;
            width: 100%;
        }

        .map-sidebar {
            background: var(--wine-dark);
            padding: 2rem 1.5rem;
            overflow-y: auto;
            max-height: 600px;
        }
        .map-sidebar-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: var(--gold-light);
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid rgba(201,168,76,0.2);
        }

        .bodega-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(201,168,76,0.15);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .bodega-card:hover {
            background: rgba(201,168,76,0.1);
            border-color: rgba(201,168,76,0.4);
            transform: translateX(3px);
        }
        .bodega-card-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.3rem;
        }
        .bodega-card-meta {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.5);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .bodega-card-meta i { color: var(--gold); }
        .bodega-card-tipo {
            display: inline-block;
            background: rgba(201,168,76,0.2);
            color: var(--gold-light);
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
            margin-top: 0.4rem;
        }

        .map-cta-banner {
            background: linear-gradient(135deg, var(--wine), var(--wine-dark));
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            margin-top: 1rem;
        }
        .map-cta-banner p {
            color: rgba(255,255,255,0.8);
            font-size: 0.8rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        .map-cta-banner strong { color: var(--gold-light); }
        .btn-map-cta {
            display: block;
            background: var(--gold);
            color: var(--wine-dark);
            padding: 0.7rem 1rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.2s;
            text-align: center;
        }
        .btn-map-cta:hover { background: var(--gold-light); }

        /* ============================================
           VALUE PROPOSITION
        ============================================ */
        .value-section {
            padding: 5rem 2rem;
            background: var(--ivory);
        }
        .value-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            max-width: 1100px;
            margin: 0 auto;
        }
        .value-card {
            background: #fff;
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid var(--cream-dark);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .value-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--wine), var(--gold));
        }
        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--cream-dark);
        }
        .value-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--wine), var(--wine-light));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--gold-light);
            margin-bottom: 1.2rem;
        }
        .value-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: var(--wine-dark);
            margin-bottom: 0.7rem;
        }
        .value-card p {
            font-size: 0.9rem;
            color: var(--text-mid);
            line-height: 1.6;
        }

        /* ============================================
           CTA PRINCIPAL — FORMULARIO DIRECTO
        ============================================ */
        .cta-main {
            padding: 5rem 2rem;
            background: linear-gradient(160deg, #3a0f14 0%, #722F37 50%, #2D5016 100%);
            text-align: center;
        }
        .cta-container {
            max-width: 780px;
            margin: 0 auto;
        }
        .cta-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(201,168,76,0.25);
            border: 1px solid rgba(201,168,76,0.5);
            color: #E8C97A;
            padding: 0.4rem 1.2rem;
            border-radius: 30px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
        }
        .cta-headline {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.2rem, 5vw, 4rem);
            font-weight: 900;
            color: #fff;
            line-height: 1.1;
            margin-bottom: 1rem;
        }
        .cta-headline .accent { color: #E8C97A; }

        /* Precio enorme e inconfundible */
        .price-display {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            margin: 1.5rem 0 2rem;
            flex-wrap: wrap;
        }
        .price-big {
            font-family: 'Playfair Display', serif;
            font-size: clamp(5rem, 14vw, 9rem);
            font-weight: 900;
            color: #E8C97A;
            line-height: 1;
            text-shadow: 0 4px 20px rgba(201,168,76,0.4);
        }
        .price-big .euro-sym {
            font-size: 0.5em;
            vertical-align: top;
            margin-top: 0.3em;
        }
        .price-details {
            text-align: left;
        }
        .price-iva-tag {
            background: #27ae60;
            color: #fff;
            padding: 0.25rem 0.7rem;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 800;
            display: inline-block;
            margin-bottom: 0.4rem;
        }
        .price-desc-line {
            color: rgba(255,255,255,0.7);
            font-size: 0.82rem;
            font-weight: 600;
            display: block;
        }
        .price-strike {
            color: rgba(255,255,255,0.35);
            font-size: 0.78rem;
            text-decoration: line-through;
        }

        /* Formulario directo en la página */
        .inscripcion-form-wrapper {
            background: rgba(255,255,255,0.06);
            border: 2px solid rgba(201,168,76,0.3);
            border-radius: 24px;
            padding: 2.5rem 2rem 2rem;
            margin: 0 auto;
            text-align: left;
        }
        .inscripcion-form-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            color: #E8C97A;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            color: rgba(255,255,255,0.6);
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            color: #fff;
            background: rgba(255,255,255,0.1);
            transition: border-color 0.2s, background 0.2s;
        }
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255,255,255,0.35);
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #C9A84C;
            background: rgba(255,255,255,0.15);
        }
        .form-group input[type="email"]:valid { border-color: rgba(39,174,96,0.5); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        .btn-pagar-stripe {
            width: 100%;
            padding: 1.1rem 2rem;
            background: #C9A84C;
            color: #4A1820;
            border: none;
            border-radius: 14px;
            font-family: 'Montserrat', sans-serif;
            font-size: 1.1rem;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            margin-top: 1.5rem;
            letter-spacing: 0.02em;
            box-shadow: 0 6px 25px rgba(201,168,76,0.4);
        }
        .btn-pagar-stripe:hover {
            background: #E8C97A;
            transform: translateY(-3px);
            box-shadow: 0 10px 35px rgba(201,168,76,0.55);
        }
        .btn-pagar-stripe:disabled {
            opacity: 0.65;
            cursor: not-allowed;
            transform: none;
        }
        .btn-pagar-stripe .price-in-btn {
            background: rgba(74,24,32,0.25);
            padding: 0.2rem 0.7rem;
            border-radius: 20px;
            font-size: 1rem;
        }

        .cta-trust {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        .trust-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: rgba(255,255,255,0.5);
            font-size: 0.72rem;
            font-weight: 600;
        }
        .trust-item i { color: #C9A84C; font-size: 0.9rem; }

        .cta-alternative {
            margin-top: 2rem;
            text-align: center;
        }
        .cta-alt-text {
            color: rgba(255,255,255,0.5);
            font-size: 0.82rem;
            margin-bottom: 0.8rem;
        }
        .cta-alt-links {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-alt {
            padding: 0.6rem 1.3rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .btn-alt-whatsapp {
            background: rgba(37,211,102,0.15);
            color: #25d366;
            border: 1px solid rgba(37,211,102,0.4);
        }
        .btn-alt-whatsapp:hover { background: rgba(37,211,102,0.25); }
        .btn-alt-email {
            background: rgba(201,168,76,0.15);
            color: #E8C97A;
            border: 1px solid rgba(201,168,76,0.3);
        }
        .btn-alt-email:hover { background: rgba(201,168,76,0.25); }

        /* ============================================
           TESTIMONIOS / SOCIAL PROOF
        ============================================ */
        .social-section {
            padding: 5rem 2rem;
            background: var(--cream);
        }
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            max-width: 1000px;
            margin: 0 auto;
        }
        .testimonial-card {
            background: #fff;
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid var(--cream-dark);
            position: relative;
        }
        .testimonial-card::before {
            content: '"';
            position: absolute;
            top: 0.5rem;
            right: 1.5rem;
            font-family: 'Playfair Display', serif;
            font-size: 5rem;
            color: var(--cream-dark);
            line-height: 1;
        }
        .testimonial-stars {
            color: var(--gold);
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
        }
        .testimonial-text {
            font-size: 0.9rem;
            color: var(--text-mid);
            line-height: 1.6;
            margin-bottom: 1.2rem;
            font-style: italic;
        }
        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .testimonial-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--wine), var(--gold));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            font-weight: 700;
            flex-shrink: 0;
        }
        .testimonial-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--wine-dark);
        }
        .testimonial-role {
            font-size: 0.72rem;
            color: var(--text-light);
        }

        /* ============================================
           FAQ
        ============================================ */
        .faq-section {
            padding: 5rem 2rem;
            background: var(--ivory);
        }
        .faq-list {
            max-width: 750px;
            margin: 0 auto;
        }
        .faq-item {
            border-bottom: 1px solid var(--cream-dark);
            overflow: hidden;
        }
        .faq-question {
            width: 100%;
            background: none;
            border: none;
            padding: 1.2rem 0;
            text-align: left;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--wine-dark);
            gap: 1rem;
        }
        .faq-question i {
            color: var(--wine);
            font-size: 0.8rem;
            transition: transform 0.3s;
            flex-shrink: 0;
        }
        .faq-question.open i { transform: rotate(180deg); }
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        .faq-answer.open { max-height: 300px; }
        .faq-answer-inner {
            padding: 0 0 1.2rem;
            font-size: 0.88rem;
            color: var(--text-mid);
            line-height: 1.7;
        }

        /* ============================================
           FOOTER
        ============================================ */
        .vino-footer {
            background: var(--wine-dark);
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .footer-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }
        .footer-logo img { height: 32px; filter: brightness(0) invert(1) opacity(0.7); }
        .footer-logo span {
            font-family: 'Playfair Display', serif;
            font-size: 1rem;
            color: rgba(255,255,255,0.6);
        }
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .footer-links a {
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            font-size: 0.78rem;
            transition: color 0.2s;
        }
        .footer-links a:hover { color: var(--gold-light); }
        .footer-copyright {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.3);
        }
        .footer-copyright a { color: rgba(255,255,255,0.5); }

        /* ============================================
           ANIMACIONES
        ============================================ */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes bounce {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50%       { transform: translateX(-50%) translateY(8px); }
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.9) translateY(20px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Floating CTA */
        .floating-cta {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 999;
            transform: translateY(120px);
            transition: transform 0.4s ease;
        }
        .floating-cta.visible { transform: translateY(0); }
        .floating-cta-btn {
            background: var(--gold);
            color: var(--wine-dark);
            padding: 0.9rem 1.8rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 800;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            box-shadow: 0 8px 30px rgba(201,168,76,0.5);
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .floating-cta-btn:hover {
            background: var(--gold-light);
            transform: scale(1.05);
        }
        .floating-cta-pulse {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 14px;
            height: 14px;
            background: #e74c3c;
            border-radius: 50%;
            border: 2px solid var(--ivory);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.3); opacity: 0.7; }
        }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 900px) {
            .map-wrapper { grid-template-columns: 1fr; }
            #vino-map { min-height: 400px; }
            .map-sidebar { max-height: 350px; }
            .pricing-grid { grid-template-columns: 1fr; max-width: 360px; }
            .form-grid { grid-template-columns: 1fr; }
            .stats-bar { gap: 0; }
            .stat-item { min-width: 120px; padding: 0.8rem 1rem; }
            .stat-number { font-size: 1.5rem; }
        }

        @media (max-width: 600px) {
            .vino-nav { padding: 0.6rem 1rem; }
            .nav-brand-text { display: none; }
            .hero { padding: 6rem 1.2rem 3rem; }
            .hero-hook-box { padding: 1.5rem; }
            .hero-buttons { flex-direction: column; align-items: stretch; }
            .cta-main { padding: 4rem 1.2rem; }
            .floating-cta { bottom: 1rem; right: 1rem; }
        }

        /* Leaflet popup personalizado */
        .leaflet-popup-content-wrapper {
            border-radius: 12px !important;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15) !important;
        }
        .bodega-popup-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--wine-dark);
            margin-bottom: 0.3rem;
        }
        .bodega-popup-meta {
            font-size: 0.78rem;
            color: var(--text-mid);
            margin-bottom: 0.5rem;
        }
        .bodega-popup-btn {
            display: inline-block;
            background: var(--wine);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<!-- ============ NAVBAR ============ -->
<nav class="vino-nav" id="vinoNav">
    <a href="/" class="nav-brand">
        <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Rurales">
        <div class="nav-brand-text">
            <span class="nav-brand-title">Las Rutas del Vino</span>
            <span class="nav-brand-sub">por rutasrurales.io</span>
        </div>
    </a>
    <div class="nav-cta">
        <a href="#mapa" class="nav-link"><i class="fas fa-map-marked-alt"></i> Ver el mapa</a>
        <a href="#inscribir" class="btn-nav-inscribir">
            <i class="fas fa-wine-bottle"></i> Inscribe tu bodega — 10€
        </a>
    </div>
</nav>

<!-- ============ HERO ============ -->
<section class="hero">
    <div class="hero-badge">
        <i class="fas fa-wine-glass-alt"></i>
        Enoturismo · España
    </div>
    <h1 class="hero-title">Las Rutas <em>del Vino</em></h1>
    <p class="hero-subtitle">El mapa colaborativo de bodegas y enoturismo de España</p>

    <div class="hero-hook-box">
        <p class="hero-hook-eyebrow">🔔 Atención bodeguero</p>
        <h2 class="hero-hook-headline">
            <span class="missing">¡Falta tu bodega!</span><br>
            Los enoturistas te están buscando
        </h2>
        <p class="hero-hook-desc">
            Cada semana cientos de viajeros buscan bodegas donde descubrir el vino.
            Ponles en el mapa hoy mismo. <strong>Sin comisiones. Sin suscripción.</strong>
            Un único pago y visible para siempre.
        </p>
        <div style="margin-bottom:1rem;">
            <span class="hero-price-pill">
                <i class="fas fa-tag"></i>
                Solo <strong>10€</strong> IVA incluido
                &nbsp;<span class="price-strike">30€</span>
            </span>
        </div>
        <div class="hero-buttons">
            <a href="#inscribir" class="btn-primary-wine">
                <i class="fas fa-magic"></i>
                Inscribir mi bodega — 10€
            </a>
            <a href="https://wa.me/34605249696?text=Hola%2C%20quiero%20inscribir%20mi%20bodega%20en%20Las%20Rutas%20del%20Vino" target="_blank" class="btn-secondary-wine">
                <i class="fab fa-whatsapp"></i>
                La inscribís vosotros por mí
            </a>
        </div>
    </div>

    <div class="hero-scroll-hint">
        Descubre el mapa
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

<!-- ============ STATS BAR ============ -->
<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-number">47+</div>
        <div class="stat-label">Bodegas inscritas</div>
    </div>
    <div class="stat-item">
        <div class="stat-number">12</div>
        <div class="stat-label">Regiones vinícolas</div>
    </div>
    <div class="stat-item">
        <div class="stat-number">1.200+</div>
        <div class="stat-label">Visitas al mes</div>
    </div>
    <div class="stat-item">
        <div class="stat-number">10€</div>
        <div class="stat-label">Pago único · IVA incl.</div>
    </div>
</div>

<!-- ============ MAPA ============ -->
<section class="map-section" id="mapa">
    <div class="section-header">
        <p class="section-eyebrow">
            <i class="fas fa-map-marked-alt"></i>
            Mapa interactivo
            <i class="fas fa-map-marked-alt"></i>
        </p>
        <h2 class="section-title">Las bodegas ya están en el mapa.<br>¿Está la tuya?</h2>
        <p class="section-desc">
            Explora las bodegas inscritas. Haz clic en cualquier marcador para ver sus detalles.
            ¿No encuentras la tuya? Inscríbete hoy.
        </p>
    </div>

    <div class="map-wrapper">
        <div id="vino-map"></div>
        <aside class="map-sidebar">
            <h3 class="map-sidebar-title">🍷 Bodegas en el mapa</h3>
            <div id="bodegasList"></div>
            <div class="map-cta-banner">
                <p>¿No ves tu bodega?<br><strong>¡Falta la tuya!</strong><br>
                Inscríbela en menos de 5 minutos.</p>
                <a href="#inscribir" class="btn-map-cta">
                    <i class="fas fa-plus-circle"></i> Añadir mi bodega — 10€
                </a>
            </div>
        </aside>
    </div>
</section>

<!-- ============ VALUE PROPS ============ -->
<section class="value-section" id="ventajas">
    <div class="section-header">
        <p class="section-eyebrow">
            <i class="fas fa-star"></i>
            Por qué inscribirte
            <i class="fas fa-star"></i>
        </p>
        <h2 class="section-title">Todo lo que consigue tu bodega</h2>
        <p class="section-desc">Una sola inversión. Presencia permanente. Sin comisiones sobre reservas.</p>
    </div>
    <div class="value-grid">
        <div class="value-card">
            <div class="value-icon"><i class="fas fa-map-pin"></i></div>
            <h3>Visibilidad en el mapa</h3>
            <p>Tu bodega aparece en el mapa interactivo de Las Rutas del Vino, accesible a todos los enoturistas que visitan la plataforma.</p>
        </div>
        <div class="value-card">
            <div class="value-icon"><i class="fas fa-camera"></i></div>
            <h3>Ficha completa con fotos</h3>
            <p>Nombre, descripción, horarios, tipos de vino, visitas guiadas, catas, fotos y enlace directo a tu web o reservas.</p>
        </div>
        <div class="value-card">
            <div class="value-icon"><i class="fas fa-route"></i></div>
            <h3>En las rutas temáticas</h3>
            <p>Tu bodega aparece en las rutas del vino diseñadas para enoturistas que planifican su viaje desde la plataforma.</p>
        </div>
        <div class="value-card">
            <div class="value-icon"><i class="fas fa-search"></i></div>
            <h3>SEO local potente</h3>
            <p>Benefíciate del posicionamiento de rutasrurales.io. Tu bodega aparece en Google cuando alguien busca enoturismo en tu zona.</p>
        </div>
        <div class="value-card">
            <div class="value-icon"><i class="fas fa-infinity"></i></div>
            <h3>Sin suscripción ni comisiones</h3>
            <p>Pagas una sola vez y tu bodega permanece en el mapa indefinidamente. Sin cuotas mensuales ni comisiones sobre visitas.</p>
        </div>
        <div class="value-card">
            <div class="value-icon"><i class="fas fa-bolt"></i></div>
            <h3>Alta en menos de 5 minutos</h3>
            <p>Rellena el formulario, paga y listo. Si lo prefieres, nosotros lo hacemos por ti al mismo precio.</p>
        </div>
    </div>
</section>

<!-- ============ CTA PRINCIPAL — FORMULARIO DIRECTO ============ -->
<section class="cta-main" id="inscribir">
    <div class="cta-container">

        <p class="cta-eyebrow">
            <i class="fas fa-exclamation-circle"></i>
            Oferta de lanzamiento — plazas limitadas
            <i class="fas fa-exclamation-circle"></i>
        </p>

        <h2 class="cta-headline">
            <span class="accent">¡Falta tu bodega!</span><br>
            Inscríbela ahora por solo
        </h2>

        <!-- PRECIO ENORME E INCONFUNDIBLE -->
        <div class="price-display">
            <div class="price-big"><span class="euro-sym">€</span>10</div>
            <div class="price-details">
                <span class="price-iva-tag">✓ IVA incluido</span>
                <span class="price-desc-line">Pago único · sin cuotas</span>
                <span class="price-desc-line">Alta permanente en el mapa</span>
                <span class="price-strike">Precio normal 30€</span>
            </div>
        </div>

        <!-- FORMULARIO DIRECTO — SIN MODAL -->
        <div class="inscripcion-form-wrapper">
            <p class="inscripcion-form-title">🍷 Rellena y te llevamos a pagar</p>
            <form id="formInscripcion" onsubmit="pagarConStripe(event)">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nombre de la bodega *</label>
                        <input type="text" name="nombre" required placeholder="Bodega El Pago" autocomplete="organization">
                    </div>
                    <div class="form-group">
                        <label>Tu nombre *</label>
                        <input type="text" name="contacto" required placeholder="María García" autocomplete="name">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required placeholder="info@tubodega.com" autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label>Teléfono *</label>
                        <input type="tel" name="telefono" required placeholder="+34 XXX XXX XXX" autocomplete="tel">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Denominación de Origen</label>
                        <input type="text" name="do" placeholder="Ribera del Duero">
                    </div>
                    <div class="form-group">
                        <label>Web o Instagram</label>
                        <input type="text" name="web" placeholder="tubodega.com o @tubodega">
                    </div>
                </div>

                <button type="submit" class="btn-pagar-stripe" id="btnPagarStripe">
                    <i class="fas fa-lock"></i>
                    Pagar con tarjeta
                    <span class="price-in-btn">10€ IVA incl.</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="cta-trust">
                <div class="trust-item"><i class="fas fa-lock"></i> Pago seguro Stripe</div>
                <div class="trust-item"><i class="fas fa-shield-alt"></i> RGPD</div>
                <div class="trust-item"><i class="fas fa-undo"></i> Garantía 14 días</div>
                <div class="trust-item"><i class="fas fa-receipt"></i> Factura automática</div>
            </div>
        </div>

        <!-- ALTERNATIVA: LO HACEMOS NOSOTROS -->
        <div class="cta-alternative">
            <p class="cta-alt-text">¿Prefieres que lo hagamos nosotros? Mismo precio.</p>
            <div class="cta-alt-links">
                <a href="https://wa.me/34605249696?text=Hola%2C%20quiero%20inscribir%20mi%20bodega%20en%20Las%20Rutas%20del%20Vino%20(10%E2%82%AC)"
                   target="_blank" class="btn-alt btn-alt-whatsapp">
                    <i class="fab fa-whatsapp"></i> WhatsApp · +34 605 249 696
                </a>
                <a href="mailto:olgamarin@rutasrurales.io?subject=Inscripci%C3%B3n%20bodega%20-%20Lo%20hac%C3%A9is%20vosotros"
                   class="btn-alt btn-alt-email">
                    <i class="fas fa-envelope"></i> olgamarin@rutasrurales.io
                </a>
            </div>
        </div>

    </div>
</section>

<!-- ============ TESTIMONIOS ============ -->
<section class="social-section" id="testimonios">
    <div class="section-header">
        <p class="section-eyebrow">
            <i class="fas fa-quote-left"></i>
            Bodegas que ya están
            <i class="fas fa-quote-right"></i>
        </p>
        <h2 class="section-title">Lo que dicen las bodegas inscritas</h2>
    </div>
    <div class="testimonials-grid">
        <div class="testimonial-card">
            <div class="testimonial-stars">★★★★★</div>
            <p class="testimonial-text">
                "En dos semanas ya tenía grupos preguntando por catas que llegaron directamente desde el mapa. Una inversión de 10€ que se amortizó al instante."
            </p>
            <div class="testimonial-author">
                <div class="testimonial-avatar">B</div>
                <div>
                    <div class="testimonial-name">Bodega Valcifuentes</div>
                    <div class="testimonial-role">D.O. Ribera del Duero, Soria</div>
                </div>
            </div>
        </div>
        <div class="testimonial-card">
            <div class="testimonial-stars">★★★★★</div>
            <p class="testimonial-text">
                "Muy fácil de inscribir. En 5 minutos estábamos publicados. El mapa es precioso y funciona perfecto en móvil, que es como nos buscan los turistas."
            </p>
            <div class="testimonial-author">
                <div class="testimonial-avatar">C</div>
                <div>
                    <div class="testimonial-name">Covitoro Cooperativa</div>
                    <div class="testimonial-role">D.O. Toro, Zamora</div>
                </div>
            </div>
        </div>
        <div class="testimonial-card">
            <div class="testimonial-stars">★★★★★</div>
            <p class="testimonial-text">
                "Les pedimos que lo hicieran ellos. A las 24 horas ya estábamos en el mapa con todo perfecto. El precio es ridículamente bajo para lo que ofrece."
            </p>
            <div class="testimonial-author">
                <div class="testimonial-avatar">V</div>
                <div>
                    <div class="testimonial-name">Viñedos del Jalón</div>
                    <div class="testimonial-role">D.O. Calatayud, Zaragoza</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ FAQ ============ -->
<section class="faq-section" id="faq">
    <div class="section-header">
        <p class="section-eyebrow">
            <i class="fas fa-question-circle"></i>
            Preguntas frecuentes
            <i class="fas fa-question-circle"></i>
        </p>
        <h2 class="section-title">Todas las respuestas</h2>
    </div>
    <div class="faq-list">
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿Cuánto tiempo permanecerá mi bodega en el mapa?
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-inner">
                    De forma indefinida. No hay cuotas anuales ni de mantenimiento. El pago de 10€ es único y tu bodega permanece visible en el mapa para siempre, mientras la plataforma esté activa.
                </div>
            </div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿Qué información aparece en la ficha de mi bodega?
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-inner">
                    Nombre, descripción, localización en el mapa, tipos de vino, visitas disponibles (catas, guiadas, maridajes), horarios, fotos, y enlace directo a tu web o sistema de reservas. Puedes solicitar actualizar los datos en cualquier momento.
                </div>
            </div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿Cómo funciona el pago?
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-inner">
                    El pago se realiza de forma 100% segura a través de Stripe, la plataforma de pagos líder mundial. Aceptamos tarjeta de crédito y débito. Recibirás la factura automáticamente en tu email.
                </div>
            </div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿Puedo inscribirme aunque no tenga web propia?
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-inner">
                    Por supuesto. No es necesario tener web. La ficha en el mapa puede incluir tu número de teléfono o email de contacto para que los enoturistas se pongan en contacto directamente contigo.
                </div>
            </div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿Qué pasa si quiero actualizar los datos de mi bodega?
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-inner">
                    Puedes solicitar actualizaciones enviando un email a olgamarin@rutasrurales.io. Las actualizaciones son gratuitas para las bodegas inscritas.
                </div>
            </div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿Hay garantía de devolución?
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-inner">
                    Sí. Si por cualquier motivo no estás satisfecho con cómo aparece tu bodega en el mapa, te devolvemos el importe íntegro en los 14 días siguientes al alta. Sin preguntas.
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ FOOTER ============ -->
<footer class="vino-footer">
    <div class="footer-logo">
        <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Rurales">
        <span>Las Rutas del Vino · rutasrurales.io</span>
    </div>
    <div class="footer-links">
        <a href="/aviso-legal.html">Aviso Legal</a>
        <a href="/politica-cookies.html">Privacidad</a>
        <a href="mailto:olgamarin@rutasrurales.io">olgamarin@rutasrurales.io</a>
        <a href="tel:+34605249696">+34 605 249 696</a>
        <a href="/">← Volver a Rutas Rurales</a>
    </div>
    <p class="footer-copyright">
        &copy; 2026 rutasrurales.io · Todos los derechos reservados ·
        <a href="https://www.instagram.com/rutas_rurales/" target="_blank">Instagram</a>
    </p>
</footer>

<!-- ============ FLOATING CTA ============ -->
<div class="floating-cta" id="floatingCta">
    <div class="floating-cta-pulse"></div>
    <button class="floating-cta-btn" onclick="document.getElementById('inscribir').scrollIntoView({behavior:'smooth'})">
        <i class="fas fa-wine-bottle"></i>
        ¡Inscribe tu bodega! — 10€
    </button>
</div>

<!-- sin modales — formulario inline en la sección #inscribir -->

<!-- ============ SCRIPTS ============ -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// ============================================================
// BODEGAS DE MUESTRA (se cargará de la API cuando haya datos)
// ============================================================
const bodegasData = [
    { id: 1, nombre: "Bodega Pago de los Capellanes", lat: 41.584, lng: -4.021, do: "Ribera del Duero", localidad: "Pedrosa de Duero", provincia: "Burgos", tipo: "Visitas y catas", descripcion: "Bodega de referencia de la Ribera con arquitectura vanguardista." },
    { id: 2, nombre: "Viñedos Pintia (Vega Sicilia)", lat: 41.578, lng: -5.251, do: "Toro", localidad: "Quintanilla de Toro", provincia: "Zamora", tipo: "Visitas guiadas", descripcion: "La apuesta de Vega Sicilia en la D.O. Toro." },
    { id: 3, nombre: "Bodegas Protos", lat: 41.599, lng: -4.112, do: "Ribera del Duero", localidad: "Peñafiel", provincia: "Valladolid", tipo: "Visitas, catas y tienda", descripcion: "Emblemática bodega medieval bajo el castillo de Peñafiel." },
    { id: 4, nombre: "Bodega Ochoa", lat: 42.538, lng: -1.645, do: "Navarra", localidad: "Olite", provincia: "Navarra", tipo: "Catas y maridajes", descripcion: "Familia vitivinícola con más de 100 años en Navarra." },
    { id: 5, nombre: "Marqués de Riscal", lat: 42.551, lng: -2.780, do: "Rioja Alavesa", localidad: "Elciego", provincia: "Álava", tipo: "Hotel, visitas y catas", descripcion: "El icónico hotel de Frank Gehry en el corazón de La Rioja." },
    { id: 6, nombre: "Torres (Casa de la Vinya)", lat: 41.382, lng: 1.668, do: "Penedès", localidad: "Vilafranca del Penedès", provincia: "Barcelona", tipo: "Ecoturismo vinícola", descripcion: "Ecoturismo entre viñedos con historia familiar centenaria." },
    { id: 7, nombre: "Bodegas Muga", lat: 42.473, lng: -2.453, do: "Rioja Alta", localidad: "Haro", provincia: "La Rioja", tipo: "Visitas y catas premium", descripcion: "Tradición artesana con crianza en barricas de roble." },
    { id: 8, nombre: "Álvaro Palacios (L'Ermita)", lat: 41.164, lng: 0.691, do: "Priorat", localidad: "Gratallops", provincia: "Tarragona", tipo: "Visitas privadas", descripcion: "El Priorat de autor más reputado de España." },
    { id: 9, nombre: "Pazo de Señorans", lat: 42.589, lng: -8.696, do: "Rías Baixas", localidad: "Meis", provincia: "Pontevedra", tipo: "Visitas y catas Albariño", descripcion: "El Albariño de referencia de Galicia en un pazo señorial." },
    { id: 10, nombre: "Clos Mogador", lat: 41.168, lng: 0.695, do: "Priorat", localidad: "Gratallops", provincia: "Tarragona", tipo: "Visitas y catas", descripcion: "Pionero de la revolución del Priorat desde los años 90." },
    { id: 11, nombre: "Bodegas Arzuaga Navarro", lat: 41.614, lng: -4.100, do: "Ribera del Duero", localidad: "Quintanilla de Onésimo", provincia: "Valladolid", tipo: "Hotel, spa y catas", descripcion: "Resort vinícola con hotel boutique entre viñedos." },
    { id: 12, nombre: "Rectoral de Amandi", lat: 42.345, lng: -7.483, do: "Ribeira Sacra", localidad: "Sober", provincia: "Lugo", tipo: "Visitas y catas Mencía", descripcion: "Viñedos en terrazas sobre el Sil, Patrimonio Natural." },
    { id: 13, nombre: "Habla del Silencio", lat: 39.487, lng: -5.866, do: "Extremadura", localidad: "Trujillo", provincia: "Cáceres", tipo: "Catas y visitas", descripcion: "Vinos de alta expresión en el corazón de Extremadura." },
    { id: 14, nombre: "Bodegas Mas Alta", lat: 41.165, lng: 0.700, do: "Priorat", localidad: "La Vilella Alta", provincia: "Tarragona", tipo: "Visitas", descripcion: "Familia francesa enamorada del Priorat y su licorella." },
    { id: 15, nombre: "Bodega Contador", lat: 42.471, lng: -2.850, do: "Rioja Alta", localidad: "San Vicente de la Sonsierra", provincia: "La Rioja", tipo: "Visitas exclusivas", descripcion: "Benjamin Romeo, uno de los mejores vinos de España." },
];

// ============================================================
// MAPA LEAFLET
// ============================================================
let vinoMap;
let bodegaMarkers = [];

function initVinoMap() {
    vinoMap = L.map('vino-map').setView([40.5, -3.5], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 18
    }).addTo(vinoMap);

    // Icono personalizado con color vino
    function createVinoIcon(index) {
        return L.divIcon({
            className: '',
            html: `<div style="
                background: linear-gradient(135deg, #722F37, #4A1820);
                color: white;
                width: 34px; height: 34px;
                border-radius: 50% 50% 50% 0;
                transform: rotate(-45deg);
                border: 3px solid #C9A84C;
                box-shadow: 0 3px 10px rgba(0,0,0,0.3);
                display: flex; align-items: center; justify-content: center;
            "><span style="transform:rotate(45deg); font-size:14px;">🍷</span></div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 34],
            popupAnchor: [0, -34]
        });
    }

    const sidebarList = document.getElementById('bodegasList');
    sidebarList.innerHTML = '';

    bodegasData.forEach((bodega, index) => {
        // Marcador en mapa
        const marker = L.marker([bodega.lat, bodega.lng], { icon: createVinoIcon(index) })
            .addTo(vinoMap)
            .bindPopup(`
                <div style="width:220px; font-family:'Montserrat',sans-serif;">
                    <div class="bodega-popup-title">🍷 ${bodega.nombre}</div>
                    <div class="bodega-popup-meta">
                        <i class="fas fa-map-marker-alt" style="color:#722F37;"></i>
                        ${bodega.localidad}, ${bodega.provincia}
                    </div>
                    <div class="bodega-popup-meta" style="margin-bottom:0.5rem;">
                        <strong style="color:#722F37;">D.O. ${bodega.do}</strong>
                    </div>
                    <p style="font-size:0.78rem;color:#555;margin-bottom:0.7rem;line-height:1.4;">${bodega.descripcion}</p>
                    <span style="background:#f0e8ea;color:#722F37;padding:0.2rem 0.6rem;border-radius:20px;font-size:0.65rem;font-weight:700;">${bodega.tipo}</span>
                </div>
            `);

        bodegaMarkers.push(marker);

        // Card en sidebar
        const card = document.createElement('div');
        card.className = 'bodega-card';
        card.innerHTML = `
            <div class="bodega-card-name">${bodega.nombre}</div>
            <div class="bodega-card-meta">
                <i class="fas fa-map-marker-alt"></i>
                ${bodega.localidad} · ${bodega.provincia}
            </div>
            <span class="bodega-card-tipo">D.O. ${bodega.do}</span>
        `;
        card.onclick = () => {
            vinoMap.setView([bodega.lat, bodega.lng], 13);
            marker.openPopup();
        };
        sidebarList.appendChild(card);
    });

    setTimeout(() => vinoMap.invalidateSize(), 500);
    window.addEventListener('resize', () => vinoMap && vinoMap.invalidateSize());
}

// ============================================================
// PAGO STRIPE — FORMULARIO DIRECTO (SIN MODAL)
// ============================================================
async function pagarConStripe(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('btnPagarStripe');
    const formData = new FormData(form);

    const nombre   = (formData.get('nombre')   || '').trim();
    const contacto = (formData.get('contacto') || '').trim();
    const email    = (formData.get('email')    || '').trim();
    const telefono = (formData.get('telefono') || '').trim();

    if (!nombre || !email || !telefono || !contacto) {
        showToast('Por favor, completa los campos obligatorios (*)', 'error');
        return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showToast('Por favor, introduce un email válido', 'error');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Conectando con Stripe...';

    const bodegaInfo = {
        nombre,
        contacto,
        do:       (formData.get('do')  || '').trim(),
        web:      (formData.get('web') || '').trim(),
        telefono,
        email
    };

    try {
        const response = await fetch('/rutas-del-vino/api/checkout-bodega.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email,
                bodega_info: bodegaInfo,
                success_url: 'https://rutasrurales.io/rutas-del-vino/gracias.php',
                cancel_url:  'https://rutasrurales.io/rutas-del-vino/#inscribir'
            })
        });

        if (!response.ok) throw new Error('HTTP ' + response.status);
        const data = await response.json();

        if (data.success && data.checkout_url) {
            // Redirigir directamente al checkout de Stripe
            window.location.href = data.checkout_url;
        } else {
            throw new Error(data.message || 'No se recibió URL de pago');
        }
    } catch (err) {
        console.error('Error checkout:', err);
        showToast('Error al conectar con el servidor de pago. Escríbenos a olgamarin@rutasrurales.io o por WhatsApp.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-lock"></i> Pagar con tarjeta <span class="price-in-btn">10€ IVA incl.</span> <i class="fas fa-arrow-right"></i>';
    }
}

// ============================================================
// FAQ TOGGLE
// ============================================================
function toggleFaq(btn) {
    const answer = btn.nextElementSibling;
    const isOpen = answer.classList.contains('open');
    // Cerrar todos
    document.querySelectorAll('.faq-answer.open').forEach(a => a.classList.remove('open'));
    document.querySelectorAll('.faq-question.open').forEach(b => b.classList.remove('open'));
    // Abrir si no estaba abierto
    if (!isOpen) {
        answer.classList.add('open');
        btn.classList.add('open');
    }
}

// ============================================================
// TOAST NOTIFICATIONS
// ============================================================
function showToast(message, type = 'info') {
    const existing = document.querySelector('.vino-toast');
    if (existing) existing.remove();

    const colors = {
        success: 'var(--green-vine)',
        error: 'var(--wine)',
        info: '#2c3e50'
    };
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        info: 'info-circle'
    };

    const toast = document.createElement('div');
    toast.className = 'vino-toast';
    toast.style.cssText = `
        position: fixed; bottom: 5rem; left: 50%; transform: translateX(-50%);
        background: ${colors[type]}; color: white;
        padding: 1rem 2rem; border-radius: 12px;
        font-family: 'Montserrat',sans-serif; font-size: 0.88rem; font-weight: 600;
        z-index: 999999; display: flex; align-items: center; gap: 0.6rem;
        box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        animation: fadeInUp 0.3s ease;
        max-width: 90vw; text-align: center;
    `;
    toast.innerHTML = `<i class="fas fa-${icons[type]}"></i> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => { if (toast.parentNode) toast.remove(); }, 5000);
}

// ============================================================
// NAVBAR SCROLL + FLOATING CTA
// ============================================================
window.addEventListener('scroll', () => {
    const nav = document.getElementById('vinoNav');
    const floatingCta = document.getElementById('floatingCta');
    if (window.scrollY > 80) {
        nav.classList.add('scrolled');
        floatingCta.classList.add('visible');
    } else {
        nav.classList.remove('scrolled');
        floatingCta.classList.remove('visible');
    }
});

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    initVinoMap();
});
</script>
</body>
</html>

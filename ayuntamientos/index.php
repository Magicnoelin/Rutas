<?php
/**
 * Ayuntamientos — Landing page B2B para municipios
 * Dirigida a alcaldes, concejales de turismo y técnicos municipales
 * Plan Básico: 19€ (5 lugares) | Plan Cultural: 39€ (5 lugares + 5 eventos)
 * Renovación anual eventos: 19,99€ | Evento extra: 5€/ud
 */
$page_title       = "Pon tu Municipio en el Mapa — Rutas Rurales para Ayuntamientos";
$page_description = "¿Tus fiestas patronales, rutas y monumentos no aparecen en el mapa? Inscribe tu municipio desde 19€. Llega a miles de turistas que buscan turismo rural auténtico en España.";
$page_canonical   = "https://rutasrurales.io/ayuntamientos/";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-MBP57VQM');</script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $page_description ?>">
    <title><?= $page_title ?></title>
    <link rel="canonical" href="<?= $page_canonical ?>">
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ============================================
           VARIABLES & RESET
        ============================================ */
        :root {
            --azul:        #1B4F72;
            --azul-dark:   #0E2D45;
            --azul-light:  #2E86C1;
            --terra:       #C0392B;
            --terra-light: #E74C3C;
            --ocre:        #D4AC0D;
            --ocre-light:  #F1C40F;
            --verde:       #1E8449;
            --verde-light: #27AE60;
            --crema:       #FDF6EC;
            --crema-dark:  #F0E6D3;
            --ivory:       #FFFDF9;
            --text-dark:   #1A1A1A;
            --text-mid:    #4A4A4A;
            --text-light:  #7A7A7A;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; overflow-x: hidden; }
        body { font-family: 'Montserrat', sans-serif; color: var(--text-dark); background: var(--ivory); overflow-x: hidden; }

        /* ============================================
           NAVBAR
        ============================================ */
        .ayto-nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 9999;
            background: rgba(11,29,48,0.93); backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(212,172,13,0.25);
            padding: 0.75rem 2rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;
            transition: all 0.3s;
        }
        .ayto-nav.scrolled { background: rgba(11,29,48,0.99); padding: 0.5rem 2rem; }
        .nav-brand { display: flex; align-items: center; gap: 0.8rem; text-decoration: none; }
        .nav-brand img { height: 36px; }
        .nav-brand-text { display: flex; flex-direction: column; line-height: 1.1; }
        .nav-brand-title { font-family: 'Playfair Display', serif; font-size: 1rem; font-weight: 700; color: #F1C40F; }
        .nav-brand-sub { font-size: 0.6rem; font-weight: 600; color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 0.15em; }
        .nav-cta { display: flex; align-items: center; gap: 1rem; }
        .nav-link { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.8rem; font-weight: 600; transition: color 0.2s; }
        .nav-link:hover { color: #F1C40F; }
        .btn-nav-inscribir {
            background: var(--terra); color: #fff;
            padding: 0.5rem 1.2rem; border-radius: 25px;
            font-size: 0.8rem; font-weight: 800; text-decoration: none;
            transition: all 0.2s; display: flex; align-items: center; gap: 0.4rem; white-space: nowrap;
        }
        .btn-nav-inscribir:hover { background: var(--terra-light); transform: translateY(-1px); box-shadow: 0 4px 16px rgba(192,57,43,0.4); }

        /* ============================================
           HERO
        ============================================ */
        .hero {
            min-height: 100vh;
            background:
                linear-gradient(150deg,
                    rgba(14,45,69,0.91) 0%,
                    rgba(27,79,114,0.82) 45%,
                    rgba(30,132,73,0.75) 100%),
                url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1600&q=80') center/cover no-repeat;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center; padding: 8rem 2rem 4rem; position: relative; overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; bottom: 0; left: 0; right: 0;
            height: 120px; background: linear-gradient(to top, var(--ivory), transparent);
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(212,172,13,0.2); border: 1px solid rgba(212,172,13,0.5);
            color: #F1C40F; padding: 0.4rem 1.2rem; border-radius: 30px;
            font-size: 0.72rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
            margin-bottom: 1.5rem; animation: fadeInDown 0.6s ease;
        }
        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.6rem, 6vw, 5rem); font-weight: 900; color: #fff;
            line-height: 1.1; margin-bottom: 0.8rem; animation: fadeInUp 0.7s ease;
        }
        .hero-title em { font-style: italic; color: #F1C40F; }
        .hero-subtitle {
            font-size: clamp(1rem, 2.2vw, 1.35rem); color: rgba(255,255,255,0.82);
            margin-bottom: 2.5rem; max-width: 580px; animation: fadeInUp 0.8s ease;
        }
        .hero-hook-box {
            background: rgba(255,255,255,0.1); backdrop-filter: blur(8px);
            border: 2px solid rgba(212,172,13,0.55); border-radius: 20px;
            padding: 2rem 3rem; max-width: 750px; margin: 0 auto 3rem;
            animation: fadeInUp 0.9s ease;
        }
        .hero-hook-eyebrow { font-size: 0.72rem; font-weight: 800; letter-spacing: 0.2em; text-transform: uppercase; color: #F1C40F; margin-bottom: 0.8rem; }
        .hero-hook-headline { font-family: 'Playfair Display', serif; font-size: clamp(1.7rem, 3.5vw, 2.8rem); font-weight: 900; color: #fff; line-height: 1.2; margin-bottom: 0.8rem; }
        .hero-hook-headline .missing { color: #F1C40F; text-decoration: underline; text-decoration-color: rgba(212,172,13,0.5); text-underline-offset: 6px; }
        .hero-hook-desc { font-size: 0.93rem; color: rgba(255,255,255,0.78); line-height: 1.65; margin-bottom: 1.5rem; }
        .hero-from-price {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: var(--terra); color: #fff;
            padding: 0.5rem 1.5rem; border-radius: 30px; font-size: 1rem; font-weight: 800; margin-bottom: 1.2rem;
        }
        .hero-buttons { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        .btn-primary {
            background: var(--ocre); color: var(--azul-dark);
            padding: 1rem 2.2rem; border-radius: 50px; font-size: 0.95rem; font-weight: 800;
            text-decoration: none; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.6rem;
            border: none; cursor: pointer; box-shadow: 0 4px 20px rgba(212,172,13,0.4);
        }
        .btn-primary:hover { background: #F1C40F; transform: translateY(-2px); box-shadow: 0 8px 30px rgba(212,172,13,0.5); }
        .btn-secondary {
            background: transparent; color: #fff;
            padding: 1rem 2.2rem; border-radius: 50px; font-size: 0.95rem; font-weight: 700;
            text-decoration: none; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.6rem;
            border: 2px solid rgba(255,255,255,0.4); cursor: pointer;
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.7); }
        .hero-scroll-hint {
            position: absolute; bottom: 2rem; left: 50%; transform: translateX(-50%);
            color: rgba(255,255,255,0.45); font-size: 0.72rem; text-align: center; animation: bounce 2s infinite;
        }
        .hero-scroll-hint i { display: block; font-size: 1.2rem; margin-top: 0.3rem; }

        /* ============================================
           SEASONS STRIP — "No solo verano"
        ============================================ */
        .seasons-strip {
            background: linear-gradient(90deg, #1B4F72, #1E8449, #C0392B, #D4AC0D);
            padding: 0.1rem;
        }
        .seasons-inner {
            background: var(--azul-dark); display: flex; justify-content: center;
            gap: 0; flex-wrap: wrap;
        }
        .season-item {
            padding: 0.8rem 2rem; text-align: center; flex: 1; min-width: 140px;
            border-right: 1px solid rgba(255,255,255,0.08);
        }
        .season-item:last-child { border-right: none; }
        .season-icon { font-size: 1.5rem; display: block; margin-bottom: 0.2rem; }
        .season-label { font-size: 0.68rem; font-weight: 700; color: rgba(255,255,255,0.55); text-transform: uppercase; letter-spacing: 0.1em; }

        /* ============================================
           STATS BAR
        ============================================ */
        .stats-bar { background: var(--azul-dark); padding: 2rem; display: flex; justify-content: center; flex-wrap: wrap; }
        .stat-item { text-align: center; padding: 1rem 2.5rem; border-right: 1px solid rgba(212,172,13,0.2); flex: 1; min-width: 130px; }
        .stat-item:last-child { border-right: none; }
        .stat-number { font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 700; color: #F1C40F; line-height: 1; }
        .stat-label { font-size: 0.68rem; font-weight: 600; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.1em; margin-top: 0.3rem; }

        /* ============================================
           MAP SECTION
        ============================================ */
        .map-section { padding: 5rem 0 0; background: var(--crema); }
        .section-header { text-align: center; padding: 0 2rem 3rem; max-width: 800px; margin: 0 auto; }
        .section-eyebrow {
            display: inline-flex; align-items: center; gap: 0.5rem;
            font-size: 0.7rem; font-weight: 800; letter-spacing: 0.2em; text-transform: uppercase; color: var(--azul);
            margin-bottom: 1rem;
        }
        .section-eyebrow::before, .section-eyebrow::after { content: ''; display: block; width: 30px; height: 1px; background: var(--azul); }
        .section-title { font-family: 'Playfair Display', serif; font-size: clamp(1.9rem, 4vw, 2.8rem); font-weight: 700; color: var(--azul-dark); line-height: 1.2; margin-bottom: 1rem; }
        .section-desc { font-size: 0.97rem; color: var(--text-mid); line-height: 1.7; }

        .map-wrapper { display: grid; grid-template-columns: 1fr 380px; min-height: 600px; }
        #ayto-map { height: 100%; min-height: 600px; width: 100%; }
        .map-sidebar { background: var(--azul-dark); padding: 2rem 1.5rem; overflow-y: auto; max-height: 600px; }
        .map-sidebar-title { font-family: 'Playfair Display', serif; font-size: 1.1rem; color: #F1C40F; margin-bottom: 1.5rem; padding-bottom: 0.8rem; border-bottom: 1px solid rgba(212,172,13,0.2); }
        .municipio-card { background: rgba(255,255,255,0.06); border: 1px solid rgba(212,172,13,0.15); border-radius: 12px; padding: 1rem; margin-bottom: 0.8rem; cursor: pointer; transition: all 0.2s; }
        .municipio-card:hover { background: rgba(212,172,13,0.1); border-color: rgba(212,172,13,0.4); transform: translateX(3px); }
        .municipio-card-name { font-size: 0.88rem; font-weight: 700; color: #fff; margin-bottom: 0.3rem; }
        .municipio-card-meta { font-size: 0.7rem; color: rgba(255,255,255,0.5); display: flex; align-items: center; gap: 0.4rem; }
        .municipio-card-meta i { color: #F1C40F; }
        .municipio-badge { display: inline-flex; gap: 0.3rem; margin-top: 0.4rem; flex-wrap: wrap; }
        .badge-small { background: rgba(212,172,13,0.2); color: #F1C40F; font-size: 0.58rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; padding: 0.1rem 0.45rem; border-radius: 20px; }
        .badge-blue { background: rgba(46,134,193,0.2); color: #85C1E9; }
        .badge-green { background: rgba(39,174,96,0.2); color: #82E0AA; }
        .map-cta-banner { background: linear-gradient(135deg, var(--terra), #922B21); padding: 1.5rem; border-radius: 12px; text-align: center; margin-top: 1rem; }
        .map-cta-banner p { color: rgba(255,255,255,0.85); font-size: 0.8rem; line-height: 1.5; margin-bottom: 1rem; }
        .map-cta-banner strong { color: #F1C40F; }
        .btn-map-cta { display: block; background: #F1C40F; color: var(--azul-dark); padding: 0.7rem 1rem; border-radius: 25px; font-size: 0.8rem; font-weight: 800; text-decoration: none; transition: all 0.2s; }
        .btn-map-cta:hover { background: #D4AC0D; }

        /* ============================================
           VALUE PROPS
        ============================================ */
        .value-section { padding: 5rem 2rem; background: var(--ivory); }
        .value-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; max-width: 1100px; margin: 0 auto; }
        .value-card { background: #fff; border-radius: 20px; padding: 2rem; border: 1px solid var(--crema-dark); transition: all 0.3s; position: relative; overflow: hidden; }
        .value-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--azul), var(--ocre)); }
        .value-card:hover { transform: translateY(-5px); box-shadow: 0 8px 32px rgba(27,79,114,0.15); }
        .value-icon { width: 58px; height: 58px; background: linear-gradient(135deg, var(--azul), var(--azul-light)); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #F1C40F; margin-bottom: 1.2rem; }
        .value-card h3 { font-family: 'Playfair Display', serif; font-size: 1.15rem; color: var(--azul-dark); margin-bottom: 0.7rem; }
        .value-card p { font-size: 0.88rem; color: var(--text-mid); line-height: 1.65; }

        /* ============================================
           PRICING — 2 PLANES
        ============================================ */
        .pricing-section { padding: 5rem 2rem; background: linear-gradient(160deg, #0E2D45 0%, #1B4F72 60%, #1A5276 100%); }
        .pricing-section .section-title { color: #fff; }
        .pricing-section .section-desc { color: rgba(255,255,255,0.65); }
        .pricing-section .section-eyebrow { color: #F1C40F; }
        .pricing-section .section-eyebrow::before,
        .pricing-section .section-eyebrow::after { background: #F1C40F; }

        .pricing-grid-wrap { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; max-width: 860px; margin: 3rem auto 0; }
        .plan-card {
            background: rgba(255,255,255,0.07); backdrop-filter: blur(8px);
            border: 2px solid rgba(212,172,13,0.25); border-radius: 24px;
            padding: 2.5rem 2rem; position: relative; transition: all 0.3s;
        }
        .plan-card.featured { border-color: #D4AC0D; background: rgba(212,172,13,0.1); }
        .plan-card.featured::before {
            content: '⭐ MÁS COMPLETO';
            position: absolute; top: -14px; left: 50%; transform: translateX(-50%);
            background: #D4AC0D; color: var(--azul-dark);
            font-size: 0.62rem; font-weight: 900; padding: 0.3rem 1.2rem;
            border-radius: 20px; white-space: nowrap; letter-spacing: 0.1em;
        }
        .plan-card:hover { transform: translateY(-6px); border-color: #D4AC0D; box-shadow: 0 12px 40px rgba(0,0,0,0.25); }
        .plan-label { font-size: 0.68rem; font-weight: 800; letter-spacing: 0.2em; text-transform: uppercase; color: rgba(255,255,255,0.5); margin-bottom: 0.5rem; }
        .plan-name { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; color: #fff; margin-bottom: 0.3rem; }
        .plan-desc-short { font-size: 0.8rem; color: rgba(255,255,255,0.55); margin-bottom: 1.5rem; min-height: 2.5rem; line-height: 1.4; }
        .plan-price-block { margin-bottom: 1.5rem; }
        .plan-price-main { font-family: 'Playfair Display', serif; font-size: 4rem; font-weight: 900; color: #F1C40F; line-height: 1; }
        .plan-price-main sup { font-size: 1.4rem; vertical-align: top; margin-top: 0.5rem; }
        .plan-price-sub { font-size: 0.72rem; color: rgba(255,255,255,0.45); margin-top: 0.2rem; }
        .plan-price-renewal {
            background: rgba(30,132,73,0.2); border: 1px solid rgba(30,132,73,0.4);
            color: #82E0AA; font-size: 0.72rem; font-weight: 700;
            padding: 0.3rem 0.8rem; border-radius: 20px; display: inline-block; margin-top: 0.5rem;
        }
        .plan-includes-basic {
            background: rgba(46,134,193,0.15); border: 1px solid rgba(46,134,193,0.3);
            color: #85C1E9; font-size: 0.72rem; font-weight: 700;
            padding: 0.3rem 0.8rem; border-radius: 20px; display: inline-block; margin-top: 0.5rem;
        }
        .plan-features { list-style: none; margin-bottom: 1.8rem; }
        .plan-features li { font-size: 0.82rem; color: rgba(255,255,255,0.78); padding: 0.4rem 0; display: flex; align-items: flex-start; gap: 0.6rem; }
        .plan-features li .fa-check { color: #D4AC0D; flex-shrink: 0; margin-top: 0.1rem; }
        .plan-features li .fa-check-circle { color: #27AE60; flex-shrink: 0; margin-top: 0.1rem; }
        .plan-features li .fa-times { color: rgba(255,255,255,0.2); flex-shrink: 0; margin-top: 0.1rem; }
        .plan-features li span.pill {
            background: rgba(192,57,43,0.25); color: #E98B80; font-size: 0.65rem; font-weight: 700;
            padding: 0.1rem 0.45rem; border-radius: 10px; margin-left: 0.2rem;
        }
        .btn-plan {
            width: 100%; padding: 1rem; border-radius: 14px;
            font-family: 'Montserrat', sans-serif; font-size: 0.95rem; font-weight: 800;
            cursor: pointer; transition: all 0.25s; display: flex; align-items: center; justify-content: center; gap: 0.6rem;
            border: none; text-decoration: none;
        }
        .btn-plan-primary { background: #D4AC0D; color: var(--azul-dark); box-shadow: 0 6px 20px rgba(212,172,13,0.4); }
        .btn-plan-primary:hover { background: #F1C40F; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(212,172,13,0.55); }
        .btn-plan-secondary { background: rgba(255,255,255,0.1); color: #fff; border: 2px solid rgba(255,255,255,0.25); }
        .btn-plan-secondary:hover { background: rgba(255,255,255,0.18); border-color: rgba(255,255,255,0.5); }

        /* Extras */
        .pricing-extras { max-width: 860px; margin: 2.5rem auto 0; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .extra-card { background: rgba(255,255,255,0.06); border: 1px solid rgba(212,172,13,0.2); border-radius: 16px; padding: 1.5rem; text-align: center; }
        .extra-price { font-family: 'Playfair Display', serif; font-size: 2.2rem; color: #F1C40F; font-weight: 700; line-height: 1; margin-bottom: 0.4rem; }
        .extra-price sup { font-size: 1rem; vertical-align: top; margin-top: 0.3rem; }
        .extra-label { font-size: 0.8rem; color: rgba(255,255,255,0.65); font-weight: 600; line-height: 1.4; }
        .pricing-note { max-width: 860px; margin: 1.5rem auto 0; text-align: center; color: rgba(255,255,255,0.4); font-size: 0.75rem; }

        /* ============================================
           CTA / FORMULARIO INLINE
        ============================================ */
        .cta-section { padding: 5rem 2rem; background: var(--crema); }
        .cta-container { max-width: 780px; margin: 0 auto; }
        .cta-eyebrow {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(27,79,114,0.12); border: 1px solid rgba(27,79,114,0.3);
            color: var(--azul); padding: 0.4rem 1.2rem; border-radius: 30px;
            font-size: 0.72rem; font-weight: 800; letter-spacing: 0.15em; text-transform: uppercase; margin-bottom: 1.5rem;
        }
        .cta-headline { font-family: 'Playfair Display', serif; font-size: clamp(2rem, 4.5vw, 3.2rem); font-weight: 900; color: var(--azul-dark); line-height: 1.15; margin-bottom: 0.8rem; text-align: center; }
        .cta-headline .accent { color: var(--terra); }
        .cta-sub { text-align: center; color: var(--text-mid); font-size: 0.95rem; margin-bottom: 2.5rem; line-height: 1.65; }

        /* Selector de plan */
        .plan-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem; }
        .plan-option {
            border: 2px solid var(--crema-dark); border-radius: 16px; padding: 1.2rem 1rem;
            cursor: pointer; transition: all 0.2s; background: #fff; text-align: center; position: relative;
        }
        .plan-option.selected { border-color: var(--azul); background: rgba(27,79,114,0.05); }
        .plan-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
        .plan-option-name { font-weight: 800; font-size: 1rem; color: var(--azul-dark); margin-bottom: 0.3rem; }
        .plan-option-price { font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 700; color: var(--terra); line-height: 1; }
        .plan-option-price sup { font-size: 0.9rem; vertical-align: top; margin-top: 0.3rem; }
        .plan-option-desc { font-size: 0.72rem; color: var(--text-light); margin-top: 0.3rem; line-height: 1.3; }
        .plan-option-badge {
            position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
            background: var(--verde); color: #fff; font-size: 0.6rem; font-weight: 800;
            padding: 0.2rem 0.8rem; border-radius: 20px; white-space: nowrap;
        }
        .plan-checkmark {
            position: absolute; top: 0.7rem; right: 0.7rem;
            width: 22px; height: 22px; border-radius: 50%;
            background: var(--azul); color: #fff; display: none;
            align-items: center; justify-content: center; font-size: 0.7rem;
        }
        .plan-option.selected .plan-checkmark { display: flex; }

        /* Formulario */
        .inscripcion-form-wrapper {
            background: #fff; border: 1px solid var(--crema-dark);
            border-radius: 24px; padding: 2.5rem 2rem 2rem; box-shadow: 0 4px 24px rgba(27,79,114,0.08);
        }
        .form-title { font-family: 'Playfair Display', serif; font-size: 1.2rem; color: var(--azul-dark); text-align: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-mid); margin-bottom: 0.3rem; text-transform: uppercase; letter-spacing: 0.08em; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 0.8rem 1rem;
            border: 2px solid var(--crema-dark); border-radius: 10px;
            font-family: 'Montserrat', sans-serif; font-size: 16px; color: var(--text-dark);
            background: #fff; transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: var(--azul); box-shadow: 0 0 0 3px rgba(27,79,114,0.08);
        }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        /* Resumen del plan seleccionado */
        .form-plan-summary {
            background: rgba(27,79,114,0.06); border: 1px solid rgba(27,79,114,0.15);
            border-radius: 12px; padding: 1rem 1.2rem; margin: 1.2rem 0;
            display: flex; justify-content: space-between; align-items: center;
        }
        .summary-label { font-size: 0.82rem; color: var(--text-mid); font-weight: 600; }
        .summary-plan-name { font-size: 0.95rem; font-weight: 800; color: var(--azul-dark); }
        .summary-price { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; color: var(--terra); }
        .summary-note { font-size: 0.68rem; color: var(--text-light); }

        .btn-pagar {
            width: 100%; padding: 1.1rem 2rem; background: var(--azul);
            color: #fff; border: none; border-radius: 14px;
            font-family: 'Montserrat', sans-serif; font-size: 1.05rem; font-weight: 900;
            cursor: pointer; transition: all 0.25s; display: flex; align-items: center; justify-content: center; gap: 0.6rem;
            margin-top: 1.5rem; box-shadow: 0 6px 20px rgba(27,79,114,0.3);
        }
        .btn-pagar:hover { background: var(--azul-dark); transform: translateY(-3px); box-shadow: 0 10px 30px rgba(27,79,114,0.4); }
        .btn-pagar:disabled { opacity: 0.65; cursor: not-allowed; transform: none; }
        .btn-pagar .price-pill { background: rgba(255,255,255,0.2); padding: 0.2rem 0.7rem; border-radius: 20px; }

        .form-trust { display: flex; align-items: center; justify-content: center; gap: 1.5rem; flex-wrap: wrap; margin-top: 1rem; }
        .trust-item { display: flex; align-items: center; gap: 0.4rem; color: var(--text-light); font-size: 0.72rem; font-weight: 600; }
        .trust-item i { color: var(--azul); font-size: 0.85rem; }

        .cta-alternative { margin-top: 2rem; text-align: center; }
        .cta-alt-text { color: var(--text-light); font-size: 0.82rem; margin-bottom: 0.8rem; }
        .cta-alt-links { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        .btn-alt { padding: 0.6rem 1.3rem; border-radius: 25px; font-size: 0.8rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s; }
        .btn-alt-whatsapp { background: rgba(37,211,102,0.1); color: #25d366; border: 1px solid rgba(37,211,102,0.4); }
        .btn-alt-whatsapp:hover { background: rgba(37,211,102,0.2); }
        .btn-alt-email { background: rgba(27,79,114,0.1); color: var(--azul); border: 1px solid rgba(27,79,114,0.3); }
        .btn-alt-email:hover { background: rgba(27,79,114,0.18); }

        /* ============================================
           FAQ
        ============================================ */
        .faq-section { padding: 5rem 2rem; background: var(--ivory); }
        .faq-list { max-width: 750px; margin: 0 auto; }
        .faq-item { border-bottom: 1px solid var(--crema-dark); overflow: hidden; }
        .faq-question { width: 100%; background: none; border: none; padding: 1.2rem 0; text-align: left; display: flex; justify-content: space-between; align-items: center; cursor: pointer; font-family: 'Montserrat', sans-serif; font-size: 0.95rem; font-weight: 700; color: var(--azul-dark); gap: 1rem; }
        .faq-question i { color: var(--azul); font-size: 0.8rem; transition: transform 0.3s; flex-shrink: 0; }
        .faq-question.open i { transform: rotate(180deg); }
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
        .faq-answer.open { max-height: 300px; }
        .faq-answer-inner { padding: 0 0 1.2rem; font-size: 0.88rem; color: var(--text-mid); line-height: 1.7; }

        /* ============================================
           FOOTER
        ============================================ */
        .ayto-footer { background: var(--azul-dark); padding: 2.5rem 2rem; text-align: center; }
        .footer-logo { display: flex; align-items: center; justify-content: center; gap: 0.8rem; margin-bottom: 1rem; }
        .footer-logo img { height: 32px; filter: brightness(0) invert(1) opacity(0.7); }
        .footer-logo span { font-family: 'Playfair Display', serif; font-size: 1rem; color: rgba(255,255,255,0.5); }
        .footer-links { display: flex; justify-content: center; gap: 1.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .footer-links a { color: rgba(255,255,255,0.4); text-decoration: none; font-size: 0.78rem; transition: color 0.2s; }
        .footer-links a:hover { color: #F1C40F; }
        .footer-copyright { font-size: 0.72rem; color: rgba(255,255,255,0.3); }

        /* ============================================
           ANIMATIONS + FLOATING CTA
        ============================================ */
        @keyframes fadeInDown { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
        @keyframes fadeInUp   { from { opacity:0; transform:translateY(30px);  } to { opacity:1; transform:translateY(0); } }
        @keyframes bounce     { 0%,100% { transform:translateX(-50%) translateY(0); } 50% { transform:translateX(-50%) translateY(8px); } }
        @keyframes pulse      { 0%,100% { transform:scale(1); opacity:1; } 50% { transform:scale(1.3); opacity:0.7; } }

        .floating-cta { position: fixed; bottom: 2rem; right: 2rem; z-index: 999; transform: translateY(120px); transition: transform 0.4s ease; }
        .floating-cta.visible { transform: translateY(0); }
        .floating-cta-btn {
            background: var(--terra); color: #fff;
            padding: 0.9rem 1.8rem; border-radius: 50px; font-size: 0.85rem; font-weight: 800;
            display: flex; align-items: center; gap: 0.6rem;
            box-shadow: 0 8px 30px rgba(192,57,43,0.5); transition: all 0.2s; border: none; cursor: pointer;
        }
        .floating-cta-btn:hover { background: var(--terra-light); transform: scale(1.05); }
        .floating-pulse { position: absolute; top: -4px; right: -4px; width: 14px; height: 14px; background: #F1C40F; border-radius: 50%; border: 2px solid var(--ivory); animation: pulse 2s infinite; }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 900px) {
            .map-wrapper { grid-template-columns: 1fr; }
            #ayto-map { min-height: 400px; }
            .map-sidebar { max-height: 350px; }
            .pricing-grid-wrap, .pricing-extras { grid-template-columns: 1fr; max-width: 400px; }
            .plan-card.featured::before { top: -12px; }
        }
        @media (max-width: 640px) {
            .form-grid, .plan-selector { grid-template-columns: 1fr; }
            .hero-hook-box { padding: 1.5rem; }
            .hero-buttons { flex-direction: column; align-items: stretch; }
            .seasons-inner { flex-direction: row; }
            .season-item { padding: 0.6rem 1rem; min-width: 70px; }
        }
        @media (max-width: 600px) {
            .ayto-nav { padding: 0.6rem 1rem; }
            .nav-brand-text { display: none; }
            .floating-cta { bottom: 1rem; right: 1rem; }
        }

        /* Leaflet popup */
        .leaflet-popup-content-wrapper { border-radius: 12px !important; box-shadow: 0 8px 30px rgba(0,0,0,0.15) !important; }
        .popup-title { font-weight: 700; font-size: 0.92rem; color: var(--azul-dark); margin-bottom: 0.3rem; }
        .popup-meta { font-size: 0.76rem; color: var(--text-mid); margin-bottom: 0.5rem; }
        .popup-tags { display: flex; gap: 0.3rem; flex-wrap: wrap; }
        .popup-tag { background: rgba(27,79,114,0.1); color: var(--azul); padding: 0.15rem 0.5rem; border-radius: 20px; font-size: 0.65rem; font-weight: 700; }
        .popup-tag-event { background: rgba(192,57,43,0.1); color: var(--terra); }
    </style>
</head>
<body>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<!-- ========== NAVBAR ========== -->
<nav class="ayto-nav" id="aytoNav">
    <a href="/" class="nav-brand">
        <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Rurales">
        <div class="nav-brand-text">
            <span class="nav-brand-title">Rutas Rurales · Ayuntamientos</span>
            <span class="nav-brand-sub">rutasrurales.io</span>
        </div>
    </a>
    <div class="nav-cta">
        <a href="#mapa" class="nav-link"><i class="fas fa-map-marked-alt"></i> Ver el mapa</a>
        <a href="#planes" class="nav-link"><i class="fas fa-tag"></i> Precios</a>
        <a href="#inscribir" class="btn-nav-inscribir">
            <i class="fas fa-landmark"></i> Inscribe tu municipio
        </a>
    </div>
</nav>

<!-- ========== HERO ========== -->
<section class="hero">
    <div class="hero-badge"><i class="fas fa-map-marked-alt"></i> Turismo Rural · España · Las 4 Estaciones</div>
    <h1 class="hero-title">Rutas Rurales<br><em>para Ayuntamientos</em></h1>
    <p class="hero-subtitle">El escaparate digital que tu municipio necesita para atraer turistas durante todo el año</p>

    <div class="hero-hook-box">
        <p class="hero-hook-eyebrow">🔔 Atención alcalde/concejal de turismo</p>
        <h2 class="hero-hook-headline">
            <span class="missing">¡Tu municipio no está en el mapa!</span><br>
            Los turistas te buscan y no te encuentran
        </h2>
        <p class="hero-hook-desc">
            Miles de viajeros buscan cada semana destinos rurales auténticos para el puente, el fin de semana
            o las vacaciones. <strong>No solo en verano.</strong> Tus monumentos, rutas de senderismo, fiestas patronales
            y eventos culturales merecen estar visibles. Inscríbete hoy desde <strong>19€</strong>.
        </p>
        <div style="margin-bottom:1.2rem;">
            <span class="hero-from-price"><i class="fas fa-tag"></i> Desde <strong>19€</strong> IVA incluido</span>
        </div>
        <div class="hero-buttons">
            <a href="#inscribir" class="btn-primary">
                <i class="fas fa-magic"></i> Inscribir mi municipio — desde 19€
            </a>
            <a href="https://wa.me/34605249696?text=Hola%2C%20soy%20del%20Ayuntamiento%20y%20quiero%20inscribir%20mi%20municipio%20en%20Rutas%20Rurales" target="_blank" class="btn-secondary">
                <i class="fab fa-whatsapp"></i> Hablamos por WhatsApp
            </a>
        </div>
    </div>
    <div class="hero-scroll-hint">Descubre el mapa <i class="fas fa-chevron-down"></i></div>
</section>

<!-- ========== STRIP: 4 ESTACIONES ========== -->
<div class="seasons-strip">
    <div class="seasons-inner">
        <div class="season-item"><span class="season-icon">🌸</span><span class="season-label">Primavera</span></div>
        <div class="season-item"><span class="season-icon">☀️</span><span class="season-label">Verano</span></div>
        <div class="season-item"><span class="season-icon">🍂</span><span class="season-label">Otoño</span></div>
        <div class="season-item"><span class="season-icon">❄️</span><span class="season-label">Invierno</span></div>
        <div class="season-item"><span class="season-icon">📍</span><span class="season-label">Todo el año</span></div>
    </div>
</div>

<!-- ========== STATS ========== -->
<div class="stats-bar">
    <div class="stat-item"><div class="stat-number">320+</div><div class="stat-label">Municipios activos</div></div>
    <div class="stat-item"><div class="stat-number">12.000+</div><div class="stat-label">Visitas/mes</div></div>
    <div class="stat-item"><div class="stat-number">850+</div><div class="stat-label">Eventos publicados</div></div>
    <div class="stat-item"><div class="stat-number">4</div><div class="stat-label">Estaciones cubiertas</div></div>
    <div class="stat-item"><div class="stat-number">19€</div><div class="stat-label">Desde · IVA incl.</div></div>
</div>

<!-- ========== MAPA ========== -->
<section class="map-section" id="mapa">
    <div class="section-header">
        <p class="section-eyebrow"><i class="fas fa-map-marked-alt"></i> Mapa interactivo <i class="fas fa-map-marked-alt"></i></p>
        <h2 class="section-title">Tu municipio merece estar aquí.<br>¿Está el tuyo?</h2>
        <p class="section-desc">Explora los municipios inscritos. Lugares de interés, eventos culturales y rutas organizadas por estación. ¿No ves el tuyo? Únete hoy.</p>
    </div>
    <div class="map-wrapper">
        <div id="ayto-map"></div>
        <aside class="map-sidebar">
            <h3 class="map-sidebar-title">🏛 Municipios en el mapa</h3>
            <div id="municipiosList"></div>
            <div class="map-cta-banner">
                <p>¿No aparece tu municipio?<br><strong>¡Miles de turistas te buscan!</strong><br>Alta en menos de 24h.</p>
                <a href="#inscribir" class="btn-map-cta"><i class="fas fa-plus-circle"></i> Inscribir mi municipio — desde 19€</a>
            </div>
        </aside>
    </div>
</section>

<!-- ========== VALUE PROPS ========== -->
<section class="value-section" id="ventajas">
    <div class="section-header">
        <p class="section-eyebrow"><i class="fas fa-star"></i> Por qué inscribirse <i class="fas fa-star"></i></p>
        <h2 class="section-title">Turismo durante todo el año,<br>no solo en verano</h2>
        <p class="section-desc">Buscamos Ayuntamientos comprometidos con el turismo rural sostenible en <strong>todas las estaciones</strong>. Primavera, verano, otoño e invierno tienen su encanto.</p>
    </div>
    <div class="value-grid">
        <div class="value-card">
            <div class="value-icon"><i class="fas fa-map-pin"></i></div>
            <h3>Lugares de interés visibles</h3>
            <p>Monumentos, ermitas, miradores, fuentes, rutas de senderismo. Todo lo que hace único a tu municipio aparece en el mapa con ficha completa.</p>
        </div>
        <div class="value-card">
            <div class="value-icon"><i class="fas fa-calendar-alt"></i></div>
            <h3>Eventos culturales actualizados</h3>
            <p>Fiestas patronales, ferias medievales, mercados artesanales, conciertos, teatro. Publica hasta 5 eventos con el Plan Cultural y amplía cuando quieras.</p>
        </div>
        <div class="value-card">
            <div class="value-icon"><i class="fas fa-route"></i></div>
            <h3>Rutas temáticas por temporada</h3>
            <p>Formamos parte activa en la creación de rutas temáticas: ruta de la naturaleza en primavera, festivales en verano, vendimia en otoño, navidades en invierno.</p>
        </div>
        <div class="value-card">
            <div class="value-icon"><i class="fas fa-search"></i></div>
            <h3>SEO local potente</h3>
            <p>Aprovecha el posicionamiento de rutasrurales.io. Cuando alguien busca "qué hacer en [tu provincia]", tu municipio aparece entre los resultados.</p>
        </div>
        <div class="value-card">
            <div class="value-icon"><i class="fas fa-globe-europe"></i></div>
            <h3>Turistas nacionales e internacionales</h3>
            <p>La plataforma está disponible en 5 idiomas. Alcanza a turistas españoles, europeos y del resto del mundo que buscan turismo rural auténtico.</p>
        </div>
        <div class="value-card">
            <div class="value-icon"><i class="fas fa-handshake"></i></div>
            <h3>Gestión sencilla para la administración</h3>
            <p>Formulario simple, pago seguro, alta en menos de 24h. Sin complicaciones técnicas para el personal municipal. Actualizamos por ti si lo necesitas.</p>
        </div>
    </div>
</section>

<!-- ========== PRICING ========== -->
<section class="pricing-section" id="planes">
    <div class="section-header">
        <p class="section-eyebrow"><i class="fas fa-tag"></i> Planes y precios <i class="fas fa-tag"></i></p>
        <h2 class="section-title">Simple, justo y sin sorpresas</h2>
        <p class="section-desc">El Plan Cultural incluye todo lo del Plan Básico. Sin ilimitados que nadie usa — pagas lo que necesitas, cuando lo necesitas.</p>
    </div>
    <div class="pricing-grid-wrap">

        <!-- PLAN BÁSICO -->
        <div class="plan-card">
            <p class="plan-label">Plan</p>
            <h3 class="plan-name">Básico</h3>
            <p class="plan-desc-short">Tus lugares de interés en el mapa. El punto de partida perfecto.</p>
            <div class="plan-price-block">
                <div class="plan-price-main"><sup>€</sup>19</div>
                <div class="plan-price-sub">IVA incluido · pago único</div>
                <span class="plan-price-renewal">🔄 Renovación anual 9,99€</span>
            </div>
            <ul class="plan-features">
                <li><i class="fas fa-check"></i> <strong>5 lugares de interés</strong> en el mapa</li>
                <li><i class="fas fa-check"></i> Ficha completa (fotos, descripción, horarios)</li>
                <li><i class="fas fa-check"></i> Enlace a la web del Ayuntamiento</li>
                <li><i class="fas fa-check"></i> SEO local incluido</li>
                <li><i class="fas fa-check"></i> Visible en 5 idiomas</li>
                <li><i class="fas fa-check"></i> Apareces en las rutas temáticas</li>
                <li class="dim"><i class="fas fa-times"></i> Eventos culturales <span class="pill">Plan Cultural</span></li>
            </ul>
            <button class="btn-plan btn-plan-secondary" onclick="seleccionarPlan('basico')">
                <i class="fas fa-map-pin"></i> Elegir Plan Básico — 19€
            </button>
        </div>

        <!-- PLAN CULTURAL -->
        <div class="plan-card featured">
            <p class="plan-label">Plan</p>
            <h3 class="plan-name">Cultural</h3>
            <p class="plan-desc-short">Lugares de interés + eventos culturales. Todo lo del Básico incluido.</p>
            <div class="plan-price-block">
                <div class="plan-price-main"><sup>€</sup>39</div>
                <div class="plan-price-sub">IVA incluido · pago único</div>
                <span class="plan-includes-basic">✓ Incluye el Plan Básico completo</span><br>
                <span class="plan-price-renewal">🔄 Renovación anual eventos 19,99€</span>
            </div>
            <ul class="plan-features">
                <li><i class="fas fa-check-circle"></i> <strong>5 lugares de interés</strong> en el mapa</li>
                <li><i class="fas fa-check-circle"></i> <strong>5 eventos culturales</strong> publicados</li>
                <li><i class="fas fa-check-circle"></i> Fichas completas para lugares y eventos</li>
                <li><i class="fas fa-check-circle"></i> SEO local + eventos en Google</li>
                <li><i class="fas fa-check-circle"></i> Visible en 5 idiomas</li>
                <li><i class="fas fa-check-circle"></i> Destacado en rutas temáticas estacionales</li>
                <li><i class="fas fa-check-circle"></i> Evento extra: <strong>5€/evento adicional</strong></li>
            </ul>
            <button class="btn-plan btn-plan-primary" onclick="seleccionarPlan('cultural')">
                <i class="fas fa-calendar-star"></i> Elegir Plan Cultural — 39€
            </button>
        </div>
    </div>

    <!-- EXTRAS -->
    <div class="pricing-extras">
        <div class="extra-card">
            <div class="extra-price"><sup>€</sup>5</div>
            <div class="extra-label">por evento adicional<br><span style="color:rgba(255,255,255,0.4);">Más allá de los 5 incluidos en el Plan Cultural</span></div>
        </div>
        <div class="extra-card">
            <div class="extra-price"><sup>€</sup>19,99</div>
            <div class="extra-label">renovación anual de eventos<br><span style="color:rgba(255,255,255,0.4);">Para mantener tus eventos actualizados cada año</span></div>
        </div>
    </div>
    <p class="pricing-note">Todos los precios incluyen IVA. Factura emitida automáticamente. Pago seguro vía Stripe.</p>
</section>

<!-- ========== FORMULARIO INSCRIPCIÓN INLINE ========== -->
<section class="cta-section" id="inscribir">
    <div class="cta-container">
        <p style="text-align:center;">
            <span class="cta-eyebrow"><i class="fas fa-landmark"></i> Inscripción de municipio <i class="fas fa-landmark"></i></span>
        </p>
        <h2 class="cta-headline"><span class="accent">¡Tu municipio merece estar en el mapa!</span><br>Elige tu plan y empieza hoy</h2>
        <p class="cta-sub">
            Selecciona el plan que mejor se ajusta a tu municipio, rellena el formulario y te llevamos directamente al pago.
            Alta en menos de <strong>24 horas</strong>. Sin complicaciones técnicas.
        </p>

        <!-- SELECTOR DE PLAN -->
        <div class="plan-selector" id="planSelector">
            <label class="plan-option" id="optionBasico">
                <input type="radio" name="plan" value="basico" id="radioBasico">
                <div class="plan-checkmark"><i class="fas fa-check"></i></div>
                <div class="plan-option-name">Plan Básico</div>
                <div class="plan-option-price"><sup>€</sup>19</div>
                <div class="plan-option-desc">5 lugares de interés · IVA incluido</div>
            </label>
            <label class="plan-option" id="optionCultural">
                <input type="radio" name="plan" value="cultural" id="radioCultural">
                <div class="plan-option-badge">⭐ MÁS COMPLETO</div>
                <div class="plan-checkmark"><i class="fas fa-check"></i></div>
                <div class="plan-option-name">Plan Cultural</div>
                <div class="plan-option-price"><sup>€</sup>39</div>
                <div class="plan-option-desc">5 lugares + 5 eventos · IVA incluido</div>
            </label>
        </div>

        <!-- FORMULARIO -->
        <div class="inscripcion-form-wrapper">
            <p class="form-title">🏛 Datos del municipio</p>
            <form id="formInscripcion" onsubmit="pagarConStripe(event)">
                <input type="hidden" name="plan" id="planSeleccionado" value="basico">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nombre del municipio *</label>
                        <input type="text" name="municipio" required placeholder="Ej: Medinaceli" autocomplete="organization">
                    </div>
                    <div class="form-group">
                        <label>Provincia *</label>
                        <input type="text" name="provincia" required placeholder="Ej: Soria" autocomplete="address-level2">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nombre del responsable *</label>
                        <input type="text" name="contacto" required placeholder="Ej: Juan López (Alcalde)" autocomplete="name">
                    </div>
                    <div class="form-group">
                        <label>Cargo</label>
                        <input type="text" name="cargo" placeholder="Ej: Concejal de Turismo">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Email del Ayuntamiento *</label>
                        <input type="email" name="email" required placeholder="turismo@ayto-municipio.es" autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label>Teléfono *</label>
                        <input type="tel" name="telefono" required placeholder="+34 XXX XXX XXX" autocomplete="tel">
                    </div>
                </div>
                <div class="form-group">
                    <label>Web del Ayuntamiento (si tienes)</label>
                    <input type="text" name="web" placeholder="www.ayto-municipio.es">
                </div>
                <div class="form-group">
                    <label>Cuéntanos brevemente qué ofrece tu municipio</label>
                    <textarea name="descripcion" rows="2" placeholder="Monumentos, rutas, fiestas, qué destaca de tu pueblo..."></textarea>
                </div>

                <!-- Resumen dinámico del plan -->
                <div class="form-plan-summary">
                    <div>
                        <div class="summary-label">Plan seleccionado</div>
                        <div class="summary-plan-name" id="summaryPlanName">Plan Básico — 5 lugares de interés</div>
                        <div class="summary-note" id="summaryPlanNote">IVA incluido · pago único · renovación anual 9,99€</div>
                    </div>
                    <div style="text-align:right;">
                        <div class="summary-price" id="summaryPrice">19€</div>
                    </div>
                </div>

                <button type="submit" class="btn-pagar" id="btnPagar">
                    <i class="fas fa-lock"></i>
                    Pagar con tarjeta
                    <span class="price-pill" id="btnPriceLabel">19€ IVA incl.</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="form-trust">
                <div class="trust-item"><i class="fas fa-lock"></i> Pago seguro Stripe</div>
                <div class="trust-item"><i class="fas fa-shield-alt"></i> RGPD</div>
                <div class="trust-item"><i class="fas fa-undo"></i> Garantía 14 días</div>
                <div class="trust-item"><i class="fas fa-receipt"></i> Factura automática</div>
            </div>
        </div>

        <!-- ALTERNATIVA -->
        <div class="cta-alternative">
            <p class="cta-alt-text">¿Prefieren que lo tramitemos nosotros? Contacta directamente.</p>
            <div class="cta-alt-links">
                <a href="https://wa.me/34605249696?text=Hola%2C%20soy%20del%20Ayuntamiento%20y%20quiero%20inscribir%20mi%20municipio%20en%20Rutas%20Rurales" target="_blank" class="btn-alt btn-alt-whatsapp">
                    <i class="fab fa-whatsapp"></i> WhatsApp · +34 605 249 696
                </a>
                <a href="mailto:olgamarin@rutasrurales.io?subject=Inscripci%C3%B3n%20municipio%20-%20Ayuntamiento" class="btn-alt btn-alt-email">
                    <i class="fas fa-envelope"></i> olgamarin@rutasrurales.io
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ========== FAQ ========== -->
<section class="faq-section" id="faq">
    <div class="section-header">
        <p class="section-eyebrow"><i class="fas fa-question-circle"></i> FAQ <i class="fas fa-question-circle"></i></p>
        <h2 class="section-title">Preguntas frecuentes</h2>
    </div>
    <div class="faq-list">
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿El Plan Cultural incluye el Plan Básico? <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer"><div class="faq-answer-inner">
                Sí, completamente. El Plan Cultural incluye todo lo del Plan Básico (5 lugares de interés) más 5 eventos culturales. No hace falta contratar los dos por separado — con el Cultural tienes todo en uno.
            </div></div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿Qué pasa cuando terminan los 5 eventos del Plan Cultural? <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer"><div class="faq-answer-inner">
                Puedes añadir eventos adicionales a 5€ cada uno. Al año siguiente, la renovación para mantener y actualizar los eventos cuesta 19,99€. Los lugares de interés del Plan Básico son permanentes sin cuota adicional una vez inscritos.
            </div></div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿En qué idiomas aparece nuestro municipio? <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer"><div class="faq-answer-inner">
                La plataforma está disponible en español, inglés, francés, alemán y chino. Tus fichas se traducen automáticamente, lo que significa que también llegas a turistas europeos e internacionales.
            </div></div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿Necesitamos conocimientos técnicos para inscribirse? <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer"><div class="faq-answer-inner">
                No. El proceso es completamente guiado: rellenáis el formulario de esta página, pagáis con tarjeta de crédito de forma segura a través de Stripe, y nosotros nos encargamos de publicar vuestro municipio en menos de 24 horas. Si preferís, podemos hacerlo nosotros directamente por el mismo precio.
            </div></div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿Cómo funciona la renovación anual de eventos? <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer"><div class="faq-answer-inner">
                Cada año os enviaremos un aviso para actualizar vuestros eventos (fiestas patronales, mercados, eventos culturales del nuevo año). La renovación cuesta 19,99€ e incluye hasta 5 eventos actualizados. Los lugares de interés permanecen visibles indefinidamente sin coste adicional.
            </div></div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿Por qué es importante estar en el mapa también en invierno o primavera? <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer"><div class="faq-answer-inner">
                Porque el turismo rural ya no es solo de verano. El turista de interior busca activamente destinos para puentes de noviembre, Navidades, Semana Santa o escapadas de otoño. Los municipios que tienen presencia digital durante todo el año captan significativamente más visitantes que los que solo se activan en verano.
            </div></div>
        </div>
    </div>
</section>

<!-- ========== FOOTER ========== -->
<footer class="ayto-footer">
    <div class="footer-logo">
        <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Rurales">
        <span>Rutas Rurales · rutasrurales.io</span>
    </div>
    <div class="footer-links">
        <a href="/aviso-legal.html">Aviso Legal</a>
        <a href="/politica-cookies.html">Privacidad</a>
        <a href="mailto:olgamarin@rutasrurales.io">olgamarin@rutasrurales.io</a>
        <a href="tel:+34605249696">+34 605 249 696</a>
        <a href="/">← Volver a Rutas Rurales</a>
    </div>
    <p class="footer-copyright">&copy; 2026 rutasrurales.io · Todos los derechos reservados</p>
</footer>

<!-- ========== FLOATING CTA ========== -->
<div class="floating-cta" id="floatingCta">
    <div class="floating-pulse"></div>
    <button class="floating-cta-btn" onclick="document.getElementById('inscribir').scrollIntoView({behavior:'smooth'})">
        <i class="fas fa-landmark"></i> ¡Inscribe tu municipio!
    </button>
</div>

<!-- ========== SCRIPTS ========== -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// ================================================================
// DATOS DE MUESTRA — municipios
// ================================================================
const municipiosData = [
    { id:1,  nombre:"Medinaceli",        lat:41.175, lng:-2.432, prov:"Soria",      lugares:["Arco romano","Colegiata","Plaza Mayor"], eventos:["Feria Medieval","Semana Santa"], desc:"La ciudad de los tres horizontes. Arco romano, judería y naturaleza." },
    { id:2,  nombre:"Albarracín",        lat:40.411, lng:-1.440, prov:"Teruel",     lugares:["Murallas medievales","Catedral","Barrio árabe"], eventos:["Mercado Medieval","Festival Teatro"], desc:"El pueblo más bonito de España. Arquitectura mudéjar declarada Patrimonio." },
    { id:3,  nombre:"Sigüenza",          lat:41.068, lng:-2.638, prov:"Guadalajara",lugares:["Castillo-Parador","Catedral románica","Plaza Mayor"], eventos:["Feria Renacentista","Conciertos Catedral"], desc:"Ciudad medieval con parador en el castillo. Catedral románica impresionante." },
    { id:4,  nombre:"Pedraza",           lat:41.124, lng:-3.808, prov:"Segovia",    lugares:["Castillo de Pedraza","Plaza Mayor","Arco de la Villa"], eventos:["Conciertos de las Velas","Mercado Castellano"], desc:"Villa medieval amurallada. Los Conciertos de las Velas, únicos en el mundo." },
    { id:5,  nombre:"Frías",             lat:42.760, lng:-3.407, prov:"Burgos",     lugares:["Castillo medieval","Puente medieval","Barrio rupestre"], eventos:["Feria Medieval","Jornadas Medievales"], desc:"El pueblo más pequeño de España con condado. Castillo y puente románico." },
    { id:6,  nombre:"Brihuega",          lat:40.762, lng:-2.870, prov:"Guadalajara",lugares:["Real Fábrica de Paños","Murallas","Jardines"], eventos:["Festival Lavanda","Mercado Artesano"], desc:"La capital de la lavanda. En junio, un mar violeta incomparable." },
    { id:7,  nombre:"Sepúlveda",         lat:41.303, lng:-3.743, prov:"Segovia",    lugares:["Hoces del Río Duratón","Iglesias románicas","Murallas"], eventos:["Senderismo Hoces","Fiestas Patronales"], desc:"Parque Natural de las Hoces del Duratón y cochinillo asado." },
    { id:8,  nombre:"Pastrana",          lat:40.427, lng:-2.917, prov:"Guadalajara",lugares:["Palacio Ducal","Iglesia Colegiata","Albarradas"], eventos:["Semana Cultural","Feria del Libro"], desc:"Villa ducal con tapices únicos en la Colegiata. Historia renacentista." },
    { id:9,  nombre:"Sos del Rey Católico",lat:42.490,lng:-1.221,prov:"Zaragoza",  lugares:["Casa natal Fernando II","Iglesia San Esteban","Lonja"], eventos:["Festival Medieval","Mercado Navideño"], desc:"Cuna del Rey Fernando el Católico. Villa medieval perfectamente conservada." },
    { id:10, nombre:"Daroca",            lat:41.118, lng:-1.419, prov:"Zaragoza",  lugares:["Murallas medievales","Colegial Santa María","Puerta Alta"], eventos:["Corpus Christi","Jornadas Medievales"], desc:"Murallas medievales de 4km. Una de las mejores conservadas de España." },
    { id:11, nombre:"Berlanga de Duero", lat:41.472, lng:-2.862, prov:"Soria",     lugares:["Castillo","Colegiata","Murallas"], eventos:["Feria Castellana","Mercado Medieval"], desc:"Conjunto monumental con castillo, colegiata y murallas renacentistas." },
    { id:12, nombre:"Potes",             lat:43.157, lng:-4.625, prov:"Cantabria", lugares:["Torre del Infantado","Picos de Europa","Liébana"], eventos:["Feria de Orujo","Semana Medieval"], desc:"Puerta a los Picos de Europa. Capital de la comarca de Liébana." },
];

// ================================================================
// MAPA LEAFLET
// ================================================================
let aytoMap;
function initMap() {
    aytoMap = L.map('ayto-map').setView([40.5, -3.0], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap', maxZoom: 18
    }).addTo(aytoMap);

    function createIcon(hasEvents) {
        const bg = hasEvents ? '#1B4F72' : '#1E8449';
        const emoji = hasEvents ? '🏛' : '📍';
        return L.divIcon({
            className: '',
            html: `<div style="background:${bg};color:#fff;width:34px;height:34px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #D4AC0D;box-shadow:0 3px 10px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;"><span style="transform:rotate(45deg);font-size:13px;">${emoji}</span></div>`,
            iconSize: [34,34], iconAnchor: [17,34], popupAnchor: [0,-34]
        });
    }

    const list = document.getElementById('municipiosList');
    list.innerHTML = '';

    municipiosData.forEach(m => {
        const hasEvents = m.eventos && m.eventos.length > 0;
        const marker = L.marker([m.lat, m.lng], { icon: createIcon(hasEvents) })
            .addTo(aytoMap)
            .bindPopup(`
                <div style="width:230px;font-family:'Montserrat',sans-serif;">
                    <div class="popup-title">🏛 ${m.nombre}</div>
                    <div class="popup-meta"><i class="fas fa-map-marker-alt" style="color:#1B4F72;"></i> ${m.prov}</div>
                    <p style="font-size:0.76rem;color:#555;line-height:1.4;margin-bottom:0.5rem;">${m.desc}</p>
                    <div class="popup-tags">
                        ${m.lugares.slice(0,2).map(l=>`<span class="popup-tag">${l}</span>`).join('')}
                        ${m.eventos.slice(0,1).map(e=>`<span class="popup-tag popup-tag-event">📅 ${e}</span>`).join('')}
                    </div>
                </div>
            `);

        const card = document.createElement('div');
        card.className = 'municipio-card';
        card.innerHTML = `
            <div class="municipio-card-name">${m.nombre}</div>
            <div class="municipio-card-meta"><i class="fas fa-map-marker-alt"></i> ${m.prov}</div>
            <div class="municipio-badge">
                <span class="badge-small">${m.lugares.length} lugares</span>
                ${hasEvents ? `<span class="badge-small badge-blue">${m.eventos.length} eventos</span>` : ''}
            </div>`;
        card.onclick = () => { aytoMap.setView([m.lat, m.lng], 13); marker.openPopup(); };
        list.appendChild(card);
    });

    setTimeout(() => aytoMap.invalidateSize(), 500);
    window.addEventListener('resize', () => aytoMap && aytoMap.invalidateSize());
}

// ================================================================
// SELECTOR DE PLAN
// ================================================================
const PLANES = {
    basico:   { nombre: 'Plan Básico — 5 lugares de interés',    nota: 'IVA incluido · pago único · renovación anual 9,99€',           precio: 19, label: '19€ IVA incl.' },
    cultural: { nombre: 'Plan Cultural — 5 lugares + 5 eventos', nota: 'IVA incluido · pago único · renovación anual eventos 19,99€', precio: 39, label: '39€ IVA incl.' }
};

function seleccionarPlan(plan) {
    // Actualizar radio
    document.getElementById('radioBasico').checked  = (plan === 'basico');
    document.getElementById('radioCultural').checked = (plan === 'cultural');
    // Actualizar clases visuales
    document.getElementById('optionBasico').classList.toggle('selected',   plan === 'basico');
    document.getElementById('optionCultural').classList.toggle('selected', plan === 'cultural');
    // Actualizar campo oculto
    document.getElementById('planSeleccionado').value = plan;
    // Actualizar resumen
    const p = PLANES[plan];
    document.getElementById('summaryPlanName').textContent = p.nombre;
    document.getElementById('summaryPlanNote').textContent = p.nota;
    document.getElementById('summaryPrice').textContent = p.precio + '€';
    document.getElementById('btnPriceLabel').textContent  = p.label;
    // Scroll al formulario si viene de los botones del pricing
    document.getElementById('inscribir').scrollIntoView({ behavior: 'smooth' });
}

// Inicializar en Básico
seleccionarPlan('basico');

// Click en las cards del selector
document.getElementById('optionBasico').addEventListener('click',   () => seleccionarPlan('basico'));
document.getElementById('optionCultural').addEventListener('click', () => seleccionarPlan('cultural'));

// ================================================================
// PAGO STRIPE — FORMULARIO DIRECTO
// ================================================================
async function pagarConStripe(e) {
    e.preventDefault();
    const form = e.target;
    const btn  = document.getElementById('btnPagar');
    const fd   = new FormData(form);

    const plan      = fd.get('plan') || 'basico';
    const municipio = (fd.get('municipio')   || '').trim();
    const provincia = (fd.get('provincia')   || '').trim();
    const contacto  = (fd.get('contacto')    || '').trim();
    const email     = (fd.get('email')       || '').trim();
    const telefono  = (fd.get('telefono')    || '').trim();

    if (!municipio || !email || !telefono || !contacto || !provincia) {
        showToast('Por favor, completa los campos obligatorios (*)', 'error'); return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showToast('Por favor, introduce un email válido', 'error'); return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Conectando con Stripe...';

    const planInfo = PLANES[plan];
    const payload = {
        plan,
        precio:   planInfo.precio,
        email,
        municipio_info: {
            municipio, provincia,
            contacto, cargo: (fd.get('cargo') || '').trim(),
            telefono, web: (fd.get('web') || '').trim(),
            descripcion: (fd.get('descripcion') || '').trim()
        },
        success_url: 'https://rutasrurales.io/ayuntamientos/gracias.php',
        cancel_url:  'https://rutasrurales.io/ayuntamientos/#inscribir'
    };

    try {
        const res  = await fetch('/ayuntamientos/api/checkout-ayuntamiento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (data.success && data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            throw new Error(data.message || 'Sin URL de pago');
        }
    } catch (err) {
        console.error('Checkout error:', err);
        showToast('Error al conectar con el servidor de pago. Contacta con olgamarin@rutasrurales.io o por WhatsApp.', 'error');
        btn.disabled = false;
        btn.innerHTML = `<i class="fas fa-lock"></i> Pagar con tarjeta <span class="price-pill">${planInfo.label}</span> <i class="fas fa-arrow-right"></i>`;
    }
}

// ================================================================
// FAQ
// ================================================================
function toggleFaq(btn) {
    const a = btn.nextElementSibling;
    const open = a.classList.contains('open');
    document.querySelectorAll('.faq-answer.open').forEach(x => x.classList.remove('open'));
    document.querySelectorAll('.faq-question.open').forEach(x => x.classList.remove('open'));
    if (!open) { a.classList.add('open'); btn.classList.add('open'); }
}

// ================================================================
// TOAST
// ================================================================
function showToast(msg, type = 'info') {
    const old = document.querySelector('.ayto-toast');
    if (old) old.remove();
    const colors = { success:'#1E8449', error:'#C0392B', info:'#1B4F72' };
    const icons  = { success:'check-circle', error:'exclamation-circle', info:'info-circle' };
    const t = document.createElement('div');
    t.className = 'ayto-toast';
    t.style.cssText = `position:fixed;bottom:5rem;left:50%;transform:translateX(-50%);background:${colors[type]};color:#fff;padding:1rem 2rem;border-radius:12px;font-family:'Montserrat',sans-serif;font-size:0.88rem;font-weight:600;z-index:999999;display:flex;align-items:center;gap:0.6rem;box-shadow:0 8px 30px rgba(0,0,0,0.2);max-width:90vw;text-align:center;`;
    t.innerHTML = `<i class="fas fa-${icons[type]}"></i> ${msg}`;
    document.body.appendChild(t);
    setTimeout(() => { if (t.parentNode) t.remove(); }, 5500);
}

// ================================================================
// NAVBAR SCROLL + FLOATING CTA
// ================================================================
window.addEventListener('scroll', () => {
    document.getElementById('aytoNav').classList.toggle('scrolled', window.scrollY > 80);
    document.getElementById('floatingCta').classList.toggle('visible', window.scrollY > 300);
});

// ================================================================
// INIT
// ================================================================
document.addEventListener('DOMContentLoaded', initMap);
</script>
</body>
</html>

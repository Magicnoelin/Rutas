<?php
/**
 * Ayuntamientos — Landing page B2B para municipios
 * Dirigida a alcaldes, concejales de turismo y técnicos municipales
 * Plan Básico: 60€ (lanzamiento, antes 120€) | Plan Cultural: 80€ (antes 160€) | Plan Territorio: 100€ (antes 200€)
 * Todos incluyen mensajería directa con turistas y traducción en 4 idiomas
 */
$page_title       = "Digitaliza tu Municipio — Rutas Rurales para Ayuntamientos";
$page_description = "Pon tu municipio en el mapa turístico digital. Planes desde 60€ con oferta de lanzamiento Mayo 2026. Mensajería directa con turistas, 5 idiomas, lugares de interés, eventos y actividades.";
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
            /* Corporate palette */
            --azul:        #1B4F72;
            --azul-dark:   #0D2137;
            --azul-mid:    #1a3a57;
            --azul-light:  #2980B9;
            --azul-bright: #3498DB;
            --dorado:      #C9A227;
            --dorado-light:#F0C040;
            --dorado-dark: #A07810;
            --terra:       #B03A2E;
            --terra-light: #E74C3C;
            --verde:       #1A7A43;
            --verde-light: #27AE60;
            --crema:       #F8F3EC;
            --crema-dark:  #EDE0CC;
            --ivory:       #FDFAF6;
            --text-dark:   #151F2B;
            --text-mid:    #3D4F5F;
            --text-light:  #7A8A9A;
            /* Plan colors */
            --plan1: #2980B9;
            --plan2: #1A7A43;
            --plan3: #8E44AD;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; overflow-x: hidden; }
        body { font-family: 'Montserrat', sans-serif; color: var(--text-dark); background: var(--ivory); overflow-x: hidden; }

        /* ============================================
           URGENCY BAR
        ============================================ */
        .urgency-bar {
            background: linear-gradient(90deg, #8E44AD, #C9A227, #B03A2E);
            background-size: 300% 100%;
            animation: gradientShift 4s ease infinite;
            padding: 0.55rem 1rem; text-align: center;
            font-size: 0.78rem; font-weight: 800; color: #fff;
            letter-spacing: 0.05em; position: relative; z-index: 10001;
        }
        .urgency-bar span { background: rgba(0,0,0,0.25); padding: 0.15rem 0.7rem; border-radius: 20px; margin: 0 0.3rem; }
        @keyframes gradientShift { 0%,100%{background-position:0% 50%} 50%{background-position:100% 50%} }

        /* ============================================
           NAVBAR
        ============================================ */
        .ayto-nav {
            position: fixed; top: 32px; left: 0; right: 0; z-index: 9999;
            background: rgba(13,33,55,0.96); backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(201,162,39,0.3);
            padding: 0.75rem 2rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;
            transition: all 0.3s;
        }
        .ayto-nav.scrolled { top: 0; padding: 0.5rem 2rem; }
        .nav-brand { display: flex; align-items: center; gap: 0.8rem; text-decoration: none; }
        .nav-brand img { height: 38px; }
        .nav-brand-text { display: flex; flex-direction: column; line-height: 1.1; }
        .nav-brand-title { font-family: 'Playfair Display', serif; font-size: 0.95rem; font-weight: 700; color: var(--dorado-light); }
        .nav-brand-sub { font-size: 0.58rem; font-weight: 600; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.15em; }
        .nav-cta { display: flex; align-items: center; gap: 1rem; }
        .nav-link { color: rgba(255,255,255,0.65); text-decoration: none; font-size: 0.78rem; font-weight: 600; transition: color 0.2s; }
        .nav-link:hover { color: var(--dorado-light); }
        .btn-nav-inscribir {
            background: linear-gradient(135deg, var(--dorado), var(--dorado-dark));
            color: var(--azul-dark);
            padding: 0.5rem 1.4rem; border-radius: 25px;
            font-size: 0.78rem; font-weight: 800; text-decoration: none;
            transition: all 0.2s; display: flex; align-items: center; gap: 0.4rem; white-space: nowrap;
            box-shadow: 0 3px 14px rgba(201,162,39,0.5);
        }
        .btn-nav-inscribir:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(201,162,39,0.6); }

        /* ============================================
           HERO
        ============================================ */
        .hero {
            min-height: 100vh;
            background:
                linear-gradient(155deg,
                    rgba(13,33,55,0.95) 0%,
                    rgba(27,79,114,0.88) 40%,
                    rgba(26,122,67,0.78) 80%,
                    rgba(142,68,173,0.6) 100%),
                url('https://images.unsplash.com/photo-1566438480900-0609be27a4be?w=1800&q=80') center/cover no-repeat;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center; padding: 9rem 2rem 5rem; position: relative; overflow: hidden;
        }
        .hero::after {
            content: ''; position: absolute; bottom: 0; left: 0; right: 0;
            height: 140px; background: linear-gradient(to top, var(--ivory), transparent);
        }
        /* Animated background elements */
        .hero-particles { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
        .particle {
            position: absolute; border-radius: 50%;
            background: rgba(201,162,39,0.15); animation: floatParticle linear infinite;
        }
        @keyframes floatParticle {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 0.5; }
            100% { transform: translateY(-100px) rotate(720deg); opacity: 0; }
        }

        .hero-official-badge {
            display: inline-flex; align-items: center; gap: 0.6rem;
            background: rgba(201,162,39,0.18); border: 1.5px solid rgba(201,162,39,0.55);
            color: var(--dorado-light); padding: 0.5rem 1.4rem; border-radius: 30px;
            font-size: 0.7rem; font-weight: 800; letter-spacing: 0.15em; text-transform: uppercase;
            margin-bottom: 1.8rem; animation: fadeInDown 0.6s ease; position: relative; z-index: 2;
        }
        .hero-official-badge .shield { font-size: 0.9rem; }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.8rem, 6.5vw, 5.5rem); font-weight: 900; color: #fff;
            line-height: 1.05; margin-bottom: 1rem; animation: fadeInUp 0.7s ease;
            position: relative; z-index: 2;
        }
        .hero-title em { font-style: italic; color: var(--dorado-light); }
        .hero-title .under { 
            text-decoration: underline; text-decoration-color: rgba(201,162,39,0.4); text-underline-offset: 8px;
        }

        .hero-subtitle {
            font-size: clamp(1rem, 2.2vw, 1.3rem); color: rgba(255,255,255,0.8);
            margin-bottom: 3rem; max-width: 620px; animation: fadeInUp 0.8s ease;
            line-height: 1.6; position: relative; z-index: 2;
        }

        /* Offer countdown box */
        .hero-offer-box {
            background: rgba(201,162,39,0.12); backdrop-filter: blur(12px);
            border: 2px solid rgba(201,162,39,0.6); border-radius: 24px;
            padding: 2rem 2.5rem; max-width: 820px; margin: 0 auto 3rem;
            animation: fadeInUp 0.9s ease; position: relative; z-index: 2;
        }
        .offer-flag {
            position: absolute; top: -16px; left: 50%; transform: translateX(-50%);
            background: linear-gradient(135deg, var(--terra), #922B21);
            color: #fff; font-size: 0.7rem; font-weight: 900; padding: 0.4rem 1.6rem;
            border-radius: 20px; white-space: nowrap; letter-spacing: 0.08em;
            box-shadow: 0 4px 16px rgba(176,58,46,0.5);
        }
        .offer-label { font-size: 0.7rem; font-weight: 800; letter-spacing: 0.2em; color: var(--dorado-light); text-transform: uppercase; margin-bottom: 0.8rem; }
        .offer-headline { font-family: 'Playfair Display', serif; font-size: clamp(1.6rem, 3.2vw, 2.6rem); font-weight: 900; color: #fff; line-height: 1.2; margin-bottom: 0.8rem; }
        .offer-headline .highlight { color: var(--dorado-light); }
        .offer-desc { font-size: 0.9rem; color: rgba(255,255,255,0.75); line-height: 1.65; margin-bottom: 1.8rem; }
        
        /* Plans mini preview in hero */
        .hero-plans-mini { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 1.8rem; }
        .hero-plan-mini {
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
            border-radius: 14px; padding: 0.8rem 1.2rem; text-align: center; min-width: 150px;
            transition: all 0.2s;
        }
        .hero-plan-mini:hover { background: rgba(255,255,255,0.18); transform: translateY(-3px); }
        .hero-plan-mini .mini-name { font-size: 0.72rem; font-weight: 700; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.2rem; }
        .hero-plan-mini .mini-price-old { font-size: 0.7rem; color: rgba(255,255,255,0.4); text-decoration: line-through; margin-bottom: 0.1rem; }
        .hero-plan-mini .mini-price { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 900; color: var(--dorado-light); line-height: 1; }
        .hero-plan-mini .mini-price sup { font-size: 0.8rem; vertical-align: top; margin-top: 0.3rem; }
        .hero-plan-mini .mini-iva { font-size: 0.62rem; color: rgba(255,255,255,0.4); margin-top: 0.1rem; }
        .hero-plan-mini.plan3-mini { border-color: rgba(142,68,173,0.5); background: rgba(142,68,173,0.15); }
        .hero-plan-mini.plan3-mini .mini-price { color: #C39BD3; }

        .hero-buttons { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        .btn-primary {
            background: linear-gradient(135deg, var(--dorado), var(--dorado-dark));
            color: var(--azul-dark);
            padding: 1rem 2.2rem; border-radius: 50px; font-size: 0.95rem; font-weight: 800;
            text-decoration: none; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.6rem;
            border: none; cursor: pointer; box-shadow: 0 4px 20px rgba(201,162,39,0.5);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(201,162,39,0.65); }
        .btn-secondary {
            background: transparent; color: #fff;
            padding: 1rem 2.2rem; border-radius: 50px; font-size: 0.95rem; font-weight: 700;
            text-decoration: none; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.6rem;
            border: 2px solid rgba(255,255,255,0.35); cursor: pointer;
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.65); }
        .hero-scroll-hint {
            position: absolute; bottom: 2.5rem; left: 50%; transform: translateX(-50%);
            color: rgba(255,255,255,0.4); font-size: 0.7rem; text-align: center; animation: bounce 2.2s infinite;
            z-index: 2;
        }
        .hero-scroll-hint i { display: block; font-size: 1.2rem; margin-top: 0.3rem; }

        /* ============================================
           TRUST BAR (official logos / certifications)
        ============================================ */
        .trust-bar {
            background: var(--azul-dark); padding: 1.5rem 2rem;
            display: flex; align-items: center; justify-content: center; gap: 3rem; flex-wrap: wrap;
            border-top: 3px solid var(--dorado);
        }
        .trust-item { display: flex; align-items: center; gap: 0.6rem; color: rgba(255,255,255,0.55); font-size: 0.78rem; font-weight: 700; }
        .trust-item i { color: var(--dorado-light); font-size: 1.1rem; }

        /* ============================================
           STATS BAR
        ============================================ */
        .stats-bar {
            background: linear-gradient(90deg, var(--azul-dark), var(--azul-mid), var(--azul-dark));
            padding: 2.5rem 2rem; display: flex; justify-content: center; flex-wrap: wrap;
            border-bottom: 1px solid rgba(201,162,39,0.15);
        }
        .stat-item { text-align: center; padding: 1rem 2.5rem; border-right: 1px solid rgba(201,162,39,0.15); flex: 1; min-width: 130px; }
        .stat-item:last-child { border-right: none; }
        .stat-number { font-family: 'Playfair Display', serif; font-size: 2.2rem; font-weight: 700; color: var(--dorado-light); line-height: 1; }
        .stat-label { font-size: 0.66rem; font-weight: 700; color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 0.12em; margin-top: 0.4rem; }

        /* ============================================
           SECTION COMMONS
        ============================================ */
        .section-header { text-align: center; padding: 0 2rem 3.5rem; max-width: 800px; margin: 0 auto; }
        .section-eyebrow {
            display: inline-flex; align-items: center; gap: 0.6rem;
            font-size: 0.68rem; font-weight: 800; letter-spacing: 0.22em; text-transform: uppercase;
            color: var(--azul); margin-bottom: 1rem;
        }
        .section-eyebrow::before, .section-eyebrow::after { content: ''; display: block; width: 28px; height: 2px; background: var(--dorado); }
        .section-title { font-family: 'Playfair Display', serif; font-size: clamp(2rem, 4.5vw, 3rem); font-weight: 700; color: var(--azul-dark); line-height: 1.15; margin-bottom: 1rem; }
        .section-desc { font-size: 0.97rem; color: var(--text-mid); line-height: 1.75; }

        /* Light section eyebrow */
        .section-eyebrow-light { color: var(--dorado-light); }
        .section-eyebrow-light::before, .section-eyebrow-light::after { background: var(--dorado-light); }

        /* ============================================
           MAP SECTION
        ============================================ */
        .map-section { padding: 5rem 0 0; background: var(--crema); }
        .map-wrapper { display: grid; grid-template-columns: 1fr 380px; min-height: 580px; }
        #ayto-map { height: 100%; min-height: 580px; width: 100%; }
        .map-sidebar { background: var(--azul-dark); padding: 2rem 1.5rem; overflow-y: auto; max-height: 580px; }
        .map-sidebar-title { font-family: 'Playfair Display', serif; font-size: 1.05rem; color: var(--dorado-light); margin-bottom: 1.5rem; padding-bottom: 0.8rem; border-bottom: 1px solid rgba(201,162,39,0.2); }
        .municipio-card { background: rgba(255,255,255,0.06); border: 1px solid rgba(201,162,39,0.15); border-radius: 12px; padding: 1rem; margin-bottom: 0.8rem; cursor: pointer; transition: all 0.2s; }
        .municipio-card:hover { background: rgba(201,162,39,0.1); border-color: rgba(201,162,39,0.4); transform: translateX(4px); }
        .municipio-card-name { font-size: 0.88rem; font-weight: 700; color: #fff; margin-bottom: 0.3rem; }
        .municipio-card-meta { font-size: 0.7rem; color: rgba(255,255,255,0.5); display: flex; align-items: center; gap: 0.4rem; }
        .municipio-card-meta i { color: var(--dorado-light); }
        .municipio-badge { display: inline-flex; gap: 0.3rem; margin-top: 0.4rem; flex-wrap: wrap; }
        .badge-small { background: rgba(201,162,39,0.2); color: var(--dorado-light); font-size: 0.58rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; padding: 0.1rem 0.45rem; border-radius: 20px; }
        .badge-blue { background: rgba(41,128,185,0.2); color: #7FB3D3; }
        .badge-purple { background: rgba(142,68,173,0.2); color: #C39BD3; }
        .map-cta-banner { background: linear-gradient(135deg, #0D2137, #1B4F72); border: 1px solid rgba(201,162,39,0.3); padding: 1.5rem; border-radius: 14px; text-align: center; margin-top: 1rem; }
        .map-cta-banner p { color: rgba(255,255,255,0.85); font-size: 0.8rem; line-height: 1.5; margin-bottom: 1rem; }
        .map-cta-banner strong { color: var(--dorado-light); }
        .btn-map-cta { display: block; background: linear-gradient(135deg, var(--dorado), var(--dorado-dark)); color: var(--azul-dark); padding: 0.8rem 1rem; border-radius: 25px; font-size: 0.8rem; font-weight: 800; text-decoration: none; transition: all 0.2s; }
        .btn-map-cta:hover { transform: scale(1.03); }

        /* ============================================
           VALUE PROPS
        ============================================ */
        .value-section { padding: 5rem 2rem; background: var(--ivory); }
        .value-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 1.8rem; max-width: 1150px; margin: 0 auto; }
        .value-card {
            background: #fff; border-radius: 22px; padding: 2rem;
            border: 1px solid var(--crema-dark); transition: all 0.3s;
            position: relative; overflow: hidden;
        }
        .value-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; }
        .value-card.v1::before { background: linear-gradient(90deg, var(--plan1), #85C1E9); }
        .value-card.v2::before { background: linear-gradient(90deg, var(--dorado-dark), var(--dorado)); }
        .value-card.v3::before { background: linear-gradient(90deg, var(--verde), #82E0AA); }
        .value-card.v4::before { background: linear-gradient(90deg, var(--terra), #E98B80); }
        .value-card.v5::before { background: linear-gradient(90deg, var(--plan3), #C39BD3); }
        .value-card.v6::before { background: linear-gradient(90deg, #2C3E50, #5D6D7E); }
        .value-card.v7::before { background: linear-gradient(90deg, #16A085, #76D7C4); }
        .value-card:hover { transform: translateY(-6px); box-shadow: 0 12px 40px rgba(27,79,114,0.12); }
        .value-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; margin-bottom: 1.2rem; }
        .v1 .value-icon { background: linear-gradient(135deg, #1B4F72, #2980B9); color: #fff; }
        .v2 .value-icon { background: linear-gradient(135deg, var(--dorado-dark), var(--dorado-light)); color: var(--azul-dark); }
        .v3 .value-icon { background: linear-gradient(135deg, var(--verde), #27AE60); color: #fff; }
        .v4 .value-icon { background: linear-gradient(135deg, var(--terra), #E74C3C); color: #fff; }
        .v5 .value-icon { background: linear-gradient(135deg, #6C3483, #8E44AD); color: #fff; }
        .v6 .value-icon { background: linear-gradient(135deg, #1A252F, #2C3E50); color: var(--dorado-light); }
        .v7 .value-icon { background: linear-gradient(135deg, #0E6655, #16A085); color: #fff; }
        .value-card h3 { font-family: 'Playfair Display', serif; font-size: 1.1rem; color: var(--azul-dark); margin-bottom: 0.6rem; }
        .value-card p { font-size: 0.87rem; color: var(--text-mid); line-height: 1.7; }
        .value-highlight { display: inline-flex; align-items: center; gap: 0.4rem; background: rgba(201,162,39,0.12); border: 1px solid rgba(201,162,39,0.3); color: var(--dorado-dark); font-size: 0.7rem; font-weight: 800; padding: 0.2rem 0.7rem; border-radius: 20px; margin-top: 0.8rem; }

        /* ============================================
           MESSAGING FEATURE — destacado
        ============================================ */
        .messaging-section { padding: 4.5rem 2rem; background: linear-gradient(135deg, #0D2137 0%, #1a3a57 60%, #0a1f30 100%); }
        .messaging-container { max-width: 1050px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; }
        .messaging-text .section-eyebrow-light::before,
        .messaging-text .section-eyebrow-light::after { background: var(--dorado-light); }
        .messaging-title { font-family: 'Playfair Display', serif; font-size: clamp(1.9rem, 3.5vw, 2.8rem); font-weight: 700; color: #fff; line-height: 1.2; margin-bottom: 1rem; }
        .messaging-title .accent { color: var(--dorado-light); }
        .messaging-desc { font-size: 0.9rem; color: rgba(255,255,255,0.7); line-height: 1.75; margin-bottom: 1.5rem; }
        .messaging-features { list-style: none; display: flex; flex-direction: column; gap: 0.8rem; }
        .messaging-features li { display: flex; align-items: flex-start; gap: 0.8rem; font-size: 0.88rem; color: rgba(255,255,255,0.8); }
        .messaging-features li i { color: var(--dorado-light); flex-shrink: 0; margin-top: 0.15rem; width: 16px; }
        /* Mock chat UI */
        .chat-mock {
            background: rgba(255,255,255,0.06); border: 1px solid rgba(201,162,39,0.25);
            border-radius: 20px; padding: 1.5rem; overflow: hidden;
        }
        .chat-mock-header { display: flex; align-items: center; gap: 0.8rem; margin-bottom: 1.2rem; padding-bottom: 0.8rem; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .chat-mock-avatar { width: 36px; height: 36px; background: linear-gradient(135deg, var(--dorado-dark), var(--dorado-light)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; color: var(--azul-dark); font-weight: 800; }
        .chat-mock-name { font-size: 0.82rem; font-weight: 700; color: #fff; }
        .chat-mock-sub { font-size: 0.65rem; color: rgba(255,255,255,0.4); }
        .chat-online { display: inline-block; width: 8px; height: 8px; background: #27AE60; border-radius: 50%; margin-right: 0.3rem; animation: blink 2s infinite; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.4} }
        .chat-messages { display: flex; flex-direction: column; gap: 0.8rem; }
        .msg { max-width: 80%; padding: 0.7rem 1rem; border-radius: 14px; font-size: 0.78rem; line-height: 1.4; }
        .msg-tourist { align-self: flex-start; background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.85); border-radius: 14px 14px 14px 4px; }
        .msg-ayto { align-self: flex-end; background: linear-gradient(135deg, var(--azul), var(--azul-light)); color: #fff; border-radius: 14px 14px 4px 14px; }
        .msg-meta { font-size: 0.6rem; color: rgba(255,255,255,0.3); margin-top: 0.2rem; text-align: right; }

        /* ============================================
           TRANSLATIONS SECTION
        ============================================ */
        .lang-section { padding: 3.5rem 2rem; background: var(--crema); }
        .lang-grid { display: flex; justify-content: center; gap: 1.5rem; flex-wrap: wrap; max-width: 900px; margin: 0 auto; }
        .lang-card {
            background: #fff; border-radius: 18px; padding: 1.5rem 2rem;
            border: 2px solid var(--crema-dark); text-align: center;
            transition: all 0.2s; min-width: 140px; flex: 1;
        }
        .lang-card:hover { transform: translateY(-4px); border-color: var(--dorado); box-shadow: 0 8px 25px rgba(201,162,39,0.15); }
        .lang-flag { font-size: 2.5rem; margin-bottom: 0.6rem; display: block; }
        .lang-name { font-size: 0.88rem; font-weight: 800; color: var(--azul-dark); margin-bottom: 0.2rem; }
        .lang-sub { font-size: 0.68rem; color: var(--text-light); }
        .lang-note { text-align: center; font-size: 0.82rem; color: var(--text-mid); margin-top: 1.5rem; }
        .lang-note strong { color: var(--azul); }

        /* ============================================
           PRICING — 3 PLANES
        ============================================ */
        .pricing-section { padding: 6rem 2rem 4rem; background: linear-gradient(170deg, #0D2137 0%, #1B4F72 55%, #16344f 100%); }
        .pricing-section .section-title { color: #fff; }
        .pricing-section .section-desc { color: rgba(255,255,255,0.6); }

        /* Oferta banner */
        .offer-banner {
            max-width: 860px; margin: 0 auto 3rem;
            background: linear-gradient(135deg, rgba(176,58,46,0.25), rgba(142,68,173,0.25));
            border: 2px solid rgba(201,162,39,0.5); border-radius: 20px; padding: 1.5rem 2rem;
            text-align: center;
        }
        .offer-banner-label { font-size: 0.68rem; font-weight: 900; letter-spacing: 0.2em; text-transform: uppercase; color: var(--dorado-light); margin-bottom: 0.5rem; }
        .offer-banner-text { font-family: 'Playfair Display', serif; font-size: clamp(1.4rem, 3vw, 2rem); font-weight: 700; color: #fff; }
        .offer-banner-text .pct { font-size: clamp(2.5rem, 5vw, 4rem); color: var(--dorado-light); font-weight: 900; }
        .offer-banner-sub { font-size: 0.82rem; color: rgba(255,255,255,0.6); margin-top: 0.4rem; }

        .pricing-grid-wrap { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.8rem; max-width: 1100px; margin: 0 auto; }

        .plan-card {
            background: rgba(255,255,255,0.05); backdrop-filter: blur(12px);
            border: 2px solid rgba(255,255,255,0.1); border-radius: 26px;
            padding: 2.5rem 1.8rem; position: relative; transition: all 0.3s;
            display: flex; flex-direction: column;
        }
        .plan-card:hover { transform: translateY(-8px); box-shadow: 0 16px 50px rgba(0,0,0,0.3); }
        .plan-card.plan-basico { border-color: rgba(41,128,185,0.35); }
        .plan-card.plan-basico:hover { border-color: rgba(41,128,185,0.7); }
        .plan-card.plan-cultural { border-color: rgba(26,122,67,0.4); }
        .plan-card.plan-cultural:hover { border-color: rgba(26,122,67,0.8); }
        .plan-card.plan-territorio { border-color: rgba(201,162,39,0.5); background: rgba(201,162,39,0.07); }
        .plan-card.plan-territorio:hover { border-color: rgba(201,162,39,0.9); box-shadow: 0 16px 50px rgba(201,162,39,0.2); }
        .plan-card.plan-territorio::before {
            content: '🏆 MÁS COMPLETO';
            position: absolute; top: -16px; left: 50%; transform: translateX(-50%);
            background: linear-gradient(135deg, var(--dorado), var(--dorado-dark));
            color: var(--azul-dark); font-size: 0.62rem; font-weight: 900;
            padding: 0.35rem 1.4rem; border-radius: 20px; white-space: nowrap; letter-spacing: 0.1em;
            box-shadow: 0 4px 14px rgba(201,162,39,0.5);
        }

        .plan-color-bar { height: 5px; border-radius: 4px; margin-bottom: 1.5rem; }
        .plan-basico .plan-color-bar { background: linear-gradient(90deg, #1B4F72, #2980B9); }
        .plan-cultural .plan-color-bar { background: linear-gradient(90deg, #1A7A43, #27AE60); }
        .plan-territorio .plan-color-bar { background: linear-gradient(90deg, var(--dorado-dark), var(--dorado-light)); }

        .plan-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 1rem; }
        .plan-basico .plan-icon { background: rgba(41,128,185,0.2); color: #85C1E9; }
        .plan-cultural .plan-icon { background: rgba(26,122,67,0.2); color: #82E0AA; }
        .plan-territorio .plan-icon { background: rgba(201,162,39,0.2); color: var(--dorado-light); }

        .plan-label { font-size: 0.65rem; font-weight: 800; letter-spacing: 0.22em; text-transform: uppercase; color: rgba(255,255,255,0.45); margin-bottom: 0.4rem; }
        .plan-name { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 700; color: #fff; margin-bottom: 0.3rem; }
        .plan-tagline { font-size: 0.78rem; color: rgba(255,255,255,0.5); margin-bottom: 1.5rem; line-height: 1.4; min-height: 2.2rem; }

        .plan-price-block { margin-bottom: 1.5rem; padding: 1.2rem; background: rgba(0,0,0,0.2); border-radius: 14px; }
        .plan-price-regular { font-size: 0.75rem; color: rgba(255,255,255,0.35); text-decoration: line-through; margin-bottom: 0.1rem; }
        .plan-price-offer-label { font-size: 0.65rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: var(--terra-light); margin-bottom: 0.3rem; }
        .plan-price-main { font-family: 'Playfair Display', serif; font-size: 3.8rem; font-weight: 900; color: #fff; line-height: 1; }
        .plan-price-main sup { font-size: 1.3rem; vertical-align: top; margin-top: 0.5rem; }
        .plan-price-iva { font-size: 0.72rem; color: rgba(255,255,255,0.4); margin-top: 0.2rem; }
        .plan-price-year { font-size: 0.7rem; color: rgba(255,255,255,0.35); }

        .plan-features { list-style: none; margin-bottom: 2rem; flex: 1; }
        .plan-features li { font-size: 0.82rem; color: rgba(255,255,255,0.78); padding: 0.4rem 0; display: flex; align-items: flex-start; gap: 0.7rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .plan-features li:last-child { border-bottom: none; }
        .plan-features li .fi { flex-shrink: 0; margin-top: 0.15rem; width: 14px; }
        .plan-features li .fi-yes1 { color: #85C1E9; }
        .plan-features li .fi-yes2 { color: #82E0AA; }
        .plan-features li .fi-yes3 { color: var(--dorado-light); }
        .plan-features li .fi-no { color: rgba(255,255,255,0.2); }
        .plan-features li strong { color: #fff; }
        .plan-features li .feat-badge {
            background: rgba(201,162,39,0.2); color: var(--dorado-light);
            font-size: 0.6rem; font-weight: 700; padding: 0.1rem 0.45rem; border-radius: 10px; margin-left: auto; white-space: nowrap;
        }

        .btn-plan {
            width: 100%; padding: 1rem; border-radius: 14px;
            font-family: 'Montserrat', sans-serif; font-size: 0.9rem; font-weight: 800;
            cursor: pointer; transition: all 0.25s; display: flex; align-items: center; justify-content: center; gap: 0.6rem;
            border: none; text-decoration: none;
        }
        .btn-plan-basico { background: rgba(41,128,185,0.25); color: #85C1E9; border: 2px solid rgba(41,128,185,0.5); }
        .btn-plan-basico:hover { background: rgba(41,128,185,0.4); border-color: #2980B9; color: #fff; transform: translateY(-2px); }
        .btn-plan-cultural { background: rgba(26,122,67,0.25); color: #82E0AA; border: 2px solid rgba(26,122,67,0.5); }
        .btn-plan-cultural:hover { background: rgba(26,122,67,0.4); border-color: #1A7A43; color: #fff; transform: translateY(-2px); }
        .btn-plan-territorio { background: linear-gradient(135deg, var(--dorado), var(--dorado-dark)); color: var(--azul-dark); box-shadow: 0 6px 20px rgba(201,162,39,0.4); }
        .btn-plan-territorio:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(201,162,39,0.6); }

        /* Extras */
        .pricing-extras { max-width: 1100px; margin: 2.5rem auto 0; }
        .extras-title { text-align: center; font-size: 0.72rem; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: rgba(255,255,255,0.35); margin-bottom: 1.2rem; }
        .extras-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .extra-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(201,162,39,0.2); border-radius: 16px; padding: 1.5rem; text-align: center; transition: all 0.2s; }
        .extra-card:hover { background: rgba(201,162,39,0.08); border-color: rgba(201,162,39,0.4); }
        .extra-icon { font-size: 1.8rem; margin-bottom: 0.6rem; display: block; }
        .extra-price { font-family: 'Playfair Display', serif; font-size: 2rem; color: var(--dorado-light); font-weight: 700; line-height: 1; margin-bottom: 0.3rem; }
        .extra-price sup { font-size: 0.9rem; vertical-align: top; margin-top: 0.3rem; }
        .extra-label { font-size: 0.78rem; color: rgba(255,255,255,0.6); font-weight: 600; line-height: 1.4; }
        .extra-sub { font-size: 0.65rem; color: rgba(255,255,255,0.35); margin-top: 0.3rem; }
        .pricing-note { max-width: 860px; margin: 1.5rem auto 0; text-align: center; color: rgba(255,255,255,0.35); font-size: 0.73rem; }

        /* Messaging included badge */
        .messaging-included-badge {
            max-width: 860px; margin: 2rem auto 0;
            background: rgba(26,122,67,0.15); border: 1px solid rgba(26,122,67,0.4);
            border-radius: 14px; padding: 1rem 1.5rem;
            display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: center;
        }
        .messaging-included-badge i { color: #82E0AA; font-size: 1.1rem; }
        .messaging-included-badge span { font-size: 0.82rem; color: #82E0AA; font-weight: 700; }
        .messaging-included-badge small { font-size: 0.72rem; color: rgba(255,255,255,0.45); }

        /* ============================================
           CTA / FORMULARIO INLINE
        ============================================ */
        .cta-section { padding: 5rem 2rem; background: var(--crema); }
        .cta-container { max-width: 820px; margin: 0 auto; }
        .cta-eyebrow {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(27,79,114,0.1); border: 1px solid rgba(27,79,114,0.25);
            color: var(--azul); padding: 0.4rem 1.2rem; border-radius: 30px;
            font-size: 0.7rem; font-weight: 800; letter-spacing: 0.15em; text-transform: uppercase; margin-bottom: 1.5rem;
        }
        .cta-headline { font-family: 'Playfair Display', serif; font-size: clamp(2rem, 4.5vw, 3.2rem); font-weight: 900; color: var(--azul-dark); line-height: 1.15; margin-bottom: 0.8rem; text-align: center; }
        .cta-headline .accent { color: var(--terra); }
        .cta-sub { text-align: center; color: var(--text-mid); font-size: 0.95rem; margin-bottom: 2.5rem; line-height: 1.65; }

        /* Selector de plan — 3 opciones */
        .plan-selector { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.8rem; margin-bottom: 2rem; }
        .plan-option {
            border: 2px solid var(--crema-dark); border-radius: 16px; padding: 1.2rem 0.8rem;
            cursor: pointer; transition: all 0.2s; background: #fff; text-align: center; position: relative;
        }
        .plan-option.selected { border-color: var(--azul); background: rgba(27,79,114,0.05); box-shadow: 0 4px 16px rgba(27,79,114,0.12); }
        .plan-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
        .plan-option-name { font-weight: 800; font-size: 0.88rem; color: var(--azul-dark); margin-bottom: 0.3rem; }
        .plan-option-price-old { font-size: 0.68rem; color: var(--text-light); text-decoration: line-through; margin-bottom: 0.1rem; }
        .plan-option-price { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 700; color: var(--terra); line-height: 1; }
        .plan-option-price sup { font-size: 0.8rem; vertical-align: top; margin-top: 0.3rem; }
        .plan-option-desc { font-size: 0.65rem; color: var(--text-light); margin-top: 0.3rem; line-height: 1.3; }
        .plan-option-badge {
            position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
            background: linear-gradient(135deg, var(--dorado), var(--dorado-dark)); color: var(--azul-dark); font-size: 0.58rem; font-weight: 900;
            padding: 0.2rem 0.8rem; border-radius: 20px; white-space: nowrap;
        }
        .plan-checkmark {
            position: absolute; top: 0.6rem; right: 0.6rem;
            width: 20px; height: 20px; border-radius: 50%;
            background: var(--azul); color: #fff; display: none;
            align-items: center; justify-content: center; font-size: 0.65rem;
        }
        .plan-option.selected .plan-checkmark { display: flex; }

        /* Formulario */
        .inscripcion-form-wrapper {
            background: #fff; border: 1px solid var(--crema-dark);
            border-radius: 26px; padding: 2.5rem 2rem 2rem; box-shadow: 0 6px 30px rgba(27,79,114,0.08);
        }
        .form-title { font-family: 'Playfair Display', serif; font-size: 1.2rem; color: var(--azul-dark); text-align: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-mid); margin-bottom: 0.3rem; text-transform: uppercase; letter-spacing: 0.08em; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 0.85rem 1rem;
            border: 2px solid var(--crema-dark); border-radius: 10px;
            font-family: 'Montserrat', sans-serif; font-size: 16px; color: var(--text-dark);
            background: #fff; transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: var(--azul); box-shadow: 0 0 0 3px rgba(27,79,114,0.07);
        }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        .form-plan-summary {
            background: rgba(27,79,114,0.05); border: 1.5px solid rgba(27,79,114,0.15);
            border-radius: 14px; padding: 1rem 1.2rem; margin: 1.2rem 0;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;
        }
        .summary-label { font-size: 0.78rem; color: var(--text-mid); font-weight: 600; }
        .summary-plan-name { font-size: 0.95rem; font-weight: 800; color: var(--azul-dark); }
        .summary-price { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 700; color: var(--terra); }
        .summary-note { font-size: 0.65rem; color: var(--text-light); }

        .btn-pagar {
            width: 100%; padding: 1.1rem 2rem;
            background: linear-gradient(135deg, var(--azul), #2980B9);
            color: #fff; border: none; border-radius: 14px;
            font-family: 'Montserrat', sans-serif; font-size: 1.05rem; font-weight: 900;
            cursor: pointer; transition: all 0.25s; display: flex; align-items: center; justify-content: center; gap: 0.6rem;
            margin-top: 1.5rem; box-shadow: 0 6px 24px rgba(27,79,114,0.35);
        }
        .btn-pagar:hover { background: linear-gradient(135deg, var(--azul-dark), var(--azul)); transform: translateY(-3px); box-shadow: 0 12px 35px rgba(27,79,114,0.45); }
        .btn-pagar:disabled { opacity: 0.65; cursor: not-allowed; transform: none; }
        .btn-pagar .price-pill { background: rgba(255,255,255,0.2); padding: 0.2rem 0.7rem; border-radius: 20px; font-size: 0.9rem; }

        .form-trust { display: flex; align-items: center; justify-content: center; gap: 1.5rem; flex-wrap: wrap; margin-top: 1rem; }
        .form-trust .trust-item-sm { display: flex; align-items: center; gap: 0.35rem; color: var(--text-light); font-size: 0.7rem; font-weight: 600; }
        .form-trust .trust-item-sm i { color: var(--azul); font-size: 0.85rem; }

        .cta-alternative { margin-top: 2.5rem; text-align: center; }
        .cta-alt-text { color: var(--text-light); font-size: 0.82rem; margin-bottom: 0.8rem; }
        .cta-alt-links { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        .btn-alt { padding: 0.65rem 1.3rem; border-radius: 25px; font-size: 0.8rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s; }
        .btn-alt-whatsapp { background: rgba(37,211,102,0.1); color: #25d366; border: 1px solid rgba(37,211,102,0.4); }
        .btn-alt-whatsapp:hover { background: rgba(37,211,102,0.2); }
        .btn-alt-email { background: rgba(27,79,114,0.1); color: var(--azul); border: 1px solid rgba(27,79,114,0.3); }
        .btn-alt-email:hover { background: rgba(27,79,114,0.18); }

        /* ============================================
           FAQ
        ============================================ */
        .faq-section { padding: 5rem 2rem; background: var(--ivory); }
        .faq-list { max-width: 760px; margin: 0 auto; }
        .faq-item { border-bottom: 1px solid var(--crema-dark); overflow: hidden; }
        .faq-question { width: 100%; background: none; border: none; padding: 1.3rem 0; text-align: left; display: flex; justify-content: space-between; align-items: center; cursor: pointer; font-family: 'Montserrat', sans-serif; font-size: 0.94rem; font-weight: 700; color: var(--azul-dark); gap: 1rem; }
        .faq-question i { color: var(--dorado-dark); font-size: 0.8rem; transition: transform 0.3s; flex-shrink: 0; }
        .faq-question.open i { transform: rotate(180deg); }
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.35s ease; }
        .faq-answer.open { max-height: 350px; }
        .faq-answer-inner { padding: 0 0 1.3rem; font-size: 0.88rem; color: var(--text-mid); line-height: 1.75; }

        /* ============================================
           FOOTER
        ============================================ */
        .ayto-footer { background: var(--azul-dark); padding: 2.5rem 2rem; text-align: center; border-top: 3px solid var(--dorado); }
        .footer-logo { display: flex; align-items: center; justify-content: center; gap: 0.8rem; margin-bottom: 1rem; }
        .footer-logo img { height: 32px; filter: brightness(0) invert(1) opacity(0.7); }
        .footer-logo span { font-family: 'Playfair Display', serif; font-size: 1rem; color: rgba(255,255,255,0.5); }
        .footer-links { display: flex; justify-content: center; gap: 1.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .footer-links a { color: rgba(255,255,255,0.4); text-decoration: none; font-size: 0.76rem; transition: color 0.2s; }
        .footer-links a:hover { color: var(--dorado-light); }
        .footer-copyright { font-size: 0.7rem; color: rgba(255,255,255,0.25); }

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
            background: linear-gradient(135deg, var(--dorado), var(--dorado-dark));
            color: var(--azul-dark);
            padding: 0.9rem 1.8rem; border-radius: 50px; font-size: 0.85rem; font-weight: 900;
            display: flex; align-items: center; gap: 0.6rem;
            box-shadow: 0 8px 30px rgba(201,162,39,0.55); transition: all 0.2s; border: none; cursor: pointer;
        }
        .floating-cta-btn:hover { transform: scale(1.05); box-shadow: 0 12px 40px rgba(201,162,39,0.7); }
        .floating-pulse { position: absolute; top: -4px; right: -4px; width: 14px; height: 14px; background: var(--terra); border-radius: 50%; border: 2px solid var(--ivory); animation: pulse 2s infinite; }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 1024px) {
            .pricing-grid-wrap { grid-template-columns: 1fr 1fr; }
            .plan-card.plan-territorio { grid-column: span 2; max-width: 480px; margin: 0 auto; }
            .messaging-container { grid-template-columns: 1fr; gap: 2rem; }
        }
        @media (max-width: 900px) {
            .map-wrapper { grid-template-columns: 1fr; }
            #ayto-map { min-height: 400px; }
            .map-sidebar { max-height: 350px; }
        }
        @media (max-width: 700px) {
            .pricing-grid-wrap { grid-template-columns: 1fr; }
            .plan-card.plan-territorio { grid-column: unset; max-width: 100%; }
            .plan-selector { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .form-grid { grid-template-columns: 1fr; }
            .hero-plans-mini { flex-direction: column; align-items: center; }
            .hero-buttons { flex-direction: column; align-items: stretch; }
            .trust-bar { gap: 1.5rem; }
            .urgency-bar { font-size: 0.7rem; }
        }
        @media (max-width: 600px) {
            .ayto-nav { padding: 0.6rem 1rem; top: 28px; }
            .nav-brand-text { display: none; }
            .floating-cta { bottom: 1rem; right: 1rem; }
        }

        /* Leaflet popup */
        .leaflet-popup-content-wrapper { border-radius: 14px !important; box-shadow: 0 8px 30px rgba(0,0,0,0.15) !important; }
        .popup-title { font-weight: 700; font-size: 0.92rem; color: var(--azul-dark); margin-bottom: 0.3rem; }
        .popup-meta { font-size: 0.74rem; color: var(--text-mid); margin-bottom: 0.5rem; }
        .popup-tags { display: flex; gap: 0.3rem; flex-wrap: wrap; }
        .popup-tag { background: rgba(27,79,114,0.1); color: var(--azul); padding: 0.15rem 0.5rem; border-radius: 20px; font-size: 0.65rem; font-weight: 700; }
        .popup-tag-event { background: rgba(176,58,46,0.1); color: var(--terra); }
        .popup-tag-activ { background: rgba(142,68,173,0.1); color: #8E44AD; }
    </style>
</head>
<body>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<!-- ========== URGENCY BAR ========== -->
<div class="urgency-bar">
    <i class="fas fa-fire" style="color:#F1C40F;"></i>
    <span>🎉 OFERTA LANZAMIENTO MAYO 2026</span>
    &nbsp;50% de descuento en todos los planes &nbsp;·&nbsp; Precio especial para organismos oficiales
    <span>⏳ Plazas limitadas</span>
</div>

<!-- ========== NAVBAR ========== -->
<nav class="ayto-nav" id="aytoNav">
        <a href="/" class="nav-brand">
            <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Rurales">
            <div class="nav-brand-text">
                <span class="nav-brand-title">Rutas Rurales · Organismos Oficiales</span>
                <span class="nav-brand-sub">rutasrurales.io</span>
            </div>
        </a>
    <div class="nav-cta">
        <a href="#mapa" class="nav-link"><i class="fas fa-map-marked-alt"></i> Mapa</a>
        <a href="#planes" class="nav-link"><i class="fas fa-tag"></i> Planes</a>
        <a href="#mensajeria" class="nav-link"><i class="fas fa-comments"></i> Mensajería</a>
        <a href="#inscribir" class="btn-nav-inscribir">
            <i class="fas fa-landmark"></i> Inscribe tu municipio
        </a>
    </div>
</nav>

<!-- ========== HERO ========== -->
<section class="hero">
    <div class="hero-particles" id="particles"></div>

    <div class="hero-official-badge">
        <span class="shield">🛡️</span>
        Plataforma oficial para Ayuntamientos y Organismos Públicos
    </div>

    <h1 class="hero-title">
        Haz que tu municipio<br>
        <em>lidere el turismo rural</em><br>
        <span class="under">en la era digital</span>
    </h1>

    <p class="hero-subtitle">
        Presencia digital completa para tu Ayuntamiento: lugares de interés, eventos culturales, actividades
        y mensajería directa con turistas. Plataforma visible todo el año en toda Europa.
    </p>

    <div class="hero-offer-box">
        <div class="offer-flag">🎉 OFERTA ESPECIAL LANZAMIENTO · MAYO 2026</div>
        <p class="offer-label"><i class="fas fa-bolt"></i> Precio reducido 50% para primeros municipios</p>
        <h2 class="offer-headline">
            Tres planes adaptados a cada<br><span class="highlight">municipio y presupuesto</span>
        </h2>
        <p class="offer-desc">
            Desde lugares de interés hasta eventos culturales y actividades turísticas completas.
            <strong style="color:#fff;">Mensajería directa con turistas incluida</strong> en todos los planes.
            Alta en menos de 24h.
        </p>
        <div class="hero-plans-mini">
            <div class="hero-plan-mini">
                <div class="mini-name">Plan Básico</div>
                <div class="mini-price-old">120€/año</div>
                <div class="mini-price"><sup>€</sup>60</div>
                <div class="mini-iva">IVA incluido</div>
            </div>
            <div class="hero-plan-mini">
                <div class="mini-name">Plan Cultural</div>
                <div class="mini-price-old">160€/año</div>
                <div class="mini-price"><sup>€</sup>80</div>
                <div class="mini-iva">IVA incluido</div>
            </div>
            <div class="hero-plan-mini plan3-mini">
                <div class="mini-name">Plan Territorio</div>
                <div class="mini-price-old">200€/año</div>
                <div class="mini-price"><sup>€</sup>100</div>
                <div class="mini-iva">IVA incluido</div>
            </div>
        </div>
        <div class="hero-buttons">
            <a href="#planes" class="btn-primary">
                <i class="fas fa-rocket"></i> Ver todos los planes
            </a>
            <a href="https://wa.me/34605249696?text=Hola%2C%20soy%20del%20Ayuntamiento%20y%20quiero%20inscribir%20mi%20municipio%20en%20Rutas%20Rurales" target="_blank" class="btn-secondary">
                <i class="fab fa-whatsapp"></i> Consultar por WhatsApp
            </a>
        </div>
    </div>
    <div class="hero-scroll-hint">Descubre más <i class="fas fa-chevron-down"></i></div>
</section>

<!-- ========== TRUST BAR ========== -->
<div class="trust-bar">
    <div class="trust-item"><i class="fas fa-shield-alt"></i> Plataforma oficial verificada</div>
    <div class="trust-item"><i class="fas fa-globe"></i> Plataforma en 5 idiomas</div>
    <div class="trust-item"><i class="fas fa-comments"></i> Mensajería directa con turistas</div>
    <div class="trust-item"><i class="fas fa-lock"></i> Pago seguro Stripe</div>
    <div class="trust-item"><i class="fas fa-clock"></i> Alta en &lt;24 horas</div>
    <div class="trust-item"><i class="fas fa-receipt"></i> Factura oficial emitida</div>
</div>

<!-- ========== STATS ========== -->
<div class="stats-bar">
    <div class="stat-item"><div class="stat-number">320+</div><div class="stat-label">Municipios activos</div></div>
    <div class="stat-item"><div class="stat-number">18.000+</div><div class="stat-label">Visitas/mes</div></div>
    <div class="stat-item"><div class="stat-number">850+</div><div class="stat-label">Eventos publicados</div></div>
    <div class="stat-item"><div class="stat-number">5</div><div class="stat-label">Idiomas</div></div>
    <div class="stat-item"><div class="stat-number">4</div><div class="stat-label">Estaciones cubiertas</div></div>
    <div class="stat-item"><div class="stat-number">60€</div><div class="stat-label">Desde · oferta</div></div>
</div>

<!-- ========== MAPA ========== -->
<section class="map-section" id="mapa">
    <div class="section-header">
        <p class="section-eyebrow"><i class="fas fa-map-marked-alt"></i> Mapa interactivo <i class="fas fa-map-marked-alt"></i></p>
        <h2 class="section-title">Tu municipio merece estar aquí.<br>¿Está el tuyo?</h2>
        <p class="section-desc">Explora los municipios inscritos. Lugares de interés, eventos culturales, actividades y rutas. ¿No ves el tuyo? Únete hoy.</p>
    </div>
    <div class="map-wrapper">
        <div id="ayto-map"></div>
        <aside class="map-sidebar">
            <h3 class="map-sidebar-title">🏛 Municipios en el mapa</h3>
            <div id="municipiosList"></div>
            <div class="map-cta-banner">
                <p>¿No aparece tu municipio?<br><strong>¡Miles de turistas te buscan!</strong><br>Alta en menos de 24h con oferta lanzamiento.</p>
                <a href="#inscribir" class="btn-map-cta"><i class="fas fa-plus-circle"></i> Inscribir mi municipio — desde 60€</a>
            </div>
        </aside>
    </div>
</section>

<!-- ========== VALUE PROPS ========== -->
<section class="value-section" id="ventajas">
    <div class="section-header">
        <p class="section-eyebrow"><i class="fas fa-star"></i> Por qué inscribirse <i class="fas fa-star"></i></p>
        <h2 class="section-title">Todo lo que necesita tu municipio<br>para atraer turistas</h2>
        <p class="section-desc">Una plataforma diseñada para <strong>organismos públicos</strong> que quieren posicionarse en el turismo rural durante <strong>las cuatro estaciones</strong>.</p>
    </div>
    <div class="value-grid">
        <div class="value-card v1">
            <div class="value-icon"><i class="fas fa-map-pin"></i></div>
            <h3>Lugares de interés en el mapa</h3>
            <p>Monumentos, ermitas, miradores, fuentes, rutas de senderismo. Todo lo que hace único a tu municipio con ficha completa, fotos y horarios.</p>
            <span class="value-highlight"><i class="fas fa-check"></i> Todos los planes</span>
        </div>
        <div class="value-card v2">
            <div class="value-icon"><i class="fas fa-calendar-star"></i></div>
            <h3>Eventos culturales publicados</h3>
            <p>Fiestas patronales, ferias medievales, mercados artesanales, conciertos, teatro. Visible para turistas nacionales e internacionales.</p>
            <span class="value-highlight"><i class="fas fa-check"></i> Cultural y Territorio</span>
        </div>
        <div class="value-card v3">
            <div class="value-icon"><i class="fas fa-hiking"></i></div>
            <h3>Actividades turísticas</h3>
            <p>Senderismo, rutas en bicicleta, experiencias gastronómicas, talleres artesanales. Convierte tu municipio en un destino de experiencias.</p>
            <span class="value-highlight"><i class="fas fa-check"></i> Plan Territorio</span>
        </div>
        <div class="value-card v4">
            <div class="value-icon"><i class="fas fa-comments"></i></div>
            <h3>Mensajería directa con turistas</h3>
            <p>Los turistas pueden contactar directamente con tu Ayuntamiento a través de la plataforma. Resuelve dudas, atiende consultas y fideliza visitantes.</p>
            <span class="value-highlight"><i class="fas fa-check"></i> Incluido en todos</span>
        </div>
        <div class="value-card v5">
            <div class="value-icon"><i class="fas fa-globe-europe"></i></div>
            <h3>Plataforma en 5 idiomas</h3>
            <p>La plataforma existe en español, inglés, francés, alemán y chino. Añade páginas traducidas a 10€/página y llega a turistas europeos de toda Europa.</p>
            <span class="value-highlight"><i class="fas fa-plus"></i> Extra: 10€/página</span>
        </div>
        <div class="value-card v6">
            <div class="value-icon"><i class="fas fa-search"></i></div>
            <h3>SEO local potente</h3>
            <p>Aprovecha el posicionamiento de rutasrurales.io. Cuando alguien busca «qué hacer en [tu provincia]», tu municipio aparece entre los primeros resultados.</p>
            <span class="value-highlight"><i class="fas fa-check"></i> Incluido en todos</span>
        </div>
        <div class="value-card v7">
            <div class="value-icon"><i class="fas fa-route"></i></div>
            <h3>Rutas temáticas estacionales</h3>
            <p>Formamos parte de rutas temáticas activas todo el año: naturaleza en primavera, festivales en verano, vendimia en otoño, navidades en invierno.</p>
            <span class="value-highlight"><i class="fas fa-check"></i> Incluido en todos</span>
        </div>
        <div class="value-card v1">
            <div class="value-icon"><i class="fas fa-handshake"></i></div>
            <h3>Gestión sencilla para la administración</h3>
            <p>Formulario simple, pago seguro con factura oficial, alta en menos de 24h. Sin complicaciones técnicas. Actualizamos por ti si lo necesitas.</p>
            <span class="value-highlight"><i class="fas fa-check"></i> Soporte incluido</span>
        </div>
    </div>
</section>

<!-- ========== MENSAJERÍA DIRECTA — FEATURE HIGHLIGHT ========== -->
<section class="messaging-section" id="mensajeria">
    <div class="messaging-container">
        <div class="messaging-text">
            <p class="section-eyebrow section-eyebrow-light" style="justify-content:flex-start;"><i class="fas fa-comments"></i> Mensajería directa <i class="fas fa-comments"></i></p>
            <h2 class="messaging-title">Conecta directamente<br>con los <span class="accent">turistas que te visitan</span></h2>
            <p class="messaging-desc">
                Todos los planes incluyen mensajería directa entre el Ayuntamiento y los turistas. Responde preguntas sobre alojamiento, accesos, fiestas locales, aparcamiento... 
                y transforma cada consulta en una visita real.
            </p>
            <ul class="messaging-features">
                <li><i class="fas fa-check-circle"></i> Turistas pueden escribirte directamente desde la ficha de tu municipio</li>
                <li><i class="fas fa-check-circle"></i> Panel de gestión de mensajes para el Ayuntamiento</li>
                <li><i class="fas fa-check-circle"></i> Notificaciones por email para el responsable de turismo</li>
                <li><i class="fas fa-check-circle"></i> Historial de conversaciones guardado</li>
                <li><i class="fas fa-check-circle"></i> Disponible en los 5 idiomas de la plataforma</li>
                <li><i class="fas fa-check-circle"></i> <strong style="color:#fff;">Incluido en los 3 planes sin coste adicional</strong></li>
            </ul>
        </div>
        <div class="chat-mock">
            <div class="chat-mock-header">
                <div class="chat-mock-avatar">🏛</div>
                <div>
                    <div class="chat-mock-name">Ayto. Medinaceli</div>
                    <div class="chat-mock-sub"><span class="chat-online"></span> En línea · Concejal de Turismo</div>
                </div>
            </div>
            <div class="chat-messages">
                <div class="msg msg-tourist">
                    Hola, ¿está abierto el arco romano el domingo por la tarde? 🏛
                    <div class="msg-meta">Marie (🇫🇷 turista francesa) · 14:32</div>
                </div>
                <div class="msg msg-ayto">
                    Bonjour Marie! Sí, el recinto está abierto todos los días de 10h a 19h. ¡Te esperamos! 😊
                    <div class="msg-meta">Ayto. Medinaceli · 14:35 ✓✓</div>
                </div>
                <div class="msg msg-tourist">
                    Perfecto, ¿hay parking cerca para autocaravana? 🚐
                    <div class="msg-meta">Marie · 14:36</div>
                </div>
                <div class="msg msg-ayto">
                    Sí, tenemos área de autocaravanas gratuita a 200m. Coordenadas: 41.175, -2.432 📍
                    <div class="msg-meta">Ayto. Medinaceli · 14:38 ✓✓</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== IDIOMAS ========== -->
<section class="lang-section">
    <div class="section-header" style="padding-bottom:2rem;">
        <p class="section-eyebrow"><i class="fas fa-globe"></i> Alcance internacional <i class="fas fa-globe"></i></p>
        <h2 class="section-title">Plataforma en 5 idiomas</h2>
        <p class="section-desc">La plataforma rutasrurales.io está disponible en 5 idiomas. Añade versiones traducidas de tus páginas por solo <strong>10€/página</strong> y llega a turistas de toda Europa.</p>
    </div>
    <div class="lang-grid">
        <div class="lang-card">
            <span class="lang-flag">🇪🇸</span>
            <div class="lang-name">Español</div>
            <div class="lang-sub">Mercado nacional</div>
        </div>
        <div class="lang-card">
            <span class="lang-flag">🇬🇧</span>
            <div class="lang-name">Inglés</div>
            <div class="lang-sub">Turismo internacional</div>
        </div>
        <div class="lang-card">
            <span class="lang-flag">🇫🇷</span>
            <div class="lang-name">Francés</div>
            <div class="lang-sub">Turistas franceses y belgas</div>
        </div>
        <div class="lang-card">
            <span class="lang-flag">🇩🇪</span>
            <div class="lang-name">Alemán</div>
            <div class="lang-sub">Turismo alemán y austriaco</div>
        </div>
        <div class="lang-card">
            <span class="lang-flag">🇨🇳</span>
            <div class="lang-name">Chino</div>
            <div class="lang-sub">Turismo asiático</div>
        </div>
    </div>
    <p class="lang-note">
        <strong>Cada página traducida a cualquier idioma: 10€ (IVA incluido).</strong> Lo mismo para páginas de contenido adicional. Sin límite de páginas.
    </p>
</section>

<!-- ========== PRICING — 3 PLANES ========== -->
<section class="pricing-section" id="planes">
    <div class="section-header">
        <p class="section-eyebrow section-eyebrow-light"><i class="fas fa-tag"></i> Planes y precios <i class="fas fa-tag"></i></p>
        <h2 class="section-title">Sin sorpresas, sin letra pequeña</h2>
        <p class="section-desc">Tres planes pensados para municipios de diferente tamaño y ambición turística. Todos incluyen mensajería con turistas. Páginas extra o traducidas: 10€/página.</p>
    </div>

    <!-- OFERTA BANNER -->
    <div class="offer-banner">
        <div class="offer-banner-label"><i class="fas fa-fire"></i> Oferta de lanzamiento — Mayo 2026</div>
        <div class="offer-banner-text">
            <span class="pct">50%</span> de descuento<br>en todos los planes
        </div>
        <div class="offer-banner-sub">Precio especial para los primeros Ayuntamientos. Válido durante el período de lanzamiento.</div>
    </div>

    <div class="pricing-grid-wrap">

        <!-- PLAN BÁSICO -->
        <div class="plan-card plan-basico">
            <div class="plan-color-bar"></div>
            <div class="plan-icon"><i class="fas fa-map-pin"></i></div>
            <p class="plan-label">Plan</p>
            <h3 class="plan-name">Básico</h3>
            <p class="plan-tagline">Tus lugares de interés en el mapa. El punto de partida perfecto para municipios que comienzan su presencia digital.</p>

            <div class="plan-price-block">
                <div class="plan-price-regular">120€ / año (precio regular)</div>
                <div class="plan-price-offer-label"><i class="fas fa-bolt"></i> Oferta lanzamiento Mayo 2026</div>
                <div class="plan-price-main"><sup>€</sup>60</div>
                <div class="plan-price-iva">IVA incluido · pago anual</div>
                <div class="plan-price-year" style="margin-top:0.4rem; color:rgba(255,255,255,0.5);">Precio regular: 120€/año</div>
            </div>

            <ul class="plan-features">
                <li><i class="fas fa-check fi fi-yes1"></i> <strong>5 lugares de interés</strong> en el mapa interactivo</li>
                <li><i class="fas fa-check fi fi-yes1"></i> Ficha completa (fotos, descripción, horarios)</li>
                <li><i class="fas fa-check fi fi-yes1"></i> Enlace a la web del Ayuntamiento</li>
                <li><i class="fas fa-check fi fi-yes1"></i> SEO local incluido</li>
                <li><i class="fas fa-check fi fi-yes1"></i> Plataforma disponible en 5 idiomas</li>
                <li><i class="fas fa-check fi fi-yes1"></i> Rutas temáticas estacionales</li>
                <li><i class="fas fa-check fi fi-yes1"></i> <strong>Mensajería directa con turistas</strong></li>
                <li><i class="fas fa-times fi fi-no"></i> Eventos culturales <span style="font-size:0.72rem;color:rgba(255,255,255,0.3)">(Plan Cultural)</span></li>
                <li><i class="fas fa-times fi fi-no"></i> Actividades turísticas <span style="font-size:0.72rem;color:rgba(255,255,255,0.3)">(Plan Territorio)</span></li>
                <li><i class="fas fa-plus-circle fi fi-yes1"></i> Páginas extra o traducidas: <span class="feat-badge">10€/página</span></li>
            </ul>
            <button class="btn-plan btn-plan-basico" onclick="seleccionarPlan('basico')">
                <i class="fas fa-map-pin"></i> Elegir Plan Básico — 60€
            </button>
        </div>

        <!-- PLAN CULTURAL -->
        <div class="plan-card plan-cultural">
            <div class="plan-color-bar"></div>
            <div class="plan-icon"><i class="fas fa-calendar-alt"></i></div>
            <p class="plan-label">Plan</p>
            <h3 class="plan-name">Cultural</h3>
            <p class="plan-tagline">Lugares de interés más eventos culturales. Muestra las fiestas, ferias y celebraciones que hacen único a tu pueblo.</p>

            <div class="plan-price-block">
                <div class="plan-price-regular">160€ / año (precio regular)</div>
                <div class="plan-price-offer-label"><i class="fas fa-bolt"></i> Oferta lanzamiento Mayo 2026</div>
                <div class="plan-price-main"><sup>€</sup>80</div>
                <div class="plan-price-iva">IVA incluido · pago anual</div>
                <div class="plan-price-year" style="margin-top:0.4rem; color:rgba(255,255,255,0.5);">Precio regular: 160€/año</div>
            </div>

            <ul class="plan-features">
                <li><i class="fas fa-check fi fi-yes2"></i> <strong>5 lugares de interés</strong> en el mapa interactivo</li>
                <li><i class="fas fa-check fi fi-yes2"></i> <strong>5 eventos culturales</strong> publicados</li>
                <li><i class="fas fa-check fi fi-yes2"></i> Fichas completas para lugares y eventos</li>
                <li><i class="fas fa-check fi fi-yes2"></i> SEO local + eventos en Google</li>
                <li><i class="fas fa-check fi fi-yes2"></i> Plataforma disponible en 5 idiomas</li>
                <li><i class="fas fa-check fi fi-yes2"></i> Destacado en rutas temáticas estacionales</li>
                <li><i class="fas fa-check fi fi-yes2"></i> <strong>Mensajería directa con turistas</strong></li>
                <li><i class="fas fa-times fi fi-no"></i> Actividades turísticas <span style="font-size:0.72rem;color:rgba(255,255,255,0.3)">(Plan Territorio)</span></li>
                <li><i class="fas fa-plus-circle fi fi-yes2"></i> Páginas extra o traducidas: <span class="feat-badge">10€/página</span></li>
            </ul>
            <button class="btn-plan btn-plan-cultural" onclick="seleccionarPlan('cultural')">
                <i class="fas fa-calendar-alt"></i> Elegir Plan Cultural — 80€
            </button>
        </div>

        <!-- PLAN TERRITORIO -->
        <div class="plan-card plan-territorio">
            <div class="plan-color-bar"></div>
            <div class="plan-icon"><i class="fas fa-trophy"></i></div>
            <p class="plan-label">Plan</p>
            <h3 class="plan-name">Territorio</h3>
            <p class="plan-tagline">La presencia digital completa. Lugares, eventos y actividades. Para municipios que quieren ser un referente turístico.</p>

            <div class="plan-price-block">
                <div class="plan-price-regular">200€ / año (precio regular)</div>
                <div class="plan-price-offer-label"><i class="fas fa-bolt"></i> Oferta lanzamiento Mayo 2026</div>
                <div class="plan-price-main" style="color:var(--dorado-light);"><sup>€</sup>100</div>
                <div class="plan-price-iva">IVA incluido · pago anual</div>
                <div class="plan-price-year" style="margin-top:0.4rem; color:rgba(255,255,255,0.5);">Precio regular: 200€/año</div>
            </div>

            <ul class="plan-features">
                <li><i class="fas fa-check-circle fi fi-yes3"></i> <strong>5 lugares de interés</strong> en el mapa interactivo</li>
                <li><i class="fas fa-check-circle fi fi-yes3"></i> <strong>5 eventos culturales</strong> publicados</li>
                <li><i class="fas fa-check-circle fi fi-yes3"></i> <strong>5 actividades turísticas</strong> publicadas</li>
                <li><i class="fas fa-check-circle fi fi-yes3"></i> Fichas completas para todo el contenido</li>
                <li><i class="fas fa-check-circle fi fi-yes3"></i> SEO máximo: lugares + eventos + actividades</li>
                <li><i class="fas fa-check-circle fi fi-yes3"></i> Plataforma disponible en 5 idiomas</li>
                <li><i class="fas fa-check-circle fi fi-yes3"></i> Máxima visibilidad en rutas temáticas</li>
                <li><i class="fas fa-check-circle fi fi-yes3"></i> <strong>Mensajería directa con turistas</strong></li>
                <li><i class="fas fa-star fi fi-yes3"></i> <strong>Perfil destacado</strong> en búsquedas</li>
                <li><i class="fas fa-plus-circle fi fi-yes3"></i> Páginas extra o traducidas: <span class="feat-badge">10€/página</span></li>
            </ul>
            <button class="btn-plan btn-plan-territorio" onclick="seleccionarPlan('territorio')">
                <i class="fas fa-trophy"></i> Elegir Plan Territorio — 100€
            </button>
        </div>
    </div>

    <!-- MENSAJERÍA INCLUIDA -->
    <div class="messaging-included-badge">
        <i class="fas fa-comments"></i>
        <span>💬 Mensajería directa con turistas</span>
        <small>·</small>
        <small>Incluida en todos los planes sin coste adicional</small>
    </div>

    <!-- EXTRAS -->
    <div class="pricing-extras">
        <p class="extras-title"><i class="fas fa-plus-circle"></i> Extras disponibles para todos los planes</p>
        <div class="extras-grid">
            <div class="extra-card">
                <span class="extra-icon">📄</span>
                <div class="extra-price"><sup>€</sup>10</div>
                <div class="extra-label">por página adicional</div>
                <div class="extra-sub">Cada lugar, evento o actividad extra más allá de los incluidos</div>
            </div>
            <div class="extra-card">
                <span class="extra-icon">🇫🇷</span>
                <div class="extra-price"><sup>€</sup>10</div>
                <div class="extra-label">traducción página en francés</div>
                <div class="extra-sub">Versión traducida adicional por página</div>
            </div>
            <div class="extra-card">
                <span class="extra-icon">🇬🇧</span>
                <div class="extra-price"><sup>€</sup>10</div>
                <div class="extra-label">traducción página en inglés</div>
                <div class="extra-sub">Versión traducida adicional por página</div>
            </div>
            <div class="extra-card">
                <span class="extra-icon">🇩🇪</span>
                <div class="extra-price"><sup>€</sup>10</div>
                <div class="extra-label">traducción página en alemán</div>
                <div class="extra-sub">Versión traducida adicional por página</div>
            </div>
            <div class="extra-card">
                <span class="extra-icon">🇨🇳</span>
                <div class="extra-price"><sup>€</sup>10</div>
                <div class="extra-label">traducción página en chino</div>
                <div class="extra-sub">Versión traducida adicional por página</div>
            </div>
        </div>
    </div>
    <p class="pricing-note">Todos los precios incluyen IVA. Factura oficial emitida automáticamente. Pago seguro vía Stripe. Cada página adicional de contenido o traducción a cualquier idioma: 10€ IVA incluido.</p>
</section>

<!-- ========== FORMULARIO INSCRIPCIÓN INLINE ========== -->
<section class="cta-section" id="inscribir">
    <div class="cta-container">
        <p style="text-align:center;">
            <span class="cta-eyebrow"><i class="fas fa-landmark"></i> Inscripción oficial de municipio <i class="fas fa-landmark"></i></span>
        </p>
        <h2 class="cta-headline"><span class="accent">¡Tu municipio merece liderar!</span><br>Elige plan y empieza hoy</h2>
        <p class="cta-sub">
            Selecciona el plan, rellena el formulario y te llevamos al pago seguro.
            Alta en menos de <strong>24 horas</strong>. Oferta de lanzamiento: <strong>50% dto hasta fin de mayo 2026</strong>.
        </p>

        <!-- SELECTOR DE PLAN — 3 opciones -->
        <div class="plan-selector" id="planSelector">
            <label class="plan-option" id="optionBasico">
                <input type="radio" name="plan" value="basico" id="radioBasico">
                <div class="plan-checkmark"><i class="fas fa-check"></i></div>
                <div class="plan-option-name">Plan Básico</div>
                <div class="plan-option-price-old">120€/año</div>
                <div class="plan-option-price"><sup>€</sup>60</div>
                <div class="plan-option-desc">5 lugares de interés<br>IVA incluido</div>
            </label>
            <label class="plan-option" id="optionCultural">
                <input type="radio" name="plan" value="cultural" id="radioCultural">
                <div class="plan-checkmark"><i class="fas fa-check"></i></div>
                <div class="plan-option-name">Plan Cultural</div>
                <div class="plan-option-price-old">160€/año</div>
                <div class="plan-option-price"><sup>€</sup>80</div>
                <div class="plan-option-desc">5 lugares + 5 eventos<br>IVA incluido</div>
            </label>
            <label class="plan-option" id="optionTerritorio">
                <input type="radio" name="plan" value="territorio" id="radioTerritorio">
                <div class="plan-option-badge">🏆 MÁS COMPLETO</div>
                <div class="plan-checkmark"><i class="fas fa-check"></i></div>
                <div class="plan-option-name">Plan Territorio</div>
                <div class="plan-option-price-old">200€/año</div>
                <div class="plan-option-price"><sup>€</sup>100</div>
                <div class="plan-option-desc">5 lugares + 5 eventos + 5 actividades<br>IVA incluido</div>
            </label>
        </div>

        <!-- FORMULARIO -->
        <div class="inscripcion-form-wrapper">
            <p class="form-title">🏛 Datos del municipio y responsable</p>
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
                        <input type="text" name="cargo" placeholder="Ej: Concejal/a de Turismo">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Email oficial del Ayuntamiento *</label>
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
                    <label>¿Qué ofrece tu municipio? (breve descripción)</label>
                    <textarea name="descripcion" rows="2" placeholder="Monumentos, rutas, fiestas, qué destaca de tu pueblo..."></textarea>
                </div>

                <!-- Resumen dinámico del plan -->
                <div class="form-plan-summary">
                    <div>
                        <div class="summary-label">Plan seleccionado</div>
                        <div class="summary-plan-name" id="summaryPlanName">Plan Básico — 5 lugares de interés</div>
                        <div class="summary-note" id="summaryPlanNote">IVA incluido · precio lanzamiento Mayo 2026 (regular 120€)</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:0.7rem;color:var(--text-light);text-decoration:line-through;" id="summaryPriceOld">120€</div>
                        <div class="summary-price" id="summaryPrice">60€</div>
                    </div>
                </div>

                <button type="submit" class="btn-pagar" id="btnPagar">
                    <i class="fas fa-lock"></i>
                    Pagar con tarjeta
                    <span class="price-pill" id="btnPriceLabel">60€ IVA incl.</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="form-trust">
                <div class="trust-item-sm"><i class="fas fa-lock"></i> Pago seguro Stripe</div>
                <div class="trust-item-sm"><i class="fas fa-shield-alt"></i> RGPD compliant</div>
                <div class="trust-item-sm"><i class="fas fa-undo"></i> Garantía 14 días</div>
                <div class="trust-item-sm"><i class="fas fa-receipt"></i> Factura oficial</div>
                <div class="trust-item-sm"><i class="fas fa-clock"></i> Alta &lt;24h</div>
            </div>
        </div>

        <!-- ALTERNATIVA -->
        <div class="cta-alternative">
            <p class="cta-alt-text">¿Prefieren tramitarlo por otro canal? Contacta directamente con nosotros.</p>
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
                ¿Cuál es la diferencia entre los tres planes? <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer"><div class="faq-answer-inner">
                El <strong>Plan Básico</strong> incluye 5 lugares de interés en el mapa. El <strong>Plan Cultural</strong> añade 5 eventos culturales (fiestas, ferias, mercados...). El <strong>Plan Territorio</strong> es la propuesta completa: 5 lugares + 5 eventos + 5 actividades turísticas, más perfil destacado en búsquedas. Todos incluyen mensajería directa con turistas. Cualquier página adicional (de contenido o traducción a cualquier idioma) cuesta 10€ IVA incluido.
            </div></div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿Qué incluye la mensajería directa con turistas? <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer"><div class="faq-answer-inner">
                Los turistas pueden enviar mensajes directamente al Ayuntamiento desde la ficha del municipio en la plataforma. El responsable de turismo recibe notificaciones por email y puede responder desde un panel de gestión. El historial de conversaciones se guarda y el sistema funciona en los 5 idiomas disponibles.
            </div></div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿Qué significa la oferta de lanzamiento del 50%? <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer"><div class="faq-answer-inner">
                Durante el período de lanzamiento de mayo 2026, los precios están reducidos al 50%: Básico a 60€ (antes 120€/año), Cultural a 80€ (antes 160€/año), Territorio a 100€ (antes 200€/año). Esta oferta es para los primeros municipios que se inscriban y tiene plazas limitadas.
            </div></div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿Cómo funcionan las páginas adicionales a 10€? <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer"><div class="faq-answer-inner">
                Cada plan incluye un número determinado de páginas (lugares, eventos o actividades). Si necesitas añadir más, cada página adicional cuesta 10€ (IVA incluido). También las traducciones de páginas adicionales a francés, inglés, alemán o chino cuestan 10€ por página.
            </div></div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿En qué idiomas aparece nuestro municipio? <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer"><div class="faq-answer-inner">
                La plataforma rutasrurales.io está disponible en <strong>español, inglés, francés, alemán y chino</strong>. Si quieres que las fichas de tu municipio aparezcan traducidas, cada página en un idioma adicional tiene un coste de <strong>10€ IVA incluido</strong>. Lo mismo aplica para cualquier página de contenido extra.
            </div></div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿Necesitamos conocimientos técnicos para inscribirse? <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer"><div class="faq-answer-inner">
                No. El proceso es completamente guiado: rellenáis el formulario, pagáis con tarjeta de crédito de forma segura a través de Stripe, y nosotros nos encargamos de publicar vuestro municipio en menos de 24 horas. Si preferís, podemos hacerlo nosotros directamente. La factura se emite automáticamente.
            </div></div>
        </div>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                ¿Por qué es importante estar visible durante todo el año? <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer"><div class="faq-answer-inner">
                Porque el turismo rural ya no es solo de verano. El turista de interior busca activamente destinos para puentes, Navidades, Semana Santa o escapadas de otoño. Los municipios con presencia digital activa todo el año captan significativamente más visitantes. Nuestra plataforma está diseñada para mostrar el atractivo de cada municipio en las cuatro estaciones.
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
    <p class="footer-copyright">&copy; 2026 rutasrurales.io · Todos los derechos reservados · Plataforma oficial para Ayuntamientos y Organismos Públicos</p>
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
// PARTICLES
// ================================================================
(function() {
    const container = document.getElementById('particles');
    if (!container) return;
    for (let i = 0; i < 12; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const size = Math.random() * 60 + 20;
        p.style.cssText = `width:${size}px;height:${size}px;left:${Math.random()*100}%;animation-duration:${Math.random()*20+15}s;animation-delay:${Math.random()*10}s;`;
        container.appendChild(p);
    }
})();

// ================================================================
// DATOS DE MUESTRA — municipios
// ================================================================
const municipiosData = [
    { id:1,  nombre:"Medinaceli",        lat:41.175, lng:-2.432, prov:"Soria",      lugares:["Arco romano","Colegiata","Plaza Mayor"], eventos:["Feria Medieval","Semana Santa"], actividades:["Senderismo","Ruta del vino"], desc:"La ciudad de los tres horizontes. Arco romano, judería y naturaleza." },
    { id:2,  nombre:"Albarracín",        lat:40.411, lng:-1.440, prov:"Teruel",     lugares:["Murallas medievales","Catedral","Barrio árabe"], eventos:["Mercado Medieval","Festival Teatro"], actividades:["Vía ferrata","Rutas BTT"], desc:"El pueblo más bonito de España. Arquitectura mudéjar declarada Patrimonio." },
    { id:3,  nombre:"Sigüenza",          lat:41.068, lng:-2.638, prov:"Guadalajara",lugares:["Castillo-Parador","Catedral románica","Plaza Mayor"], eventos:["Feria Renacentista","Conciertos Catedral"], actividades:[], desc:"Ciudad medieval con parador en el castillo. Catedral románica impresionante." },
    { id:4,  nombre:"Pedraza",           lat:41.124, lng:-3.808, prov:"Segovia",    lugares:["Castillo de Pedraza","Plaza Mayor","Arco de la Villa"], eventos:["Conciertos de las Velas","Mercado Castellano"], actividades:["Turismo ecuestre"], desc:"Villa medieval amurallada. Los Conciertos de las Velas, únicos en el mundo." },
    { id:5,  nombre:"Frías",             lat:42.760, lng:-3.407, prov:"Burgos",     lugares:["Castillo medieval","Puente medieval","Barrio rupestre"], eventos:["Feria Medieval","Jornadas Medievales"], actividades:[], desc:"El pueblo más pequeño de España con condado. Castillo y puente románico." },
    { id:6,  nombre:"Brihuega",          lat:40.762, lng:-2.870, prov:"Guadalajara",lugares:["Real Fábrica de Paños","Murallas","Jardines"], eventos:["Festival Lavanda","Mercado Artesano"], actividades:["Recogida de lavanda"], desc:"La capital de la lavanda. En junio, un mar violeta incomparable." },
    { id:7,  nombre:"Sepúlveda",         lat:41.303, lng:-3.743, prov:"Segovia",    lugares:["Hoces del Río Duratón","Iglesias románicas","Murallas"], eventos:["Senderismo Hoces","Fiestas Patronales"], actividades:["Piragüismo","Senderismo guiado"], desc:"Parque Natural de las Hoces del Duratón y cochinillo asado." },
    { id:8,  nombre:"Pastrana",          lat:40.427, lng:-2.917, prov:"Guadalajara",lugares:["Palacio Ducal","Iglesia Colegiata","Albarradas"], eventos:["Semana Cultural","Feria del Libro"], actividades:[], desc:"Villa ducal con tapices únicos en la Colegiata. Historia renacentista." },
    { id:9,  nombre:"Sos del Rey Católico",lat:42.490,lng:-1.221,prov:"Zaragoza",  lugares:["Casa natal Fernando II","Iglesia San Esteban","Lonja"], eventos:["Festival Medieval","Mercado Navideño"], actividades:["Senderismo medieval"], desc:"Cuna del Rey Fernando el Católico. Villa medieval perfectamente conservada." },
    { id:10, nombre:"Daroca",            lat:41.118, lng:-1.419, prov:"Zaragoza",  lugares:["Murallas medievales","Colegial Santa María","Puerta Alta"], eventos:["Corpus Christi","Jornadas Medievales"], actividades:[], desc:"Murallas medievales de 4km. Una de las mejores conservadas de España." },
    { id:11, nombre:"Berlanga de Duero", lat:41.472, lng:-2.862, prov:"Soria",     lugares:["Castillo","Colegiata","Murallas"], eventos:["Feria Castellana","Mercado Medieval"], actividades:["Ruta castillos","Birdwatching"], desc:"Conjunto monumental con castillo, colegiata y murallas renacentistas." },
    { id:12, nombre:"Potes",             lat:43.157, lng:-4.625, prov:"Cantabria", lugares:["Torre del Infantado","Picos de Europa","Liébana"], eventos:["Feria de Orujo","Semana Medieval"], actividades:["Barranquismo","Rutas a caballo"], desc:"Puerta a los Picos de Europa. Capital de la comarca de Liébana." },
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

    function createIcon(hasActivities) {
        const bg = hasActivities ? '#8E44AD' : '#1B4F72';
        const emoji = hasActivities ? '🏆' : '🏛';
        return L.divIcon({
            className: '',
            html: `<div style="background:${bg};color:#fff;width:36px;height:36px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #C9A227;box-shadow:0 3px 10px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;"><span style="transform:rotate(45deg);font-size:13px;">${emoji}</span></div>`,
            iconSize: [36,36], iconAnchor: [18,36], popupAnchor: [0,-36]
        });
    }

    const list = document.getElementById('municipiosList');
    list.innerHTML = '';

    municipiosData.forEach(m => {
        const hasActivities = m.actividades && m.actividades.length > 0;
        const marker = L.marker([m.lat, m.lng], { icon: createIcon(hasActivities) })
            .addTo(aytoMap)
            .bindPopup(`
                <div style="width:240px;font-family:'Montserrat',sans-serif;">
                    <div class="popup-title">🏛 ${m.nombre}</div>
                    <div class="popup-meta"><i class="fas fa-map-marker-alt" style="color:#1B4F72;"></i> ${m.prov}</div>
                    <p style="font-size:0.74rem;color:#555;line-height:1.4;margin-bottom:0.5rem;">${m.desc}</p>
                    <div class="popup-tags">
                        ${m.lugares.slice(0,2).map(l=>`<span class="popup-tag">${l}</span>`).join('')}
                        ${m.eventos.slice(0,1).map(e=>`<span class="popup-tag popup-tag-event">📅 ${e}</span>`).join('')}
                        ${m.actividades.slice(0,1).map(a=>`<span class="popup-tag popup-tag-activ">🎯 ${a}</span>`).join('')}
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
                ${m.eventos.length > 0 ? `<span class="badge-small badge-blue">${m.eventos.length} eventos</span>` : ''}
                ${m.actividades.length > 0 ? `<span class="badge-small badge-purple">${m.actividades.length} activ.</span>` : ''}
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
    basico:    { nombre: 'Plan Básico — 5 lugares de interés',                        nota: 'IVA incluido · oferta lanzamiento Mayo 2026 (regular 120€)',        precio: 60,  precioOld: '120€', label: '60€ IVA incl.' },
    cultural:  { nombre: 'Plan Cultural — 5 lugares + 5 eventos',                     nota: 'IVA incluido · oferta lanzamiento Mayo 2026 (regular 160€)',        precio: 80,  precioOld: '160€', label: '80€ IVA incl.' },
    territorio:{ nombre: 'Plan Territorio — 5 lugares + 5 eventos + 5 actividades',  nota: 'IVA incluido · oferta lanzamiento Mayo 2026 (regular 200€)',        precio: 100, precioOld: '200€', label: '100€ IVA incl.' }
};

function seleccionarPlan(plan) {
    ['basico','cultural','territorio'].forEach(p => {
        const radio = document.getElementById('radio' + p.charAt(0).toUpperCase() + p.slice(1));
        const option = document.getElementById('option' + p.charAt(0).toUpperCase() + p.slice(1));
        if (radio) radio.checked = (plan === p);
        if (option) option.classList.toggle('selected', plan === p);
    });
    document.getElementById('planSeleccionado').value = plan;
    const p = PLANES[plan];
    document.getElementById('summaryPlanName').textContent = p.nombre;
    document.getElementById('summaryPlanNote').textContent = p.nota;
    document.getElementById('summaryPrice').textContent = p.precio + '€';
    document.getElementById('summaryPriceOld').textContent = p.precioOld;
    document.getElementById('btnPriceLabel').textContent = p.label;
    document.getElementById('inscribir').scrollIntoView({ behavior: 'smooth' });
}

// Inicializar en Básico
seleccionarPlan('basico');

// Click en las cards del selector
['Basico','Cultural','Territorio'].forEach(p => {
    const el = document.getElementById('option' + p);
    if (el) el.addEventListener('click', () => seleccionarPlan(p.toLowerCase()));
});

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

    const planInfo = PLANES[plan];
    if (!planInfo) {
        showToast('Plan no válido. Recarga la página e inténtalo de nuevo.', 'error'); return;
    }

    // Deshabilitar botón y mostrar spinner
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando sesión de pago...';

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

    const restoreBtn = () => {
        btn.disabled = false;
        btn.innerHTML = `<i class="fas fa-lock"></i> Pagar con tarjeta <span class="price-pill">${planInfo.label}</span> <i class="fas fa-arrow-right"></i>`;
    };

    try {
        const res = await fetch('/ayuntamientos/api/checkout-ayuntamiento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        // Leer el cuerpo como texto para diagnosticar errores PHP
        const rawText = await res.text();

        let data;
        try {
            data = JSON.parse(rawText);
        } catch(parseErr) {
            // El servidor devolvió HTML/texto de error PHP
            console.error('Respuesta no-JSON del servidor:', rawText.substring(0, 500));
            showToast('Error del servidor (respuesta no válida). Contacta con olgamarin@rutasrurales.io', 'error');
            restoreBtn();
            return;
        }

        if (data.success && data.checkout_url) {
            // Éxito → redirigir a Stripe
            btn.innerHTML = '<i class="fas fa-check"></i> Redirigiendo a Stripe...';
            showToast('¡Perfecto! Redirigiendo al pago seguro...', 'success');
            setTimeout(() => { window.location.href = data.checkout_url; }, 800);
        } else {
            const errMsg = data.message || 'Error desconocido al crear el pago';
            console.error('API error:', errMsg);
            showToast('Error: ' + errMsg + ' — Contacta con olgamarin@rutasrurales.io o WhatsApp.', 'error');
            restoreBtn();
        }

    } catch (networkErr) {
        console.error('Error de red:', networkErr);
        showToast('Error de conexión. Verifica tu internet e inténtalo de nuevo.', 'error');
        restoreBtn();
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
    const colors = { success:'#1A7A43', error:'#B03A2E', info:'#1B4F72' };
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

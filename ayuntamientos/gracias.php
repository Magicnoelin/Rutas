<?php
$municipio = htmlspecialchars($_GET['municipio'] ?? 'tu municipio');
$plan      = $_GET['plan'] ?? 'basico';
$planLabel = ($plan === 'cultural') ? 'Plan Cultural' : 'Plan Básico';
$planDesc  = ($plan === 'cultural') ? '5 lugares de interés + 5 eventos culturales' : '5 lugares de interés en el mapa';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Inscripción recibida! — Rutas Rurales Ayuntamientos</title>
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --azul:      #1B4F72;
            --azul-dark: #0E2D45;
            --ocre:      #D4AC0D;
            --verde:     #1E8449;
            --terra:     #C0392B;
            --crema:     #FDF6EC;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--azul-dark);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 2rem;
        }
        .card {
            background: var(--crema);
            border-radius: 28px;
            max-width: 620px; width: 100%;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,0.4);
            animation: popIn 0.5s cubic-bezier(0.34,1.56,0.64,1);
        }
        @keyframes popIn {
            from { opacity:0; transform:scale(0.85) translateY(30px); }
            to   { opacity:1; transform:scale(1) translateY(0); }
        }
        .card-header {
            background: linear-gradient(135deg, var(--azul-dark), var(--azul));
            padding: 3rem 2.5rem 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .card-header::before {
            content: '🏛';
            position: absolute; font-size: 16rem;
            opacity: 0.04; top: -3rem; left: 50%;
            transform: translateX(-50%); line-height: 1;
            pointer-events: none;
        }
        .success-badge {
            width: 80px; height: 80px;
            background: var(--verde);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem; margin: 0 auto 1.5rem;
            box-shadow: 0 8px 25px rgba(30,132,73,0.4);
            animation: bounceIn 0.6s 0.3s both;
        }
        @keyframes bounceIn {
            0%   { transform: scale(0); }
            60%  { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            font-weight: 900; color: #fff;
            line-height: 1.2; margin-bottom: 0.7rem;
        }
        .card-title .highlight { color: #F1C40F; }
        .card-subtitle { font-size: 0.95rem; color: rgba(255,255,255,0.72); line-height: 1.6; }

        .card-body { padding: 2.5rem 2.5rem 2rem; }

        .plan-badge {
            background: rgba(27,79,114,0.1); border: 1px solid rgba(27,79,114,0.25);
            border-radius: 14px; padding: 1rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 2rem; gap: 1rem;
        }
        .plan-badge-info { font-size: 0.88rem; color: var(--azul); font-weight: 700; }
        .plan-badge-name { font-family: 'Playfair Display', serif; font-size: 1.1rem; color: var(--azul-dark); font-weight: 700; }
        .plan-badge-icon { font-size: 2rem; }

        .steps { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem; }
        .step {
            display: flex; align-items: flex-start; gap: 1rem;
            background: #fff; border-radius: 14px; padding: 1rem 1.2rem;
            border: 1px solid #EDE8DF;
        }
        .step-num {
            width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem; font-weight: 800;
        }
        .step-num.done  { background: var(--verde);   color: #fff; }
        .step-num.next  { background: var(--azul);    color: #fff; }
        .step-num.later { background: #E8E4DC; color: #888; }
        .step-text { flex: 1; }
        .step-title { font-size: 0.88rem; font-weight: 700; color: #1A1A1A; margin-bottom: 0.2rem; }
        .step-desc  { font-size: 0.78rem; color: #777; line-height: 1.4; }

        .seasons-reminder {
            background: linear-gradient(90deg, rgba(27,79,114,0.08), rgba(30,132,73,0.08));
            border-left: 3px solid var(--azul);
            border-radius: 0 10px 10px 0;
            padding: 0.8rem 1rem; margin-bottom: 2rem;
            font-size: 0.82rem; color: #444; line-height: 1.5;
        }
        .seasons-reminder strong { color: var(--azul-dark); }

        .action-buttons { display: flex; flex-direction: column; gap: 0.8rem; }
        .btn-action {
            padding: 0.9rem 1.5rem; border-radius: 14px;
            font-family: 'Montserrat', sans-serif; font-size: 0.88rem; font-weight: 800;
            text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 0.6rem;
            transition: all 0.2s; cursor: pointer; border: none;
        }
        .btn-primary-action { background: var(--azul); color: #fff; box-shadow: 0 4px 15px rgba(27,79,114,0.3); }
        .btn-primary-action:hover { background: var(--azul-dark); transform: translateY(-2px); }
        .btn-secondary-action { background: rgba(27,79,114,0.08); color: var(--azul); border: 2px solid rgba(27,79,114,0.2); }
        .btn-secondary-action:hover { background: rgba(27,79,114,0.15); }
        .btn-wa { background: rgba(37,211,102,0.12); color: #1a8a3f; border: 2px solid rgba(37,211,102,0.3); }
        .btn-wa:hover { background: rgba(37,211,102,0.22); }

        .footer-note {
            text-align: center; margin-top: 1.5rem;
            font-size: 0.72rem; color: #AAA;
        }
        .footer-note a { color: var(--azul); text-decoration: none; }

        /* Emojis animados */
        .confetti-row { text-align: center; font-size: 1.8rem; margin-bottom: 0.5rem; animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-6px);} }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <div class="success-badge">✓</div>
            <div class="confetti-row">🏛 🌸 ☀️ 🍂 ❄️ 🏛</div>
            <h1 class="card-title">
                <span class="highlight">¡Inscripción recibida!</span><br>
                <?= $municipio ?>
            </h1>
            <p class="card-subtitle">
                Tu pago ha sido procesado correctamente.<br>
                Publicaremos tu municipio en el mapa en menos de <strong>24 horas</strong>.
            </p>
        </div>

        <div class="card-body">

            <!-- Plan contratado -->
            <div class="plan-badge">
                <div>
                    <div class="plan-badge-info">Plan contratado</div>
                    <div class="plan-badge-name"><?= htmlspecialchars($planLabel) ?></div>
                    <div style="font-size:0.75rem;color:#777;margin-top:0.2rem;"><?= htmlspecialchars($planDesc) ?></div>
                </div>
                <div class="plan-badge-icon"><?= ($plan === 'cultural') ? '📅' : '📍' ?></div>
            </div>

            <!-- Próximos pasos -->
            <div class="steps">
                <div class="step">
                    <div class="step-num done"><i class="fas fa-check"></i></div>
                    <div class="step-text">
                        <div class="step-title">Pago confirmado ✓</div>
                        <div class="step-desc">Recibirás la factura en tu email en los próximos minutos.</div>
                    </div>
                </div>
                <div class="step">
                    <div class="step-num next">2</div>
                    <div class="step-text">
                        <div class="step-title">Te contactamos en 24h</div>
                        <div class="step-desc">Nuestro equipo se pondrá en contacto para recopilar fotos, horarios y descripción de tus lugares y eventos.</div>
                    </div>
                </div>
                <div class="step">
                    <div class="step-num later">3</div>
                    <div class="step-text">
                        <div class="step-title">Alta en el mapa</div>
                        <div class="step-desc">Tu municipio aparecerá en el mapa de rutasrurales.io, accesible a miles de turistas en 5 idiomas.</div>
                    </div>
                </div>
            </div>

            <!-- Recordatorio estaciones -->
            <div class="seasons-reminder">
                🌸☀️🍂❄️ <strong>Recuerda:</strong> No solo cubrimos el verano. Prepara ya tu contenido para
                <strong>primavera</strong> (senderismo, naturaleza), <strong>otoño</strong> (ferias, vendimias, castañas)
                e <strong>invierno</strong> (Navidades, gastronomía, turismo de interior).
                ¡El turismo rural es de todo el año!
            </div>

            <!-- Botones -->
            <div class="action-buttons">
                <a href="/" class="btn-action btn-primary-action">
                    <i class="fas fa-map-marked-alt"></i> Ver el mapa de Rutas Rurales
                </a>
                <a href="https://wa.me/34605249696?text=Hola%2C%20acabo%20de%20inscribir%20el%20municipio%20de%20<?= urlencode($municipio) ?>%20en%20el%20Plan%20<?= urlencode($planLabel) ?>" target="_blank" class="btn-action btn-wa">
                    <i class="fab fa-whatsapp"></i> Enviar información por WhatsApp
                </a>
                <a href="/ayuntamientos/" class="btn-action btn-secondary-action">
                    <i class="fas fa-arrow-left"></i> Volver a la página de Ayuntamientos
                </a>
            </div>

            <p class="footer-note">
                ¿Dudas? Escríbenos a <a href="mailto:olgamarin@rutasrurales.io">olgamarin@rutasrurales.io</a>
                o llama al <a href="tel:+34605249696">+34 605 249 696</a>
            </p>
        </div>
    </div>
</body>
</html>

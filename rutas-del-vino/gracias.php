<?php
/**
 * Página de éxito tras inscripción de bodega
 * Se muestra después del pago exitoso en Stripe
 */

$bodegaNombre = isset($_GET['bodega']) ? htmlspecialchars(urldecode($_GET['bodega'])) : 'tu bodega';
$sessionId    = isset($_GET['session_id']) ? htmlspecialchars($_GET['session_id']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Bodega inscrita! — Las Rutas del Vino</title>
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --wine:      #722F37;
            --wine-dark: #4A1820;
            --gold:      #C9A84C;
            --gold-light:#E8C97A;
            --cream:     #F5F0E8;
            --ivory:     #FDFAF5;
            --green:     #2D5016;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--ivory);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        /* Fondo decorativo */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                linear-gradient(135deg,
                    rgba(74,24,32,0.06) 0%,
                    rgba(201,168,76,0.04) 50%,
                    rgba(45,80,22,0.05) 100%);
            pointer-events: none;
        }

        .confetti-emoji {
            position: fixed;
            font-size: 2rem;
            animation: fall linear infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes fall {
            0%   { transform: translateY(-100px) rotate(0deg); opacity: 1; }
            100% { transform: translateY(110vh) rotate(720deg); opacity: 0; }
        }

        .success-card {
            background: #fff;
            border-radius: 28px;
            max-width: 620px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(114,47,55,0.15);
            position: relative;
            z-index: 1;
            animation: cardIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes cardIn {
            from { opacity: 0; transform: scale(0.8) translateY(30px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        .success-header {
            background: linear-gradient(135deg, var(--wine-dark), var(--wine));
            padding: 3rem 2rem 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .success-header::before {
            content: '🍷';
            position: absolute;
            font-size: 18rem;
            opacity: 0.04;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            line-height: 1;
        }

        .checkmark-circle {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--green), #3d7a22);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            box-shadow: 0 8px 30px rgba(45,80,22,0.4);
            animation: popIn 0.5s 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        @keyframes popIn {
            from { transform: scale(0); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }

        .success-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 900;
            color: #fff;
            margin-bottom: 0.5rem;
            position: relative;
        }

        .success-header .bodega-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: var(--gold-light);
            font-style: italic;
            margin-bottom: 0;
            position: relative;
        }

        .success-body {
            padding: 2.5rem 2rem;
        }

        .steps-list {
            list-style: none;
            margin-bottom: 2rem;
        }

        .steps-list li {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.9rem 0;
            border-bottom: 1px solid var(--cream);
        }
        .steps-list li:last-child { border-bottom: none; }

        .step-icon {
            width: 42px;
            height: 42px;
            background: var(--cream);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.1rem;
        }
        .step-icon.done {
            background: linear-gradient(135deg, var(--green), #3d7a22);
            color: white;
        }
        .step-icon.pending {
            background: linear-gradient(135deg, var(--gold), #b8952a);
            color: var(--wine-dark);
        }
        .step-icon.wait {
            background: var(--cream);
            color: var(--wine);
        }

        .step-content h4 {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--wine-dark);
            margin-bottom: 0.2rem;
        }
        .step-content p {
            font-size: 0.78rem;
            color: #7a7a7a;
            line-height: 1.4;
        }

        .info-box {
            background: var(--cream);
            border-radius: 14px;
            padding: 1.2rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        .info-box h3 {
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--wine);
            margin-bottom: 0.6rem;
        }
        .info-box p {
            font-size: 0.85rem;
            color: #4a4a4a;
            line-height: 1.5;
        }
        .info-box a {
            color: var(--wine);
            font-weight: 700;
        }

        .share-box {
            background: linear-gradient(135deg, var(--wine-dark), var(--wine));
            border-radius: 14px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .share-box p {
            color: rgba(255,255,255,0.85);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        .share-box strong { color: var(--gold-light); }
        .share-buttons {
            display: flex;
            gap: 0.8rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .share-btn {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s;
        }
        .share-btn-whatsapp { background: #25d366; color: #fff; }
        .share-btn-email    { background: var(--gold); color: var(--wine-dark); }
        .share-btn:hover { transform: translateY(-2px); opacity: 0.9; }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn-volver {
            flex: 1;
            padding: 0.9rem 1.5rem;
            background: var(--wine);
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-volver:hover { background: var(--wine-dark); transform: translateY(-2px); }
        .btn-mapa {
            flex: 1;
            padding: 0.9rem 1.5rem;
            background: var(--gold);
            color: var(--wine-dark);
            border: none;
            border-radius: 12px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-mapa:hover { background: var(--gold-light); transform: translateY(-2px); }

        /* Confetti */
        .c1  { left: 5%;   animation-duration: 3.2s; animation-delay: 0.0s; }
        .c2  { left: 15%;  animation-duration: 2.8s; animation-delay: 0.3s; }
        .c3  { left: 25%;  animation-duration: 3.5s; animation-delay: 0.1s; }
        .c4  { left: 35%;  animation-duration: 2.5s; animation-delay: 0.5s; }
        .c5  { left: 45%;  animation-duration: 3.0s; animation-delay: 0.2s; }
        .c6  { left: 55%;  animation-duration: 3.8s; animation-delay: 0.4s; }
        .c7  { left: 65%;  animation-duration: 2.7s; animation-delay: 0.1s; }
        .c8  { left: 75%;  animation-duration: 3.3s; animation-delay: 0.6s; }
        .c9  { left: 85%;  animation-duration: 2.9s; animation-delay: 0.3s; }
        .c10 { left: 92%;  animation-duration: 3.1s; animation-delay: 0.0s; }

        @media (max-width: 500px) {
            .success-header h1 { font-size: 1.7rem; }
            .success-body { padding: 1.5rem 1.2rem; }
            .action-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>

<!-- Confetti emojis -->
<span class="confetti-emoji c1">🍷</span>
<span class="confetti-emoji c2">🥂</span>
<span class="confetti-emoji c3">🍇</span>
<span class="confetti-emoji c4">✨</span>
<span class="confetti-emoji c5">🍷</span>
<span class="confetti-emoji c6">🎉</span>
<span class="confetti-emoji c7">🍾</span>
<span class="confetti-emoji c8">🍇</span>
<span class="confetti-emoji c9">✨</span>
<span class="confetti-emoji c10">🥂</span>

<div class="success-card">
    <div class="success-header">
        <div class="checkmark-circle">✓</div>
        <h1>¡Pago confirmado!</h1>
        <p class="bodega-name">
            <?php echo $bodegaNombre; ?> ya forma parte de<br>Las Rutas del Vino
        </p>
    </div>

    <div class="success-body">
        <ul class="steps-list">
            <li>
                <div class="step-icon done"><i class="fas fa-check"></i></div>
                <div class="step-content">
                    <h4>Pago recibido ✓</h4>
                    <p>10€ IVA incluido procesado correctamente. Recibirás la factura en tu email.</p>
                </div>
            </li>
            <li>
                <div class="step-icon pending"><i class="fas fa-cog"></i></div>
                <div class="step-content">
                    <h4>Procesando tu alta</h4>
                    <p>Estamos preparando la ficha de tu bodega. Alta en el mapa en <strong>24-48h</strong>.</p>
                </div>
            </li>
            <li>
                <div class="step-icon wait"><i class="fas fa-map-marked-alt"></i></div>
                <div class="step-content">
                    <h4>Pronto en el mapa</h4>
                    <p>Recibirás un email de confirmación cuando tu bodega esté publicada.</p>
                </div>
            </li>
        </ul>

        <div class="info-box">
            <h3>🤔 ¿Tienes dudas?</h3>
            <p>
                Escríbenos a <a href="mailto:olgamarin@rutasrurales.io">olgamarin@rutasrurales.io</a>
                o llama al <a href="tel:+34605249696">+34 605 249 696</a>.
                Estaremos encantadas de ayudarte.
            </p>
        </div>

        <div class="share-box">
            <p>¿Conoces otras bodegas que deberían estar en el mapa?<br>
            <strong>Cuéntaselo, también les ayudará.</strong></p>
            <div class="share-buttons">
                <?php
                $shareUrl  = 'https://rutasrurales.io/rutas-del-vino/';
                $shareText = '¡Acabo de inscribir mi bodega en Las Rutas del Vino! Si tienes una bodega, inscríbela también por solo 10€. 🍷';
                ?>
                <a href="https://wa.me/?text=<?php echo urlencode($shareText . ' ' . $shareUrl); ?>"
                   target="_blank" class="share-btn share-btn-whatsapp">
                    <i class="fab fa-whatsapp"></i> Compartir por WhatsApp
                </a>
                <a href="mailto:?subject=<?php echo urlencode('Las Rutas del Vino — Inscribe tu bodega'); ?>&body=<?php echo urlencode($shareText . "\n\n" . $shareUrl); ?>"
                   class="share-btn share-btn-email">
                    <i class="fas fa-envelope"></i> Enviar por email
                </a>
            </div>
        </div>

        <div class="action-buttons">
            <a href="/rutas-del-vino/" class="btn-volver">
                <i class="fas fa-map-marked-alt"></i>
                Ver el mapa
            </a>
            <a href="/" class="btn-mapa">
                <i class="fas fa-home"></i>
                Volver a Rutas Rurales
            </a>
        </div>
    </div>
</div>

<?php
// Registrar confirmación en log
if ($sessionId) {
    $logDir  = __DIR__ . '/logs';
    $logFile = $logDir . '/inscripciones.log';
    if (is_dir($logDir) || mkdir($logDir, 0755, true)) {
        $logEntry = date('Y-m-d H:i:s') . ' | BODEGA_PAGO_COMPLETADO'
            . ' | Bodega: ' . $bodegaNombre
            . ' | Session: ' . $sessionId
            . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>

</body>
</html>

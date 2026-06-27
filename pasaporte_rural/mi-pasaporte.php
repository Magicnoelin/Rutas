<?php
/**
 * =============================================================================
 * PASAPORTE RURAL — Vista del Turista: "Mi Pasaporte Rural"
 * =============================================================================
 * Archivo  : pasaporte_rural/mi-pasaporte.php
 * Acceso   : Turistas registrados con sesión activa
 * Función  : Muestra el pasaporte digital con QR dinámico rotativo.
 *
 * Flujo:
 *   1. El turista accede → se verifica su sesión.
 *   2. Se carga/crea su pasaporte desde pasaporte_turistas.
 *   3. Se renderiza el pasaporte con datos del turista (nombre, nivel, puntos).
 *   4. JS llama a generar_token_qr.php para obtener el primer QR.
 *   5. Cada 45 segundos JS rota el QR automáticamente.
 *   6. Se muestra historial de los últimos 5 sellos.
 * =============================================================================
 */

declare(strict_types=1);

// Cargar configuración del módulo (incluye api/config.php)
define('API_NO_HEADERS', true);
require_once __DIR__ . '/config.php';

// ── 1. SESIÓN Y AUTENTICACIÓN ─────────────────────────────────────────────────
pasaporte_iniciar_sesion();

if (empty($_SESSION['user_id'])) {
    // Redirigir al login con parámetro de vuelta
    header('Location: /login.html?redirect=/pasaporte_rural/mi-pasaporte.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// ── 2. CONEXIÓN A BD ──────────────────────────────────────────────────────────
$pdo = getDBConnection();

// ── 3. CARGAR DATOS DEL TURISTA Y SU PASAPORTE ───────────────────────────────
$stmt = $pdo->prepare(
    'SELECT pt.*,
            u.first_name, u.last_name, u.email, u.avatar_url,
            CONCAT(u.first_name, " ", u.last_name) AS nombre_completo
       FROM pasaporte_turistas pt
       JOIN users u ON u.id = pt.user_id
      WHERE pt.user_id = ?
      LIMIT 1'
);
$stmt->execute([$user_id]);
$pasaporte = $stmt->fetch();

// Si no existe pasaporte todavía, lo creamos ahora (alta automática)
if (!$pasaporte) {
    $token_fijo = bin2hex(random_bytes(32));
    $pdo->prepare(
        'INSERT INTO pasaporte_turistas
            (user_id, token_fijo, descuento_actual, puntos_totales, puntos_periodo, nivel, estado)
         VALUES (?, ?, ?, 0, 0, "Viajero", "activo")'
    )->execute([$user_id, $token_fijo, DESCUENTO_BASE]);

    // Recargar
    $stmt->execute([$user_id]);
    $pasaporte = $stmt->fetch();
}

// Datos del turista para la vista
$nombre       = esc_p($pasaporte['nombre_completo'] ?? 'Turista');
$email        = esc_p($pasaporte['email'] ?? '');
$avatar_url   = $pasaporte['avatar_url'] ?? '';
$nivel        = $pasaporte['nivel'] ?? 'Viajero';
$nivel_emoji  = NIVELES_EMOJI[$nivel] ?? '🌱';
$puntos       = (int) ($pasaporte['puntos_totales'] ?? 0);
$descuento    = (int) ($pasaporte['descuento_actual'] ?? DESCUENTO_BASE);
$total_sellos = (int) ($pasaporte['total_sellos'] ?? 0);
$estado       = $pasaporte['estado'] ?? 'activo';

// Calcular progreso hacia el siguiente descuento
$puntos_en_escalon  = $puntos % PUNTOS_POR_DESCUENTO;
$progreso_pct       = ($descuento >= DESCUENTO_MAXIMO)
                        ? 100
                        : round(($puntos_en_escalon / PUNTOS_POR_DESCUENTO) * 100);
$puntos_para_subir  = ($descuento >= DESCUENTO_MAXIMO)
                        ? 0
                        : PUNTOS_POR_DESCUENTO - $puntos_en_escalon;

// ── 4. HISTORIAL DE LOS ÚLTIMOS SELLOS ───────────────────────────────────────
$stmt_sellos = $pdo->prepare(
    'SELECT hs.created_at, hs.puntos_sumados, hs.descuento_nuevo,
            hs.puntuacion_limpieza, hs.puntuacion_civismo,
            a.name AS nombre_alojamiento
       FROM historico_sellos hs
       JOIN accommodations a ON a.id = hs.alojamiento_id
      WHERE hs.pasaporte_id = ?
      ORDER BY hs.created_at DESC
      LIMIT 5'
);
$stmt_sellos->execute([$pasaporte['id']]);
$sellos_recientes = $stmt_sellos->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <!-- noindex: página privada del turista -->
    <meta name="robots" content="noindex, nofollow">
    <title>Mi Pasaporte Rural — rutasrurales.io</title>

    <!-- Bootstrap 5 CDN -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Estilos propios del módulo -->
    <link rel="stylesheet" href="css/pasaporte.css">

    <!-- QRCode.js — librería open-source para renderizar QR en canvas/img -->
    <!-- Documentación: https://github.com/davidshimjs/qrcodejs -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"
            integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmBAUSHSQX0FslNhTDadL4zsuzycrYJ6uer9L3HRw=="
            crossorigin="anonymous"></script>
</head>
<body class="pasaporte-body">

<!-- ── CABECERA ───────────────────────────────────────────────────────────── -->
<div class="pasaporte-header">
    <span class="logo-pasaporte">🌿</span>
    <h1>Pasaporte Rural</h1>
    <p class="subtitle">by rutasrurales.io · Sella experiencias. Consigue ventajas.</p>
</div>

<div class="container" style="max-width:520px; padding-bottom: 2rem;">

    <?php if ($estado !== 'activo'): ?>
    <!-- ── AVISO PASAPORTE INACTIVO ────────────────────────────────────── -->
    <div class="alert alert-warning rounded-3 d-flex gap-2 align-items-start mb-4">
        <i class="fa-solid fa-triangle-exclamation mt-1"></i>
        <div>
            <strong>Pasaporte <?= esc_p($estado) ?></strong><br>
            <span style="font-size:.85rem;">Tu pasaporte no está activo. Contacta con soporte en
            <a href="mailto:hola@rutasrurales.io">hola@rutasrurales.io</a></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── TARJETA PRINCIPAL DEL PASAPORTE ────────────────────────────────── -->
    <div class="pasaporte-card">

        <!-- Datos del turista -->
        <div class="d-flex align-items-center gap-3 mb-4">
            <?php if ($avatar_url): ?>
                <img src="<?= esc_p($avatar_url) ?>"
                     alt="Avatar de <?= $nombre ?>"
                     class="turista-avatar">
            <?php else: ?>
                <div class="turista-avatar-placeholder">
                    <?= mb_substr(strip_tags($nombre), 0, 1) ?>
                </div>
            <?php endif; ?>

            <div class="flex-grow-1 min-width-0">
                <p class="turista-nombre"><?= $nombre ?></p>
                <p class="turista-email"><?= $email ?></p>
                <span class="nivel-badge">
                    <?= $nivel_emoji ?> <?= esc_p($nivel) ?>
                </span>
            </div>

            <!-- Descuento actual destacado -->
            <div class="text-center flex-shrink-0">
                <span class="descuento-badge"><?= $descuento ?>%</span>
                <div style="font-size:.7rem;color:#6c757d;font-weight:600;margin-top:4px;">
                    DESCUENTO
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-valor" id="js-puntos"><?= $puntos ?></span>
                <span class="stat-label">Puntos</span>
            </div>
            <div class="stat-item">
                <span class="stat-valor" id="js-sellos"><?= $total_sellos ?></span>
                <span class="stat-label">Sellos</span>
            </div>
            <div class="stat-item">
                <span class="stat-valor" id="js-descuento"><?= $descuento ?>%</span>
                <span class="stat-label">Dto. actual</span>
            </div>
        </div>

        <?php if ($descuento < DESCUENTO_MAXIMO): ?>
        <!-- Barra de progreso hacia siguiente descuento -->
        <div class="progreso-descuento">
            <div class="progreso-label">
                <span>Próximo descuento: <?= $descuento + 1 ?>%</span>
                <span><?= $puntos_para_subir ?> puntos</span>
            </div>
            <div class="progreso-bar-bg">
                <div class="progreso-bar-fill" id="js-progreso-fill"
                     style="width:<?= $progreso_pct ?>%;"></div>
            </div>
        </div>
        <?php else: ?>
        <!-- Descuento máximo alcanzado -->
        <div class="alert alert-success py-2 px-3 rounded-3 mb-3 d-flex align-items-center gap-2"
             style="font-size:.84rem;">
            <i class="fa-solid fa-trophy"></i>
            <span>¡Descuento máximo alcanzado! Eres un <strong>Embajador Rural</strong> 🏅</span>
        </div>
        <?php endif; ?>

        <!-- ── CÓDIGO QR ─────────────────────────────────────────────────── -->
        <div class="text-center">
            <div class="qr-wrapper d-inline-flex" id="qr-wrapper">

                <!-- Estado del QR (activo / cargando / error) -->
                <div class="qr-status cargando" id="qr-status">
                    <span class="dot-pulse"></span>
                    <span id="qr-status-texto">Generando código...</span>
                </div>

                <!-- Canvas donde se renderiza el QR -->
                <div id="qr-canvas-container">
                    <!-- qrcode.js inyecta aquí un <canvas> o <img> -->
                </div>

                <!-- Etiqueta -->
                <p class="qr-label">
                    <i class="fa-solid fa-shield-halved me-1"></i>
                    Código único · Válido <?= QR_TTL_SEGUNDOS ?> segundos
                </p>
            </div>

            <!-- Contador regresivo del QR -->
            <div class="qr-timer-wrap">
                <span class="qr-timer-label" id="qr-timer-label">Actualizando...</span>
                <div class="qr-progress-bar-bg">
                    <div class="qr-progress-bar-fill" id="qr-timer-bar"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── INSTRUCCIONES PARA EL PROPIETARIO ──────────────────────────────── -->
    <div class="instrucciones-card">
        <h3><i class="fa-solid fa-circle-info me-1"></i> ¿Cómo funciona?</h3>
        <ol>
            <li>Muestra este código QR al llegar al alojamiento Premium.</li>
            <li>El propietario lo escanea con su móvil.</li>
            <li>El sistema verifica tu pasaporte al instante.</li>
            <li>Al finalizar tu estancia, recibes el <strong>Sello Rural</strong> y puntos.</li>
        </ol>
    </div>

    <!-- ── HISTORIAL DE SELLOS ─────────────────────────────────────────────── -->
    <?php if (!empty($sellos_recientes)): ?>
    <div class="pasaporte-card">
        <h2 style="font-size:.95rem;font-weight:700;color:var(--p-primary);
                   border-bottom:2px solid var(--p-border);padding-bottom:.5rem;margin-bottom:1rem;">
            <i class="fa-solid fa-stamp me-2"></i>Últimos sellos
        </h2>
        <ul class="sellos-lista">
            <?php foreach ($sellos_recientes as $sello): ?>
            <li class="sello-item">
                <div class="sello-icon">🏡</div>
                <div class="sello-info">
                    <p class="sello-alojamiento">
                        <?= esc_p($sello['nombre_alojamiento']) ?>
                    </p>
                    <p class="sello-meta">
                        <?= date('d/m/Y', strtotime($sello['created_at'])) ?>
                        · Limpieza <?= str_repeat('⭐', (int)$sello['puntuacion_limpieza']) ?>
                        · Civismo <?= str_repeat('⭐', (int)$sello['puntuacion_civismo']) ?>
                    </p>
                </div>
                <span class="sello-puntos">+<?= (int)$sello['puntos_sumados'] ?> pts</span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php else: ?>
    <!-- Sin sellos aún -->
    <div class="text-center py-3" style="color:#6c757d; font-size:.85rem;">
        <span style="font-size:2rem;">🗺️</span><br>
        <p class="mt-2">Tu pasaporte aún no tiene sellos.<br>
        ¡Visita tu primer alojamiento Premium!</p>
    </div>
    <?php endif; ?>

    <!-- ── PIE ─────────────────────────────────────────────────────────────── -->
    <div class="pasaporte-footer">
        <p><strong>Pasaporte Rural by rutasrurales.io</strong></p>
        <p>Sella experiencias · Consigue ventajas · Descubre la España rural</p>
    </div>

</div><!-- /container -->

<!-- ============================================================================
     JAVASCRIPT — Rotación dinámica del QR cada 45 segundos
     ============================================================================ -->
<script>
/**
 * Pasaporte Rural — Motor QR dinámico
 * ─────────────────────────────────────
 * Responsabilidades:
 *   1. Llamar a generar_token_qr.php para obtener un token OTP.
 *   2. Renderizar el QR con qrcode.js (librería ya cargada).
 *   3. Mostrar barra de progreso y contador regresivo.
 *   4. Rotar el QR cada ROTACION_CADA segundos (45).
 *   5. Gestionar estados: cargando, activo, error.
 */

'use strict';

// ── Constantes ────────────────────────────────────────────────────────────────
// URL absoluta generada por PHP para evitar problemas con rutas relativas,
// rewrites de .htaccess o si la página se sirve desde un subdirectorio.
const ENDPOINT_URL   = '<?= PASAPORTE_URL ?>/generar_token_qr.php';
const ROTACION_CADA  = <?= QR_ROTACION_SEGUNDOS ?>;  // 45 segundos (PHP → JS)
const QR_SIZE        = 220;                          // Tamaño del QR en píxeles

// ── Referencias DOM ───────────────────────────────────────────────────────────
const elWrapper      = document.getElementById('qr-wrapper');
const elContainer    = document.getElementById('qr-canvas-container');
const elStatus       = document.getElementById('qr-status');
const elStatusTexto  = document.getElementById('qr-status-texto');
const elTimerLabel   = document.getElementById('qr-timer-label');
const elTimerBar     = document.getElementById('qr-timer-bar');

// ── Estado interno ────────────────────────────────────────────────────────────
let qrInstance       = null;   // Instancia de QRCode
let timerInterval    = null;   // setInterval del contador regresivo
let rotacionTimeout  = null;   // setTimeout de la próxima rotación
let segundosRestantes = ROTACION_CADA;

/**
 * Establecer el estado visual del QR.
 * @param {'activo'|'cargando'|'error'} estado
 * @param {string} texto  Texto descriptivo
 */
function setEstado(estado, texto) {
    elStatus.className = 'qr-status ' + estado;
    elStatusTexto.textContent = texto;
}

/**
 * Actualizar la barra de progreso y el label del contador.
 * @param {number} segundos  Segundos restantes
 * @param {number} total     Total de segundos del período
 */
function actualizarTimer(segundos, total) {
    const pct = Math.max(0, Math.min(100, (segundos / total) * 100));
    elTimerBar.style.width = pct + '%';

    // Poner color urgente cuando quedan menos de 10 segundos
    if (segundos <= 10) {
        elTimerBar.classList.add('urgente');
    } else {
        elTimerBar.classList.remove('urgente');
    }

    elTimerLabel.textContent = segundos > 0
        ? '🔄 Nuevo código en ' + segundos + ' s'
        : '🔄 Actualizando código...';
}

/**
 * Renderizar o actualizar el QR con una URL nueva.
 * Si ya existe una instancia, la actualiza; si no, la crea.
 * @param {string} url  La URL que codifica el QR
 */
function renderizarQR(url) {
    if (qrInstance) {
        // Actualizar QR existente (qrcode.js lo limpia internamente)
        qrInstance.clear();
        qrInstance.makeCode(url);
    } else {
        // Primera vez: crear instancia
        qrInstance = new QRCode(elContainer, {
            text:           url,
            width:          QR_SIZE,
            height:         QR_SIZE,
            colorDark:      '#1A2E1A',    // Color corporativo dark
            colorLight:     '#ffffff',
            correctLevel:   QRCode.CorrectLevel.H,  // Alta corrección de errores
        });
    }
}

/**
 * Iniciar el contador regresivo.
 * Llama a rotarQR() cuando llega a 0.
 * @param {number} duracion  Segundos que dura el contador
 */
function iniciarContador(duracion) {
    // Limpiar timers previos
    clearInterval(timerInterval);
    clearTimeout(rotacionTimeout);

    segundosRestantes = duracion;
    actualizarTimer(segundosRestantes, duracion);

    // Tick cada segundo
    timerInterval = setInterval(function () {
        segundosRestantes--;
        actualizarTimer(segundosRestantes, duracion);

        if (segundosRestantes <= 0) {
            clearInterval(timerInterval);
        }
    }, 1000);

    // Rotar al acabar (1 segundo antes para evitar token expirado en tránsito)
    rotacionTimeout = setTimeout(rotarQR, Math.max(1000, (duracion - 1) * 1000));
}

/**
 * Pedir un nuevo token OTP al servidor y actualizar el QR.
 * Maneja errores de red/servidor con reintentos suaves.
 */
async function rotarQR() {
    // Efecto visual de actualización
    elWrapper.classList.add('actualizando');
    setEstado('cargando', 'Actualizando código...');

    try {
        const respuesta = await fetch(ENDPOINT_URL, {
            method:       'GET',
            credentials:  'same-origin',   // Enviar cookies de sesión
            cache:        'no-store',       // Sin caché
            headers:      { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!respuesta.ok) {
            throw new Error('HTTP ' + respuesta.status);
        }

        const datos = await respuesta.json();

        if (!datos.success) {
            // Error de negocio (sesión expirada, pasaporte inactivo, etc.)
            if (datos.redirect) {
                window.location.href = datos.redirect;
                return;
            }
            throw new Error(datos.error || 'Error desconocido del servidor');
        }

        // ── Renderizar el nuevo QR ───────────────────────────────────────
        renderizarQR(datos.token_url);

        // ── Actualizar datos en pantalla ─────────────────────────────────
        // (si el servidor devuelve datos actualizados del pasaporte)
        if (datos.puntos !== undefined) {
            const elPuntos = document.getElementById('js-puntos');
            if (elPuntos) elPuntos.textContent = datos.puntos;
        }
        if (datos.descuento !== undefined) {
            const elDescuento = document.getElementById('js-descuento');
            if (elDescuento) elDescuento.textContent = datos.descuento + '%';
        }

        // ── Estado activo ───────────────────────────────────────────────
        elWrapper.classList.remove('actualizando');
        setEstado('activo', '✅ Código activo · Muéstralo al propietario');

        // Iniciar contador para la próxima rotación
        // Usamos ROTACION_CADA (45s), no expira_en (60s), para renovar con margen
        iniciarContador(ROTACION_CADA);

    } catch (error) {
        console.error('[PasaporteQR] Error:', error);
        elWrapper.classList.remove('actualizando');
        setEstado('error', '⚠️ Sin conexión — Reintentando en 10 s...');
        elTimerLabel.textContent = '⚠️ Error de conexión';
        elTimerBar.style.width   = '0%';

        // Reintentar automáticamente en 10 segundos
        rotacionTimeout = setTimeout(rotarQR, 10000);
    }
}

// ── Arranque ──────────────────────────────────────────────────────────────────
// Cargar el primer QR inmediatamente al cargar la página
document.addEventListener('DOMContentLoaded', function () {
    rotarQR();
});

// ── Visibilidad de pestaña ────────────────────────────────────────────────────
// Si el usuario vuelve a la pestaña después de tenerla oculta,
// forzar una rotación inmediata para asegurar que el QR no haya expirado.
document.addEventListener('visibilitychange', function () {
    if (!document.hidden) {
        clearTimeout(rotacionTimeout);
        clearInterval(timerInterval);
        rotarQR();
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFsgAGFKQHgMRLxkh+MXJqVBRy"
        crossorigin="anonymous"></script>

</body>
</html>

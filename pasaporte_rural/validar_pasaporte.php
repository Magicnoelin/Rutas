<?php
/**
 * =============================================================================
 * PASAPORTE RURAL — Validación del QR para Propietarios Premium
 * =============================================================================
 * Archivo  : pasaporte_rural/validar_pasaporte.php
 * Acceso   : Propietarios/gestores de alojamientos Premium (logueados)
 * URL      : /pasaporte_rural/validar_pasaporte.php?token=HASH_96_CHARS
 * Método   : GET (lectura del QR) + POST (envía el formulario a procesar_sello.php)
 *
 * Flujo completo:
 *   1. Propietario escanea el QR del turista con su móvil.
 *   2. Este script recibe el token por GET.
 *   3. Verifica que quien accede es propietario Premium (user_resources + is_premium).
 *   4. Valida el token: existe + no usado + no expirado (< 60 s).
 *   5. Si VÁLIDO → pantalla verde con datos del turista + formulario de puntuación.
 *   6. Si INVÁLIDO → pantalla roja con error específico.
 *
 * SEGURIDAD:
 *   - El token se busca por hash exacto en BD (prepared statement).
 *   - La expiración se calcula comparando UNIX timestamps en PHP (no en SQL).
 *   - El CSRF del formulario de sello se genera aquí y se valida en procesar_sello.php.
 *   - Nunca se confía en datos POST/GET para identificar al turista; todo viene de BD.
 * =============================================================================
 */

declare(strict_types=1);

define('API_NO_HEADERS', true);
require_once __DIR__ . '/config.php';

// ── 1. SESIÓN Y AUTENTICACIÓN DEL PROPIETARIO ─────────────────────────────────
pasaporte_iniciar_sesion();

if (empty($_SESSION['user_id'])) {
    // El propietario debe estar logueado para escanear
    header('Location: /login.html?redirect=/pasaporte_rural/validar_pasaporte.php?token=' . urlencode($_GET['token'] ?? ''));
    exit;
}

$propietario_user_id = (int) $_SESSION['user_id'];

// ── 2. RECOGER Y VALIDAR EL TOKEN DE LA URL ────────────────────────────────────
$token_raw = trim($_GET['token'] ?? '');

// El token siempre tiene 96 caracteres hexadecimales (48 bytes → bin2hex)
if (empty($token_raw) || !ctype_xdigit($token_raw) || strlen($token_raw) !== 96) {
    mostrar_error_pasaporte('Token con formato inválido.', 'El código QR no tiene el formato correcto. ¿Está completamente visible?');
    exit;
}

// ── 3. CONEXIÓN A BD ──────────────────────────────────────────────────────────
$pdo = getDBConnection();

// ── 4. VERIFICAR QUE EL ESCANEADOR ES PROPIETARIO PREMIUM ─────────────────────
$info_premium = verificar_propietario_premium($pdo, $propietario_user_id);

if (!$info_premium) {
    mostrar_error_pasaporte(
        'Acceso exclusivo Premium',
        'Solo los alojamientos con plan Premium pueden validar el Pasaporte Rural. ' .
        'Contacta con <a href="mailto:hola@rutasrurales.io">hola@rutasrurales.io</a> para activar tu plan.'
    );
    exit;
}

$alojamiento_id     = (int) $info_premium['alojamiento_id'];
$nombre_alojamiento = $info_premium['nombre_alojamiento'];

// ── 5. BUSCAR EL TOKEN EN LA BASE DE DATOS ────────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT qt.*,
            UNIX_TIMESTAMP(qt.created_at) AS created_unix,
            pt.id           AS pasaporte_id,
            pt.descuento_actual,
            pt.puntos_totales,
            pt.nivel,
            pt.estado       AS pasaporte_estado,
            pt.total_sellos,
            u.first_name, u.last_name,
            CONCAT(u.first_name, " ", u.last_name) AS nombre_turista,
            u.email, u.avatar_url
       FROM qr_temporales qt
       JOIN pasaporte_turistas pt ON pt.id = qt.pasaporte_id
       JOIN users u ON u.id = pt.user_id
      WHERE qt.hash_token = ?
      LIMIT 1'
);
$stmt->execute([$token_raw]);
$datos = $stmt->fetch();

// ── 6. VALIDACIONES EN CADENA ─────────────────────────────────────────────────

// 6a. ¿El token existe?
if (!$datos) {
    mostrar_error_pasaporte(
        'Pasaporte no reconocido',
        'Este código QR no existe en nuestro sistema. Pide al turista que genere un nuevo código.'
    );
    exit;
}

// 6b. ¿El token ya fue usado?
if ($datos['estado'] === 'usado') {
    mostrar_error_pasaporte(
        'Código ya utilizado',
        'Este código QR ya fue escaneado anteriormente. El turista debe mostrar un nuevo código.'
    );
    exit;
}

// 6c. ¿El token está expirado (marcado en BD)?
if ($datos['estado'] === 'expirado') {
    mostrar_error_pasaporte(
        'Código caducado',
        'El código QR ha caducado. Pide al turista que actualice su Pasaporte Rural (se renueva automáticamente cada ' . QR_ROTACION_SEGUNDOS . ' segundos).'
    );
    exit;
}

// 6d. ¿El token sigue dentro del TTL de 60 segundos? (doble comprobación en PHP)
// Usamos UNIX_TIMESTAMP devuelto por MySQL para evitar desfases UTC/local (timezone bug)
$segundos_transcurridos = time() - (int) $datos['created_unix'];
if ($segundos_transcurridos > QR_TTL_SEGUNDOS) {
    // Marcar como expirado en BD para futuras consultas
    $pdo->prepare(
        'UPDATE qr_temporales SET estado = "expirado" WHERE id = ?'
    )->execute([$datos['id']]);

    mostrar_error_pasaporte(
        'Código caducado (' . $segundos_transcurridos . 's)',
        'El código QR ha superado los ' . QR_TTL_SEGUNDOS . ' segundos de validez. ' .
        'Pide al turista que actualice su Pasaporte Rural.'
    );
    exit;
}

// 6e. ¿El pasaporte del turista está activo?
if ($datos['pasaporte_estado'] !== 'activo') {
    mostrar_error_pasaporte(
        'Pasaporte ' . esc_p($datos['pasaporte_estado']),
        'El pasaporte de este turista no está activo actualmente.'
    );
    exit;
}

// ── 7. TOKEN VÁLIDO — preparar datos para mostrar ─────────────────────────────

$nombre_turista   = esc_p($datos['nombre_turista']);
$email_turista    = esc_p($datos['email']);
$avatar_url       = $datos['avatar_url'] ?? '';
$nivel            = $datos['nivel'];
$nivel_emoji      = NIVELES_EMOJI[$nivel] ?? '🌱';
$descuento        = (int) $datos['descuento_actual'];
$puntos           = (int) $datos['puntos_totales'];
$total_sellos     = (int) $datos['total_sellos'];
$pasaporte_id     = (int) $datos['pasaporte_id'];
$qr_token_id      = (int) $datos['id'];
$segundos_valido  = QR_TTL_SEGUNDOS - $segundos_transcurridos;

// ── 8. GENERAR TOKEN CSRF PARA EL FORMULARIO DE SELLO ─────────────────────────
if (empty($_SESSION['csrf_sello'])) {
    $_SESSION['csrf_sello'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_sello'];

// =============================================================================
// HELPER: Mostrar pantalla de error
// =============================================================================
/**
 * Renderizar la pantalla roja de error y terminar la ejecución.
 *
 * @param string $titulo   Título del error (breve)
 * @param string $mensaje  Explicación con posible HTML básico
 */
function mostrar_error_pasaporte(string $titulo, string $mensaje): void
{
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title>Error — Pasaporte Rural</title>
        <link rel="stylesheet"
              href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet"
              href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="css/pasaporte.css">
    </head>
    <body class="pasaporte-body">
        <div class="pasaporte-header">
            <span class="logo-pasaporte">🌿</span>
            <h1>Pasaporte Rural</h1>
            <p class="subtitle">Validación de Pasaporte</p>
        </div>
        <div class="container" style="max-width:480px;">
            <div class="validacion-error">
                <span class="error-icon">❌</span>
                <h2><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= $mensaje /* puede contener links seguros */ ?></p>
            </div>
            <div class="text-center mt-3">
                <button onclick="history.back()"
                        class="btn btn-outline-secondary btn-sm me-2">
                    <i class="fa-solid fa-arrow-left me-1"></i> Volver
                </button>
                <a href="https://rutasrurales.io" class="btn btn-sm"
                   style="background:var(--p-primary,#2F5233);color:#fff;">
                    <i class="fa-solid fa-house me-1"></i> Inicio
                </a>
            </div>
            <div class="pasaporte-footer mt-4">
                <p><strong>Pasaporte Rural by rutasrurales.io</strong></p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

// ── 9. RENDERIZAR PANTALLA DE ÉXITO ──────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Pasaporte Válido — <?= esc_p($nombre_turista) ?></title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Módulo CSS -->
    <link rel="stylesheet" href="css/pasaporte.css">
</head>
<body class="pasaporte-body">

<!-- ── CABECERA ───────────────────────────────────────────────────────────── -->
<div class="pasaporte-header">
    <span class="logo-pasaporte">🌿</span>
    <h1>Pasaporte Válido ✅</h1>
    <p class="subtitle"><?= esc_p($nombre_alojamiento) ?></p>
</div>

<div class="container" style="max-width:480px; padding-bottom:2rem;">

    <!-- ── TARJETA VERDE DE VALIDACIÓN ────────────────────────────────────── -->
    <div class="validacion-ok">
        <span class="check-icon">✅</span>
        <h2>Pasaporte Rural Verificado</h2>
        <p style="color:var(--p-primary);font-size:.85rem;margin-bottom:0;">
            Código válido · <?= $segundos_valido ?> segundos restantes
        </p>

        <!-- Datos del turista -->
        <div class="turista-info-row">
            <?php if ($avatar_url): ?>
                <img src="<?= esc_p($avatar_url) ?>"
                     alt="<?= $nombre_turista ?>"
                     class="turista-avatar" style="width:56px;height:56px;">
            <?php else: ?>
                <div class="turista-avatar-placeholder"
                     style="width:56px;height:56px;font-size:1.4rem;">
                    <?= mb_substr(strip_tags($nombre_turista), 0, 1) ?>
                </div>
            <?php endif; ?>

            <div class="text-start">
                <p class="turista-nombre mb-0"><?= $nombre_turista ?></p>
                <p class="turista-email mb-1"><?= $email_turista ?></p>
                <span class="nivel-badge" style="margin-top:0;">
                    <?= $nivel_emoji ?> <?= esc_p($nivel) ?>
                </span>
            </div>
        </div>

        <!-- Descuento que le corresponde al turista -->
        <div class="d-flex justify-content-center gap-4 mt-2">
            <div class="text-center">
                <span class="descuento-badge" style="font-size:2.2rem;"><?= $descuento ?>%</span>
                <div style="font-size:.7rem;color:#2F5233;font-weight:700;margin-top:4px;">
                    DESCUENTO APLICABLE
                </div>
            </div>
            <div class="text-center d-flex flex-column justify-content-center">
                <span style="font-size:1.5rem;font-weight:800;color:var(--p-primary);">
                    <?= $puntos ?>
                </span>
                <span style="font-size:.7rem;color:#6c757d;font-weight:600;
                             text-transform:uppercase;">Puntos totales</span>
            </div>
            <div class="text-center d-flex flex-column justify-content-center">
                <span style="font-size:1.5rem;font-weight:800;color:var(--p-primary);">
                    <?= $total_sellos ?>
                </span>
                <span style="font-size:.7rem;color:#6c757d;font-weight:600;
                             text-transform:uppercase;">Sellos anteriores</span>
            </div>
        </div>
    </div>

    <!-- ── FORMULARIO DE PUNTUACIÓN (SELLO) ───────────────────────────────── -->
    <div class="pasaporte-card">
        <h2 style="font-size:.95rem;font-weight:700;color:var(--p-primary);
                   border-bottom:2px solid var(--p-border);padding-bottom:.5rem;margin-bottom:1.25rem;">
            <i class="fa-solid fa-stamp me-2"></i>Sellar y valorar la estancia
        </h2>

        <p style="font-size:.82rem;color:#6c757d;margin-bottom:1.25rem;">
            Valora al huésped al final de la estancia.
            Las puntuaciones son privadas y suman puntos a su Pasaporte Rural.
        </p>

        <form method="POST" action="procesar_sello.php" id="form-sello"
              onsubmit="return validarFormulario()">

            <!-- Campos ocultos de seguridad — NUNCA confiar en inputs del cliente -->
            <input type="hidden" name="csrf_token"    value="<?= esc_p($csrf_token) ?>">
            <input type="hidden" name="qr_token_id"   value="<?= $qr_token_id ?>">
            <input type="hidden" name="hash_token"    value="<?= esc_p($token_raw) ?>">
            <input type="hidden" name="pasaporte_id"  value="<?= $pasaporte_id ?>">
            <input type="hidden" name="alojamiento_id" value="<?= $alojamiento_id ?>">

            <!-- ── Puntuación: LIMPIEZA ──────────────────────────────────── -->
            <div class="rating-group">
                <label class="rating-titulo">
                    🧹 Limpieza de la habitación / espacio
                    <span style="color:#dc3545">*</span>
                </label>
                <div class="stars-input" id="stars-limpieza">
                    <!-- row-reverse: las estrellas van de 5 a 1 visualmente de izquierda a derecha -->
                    <input type="radio" id="l5" name="limpieza" value="5" required>
                    <label for="l5" title="5 — Impecable">⭐</label>

                    <input type="radio" id="l4" name="limpieza" value="4">
                    <label for="l4" title="4 — Muy buena">⭐</label>

                    <input type="radio" id="l3" name="limpieza" value="3">
                    <label for="l3" title="3 — Normal">⭐</label>

                    <input type="radio" id="l2" name="limpieza" value="2">
                    <label for="l2" title="2 — Regular">⭐</label>

                    <input type="radio" id="l1" name="limpieza" value="1">
                    <label for="l1" title="1 — Deficiente">⭐</label>
                </div>
                <div id="limpieza-error" style="color:#dc3545;font-size:.8rem;display:none;">
                    Por favor, selecciona una puntuación de limpieza.
                </div>
            </div>

            <!-- ── Puntuación: CIVISMO / COMPORTAMIENTO ──────────────────── -->
            <div class="rating-group">
                <label class="rating-titulo">
                    🤝 Comportamiento y civismo
                    <span style="color:#dc3545">*</span>
                </label>
                <div class="stars-input" id="stars-civismo">
                    <input type="radio" id="c5" name="civismo" value="5" required>
                    <label for="c5" title="5 — Excelente">⭐</label>

                    <input type="radio" id="c4" name="civismo" value="4">
                    <label for="c4" title="4 — Muy bueno">⭐</label>

                    <input type="radio" id="c3" name="civismo" value="3">
                    <label for="c3" title="3 — Normal">⭐</label>

                    <input type="radio" id="c2" name="civismo" value="2">
                    <label for="c2" title="2 — Regular">⭐</label>

                    <input type="radio" id="c1" name="civismo" value="1">
                    <label for="c1" title="1 — Deficiente">⭐</label>
                </div>
                <div id="civismo-error" style="color:#dc3545;font-size:.8rem;display:none;">
                    Por favor, selecciona una puntuación de comportamiento.
                </div>
            </div>

            <!-- ── Notas opcionales ──────────────────────────────────────── -->
            <div class="mb-3">
                <label for="notas" style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">
                    📝 Notas internas (opcional, privadas)
                </label>
                <textarea id="notas" name="notas"
                          rows="2"
                          maxlength="500"
                          placeholder="Observaciones sobre la estancia... (solo las ves tú)"
                          style="width:100%;border:1.5px solid var(--p-border,#d4e0d4);
                                 border-radius:.5rem;padding:.5rem .75rem;
                                 font-size:.85rem;resize:vertical;font-family:inherit;"></textarea>
            </div>

            <!-- ── Botón Sellar ──────────────────────────────────────────── -->
            <button type="submit" class="btn-sellar" id="btn-sellar">
                <i class="fa-solid fa-stamp"></i>
                Confirmar Sello Rural
            </button>

            <p class="text-center mt-2" style="font-size:.75rem;color:#6c757d;">
                <i class="fa-solid fa-lock me-1"></i>
                El sello es definitivo. El turista no puede ver las puntuaciones individuales.
            </p>
        </form>
    </div>

    <!-- Pie -->
    <div class="pasaporte-footer">
        <p><strong>Pasaporte Rural by rutasrurales.io</strong></p>
        <p>Panel de validación · Alojamiento Premium</p>
    </div>

</div><!-- /container -->

<script>
'use strict';

/**
 * Validar que ambas puntuaciones están seleccionadas antes de enviar.
 * HTML5 required en los radio no funciona bien en todos los navegadores
 * para grupos; validamos manualmente.
 */
function validarFormulario() {
    let valido = true;

    const limpieza = document.querySelector('input[name="limpieza"]:checked');
    const errL = document.getElementById('limpieza-error');
    if (!limpieza) {
        errL.style.display = 'block';
        valido = false;
    } else {
        errL.style.display = 'none';
    }

    const civismo = document.querySelector('input[name="civismo"]:checked');
    const errC = document.getElementById('civismo-error');
    if (!civismo) {
        errC.style.display = 'block';
        valido = false;
    } else {
        errC.style.display = 'none';
    }

    if (valido) {
        // Deshabilitar el botón para evitar doble envío
        const btn = document.getElementById('btn-sellar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-rural" style="width:20px;height:20px;border-width:2px;"></span> Sellando...';
    }

    return valido;
}
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

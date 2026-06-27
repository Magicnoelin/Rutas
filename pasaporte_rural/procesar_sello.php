<?php
/**
 * =============================================================================
 * PASAPORTE RURAL — Procesador del Sello
 * =============================================================================
 * Archivo  : pasaporte_rural/procesar_sello.php
 * Método   : POST (desde el formulario de validar_pasaporte.php)
 * Acceso   : Propietarios Premium logueados
 *
 * Operaciones en orden:
 *   1. Verificar sesión del propietario.
 *   2. Validar token CSRF.
 *   3. Sanitizar y validar inputs del formulario.
 *   4. Re-verificar el token QR en BD (idempotencia, anti-doble-envío).
 *   5. Re-verificar que el usuario sigue siendo propietario Premium.
 *   6. Calcular puntos del sello.
 *   7. Transacción atómica:
 *      a. Insertar en historico_sellos.
 *      b. Marcar qr_temporales.estado = 'usado'.
 *      c. Actualizar pasaporte_turistas (puntos, descuento, nivel, sellos).
 *   8. Renderizar pantalla de confirmación con el resumen.
 *
 * SEGURIDAD:
 *   - CSRF: token de sesión con hash_equals().
 *   - Idempotencia: si el token ya está 'usado', devuelve error amigable.
 *   - Transacción PDO: o todo se guarda o nada (consistencia de datos).
 *   - Prepared statements en todas las queries.
 *   - IDs críticos se re-leen de la BD, no del POST.
 * =============================================================================
 */

declare(strict_types=1);

define('API_NO_HEADERS', true);
require_once __DIR__ . '/config.php';

// ── 1. SESIÓN Y MÉTODO ────────────────────────────────────────────────────────
pasaporte_iniciar_sesion();

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mi-pasaporte.php');
    exit;
}

// Solo propietarios logueados
if (empty($_SESSION['user_id'])) {
    header('Location: /login.html');
    exit;
}

$propietario_user_id = (int) $_SESSION['user_id'];

// ── 2. CSRF ───────────────────────────────────────────────────────────────────
$csrf_enviado = $_POST['csrf_token'] ?? '';
$csrf_sesion  = $_SESSION['csrf_sello'] ?? '';

if (!$csrf_sesion || !hash_equals($csrf_sesion, $csrf_enviado)) {
    mostrar_error_sello('Error de seguridad.', 'Token de formulario inválido. Vuelve a escanear el QR.');
    exit;
}

// Rotar el token CSRF tras validarlo (protección contra reenvío)
$_SESSION['csrf_sello'] = bin2hex(random_bytes(32));

// ── 3. RECOGER Y SANITIZAR INPUTS ────────────────────────────────────────────
$hash_token    = trim($_POST['hash_token']    ?? '');
$qr_token_id   = (int) ($_POST['qr_token_id']  ?? 0);
$pasaporte_id  = (int) ($_POST['pasaporte_id'] ?? 0);
$alojamiento_id_post = (int) ($_POST['alojamiento_id'] ?? 0);

$limpieza_raw  = (int) ($_POST['limpieza'] ?? 0);
$civismo_raw   = (int) ($_POST['civismo']  ?? 0);
$notas         = mb_substr(trim($_POST['notas'] ?? ''), 0, 500);

// Validar formato del token
if (empty($hash_token) || !ctype_xdigit($hash_token) || strlen($hash_token) !== 96) {
    mostrar_error_sello('Datos inválidos.', 'El token QR tiene un formato incorrecto.');
    exit;
}

// Validar IDs positivos
if ($qr_token_id <= 0 || $pasaporte_id <= 0 || $alojamiento_id_post <= 0) {
    mostrar_error_sello('Datos incompletos.', 'Faltan datos necesarios para procesar el sello.');
    exit;
}

// Validar puntuaciones (1-5)
if ($limpieza_raw < 1 || $limpieza_raw > 5 || $civismo_raw < 1 || $civismo_raw > 5) {
    mostrar_error_sello('Puntuación inválida.', 'Las puntuaciones deben ser entre 1 y 5 estrellas.');
    exit;
}

$limpieza = $limpieza_raw;
$civismo  = $civismo_raw;

// ── 4. CONEXIÓN A BD ──────────────────────────────────────────────────────────
$pdo = getDBConnection();

// ── 5. RE-VERIFICAR PROPIETARIO PREMIUM ──────────────────────────────────────
// No confiamos en el alojamiento_id del POST; lo re-leemos de BD
$info_premium = verificar_propietario_premium($pdo, $propietario_user_id);

if (!$info_premium) {
    mostrar_error_sello('Sin permisos Premium.', 'Tu alojamiento no está activo como Premium.');
    exit;
}

$alojamiento_id    = (int) $info_premium['alojamiento_id'];
$nombre_alojamiento = $info_premium['nombre_alojamiento'];

// Verificar que el alojamiento del POST coincide con el de BD (defensa en profundidad)
if ($alojamiento_id !== $alojamiento_id_post) {
    error_log('[PasaporteSello] Discrepancia de alojamiento_id. POST=' . $alojamiento_id_post . ' BD=' . $alojamiento_id . ' user=' . $propietario_user_id);
    mostrar_error_sello('Error de verificación.', 'Los datos del formulario no coinciden. Vuelve a escanear el QR.');
    exit;
}

// ── 6. RE-VERIFICAR TOKEN QR (idempotencia) ───────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT qt.id, qt.estado, qt.created_at, qt.pasaporte_id,
            pt.descuento_actual, pt.puntos_totales, pt.nivel,
            pt.estado AS pasaporte_estado,
            CONCAT(u.first_name, " ", u.last_name) AS nombre_turista
       FROM qr_temporales qt
       JOIN pasaporte_turistas pt ON pt.id = qt.pasaporte_id
       JOIN users u ON u.id = pt.user_id
      WHERE qt.hash_token = ?
        AND qt.id = ?
        AND qt.pasaporte_id = ?
      LIMIT 1'
);
$stmt->execute([$hash_token, $qr_token_id, $pasaporte_id]);
$token_data = $stmt->fetch();

if (!$token_data) {
    mostrar_error_sello('Token no encontrado.', 'El código QR no existe o los datos no coinciden. Vuelve a escanear.');
    exit;
}

if ($token_data['estado'] === 'usado') {
    mostrar_error_sello('Sello ya registrado.', 'Este pasaporte ya fue sellado con este código QR. El turista tiene puntos de esta visita.');
    exit;
}

if ($token_data['estado'] === 'expirado') {
    mostrar_error_sello('Código caducado.', 'El código QR ha expirado. El turista debe mostrar un nuevo código.');
    exit;
}

// Doble comprobación del TTL en PHP
$segundos_transcurridos = time() - strtotime($token_data['created_at']);
if ($segundos_transcurridos > QR_TTL_SEGUNDOS) {
    $pdo->prepare('UPDATE qr_temporales SET estado = "expirado" WHERE id = ?')
        ->execute([$token_data['id']]);
    mostrar_error_sello(
        'Código caducado (' . $segundos_transcurridos . ' s).',
        'El código QR superó los ' . QR_TTL_SEGUNDOS . ' segundos de validez. Pide un nuevo QR al turista.'
    );
    exit;
}

if ($token_data['pasaporte_estado'] !== 'activo') {
    mostrar_error_sello('Pasaporte inactivo.', 'El pasaporte de este turista no está activo.');
    exit;
}

// ── 7. CALCULAR PUNTOS DEL SELLO ─────────────────────────────────────────────
$puntos_calc        = calcular_puntos_sello($limpieza, $civismo);
$puntos_base        = $puntos_calc['base'];
$puntos_bonus       = $puntos_calc['bonus'];
$puntos_sumados     = $puntos_calc['total'];

// Estado previo del pasaporte
$descuento_previo   = (int) $token_data['descuento_actual'];
$puntos_previos     = (int) $token_data['puntos_totales'];
$nivel_previo       = $token_data['nivel'];
$nombre_turista     = $token_data['nombre_turista'];

// Calcular nuevo estado tras el sello
$puntos_nuevos      = $puntos_previos + $puntos_sumados;
$descuento_nuevo    = calcular_descuento($puntos_nuevos);
$nivel_nuevo        = calcular_nivel($puntos_nuevos);
$subio_nivel        = ($nivel_nuevo !== $nivel_previo) ? 1 : 0;

// ── 8. TRANSACCIÓN ATÓMICA ────────────────────────────────────────────────────
/*
 * Todo o nada:
 *   a. Insertar en historico_sellos.
 *   b. Marcar token como 'usado'.
 *   c. Actualizar pasaporte_turistas.
 *
 * Si cualquier operación falla → rollback automático → BD sin cambios.
 */
try {
    $pdo->beginTransaction();

    // a) Insertar el sello en el histórico
    $pdo->prepare(
        'INSERT INTO historico_sellos
            (pasaporte_id, alojamiento_id, propietario_user_id, qr_token_id,
             puntuacion_limpieza, puntuacion_civismo,
             puntos_base, puntos_bonus, puntos_sumados,
             descuento_previo, descuento_nuevo, subio_nivel,
             notas_propietario, ip_sello)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $pasaporte_id,
        $alojamiento_id,
        $propietario_user_id,
        $qr_token_id,
        $limpieza,
        $civismo,
        $puntos_base,
        $puntos_bonus,
        $puntos_sumados,
        $descuento_previo,
        $descuento_nuevo,
        $subio_nivel,
        ($notas !== '') ? $notas : null,  // NULL si no hay notas
        pasaporte_obtener_ip(),
    ]);

    // b) Marcar el token QR como 'usado' (destruirlo para evitar reutilización)
    $pdo->prepare(
        'UPDATE qr_temporales
            SET estado = "usado", usado_at = NOW()
          WHERE id = ?'
    )->execute([$qr_token_id]);

    // c) Actualizar el pasaporte del turista
    $pdo->prepare(
        'UPDATE pasaporte_turistas
            SET puntos_totales  = puntos_totales + ?,
                puntos_periodo  = puntos_periodo + ?,
                descuento_actual = ?,
                nivel           = ?,
                total_sellos    = total_sellos + 1,
                ultimo_sello_at = NOW()
          WHERE id = ?'
    )->execute([
        $puntos_sumados,
        $puntos_sumados,
        $descuento_nuevo,
        $nivel_nuevo,
        $pasaporte_id,
    ]);

    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('[PasaporteSello] Error en transacción: ' . $e->getMessage());
    mostrar_error_sello(
        'Error al registrar el sello.',
        'Ha ocurrido un error técnico. Por favor, inténtalo de nuevo o contacta con soporte.'
    );
    exit;
}

// ── 9. PANTALLA DE CONFIRMACIÓN ───────────────────────────────────────────────
$nombre_turista_esc  = esc_p($nombre_turista);
$nombre_alo_esc      = esc_p($nombre_alojamiento);
$excelente           = ($puntos_bonus > 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>✅ Sello Registrado — Pasaporte Rural</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/pasaporte.css">
</head>
<body class="pasaporte-body">

<div class="pasaporte-header">
    <span class="logo-pasaporte">🌿</span>
    <h1>¡Sello Registrado!</h1>
    <p class="subtitle"><?= $nombre_alo_esc ?></p>
</div>

<div class="container" style="max-width:480px; padding-bottom:2rem;">

    <!-- ── CONFIRMACIÓN VISUAL ────────────────────────────────────────────── -->
    <div class="pasaporte-card sello-confirmado">
        <span class="sello-stamp">🏡</span>

        <h2 style="color:var(--p-primary);font-size:1.3rem;font-weight:800;margin-bottom:.25rem;">
            ¡Sello Rural aplicado!
        </h2>
        <p style="color:#6c757d;font-size:.88rem;margin-bottom:1.5rem;">
            Turista: <strong><?= $nombre_turista_esc ?></strong>
        </p>

        <!-- Resumen de puntuación dada -->
        <div class="d-flex justify-content-center gap-4 mb-3">
            <div class="text-center">
                <div style="font-size:1.5rem;"><?= str_repeat('⭐', $limpieza) ?></div>
                <div style="font-size:.72rem;color:#6c757d;font-weight:600;margin-top:3px;">
                    LIMPIEZA
                </div>
            </div>
            <div class="text-center">
                <div style="font-size:1.5rem;"><?= str_repeat('⭐', $civismo) ?></div>
                <div style="font-size:.72rem;color:#6c757d;font-weight:600;margin-top:3px;">
                    CIVISMO
                </div>
            </div>
        </div>

        <?php if ($excelente): ?>
        <!-- Bonus de excelencia -->
        <div class="alert py-2 px-3 mb-3 d-flex align-items-center gap-2"
             style="background:#fff8e1;border:1px solid #ffe082;font-size:.82rem;border-radius:.5rem;">
            <i class="fa-solid fa-star" style="color:#f9a825;"></i>
            <span><strong>¡Bono Huésped Excelente!</strong> +<?= BONUS_EXCELENCIA ?> puntos extra</span>
        </div>
        <?php endif; ?>

        <!-- Puntos sumados -->
        <div class="stats-grid" style="margin-bottom:1rem;">
            <div class="stat-item">
                <span class="stat-valor" style="color:var(--p-accent);">
                    +<?= $puntos_sumados ?>
                </span>
                <span class="stat-label">Puntos sumados</span>
            </div>
            <div class="stat-item">
                <span class="stat-valor"><?= $puntos_nuevos ?></span>
                <span class="stat-label">Puntos totales</span>
            </div>
            <div class="stat-item">
                <span class="stat-valor"><?= $descuento_nuevo ?>%</span>
                <span class="stat-label">Dto. turista</span>
            </div>
        </div>

        <?php if ($subio_nivel): ?>
        <!-- Subida de nivel -->
        <div class="alert py-2 px-3 mb-3 text-center"
             style="background:#e8f5e9;border:1px solid #a5d6a7;font-size:.85rem;border-radius:.5rem;">
            🎉 <strong>¡El turista sube a nivel <?= esc_p($nivel_nuevo) ?>!</strong>
            <?= NIVELES_EMOJI[$nivel_nuevo] ?? '' ?>
        </div>
        <?php endif; ?>

        <?php if ($descuento_nuevo > $descuento_previo): ?>
        <!-- Subida de descuento -->
        <div class="alert py-2 px-3 mb-3 text-center"
             style="background:#e3f2fd;border:1px solid #90caf9;font-size:.85rem;border-radius:.5rem;">
            🎉 <strong>¡El descuento del turista sube al <?= $descuento_nuevo ?>%!</strong>
        </div>
        <?php endif; ?>

        <!-- Botones de acción -->
        <div class="d-flex gap-2 mt-3">
            <button onclick="history.go(-2)"
                    class="btn btn-outline-secondary flex-fill">
                <i class="fa-solid fa-qrcode me-1"></i>
                Nuevo escaneo
            </button>
            <a href="https://rutasrurales.io"
               class="btn flex-fill"
               style="background:var(--p-primary);color:#fff;font-weight:600;">
                <i class="fa-solid fa-house me-1"></i>
                Inicio
            </a>
        </div>
    </div>

    <div class="pasaporte-footer">
        <p><strong>Pasaporte Rural by rutasrurales.io</strong></p>
        <p>Sello registrado el <?= date('d/m/Y \a \l\a\s H:i') ?></p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFsgAGFKQHgMRLxkh+MXJqVBRy"
        crossorigin="anonymous"></script>
</body>
</html>
<?php

// =============================================================================
// HELPERS LOCALES
// =============================================================================

/**
 * Renderizar pantalla roja de error y terminar.
 */
function mostrar_error_sello(string $titulo, string $mensaje): void
{
    // Si ya se enviaron cabeceras HTML (por el formulario), mostrar en texto simple
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title>Error al sellar — Pasaporte Rural</title>
        <link rel="stylesheet"
              href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="css/pasaporte.css">
    </head>
    <body class="pasaporte-body">
        <div class="pasaporte-header">
            <span class="logo-pasaporte">🌿</span>
            <h1>Error al Sellar</h1>
        </div>
        <div class="container" style="max-width:480px;">
            <div class="validacion-error">
                <span class="error-icon">❌</span>
                <h2><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="text-center mt-3">
                <button onclick="history.back()"
                        class="btn btn-outline-secondary">
                    ← Volver
                </button>
            </div>
        </div>
    </body>
    </html>
    <?php
}

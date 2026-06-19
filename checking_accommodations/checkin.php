<?php
/**
 * =============================================================================
 * SISTEMA DE CHECK-IN — Formulario público de registro de huéspedes
 * =============================================================================
 * Archivo  : checkin.php
 * Acceso   : Público — URL: checkin.php?token=XXXXXX
 * Descripción: Formulario que rellena cada huésped mayor de 14 años.
 *              Campos obligatorios según Real Decreto 933/2021 / SES.MIR.
 *
 * SEGURIDAD:
 *   - El token se valida en BD; nunca se confía en datos POST para el alojamiento_id.
 *   - Token CSRF para proteger el envío del formulario.
 *   - Todos los inputs se sanitizan y validan en servidor.
 *   - noindex/nofollow para no indexar URLs con token en Google.
 * =============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------------------
// 1. VALIDAR TOKEN DE ALOJAMIENTO
//    El token viene por GET. Si no existe o es inválido → error 404.
// ---------------------------------------------------------------------------
$token_raw = trim($_GET['token'] ?? '');

if (empty($token_raw) || !ctype_alnum($token_raw) || strlen($token_raw) !== 64) {
    http_response_code(404);
    mostrar_error_404();
    exit;
}

$pdo = obtener_pdo();

// Buscar el alojamiento por token público en la tabla accommodations existente
$stmt = $pdo->prepare(
    'SELECT id, name FROM accommodations WHERE token_publico = ? LIMIT 1'
);
$stmt->execute([$token_raw]);
$alojamiento = $stmt->fetch();

if (!$alojamiento) {
    http_response_code(404);
    mostrar_error_404();
    exit;
}

$alojamiento_id     = (int) $alojamiento['id'];
$alojamiento_nombre = $alojamiento['name'];

// ---------------------------------------------------------------------------
// 2. INICIALIZAR VARIABLES DEL FORMULARIO
// ---------------------------------------------------------------------------
$errores      = [];
$exito        = false;
$form_valores = [];  // Repoblar formulario en caso de error

// ---------------------------------------------------------------------------
// 3. GENERAR / VERIFICAR TOKEN CSRF
//    Se genera una vez por sesión y se valida en el POST.
// ---------------------------------------------------------------------------
iniciar_sesion_segura();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ---------------------------------------------------------------------------
// 4. PROCESAR ENVÍO DEL FORMULARIO (POST)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- 4.1 Verificar token CSRF ---
    $csrf_post = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf_token, $csrf_post)) {
        $errores[] = 'Error de seguridad. Por favor, recarga la página e inténtalo de nuevo.';
    }

    if (empty($errores)) {

        // --- 4.2 Recoger y sanitizar campos ---
        $campos = [
            'nombre'               => trim($_POST['nombre']               ?? ''),
            'apellidos'            => trim($_POST['apellidos']            ?? ''),
            'sexo'                 => trim($_POST['sexo']                 ?? ''),
            'fecha_nacimiento'     => trim($_POST['fecha_nacimiento']     ?? ''),
            'nacionalidad'         => trim($_POST['nacionalidad']         ?? ''),
            'tipo_documento'       => trim($_POST['tipo_documento']       ?? ''),
            'numero_documento'     => strtoupper(trim($_POST['numero_documento'] ?? '')),
            'fecha_expedicion_doc' => trim($_POST['fecha_expedicion_doc'] ?? ''),
            'numero_soporte'       => strtoupper(trim($_POST['numero_soporte']   ?? '')),
            'telefono'             => trim($_POST['telefono']             ?? ''),
            'email'                => trim($_POST['email']                ?? ''),
            'direccion_calle'      => trim($_POST['direccion_calle']      ?? ''),
            'direccion_numero'     => trim($_POST['direccion_numero']     ?? ''),
            'provincia'            => trim($_POST['provincia']            ?? ''),
            'codigo_postal'        => trim($_POST['codigo_postal']        ?? ''),
            'pais'                 => trim($_POST['pais']                 ?? 'España'),
            'fecha_entrada'        => trim($_POST['fecha_entrada']        ?? ''),
            'fecha_salida_prevista'=> trim($_POST['fecha_salida_prevista']?? ''),
        ];
        $form_valores = $campos;

        // --- 4.3 Validaciones ---

        // Campos de texto obligatorios
        $obligatorios = [
            'nombre'               => 'Nombre',
            'apellidos'            => 'Apellidos',
            'sexo'                 => 'Sexo',
            'fecha_nacimiento'     => 'Fecha de nacimiento',
            'nacionalidad'         => 'Nacionalidad',
            'tipo_documento'       => 'Tipo de documento',
            'numero_documento'     => 'Número de documento',
            'fecha_expedicion_doc' => 'Fecha de expedición del documento',
            'telefono'             => 'Teléfono móvil',
            'email'                => 'Correo electrónico',
            'direccion_calle'      => 'Calle',
            'direccion_numero'     => 'Número',
            'provincia'            => 'Provincia',
            'codigo_postal'        => 'Código postal',
            'pais'                 => 'País',
            'fecha_entrada'        => 'Fecha de entrada',
            'fecha_salida_prevista'=> 'Fecha de salida prevista',
        ];

        foreach ($obligatorios as $campo => $etiqueta) {
            if (empty($campos[$campo])) {
                $errores[] = "El campo <strong>{$etiqueta}</strong> es obligatorio.";
            }
        }

        // Validar sexo
        if (!empty($campos['sexo']) && !in_array($campos['sexo'], ['H', 'M', 'X'])) {
            $errores[] = 'El valor de Sexo no es válido.';
        }

        // Validar tipo de documento
        $tipos_validos = ['DNI', 'NIE', 'Pasaporte', 'Otro'];
        if (!empty($campos['tipo_documento']) && !in_array($campos['tipo_documento'], $tipos_validos)) {
            $errores[] = 'El tipo de documento no es válido.';
        }

        // Validar número de soporte (obligatorio si DNI)
        if ($campos['tipo_documento'] === 'DNI') {
            if (empty($campos['numero_soporte'])) {
                $errores[] = 'El <strong>Número de Soporte</strong> es obligatorio para el DNI.';
            } elseif (!preg_match('/^[A-Z]{3}[0-9]{6}$/', $campos['numero_soporte'])) {
                $errores[] = 'El <strong>Número de Soporte</strong> debe tener el formato: 3 letras mayúsculas seguidas de 6 números (ej: ABC123456).';
            }
        }

        // Validar email
        if (!empty($campos['email']) && !filter_var($campos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo electrónico no tiene un formato válido.';
        }

        // Validar fechas
        $fecha_hoy = new DateTimeImmutable('today');

        foreach (['fecha_nacimiento', 'fecha_expedicion_doc', 'fecha_entrada', 'fecha_salida_prevista'] as $f) {
            if (!empty($campos[$f])) {
                $dt = DateTimeImmutable::createFromFormat('Y-m-d', $campos[$f]);
                if (!$dt || $dt->format('Y-m-d') !== $campos[$f]) {
                    $etiqueta = $obligatorios[$f] ?? $f;
                    $errores[] = "La fecha de <strong>{$etiqueta}</strong> no es válida.";
                }
            }
        }

        // Validar que fecha de salida sea posterior a la de entrada
        if (empty($errores)) {
            $f_entrada = DateTimeImmutable::createFromFormat('Y-m-d', $campos['fecha_entrada']);
            $f_salida  = DateTimeImmutable::createFromFormat('Y-m-d', $campos['fecha_salida_prevista']);
            if ($f_entrada && $f_salida && $f_salida <= $f_entrada) {
                $errores[] = 'La <strong>fecha de salida</strong> debe ser posterior a la fecha de entrada.';
            }
        }

        // Validar código postal (5 dígitos si el país es España)
        if (strtolower($campos['pais']) === 'españa' || strtolower($campos['pais']) === 'espana') {
            if (!empty($campos['codigo_postal']) && !preg_match('/^\d{5}$/', $campos['codigo_postal'])) {
                $errores[] = 'El <strong>Código Postal</strong> debe tener 5 dígitos para España.';
            }
        }

        // --- 4.4 Insertar en BD si no hay errores ---
        if (empty($errores)) {
            try {
                $sql = '
                    INSERT INTO huespedes_registro (
                        alojamiento_id, nombre, apellidos, sexo,
                        fecha_nacimiento, nacionalidad,
                        tipo_documento, numero_documento, fecha_expedicion_doc, numero_soporte,
                        telefono, email,
                        direccion_calle, direccion_numero, provincia, codigo_postal, pais,
                        fecha_entrada, fecha_salida_prevista,
                        ip_registro
                    ) VALUES (
                        :alojamiento_id, :nombre, :apellidos, :sexo,
                        :fecha_nacimiento, :nacionalidad,
                        :tipo_documento, :numero_documento, :fecha_expedicion_doc, :numero_soporte,
                        :telefono, :email,
                        :direccion_calle, :direccion_numero, :provincia, :codigo_postal, :pais,
                        :fecha_entrada, :fecha_salida_prevista,
                        :ip_registro
                    )
                ';

                $insert = $pdo->prepare($sql);
                $insert->execute([
                    ':alojamiento_id'       => $alojamiento_id,  // SIEMPRE de la sesión/BD, nunca del POST
                    ':nombre'               => $campos['nombre'],
                    ':apellidos'            => $campos['apellidos'],
                    ':sexo'                 => $campos['sexo'],
                    ':fecha_nacimiento'     => $campos['fecha_nacimiento'],
                    ':nacionalidad'         => $campos['nacionalidad'],
                    ':tipo_documento'       => $campos['tipo_documento'],
                    ':numero_documento'     => $campos['numero_documento'],
                    ':fecha_expedicion_doc' => $campos['fecha_expedicion_doc'],
                    ':numero_soporte'       => ($campos['tipo_documento'] === 'DNI') ? $campos['numero_soporte'] : null,
                    ':telefono'             => $campos['telefono'],
                    ':email'                => $campos['email'],
                    ':direccion_calle'      => $campos['direccion_calle'],
                    ':direccion_numero'     => $campos['direccion_numero'],
                    ':provincia'            => $campos['provincia'],
                    ':codigo_postal'        => $campos['codigo_postal'],
                    ':pais'                 => $campos['pais'],
                    ':fecha_entrada'        => $campos['fecha_entrada'],
                    ':fecha_salida_prevista'=> $campos['fecha_salida_prevista'],
                    ':ip_registro'          => obtener_ip(),
                ]);

                $exito = true;
                // Limpiar valores del formulario tras éxito
                $form_valores = [];
                // Regenerar token CSRF tras envío exitoso
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            } catch (PDOException $e) {
                error_log('[CheckIn] Error al insertar huésped: ' . $e->getMessage());
                $errores[] = 'Ocurrió un error al guardar el registro. Por favor, inténtalo de nuevo.';
            }
        }
    }
}

// ---------------------------------------------------------------------------
// 5. FUNCIÓN AUXILIAR: Valor del campo (repoblar formulario)
// ---------------------------------------------------------------------------
function val(string $campo, array $valores, string $defecto = ''): string
{
    return esc($valores[$campo] ?? $defecto);
}

// ---------------------------------------------------------------------------
// 6. FUNCIÓN: Error 404 amigable
// ---------------------------------------------------------------------------
function mostrar_error_404(): void
{
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title>Enlace no válido — Check-in</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
        <div class="text-center p-5">
            <div style="font-size:4rem;">🏡</div>
            <h1 class="h3 mt-3" style="color:#2F5233;">Enlace de check-in no válido</h1>
            <p class="text-muted">El enlace que has utilizado no existe o ha caducado.<br>
            Por favor, solicita un nuevo enlace a tu alojamiento.</p>
        </div>
    </body>
    </html>
    <?php
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- noindex: Las URLs con token no deben indexarse en buscadores -->
    <meta name="robots" content="noindex, nofollow">
    <title>Registro de Huésped — <?= esc($alojamiento_nombre) ?></title>

    <!-- Bootstrap 5 CDN -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <!-- Font Awesome (iconos) -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ----------------------------------------------------------------
           Colores corporativos del proyecto
           ---------------------------------------------------------------- */
        :root {
            --primary:    #2F5233;
            --secondary:  #6B8E6B;
            --accent:     #B8956A;
            --dark:       #1A2E1A;
            --light-bg:   #f4f7f4;
            --card-bg:    #ffffff;
            --border:     #d4e0d4;
            --focus-ring: rgba(47, 82, 51, 0.25);
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--dark);
        }

        /* Cabecera del formulario */
        .checkin-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #fff;
            padding: 2.5rem 1.5rem 2rem;
            border-radius: 0 0 1.5rem 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .checkin-header .icon-alojamiento {
            font-size: 3.5rem;
            margin-bottom: 0.75rem;
            opacity: 0.9;
        }

        .checkin-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .checkin-header .nombre-alojamiento {
            font-size: 1.15rem;
            font-weight: 600;
            color: #d4efda;
        }

        .checkin-header .subtitulo {
            font-size: 0.88rem;
            opacity: 0.85;
            margin-top: 0.5rem;
        }

        /* Tarjeta del formulario */
        .checkin-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.75rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 12px rgba(47, 82, 51, 0.07);
        }

        /* Título de sección del formulario */
        .section-title {
            color: var(--primary);
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Labels */
        .form-label {
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--dark);
            margin-bottom: 0.3rem;
        }

        .required-mark {
            color: #dc3545;
            margin-left: 2px;
        }

        /* Inputs y selects */
        .form-control,
        .form-select {
            border: 1.5px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.93rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--focus-ring);
        }

        /* Botón principal */
        .btn-checkin {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            padding: 0.75rem 2rem;
            border-radius: 0.6rem;
            transition: transform 0.15s, box-shadow 0.15s;
            width: 100%;
        }

        .btn-checkin:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(47, 82, 51, 0.3);
            color: #fff;
        }

        .btn-checkin:active {
            transform: translateY(0);
        }

        /* Tooltip número de soporte */
        .soporte-info {
            background: #fff8f0;
            border: 1px solid #f0d9b8;
            border-radius: 0.5rem;
            padding: 0.6rem 0.9rem;
            font-size: 0.82rem;
            color: #7a5c2e;
            margin-top: 0.4rem;
            display: none;
        }

        .soporte-info.visible {
            display: block;
        }

        /* Alerta de éxito */
        .alert-exito {
            background: linear-gradient(135deg, #d4efda 0%, #e8f5e9 100%);
            border: 2px solid var(--secondary);
            border-radius: 1rem;
            color: var(--dark);
            padding: 2.5rem;
            text-align: center;
        }

        .alert-exito .icon-ok {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        /* Pie del formulario */
        .checkin-footer {
            text-align: center;
            font-size: 0.78rem;
            color: #7a8a7a;
            padding: 1.5rem 0 2rem;
        }

        .checkin-footer strong {
            color: var(--primary);
        }

        /* Responsive */
        @media (max-width: 576px) {
            .checkin-card {
                padding: 1.25rem 1rem;
            }
            .checkin-header {
                padding: 2rem 1rem 1.5rem;
            }
        }
    </style>
</head>
<body>

<!-- Cabecera del formulario -->
<div class="checkin-header">
    <div class="icon-alojamiento">🏡</div>
    <p class="subtitulo">Formulario de registro de huéspedes</p>
    <h1 class="nombre-alojamiento"><?= esc($alojamiento_nombre) ?></h1>
    <p class="subtitulo mt-2">
        <i class="fa-solid fa-shield-halved me-1"></i>
        Sus datos se tratarán según la normativa RGPD y el RD 933/2021
    </p>
</div>

<div class="container" style="max-width:720px;">

    <?php if ($exito): ?>
    <!-- ===== MENSAJE DE ÉXITO ===== -->
    <div class="alert-exito mb-4">
        <div class="icon-ok">✅</div>
        <h2 class="h4 mb-2" style="color:var(--primary);">¡Registro completado!</h2>
        <p class="mb-1">Sus datos han sido registrados correctamente en <strong><?= esc($alojamiento_nombre) ?></strong>.</p>
        <p class="text-muted" style="font-size:0.88rem;">
            Si viajan acompañados, cada huésped mayor de 14 años debe rellenar su propio formulario.
        </p>
        <hr>
        <p style="font-size:0.82rem; color:#7a8a7a;">
            <i class="fa-solid fa-lock me-1"></i>
            Sus datos se almacenan de forma segura y cifrada conforme al RGPD.
        </p>
    </div>

    <?php else: ?>

    <!-- ===== ERRORES DE VALIDACIÓN ===== -->
    <?php if (!empty($errores)): ?>
    <div class="alert alert-danger rounded-3 mb-4" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <strong>Por favor, corrija los siguientes errores:</strong>
        <ul class="mb-0 mt-2 ps-3">
            <?php foreach ($errores as $error): ?>
                <li><?= $error /* Ya contiene HTML etiquetas <strong>, escapado en origen */ ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- ===== AVISO OBLIGATORIEDAD ===== -->
    <div class="alert alert-warning rounded-3 mb-4 d-flex align-items-start gap-2" role="alert">
        <i class="fa-solid fa-circle-info mt-1 flex-shrink-0"></i>
        <div style="font-size:0.87rem;">
            <strong>Obligatorio para todos los huéspedes mayores de 14 años.</strong><br>
            Datos requeridos por la Ley Orgánica 4/2015 y el Real Decreto 933/2021
            para la comunicación de viajeros al portal SES.MIR del Ministerio del Interior.
        </div>
    </div>

    <!-- ===== FORMULARIO ===== -->
    <form method="POST"
          action="checkin.php?token=<?= esc($token_raw) ?>"
          novalidate
          id="form-checkin">

        <!-- Token CSRF oculto -->
        <input type="hidden" name="csrf_token" value="<?= esc($csrf_token) ?>">

        <!-- ================================================================
             SECCIÓN 1: DATOS PERSONALES
             ================================================================ -->
        <div class="checkin-card">
            <h2 class="section-title">
                <i class="fa-solid fa-user"></i> Datos personales
            </h2>

            <div class="row g-3">
                <!-- Nombre -->
                <div class="col-md-6">
                    <label class="form-label" for="nombre">
                        Nombre <span class="required-mark">*</span>
                    </label>
                    <input type="text"
                           class="form-control"
                           id="nombre"
                           name="nombre"
                           value="<?= val('nombre', $form_valores) ?>"
                           maxlength="100"
                           autocomplete="given-name"
                           required>
                </div>

                <!-- Apellidos -->
                <div class="col-md-6">
                    <label class="form-label" for="apellidos">
                        Apellidos <span class="required-mark">*</span>
                    </label>
                    <input type="text"
                           class="form-control"
                           id="apellidos"
                           name="apellidos"
                           value="<?= val('apellidos', $form_valores) ?>"
                           maxlength="150"
                           autocomplete="family-name"
                           required>
                </div>

                <!-- Sexo -->
                <div class="col-md-4">
                    <label class="form-label" for="sexo">
                        Sexo <span class="required-mark">*</span>
                    </label>
                    <select class="form-select" id="sexo" name="sexo" required>
                        <option value="">— Seleccionar —</option>
                        <option value="H" <?= val('sexo', $form_valores) === 'H' ? 'selected' : '' ?>>Hombre</option>
                        <option value="M" <?= val('sexo', $form_valores) === 'M' ? 'selected' : '' ?>>Mujer</option>
                        <option value="X" <?= val('sexo', $form_valores) === 'X' ? 'selected' : '' ?>>No binario / Otro</option>
                    </select>
                </div>

                <!-- Fecha de nacimiento -->
                <div class="col-md-4">
                    <label class="form-label" for="fecha_nacimiento">
                        Fecha de nacimiento <span class="required-mark">*</span>
                    </label>
                    <input type="date"
                           class="form-control"
                           id="fecha_nacimiento"
                           name="fecha_nacimiento"
                           value="<?= val('fecha_nacimiento', $form_valores) ?>"
                           max="<?= date('Y-m-d', strtotime('-14 years')) ?>"
                           required>
                </div>

                <!-- Nacionalidad -->
                <div class="col-md-4">
                    <label class="form-label" for="nacionalidad">
                        Nacionalidad <span class="required-mark">*</span>
                    </label>
                    <input type="text"
                           class="form-control"
                           id="nacionalidad"
                           name="nacionalidad"
                           value="<?= val('nacionalidad', $form_valores, 'Española') ?>"
                           maxlength="80"
                           required>
                </div>
            </div>
        </div>

        <!-- ================================================================
             SECCIÓN 2: DOCUMENTO DE IDENTIDAD
             ================================================================ -->
        <div class="checkin-card">
            <h2 class="section-title">
                <i class="fa-solid fa-id-card"></i> Documento de identidad
            </h2>

            <div class="row g-3">
                <!-- Tipo de documento -->
                <div class="col-md-4">
                    <label class="form-label" for="tipo_documento">
                        Tipo de documento <span class="required-mark">*</span>
                    </label>
                    <select class="form-select"
                            id="tipo_documento"
                            name="tipo_documento"
                            required
                            onchange="gestionarSoporte(this.value)">
                        <option value="">— Seleccionar —</option>
                        <option value="DNI"       <?= val('tipo_documento', $form_valores) === 'DNI'       ? 'selected' : '' ?>>DNI</option>
                        <option value="NIE"       <?= val('tipo_documento', $form_valores) === 'NIE'       ? 'selected' : '' ?>>NIE</option>
                        <option value="Pasaporte" <?= val('tipo_documento', $form_valores) === 'Pasaporte' ? 'selected' : '' ?>>Pasaporte</option>
                        <option value="Otro"      <?= val('tipo_documento', $form_valores) === 'Otro'      ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>

                <!-- Número de documento -->
                <div class="col-md-4">
                    <label class="form-label" for="numero_documento">
                        Número de documento <span class="required-mark">*</span>
                    </label>
                    <input type="text"
                           class="form-control"
                           id="numero_documento"
                           name="numero_documento"
                           value="<?= val('numero_documento', $form_valores) ?>"
                           maxlength="30"
                           style="text-transform:uppercase;"
                           required>
                </div>

                <!-- Fecha de expedición -->
                <div class="col-md-4">
                    <label class="form-label" for="fecha_expedicion_doc">
                        Fecha de expedición <span class="required-mark">*</span>
                    </label>
                    <input type="date"
                           class="form-control"
                           id="fecha_expedicion_doc"
                           name="fecha_expedicion_doc"
                           value="<?= val('fecha_expedicion_doc', $form_valores) ?>"
                           max="<?= date('Y-m-d') ?>"
                           required>
                </div>

                <!-- Número de soporte (solo DNI) -->
                <div class="col-12" id="bloque-soporte">
                    <label class="form-label" for="numero_soporte">
                        Número de Soporte
                        <span class="required-mark" id="soporte-required-mark">*</span>
                        <span class="badge ms-1"
                              style="background:var(--accent); font-size:0.72rem; font-weight:600;">
                            Solo DNI
                        </span>
                    </label>
                    <input type="text"
                           class="form-control"
                           id="numero_soporte"
                           name="numero_soporte"
                           value="<?= val('numero_soporte', $form_valores) ?>"
                           maxlength="9"
                           placeholder="Ej: ABC123456"
                           style="text-transform:uppercase; max-width:200px;">
                    <!-- Información aclaratoria del número de soporte -->
                    <div class="soporte-info visible" id="soporte-info-box">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        <strong>¿Dónde encuentro este número?</strong> El Número de Soporte del DNI
                        se encuentra en el <strong>reverso</strong> de la tarjeta, en la parte inferior.
                        Formato: <strong>3 letras + 6 números</strong> (ej: <code>ABC123456</code>).
                        Es obligatorio para el registro en el sistema SES.MIR del Ministerio del Interior.
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================================
             SECCIÓN 3: DATOS DE CONTACTO
             ================================================================ -->
        <div class="checkin-card">
            <h2 class="section-title">
                <i class="fa-solid fa-address-book"></i> Datos de contacto
            </h2>

            <div class="row g-3">
                <!-- Teléfono -->
                <div class="col-md-5">
                    <label class="form-label" for="telefono">
                        Teléfono móvil <span class="required-mark">*</span>
                    </label>
                    <input type="tel"
                           class="form-control"
                           id="telefono"
                           name="telefono"
                           value="<?= val('telefono', $form_valores) ?>"
                           maxlength="20"
                           autocomplete="tel"
                           placeholder="+34 600 000 000"
                           required>
                </div>

                <!-- Email -->
                <div class="col-md-7">
                    <label class="form-label" for="email">
                        Correo electrónico <span class="required-mark">*</span>
                    </label>
                    <input type="email"
                           class="form-control"
                           id="email"
                           name="email"
                           value="<?= val('email', $form_valores) ?>"
                           maxlength="180"
                           autocomplete="email"
                           placeholder="nombre@correo.com"
                           required>
                </div>
            </div>
        </div>

        <!-- ================================================================
             SECCIÓN 4: DIRECCIÓN
             ================================================================ -->
        <div class="checkin-card">
            <h2 class="section-title">
                <i class="fa-solid fa-location-dot"></i> Dirección de residencia
            </h2>

            <div class="row g-3">
                <!-- Calle -->
                <div class="col-md-8">
                    <label class="form-label" for="direccion_calle">
                        Calle / Vía <span class="required-mark">*</span>
                    </label>
                    <input type="text"
                           class="form-control"
                           id="direccion_calle"
                           name="direccion_calle"
                           value="<?= val('direccion_calle', $form_valores) ?>"
                           maxlength="200"
                           autocomplete="street-address"
                           placeholder="Calle Mayor"
                           required>
                </div>

                <!-- Número -->
                <div class="col-md-4">
                    <label class="form-label" for="direccion_numero">
                        Nº / Piso / Puerta <span class="required-mark">*</span>
                    </label>
                    <input type="text"
                           class="form-control"
                           id="direccion_numero"
                           name="direccion_numero"
                           value="<?= val('direccion_numero', $form_valores) ?>"
                           maxlength="20"
                           placeholder="12, 3ºA"
                           required>
                </div>

                <!-- Provincia -->
                <div class="col-md-5">
                    <label class="form-label" for="provincia">
                        Provincia <span class="required-mark">*</span>
                    </label>
                    <input type="text"
                           class="form-control"
                           id="provincia"
                           name="provincia"
                           value="<?= val('provincia', $form_valores) ?>"
                           maxlength="100"
                           autocomplete="address-level2"
                           required>
                </div>

                <!-- Código postal -->
                <div class="col-md-3">
                    <label class="form-label" for="codigo_postal">
                        Código postal <span class="required-mark">*</span>
                    </label>
                    <input type="text"
                           class="form-control"
                           id="codigo_postal"
                           name="codigo_postal"
                           value="<?= val('codigo_postal', $form_valores) ?>"
                           maxlength="10"
                           autocomplete="postal-code"
                           placeholder="28001"
                           required>
                </div>

                <!-- País -->
                <div class="col-md-4">
                    <label class="form-label" for="pais">
                        País <span class="required-mark">*</span>
                    </label>
                    <input type="text"
                           class="form-control"
                           id="pais"
                           name="pais"
                           value="<?= val('pais', $form_valores, 'España') ?>"
                           maxlength="80"
                           autocomplete="country-name"
                           required>
                </div>
            </div>
        </div>

        <!-- ================================================================
             SECCIÓN 5: DATOS DE ESTANCIA
             ================================================================ -->
        <div class="checkin-card">
            <h2 class="section-title">
                <i class="fa-solid fa-calendar-check"></i> Datos de la estancia
            </h2>

            <div class="row g-3">
                <!-- Fecha de entrada -->
                <div class="col-md-6">
                    <label class="form-label" for="fecha_entrada">
                        Fecha de entrada <span class="required-mark">*</span>
                    </label>
                    <input type="date"
                           class="form-control"
                           id="fecha_entrada"
                           name="fecha_entrada"
                           value="<?= val('fecha_entrada', $form_valores) ?>"
                           required>
                </div>

                <!-- Fecha de salida prevista -->
                <div class="col-md-6">
                    <label class="form-label" for="fecha_salida_prevista">
                        Fecha de salida prevista <span class="required-mark">*</span>
                    </label>
                    <input type="date"
                           class="form-control"
                           id="fecha_salida_prevista"
                           name="fecha_salida_prevista"
                           value="<?= val('fecha_salida_prevista', $form_valores) ?>"
                           required>
                </div>
            </div>
        </div>

        <!-- ================================================================
             AVISO LEGAL Y BOTÓN ENVIAR
             ================================================================ -->
        <div class="checkin-card">
            <div class="form-check mb-3">
                <input class="form-check-input"
                       type="checkbox"
                       id="acepta_rgpd"
                       required>
                <label class="form-check-label" style="font-size:0.85rem;" for="acepta_rgpd">
                    He leído y acepto que mis datos serán tratados conforme al
                    <strong>Reglamento General de Protección de Datos (RGPD)</strong> y
                    al <strong>Real Decreto 933/2021</strong> con el único fin de
                    cumplir la obligación legal de registro de viajeros ante la
                    Secretaría de Estado de Seguridad. <span class="required-mark">*</span>
                </label>
            </div>

            <button type="submit" class="btn-checkin btn">
                <i class="fa-solid fa-paper-plane me-2"></i>
                Enviar registro de check-in
            </button>

            <p class="text-center mt-3 text-muted" style="font-size:0.8rem;">
                <i class="fa-solid fa-lock me-1"></i>
                Conexión segura. Sus datos se transmiten cifrados.
            </p>
        </div>

    </form>
    <?php endif; ?>

    <!-- Pie -->
    <div class="checkin-footer">
        <p>
            <strong><?= esc($alojamiento_nombre) ?></strong> utiliza este sistema de
            check-in digital en cumplimiento del
            <strong>Real Decreto 933/2021</strong> sobre obligaciones de registro
            de viajeros por parte del Ministerio del Interior de España.
        </p>
        <p>Todos los datos son tratados con la máxima confidencialidad.</p>
    </div>

</div><!-- /container -->

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFsgAGFKQHgMRLxkh+MXJqVBRy"
        crossorigin="anonymous"></script>

<script>
/**
 * Gestiona la visibilidad y obligatoriedad del campo Número de Soporte.
 * Solo es obligatorio cuando el tipo de documento es DNI.
 */
function gestionarSoporte(tipo) {
    const input  = document.getElementById('numero_soporte');
    const info   = document.getElementById('soporte-info-box');
    const bloque = document.getElementById('bloque-soporte');
    const mark   = document.getElementById('soporte-required-mark');

    if (tipo === 'DNI') {
        input.required = true;
        info.classList.add('visible');
        bloque.style.display = 'block';
        mark.style.display   = 'inline';
    } else {
        input.required = false;
        input.value    = '';
        info.classList.remove('visible');
        // Ocultamos el campo pero lo mantenemos en el DOM
        if (tipo === '') {
            bloque.style.display = 'none';
        } else {
            bloque.style.display = 'block';
            mark.style.display   = 'none';
        }
    }
}

// Inicializar según el valor que venga del servidor (repoblado del formulario)
document.addEventListener('DOMContentLoaded', function() {
    const tipoDoc = document.getElementById('tipo_documento');
    if (tipoDoc) {
        gestionarSoporte(tipoDoc.value);
    }

    // Validar que fecha de salida > fecha de entrada en cliente también
    const fEntrada = document.getElementById('fecha_entrada');
    const fSalida  = document.getElementById('fecha_salida_prevista');

    if (fEntrada && fSalida) {
        fEntrada.addEventListener('change', function() {
            fSalida.min = this.value;
            if (fSalida.value && fSalida.value <= this.value) {
                fSalida.value = '';
            }
        });
    }

    // Convertir a mayúsculas número de documento y soporte en tiempo real
    ['numero_documento', 'numero_soporte'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', function() {
                const pos = this.selectionStart;
                this.value = this.value.toUpperCase();
                this.setSelectionRange(pos, pos);
            });
        }
    });
});
</script>

</body>
</html>

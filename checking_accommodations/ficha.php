<?php
/**
 * =============================================================================
 * SISTEMA DE CHECK-IN — Ficha detalle de un huésped
 * =============================================================================
 * Archivo  : ficha.php
 * Acceso   : Privado (requiere sesión activa)
 * URL      : ficha.php?id=N
 * Descripción: Muestra todos los datos del registro de un huésped.
 *
 * SEGURIDAD — VALIDACIÓN CRUZADA (IDOR Prevention):
 *   La consulta filtra SIEMPRE por:
 *     WHERE id = :id AND alojamiento_id = :alojamiento_id_sesion
 *
 *   Esto garantiza que aunque un alojamiento manipule el parámetro ?id=
 *   de la URL, NUNCA podrá ver registros de otro alojamiento.
 *   Si el huésped no pertenece al alojamiento → HTTP 403 + log del intento.
 * =============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------------------
// VERIFICACIÓN DE SESIÓN
// ---------------------------------------------------------------------------
requiere_autenticacion();

// ID del alojamiento desde la sesión (NUNCA de la URL o el POST)
$alojamiento_id     = (int)    $_SESSION['alojamiento_id'];
$alojamiento_nombre = (string) ($_SESSION['alojamiento_nombre'] ?? '');

// ---------------------------------------------------------------------------
// VALIDAR PARÁMETRO ?id=
// ---------------------------------------------------------------------------
$huesped_id = (int) ($_GET['id'] ?? 0);

if ($huesped_id <= 0) {
    http_response_code(400);
    mostrar_error_acceso(400, 'ID de huésped no válido.');
    exit;
}

// ---------------------------------------------------------------------------
// CONSULTA CON VALIDACIÓN CRUZADA — CRÍTICO PARA SEGURIDAD MULTI-TENANT
//
// La cláusula "AND alojamiento_id = :alojamiento_id" garantiza que un
// alojamiento NUNCA pueda ver datos de los huéspedes de otro alojamiento,
// aunque conozca o adivine el ID del registro.
// ---------------------------------------------------------------------------
$pdo = obtener_pdo();

$stmt = $pdo->prepare(
    'SELECT * FROM huespedes_registro
     WHERE id = :id
       AND alojamiento_id = :alojamiento_id
     LIMIT 1'
);
$stmt->execute([
    ':id'             => $huesped_id,
    ':alojamiento_id' => $alojamiento_id,  // Siempre de la sesión, nunca de la URL
]);
$huesped = $stmt->fetch();

// ---------------------------------------------------------------------------
// CONTROL DE ACCESO: Si el huésped no existe O no pertenece a este alojamiento
// ---------------------------------------------------------------------------
if (!$huesped) {
    // Log del intento de acceso no autorizado
    error_log(sprintf(
        '[CheckIn-IDOR] Intento de acceso no autorizado — alojamiento_id: %d — huesped_id solicitado: %d — IP: %s',
        $alojamiento_id,
        $huesped_id,
        obtener_ip()
    ));

    http_response_code(403);
    mostrar_error_acceso(403, 'No tienes permiso para ver este registro.');
    exit;
}

// ---------------------------------------------------------------------------
// FUNCIÓN: Mostrar página de error de acceso
// ---------------------------------------------------------------------------
function mostrar_error_acceso(int $codigo, string $mensaje): void
{
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title>Acceso denegado — Check-in</title>
        <link rel="stylesheet"
              href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
        <div class="text-center p-5" style="max-width:450px;">
            <div style="font-size:4rem;"><?= $codigo === 403 ? '🔒' : '⚠️' ?></div>
            <h1 class="h3 mt-3" style="color:#2F5233;">
                <?= $codigo === 403 ? 'Acceso denegado' : 'Solicitud incorrecta' ?>
            </h1>
            <p class="text-muted"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
            <a href="panel.php" class="btn btn-sm mt-2"
               style="background:#2F5233; color:#fff; border-radius:0.5rem;">
                ← Volver al panel
            </a>
        </div>
    </body>
    </html>
    <?php
}

// ---------------------------------------------------------------------------
// FUNCIÓN: Formatear fecha para display (YYYY-MM-DD → DD/MM/YYYY)
// ---------------------------------------------------------------------------
function formato_fecha(string $fecha): string
{
    if (empty($fecha) || $fecha === '0000-00-00') return '—';
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);
    return $dt ? $dt->format('d/m/Y') : esc($fecha);
}

// ---------------------------------------------------------------------------
// FUNCIÓN: Formatear fecha y hora
// ---------------------------------------------------------------------------
function formato_fecha_hora(string $fecha_hora): string
{
    if (empty($fecha_hora)) return '—';
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $fecha_hora);
    return $dt ? $dt->format('d/m/Y \a \l\a\s H:i') : esc($fecha_hora);
}

// Calcular duración de la estancia
$dias_estancia = 0;
if (!empty($huesped['fecha_entrada']) && !empty($huesped['fecha_salida_prevista'])) {
    $f1 = new DateTimeImmutable($huesped['fecha_entrada']);
    $f2 = new DateTimeImmutable($huesped['fecha_salida_prevista']);
    $dias_estancia = max(0, (int) $f1->diff($f2)->days);
}

// Etiqueta del sexo
$sexo_etiqueta = match($huesped['sexo']) {
    'H' => 'Hombre',
    'M' => 'Mujer',
    'X' => 'No binario / Otro',
    default => esc($huesped['sexo']),
};

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- No indexar fichas de huéspedes -->
    <meta name="robots" content="noindex, nofollow">
    <title>Ficha — <?= esc($huesped['nombre'] . ' ' . $huesped['apellidos']) ?></title>

    <!-- Bootstrap 5 CDN -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary:    #2F5233;
            --secondary:  #6B8E6B;
            --accent:     #B8956A;
            --dark:       #1A2E1A;
            --light-bg:   #f4f7f4;
            --border:     #d4e0d4;
            --focus-ring: rgba(47, 82, 51, 0.25);
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--dark);
        }

        /* Barra superior */
        .topbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #fff;
            padding: 0.85rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
            box-shadow: 0 3px 12px rgba(0,0,0,0.15);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-volver {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.4rem 0.9rem;
            border-radius: 0.4rem;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-volver:hover {
            background: rgba(255,255,255,0.25);
            color: #fff;
        }

        /* Cabecera de la ficha */
        .ficha-header {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-wrap: wrap;
            box-shadow: 0 2px 10px rgba(47, 82, 51, 0.07);
        }

        .ficha-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #fff;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(47,82,51,0.25);
        }

        .ficha-header-info h1 {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .ficha-meta {
            font-size: 0.82rem;
            color: #7a8a7a;
        }

        .ficha-meta strong {
            color: var(--primary);
        }

        /* Tarjeta de sección */
        .ficha-seccion {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 0.9rem;
            overflow: hidden;
            margin-bottom: 1.25rem;
            box-shadow: 0 2px 8px rgba(47,82,51,0.05);
        }

        .ficha-seccion-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            padding: 0.75rem 1.25rem;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .ficha-seccion-body {
            padding: 1.25rem;
        }

        /* Campo de dato */
        .dato-item {
            margin-bottom: 1rem;
        }

        .dato-item:last-child {
            margin-bottom: 0;
        }

        .dato-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.2rem;
        }

        .dato-valor {
            font-size: 0.95rem;
            color: var(--dark);
            font-weight: 500;
        }

        .dato-valor.vacio {
            color: #b0bab0;
            font-style: italic;
        }

        /* Badge tipo documento */
        .badge-doc-ficha {
            display: inline-block;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 0.3rem 0.7rem;
            border-radius: 0.4rem;
            margin-right: 0.5rem;
        }
        .badge-dni-f      { background: #e8f0ff; color: #2c5282; }
        .badge-nie-f      { background: #fff8e0; color: #7a5900; }
        .badge-pasaport-f { background: #f0e8ff; color: #5b2c82; }
        .badge-otro-f     { background: #f0f0f0; color: #555; }

        /* Chip de estancia */
        .estancia-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #e8f5e9;
            border: 1px solid var(--border);
            border-radius: 2rem;
            padding: 0.4rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--primary);
        }

        /* Aviso seguridad */
        .aviso-seguridad {
            background: #f8fff8;
            border: 1px solid var(--border);
            border-radius: 0.6rem;
            padding: 0.6rem 1rem;
            font-size: 0.78rem;
            color: #7a8a7a;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        /* Botón imprimir */
        .btn-imprimir {
            background: var(--accent);
            border: none;
            color: #fff;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.45rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .btn-imprimir:hover {
            opacity: 0.85;
            color: #fff;
        }

        @media print {
            .topbar, .btn-imprimir, .aviso-seguridad { display: none !important; }
            body { background: #fff; }
            .ficha-seccion { box-shadow: none; border: 1px solid #ccc; }
        }

        @media (max-width: 576px) {
            .ficha-header { gap: 0.75rem; }
            .ficha-avatar { width: 55px; height: 55px; font-size: 1.6rem; }
        }
    </style>
</head>
<body>

<!-- Barra superior -->
<div class="topbar">
    <div class="topbar-left">
        <span style="font-size:1.3rem;">🏡</span>
        <div>
            <div style="font-size:0.9rem; font-weight:700;"><?= esc($alojamiento_nombre) ?></div>
            <div style="font-size:0.72rem; opacity:0.8;">Ficha de huésped</div>
        </div>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <button class="btn-imprimir btn" onclick="window.print()">
            <i class="fa-solid fa-print me-1"></i> Imprimir
        </button>
        <a href="panel.php" class="btn-volver">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver al panel
        </a>
    </div>
</div>

<div class="container py-4" style="max-width:800px;">

    <!-- =====================================================================
         CABECERA DE LA FICHA
         ===================================================================== -->
    <div class="ficha-header">
        <div class="ficha-avatar">👤</div>
        <div class="ficha-header-info">
            <h1><?= esc($huesped['nombre'] . ' ' . $huesped['apellidos']) ?></h1>
            <div class="ficha-meta">
                <span class="me-3">
                    <i class="fa-solid fa-id-card me-1"></i>
                    <?php
                    $tipo  = $huesped['tipo_documento'];
                    $clase = match($tipo) {
                        'DNI'       => 'badge-dni-f',
                        'NIE'       => 'badge-nie-f',
                        'Pasaporte' => 'badge-pasaport-f',
                        default     => 'badge-otro-f',
                    };
                    ?>
                    <span class="badge-doc-ficha <?= $clase ?>"><?= esc($tipo) ?></span>
                    <strong><?= esc($huesped['numero_documento']) ?></strong>
                </span>
                <span class="me-3">
                    <i class="fa-solid fa-globe me-1"></i>
                    <?= esc($huesped['nacionalidad']) ?>
                </span>
                <span>
                    <i class="fa-solid fa-clock me-1"></i>
                    Registrado el <?= formato_fecha_hora($huesped['created_at']) ?>
                </span>
            </div>
            <?php if ($dias_estancia > 0): ?>
            <div class="mt-2">
                <span class="estancia-chip">
                    <i class="fa-solid fa-moon"></i>
                    <?= $dias_estancia ?> noche<?= $dias_estancia !== 1 ? 's' : '' ?> de estancia
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- =====================================================================
         SECCIÓN 1: DATOS PERSONALES
         ===================================================================== -->
    <div class="ficha-seccion">
        <div class="ficha-seccion-header">
            <i class="fa-solid fa-user"></i> Datos personales
        </div>
        <div class="ficha-seccion-body">
            <div class="row g-3">
                <div class="col-sm-6 col-md-4">
                    <div class="dato-item">
                        <div class="dato-label">Nombre</div>
                        <div class="dato-valor"><?= esc($huesped['nombre']) ?></div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <div class="dato-item">
                        <div class="dato-label">Apellidos</div>
                        <div class="dato-valor"><?= esc($huesped['apellidos']) ?></div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <div class="dato-item">
                        <div class="dato-label">Sexo</div>
                        <div class="dato-valor"><?= esc($sexo_etiqueta) ?></div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <div class="dato-item">
                        <div class="dato-label">Fecha de nacimiento</div>
                        <div class="dato-valor"><?= formato_fecha($huesped['fecha_nacimiento']) ?></div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <div class="dato-item">
                        <div class="dato-label">Nacionalidad</div>
                        <div class="dato-valor"><?= esc($huesped['nacionalidad']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         SECCIÓN 2: DOCUMENTO DE IDENTIDAD
         ===================================================================== -->
    <div class="ficha-seccion">
        <div class="ficha-seccion-header">
            <i class="fa-solid fa-id-card"></i> Documento de identidad
        </div>
        <div class="ficha-seccion-body">
            <div class="row g-3">
                <div class="col-sm-6 col-md-3">
                    <div class="dato-item">
                        <div class="dato-label">Tipo</div>
                        <div class="dato-valor">
                            <span class="badge-doc-ficha <?= $clase ?>"><?= esc($tipo) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="dato-item">
                        <div class="dato-label">Número</div>
                        <div class="dato-valor" style="font-family:monospace; font-size:1.05rem;">
                            <?= esc($huesped['numero_documento']) ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="dato-item">
                        <div class="dato-label">Fecha de expedición</div>
                        <div class="dato-valor"><?= formato_fecha($huesped['fecha_expedicion_doc']) ?></div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="dato-item">
                        <div class="dato-label">Nº de soporte (DNI)</div>
                        <div class="dato-valor <?= empty($huesped['numero_soporte']) ? 'vacio' : '' ?>"
                             style="font-family:monospace;">
                            <?= !empty($huesped['numero_soporte']) ? esc($huesped['numero_soporte']) : 'No aplica' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         SECCIÓN 3: DATOS DE CONTACTO
         ===================================================================== -->
    <div class="ficha-seccion">
        <div class="ficha-seccion-header">
            <i class="fa-solid fa-address-book"></i> Datos de contacto
        </div>
        <div class="ficha-seccion-body">
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="dato-item">
                        <div class="dato-label">Teléfono móvil</div>
                        <div class="dato-valor">
                            <a href="tel:<?= esc($huesped['telefono']) ?>"
                               style="color:var(--primary); text-decoration:none;">
                                <i class="fa-solid fa-phone me-1" style="color:var(--secondary);"></i>
                                <?= esc($huesped['telefono']) ?>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="dato-item">
                        <div class="dato-label">Correo electrónico</div>
                        <div class="dato-valor">
                            <a href="mailto:<?= esc($huesped['email']) ?>"
                               style="color:var(--primary); text-decoration:none;">
                                <i class="fa-solid fa-envelope me-1" style="color:var(--secondary);"></i>
                                <?= esc($huesped['email']) ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         SECCIÓN 4: DIRECCIÓN
         ===================================================================== -->
    <div class="ficha-seccion">
        <div class="ficha-seccion-header">
            <i class="fa-solid fa-location-dot"></i> Dirección de residencia
        </div>
        <div class="ficha-seccion-body">
            <div class="row g-3">
                <div class="col-sm-8">
                    <div class="dato-item">
                        <div class="dato-label">Calle / Vía</div>
                        <div class="dato-valor"><?= esc($huesped['direccion_calle']) ?></div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="dato-item">
                        <div class="dato-label">Nº / Piso / Puerta</div>
                        <div class="dato-valor"><?= esc($huesped['direccion_numero']) ?></div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="dato-item">
                        <div class="dato-label">Provincia</div>
                        <div class="dato-valor"><?= esc($huesped['provincia']) ?></div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="dato-item">
                        <div class="dato-label">Código postal</div>
                        <div class="dato-valor"><?= esc($huesped['codigo_postal']) ?></div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="dato-item">
                        <div class="dato-label">País</div>
                        <div class="dato-valor"><?= esc($huesped['pais']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         SECCIÓN 5: DATOS DE ESTANCIA
         ===================================================================== -->
    <div class="ficha-seccion">
        <div class="ficha-seccion-header">
            <i class="fa-solid fa-calendar-check"></i> Datos de la estancia
        </div>
        <div class="ficha-seccion-body">
            <div class="row g-3 align-items-center">
                <div class="col-sm-4">
                    <div class="dato-item">
                        <div class="dato-label">Fecha de entrada</div>
                        <div class="dato-valor">
                            <i class="fa-solid fa-plane-arrival me-1" style="color:var(--secondary);"></i>
                            <?= formato_fecha($huesped['fecha_entrada']) ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="dato-item">
                        <div class="dato-label">Fecha de salida prevista</div>
                        <div class="dato-valor">
                            <i class="fa-solid fa-plane-departure me-1" style="color:var(--secondary);"></i>
                            <?= formato_fecha($huesped['fecha_salida_prevista']) ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="dato-item">
                        <div class="dato-label">Duración</div>
                        <div class="dato-valor">
                            <span class="estancia-chip">
                                <i class="fa-solid fa-moon"></i>
                                <?= $dias_estancia ?> noche<?= $dias_estancia !== 1 ? 's' : '' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         AVISO DE SEGURIDAD Y METADATOS
         ===================================================================== -->
    <div class="ficha-seccion">
        <div class="ficha-seccion-header" style="background: linear-gradient(135deg, #555, #777);">
            <i class="fa-solid fa-shield-halved"></i> Metadatos del registro
        </div>
        <div class="ficha-seccion-body">
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="dato-item">
                        <div class="dato-label">Fecha y hora de registro</div>
                        <div class="dato-valor"><?= formato_fecha_hora($huesped['created_at']) ?></div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="dato-item">
                        <div class="dato-label">ID de registro interno</div>
                        <div class="dato-valor" style="font-family:monospace; color:#9aaa9a;">
                            #<?= (int) $huesped['id'] ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="aviso-seguridad mt-3">
                <i class="fa-solid fa-lock mt-1 flex-shrink-0" style="color:var(--secondary);"></i>
                <span>
                    Este registro pertenece exclusivamente a
                    <strong style="color:var(--primary);"><?= esc($alojamiento_nombre) ?></strong>.
                    Los datos han sido validados con acceso cruzado (alojamiento_id + registro_id)
                    y están protegidos conforme al RGPD y el Real Decreto 933/2021.
                </span>
            </div>
        </div>
    </div>

    <!-- Navegación inferior -->
    <div class="d-flex justify-content-between align-items-center py-3">
        <a href="panel.php" class="btn btn-sm"
           style="background:var(--primary); color:#fff; border-radius:0.5rem; font-weight:600; padding:0.5rem 1.25rem;">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver al panel
        </a>
        <button onclick="window.print()" class="btn btn-sm"
                style="background:var(--accent); color:#fff; border-radius:0.5rem; font-weight:600; padding:0.5rem 1.25rem; border:none;">
            <i class="fa-solid fa-print me-1"></i> Imprimir ficha
        </button>
    </div>

</div><!-- /container -->

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFsgAGFKQHgMRLxkh+MXJqVBRy"
        crossorigin="anonymous"></script>

</body>
</html>

<?php
/**
 * =============================================================================
 * SISTEMA DE CHECK-IN — Panel privado del alojamiento
 * =============================================================================
 * Archivo  : panel.php
 * Acceso   : Privado (requiere sesión activa)
 * Descripción: Panel de administración donde el alojamiento visualiza el
 *              listado completo de sus huéspedes registrados.
 *
 * SEGURIDAD MULTI-TENANT (CRÍTICO):
 *   - requiere_autenticacion() verifica $_SESSION['alojamiento_id'] al inicio.
 *   - TODAS las consultas SQL filtran por alojamiento_id = $_SESSION['alojamiento_id'].
 *   - El ID del alojamiento NUNCA se obtiene de la URL o del POST.
 *   - noindex/nofollow — no indexar en Google Search Console.
 * =============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------------------
// VERIFICACIÓN DE SESIÓN — Redirige a login si no está autenticado
// ---------------------------------------------------------------------------
requiere_autenticacion();

// Obtener datos del alojamiento desde la sesión (NUNCA desde la URL)
$alojamiento_id     = (int)    $_SESSION['alojamiento_id'];
$alojamiento_nombre = (string) ($_SESSION['alojamiento_nombre'] ?? '');

$pdo = obtener_pdo();

// ---------------------------------------------------------------------------
// PAGINACIÓN
// ---------------------------------------------------------------------------
$por_pagina  = 20;
$pagina_actual = max(1, (int) ($_GET['pagina'] ?? 1));
$offset      = ($pagina_actual - 1) * $por_pagina;

// ---------------------------------------------------------------------------
// BÚSQUEDA (opcional)
// Solo busca dentro de los huéspedes de ESTE alojamiento
// ---------------------------------------------------------------------------
$busqueda     = trim($_GET['buscar'] ?? '');
$where_buscar = '';
$params_count = [$alojamiento_id];
$params_lista = [$alojamiento_id];

if (!empty($busqueda)) {
    $where_buscar   = ' AND (nombre LIKE ? OR apellidos LIKE ? OR numero_documento LIKE ?)';
    $term           = '%' . $busqueda . '%';
    $params_count   = array_merge($params_count, [$term, $term, $term]);
    $params_lista   = array_merge($params_lista,  [$term, $term, $term]);
}

// ---------------------------------------------------------------------------
// TOTAL DE REGISTROS (para paginación y estadísticas)
// SIEMPRE filtrado por alojamiento_id de la sesión
// ---------------------------------------------------------------------------
$stmt_total = $pdo->prepare(
    'SELECT COUNT(*) FROM huespedes_registro WHERE alojamiento_id = ?' . $where_buscar
);
$stmt_total->execute($params_count);
$total_registros = (int) $stmt_total->fetchColumn();
$total_paginas   = max(1, (int) ceil($total_registros / $por_pagina));

// Asegurar que la página actual no supere el total
$pagina_actual = min($pagina_actual, $total_paginas);

// ---------------------------------------------------------------------------
// ESTADÍSTICAS DEL ALOJAMIENTO
// SIEMPRE filtradas por alojamiento_id de la sesión
// ---------------------------------------------------------------------------

// Huéspedes este mes
$stmt_mes = $pdo->prepare(
    'SELECT COUNT(*) FROM huespedes_registro
     WHERE alojamiento_id = ?
       AND YEAR(created_at) = YEAR(CURDATE())
       AND MONTH(created_at) = MONTH(CURDATE())'
);
$stmt_mes->execute([$alojamiento_id]);
$huespedes_mes = (int) $stmt_mes->fetchColumn();

// Huéspedes actualmente en casa (entrada <= hoy <= salida prevista)
$stmt_activos = $pdo->prepare(
    'SELECT COUNT(*) FROM huespedes_registro
     WHERE alojamiento_id = ?
       AND fecha_entrada <= CURDATE()
       AND fecha_salida_prevista >= CURDATE()'
);
$stmt_activos->execute([$alojamiento_id]);
$huespedes_activos = (int) $stmt_activos->fetchColumn();

// ---------------------------------------------------------------------------
// LISTADO DE HUÉSPEDES PAGINADO
// SIEMPRE filtrado por alojamiento_id de la sesión
// Ordenado por fecha de registro más reciente
// ---------------------------------------------------------------------------
$params_lista[] = $por_pagina;
$params_lista[] = $offset;

$stmt_lista = $pdo->prepare(
    'SELECT id, nombre, apellidos, tipo_documento, numero_documento,
            fecha_entrada, fecha_salida_prevista, created_at
     FROM huespedes_registro
     WHERE alojamiento_id = ?' . $where_buscar . '
     ORDER BY created_at DESC
     LIMIT ? OFFSET ?'
);
$stmt_lista->execute($params_lista);
$huespedes = $stmt_lista->fetchAll();

// ---------------------------------------------------------------------------
// OBTENER TOKEN PÚBLICO DEL ALOJAMIENTO (para mostrar el enlace de check-in)
// SIEMPRE filtrado por alojamiento_id de la sesión
// ---------------------------------------------------------------------------
$stmt_token = $pdo->prepare(
    'SELECT token_publico FROM accommodations WHERE id = ? LIMIT 1'
);
$stmt_token->execute([$alojamiento_id]);
$token_row     = $stmt_token->fetch();
$token_publico = $token_row['token_publico'] ?? '';
$url_checkin   = CHECKIN_APP_URL . '/checkin.php?token=' . $token_publico;

// ---------------------------------------------------------------------------
// MENSAJE DE SESIÓN CERRADA (desde logout.php)
// ---------------------------------------------------------------------------
$logout_ok = isset($_GET['logout']) && $_GET['logout'] === '1';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- No indexar el panel privado en Google -->
    <meta name="robots" content="noindex, nofollow">
    <title>Panel — <?= esc($alojamiento_nombre) ?></title>

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
            padding: 0.9rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
            box-shadow: 0 3px 12px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .topbar-brand .icon {
            font-size: 1.6rem;
        }

        .topbar-brand .nombre {
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .topbar-brand .subtitulo {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .btn-logout {
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

        .btn-logout:hover {
            background: rgba(255,255,255,0.25);
            color: #fff;
        }

        /* Tarjetas de estadísticas */
        .stat-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 0.9rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 2px 10px rgba(47, 82, 51, 0.06);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-card .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .stat-card .stat-valor {
            font-size: 1.9rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
        }

        .stat-card .stat-label {
            font-size: 0.82rem;
            color: #7a8a7a;
            margin-top: 0.15rem;
        }

        /* Enlace de check-in */
        .checkin-link-card {
            background: #fff;
            border: 2px solid var(--border);
            border-radius: 0.9rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 2px 10px rgba(47, 82, 51, 0.06);
        }

        .checkin-link-card .link-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.6rem;
        }

        .input-link {
            background: var(--light-bg);
            border: 1.5px solid var(--border);
            border-radius: 0.5rem;
            padding: 0.55rem 0.9rem;
            font-size: 0.82rem;
            color: var(--dark);
            font-family: monospace;
            width: 100%;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn-copiar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: #fff;
            font-size: 0.83rem;
            font-weight: 600;
            padding: 0.52rem 1rem;
            border-radius: 0.5rem;
            white-space: nowrap;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .btn-copiar:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(47,82,51,0.3);
            color: #fff;
        }

        /* Tabla de huéspedes */
        .panel-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 0.9rem;
            box-shadow: 0 2px 10px rgba(47, 82, 51, 0.06);
            overflow: hidden;
        }

        .panel-card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .panel-card-header h2 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }

        .table-huespedes {
            margin: 0;
        }

        .table-huespedes thead th {
            background: #f4f7f4;
            color: var(--primary);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--border);
            padding: 0.7rem 1rem;
            white-space: nowrap;
        }

        .table-huespedes tbody td {
            padding: 0.75rem 1rem;
            font-size: 0.88rem;
            border-bottom: 1px solid #f0f4f0;
            vertical-align: middle;
        }

        .table-huespedes tbody tr:hover {
            background: #f8faf8;
        }

        .badge-doc {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.25rem 0.55rem;
            border-radius: 0.3rem;
        }

        .badge-dni      { background: #e8f0ff; color: #2c5282; }
        .badge-nie      { background: #fff8e0; color: #7a5900; }
        .badge-pasaport { background: #f0e8ff; color: #5b2c82; }
        .badge-otro     { background: #f0f0f0; color: #555; }

        .btn-ver-ficha {
            background: var(--primary);
            color: #fff;
            border: none;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 0.3rem 0.75rem;
            border-radius: 0.4rem;
            text-decoration: none;
            transition: background 0.2s;
            white-space: nowrap;
        }

        .btn-ver-ficha:hover {
            background: var(--dark);
            color: #fff;
        }

        /* Paginación */
        .paginacion-wrap .page-link {
            color: var(--primary);
            border-color: var(--border);
            font-size: 0.88rem;
        }

        .paginacion-wrap .page-item.active .page-link {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .paginacion-wrap .page-link:focus {
            box-shadow: 0 0 0 3px var(--focus-ring);
        }

        /* Buscador */
        .buscador-input {
            border: 1.5px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.88rem;
            padding: 0.45rem 0.9rem;
        }

        .buscador-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--focus-ring);
        }

        .btn-buscar {
            background: var(--primary);
            border: none;
            color: #fff;
            padding: 0.45rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.88rem;
        }

        .btn-buscar:hover {
            background: var(--dark);
            color: #fff;
        }

        /* Vacío */
        .tabla-vacia {
            text-align: center;
            padding: 3rem 1rem;
            color: #9aaa9a;
        }

        .tabla-vacia .icon-empty {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .topbar {
                padding: 0.75rem 1rem;
            }
            .table-responsive {
                font-size: 0.82rem;
            }
        }
    </style>
</head>
<body>

<!-- =========================================================================
     BARRA SUPERIOR
     ========================================================================= -->
<div class="topbar">
    <div class="topbar-brand">
        <span class="icon">🏡</span>
        <div>
            <div class="nombre"><?= esc($alojamiento_nombre) ?></div>
            <div class="subtitulo">Panel de gestión de check-in</div>
        </div>
    </div>
    <a href="logout.php" class="btn-logout" onclick="return confirm('¿Cerrar sesión?')">
        <i class="fa-solid fa-right-from-bracket me-1"></i> Cerrar sesión
    </a>
</div>

<div class="container-fluid px-3 px-md-4 py-4" style="max-width:1200px;">

    <!-- =====================================================================
         ESTADÍSTICAS
         ===================================================================== -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f0e8;">📋</div>
                <div>
                    <div class="stat-valor"><?= number_format($total_registros) ?></div>
                    <div class="stat-label">Total registros</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff3e0;">📅</div>
                <div>
                    <div class="stat-valor"><?= number_format($huespedes_mes) ?></div>
                    <div class="stat-label">Este mes</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f5e9;">🏠</div>
                <div>
                    <div class="stat-valor"><?= number_format($huespedes_activos) ?></div>
                    <div class="stat-label">En casa hoy</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f3e8ff;">📄</div>
                <div>
                    <div class="stat-valor"><?= $total_paginas ?></div>
                    <div class="stat-label">Páginas totales</div>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         ENLACE DE CHECK-IN PÚBLICO
         ===================================================================== -->
    <?php if (!empty($token_publico)): ?>
    <div class="checkin-link-card mb-4">
        <div class="link-title">
            <i class="fa-solid fa-link me-1"></i>
            Enlace de check-in para compartir con sus huéspedes
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <input type="text"
                   id="input-url-checkin"
                   class="input-link"
                   value="<?= esc($url_checkin) ?>"
                   readonly
                   onclick="this.select()">
            <button class="btn-copiar btn" onclick="copiarEnlace()" id="btn-copiar">
                <i class="fa-solid fa-copy me-1"></i> Copiar enlace
            </button>
        </div>
        <p class="mb-0 mt-2" style="font-size:0.78rem; color:#7a8a7a;">
            <i class="fa-solid fa-circle-info me-1"></i>
            Comparte este enlace con tus huéspedes para que rellenen su ficha de registro.
            Cada huésped mayor de 14 años debe rellenar su propio formulario.
        </p>
    </div>
    <?php endif; ?>

    <!-- =====================================================================
         TABLA DE HUÉSPEDES
         ===================================================================== -->
    <div class="panel-card">

        <!-- Encabezado de la tarjeta -->
        <div class="panel-card-header">
            <h2>
                <i class="fa-solid fa-users me-2"></i>
                Huéspedes registrados
            </h2>
            <!-- Buscador -->
            <form method="GET" action="panel.php" class="d-flex gap-2 align-items-center" role="search">
                <input type="search"
                       name="buscar"
                       class="buscador-input"
                       placeholder="Buscar por nombre, apellidos o documento..."
                       value="<?= esc($busqueda) ?>"
                       style="min-width:220px;"
                       aria-label="Buscar huésped">
                <button type="submit" class="btn-buscar btn">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <?php if (!empty($busqueda)): ?>
                <a href="panel.php" class="btn btn-outline-light btn-sm">
                    <i class="fa-solid fa-xmark"></i>
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Resultados de búsqueda -->
        <?php if (!empty($busqueda)): ?>
        <div class="px-3 py-2" style="background:#fffbf0; border-bottom:1px solid var(--border); font-size:0.83rem;">
            <i class="fa-solid fa-filter me-1" style="color:var(--accent);"></i>
            Mostrando resultados para: <strong>"<?= esc($busqueda) ?>"</strong>
            — <?= $total_registros ?> registro(s) encontrado(s).
            <a href="panel.php" style="color:var(--primary); margin-left:0.5rem;">Limpiar búsqueda</a>
        </div>
        <?php endif; ?>

        <!-- Tabla -->
        <div class="table-responsive">
            <?php if (empty($huespedes)): ?>
            <div class="tabla-vacia">
                <div class="icon-empty">📭</div>
                <?php if (!empty($busqueda)): ?>
                    <p>No se encontraron huéspedes que coincidan con la búsqueda.</p>
                <?php else: ?>
                    <p>Aún no hay huéspedes registrados.</p>
                    <p style="font-size:0.83rem;">Comparte el enlace de check-in con tus huéspedes para comenzar.</p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <table class="table table-huespedes table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre completo</th>
                        <th>Documento</th>
                        <th>Entrada</th>
                        <th>Salida prevista</th>
                        <th>Registrado el</th>
                        <th class="text-center">Ficha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($huespedes as $i => $h): ?>
                    <tr>
                        <td style="color:#9aaa9a; font-size:0.78rem;">
                            <?= $offset + $i + 1 ?>
                        </td>
                        <td>
                            <strong><?= esc($h['nombre'] . ' ' . $h['apellidos']) ?></strong>
                        </td>
                        <td>
                            <?php
                            $tipo = $h['tipo_documento'];
                            $clase_badge = match($tipo) {
                                'DNI'       => 'badge-dni',
                                'NIE'       => 'badge-nie',
                                'Pasaporte' => 'badge-pasaport',
                                default     => 'badge-otro',
                            };
                            ?>
                            <span class="badge-doc <?= $clase_badge ?>"><?= esc($tipo) ?></span>
                            <span style="font-size:0.82rem; margin-left:0.3rem;">
                                <?= esc($h['numero_documento']) ?>
                            </span>
                        </td>
                        <td><?= esc(date('d/m/Y', strtotime($h['fecha_entrada']))) ?></td>
                        <td><?= esc(date('d/m/Y', strtotime($h['fecha_salida_prevista']))) ?></td>
                        <td style="font-size:0.8rem; color:#7a8a7a;">
                            <?= esc(date('d/m/Y H:i', strtotime($h['created_at']))) ?>
                        </td>
                        <td class="text-center">
                            <a href="ficha.php?id=<?= (int) $h['id'] ?>"
                               class="btn-ver-ficha">
                                <i class="fa-solid fa-eye me-1"></i>Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
        <div class="px-3 py-3 border-top" style="border-color:var(--border)!important;">
            <nav aria-label="Paginación de huéspedes" class="paginacion-wrap">
                <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap gap-1">

                    <!-- Anterior -->
                    <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?>&buscar=<?= urlencode($busqueda) ?>">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                    </li>

                    <!-- Números de página -->
                    <?php
                    $rango = 2; // páginas a cada lado de la actual
                    for ($p = max(1, $pagina_actual - $rango); $p <= min($total_paginas, $pagina_actual + $rango); $p++):
                    ?>
                    <li class="page-item <?= $p === $pagina_actual ? 'active' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $p ?>&buscar=<?= urlencode($busqueda) ?>">
                            <?= $p ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <!-- Siguiente -->
                    <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?>&buscar=<?= urlencode($busqueda) ?>">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </li>

                </ul>
            </nav>
            <p class="text-center text-muted mt-2 mb-0" style="font-size:0.78rem;">
                Página <?= $pagina_actual ?> de <?= $total_paginas ?>
                (<?= number_format($total_registros) ?> registro<?= $total_registros !== 1 ? 's' : '' ?> en total)
            </p>
        </div>
        <?php endif; ?>

    </div><!-- /panel-card -->

    <!-- Pie -->
    <div class="text-center py-4" style="font-size:0.75rem; color:#9aaa9a;">
        <i class="fa-solid fa-shield-halved me-1"></i>
        Panel seguro — Los datos mostrados pertenecen exclusivamente a
        <strong style="color:var(--primary);"><?= esc($alojamiento_nombre) ?></strong>.
        Sistema conforme al RGPD y al RD 933/2021.
    </div>

</div><!-- /container -->

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFsgAGFKQHgMRLxkh+MXJqVBRy"
        crossorigin="anonymous"></script>

<script>
/**
 * Copia el enlace de check-in al portapapeles y muestra confirmación visual.
 */
async function copiarEnlace() {
    const input = document.getElementById('input-url-checkin');
    const btn   = document.getElementById('btn-copiar');

    try {
        await navigator.clipboard.writeText(input.value);
        const textoOriginal = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> ¡Copiado!';
        btn.style.background = '#2e7d32';
        setTimeout(() => {
            btn.innerHTML = textoOriginal;
            btn.style.background = '';
        }, 2500);
    } catch (err) {
        // Fallback para navegadores sin soporte de clipboard API
        input.select();
        document.execCommand('copy');
        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> ¡Copiado!';
        setTimeout(() => {
            btn.innerHTML = '<i class="fa-solid fa-copy me-1"></i> Copiar enlace';
        }, 2500);
    }
}
</script>

</body>
</html>

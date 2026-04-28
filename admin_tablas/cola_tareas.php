<?php
/**
 * Panel de Moderación — Cola de Tareas
 * admin_tablas/cola_tareas.php
 */
require_once 'db.php';

// ─── Acciones POST ────────────────────────────────────────────
$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id     = intval($_POST['id'] ?? 0);
    $ids    = $_POST['ids'] ?? [];

    try {
        switch ($accion) {

            case 'aprobar':
                $pdo->prepare("UPDATE cola_tareas SET estado='pendiente' WHERE id=? AND estado='moderacion'")->execute([$id]);
                $mensaje = "✅ Tarea #$id aprobada y lista para procesar.";
                $tipoMensaje = 'success';
                break;

            case 'cancelar':
                $pdo->prepare("UPDATE cola_tareas SET estado='cancelada' WHERE id=? AND estado IN ('moderacion','pendiente','error')")->execute([$id]);
                $mensaje = "🚫 Tarea #$id cancelada.";
                $tipoMensaje = 'warning';
                break;

            case 'reintentar':
                $pdo->prepare("UPDATE cola_tareas SET estado='pendiente', intentos=0, error_msg=NULL, disponible_desde=NOW() WHERE id=? AND estado='error'")->execute([$id]);
                $mensaje = "🔄 Tarea #$id reactivada para reintento.";
                $tipoMensaje = 'info';
                break;

            case 'aprobar_todos':
                $n = $pdo->exec("UPDATE cola_tareas SET estado='pendiente' WHERE estado='moderacion'");
                $mensaje = "✅ $n tareas aprobadas y listas para procesar.";
                $tipoMensaje = 'success';
                break;

            case 'cancelar_seleccion':
                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $pdo->prepare("UPDATE cola_tareas SET estado='cancelada' WHERE id IN ($placeholders) AND estado IN ('moderacion','pendiente','error')");
                    $stmt->execute(array_map('intval', $ids));
                    $n = $stmt->rowCount();
                    $mensaje = "🚫 $n tareas canceladas.";
                    $tipoMensaje = 'warning';
                }
                break;

            case 'procesar_cola':
                // Llamar al procesador
                $token = 'RutasRurales_Cola_2026_$ecret';
                $url = 'https://rutasrurales.io/api/procesar_cola.php?token=' . urlencode($token);
                $ctx = stream_context_create(['http' => ['timeout' => 30, 'ignore_errors' => true]]);
                $resp = @file_get_contents($url, false, $ctx);
                $data = $resp ? json_decode($resp, true) : null;
                if ($data && isset($data['completadas'])) {
                    $mensaje = "▶️ Cola procesada: {$data['completadas']} completadas, {$data['errores']} errores ({$data['tiempo_ms']}ms)";
                    $tipoMensaje = $data['errores'] > 0 ? 'warning' : 'success';
                } else {
                    $mensaje = "⚠️ No se pudo conectar con el procesador. Respuesta: " . htmlspecialchars(substr($resp ?? 'sin respuesta', 0, 200));
                    $tipoMensaje = 'danger';
                }
                break;

            case 'limpiar_completadas':
                $n = $pdo->exec("DELETE FROM cola_tareas WHERE estado IN ('completada','cancelada') AND procesada_en < NOW() - INTERVAL 7 DAY");
                $mensaje = "🧹 $n tareas antiguas eliminadas (completadas/canceladas > 7 días).";
                $tipoMensaje = 'info';
                break;
        }
    } catch (Exception $e) {
        $mensaje = "❌ Error: " . htmlspecialchars($e->getMessage());
        $tipoMensaje = 'danger';
    }
}

// ─── Filtros GET ──────────────────────────────────────────────
$filtroEstado = $_GET['estado'] ?? 'moderacion';
$filtroTipo   = $_GET['tipo'] ?? '';
$pagina       = max(1, intval($_GET['p'] ?? 1));
$porPagina    = 20;
$offset       = ($pagina - 1) * $porPagina;

$estadosValidos = ['moderacion', 'pendiente', 'procesando', 'completada', 'error', 'cancelada', 'todos'];
if (!in_array($filtroEstado, $estadosValidos)) $filtroEstado = 'moderacion';

// ─── Contadores por estado ────────────────────────────────────
$contadores = [];
try {
    $stmt = $pdo->query("SELECT estado, COUNT(*) as n FROM cola_tareas GROUP BY estado");
    foreach ($stmt->fetchAll() as $row) {
        $contadores[$row['estado']] = $row['n'];
    }
} catch (Exception $e) {
    $contadores = [];
    $errorTabla = $e->getMessage();
}

$totalGeneral = array_sum($contadores);

// ─── Detectar columnas disponibles en cola_tareas ────────────
$columnasDisponibles = [];
try {
    $cols = $pdo->query("SHOW COLUMNS FROM cola_tareas")->fetchAll(PDO::FETCH_COLUMN);
    $columnasDisponibles = $cols;
} catch (Exception $e) {}

$tieneReglaId     = in_array('regla_id', $columnasDisponibles);
$tienePlantillaId = in_array('plantilla_id', $columnasDisponibles);
$tienePrioridad   = in_array('prioridad', $columnasDisponibles);
$tieneTipoTarea   = in_array('tipo_tarea', $columnasDisponibles);
$tieneEntidadTipo = in_array('entidad_tipo', $columnasDisponibles);

// ─── Consulta principal ───────────────────────────────────────
$tareas = [];
$totalFiltrado = 0;

if (!isset($errorTabla)) {
    try {
        $where = $filtroEstado !== 'todos' ? "WHERE ct.estado = " . $pdo->quote($filtroEstado) : "WHERE 1=1";
        if ($filtroTipo && $tieneTipoTarea) $where .= " AND ct.tipo_tarea = " . $pdo->quote($filtroTipo);

        $totalFiltrado = $pdo->query("SELECT COUNT(*) FROM cola_tareas ct $where")->fetchColumn();

        // Construir SELECT según columnas disponibles
        $selectExtra = '';
        $joinExtra   = '';
        if ($tieneReglaId) {
            $selectExtra .= ", rn.nombre AS regla_nombre";
            $joinExtra   .= " LEFT JOIN reglas_notificacion rn ON rn.id = ct.regla_id";
        }
        if ($tienePlantillaId) {
            $selectExtra .= ", pm.nombre AS plantilla_nombre, pm.canal";
            $joinExtra   .= " LEFT JOIN plantillas_mensaje pm ON pm.id = ct.plantilla_id";
        }

        $orderBy = $tienePrioridad ? "ct.prioridad ASC, ct.creada_en DESC" : "ct.creada_en DESC";

        $stmt = $pdo->query("
            SELECT ct.* $selectExtra
            FROM cola_tareas ct
            $joinExtra
            $where
            ORDER BY $orderBy
            LIMIT $porPagina OFFSET $offset
        ");
        $tareas = $stmt->fetchAll();
    } catch (Exception $e) {
        $errorConsulta = $e->getMessage();
    }
}

$totalPaginas = $totalFiltrado > 0 ? ceil($totalFiltrado / $porPagina) : 1;

// ─── Reglas para el panel de configuración ───────────────────
$reglas = [];
try {
    $reglas = $pdo->query("SELECT * FROM reglas_notificacion ORDER BY tabla_origen, nombre")->fetchAll();
} catch (Exception $e) { }

// ─── Helpers ──────────────────────────────────────────────────
function badgeEstado(string $estado): string {
    $map = [
        'pendiente'   => 'bg-primary',
        'moderacion'  => 'bg-warning text-dark',
        'procesando'  => 'bg-info text-dark',
        'completada'  => 'bg-success',
        'error'       => 'bg-danger',
        'cancelada'   => 'bg-secondary',
    ];
    $cls = $map[$estado] ?? 'bg-secondary';
    return "<span class='badge $cls'>$estado</span>";
}

function iconoCanal(?string $canal): string {
    return match($canal) {
        'email'    => '📧',
        'push'     => '🔔',
        'sms'      => '💬',
        'interno'  => '🏠',
        default    => '❓'
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cola de Tareas — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-size: 0.9rem; }
        .badge-contador { font-size: 0.75rem; }
        .payload-pre { font-size: 0.75rem; max-height: 80px; overflow-y: auto; background: #f1f3f5; border-radius: 4px; padding: 4px 8px; white-space: pre-wrap; word-break: break-all; }
        .tab-estado.active { font-weight: bold; }
        .table th { font-size: 0.8rem; white-space: nowrap; }
        .table td { vertical-align: middle; }
        .acciones-btn { white-space: nowrap; }
        .regla-badge { font-size: 0.7rem; }
        .card-contador { cursor: pointer; transition: transform .1s; }
        .card-contador:hover { transform: translateY(-2px); }
        .section-title { border-left: 4px solid #0d6efd; padding-left: 10px; margin-bottom: 1rem; }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container-fluid px-4">

    <!-- Título -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">⚙️ Cola de Tareas</h4>
        <div class="d-flex gap-2">
            <form method="post" class="d-inline">
                <input type="hidden" name="accion" value="procesar_cola">
                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Procesar todas las tareas pendientes ahora?')">
                    ▶️ Procesar cola ahora
                </button>
            </form>
            <form method="post" class="d-inline">
                <input type="hidden" name="accion" value="limpiar_completadas">
                <button type="submit" class="btn btn-outline-secondary btn-sm" onclick="return confirm('¿Eliminar tareas completadas/canceladas de más de 7 días?')">
                    🧹 Limpiar antiguas
                </button>
            </form>
        </div>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show py-2" role="alert">
        <?= $mensaje ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($errorTabla)): ?>
    <div class="alert alert-danger">
        <strong>⚠️ Las tablas del sistema no existen aún.</strong><br>
        Ejecuta primero los scripts SQL del PASO 1 al PASO 5 en phpMyAdmin.<br>
        <small class="text-muted"><?= htmlspecialchars($errorTabla) ?></small>
    </div>
    <?php else: ?>

    <?php if (!$tieneReglaId): ?>
    <div class="alert alert-warning alert-dismissible fade show py-2">
        <strong>⚠️ Tabla <code>cola_tareas</code> en versión antigua.</strong>
        Funciona en modo básico. Para activar todas las funciones (reglas, plantillas, moderación avanzada),
        ejecuta <strong>PASO1_tablas.sql</strong> en phpMyAdmin para recrear las tablas con la nueva estructura.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Contadores por estado -->
    <div class="row g-2 mb-3">
        <?php
        $estadoInfo = [
            'moderacion' => ['color' => 'warning', 'icon' => '⏳', 'label' => 'Moderación'],
            'pendiente'  => ['color' => 'primary',  'icon' => '🕐', 'label' => 'Pendientes'],
            'procesando' => ['color' => 'info',     'icon' => '⚡', 'label' => 'Procesando'],
            'completada' => ['color' => 'success',  'icon' => '✅', 'label' => 'Completadas'],
            'error'      => ['color' => 'danger',   'icon' => '❌', 'label' => 'Errores'],
            'cancelada'  => ['color' => 'secondary','icon' => '🚫', 'label' => 'Canceladas'],
        ];
        foreach ($estadoInfo as $est => $info):
            $n = $contadores[$est] ?? 0;
            $activo = $filtroEstado === $est ? 'border-3' : '';
        ?>
        <div class="col-6 col-md-2">
            <a href="?estado=<?= $est ?>" class="text-decoration-none">
                <div class="card card-contador border-<?= $info['color'] ?> <?= $activo ?> text-center py-2">
                    <div class="fs-4"><?= $info['icon'] ?></div>
                    <div class="fw-bold fs-5 text-<?= $info['color'] ?>"><?= $n ?></div>
                    <div class="text-muted small"><?= $info['label'] ?></div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Tabs de estado -->
    <ul class="nav nav-tabs mb-3">
        <?php foreach (array_merge(['todos' => ['icon' => '📋', 'label' => "Todos ($totalGeneral)"]], array_map(fn($i) => ['icon' => $i['icon'], 'label' => $i['label'] . ' (' . ($contadores[array_search($i, $estadoInfo)] ?? 0) . ')'], $estadoInfo)) as $est => $info):
            // Simplificado: solo los tabs principales
        endforeach; ?>
        <?php foreach (['todos' => "📋 Todos ($totalGeneral)", 'moderacion' => "⏳ Moderación (" . ($contadores['moderacion'] ?? 0) . ")", 'pendiente' => "🕐 Pendientes (" . ($contadores['pendiente'] ?? 0) . ")", 'error' => "❌ Errores (" . ($contadores['error'] ?? 0) . ")", 'completada' => "✅ Completadas (" . ($contadores['completada'] ?? 0) . ")", 'cancelada' => "🚫 Canceladas (" . ($contadores['cancelada'] ?? 0) . ")"] as $est => $label): ?>
        <li class="nav-item">
            <a class="nav-link <?= $filtroEstado === $est ? 'active' : '' ?>" href="?estado=<?= $est ?>"><?= $label ?></a>
        </li>
        <?php endforeach; ?>
    </ul>

    <!-- Acciones masivas (solo para moderación) -->
    <?php if ($filtroEstado === 'moderacion' && ($contadores['moderacion'] ?? 0) > 0): ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-center py-2 mb-3">
        <span>⏳ Hay <strong><?= $contadores['moderacion'] ?? 0 ?></strong> tareas esperando tu aprobación.</span>
        <form method="post" class="d-inline">
            <input type="hidden" name="accion" value="aprobar_todos">
            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Aprobar TODAS las tareas en moderación?')">
                ✅ Aprobar todas
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Tabla de tareas -->
    <?php if (isset($errorConsulta)): ?>
    <div class="alert alert-danger">Error al cargar tareas: <?= htmlspecialchars($errorConsulta) ?></div>
    <?php elseif (empty($tareas)): ?>
    <div class="alert alert-light text-center py-4">
        <div class="fs-1">🎉</div>
        <p class="mb-0">No hay tareas con estado <strong><?= htmlspecialchars($filtroEstado) ?></strong>.</p>
    </div>
    <?php else: ?>

    <form method="post" id="formMasivo">
        <input type="hidden" name="accion" value="cancelar_seleccion">

        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-muted">Mostrando <?= count($tareas) ?> de <?= $totalFiltrado ?> tareas</small>
            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Cancelar las tareas seleccionadas?')">
                🚫 Cancelar seleccionadas
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-bordered table-sm align-middle">
                <thead class="table-dark">
                    <tr>
                        <th><input type="checkbox" id="selAll" title="Seleccionar todas"></th>
                        <th>#ID</th>
                        <th>Estado</th>
                        <th>Tipo tarea</th>
                        <th>Canal</th>
                        <th>Entidad</th>
                        <th>Regla</th>
                        <th>Payload</th>
                        <th>Intentos</th>
                        <th>Creada</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tareas as $t):
                    $payload = json_decode($t['payload'] ?? '{}', true) ?: [];
                    $payloadStr = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    $errorMsg = !empty($t['error_msg']) ? htmlspecialchars(substr($t['error_msg'], 0, 100)) : '';
                    // Compatibilidad con tabla antigua (columnas opcionales)
                    $tipoTarea        = $t['tipo_tarea'] ?? ($t['tipo'] ?? '—');
                    $entidadTipo      = $t['entidad_tipo'] ?? ($t['resource_type'] ?? '');
                    $entidadId        = $t['entidad_id'] ?? ($t['resource_id'] ?? '');
                    $reqMod           = $t['requiere_moderacion'] ?? 0;
                    $canal            = $t['canal'] ?? null;
                    $regla_nombre     = $t['regla_nombre'] ?? null;
                    $destinatarioEmail = $t['destinatario_email'] ?? '';
                    $intentos         = $t['intentos'] ?? 0;
                    $maxIntentos      = $t['max_intentos'] ?? 3;
                    $procesadaEn      = $t['procesada_en'] ?? null;
                ?>
                <tr class="<?= $t['estado'] === 'error' ? 'table-danger' : ($t['estado'] === 'moderacion' ? 'table-warning' : '') ?>">
                    <td><input type="checkbox" name="ids[]" value="<?= $t['id'] ?>" class="check-item"></td>
                    <td><strong><?= $t['id'] ?></strong></td>
                    <td><?= badgeEstado($t['estado']) ?></td>
                    <td>
                        <code class="small"><?= htmlspecialchars($tipoTarea) ?></code>
                        <?php if ($reqMod): ?>
                        <span class="badge bg-warning text-dark regla-badge ms-1">mod</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= iconoCanal($canal) ?> <small><?= htmlspecialchars($canal ?? '—') ?></small></td>
                    <td>
                        <?php if ($entidadTipo): ?>
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($entidadTipo) ?></span>
                        <small>#<?= $entidadId ?></small>
                        <?php endif; ?>
                        <?php if ($destinatarioEmail): ?>
                        <br><small class="text-muted">📧 <?= htmlspecialchars($destinatarioEmail) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($regla_nombre): ?>
                        <small class="text-muted"><?= htmlspecialchars($regla_nombre) ?></small>
                        <?php else: ?>
                        <small class="text-muted">—</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <pre class="payload-pre mb-0"><?= htmlspecialchars($payloadStr) ?></pre>
                        <?php if ($errorMsg): ?>
                        <small class="text-danger">⚠️ <?= $errorMsg ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $intentos >= $maxIntentos ? 'bg-danger' : 'bg-secondary' ?>">
                            <?= $intentos ?>/<?= $maxIntentos ?>
                        </span>
                    </td>
                    <td>
                        <small><?= date('d/m H:i', strtotime($t['creada_en'])) ?></small>
                        <?php if ($t['procesada_en']): ?>
                        <br><small class="text-muted">✓ <?= date('d/m H:i', strtotime($t['procesada_en'])) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="acciones-btn">
                        <?php if ($t['estado'] === 'moderacion'): ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="accion" value="aprobar">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm py-0 px-1" title="Aprobar y enviar">✅</button>
                        </form>
                        <?php endif; ?>

                        <?php if ($t['estado'] === 'error'): ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="accion" value="reintentar">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button type="submit" class="btn btn-info btn-sm py-0 px-1" title="Reintentar">🔄</button>
                        </form>
                        <?php endif; ?>

                        <?php if (in_array($t['estado'], ['moderacion', 'pendiente', 'error'])): ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="accion" value="cancelar">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1" title="Cancelar" onclick="return confirm('¿Cancelar tarea #<?= $t['id'] ?>?')">🚫</button>
                        </form>
                        <?php endif; ?>

                        <!-- Ver payload completo -->
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1"
                            data-bs-toggle="modal" data-bs-target="#modalPayload"
                            data-id="<?= $t['id'] ?>"
                            data-payload="<?= htmlspecialchars($payloadStr) ?>"
                            data-error="<?= htmlspecialchars($t['error_msg'] ?? '') ?>"
                            title="Ver detalle">🔍</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>

    <!-- Paginación -->
    <?php if ($totalPaginas > 1): ?>
    <nav>
        <ul class="pagination pagination-sm justify-content-center">
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                <a class="page-link" href="?estado=<?= $filtroEstado ?>&p=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <?php endif; // fin empty tareas ?>

    <!-- ── Panel de Reglas ──────────────────────────────────── -->
    <hr class="my-4">
    <h5 class="section-title">📋 Reglas de Notificación</h5>
    <p class="text-muted small mb-3">Configura aquí cuándo y qué notificar. Cada fila es una regla. Activa/desactiva sin tocar código.</p>

    <?php if (!empty($reglas)): ?>
    <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered table-hover">
            <thead class="table-secondary">
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Activa</th>
                    <th>Tabla</th>
                    <th>Evento</th>
                    <th>Campo</th>
                    <th>Umbral</th>
                    <th>Tipo</th>
                    <th>Filtro tipo</th>
                    <th>Tarea</th>
                    <th>Destinatario</th>
                    <th>Mod.</th>
                    <th>Cooldown</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($reglas as $r): ?>
            <tr class="<?= $r['activa'] ? '' : 'table-secondary text-muted' ?>">
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['nombre']) ?></td>
                <td class="text-center">
                    <form method="post" class="d-inline">
                        <input type="hidden" name="accion" value="toggle_regla">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-sm py-0 px-1 <?= $r['activa'] ? 'btn-success' : 'btn-outline-secondary' ?>"
                            title="<?= $r['activa'] ? 'Desactivar' : 'Activar' ?>">
                            <?= $r['activa'] ? '✅' : '⭕' ?>
                        </button>
                    </form>
                </td>
                <td><code class="small"><?= htmlspecialchars($r['tabla_origen']) ?></code></td>
                <td><span class="badge <?= $r['evento_tipo'] === 'INSERT' ? 'bg-success' : 'bg-primary' ?>"><?= $r['evento_tipo'] ?></span></td>
                <td><small><?= htmlspecialchars($r['campo_umbral'] ?? '—') ?></small></td>
                <td class="text-center"><strong><?= $r['umbral_valor'] ?? '—' ?></strong></td>
                <td><small><?= htmlspecialchars($r['umbral_tipo'] ?? '—') ?></small></td>
                <td><small><?= htmlspecialchars($r['resource_type_filtro'] ?? 'todos') ?></small></td>
                <td><code class="small"><?= htmlspecialchars($r['tipo_tarea']) ?></code></td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['destinatario']) ?></span></td>
                <td class="text-center"><?= $r['requiere_moderacion'] ? '⏳' : '—' ?></td>
                <td class="text-center"><small><?= $r['cooldown_horas'] ?>h</small></td>
                <td>
                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-1"
                        data-bs-toggle="modal" data-bs-target="#modalEditarRegla"
                        data-regla='<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>'
                        title="Editar regla">✏️</button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Añadir nueva regla (SQL helper) -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <strong>➕ Añadir nueva regla</strong>
            <small class="text-muted ms-2">Copia este SQL y ejecútalo en phpMyAdmin</small>
        </div>
        <div class="card-body">
            <pre class="bg-dark text-light p-3 rounded small mb-0">INSERT INTO reglas_notificacion
(nombre, activa, tabla_origen, evento_tipo, campo_umbral, umbral_valor, umbral_tipo,
 resource_type_filtro, tipo_tarea, plantilla_id, destinatario, requiere_moderacion, cooldown_horas, prioridad)
VALUES
('Mi nueva regla', 1, 'resource_stats', 'UPDATE',
 'views_count', 50, 'multiplo',
 'accommodation', 'email_propietario', 2, 'propietario', 0, 48, 5);</pre>
        </div>
    </div>

    <?php else: ?>
    <div class="alert alert-info">No hay reglas configuradas aún. Ejecuta el PASO 5 del SQL.</div>
    <?php endif; ?>

    <!-- ── Historial reciente ───────────────────────────────── -->
    <hr class="my-4">
    <h5 class="section-title">📜 Historial reciente (últimas 20 ejecuciones)</h5>
    <?php
    try {
        $historial = $pdo->query("
            SELECT * FROM historial_tareas
            ORDER BY ejecutada_en DESC
            LIMIT 20
        ")->fetchAll();
    } catch (Exception $e) {
        $historial = [];
    }
    ?>
    <?php if (!empty($historial)): ?>
    <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered">
            <thead class="table-secondary">
                <tr>
                    <th>Tarea ID</th>
                    <th>Tipo</th>
                    <th>Entidad</th>
                    <th>Destinatario</th>
                    <th>Resultado</th>
                    <th>Intentos</th>
                    <th>Ejecutada</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($historial as $h): ?>
            <tr class="<?= $h['resultado'] === 'error' ? 'table-danger' : '' ?>">
                <td>#<?= $h['tarea_id'] ?></td>
                <td><code class="small"><?= htmlspecialchars($h['tipo_tarea']) ?></code></td>
                <td><small><?= htmlspecialchars($h['entidad_tipo'] ?? '—') ?> <?= $h['entidad_id'] ? '#'.$h['entidad_id'] : '' ?></small></td>
                <td><small><?= htmlspecialchars($h['destinatario_email'] ?? ($h['destinatario_id'] ? 'ID:'.$h['destinatario_id'] : '—')) ?></small></td>
                <td><?= badgeEstado($h['resultado']) ?></td>
                <td class="text-center"><?= $h['intentos_realizados'] ?></td>
                <td><small><?= date('d/m H:i', strtotime($h['ejecutada_en'])) ?></small></td>
                <td><small class="text-danger"><?= htmlspecialchars(substr($h['error_msg'] ?? '', 0, 60)) ?></small></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <p class="text-muted">Sin historial aún.</p>
    <?php endif; ?>

    <?php endif; // fin !errorTabla ?>

</div><!-- /container -->

<!-- Modal: Ver payload completo -->
<div class="modal fade" id="modalPayload" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">🔍 Detalle de tarea <span id="modalPayloadId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Payload:</h6>
                <pre id="modalPayloadContent" class="bg-dark text-light p-3 rounded small" style="max-height:300px;overflow-y:auto;"></pre>
                <div id="modalErrorBlock" class="d-none">
                    <h6 class="text-danger mt-3">Error:</h6>
                    <pre id="modalErrorContent" class="bg-danger text-white p-3 rounded small"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar regla -->
<div class="modal fade" id="modalEditarRegla" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">✏️ Editar Regla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Copia el SQL generado y ejecútalo en phpMyAdmin para guardar los cambios.</p>
                <div class="row g-2" id="editarReglaForm">
                    <!-- Se rellena por JS -->
                </div>
                <hr>
                <h6>SQL generado:</h6>
                <pre id="sqlEditarRegla" class="bg-dark text-light p-3 rounded small" style="max-height:200px;overflow-y:auto;"></pre>
                <button class="btn btn-sm btn-outline-light mt-1" onclick="copiarSQL()">📋 Copiar SQL</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Seleccionar todos los checkboxes
document.getElementById('selAll')?.addEventListener('change', function() {
    document.querySelectorAll('.check-item').forEach(c => c.checked = this.checked);
});

// Modal payload
document.getElementById('modalPayload')?.addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('modalPayloadId').textContent = '#' + btn.dataset.id;
    document.getElementById('modalPayloadContent').textContent = btn.dataset.payload;
    const err = btn.dataset.error;
    if (err) {
        document.getElementById('modalErrorBlock').classList.remove('d-none');
        document.getElementById('modalErrorContent').textContent = err;
    } else {
        document.getElementById('modalErrorBlock').classList.add('d-none');
    }
});

// Modal editar regla
document.getElementById('modalEditarRegla')?.addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    const r = JSON.parse(btn.dataset.regla);
    const form = document.getElementById('editarReglaForm');

    const campos = [
        {k:'nombre', label:'Nombre', type:'text'},
        {k:'activa', label:'Activa (1/0)', type:'number'},
        {k:'tabla_origen', label:'Tabla origen', type:'text'},
        {k:'evento_tipo', label:'Evento (INSERT/UPDATE)', type:'text'},
        {k:'campo_umbral', label:'Campo umbral', type:'text'},
        {k:'umbral_valor', label:'Umbral valor', type:'number'},
        {k:'umbral_tipo', label:'Umbral tipo (multiplo/mayor_igual/igual)', type:'text'},
        {k:'resource_type_filtro', label:'Filtro resource_type', type:'text'},
        {k:'tipo_tarea', label:'Tipo tarea', type:'text'},
        {k:'plantilla_id', label:'Plantilla ID', type:'number'},
        {k:'destinatario', label:'Destinatario', type:'text'},
        {k:'requiere_moderacion', label:'Requiere moderación (1/0)', type:'number'},
        {k:'cooldown_horas', label:'Cooldown horas', type:'number'},
        {k:'prioridad', label:'Prioridad (1-10)', type:'number'},
    ];

    form.innerHTML = campos.map(c => `
        <div class="col-md-6">
            <label class="form-label small mb-0">${c.label}</label>
            <input type="${c.type}" class="form-control form-control-sm regla-input" 
                   data-key="${c.k}" value="${r[c.k] ?? ''}" oninput="generarSQL(${r.id})">
        </div>
    `).join('');

    generarSQL(r.id);
});

function generarSQL(id) {
    const inputs = document.querySelectorAll('.regla-input');
    const sets = [];
    inputs.forEach(inp => {
        const v = inp.value.replace(/'/g, "\\'");
        const isNum = inp.type === 'number';
        sets.push(`  ${inp.dataset.key} = ${isNum ? (v === '' ? 'NULL' : v) : "'" + v + "'"}`);
    });
    document.getElementById('sqlEditarRegla').textContent =
        `UPDATE reglas_notificacion\nSET\n${sets.join(',\n')}\nWHERE id = ${id};`;
}

function copiarSQL() {
    const sql = document.getElementById('sqlEditarRegla').textContent;
    navigator.clipboard.writeText(sql).then(() => alert('SQL copiado al portapapeles'));
}
</script>
</body>
</html>

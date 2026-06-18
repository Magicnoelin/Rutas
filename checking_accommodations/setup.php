<?php
/**
 * =============================================================================
 * SISTEMA DE CHECK-IN — Script de configuración inicial
 * =============================================================================
 * Archivo  : setup.php
 * Acceso   : SOLO durante la configuración inicial. ELIMINAR tras su uso.
 * Descripción: Crea alojamientos en la base de datos con token único
 *              generado automáticamente y contraseña hasheada con bcrypt.
 *
 * ⚠️  IMPORTANTE: Elimina o protege este archivo tras crear los alojamientos.
 *     No debe quedar accesible en producción.
 * =============================================================================
 */

declare(strict_types=1);

// Carga explícita con ruta absoluta para evitar problemas en servidores compartidos
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------------------
// PROTECCIÓN MÍNIMA: Cambia esta clave antes de usar el script
// ---------------------------------------------------------------------------
define('SETUP_KEY', 'cambia_esta_clave_secreta_2026');

$clave_ok    = (($_GET['key'] ?? '') === SETUP_KEY);
$mensaje     = '';
$tipo_msg    = '';
$alojamientos_existentes = [];
$token_generado = '';
$url_checkin_generada = '';

$pdo = obtener_pdo();

// ---------------------------------------------------------------------------
// CARGAR ALOJAMIENTOS EXISTENTES
// ---------------------------------------------------------------------------
try {
    $stmt = $pdo->query('SELECT id, nombre, email, token_publico, activo, created_at FROM alojamientos ORDER BY id DESC');
    $alojamientos_existentes = $stmt->fetchAll();
} catch (PDOException $e) {
    $mensaje  = '⚠️ Error al cargar alojamientos: ' . $e->getMessage() . '. ¿Has importado schema.sql en la base de datos?';
    $tipo_msg = 'danger';
}

// ---------------------------------------------------------------------------
// PROCESAR FORMULARIO DE CREACIÓN
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $clave_ok) {

    $nombre   = trim($_POST['nombre']   ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($nombre) || empty($email) || empty($password)) {
        $mensaje  = 'Todos los campos son obligatorios.';
        $tipo_msg = 'warning';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje  = 'El email no tiene un formato válido.';
        $tipo_msg = 'warning';
    } elseif (strlen($password) < 8) {
        $mensaje  = 'La contraseña debe tener al menos 8 caracteres.';
        $tipo_msg = 'warning';
    } else {
        try {
            // Generar token público único (64 caracteres hex)
            $token      = bin2hex(random_bytes(32));
            $hash       = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $stmt = $pdo->prepare(
                'INSERT INTO alojamientos (nombre, token_publico, email, password_hash)
                 VALUES (:nombre, :token, :email, :hash)'
            );
            $stmt->execute([
                ':nombre' => $nombre,
                ':token'  => $token,
                ':email'  => $email,
                ':hash'   => $hash,
            ]);

            $token_generado     = $token;
            $url_checkin_generada = APP_URL . '/checkin.php?token=' . $token;
            $mensaje  = "✅ Alojamiento <strong>" . esc($nombre) . "</strong> creado correctamente.";
            $tipo_msg = 'success';

            // Recargar listado
            $stmt2 = $pdo->query('SELECT id, nombre, email, token_publico, activo, created_at FROM alojamientos ORDER BY id DESC');
            $alojamientos_existentes = $stmt2->fetchAll();

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $mensaje  = 'Ya existe un alojamiento con ese email. Usa uno diferente.';
            } else {
                $mensaje  = 'Error al crear el alojamiento: ' . $e->getMessage();
            }
            $tipo_msg = 'danger';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Setup — Sistema Check-in</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary:   #2F5233;
            --secondary: #6B8E6B;
            --accent:    #B8956A;
        }
        body { background: #f4f7f4; font-family: 'Segoe UI', system-ui, sans-serif; }
        .setup-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 0 0 1rem 1rem;
            margin-bottom: 2rem;
        }
        .card-setup {
            border: 1px solid #d4e0d4;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(47,82,51,.07);
        }
        .card-setup .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            font-weight: 700;
            border-radius: 1rem 1rem 0 0;
            padding: .85rem 1.25rem;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none; color: #fff; font-weight: 700;
            padding: .65rem 1.5rem; border-radius: .5rem;
            transition: transform .15s, box-shadow .15s;
        }
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(47,82,51,.3);
            color: #fff;
        }
        .token-box {
            background: #1a2e1a;
            color: #81e08a;
            font-family: monospace;
            font-size: .85rem;
            border-radius: .5rem;
            padding: .75rem 1rem;
            word-break: break-all;
        }
        .url-box {
            background: #fff8f0;
            border: 1.5px solid #f0d9b8;
            border-radius: .5rem;
            padding: .75rem 1rem;
            font-size: .88rem;
            word-break: break-all;
        }
        .warning-banner {
            background: #fff3cd;
            border: 1.5px solid #ffc107;
            border-radius: .75rem;
            padding: 1rem 1.25rem;
            font-size: .88rem;
        }
        .badge-activo   { background: #d4efda; color: #1a4a1a; }
        .badge-inactivo { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="setup-header text-center">
    <h1 class="h3 mb-1">🔧 Setup — Sistema de Check-in</h1>
    <p class="mb-0" style="opacity:.85; font-size:.88rem;">
        Herramienta de configuración inicial. <strong>Elimina este archivo tras su uso.</strong>
    </p>
</div>

<div class="container" style="max-width:800px; padding-bottom:3rem;">

    <!-- ===== AVISO DE SEGURIDAD ===== -->
    <div class="warning-banner mb-4 d-flex gap-2 align-items-start">
        <i class="fa-solid fa-triangle-exclamation mt-1 flex-shrink-0" style="color:#b8860b;"></i>
        <div>
            <strong>⚠️ Archivo de configuración — Uso único</strong><br>
            Una vez creados todos tus alojamientos, <strong>elimina este archivo</strong>
            del servidor para evitar que terceros puedan crear accesos no autorizados.
            <br><small>Ruta a eliminar: <code>checking_accommodations/setup.php</code></small>
        </div>
    </div>

    <?php if (!$clave_ok): ?>
    <!-- ===== PROTECCIÓN POR CLAVE ===== -->
    <div class="card card-setup mb-4">
        <div class="card-header">🔒 Acceso protegido</div>
        <div class="card-body">
            <p>Este script requiere una clave de acceso en la URL para poder usarse.</p>
            <p>Accede con: <code><?= esc($_SERVER['PHP_SELF']) ?>?key=<strong>cambia_esta_clave_secreta_2026</strong></code></p>
            <div class="alert alert-warning mt-3">
                <strong>Antes de usar:</strong> edita <code>setup.php</code> y cambia el valor de
                <code>SETUP_KEY</code> por una clave propia y segura.
            </div>
        </div>
    </div>

    <?php else: ?>

    <!-- ===== RESULTADO DE CREACIÓN ===== -->
    <?php if (!empty($mensaje)): ?>
    <div class="alert alert-<?= esc($tipo_msg) ?> rounded-3 mb-4">
        <?= $mensaje ?>
    </div>
    <?php endif; ?>

    <!-- ===== TOKEN Y ENLACE GENERADO ===== -->
    <?php if (!empty($token_generado)): ?>
    <div class="card card-setup mb-4">
        <div class="card-header">
            <i class="fa-solid fa-link me-1"></i> Enlace de check-in generado
        </div>
        <div class="card-body">
            <p class="mb-2"><strong>Token público del alojamiento:</strong></p>
            <div class="token-box mb-3"><?= esc($token_generado) ?></div>

            <p class="mb-2"><strong>🔗 URL que debes enviar a tus huéspedes:</strong></p>
            <div class="url-box mb-3">
                <i class="fa-solid fa-link me-1" style="color:var(--accent);"></i>
                <a href="<?= esc($url_checkin_generada) ?>" target="_blank" style="color:var(--primary);">
                    <?= esc($url_checkin_generada) ?>
                </a>
            </div>

            <button class="btn btn-sm btn-outline-success"
                    onclick="navigator.clipboard.writeText('<?= esc($url_checkin_generada) ?>').then(()=>this.innerHTML='✅ Copiado')">
                <i class="fa-solid fa-copy me-1"></i> Copiar enlace
            </button>

            <div class="alert alert-info mt-3 mb-0" style="font-size:.85rem;">
                <i class="fa-solid fa-circle-info me-1"></i>
                Este mismo enlace también estará visible en el
                <strong><a href="panel.php" style="color:inherit;">Panel de administración</a></strong>
                una vez hayas iniciado sesión. Desde allí podrás copiarlo y enviarlo a tus huéspedes.
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== FORMULARIO DE CREACIÓN ===== -->
    <div class="card card-setup mb-4">
        <div class="card-header">
            <i class="fa-solid fa-plus me-1"></i> Crear nuevo alojamiento
        </div>
        <div class="card-body">
            <form method="POST" action="setup.php?key=<?= esc(SETUP_KEY) ?>">
                <div class="mb-3">
                    <label class="form-label fw-bold" for="nombre">
                        Nombre del alojamiento <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="nombre" name="nombre"
                           placeholder="Ej: Casa Rural El Roble" required maxlength="150">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" for="email">
                        Email de administrador <span class="text-danger">*</span>
                    </label>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="admin@tualojamiento.es" required>
                    <div class="form-text">Este email se usará para acceder al panel privado.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold" for="password">
                        Contraseña de acceso <span class="text-danger">*</span>
                    </label>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Mínimo 8 caracteres" required minlength="8">
                    <div class="form-text">
                        Se guardará con hash bcrypt. Guárdala en un lugar seguro.
                    </div>
                </div>
                <button type="submit" class="btn-primary-custom btn">
                    <i class="fa-solid fa-plus me-1"></i>
                    Crear alojamiento y generar token
                </button>
            </form>
        </div>
    </div>

    <!-- ===== LISTADO DE ALOJAMIENTOS ===== -->
    <?php if (!empty($alojamientos_existentes)): ?>
    <div class="card card-setup mb-4">
        <div class="card-header">
            <i class="fa-solid fa-list me-1"></i>
            Alojamientos registrados (<?= count($alojamientos_existentes) ?>)
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.88rem;">
                <thead style="background:#f4f7f4;">
                    <tr>
                        <th class="px-3">ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Enlace de check-in</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alojamientos_existentes as $alo): ?>
                    <?php $url_alo = APP_URL . '/checkin.php?token=' . $alo['token_publico']; ?>
                    <tr>
                        <td class="px-3 text-muted">#<?= (int)$alo['id'] ?></td>
                        <td><strong><?= esc($alo['nombre']) ?></strong></td>
                        <td><?= esc($alo['email']) ?></td>
                        <td>
                            <span class="badge rounded-pill <?= $alo['activo'] ? 'badge-activo' : 'badge-inactivo' ?>">
                                <?= $alo['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex; gap:.5rem; align-items:center; flex-wrap:wrap;">
                                <a href="<?= esc($url_alo) ?>"
                                   target="_blank"
                                   style="font-size:.78rem; color:var(--primary); font-family:monospace; word-break:break-all;">
                                    <?= esc(substr($url_alo, 0, 55)) ?>…
                                </a>
                                <button class="btn btn-sm btn-outline-success"
                                        style="font-size:.72rem; padding:.2rem .5rem; white-space:nowrap;"
                                        onclick="navigator.clipboard.writeText('<?= esc($url_alo) ?>').then(()=>this.innerHTML='✅')">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== CÓMO FUNCIONA ===== -->
    <div class="card card-setup">
        <div class="card-header">
            <i class="fa-solid fa-circle-question me-1"></i> ¿Cómo funciona el sistema?
        </div>
        <div class="card-body">
            <ol style="font-size:.9rem; line-height:2;">
                <li>
                    <strong>Crea el alojamiento</strong> con este formulario →
                    se genera automáticamente un <em>token único</em>.
                </li>
                <li>
                    <strong>Envía el enlace de check-in</strong> a tus huéspedes
                    por WhatsApp, email o QR. Ejemplo:<br>
                    <code style="font-size:.82rem;"><?= esc(APP_URL) ?>/checkin.php?token=<em>TOKEN_DEL_ALOJAMIENTO</em></code>
                </li>
                <li>
                    El huésped <strong>rellena el formulario</strong> en su móvil o PC
                    (nombre, DNI, dirección, fechas de estancia, etc.).
                </li>
                <li>
                    Tú accedes al <strong>Panel privado</strong> en
                    <a href="login.php" style="color:var(--primary);"><code>login.php</code></a>
                    con tu email y contraseña para ver todos los registros.
                </li>
                <li>
                    Desde el panel también puedes <strong>copiar el enlace de check-in</strong>
                    en cualquier momento para enviarlo a nuevos huéspedes.
                </li>
            </ol>
            <div class="d-flex gap-2 flex-wrap mt-2">
                <a href="login.php"
                   class="btn btn-sm btn-primary-custom">
                    <i class="fa-solid fa-right-to-bracket me-1"></i> Ir al login del panel
                </a>
                <?php if (!empty($alojamientos_existentes)): ?>
                <a href="checkin.php?token=<?= esc($alojamientos_existentes[0]['token_publico']) ?>"
                   target="_blank"
                   class="btn btn-sm"
                   style="background:var(--accent); color:#fff; border:none; border-radius:.5rem; font-weight:600; padding:.4rem 1rem;">
                    <i class="fa-solid fa-eye me-1"></i> Ver formulario de check-in
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php endif; /* fin clave_ok */ ?>

</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

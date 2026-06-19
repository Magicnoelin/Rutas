<?php
/**
 * =============================================================================
 * SISTEMA DE CHECK-IN — Setup / Configuración inicial
 * =============================================================================
 * Este script lee los alojamientos YA EXISTENTES en la tabla 'accommodations'
 * del proyecto y permite asignarles token de check-in y contraseña de acceso.
 *
 * ⚠️ ELIMINAR este archivo del servidor una vez configurados los alojamientos.
 * =============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Clave de acceso — cámbiala antes de usar
define('SETUP_KEY', 'cambia_esta_clave_secreta_2026');

$clave_ok = (($_GET['key'] ?? '') === SETUP_KEY);
$mensaje  = '';
$tipo_msg = '';
$pdo      = obtener_pdo();

// ---------------------------------------------------------------------------
// Cargar todos los alojamientos activos del proyecto
// ---------------------------------------------------------------------------
$todos = [];
try {
    $s = $pdo->query("SELECT id, name, email, token_publico, password_hash FROM accommodations ORDER BY name ASC");
    $todos = $s->fetchAll();
} catch (PDOException $e) {
    $mensaje  = '⚠️ Error al leer accommodations: ' . $e->getMessage() . ' — ¿Importaste schema.sql?';
    $tipo_msg = 'danger';
}

// ---------------------------------------------------------------------------
// PROCESAR FORMULARIO
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $clave_ok) {

    $accion   = $_POST['accion']         ?? '';
    $acc_id   = (int) ($_POST['acc_id'] ?? 0);
    $password = trim($_POST['password']  ?? '');

    if ($accion === 'asignar' && $acc_id > 0) {

        if (strlen($password) < 8) {
            $mensaje  = 'La contraseña debe tener al menos 8 caracteres.';
            $tipo_msg = 'warning';
        } else {
            try {
                // Verificar que el alojamiento existe
                $check = $pdo->prepare("SELECT id, name FROM accommodations WHERE id = ? LIMIT 1");
                $check->execute([$acc_id]);
                $alo = $check->fetch();

                if (!$alo) {
                    $mensaje  = 'Alojamiento no encontrado.';
                    $tipo_msg = 'danger';
                } else {
                    // Generar token único si no tiene uno
                    $stmt_tok = $pdo->prepare('SELECT token_publico FROM accommodations WHERE id = ? LIMIT 1');
                    $stmt_tok->execute([$acc_id]);
                    $tok_actual = $stmt_tok->fetchColumn();

                    $nuevo_token = empty($tok_actual) ? bin2hex(random_bytes(32)) : $tok_actual;
                    $nuevo_hash  = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                    $upd = $pdo->prepare(
                        'UPDATE accommodations SET token_publico = ?, password_hash = ? WHERE id = ?'
                    );
                    $upd->execute([$nuevo_token, $nuevo_hash, $acc_id]);

                    $url_ci   = CHECKIN_APP_URL . '/checkin.php?token=' . $nuevo_token;
                    $mensaje  = '✅ <strong>' . htmlspecialchars($alo['name'], ENT_QUOTES, 'UTF-8') . '</strong> configurado. '
                              . 'URL de check-in: <a href="' . htmlspecialchars($url_ci, ENT_QUOTES, 'UTF-8') . '" target="_blank" style="color:inherit;">' . htmlspecialchars($url_ci, ENT_QUOTES, 'UTF-8') . '</a>';
                    $tipo_msg = 'success';

                    // Recargar listado
                    $s2 = $pdo->query("SELECT id, name, email, token_publico, password_hash FROM accommodations ORDER BY name ASC");
                    $todos = $s2->fetchAll();
                }
            } catch (PDOException $e) {
                $mensaje  = 'Error al actualizar: ' . $e->getMessage();
                $tipo_msg = 'danger';
            }
        }
    }

    // --- Regenerar token ---
    if ($accion === 'regenerar' && $acc_id > 0) {
        try {
            $nuevo_token = bin2hex(random_bytes(32));
            $pdo->prepare('UPDATE accommodations SET token_publico = ? WHERE id = ?')
                ->execute([$nuevo_token, $acc_id]);
            $mensaje  = '🔄 Token regenerado correctamente para el alojamiento #' . $acc_id;
            $tipo_msg = 'info';
            $s3 = $pdo->query("SELECT id, name, email, token_publico, password_hash FROM accommodations ORDER BY name ASC");
            $todos = $s3->fetchAll();
        } catch (PDOException $e) {
            $mensaje  = 'Error al regenerar token: ' . $e->getMessage();
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
    <title>Setup Check-in — Alojamientos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary:#2F5233; --secondary:#6B8E6B; --accent:#B8956A; }
        body { background:#f4f7f4; font-family:'Segoe UI',system-ui,sans-serif; }
        .hdr { background:linear-gradient(135deg,var(--primary),var(--secondary)); color:#fff;
               padding:1.5rem 2rem; border-radius:0 0 1rem 1rem; margin-bottom:2rem; }
        .card-s { border:1px solid #d4e0d4; border-radius:1rem; box-shadow:0 2px 12px rgba(47,82,51,.07); }
        .card-s .card-header { background:linear-gradient(135deg,var(--primary),var(--secondary));
               color:#fff; font-weight:700; border-radius:1rem 1rem 0 0; padding:.85rem 1.25rem; }
        .btn-p { background:linear-gradient(135deg,var(--primary),var(--secondary));
               border:none; color:#fff; font-weight:700; border-radius:.5rem; }
        .btn-p:hover { filter:brightness(1.1); color:#fff; }
        .token-pill { font-family:monospace; font-size:.75rem; background:#1a2e1a; color:#81e08a;
               padding:.25rem .6rem; border-radius:.4rem; word-break:break-all; }
        .no-token { color:#b0bab0; font-style:italic; font-size:.82rem; }
        .badge-ok { background:#d4efda; color:#1a4a1a; }
        .badge-no { background:#f8d7da; color:#721c24; }
        .warn { background:#fff3cd; border:1.5px solid #ffc107; border-radius:.75rem;
                padding:1rem 1.25rem; font-size:.88rem; }
    </style>
</head>
<body>

<div class="hdr text-center">
    <h1 class="h3 mb-1">🔧 Setup — Check-in por alojamiento</h1>
    <p class="mb-0" style="opacity:.85;font-size:.88rem;">
        Asigna token y contraseña a tus alojamientos existentes. <strong>Elimina este archivo tras usarlo.</strong>
    </p>
</div>

<div class="container" style="max-width:900px;padding-bottom:3rem;">

    <div class="warn mb-4 d-flex gap-2 align-items-start">
        <i class="fa-solid fa-triangle-exclamation mt-1 flex-shrink-0" style="color:#b8860b;"></i>
        <div>
            <strong>⚠️ Uso único — Eliminar tras configurar los alojamientos</strong><br>
            Ruta a eliminar: <code>checking_accommodations/setup.php</code>
        </div>
    </div>

<?php if (!$clave_ok): ?>
    <div class="card card-s mb-4">
        <div class="card-header">🔒 Acceso protegido</div>
        <div class="card-body">
            <p>Añade <code>?key=cambia_esta_clave_secreta_2026</code> a la URL.</p>
            <p><strong>URL completa:</strong><br>
            <code><?= htmlspecialchars('https://rutasrurales.io/checking_accommodations/setup.php?key=cambia_esta_clave_secreta_2026', ENT_QUOTES, 'UTF-8') ?></code></p>
        </div>
    </div>

<?php else: ?>

    <?php if (!empty($mensaje)): ?>
    <div class="alert alert-<?= htmlspecialchars($tipo_msg, ENT_QUOTES, 'UTF-8') ?> rounded-3 mb-4">
        <?= $mensaje ?>
    </div>
    <?php endif; ?>

    <!-- ===== TABLA DE ALOJAMIENTOS EXISTENTES ===== -->
    <div class="card card-s mb-4">
        <div class="card-header">
            <i class="fa-solid fa-hotel me-1"></i>
            Alojamientos en la BD (<?= count($todos) ?>) — Tabla: accommodations
        </div>

        <?php if (empty($todos)): ?>
        <div class="card-body text-muted">No se encontraron alojamientos activos en la tabla <code>accommodations</code>.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.88rem;">
                <thead style="background:#f4f7f4;">
                    <tr>
                        <th class="px-3">ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Estado check-in</th>
                        <th>Token / URL</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($todos as $a): ?>
                <?php
                    $tiene_token = !empty($a['token_publico']);
                    $tiene_pass  = !empty($a['password_hash']);
                    $url_ci      = CHECKIN_APP_URL . '/checkin.php?token=' . ($a['token_publico'] ?? '');
                ?>
                <tr>
                    <td class="px-3 text-muted">#<?= (int)$a['id'] ?></td>
                    <td><strong><?= htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td style="font-size:.82rem;"><?= htmlspecialchars($a['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($tiene_token && $tiene_pass): ?>
                            <span class="badge rounded-pill badge-ok">✅ Listo</span>
                        <?php elseif ($tiene_token): ?>
                            <span class="badge rounded-pill" style="background:#fff3cd;color:#856404;">⚠️ Sin contraseña</span>
                        <?php else: ?>
                            <span class="badge rounded-pill badge-no">❌ Sin configurar</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($tiene_token): ?>
                            <div class="token-pill mb-1"><?= htmlspecialchars(substr($a['token_publico'], 0, 20), ENT_QUOTES, 'UTF-8') ?>…</div>
                            <div style="display:flex;gap:.35rem;flex-wrap:wrap;align-items:center;">
                                <a href="<?= htmlspecialchars($url_ci, ENT_QUOTES, 'UTF-8') ?>" target="_blank"
                                   style="font-size:.75rem;color:var(--primary);">
                                    <i class="fa-solid fa-external-link-alt"></i> Ver formulario
                                </a>
                                <button class="btn btn-sm btn-outline-success"
                                        style="font-size:.72rem;padding:.15rem .45rem;"
                                        onclick="navigator.clipboard.writeText('<?= htmlspecialchars($url_ci, ENT_QUOTES, 'UTF-8') ?>').then(()=>this.innerHTML='✅')">
                                    <i class="fa-solid fa-copy"></i> Copiar URL
                                </button>
                            </div>
                        <?php else: ?>
                            <span class="no-token">Sin token aún</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Botón asignar/cambiar contraseña -->
                        <button class="btn btn-sm btn-p mb-1 w-100"
                                data-bs-toggle="collapse"
                                data-bs-target="#form-<?= (int)$a['id'] ?>">
                            <i class="fa-solid fa-key me-1"></i>
                            <?= $tiene_token ? 'Cambiar contraseña' : 'Activar check-in' ?>
                        </button>

                        <?php if ($tiene_token): ?>
                        <!-- Botón regenerar token -->
                        <form method="POST" action="setup.php?key=<?= htmlspecialchars(SETUP_KEY, ENT_QUOTES, 'UTF-8') ?>"
                              onsubmit="return confirm('¿Regenerar token? Los enlaces enviados dejarán de funcionar.')">
                            <input type="hidden" name="accion"   value="regenerar">
                            <input type="hidden" name="acc_id"   value="<?= (int)$a['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-warning w-100"
                                    style="font-size:.78rem;">
                                <i class="fa-solid fa-rotate me-1"></i> Regenerar token
                            </button>
                        </form>
                        <?php endif; ?>

                        <!-- Panel colapsable con el formulario de contraseña -->
                        <div class="collapse mt-2" id="form-<?= (int)$a['id'] ?>">
                            <form method="POST"
                                  action="setup.php?key=<?= htmlspecialchars(SETUP_KEY, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="accion" value="asignar">
                                <input type="hidden" name="acc_id" value="<?= (int)$a['id'] ?>">
                                <div class="input-group input-group-sm">
                                    <input type="password"
                                           name="password"
                                           class="form-control"
                                           placeholder="Contraseña (min. 8 car.)"
                                           minlength="8"
                                           required>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </div>
                                <div style="font-size:.72rem;color:#7a8a7a;margin-top:.25rem;">
                                    Se usará para acceder al panel de check-in de este alojamiento.
                                </div>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== INSTRUCCIONES ===== -->
    <div class="card card-s">
        <div class="card-header"><i class="fa-solid fa-circle-question me-1"></i> Pasos</div>
        <div class="card-body">
            <ol style="font-size:.9rem;line-height:2.2;">
                <li>Haz clic en <strong>"Activar check-in"</strong> en cada alojamiento → pon una contraseña → guarda.</li>
                <li>Copia la <strong>URL de check-in</strong> y envíasela a los huéspedes por WhatsApp o email.</li>
                <li>Los huéspedes abren esa URL en su móvil y rellenan el formulario.</li>
                <li>Accede al <strong>panel de administración</strong> en
                    <a href="login.php" style="color:var(--primary);">login.php</a>
                    con el email del alojamiento y la contraseña que acabas de asignar.</li>
                <li><strong>Elimina este archivo</strong> del servidor cuando hayas terminado.</li>
            </ol>
            <a href="login.php" class="btn btn-sm btn-p px-3">
                <i class="fa-solid fa-right-to-bracket me-1"></i> Ir al login del panel
            </a>
        </div>
    </div>

<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

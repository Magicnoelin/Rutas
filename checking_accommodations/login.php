<?php
/**
 * =============================================================================
 * SISTEMA DE CHECK-IN — Login para administradores de alojamientos
 * =============================================================================
 * Archivo  : login.php
 * Acceso   : Público (solo para administradores del alojamiento)
 * Descripción: Pantalla de acceso al panel privado. Verifica credenciales
 *              contra la BD usando password_verify(). Inicia una sesión
 *              segura con regeneración de ID de sesión tras login correcto.
 *
 * SEGURIDAD:
 *   - password_verify() con bcrypt (PASSWORD_BCRYPT)
 *   - session_regenerate_id(true) tras login exitoso
 *   - Token CSRF para el formulario de login
 *   - noindex/nofollow — no indexar en buscadores
 * =============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Iniciar sesión segura
iniciar_sesion_segura();

// Si ya hay sesión activa, redirigir directamente al panel
if (!empty($_SESSION['alojamiento_id']) && is_int($_SESSION['alojamiento_id'])) {
    header('Location: panel.php');
    exit;
}

// ---------------------------------------------------------------------------
// Token CSRF para el formulario de login
// ---------------------------------------------------------------------------
if (empty($_SESSION['csrf_login'])) {
    $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_login'];

// ---------------------------------------------------------------------------
// Variables del formulario
// ---------------------------------------------------------------------------
$error_login  = '';
$email_valor  = '';

// ---------------------------------------------------------------------------
// PROCESAR LOGIN (POST)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verificar CSRF
    $csrf_post = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf_token, $csrf_post)) {
        $error_login = 'Error de seguridad. Recarga la página e inténtalo de nuevo.';
    } else {
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validación básica de formato
        if (empty($email) || empty($password)) {
            $error_login = 'Por favor, introduce tu email y contraseña.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_login = 'El formato del email no es válido.';
        } else {
            $email_valor = $email;

            try {
                $pdo = obtener_pdo();

                // Buscar alojamiento por email (solo activos)
                // NOTA: Siempre ejecutamos password_verify() aunque no exista el usuario,
                // para evitar ataques de tiempo que permitan enumerar emails válidos.
                $stmt = $pdo->prepare(
                    'SELECT id, nombre, password_hash FROM alojamientos WHERE email = ? AND activo = 1 LIMIT 1'
                );
                $stmt->execute([$email]);
                $alojamiento = $stmt->fetch();

                // Verificar contraseña (timing-safe)
                $hash_verificar = $alojamiento['password_hash'] ?? '$2y$12$invalidhashtopreventtimingattack00000000000000000000000';
                $credenciales_ok = password_verify($password, $hash_verificar);

                if ($alojamiento && $credenciales_ok) {
                    // ✅ LOGIN CORRECTO

                    // Regenerar ID de sesión para prevenir session fixation
                    session_regenerate_id(true);

                    // Guardar datos en sesión
                    $_SESSION['alojamiento_id']     = (int) $alojamiento['id'];
                    $_SESSION['alojamiento_nombre'] = $alojamiento['nombre'];
                    $_SESSION['login_at']           = time();

                    // Limpiar token CSRF de login (ya no necesario)
                    unset($_SESSION['csrf_login']);

                    // Redirigir al panel
                    header('Location: panel.php');
                    exit;

                } else {
                    // ❌ Credenciales incorrectas — mensaje genérico (no revelar cuál falla)
                    $error_login = 'Email o contraseña incorrectos. Por favor, inténtalo de nuevo.';

                    // Log de intento fallido (sin revelar datos sensibles)
                    error_log(sprintf(
                        '[CheckIn-Login] Intento fallido — email: %s — IP: %s',
                        substr($email, 0, 3) . '***', // Ofuscar email en logs
                        obtener_ip()
                    ));
                }

            } catch (PDOException $e) {
                error_log('[CheckIn-Login] Error BD: ' . $e->getMessage());
                $error_login = 'Error del sistema. Inténtalo de nuevo más tarde.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- No indexar el panel de acceso -->
    <meta name="robots" content="noindex, nofollow">
    <title>Acceso — <?= APP_NAME ?></title>

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
            background: linear-gradient(160deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            padding: 1rem;
        }

        .login-card {
            background: #fff;
            border-radius: 1.25rem;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .login-logo .icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            font-size: 2rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(47, 82, 51, 0.3);
        }

        .login-logo h1 {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.2rem;
        }

        .login-logo p {
            font-size: 0.85rem;
            color: #7a8a7a;
            margin: 0;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--dark);
        }

        .form-control {
            border: 1.5px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.93rem;
            padding: 0.65rem 0.9rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--focus-ring);
        }

        .input-group .btn-toggle-pass {
            border: 1.5px solid var(--border);
            border-left: none;
            background: #fff;
            color: #7a8a7a;
            border-radius: 0 0.5rem 0.5rem 0;
            transition: color 0.2s;
        }

        .input-group .btn-toggle-pass:hover {
            color: var(--primary);
        }

        .input-group .form-control {
            border-right: none;
            border-radius: 0.5rem 0 0 0.5rem;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            padding: 0.72rem 1.5rem;
            border-radius: 0.6rem;
            width: 100%;
            transition: transform 0.15s, box-shadow 0.15s;
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(47, 82, 51, 0.35);
            color: #fff;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert-error {
            background: #fff2f2;
            border: 1px solid #f5c6cb;
            border-radius: 0.5rem;
            color: #842029;
            padding: 0.75rem 1rem;
            font-size: 0.88rem;
            margin-bottom: 1.25rem;
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.78rem;
            color: #9aaa9a;
        }

        .login-footer a {
            color: var(--secondary);
            text-decoration: none;
        }

        .login-footer a:hover {
            text-decoration: underline;
            color: var(--primary);
        }

        .divider {
            height: 1px;
            background: var(--border);
            margin: 1.5rem 0;
        }
    </style>
</head>
<body>

<div class="login-card">

    <!-- Logo / Encabezado -->
    <div class="login-logo">
        <div class="icon-wrap">🏡</div>
        <h1>Panel de Check-in</h1>
        <p>Acceso exclusivo para administradores de alojamientos</p>
    </div>

    <!-- Mensaje de error -->
    <?php if (!empty($error_login)): ?>
    <div class="alert-error" role="alert">
        <i class="fa-solid fa-circle-xmark me-2"></i>
        <?= esc($error_login) ?>
    </div>
    <?php endif; ?>

    <!-- Formulario de login -->
    <form method="POST" action="login.php" novalidate id="form-login">

        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= esc($csrf_token) ?>">

        <!-- Email -->
        <div class="mb-3">
            <label class="form-label" for="email">
                <i class="fa-solid fa-envelope me-1" style="color:var(--secondary);"></i>
                Correo electrónico
            </label>
            <input type="email"
                   class="form-control"
                   id="email"
                   name="email"
                   value="<?= esc($email_valor) ?>"
                   autocomplete="email"
                   autofocus
                   required
                   placeholder="admin@tualojamiento.es">
        </div>

        <!-- Contraseña -->
        <div class="mb-3">
            <label class="form-label" for="password">
                <i class="fa-solid fa-lock me-1" style="color:var(--secondary);"></i>
                Contraseña
            </label>
            <div class="input-group">
                <input type="password"
                       class="form-control"
                       id="password"
                       name="password"
                       autocomplete="current-password"
                       required
                       placeholder="••••••••">
                <button type="button"
                        class="btn btn-toggle-pass"
                        onclick="togglePassword()"
                        title="Mostrar / ocultar contraseña"
                        aria-label="Mostrar u ocultar contraseña">
                    <i class="fa-solid fa-eye" id="eye-icon"></i>
                </button>
            </div>
        </div>

        <!-- Botón acceder -->
        <button type="submit" class="btn-login btn">
            <i class="fa-solid fa-right-to-bracket me-2"></i>
            Acceder al panel
        </button>

    </form>

    <div class="divider"></div>

    <div class="login-footer">
        <p>
            <i class="fa-solid fa-shield-halved me-1"></i>
            Área privada — Solo para administradores autorizados
        </p>
        <p class="mt-1">
            ¿Problemas de acceso? Contacta con el soporte técnico.
        </p>
    </div>

</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFsgAGFKQHgMRLxkh+MXJqVBRy"
        crossorigin="anonymous"></script>

<script>
/**
 * Alterna la visibilidad de la contraseña en el campo de login.
 */
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eye-icon');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

</body>
</html>

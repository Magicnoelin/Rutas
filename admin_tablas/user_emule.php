<?php
session_start();
include 'db.php';

// 1. Verificación de seguridad
if (!isset($_SESSION['user_id'])) {
    die("Acceso no autorizado. Debes estar logueado como administrador.");
}

// 2. Obtener el ID del usuario a emular desde la URL (?id=XXX)
$user_id_a_emular = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id_a_emular > 0) {
    // 3. Guardar tu ID original de admin (solo si no estamos emulando ya a otro)
    if (!isset($_SESSION['admin_impersonating'])) {
        $_SESSION['original_admin_id'] = $_SESSION['user_id'];
    }
    
    // 4. Cambiar la sesión activa al ID del usuario seleccionado
    $_SESSION['user_id'] = $user_id_a_emular;
    $_SESSION['admin_impersonating'] = true;

    // 5. Redirigir al panel principal de usuario
    header("Location: /user-dashboard.html");
    exit;
} else {
    echo "ID de usuario no válido.";
}
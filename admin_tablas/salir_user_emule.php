<?php
session_start();

if (isset($_SESSION['admin_impersonating']) && $_SESSION['admin_impersonating'] === true) {
    // 1. Restaurar el ID del administrador original
    $_SESSION['user_id'] = $_SESSION['original_admin_id'];
    
    // 2. Limpiar variables temporales de emulación
    unset($_SESSION['original_admin_id']);
    unset($_SESSION['admin_impersonating']);
}

// 3. Redirigir de vuelta a la tabla de usuarios
header("Location: /admin_tablas/usuarios_index.php");
exit;
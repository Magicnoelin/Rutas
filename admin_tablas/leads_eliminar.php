<?php
// leads_eliminar.php
include 'db.php';

// 1. Verificar que nos llega un ID válido por la URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        // 2. Eliminar el lead de forma segura usando PDO
        $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ?");
        $resultado = $stmt->execute([$id]);
        
        if ($resultado) {
            // Redirigir al listado con mensaje de éxito (eliminado)
            header("Location: leads_index.php?status=deleted");
            exit;
        } else {
            // Si por alguna razón no se pudo borrar, redirigir con error
            header("Location: leads_index.php?status=error");
            exit;
        }
    } catch (PDOException $e) {
        // Manejar el error de base de datos
        header("Location: leads_index.php?status=error&msg=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    // Si se intenta acceder directamente sin un ID, volvemos al listado
    header("Location: leads_index.php");
    exit;
}
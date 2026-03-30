<?php
include 'db.php';

// Habilitar errores para ver qué pasa si falla
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $id = $_POST['id'];
        $nuevo_estado = $_POST['nuevo_estado'];
        
        // Ejecutamos la actualización
        $stmt = $pdo->prepare("UPDATE places_of_interest SET is_active = ? WHERE id = ?");
        $stmt->execute([$nuevo_estado, $id]);
        
        // Volver a la lista
        header("Location: lugares_index.php?status=success");
        exit;
        
    } catch (PDOException $e) {
        // Si hay un error de base de datos, lo veremos aquí
        die("Error en la base de datos: " . $e->getMessage());
    }
} else {
    // Si se accede directamente sin POST
    header("Location: lugares_index.php");
    exit;
}
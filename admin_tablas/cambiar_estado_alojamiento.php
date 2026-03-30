<?php
// 1. Incluimos la conexión
include 'db.php';

// 2. Verificamos que lleguen los datos
if (isset($_GET['id']) && isset($_GET['status'])) {
    
    $id = (int)$_GET['id'];
    $status = (int)$_GET['status'];

    try {
        // 3. Preparamos la consulta (asegúrate que la columna es 'is_active')
        $stmt = $pdo->prepare("UPDATE accommodations SET is_active = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        // 4. Si todo va bien, volvemos al index
        header("Location: index.php?msg=actualizado");
        exit;
        
    } catch (PDOException $e) {
        // Si hay un error de base de datos, lo mostramos para saber qué falla
        die("Error en la base de datos: " . $e->getMessage());
    }
} else {
    die("Faltan parámetros: id o status.");
}
?>
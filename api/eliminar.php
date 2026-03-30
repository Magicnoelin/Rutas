<?php
/**
 * API Endpoint: Eliminar Alojamiento
 * DELETE /api/eliminar.php?id=XXX
 */

require_once 'config.php';

// Permitir métodos DELETE y POST
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    // Obtener ID desde query string o body
    $id = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id'])) {
        $id = $_GET['id'];
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $id = isset($data['ID']) ? $data['ID'] : null;
    }
    
    if (!$id || empty($id)) {
        jsonError('ID de alojamiento requerido', 400);
    }
    
    $id = sanitizeInput($id);
    $pdo = getDBConnection();
    
    // Verificar que el alojamiento existe
    $sqlCheck = "SELECT ID, Nombre FROM " . DB_TABLE . " WHERE ID = :id";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->bindValue(':id', $id);
    $stmtCheck->execute();
    $alojamiento = $stmtCheck->fetch();
    
    if (!$alojamiento) {
        jsonError('Alojamiento no encontrado', 404);
    }
    
    // Eliminar alojamiento
    $sql = "DELETE FROM " . DB_TABLE . " WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    
    jsonSuccess([
        'id' => $id,
        'nombre' => $alojamiento['Nombre']
    ], 'Alojamiento eliminado correctamente');
    
} catch (PDOException $e) {
    jsonError('Error al eliminar alojamiento: ' . $e->getMessage(), 500);
}

<?php
/**
 * API Endpoint: Obtener Todas las Provincias
 * GET /api/provincias.php
 */

require_once 'config.php';

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

try {
    $pdo = getDBConnection();

    // Obtener provincias únicas activas
    $sql = "SELECT DISTINCT province FROM accommodations
            WHERE province IS NOT NULL AND province != ''
            AND is_active = 1
            ORDER BY province ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $provincias = $stmt->fetchAll(PDO::FETCH_COLUMN);

    jsonSuccess(['provincias' => $provincias], 'Provincias obtenidas correctamente');

} catch (PDOException $e) {
    jsonError('Error al obtener provincias: ' . $e->getMessage(), 500);
}

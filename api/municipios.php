<?php
/**
 * API Endpoint: Obtener Municipios por Provincia
 * GET /api/municipios.php?provincia=NombreProvincia
 */

require_once 'config.php';

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

try {
    $pdo = getDBConnection();

    // Obtener provincia del parámetro
    $provincia = isset($_GET['provincia']) ? sanitizeInput($_GET['provincia']) : '';

    if (empty($provincia)) {
        jsonError('Provincia requerida', 400);
    }

    // Obtener municipios únicos de la provincia especificada
    $sql = "SELECT DISTINCT municipality FROM accommodations
            WHERE province = ? AND municipality IS NOT NULL AND municipality != ''
            AND is_active = 1
            ORDER BY municipality ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$provincia]);
    $municipios = $stmt->fetchAll(PDO::FETCH_COLUMN);

    jsonSuccess(['municipios' => $municipios], 'Municipios obtenidos correctamente');

} catch (PDOException $e) {
    jsonError('Error al obtener municipios: ' . $e->getMessage(), 500);
}

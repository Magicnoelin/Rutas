<?php
/**
 * DEBUG: Muestra la estructura real de las tablas para corregir search_entity.php
 * BORRAR DESPUÉS DE USAR
 */
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDBConnection();
    $result = [];

    $tables = ['accommodations', 'places_of_interest', 'cultural_events', 'tourist_activities'];

    foreach ($tables as $table) {
        try {
            // Columnas de la tabla
            $cols = $pdo->query("DESCRIBE `$table`")->fetchAll();
            $colNames = array_column($cols, 'Field');
            $result[$table]['columns'] = $colNames;

            // Una fila de ejemplo
            $row = $pdo->query("SELECT * FROM `$table` LIMIT 1")->fetch();
            $result[$table]['sample'] = $row ? array_keys($row) : [];

            // Contar registros
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            $result[$table]['total'] = $count;

        } catch (Exception $e) {
            $result[$table]['error'] = $e->getMessage();
        }
    }

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

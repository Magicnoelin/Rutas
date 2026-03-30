<?php
/**
 * DEBUG: Muestra estructura de las 4 tablas principales + carpetas de imágenes
 * BORRAR DESPUÉS DE USAR
 */
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDBConnection();
    $result = [];

    $tables = [
        'accommodations'     => 'alojamientos',
        'places_of_interest' => 'lugares-interes',
        'cultural_events'    => 'eventos-culturales',
        'activities'         => 'actividades',
    ];

    foreach ($tables as $table => $folder) {
        try {
            $cols = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_COLUMN);
            $result[$table]['columns'] = $cols;

            // Buscar columnas relacionadas con imágenes y slug
            $imgCols  = array_filter($cols, fn($c) => str_contains($c,'photo') || str_contains($c,'image') || str_contains($c,'img') || str_contains($c,'cover'));
            $slugCols = array_filter($cols, fn($c) => str_contains($c,'slug') || str_contains($c,'url') || str_contains($c,'path'));

            $result[$table]['image_cols'] = array_values($imgCols);
            $result[$table]['slug_cols']  = array_values($slugCols);

            // Muestra de 1 fila con esas columnas
            $row = $pdo->query("SELECT * FROM `$table` LIMIT 1")->fetch();
            if ($row) {
                $relevant = [];
                foreach (array_merge($imgCols, $slugCols) as $col) {
                    if (isset($row[$col])) $relevant[$col] = $row[$col];
                }
                $result[$table]['sample_relevant'] = $relevant;
            }

        } catch (Exception $e) {
            $result[$table]['error'] = $e->getMessage();
        }
    }

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

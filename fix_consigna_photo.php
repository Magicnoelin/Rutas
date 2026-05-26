<?php
/**
 * Script para limpiar el espacio en photo1 de la actividad consigna-maletas-soria
 */
define('API_NO_HEADERS', true);
require_once 'api/config.php';

try {
    $pdo = getDBConnection();
    
    // Ver el valor actual
    $stmt = $pdo->prepare("SELECT id, slug, photo1 FROM tourist_activities WHERE slug = 'consigna-maletas-soria'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        echo "Actividad no encontrada\n";
        exit;
    }
    
    echo "ID: " . $row['id'] . "\n";
    echo "Slug: " . $row['slug'] . "\n";
    echo "photo1 ANTES: [" . $row['photo1'] . "]\n";
    echo "photo1 length: " . strlen($row['photo1']) . "\n";
    
    // Limpiar espacios
    $cleaned = trim($row['photo1']);
    
    if ($cleaned !== $row['photo1']) {
        $update = $pdo->prepare("UPDATE tourist_activities SET photo1 = ? WHERE id = ?");
        $update->execute([$cleaned, $row['id']]);
        echo "✅ photo1 CORREGIDO\n";
        echo "photo1 DESPUÉS: [" . $cleaned . "]\n";
    } else {
        echo "ℹ️ No había espacios que limpiar\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

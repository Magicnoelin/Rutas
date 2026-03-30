<?php
require_once 'api/config.php';

try {
    $pdo = getDBConnection();
    
    // Contar total de alojamientos activos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM accommodations WHERE is_active = 1");
    $total = $stmt->fetch()['total'];
    echo "Total alojamientos activos: $total\n\n";
    
    // Contar por provincia
    $stmt = $pdo->query("SELECT province, COUNT(*) as count FROM accommodations WHERE is_active = 1 GROUP BY province ORDER BY province");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Alojamientos por provincia:\n";
    echo "==========================\n";
    foreach($results as $row) {
        echo $row['province'] . ': ' . $row['count'] . " alojamientos\n";
    }
    
    echo "\n\nAlojamientos en Soria (detalle):\n";
    echo "=================================\n";
    $stmt = $pdo->query("SELECT id, name, municipality FROM accommodations WHERE is_active = 1 AND province = 'Soria' ORDER BY name");
    $soriaAccommodations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($soriaAccommodations as $acc) {
        echo "ID: {$acc['id']} - {$acc['name']} ({$acc['municipality']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

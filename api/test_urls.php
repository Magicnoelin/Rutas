<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    // Buscar alojamientos con "curro" o "astial"
    $stmt = $pdo->prepare('SELECT name, slug, photo1, photo2, photo3, photo4 FROM accommodations WHERE name LIKE ? OR slug LIKE ? OR name LIKE ?');
    $stmt->execute(['%curro%', '%astial%', '%Finca%']);
    
    echo "=== ALOJAMIENTOS ENCONTRADOS ===\n";
    $count = 0;
    
    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        echo "\n" . $count . ". " . $result['name'] . " (slug: " . $result['slug'] . ")\n";
        
        for ($i = 1; $i <= 4; $i++) {
            $photoKey = 'photo' . $i;
            $photoValue = $result[$photoKey];
            
            if (!empty($photoValue)) {
                echo "   photo$i: [" . $photoValue . "]\n";
                
                // Verificar si es URL completa
                if (preg_match('/^https?:\/\//', $photoValue)) {
                    echo "   ✓ Es URL completa\n";
                } else {
                    echo "   ✗ NO es URL completa - se agregará prefijo\n";
                }
                
                // Verificar si tiene espacio al inicio
                if (substr($photoValue, 0, 1) === ' ') {
                    echo "   ⚠️ Tiene espacio al inicio!\n";
                }
            } else {
                echo "   photo$i: [VACÍO]\n";
            }
        }
    }
    
    if ($count === 0) {
        echo "No se encontraron alojamientos con esos términos\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
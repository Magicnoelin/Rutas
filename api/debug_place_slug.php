<?php
header('Content-Type: text/plain; charset=utf-8');

$slug = $_GET['slug'] ?? '';
$lang = $_GET['lang'] ?? 'es';

echo "DEBUG: slug=$slug, lang=$lang\n\n";

$host = 'localhost';
$db   = 'u412199647_Rutas';
$user = 'u412199647_olgamarin';
$pass = 'Rutas5Rurales7$';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar en traducciones
    if ($lang !== 'es') {
        $stmt = $pdo->prepare("SELECT * FROM places_of_interest_trads WHERE slug = ? AND language_code = ? LIMIT 1");
        $stmt->execute([$slug, $lang]);
        $trad = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($trad) {
            echo "✅ Encontrado en traducciones:\n";
            print_r($trad);
            
            // Ahora buscar el lugar
            $stmt2 = $pdo->prepare("SELECT * FROM places_of_interest WHERE id = ? AND is_active = 1 LIMIT 1");
            $stmt2->execute([$trad['place_id']]);
            $lugar = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            if ($lugar) {
                echo "\n✅ Lugar encontrado: " . $lugar['name'] . "\n";
            } else {
                echo "\n❌ Lugar no encontrado o inactivo\n";
            }
        } else {
            echo "❌ NO encontrado en traducciones\n";
            
            // Verificar si existe cualquier slug similar
            $stmt3 = $pdo->prepare("SELECT * FROM places_of_interest_trads WHERE slug LIKE ? LIMIT 5");
            $stmt3->execute([%$slug%]);
            $similares = $stmt3->fetchAll(PDO::FETCH_ASSOC);
            
            if ($similares) {
                echo "\nSlugs similares en BD:\n";
                foreach ($similares as $s) {
                    echo "  - {$s['slug']} (lang: {$s['language_code']}, place_id: {$s['place_id']})\n";
                }
            }
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

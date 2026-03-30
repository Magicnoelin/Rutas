<?php
/**
 * Diagnóstico de la API de eventos - TEMPORAL
 * Subir a /api/diagnostico_eventos.php y acceder desde el navegador
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'config.php';

echo "=== DIAGNÓSTICO EVENTO-DETALLE ===\n\n";

// 1. Conexión
echo "1. CONEXIÓN A BASE DE DATOS\n";
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
    echo "   ✓ Conexión OK\n\n";
} catch (PDOException $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n\n";
    exit;
}

// 2. Verificar tabla cultural_events
echo "2. TABLA cultural_events\n";
try {
    $check = $pdo->query("SHOW TABLES LIKE 'cultural_events'");
    $exists = $check->rowCount() > 0;
    echo "   Existe: " . ($exists ? 'SÍ' : 'NO') . "\n";
    
    if ($exists) {
        $count = $pdo->query("SELECT COUNT(*) FROM cultural_events")->fetchColumn();
        echo "   Total registros: $count\n";
        
        $active = $pdo->query("SELECT COUNT(*) FROM cultural_events WHERE is_active = 1")->fetchColumn();
        echo "   Registros activos: $active\n";
        
        // Mostrar columnas
        $cols = $pdo->query("SHOW COLUMNS FROM cultural_events")->fetchAll(PDO::FETCH_COLUMN);
        echo "   Columnas: " . implode(', ', $cols) . "\n";
    }
} catch (PDOException $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Verificar tabla cultural_events_trads
echo "3. TABLA cultural_events_trads\n";
try {
    $check = $pdo->query("SHOW TABLES LIKE 'cultural_events_trads'");
    $exists = $check->rowCount() > 0;
    echo "   Existe: " . ($exists ? 'SÍ' : 'NO') . "\n";
    
    if ($exists) {
        $count = $pdo->query("SELECT COUNT(*) FROM cultural_events_trads")->fetchColumn();
        echo "   Total registros: $count\n";
        
        $cols = $pdo->query("SHOW COLUMNS FROM cultural_events_trads")->fetchAll(PDO::FETCH_COLUMN);
        echo "   Columnas: " . implode(', ', $cols) . "\n";
    }
} catch (PDOException $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Buscar el slug específico
$testSlug = 'semana-santa-zaragoza-2026-procesiones';
echo "4. BUSCAR SLUG: $testSlug\n";
try {
    $stmt = $pdo->prepare("SELECT id, slug, name, is_active FROM cultural_events WHERE slug = ? LIMIT 1");
    $stmt->execute([$testSlug]);
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($evento) {
        echo "   ✓ ENCONTRADO\n";
        echo "   ID: " . $evento['id'] . "\n";
        echo "   Slug: " . $evento['slug'] . "\n";
        echo "   Name: " . $evento['name'] . "\n";
        echo "   is_active: " . $evento['is_active'] . "\n";
    } else {
        echo "   ✗ NO ENCONTRADO con slug exacto\n";
        
        // Buscar slugs similares
        echo "\n   Buscando slugs similares (LIKE '%semana-santa-zaragoza%'):\n";
        $stmt = $pdo->prepare("SELECT id, slug, name, is_active FROM cultural_events WHERE slug LIKE ? LIMIT 10");
        $stmt->execute(['%semana-santa-zaragoza%']);
        $similares = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($similares) > 0) {
            foreach ($similares as $s) {
                echo "   - [{$s['id']}] {$s['slug']} | {$s['name']} | active={$s['is_active']}\n";
            }
        } else {
            echo "   No hay slugs similares con 'semana-santa-zaragoza'\n";
            
            // Buscar cualquier evento con 'semana-santa'
            echo "\n   Buscando slugs con 'semana-santa':\n";
            $stmt = $pdo->prepare("SELECT id, slug, name, is_active FROM cultural_events WHERE slug LIKE ? LIMIT 10");
            $stmt->execute(['%semana-santa%']);
            $similares = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($similares) > 0) {
                foreach ($similares as $s) {
                    echo "   - [{$s['id']}] {$s['slug']} | {$s['name']} | active={$s['is_active']}\n";
                }
            } else {
                echo "   No hay eventos con 'semana-santa' en el slug\n";
            }
        }
    }
} catch (PDOException $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Mostrar últimos 5 eventos
echo "5. ÚLTIMOS 5 EVENTOS ACTIVOS\n";
try {
    $stmt = $pdo->query("SELECT id, slug, name, is_active FROM cultural_events WHERE is_active = 1 ORDER BY id DESC LIMIT 5");
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($eventos as $e) {
        echo "   - [{$e['id']}] {$e['slug']} | {$e['name']}\n";
    }
} catch (PDOException $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Verificar versión PHP y funciones
echo "6. ENTORNO PHP\n";
echo "   PHP Version: " . phpversion() . "\n";
echo "   str_starts_with disponible: " . (function_exists('str_starts_with') ? 'SÍ' : 'NO') . "\n";
echo "\n";

// 7. Probar la consulta simple (sin traducciones)
echo "7. PROBAR CONSULTA SIMPLE (sin JOIN traducciones)\n";
try {
    $stmt = $pdo->prepare("
        SELECT e.*,
            e.name as display_name,
            e.slug as display_slug
        FROM cultural_events e
        WHERE e.slug = :slug AND e.is_active = 1 LIMIT 1
    ");
    $stmt->execute(['slug' => $testSlug]);
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($evento) {
        echo "   ✓ Consulta simple FUNCIONA - Evento encontrado: {$evento['display_name']}\n";
    } else {
        echo "   ✗ Consulta simple OK pero evento no existe con ese slug\n";
    }
} catch (PDOException $e) {
    echo "   ✗ ERROR en consulta simple: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DIAGNÓSTICO ===\n";

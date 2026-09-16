<?php
/**
 * ANALIZAR TRADUCCIONES FALTANTES DE LUGARES DE INTERÉS
 * Compara lugares activos con sus traducciones en places_of_interest_trads
 * Basado en analizar_traducciones_faltantes.php de eventos
 */

require_once __DIR__ . '/api/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <title>Análisis de Traducciones de Lugares | Rutas Rurales</title>
</head>
<body class='bg-light'>
<div class='container py-5'>
    <h1 class='mb-4'>📍 Análisis de Traducciones de Lugares de Interés</h1>";

try {
    $pdo = getDBConnection();
    
    // 1. Obtener todos los lugares activos
    $stmt = $pdo->query("
        SELECT id, name, slug, municipality, province
        FROM places_of_interest
        WHERE is_active = 1
        ORDER BY name
    ");
    $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='alert alert-info'>Total lugares activos: <strong>" . count($lugares) . "</strong></div>";
    
    // 2. Verificar traducciones para cada lugar
    $idiomas = ['en', 'fr', 'de', 'zh'];
    $resultados = [];
    
    foreach ($lugares as $lugar) {
        $resultados[$lugar['id']] = [
            'lugar' => $lugar,
            'traducciones' => []
        ];
        
        foreach ($idiomas as $idioma) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM places_of_interest_trads 
                WHERE place_id = ? AND language_code = ?
            ");
            $stmt->execute([$lugar['id'], $idioma]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $resultados[$lugar['id']]['traducciones'][$idioma] = ($row['count'] > 0);
        }
    }
    
    // 3. Analizar resultados
    $lugaresCompletos = 0;
    $lugaresIncompletos = 0;
    $lugaresSinTraducciones = 0;
    $traduccionesFaltantes = [
        'en' => 0,
        'fr' => 0,
        'de' => 0,
        'zh' => 0
    ];
    
    foreach ($resultados as $id => $data) {
        $count = array_sum($data['traducciones']);
        
        if ($count == 4) {
            $lugaresCompletos++;
        } elseif ($count > 0) {
            $lugaresIncompletos++;
        } else {
            $lugaresSinTraducciones++;
        }
        
        foreach ($idiomas as $idioma) {
            if (!$data['traducciones'][$idioma]) {
                $traduccionesFaltantes[$idioma]++;
            }
        }
    }
    
    // 4. Mostrar resumen
    echo "
    <div class='card mb-4'>
        <div class='card-header bg-primary text-white'>
            <h4 class='mb-0'>📊 Resumen de Traducciones</h4>
        </div>
        <div class='card-body'>
            <table class='table table-bordered'>
                <tr><td>Lugares con TODAS las traducciones (4 idiomas)</td><td><strong class='text-success'>$lugaresCompletos</strong></td></tr>
                <tr><td>Lugares con ALGUNAS traducciones</td><td><strong class='text-warning'>$lugaresIncompletos</strong></td></tr>
                <tr><td>Lugares SIN traducciones</td><td><strong class='text-danger'>$lugaresSinTraducciones</strong></td></tr>
            </table>
        </div>
    </div>
    
    <div class='card mb-4'>
        <div class='card-header'>
            <h4 class='mb-0'>📋 Traducciones Faltantes por Idioma</h4>
        </div>
        <div class='card-body'>
            <table class='table'>
                <tr><td>🇬🇧 Inglés (en)</td><td><strong>{$traduccionesFaltantes['en']}</strong> lugares faltantes</td></tr>
                <tr><td>🇫🇷 Francés (fr)</td><td><strong>{$traduccionesFaltantes['fr']}</strong> lugares faltantes</td></tr>
                <tr><td>🇩🇪 Alemán (de)</td><td><strong>{$traduccionesFaltantes['de']}</strong> lugares faltantes</td></tr>
                <tr><td>🇨🇳 Chino (zh)</td><td><strong>{$traduccionesFaltantes['zh']}</strong> lugares faltantes</td></tr>
            </table>
        </div>
    </div>";
    
    // 5. Mostrar lugares que necesitan traducciones
    echo "
    <div class='card'>
        <div class='card-header'>
            <h4 class='mb-0'>📝 Lugares que Necesitan Traducciones</h4>
        </div>
        <div class='card-body'>
            <table class='table table-striped'>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Municipio</th>
                        <th>Provincia</th>
                        <th>EN</th>
                        <th>FR</th>
                        <th>DE</th>
                        <th>ZH</th>
                    </tr>
                </thead>
                <tbody>";
    
    foreach ($resultados as $id => $data) {
        $lugar = $data['lugar'];
        $traducciones = $data['traducciones'];
        
        // Solo mostrar los que no tienen todas las traducciones
        if (array_sum($traducciones) < 4) {
            $en = $traducciones['en'] ? '✅' : '❌';
            $fr = $traducciones['fr'] ? '✅' : '❌';
            $de = $traducciones['de'] ? '✅' : '❌';
            $zh = $traducciones['zh'] ? '✅' : '❌';
            
            echo "<tr>
                <td>{$lugar['id']}</td>
                <td>{$lugar['name']}</td>
                <td>{$lugar['municipality']}</td>
                <td>{$lugar['province']}</td>
                <td>$en</td>
                <td>$fr</td>
                <td>$de</td>
                <td>$zh</td>
            </tr>";
        }
    }
    
    echo "      </tbody>
            </table>
        </div>
    </div>
    
    <div class='mt-4'>
        <a href='admin_tablas/generar_traducciones_lugares.php' class='btn btn-success'>
            🚀 Generar Traducciones Faltantes
        </a>
        <a href='lugares_index.php' class='btn btn-secondary'>
            ← Volver a Lugares
        </a>
    </div>
</div>
</body>
</html>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

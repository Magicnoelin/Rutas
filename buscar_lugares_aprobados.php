<?php
/**
 * Script para buscar lugares aprobados recientemente
 */

header('Content-Type: text/html; charset=utf-8');

// Configuración de la base de datos
$host = "localhost";
$db   = "u412199647_Rutas";
$user = "u412199647_olgamarin";
$pass = "Rutas5Rurales7$";
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Buscar Lugares Aprobados Recientemente</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>Lugares Aprobados Recientemente</h1>
";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // 1. Buscar lugares aprobados en las últimas 24 horas
    echo "<h2>Lugares aprobados en las últimas 24 horas</h2>";
    $stmt = $pdo->prepare("
        SELECT 
            id,
            name,
            slug,
            category_id,
            moderation_status,
            is_active,
            reviewed_at,
            published_at,
            created_at,
            updated_at
        FROM places_of_interest 
        WHERE moderation_status = 'approved' 
          AND reviewed_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY reviewed_at DESC
    ");
    $stmt->execute();
    $recentApproved = $stmt->fetchAll();
    
    if (count($recentApproved) > 0) {
        echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Slug</th>
                    <th>Category ID</th>
                    <th>Estado</th>
                    <th>Revisado el</th>
                    <th>Publicado el</th>
                </tr>";
        
        foreach ($recentApproved as $place) {
            echo "<tr>
                    <td>{$place['id']}</td>
                    <td>{$place['name']}</td>
                    <td>{$place['slug']}</td>
                    <td>{$place['category_id']}</td>
                    <td>" . ($place['is_active'] ? '✅ Activo' : '❌ Inactivo') . "</td>
                    <td>{$place['reviewed_at']}</td>
                    <td>{$place['published_at']}</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>No se encontraron lugares aprobados en las últimas 24 horas.</p>";
    }
    
    // 2. Buscar todos los lugares aprobados
    echo "<h2>Todos los lugares aprobados</h2>";
    $stmt = $pdo->prepare("
        SELECT 
            id,
            name,
            slug,
            category_id,
            moderation_status,
            is_active,
            reviewed_at,
            published_at
        FROM places_of_interest 
        WHERE moderation_status = 'approved'
        ORDER BY reviewed_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $allApproved = $stmt->fetchAll();
    
    if (count($allApproved) > 0) {
        echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Slug</th>
                    <th>Category ID</th>
                    <th>Estado</th>
                    <th>Revisado el</th>
                    <th>Publicado el</th>
                </tr>";
        
        foreach ($allApproved as $place) {
            echo "<tr>
                    <td>{$place['id']}</td>
                    <td>{$place['name']}</td>
                    <td>{$place['slug']}</td>
                    <td>{$place['category_id']}</td>
                    <td>" . ($place['is_active'] ? '✅ Activo' : '❌ Inactivo') . "</td>
                    <td>{$place['reviewed_at']}</td>
                    <td>{$place['published_at']}</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>No se encontraron lugares aprobados.</p>";
    }
    
    // 3. Verificar si hay lugares con problemas de categoría
    echo "<h2>Lugares con posibles problemas de categoría</h2>";
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.category_id,
            c.name as category_name,
            p.moderation_status,
            p.is_active
        FROM places_of_interest p
        LEFT JOIN categories_places c ON p.category_id = c.id
        WHERE p.category_id IS NULL 
           OR c.id IS NULL
        ORDER BY p.id DESC
    ");
    $stmt->execute();
    $problematicPlaces = $stmt->fetchAll();
    
    if (count($problematicPlaces) > 0) {
        echo "<p class='error'>⚠️ Se encontraron lugares con problemas de categoría:</p>";
        echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Category ID</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                </tr>";
        
        foreach ($problematicPlaces as $place) {
            $categoryInfo = $place['category_name'] ? "✅ {$place['category_name']}" : "❌ Categoría ID {$place['category_id']} no existe";
            echo "<tr>
                    <td>{$place['id']}</td>
                    <td>{$place['name']}</td>
                    <td>{$place['category_id']}</td>
                    <td>{$categoryInfo}</td>
                    <td>" . ($place['is_active'] ? '✅ Activo' : '❌ Inactivo') . "</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='success'>✅ No se encontraron lugares con problemas de categoría.</p>";
    }
    
    // 4. Verificar el historial de moderación
    echo "<h2>Historial de moderación reciente</h2>";
    
    // Primero verificar si existe la tabla de historial
    $tables = ['content_moderation_history', 'accommodation_moderation_history'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "<h3>Tabla: {$table}</h3>";
            
            if ($table === 'content_moderation_history') {
                $historyStmt = $pdo->prepare("
                    SELECT 
                        id,
                        content_type,
                        content_id,
                        action,
                        previous_status,
                        new_status,
                        performed_by,
                        performed_at,
                        notes
                    FROM {$table}
                    WHERE content_type = 'place'
                    ORDER BY performed_at DESC
                    LIMIT 10
                ");
                $historyStmt->execute();
            } else {
                $historyStmt = $pdo->prepare("
                    SELECT 
                        id,
                        accommodation_id as content_id,
                        action,
                        previous_status,
                        new_status,
                        performed_by,
                        performed_at,
                        notes
                    FROM {$table}
                    WHERE action = 'approved'
                    ORDER BY performed_at DESC
                    LIMIT 10
                ");
                $historyStmt->execute();
            }
            
            $history = $historyStmt->fetchAll();
            
            if (count($history) > 0) {
                echo "<table>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Content ID</th>
                            <th>Acción</th>
                            <th>Estado Anterior</th>
                            <th>Estado Nuevo</th>
                            <th>Realizado por</th>
                            <th>Fecha</th>
                        </tr>";
                
                foreach ($history as $record) {
                    echo "<tr>
                            <td>{$record['id']}</td>
                            <td>" . ($table === 'content_moderation_history' ? $record['content_type'] : 'accommodation') . "</td>
                            <td>{$record['content_id']}</td>
                            <td>{$record['action']}</td>
                            <td>{$record['previous_status']}</td>
                            <td>{$record['new_status']}</td>
                            <td>{$record['performed_by']}</td>
                            <td>{$record['performed_at']}</td>
                          </tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No hay registros en esta tabla.</p>";
            }
        }
    }
    
    // 5. Resumen
    echo "<h2>Resumen</h2>";
    $totalPlaces = $pdo->query("SELECT COUNT(*) FROM places_of_interest")->fetchColumn();
    $approvedPlaces = $pdo->query("SELECT COUNT(*) FROM places_of_interest WHERE moderation_status = 'approved'")->fetchColumn();
    $pendingPlaces = $pdo->query("SELECT COUNT(*) FROM places_of_interest WHERE moderation_status = 'pending'")->fetchColumn();
    $draftPlaces = $pdo->query("SELECT COUNT(*) FROM places_of_interest WHERE moderation_status = 'draft'")->fetchColumn();
    
    echo "<ul>
            <li>Total de lugares: {$totalPlaces}</li>
            <li>Lugares aprobados: {$approvedPlaces}</li>
            <li>Lugares pendientes: {$pendingPlaces}</li>
            <li>Lugares en borrador: {$draftPlaces}</li>
          </ul>";
    
    echo "<h3>Recomendaciones:</h3>";
    echo "<ol>
            <li>Si no ves los lugares que aprobaste, verifica que tengan <code>moderation_status = 'approved'</code></li>
            <li>Verifica que tengan <code>is_active = 1</code> para que sean visibles</li>
            <li>Revisa la columna <code>reviewed_at</code> para ver cuándo fueron aprobados</li>
            <li>Si los lugares tienen problemas de categoría, ejecuta el script <code>fix_categories_places.sql</code></li>
          </ol>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Error de conexión a la base de datos: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
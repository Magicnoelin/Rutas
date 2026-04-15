<?php
/**
 * Script para recuperar lugares que parecen perdidos después de la aprobación
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
    <title>Recuperar Lugares Perdidos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .btn { padding: 10px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <h1>Recuperar Lugares Perdidos Después de Aprobación</h1>
";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // 1. Buscar lugares con inconsistencias
    echo "<h2>Lugares con inconsistencias en el estado</h2>";
    
    // a) Lugares aprobados pero inactivos
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
          AND is_active = 0
        ORDER BY reviewed_at DESC
    ");
    $stmt->execute();
    $approvedButInactive = $stmt->fetchAll();
    
    if (count($approvedButInactive) > 0) {
        echo "<h3>Lugares aprobados pero inactivos (deberían estar activos)</h3>";
        echo "<form method='POST' action=''>
                <input type='hidden' name='action' value='activate_approved'>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Slug</th>
                        <th>Estado</th>
                        <th>Revisado el</th>
                        <th>Acción</th>
                    </tr>";
        
        foreach ($approvedButInactive as $place) {
            echo "<tr>
                    <td>{$place['id']}</td>
                    <td>{$place['name']}</td>
                    <td>{$place['slug']}</td>
                    <td>✅ Aprobado pero ❌ Inactivo</td>
                    <td>{$place['reviewed_at']}</td>
                    <td><input type='checkbox' name='place_ids[]' value='{$place['id']}'></td>
                  </tr>";
        }
        
        echo "</table>
              <button type='submit' class='btn'>Activar lugares seleccionados (is_active = 1)</button>
              </form>";
    } else {
        echo "<p class='success'>✅ No hay lugares aprobados pero inactivos.</p>";
    }
    
    // b) Lugares con moderation_status = 'approved' pero sin reviewed_at
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
          AND reviewed_at IS NULL
        ORDER BY id DESC
    ");
    $stmt->execute();
    $approvedNoReviewDate = $stmt->fetchAll();
    
    if (count($approvedNoReviewDate) > 0) {
        echo "<h3>Lugares aprobados sin fecha de revisión</h3>";
        echo "<form method='POST' action=''>
                <input type='hidden' name='action' value='fix_review_dates'>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Slug</th>
                        <th>Estado</th>
                        <th>Revisado el</th>
                        <th>Acción</th>
                    </tr>";
        
        foreach ($approvedNoReviewDate as $place) {
            echo "<tr>
                    <td>{$place['id']}</td>
                    <td>{$place['name']}</td>
                    <td>{$place['slug']}</td>
                    <td>✅ Aprobado sin fecha</td>
                    <td>{$place['reviewed_at'] ?: 'NULL'}</td>
                    <td><input type='checkbox' name='place_ids[]' value='{$place['id']}'></td>
                  </tr>";
        }
        
        echo "</table>
              <button type='submit' class='btn'>Establecer fecha de revisión (NOW())</button>
              </form>";
    } else {
        echo "<p class='success'>✅ No hay lugares aprobados sin fecha de revisión.</p>";
    }
    
    // 2. Buscar lugares recién creados (últimas 2 horas) que podrían ser los que aprobaste
    echo "<h2>Lugares creados recientemente (últimas 2 horas)</h2>";
    $stmt = $pdo->prepare("
        SELECT 
            id,
            name,
            slug,
            category_id,
            moderation_status,
            is_active,
            created_at,
            updated_at,
            reviewed_at
        FROM places_of_interest 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $recentlyCreated = $stmt->fetchAll();
    
    if (count($recentlyCreated) > 0) {
        echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Slug</th>
                    <th>Categoría ID</th>
                    <th>Estado Moderación</th>
                    <th>Activo</th>
                    <th>Creado el</th>
                    <th>Revisado el</th>
                </tr>";
        
        foreach ($recentlyCreated as $place) {
            $modStatus = $place['moderation_status'];
            $statusIcon = ($modStatus === 'approved') ? '✅' : 
                         (($modStatus === 'pending') ? '🟡' : 
                         (($modStatus === 'draft') ? '📝' : '❓'));
            
            $activeIcon = $place['is_active'] ? '✅' : '❌';
            
            echo "<tr>
                    <td>{$place['id']}</td>
                    <td>{$place['name']}</td>
                    <td>{$place['slug']}</td>
                    <td>{$place['category_id']}</td>
                    <td>{$statusIcon} {$modStatus}</td>
                    <td>{$activeIcon}</td>
                    <td>{$place['created_at']}</td>
                    <td>{$place['reviewed_at'] ?: 'No revisado'}</td>
                  </tr>";
        }
        echo "</table>";
        
        echo "<h3>Acciones rápidas</h3>";
        echo "<form method='POST' action='' style='margin-bottom: 20px;'>
                <input type='hidden' name='action' value='approve_recent'>
                <p>¿Son estos los lugares que aprobaste? Puedes aprobarlos masivamente:</p>
                <button type='submit' class='btn'>Aprobar todos los lugares recientes (moderation_status = 'approved', is_active = 1)</button>
              </form>";
    } else {
        echo "<p class='warning'>No se encontraron lugares creados en las últimas 2 horas.</p>";
    }
    
    // 3. Verificar si hay lugares con category_id que no existe
    echo "<h2>Lugares con problemas de categoría (posible causa del error)</h2>";
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.category_id,
            c.name as category_name,
            p.moderation_status,
            p.is_active,
            p.created_at
        FROM places_of_interest p
        LEFT JOIN categories_places c ON p.category_id = c.id
        WHERE c.id IS NULL 
          AND p.category_id IS NOT NULL
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $invalidCategoryPlaces = $stmt->fetchAll();
    
    if (count($invalidCategoryPlaces) > 0) {
        echo "<p class='error'>⚠️ Se encontraron lugares con category_id que no existe en categories_places:</p>";
        echo "<form method='POST' action=''>
                <input type='hidden' name='action' value='fix_categories'>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Category ID</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Creado el</th>
                        <th>Nueva Categoría</th>
                    </tr>";
        
        foreach ($invalidCategoryPlaces as $place) {
            echo "<tr>
                    <td>{$place['id']}</td>
                    <td>{$place['name']}</td>
                    <td>{$place['category_id']}</td>
                    <td class='error'>❌ No existe (ID: {$place['category_id']})</td>
                    <td>" . ($place['is_active'] ? '✅ Activo' : '❌ Inactivo') . "</td>
                    <td>{$place['created_at']}</td>
                    <td>
                        <select name='new_category[{$place['id']}]'>
                            <option value=''>-- Seleccionar --</option>";
            
            // Obtener categorías válidas
            $catStmt = $pdo->query("SELECT id, name FROM categories_places WHERE is_active = 1 ORDER BY name");
            $categories = $catStmt->fetchAll();
            foreach ($categories as $cat) {
                echo "<option value='{$cat['id']}'>{$cat['name']} (ID: {$cat['id']})</option>";
            }
            
            echo "</select>
                    </td>
                  </tr>";
        }
        
        echo "</table>
              <button type='submit' class='btn'>Corregir categorías seleccionadas</button>
              </form>";
    } else {
        echo "<p class='success'>✅ No hay lugares con category_id inválido.</p>";
    }
    
    // 4. Procesar acciones POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        echo "<h2>Resultado de la acción: {$action}</h2>";
        
        try {
            if ($action === 'activate_approved' && isset($_POST['place_ids'])) {
                $placeIds = $_POST['place_ids'];
                $placeIdsStr = implode(',', array_map('intval', $placeIds));
                
                $stmt = $pdo->prepare("
                    UPDATE places_of_interest 
                    SET is_active = 1,
                        updated_at = NOW()
                    WHERE id IN ({$placeIdsStr})
                      AND moderation_status = 'approved'
                ");
                $stmt->execute();
                
                $count = $stmt->rowCount();
                echo "<p class='success'>✅ Activados {$count} lugares aprobados.</p>";
                
            } elseif ($action === 'fix_review_dates' && isset($_POST['place_ids'])) {
                $placeIds = $_POST['place_ids'];
                $placeIdsStr = implode(',', array_map('intval', $placeIds));
                
                $stmt = $pdo->prepare("
                    UPDATE places_of_interest 
                    SET reviewed_at = NOW(),
                        published_at = COALESCE(published_at, NOW()),
                        updated_at = NOW()
                    WHERE id IN ({$placeIdsStr})
                      AND moderation_status = 'approved'
                      AND reviewed_at IS NULL
                ");
                $stmt->execute();
                
                $count = $stmt->rowCount();
                echo "<p class='success'>✅ Actualizadas fechas de revisión para {$count} lugares.</p>";
                
            } elseif ($action === 'approve_recent') {
                $stmt = $pdo->prepare("
                    UPDATE places_of_interest 
                    SET moderation_status = 'approved',
                        is_active = 1,
                        reviewed_at = NOW(),
                        published_at = NOW(),
                        updated_at = NOW()
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
                      AND moderation_status != 'approved'
                ");
                $stmt->execute();
                
                $count = $stmt->rowCount();
                echo "<p class='success'>✅ Aprobados {$count} lugares recientes.</p>";
                
            } elseif ($action === 'fix_categories' && isset($_POST['new_category'])) {
                $updated = 0;
                foreach ($_POST['new_category'] as $placeId => $newCategoryId) {
                    if (!empty($newCategoryId)) {
                        $placeId = intval($placeId);
                        $newCategoryId = intval($newCategoryId);
                        
                        // Verificar que la nueva categoría existe
                        $checkStmt = $pdo->prepare("SELECT id FROM categories_places WHERE id = ?");
                        $checkStmt->execute([$newCategoryId]);
                        
                        if ($checkStmt->fetch()) {
                            $updateStmt = $pdo->prepare("
                                UPDATE places_of_interest 
                                SET category_id = ?,
                                    updated_at = NOW()
                                WHERE id = ?
                            ");
                            $updateStmt->execute([$newCategoryId, $placeId]);
                            $updated++;
                        }
                    }
                }
                
                echo "<p class='success'>✅ Actualizadas {$updated} categorías.</p>";
            }
            
            echo "<p><a href='recuperar_lugares_perdidos.php'>Volver a cargar la página</a></p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error al procesar la acción: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // 5. Resumen y recomendaciones
    echo "<h2>Recomendaciones</h2>";
    echo "<ol>
            <li><strong>Si los lugares que aprobaste no aparecen:</strong>
                <ul>
                    <li>Verifica en la sección 'Lugares creados recientemente' si están ahí</li>
                    <li>Si están pero no están aprobados, usa 'Aprobar todos los lugares recientes'</li>
                    <li>Si están aprobados pero inactivos, actívalos</li>
                </ul>
            </li>
            <li><strong>Si recibiste el error de foreign key constraint:</strong>
                <ul>
                    <li>Verifica en 'Lugares con problemas de categoría'</li>
                    <li>Corrige las categorías inválidas</li>
                    <li>Ejecuta el script <code>fix_categories_places.sql</code></li>
                </ul>
            </li>
            <li><strong>Para prevenir problemas futuros:</strong>
                <ul>
                    <li>Asegúrate de que todas las categorías existan antes de crear lugares</li>
                    <li>Usa el dropdown de categorías en lugar de ingresar IDs manualmente</li>
                    <li>Verifica que el proceso de aprobación establezca <code>is_active = 1</code></li>
                </ul>
            </li>
          </ol>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Error de conexión a la base de datos: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
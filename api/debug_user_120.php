<?php
/**
 * Debug: Ver recursos del usuario 120
 * Ejecutar: https://rutasrurales.io/api/debug_user_120.php
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Debug: Recursos del Usuario 120</h1>";

try {
    $pdo = getDBConnection();
    
    // 1. Verificar que existe el usuario 120
    echo "<h2>1. Usuario 120</h2>";
    $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = 120");
    $stmtUser->execute();
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<p style='color:red'>❌ Usuario 120 NO existe</p>";
    } else {
        echo "<p>✅ Usuario encontrado:</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $user['id'] . "</li>";
        echo "<li><strong>Email:</strong> " . $user['email'] . "</li>";
        echo "<li><strong>Nombre:</strong> " . $user['first_name'] . " " . $user['last_name'] . "</li>";
        echo "<li><strong>User Type:</strong> " . $user['user_type'] . "</li>";
        echo "</ul>";
    }
    
    // 2. Ver vinculaciones en user_resources
    echo "<h2>2. Vinculaciones en user_resources</h2>";
    $stmtRes = $pdo->prepare("
        SELECT * FROM user_resources 
        WHERE user_id = 120 
        ORDER BY resource_type, resource_id
    ");
    $stmtRes->execute();
    $resources = $stmtRes->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($resources) === 0) {
        echo "<p style='color:red'>❌ No hay vinculaciones en user_resources para user_id=120</p>";
    } else {
        echo "<p>✅ Encontradas " . count($resources) . " vinculaciones:</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
        echo "<tr><th>ID</th><th>Resource Type</th><th>Resource ID</th><th>Role</th><th>Status</th><th>Created At</th></tr>";
        foreach ($resources as $r) {
            echo "<tr>";
            echo "<td>" . $r['id'] . "</td>";
            echo "<td>" . $r['resource_type'] . "</td>";
            echo "<td>" . $r['resource_id'] . "</td>";
            echo "<td>" . $r['role'] . "</td>";
            echo "<td>" . $r['status'] . "</td>";
            echo "<td>" . $r['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Ver alojamientos que tienen created_by = 120
    echo "<h2>3. Alojamientos con created_by = 120</h2>";
    $stmtAcc = $pdo->prepare("
        SELECT id, name, slug, municipality, province, created_by 
        FROM accommodations 
        WHERE created_by = 120
    ");
    $stmtAcc->execute();
    $accommodations = $stmtAcc->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($accommodations) === 0) {
        echo "<p>No hay alojamientos con created_by = 120</p>";
    } else {
        echo "<p>Encontrados " . count($accommodations) . " alojamientos:</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Slug</th><th>Municipio</th><th>Provincia</th></tr>";
        foreach ($accommodations as $a) {
            echo "<tr>";
            echo "<td>" . $a['id'] . "</td>";
            echo "<td>" . $a['name'] . "</td>";
            echo "<td>" . $a['slug'] . "</td>";
            echo "<td>" . $a['municipality'] . "</td>";
            echo "<td>" . $a['province'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Comparar: ¿están todos los created_by en user_resources?
    echo "<h2>4. Análisis: ¿Faltan vinculaciones?</h2>";
    $idsFromCreatedBy = array_column($accommodations, 'id');
    $idsFromUserResources = array_column($resources, 'resource_id');
    
    $faltantes = array_diff($idsFromCreatedBy, $idsFromUserResources);
    
    if (count($faltantes) > 0) {
        echo "<p style='color:red'>❌ Faltan " . count($faltantes) . " alojamientos por vincular:</p>";
        echo "<ul>";
        foreach ($faltantes as $id) {
            echo "<li>Alojamiento ID: $id</li>";
        }
        echo "</ul>";
        echo "<p><strong>SOLUCIÓN:</strong> Ejecutar:</p>";
        echo "<pre>INSERT INTO user_resources (user_id, resource_type, resource_id, role, status) VALUES ";
        $values = [];
        foreach ($faltantes as $id) {
            $values[] = "(120, 'accommodation', $id, 'owner', 'active')";
        }
        echo implode(",\n", $values) . ";</pre>";
    } else {
        echo "<p style='color:green'>✅ Todos los alojamientos están vinculados correctamente</p>";
    }
    
    // 5. Simular respuesta de API
    echo "<h2>5. Respuesta que vería el usuario 120</h2>";
    // Ejecutar la lógica de la API
    $stmtResources = $pdo->prepare("
        SELECT 
            ur.id AS link_id,
            ur.resource_type,
            ur.resource_id,
            ur.role,
            ur.status,
            a.name,
            a.slug,
            a.municipality,
            a.province,
            a.photo1
        FROM user_resources ur
        LEFT JOIN accommodations a ON ur.resource_id = a.id AND ur.resource_type = 'accommodation'
        WHERE ur.user_id = 120 AND ur.resource_type = 'accommodation'
    ");
    $stmtResources->execute();
    $apiResources = $stmtResources->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre style='background:#f5f5f5;padding:15px;overflow:auto'>";
    echo json_encode([
        'success' => true,
        'data' => [
            'resources' => [
                'accommodation' => array_map(function($r) {
                    return [
                        'data' => [
                            'id' => $r['resource_id'],
                            'name' => $r['name'],
                            'slug' => $r['slug'],
                            'municipality' => $r['municipality'],
                            'province' => $r['province']
                        ]
                    ];
                }, $apiResources)
            ]
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

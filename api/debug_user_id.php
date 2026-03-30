<?php
/**
 * Script de debug para un usuario específico por ID
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

// ID del usuario
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 94;

try {
    $pdo = getDBConnection();
    
    echo "<h1>Debug: Usuario ID {$userId}</h1>";
    
    // 1. Información del usuario
    echo "<h2>1. Información del Usuario</h2>";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p style='color: red;'>❌ Usuario con ID {$userId} no encontrado</p>";
        exit;
    }
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    echo "<tr><td><strong>ID</strong></td><td>{$user['id']}</td></tr>";
    echo "<tr><td><strong>Email</strong></td><td>{$user['email']}</td></tr>";
    echo "<tr><td><strong>Nombre</strong></td><td>{$user['first_name']} {$user['last_name']}</td></tr>";
    echo "<tr><td><strong>Tipo Usuario</strong></td><td>{$user['user_type']}</td></tr>";
    echo "<tr><td><strong>Membresía</strong></td><td>{$user['membership_type']}</td></tr>";
    echo "</table>";
    
    // 2. Alojamientos donde created_by = userId
    echo "<hr><h2>2. Alojamientos con created_by = {$userId}</h2>";
    $stmtAccom = $pdo->prepare("
        SELECT id, name, slug, is_active, created_by, manager_nickname, email, municipality, province
        FROM accommodations 
        WHERE created_by = ?
    ");
    $stmtAccom->execute([$userId]);
    $accommodations = $stmtAccom->fetchAll();
    
    if (count($accommodations) > 0) {
        echo "<p style='color: green;'>✅ Encontrados <strong>" . count($accommodations) . "</strong> alojamientos</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Ubicación</th><th>Activo</th><th>Manager</th></tr>";
        foreach ($accommodations as $acc) {
            $activeStyle = $acc['is_active'] ? 'color: green;' : 'color: red;';
            echo "<tr>";
            echo "<td><strong>{$acc['id']}</strong></td>";
            echo "<td>{$acc['name']}</td>";
            echo "<td>{$acc['municipality']}, {$acc['province']}</td>";
            echo "<td style='$activeStyle'>" . ($acc['is_active'] ? '✅ Activo' : '❌ Inactivo') . "</td>";
            echo "<td>{$acc['manager_nickname']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No hay alojamientos con created_by = {$userId}</p>";
        
        // Buscar por email
        echo "<h3>Buscando por email...</h3>";
        $stmtByEmail = $pdo->prepare("
            SELECT id, name, slug, is_active, email, manager_nickname
            FROM accommodations 
            WHERE email = ?
        ");
        $stmtByEmail->execute([$user['email']]);
        $byEmail = $stmtByEmail->fetchAll();
        
        if (count($byEmail) > 0) {
            echo "<p style='color: blue;'>ℹ️ Encontrados " . count($byEmail) . " alojamientos con el mismo email</p>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Activo</th><th>created_by</th></tr>";
            foreach ($byEmail as $acc) {
                echo "<tr>";
                echo "<td>{$acc['id']}</td>";
                echo "<td>{$acc['name']}</td>";
                echo "<td>" . ($acc['is_active'] ? '✅' : '❌') . "</td>";
                echo "<td>" . ($acc['created_by'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p><strong>💡 Solución:</strong> Estos alojamientos tienen el mismo email pero no tienen created_by. Necesitas actualizarlos.</p>";
        }
    }
    
    // 3. Verificar tabla user_resources
    echo "<hr><h2>3. Tabla user_resources</h2>";
    $checkTable = $pdo->query("SHOW TABLES LIKE 'user_resources'");
    
    if ($checkTable->rowCount() === 0) {
        echo "<p style='color: red;'>❌ La tabla <code>user_resources</code> NO EXISTE</p>";
        echo "<p><strong>Solución:</strong></p>";
        echo "<pre>mysql -u u412199647_olgamarin -p u412199647_Rutas < api/crear_tabla_user_resources.sql</pre>";
    } else {
        echo "<p style='color: green;'>✅ La tabla existe</p>";
        
        // Ver vinculaciones
        $stmtRes = $pdo->prepare("SELECT * FROM user_resources WHERE user_id = ?");
        $stmtRes->execute([$userId]);
        $resources = $stmtRes->fetchAll();
        
        if (count($resources) > 0) {
            echo "<p style='color: green;'>✅ Encontradas <strong>" . count($resources) . "</strong> vinculaciones</p>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Tipo</th><th>Resource ID</th><th>Rol</th><th>Estado</th><th>Creado</th></tr>";
            foreach ($resources as $res) {
                echo "<tr>";
                echo "<td>{$res['id']}</td>";
                echo "<td><strong>{$res['resource_type']}</strong></td>";
                echo "<td>{$res['resource_id']}</td>";
                echo "<td>{$res['role']}</td>";
                echo "<td>{$res['status']}</td>";
                echo "<td>{$res['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>❌ No hay vinculaciones para este usuario</p>";
            echo "<p><strong>Solución:</strong> Ejecuta el script de vinculación:</p>";
            echo "<pre>https://rutasrurales.io/api/vincular_alojamientos_existentes.php</pre>";
        }
    }
    
    // 4. Verificar tabla resource_stats
    echo "<hr><h2>4. Tabla resource_stats</h2>";
    $checkStats = $pdo->query("SHOW TABLES LIKE 'resource_stats'");
    
    if ($checkStats->rowCount() === 0) {
        echo "<p style='color: red;'>❌ La tabla <code>resource_stats</code> NO EXISTE</p>";
    } else {
        echo "<p style='color: green;'>✅ La tabla existe</p>";
        
        if (count($accommodations) > 0) {
            foreach ($accommodations as $acc) {
                $stmtStats = $pdo->prepare("
                    SELECT * FROM resource_stats 
                    WHERE resource_type = 'accommodation' 
                    AND resource_id = ?
                ");
                $stmtStats->execute([$acc['id']]);
                $stats = $stmtStats->fetch();
                
                if ($stats) {
                    echo "<p>✅ Estadísticas para '{$acc['name']}': Visitas={$stats['views_count']}, Intereses={$stats['interests_count']}</p>";
                } else {
                    echo "<p style='color: orange;'>⚠️ No hay estadísticas para '{$acc['name']}' (ID: {$acc['id']})</p>";
                }
            }
        }
    }
    
    // 5. Probar API get_user_resources
    echo "<hr><h2>5. Prueba de API get_user_resources.php</h2>";
    
    // Simular sesión
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_type'] = $user['user_type'];
    
    echo "<p>Simulando sesión del usuario {$userId}...</p>";
    
    // Hacer petición interna
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://rutasrurales.io/api/get_user_resources.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    $apiResponse = curl_exec($ch);
    curl_close($ch);
    
    echo "<h3>Respuesta de la API:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
    echo htmlspecialchars($apiResponse);
    echo "</pre>";
    
    // Decodificar y mostrar de forma legible
    $decoded = json_decode($apiResponse, true);
    if ($decoded) {
        echo "<h3>Datos Decodificados:</h3>";
        echo "<pre style='background: #e8f5e9; padding: 10px; border-radius: 5px;'>";
        print_r($decoded);
        echo "</pre>";
    }
    
    echo "<hr>";
    echo "<h2>📋 Resumen y Acciones</h2>";
    
    if (count($accommodations) === 0) {
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
        echo "<h3>⚠️ Problema: No hay alojamientos vinculados</h3>";
        echo "<p><strong>Causa:</strong> Los alojamientos no tienen <code>created_by = {$userId}</code></p>";
        echo "<p><strong>Solución:</strong> Actualiza manualmente el campo created_by en la base de datos:</p>";
        echo "<pre>UPDATE accommodations SET created_by = {$userId} WHERE id = [ID_DEL_ALOJAMIENTO];</pre>";
        echo "</div>";
    } else if (count($resources) === 0) {
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
        echo "<h3>⚠️ Problema: Alojamientos existen pero no están vinculados en user_resources</h3>";
        echo "<p><strong>Solución:</strong> Ejecuta el script de vinculación:</p>";
        echo "<pre>https://rutasrurales.io/api/vincular_alojamientos_existentes.php</pre>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
        echo "<h3>✅ Todo está correcto</h3>";
        echo "<p>El usuario tiene alojamientos vinculados. Deberían aparecer en el dashboard.</p>";
        echo "<p><strong>Si no aparecen:</strong> Verifica la consola del navegador en el dashboard para ver errores de JavaScript.</p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

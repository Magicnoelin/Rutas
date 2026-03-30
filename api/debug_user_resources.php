<?php
/**
 * Script de debug para verificar recursos de un usuario específico
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

// Obtener email del usuario desde URL (ej: ?email=casaenrique2@example.com)
$searchEmail = isset($_GET['email']) ? $_GET['email'] : '';
$searchNickname = isset($_GET['nickname']) ? $_GET['nickname'] : 'casaenrique2';

try {
    $pdo = getDBConnection();
    
    echo "<h1>Debug: Recursos del Usuario</h1>";
    
    // Buscar usuario por email o nickname
    echo "<h2>1. Buscar Usuario</h2>";
    
    if ($searchEmail) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email LIKE ?");
        $stmt->execute(["%$searchEmail%"]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email LIKE ? OR first_name LIKE ? OR last_name LIKE ?");
        $stmt->execute(["%$searchNickname%", "%$searchNickname%", "%$searchNickname%"]);
    }
    
    $users = $stmt->fetchAll();
    
    if (count($users) === 0) {
        echo "<p style='color: red;'>❌ No se encontró ningún usuario con ese criterio.</p>";
        echo "<p>Busca por: <code>?nickname=casaenrique2</code> o <code>?email=email@example.com</code></p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Nombre</th><th>Tipo</th><th>Membresía</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td><strong>{$user['id']}</strong></td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "<td>{$user['user_type']}</td>";
            echo "<td>{$user['membership_type']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Para cada usuario, buscar sus alojamientos
        foreach ($users as $user) {
            $userId = $user['id'];
            echo "<hr>";
            echo "<h2>2. Alojamientos donde created_by = {$userId}</h2>";
            
            $stmtAccom = $pdo->prepare("
                SELECT id, name, slug, is_active, created_by, manager_nickname, email
                FROM accommodations 
                WHERE created_by = ?
            ");
            $stmtAccom->execute([$userId]);
            $accommodations = $stmtAccom->fetchAll();
            
            if (count($accommodations) > 0) {
                echo "<p>✅ Encontrados " . count($accommodations) . " alojamientos</p>";
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Nombre</th><th>Slug</th><th>Activo</th><th>Manager</th></tr>";
                foreach ($accommodations as $acc) {
                    echo "<tr>";
                    echo "<td>{$acc['id']}</td>";
                    echo "<td>{$acc['name']}</td>";
                    echo "<td>{$acc['slug']}</td>";
                    echo "<td>" . ($acc['is_active'] ? '✅ Sí' : '❌ No') . "</td>";
                    echo "<td>{$acc['manager_nickname']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p style='color: orange;'>⚠️ No hay alojamientos con created_by = {$userId}</p>";
            }
            
            // Verificar vinculaciones en user_resources
            echo "<h2>3. Vinculaciones en user_resources</h2>";
            
            $checkTable = $pdo->query("SHOW TABLES LIKE 'user_resources'");
            if ($checkTable->rowCount() === 0) {
                echo "<p style='color: red;'>❌ La tabla user_resources NO EXISTE</p>";
                echo "<p><strong>Solución:</strong> Ejecuta el script SQL: <code>api/crear_tabla_user_resources.sql</code></p>";
            } else {
                $stmtRes = $pdo->prepare("
                    SELECT * FROM user_resources 
                    WHERE user_id = ?
                ");
                $stmtRes->execute([$userId]);
                $resources = $stmtRes->fetchAll();
                
                if (count($resources) > 0) {
                    echo "<p>✅ Encontradas " . count($resources) . " vinculaciones</p>";
                    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                    echo "<tr><th>ID</th><th>Tipo</th><th>Resource ID</th><th>Rol</th><th>Estado</th></tr>";
                    foreach ($resources as $res) {
                        echo "<tr>";
                        echo "<td>{$res['id']}</td>";
                        echo "<td>{$res['resource_type']}</td>";
                        echo "<td>{$res['resource_id']}</td>";
                        echo "<td>{$res['role']}</td>";
                        echo "<td>{$res['status']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p style='color: red;'>❌ No hay vinculaciones en user_resources para este usuario</p>";
                    echo "<p><strong>Solución:</strong> Ejecuta: <code>api/vincular_alojamientos_existentes.php</code></p>";
                }
            }
            
            // Probar la API get_user_resources
            echo "<h2>4. Prueba de API get_user_resources.php</h2>";
            echo "<p>Simular sesión del usuario {$userId}...</p>";
            
            // Simular sesión
            session_start();
            $_SESSION['user_id'] = $userId;
            
            // Llamar a la API internamente
            ob_start();
            include 'get_user_resources.php';
            $apiResponse = ob_get_clean();
            
            echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
            echo htmlspecialchars($apiResponse);
            echo "</pre>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

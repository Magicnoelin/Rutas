<?php
/**
 * Diagnóstico: Verificar por qué no se ven los alojamientos del usuario 99 en el dashboard
 */

require_once 'api/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Forzar usuario 99 para diagnóstico
$userId = 99;

echo "<h1>🔍 Diagnóstico Dashboard - Usuario {$userId}</h1>";

try {
    $pdo = getDBConnection();

    // 1. Verificar usuario
    echo "<h2>1. Datos del Usuario</h2>";
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, user_type FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo "<pre>" . print_r($user, true) . "</pre>";
        echo "<p>➡️ El usuario es tipo: <strong>{$user['user_type']}</strong></p>";
        echo "<p>➡️ dashboard.html redirige a: <strong>" . 
             ($user['user_type'] === 'gestor' || $user['user_type'] === 'provider' || $user['user_type'] === 'admin' 
              ? 'gestor-dashboard.html' : 'user-dashboard.html') . "</strong></p>";
    } else {
        echo "<p style='color:red'>❌ Usuario no encontrado</p>";
    }

    // 2. Verificar roles
    echo "<h2>2. Roles del Usuario</h2>";
    $stmt = $pdo->prepare("SELECT r.slug, r.nombre FROM roles r JOIN role_user ru ON r.id = ru.role_id WHERE ru.user_id = ?");
    $stmt->execute([$userId]);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($roles) {
        echo "<pre>" . print_r($roles, true) . "</pre>";
        $roleSlugs = array_column($roles, 'slug');
        echo "<p>➡️ Tiene rol 'alojamiento'? <strong>" . (in_array('alojamiento', $roleSlugs) ? '✅ SÍ' : '❌ NO') . "</strong></p>";
        echo "<p>➡️ En user-dashboard.html, la pestaña 'Mis Alojamientos' SOLO se muestra si tiene el rol 'alojamiento'</p>";
    } else {
        echo "<p style='color:orange'>⚠️ No tiene roles asignados</p>";
    }

    // 3. Verificar user_resources
    echo "<h2>3. user_resources para accommodation</h2>";
    $stmt = $pdo->prepare("SELECT * FROM user_resources WHERE user_id = ? AND resource_type = 'accommodation'");
    $stmt->execute([$userId]);
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($resources) {
        echo "<pre>" . print_r($resources, true) . "</pre>";
        echo "<p>✅ Encontrados " . count($resources) . " registros en user_resources</p>";
    } else {
        echo "<p style='color:red'>❌ No hay registros en user_resources para accommodation</p>";
    }

    // 4. Verificar accommodations 186 y 207 (SIN columna 'status' que no existe)
    echo "<h2>4. Datos de accommodations 186 y 207</h2>";
    $stmt = $pdo->prepare("SELECT id, name, slug, municipality, province, accommodation_type, price_per_night, is_active FROM accommodations WHERE id IN (186, 207)");
    $stmt->execute();
    $accommodations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($accommodations) {
        echo "<pre>" . print_r($accommodations, true) . "</pre>";
    } else {
        echo "<p style='color:red'>❌ No se encontraron los accommodations 186 y 207</p>";
    }

    // 5. Probar la query CORREGIDA de la API (sin 'status')
    echo "<h2>5. Query CORREGIDA de la API (sin columna 'status')</h2>";
    $accommodationColumns = ['id', 'name', 'slug', 'municipality', 'province', 'accommodation_type', 'price_per_night', 'photo1 AS photo', 'is_active'];
    $colsSql = implode(', ', array_map(fn($col) => "a.$col", $accommodationColumns));
    
    $check = $pdo->query("SHOW TABLES LIKE 'user_resources'");
    echo "<p>Tabla user_resources existe: " . ($check->rowCount() > 0 ? '✅ SÍ' : '❌ NO') . "</p>";
    
    if ($check->rowCount() > 0) {
        $stmt = $pdo->prepare("
            SELECT $colsSql
            FROM accommodations a
            JOIN user_resources ur ON a.id = ur.resource_id
                AND ur.resource_type = 'accommodation'
                AND ur.user_id = ?
            ORDER BY a.name ASC
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Resultados: <strong>" . count($result) . "</strong></p>";
        if ($result) {
            echo "<pre>" . print_r($result, true) . "</pre>";
            echo "<p style='color:green;font-weight:bold;font-size:1.2em'>✅ ¡LA API AHORA FUNCIONA CORRECTAMENTE!</p>";
        } else {
            echo "<p style='color:red'>❌ La query JOIN no devuelve resultados</p>";
        }
    }

    // 6. Verificar columnas de accommodations
    echo "<h2>6. Columnas de accommodations</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM accommodations");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $colNames = array_column($columns, 'Field');
    echo "<pre>" . implode(', ', $colNames) . "</pre>";
    echo "<p>NOTA: La columna 'status' NO existe en la tabla. Se ha eliminado de la API.</p>";

} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>

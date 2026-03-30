<?php
/**
 * Script to link accommodation ID 182 to user nuevoaloja1@gmail.com
 */

require_once 'api/config.php';

try {
    $pdo = getDBConnection();

    // 1. Find user ID for nuevoaloja1@gmail.com
    echo "🔍 Buscando usuario nuevoaloja1@gmail.com...\n";
    $stmtUser = $pdo->prepare("SELECT id, first_name, last_name, user_type FROM users WHERE email = ?");
    $stmtUser->execute(['nuevoaloja1@gmail.com']);
    $user = $stmtUser->fetch();

    if (!$user) {
        echo "❌ Error: Usuario nuevoaloja1@gmail.com no encontrado\n";
        exit(1);
    }

    $userId = $user['id'];
    echo "✅ Usuario encontrado: ID {$userId} - {$user['first_name']} {$user['last_name']}\n";

    // 2. Check if accommodation ID 182 exists
    echo "\n🔍 Buscando alojamiento ID 182...\n";
    $stmtAccom = $pdo->prepare("SELECT id, name, created_by FROM accommodations WHERE id = ?");
    $stmtAccom->execute([182]);
    $accommodation = $stmtAccom->fetch();

    if (!$accommodation) {
        echo "❌ Error: Alojamiento con ID 182 no encontrado\n";
        exit(1);
    }

    echo "✅ Alojamiento encontrado: ID {$accommodation['id']} - {$accommodation['name']}\n";
    echo "   Estado actual: created_by = " . ($accommodation['created_by'] ?? 'NULL') . "\n";

    // 3. Check if already linked in user_resources
    echo "\n🔍 Verificando vinculación existente...\n";
    $stmtCheck = $pdo->prepare("
        SELECT id, status FROM user_resources
        WHERE user_id = ? AND resource_type = 'accommodation' AND resource_id = ?
    ");
    $stmtCheck->execute([$userId, 182]);
    $existingLink = $stmtCheck->fetch();

    if ($existingLink) {
        echo "✅ Ya existe vinculación: ID {$existingLink['id']} - Estado: {$existingLink['status']}\n";
        echo "   No es necesario crear nueva vinculación.\n";
        exit(0);
    }

    // 4. Update created_by field if not set
    if (empty($accommodation['created_by'])) {
        echo "\n🔧 Actualizando campo created_by...\n";
        $stmtUpdate = $pdo->prepare("UPDATE accommodations SET created_by = ? WHERE id = ?");
        $stmtUpdate->execute([$userId, 182]);
        echo "✅ Campo created_by actualizado a {$userId}\n";
    }

    // 5. Create link in user_resources table
    echo "\n🔗 Creando vinculación en user_resources...\n";
    $stmtInsert = $pdo->prepare("
        INSERT INTO user_resources (user_id, resource_type, resource_id, role, status)
        VALUES (?, 'accommodation', ?, 'owner', 'active')
    ");
    $stmtInsert->execute([$userId, 182]);
    $linkId = $pdo->lastInsertId();

    echo "✅ Vinculación creada: ID {$linkId}\n";

    // 6. Create resource stats if not exists
    echo "\n📊 Creando estadísticas para el recurso...\n";
    $stmtStats = $pdo->prepare("
        INSERT IGNORE INTO resource_stats (resource_type, resource_id, views_count, interests_count, messages_count, favorites_count)
        VALUES ('accommodation', ?, 0, 0, 0, 0)
    ");
    $stmtStats->execute([182]);
    echo "✅ Estadísticas inicializadas\n";

    // 7. Verify the link
    echo "\n🔍 Verificando vinculación...\n";
    $stmtVerify = $pdo->prepare("
        SELECT ur.id, ur.status, a.name as accommodation_name
        FROM user_resources ur
        JOIN accommodations a ON ur.resource_id = a.id
        WHERE ur.user_id = ? AND ur.resource_type = 'accommodation' AND ur.resource_id = ?
    ");
    $stmtVerify->execute([$userId, 182]);
    $verification = $stmtVerify->fetch();

    if ($verification) {
        echo "✅ Vinculación verificada:\n";
        echo "   - ID: {$verification['id']}\n";
        echo "   - Alojamiento: {$verification['accommodation_name']}\n";
        echo "   - Estado: {$verification['status']}\n";
    } else {
        echo "❌ Error: No se pudo verificar la vinculación\n";
        exit(1);
    }

    echo "\n🎉 ¡Proceso completado con éxito!\n";
    echo "El alojamiento ID 182 ahora está vinculado al usuario nuevoaloja1@gmail.com\n";
    echo "Debería aparecer en el dashboard del usuario en la sección 'Mis Alojamientos'\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
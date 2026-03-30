<?php
/**
 * Script para verificar y corregir la estructura de la tabla conversations
 */

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Permitir acceso solo desde autenticados o para verificación
if (!isset($_SESSION['user_id']) && !isset($_GET['force'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    echo "<h1>🔍 Verificación del Sistema de Chat</h1>";
    
    // 1. Verificar tabla conversations
    echo "<h2>1. Tabla conversations</h2>";
    $checkTable = $pdo->query("SHOW TABLES LIKE 'conversations'");
    
    if ($checkTable->rowCount() === 0) {
        echo "<p style='color: red;'>❌ La tabla conversations NO existe</p>";
        echo "<p>Creando tabla...</p>";
        
        $createSQL = "CREATE TABLE IF NOT EXISTS conversations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_1_id INT NOT NULL,
            user_2_id INT NOT NULL,
            last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_1_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (user_2_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX (user_1_id),
            INDEX (user_2_id),
            INDEX (last_message_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createSQL);
        echo "<p style='color: green;'>✅ Tabla conversations creada</p>";
    } else {
        echo "<p style='color: green;'>✅ Tabla conversations existe</p>";
        
        // Verificar columnas
        $columns = $pdo->query("SHOW COLUMNS FROM conversations")->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Columnas actuales:</h3>";
        echo "<ul>";
        foreach ($columns as $col) {
            echo "<li>$col</li>";
        }
        echo "</ul>";
        
        // Verificar si existen las columnas necesarias
        $hasUser1 = in_array('user_1_id', $columns);
        $hasUser2 = in_array('user_2_id', $columns);
        $hasTourist = in_array('tourist_id', $columns);
        $hasProvider = in_array('provider_id', $columns);
        
        if ($hasTourist && !$hasUser1) {
            echo "<p style='color: orange;'>⚠️ Detectado esquema antiguo (tourist_id)</p>";
            echo "<p>Actualizando a nuevo esquema...</p>";
            
            // Renombrar columnas
            $pdo->exec("ALTER TABLE conversations CHANGE tourist_id user_1_id INT NOT NULL");
            $pdo->exec("ALTER TABLE conversations CHANGE provider_id user_2_id INT NOT NULL");
            
            echo "<p style='color: green;'>✅ Columnas actualizadas</p>";
        } elseif (!$hasUser1 || !$hasUser2) {
            echo "<p style='color: red;'>❌ Faltan columnas user_1_id o user_2_id</p>";
            echo "<p>Creando columnas faltantes...</p>";
            
            if (!$hasUser1) {
                $pdo->exec("ALTER TABLE conversations ADD COLUMN user_1_id INT NOT NULL AFTER id");
            }
            if (!$hasUser2) {
                $pdo->exec("ALTER TABLE conversations ADD COLUMN user_2_id INT NOT NULL AFTER user_1_id");
            }
            
            echo "<p style='color: green;'>✅ Columnas creadas</p>";
        } else {
            echo "<p style='color: green;'>✅ Estructura correcta</p>";
        }
        
        // Verificar índices
        $indexes = $pdo->query("SHOW INDEX FROM conversations")->fetchAll(PDO::FETCH_ASSOC);
        $indexNames = array_column($indexes, 'Key_name');
        
        if (!in_array('user_1_id', $indexNames)) {
            echo "<p>Creando índice user_1_id...</p>";
            $pdo->exec("CREATE INDEX user_1_id ON conversations (user_1_id)");
        }
        if (!in_array('user_2_id', $indexNames)) {
            echo "<p>Creando índice user_2_id...</p>";
            $pdo->exec("CREATE INDEX user_2_id ON conversations (user_2_id)");
        }
        
        echo "<p style='color: green;'>✅ Índices verificados</p>";
    }
    
    // 2. Verificar tabla messages
    echo "<h2>2. Tabla messages</h2>";
    $checkMsgTable = $pdo->query("SHOW TABLES LIKE 'messages'");
    
    if ($checkMsgTable->rowCount() === 0) {
        echo "<p style='color: red;'>❌ La tabla messages NO existe</p>";
        echo "<p>Creando tabla...</p>";
        
        $createSQL = "CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT NOT NULL,
            sender_id INT NOT NULL,
            content TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX (conversation_id),
            INDEX (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createSQL);
        echo "<p style='color: green;'>✅ Tabla messages creada</p>";
    } else {
        echo "<p style='color: green;'>✅ Tabla messages existe</p>";
        
        // Verificar columnas
        $columns = $pdo->query("SHOW COLUMNS FROM messages")->fetchAll(PDO::FETCH_COLUMN);
        
        // Verificar si necesita actualizar de message_text a content
        if (in_array('message_text', $columns) && !in_array('content', $columns)) {
            echo "<p style='color: orange;'>⚠️ Detectado campo message_text antiguo</p>";
            echo "<p>Actualizando a 'content'...</p>";
            $pdo->exec("ALTER TABLE messages CHANGE message_text content TEXT NOT NULL");
            echo "<p style='color: green;'>✅ Campo actualizado</p>";
        }
        
        echo "<p style='color: green;'>✅ Estructura messages correcta</p>";
    }
    
    // 3. Verificar datos de ejemplo
    echo "<h2>3. Verificación de datos</h2>";
    
    // Contar conversaciones
    $countConv = $pdo->query("SELECT COUNT(*) as total FROM conversations")->fetch();
    echo "<p>Conversaciones totales: <strong>{$countConv['total']}</strong></p>";
    
    // Contar mensajes
    $countMsg = $pdo->query("SELECT COUNT(*) as total FROM messages")->fetch();
    echo "<p>Mensajes totales: <strong>{$countMsg['total']}</strong></p>";
    
    // Mostrar últimas conversaciones
    if ($countConv['total'] > 0) {
        echo "<h3>Últimas 5 conversaciones:</h3>";
        $sql = "SELECT c.id, 
                       u1.first_name as user1_name, 
                       u2.first_name as user2_name,
                       c.last_message_at
                FROM conversations c
                JOIN users u1 ON c.user_1_id = u1.id
                JOIN users u2 ON c.user_2_id = u2.id
                ORDER BY c.last_message_at DESC
                LIMIT 5";
        $stmt = $pdo->query($sql);
        $convs = $stmt->fetchAll();
        
        echo "<ul>";
        foreach ($convs as $conv) {
            echo "<li>Conv #{$conv['id']}: {$conv['user1_name']} ↔ {$conv['user2_name']} ({$conv['last_message_at']})</li>";
        }
        echo "</ul>";
    }
    
    echo "<hr>";
    echo "<p style='color: green; font-weight: bold;'>✅ Verificación completada</p>";
    echo "<p>El sistema de chat debería funcionar correctamente ahora.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>
<?php
/**
 * Script de diagnóstico para chat.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

session_start();

echo "<h1>Diagnóstico de Chat API</h1>";

// Simular sesión de usuario
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: orange;'>⚠️ No hay sesión activa. Creando sesión de prueba...</p>";
    $_SESSION['user_id'] = 1; // Usuario de prueba
    $_SESSION['user_type'] = 'turista';
}

echo "<p>✅ User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>✅ User Type: " . $_SESSION['user_type'] . "</p>";

try {
    $pdo = getDBConnection();
    echo "<p>✅ Conexión a BD establecida</p>";
    
    // Verificar tabla conversations
    $checkConv = $pdo->query("SHOW TABLES LIKE 'conversations'");
    if ($checkConv->rowCount() > 0) {
        echo "<p>✅ Tabla 'conversations' existe</p>";
        
        // Ver columnas
        $columns = $pdo->query("SHOW COLUMNS FROM conversations")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Columnas en conversations: " . implode(', ', $columns) . "</p>";
        
        // Contar registros
        $count = $pdo->query("SELECT COUNT(*) FROM conversations")->fetchColumn();
        echo "<p>Registros en conversations: $count</p>";
    } else {
        echo "<p style='color: red;'>❌ Tabla 'conversations' NO existe</p>";
    }
    
    // Verificar tabla messages
    $checkMsg = $pdo->query("SHOW TABLES LIKE 'messages'");
    if ($checkMsg->rowCount() > 0) {
        echo "<p>✅ Tabla 'messages' existe</p>";
        
        $msgColumns = $pdo->query("SHOW COLUMNS FROM messages")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Columnas en messages: " . implode(', ', $msgColumns) . "</p>";
        
        $msgCount = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
        echo "<p>Registros en messages: $msgCount</p>";
    } else {
        echo "<p style='color: red;'>❌ Tabla 'messages' NO existe</p>";
    }
    
    // Verificar tabla users
    $userColumns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>✅ Columnas en users: " . implode(', ', $userColumns) . "</p>";
    
    $hasAvatar = in_array('avatar_url', $userColumns);
    $hasUserType = in_array('user_type', $userColumns);
    
    echo "<p>" . ($hasAvatar ? "✅" : "❌") . " Columna 'avatar_url' " . ($hasAvatar ? "existe" : "NO existe") . "</p>";
    echo "<p>" . ($hasUserType ? "✅" : "❌") . " Columna 'user_type' " . ($hasUserType ? "existe" : "NO existe") . "</p>";
    
    // Intentar ejecutar la consulta de list_conversations
    echo "<hr><h2>Probando consulta list_conversations</h2>";
    
    $userId = $_SESSION['user_id'];
    $avatarColumnSQL = $hasAvatar ? 'u.avatar_url' : 'NULL as avatar_url';
    $userTypeSQL = $hasUserType ? 'u.user_type' : "'turista' as user_type";
    
    $sql = "
        SELECT 
            c.id as conversation_id,
            c.last_message_at,
            CASE 
                WHEN c.user_1_id = :me THEN c.user_2_id
                ELSE c.user_1_id
            END as other_user_id,
            u.first_name,
            u.last_name,
            $avatarColumnSQL,
            $userTypeSQL,
            (SELECT content FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message,
            (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.sender_id != :me) as unread_count
        FROM conversations c
        JOIN users u ON (CASE WHEN c.user_1_id = :me THEN c.user_2_id ELSE c.user_1_id END) = u.id
        WHERE c.user_1_id = :me OR c.user_2_id = :me
        ORDER BY c.last_message_at DESC
    ";
    
    echo "<pre>SQL Query:\n" . htmlspecialchars($sql) . "</pre>";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':me' => $userId]);
    $conversations = $stmt->fetchAll();
    
    echo "<p>✅ Consulta ejecutada exitosamente</p>";
    echo "<p>Conversaciones encontradas: " . count($conversations) . "</p>";
    
    if (count($conversations) > 0) {
        echo "<pre>" . print_r($conversations, true) . "</pre>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error de BD: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>Stack trace:\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error general: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>Stack trace:\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr><h2>Probando acceso directo a chat.php</h2>";

// Simular la llamada a chat.php
$_GET['action'] = 'list_conversations';

echo "<h3>Ejecutando chat.php con action=list_conversations...</h3>";

ob_start();
try {
    include 'chat.php';
    $output = ob_get_clean();
    echo "<p>✅ Respuesta de chat.php:</p>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
} catch (Exception $e) {
    ob_end_clean();
    echo "<p style='color: red;'>❌ Error al ejecutar chat.php: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><a href='chat.php?action=list_conversations' target='_blank'>Probar chat.php?action=list_conversations en nueva ventana</a></p>";
?>

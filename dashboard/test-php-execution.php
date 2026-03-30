<!DOCTYPE html>
<html>
<head>
    <title>Test PHP Execution</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f0f0; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; border: 2px solid #333; }
        .success { border-color: #4CAF50; background: #e8f5e9; }
        .error { border-color: #f44336; background: #ffebee; }
        h2 { margin: 0 0 10px 0; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico de Ejecución PHP</h1>
    
    <div class="box success">
        <h2>✅ PHP está ejecutándose correctamente</h2>
        <p>Si ves este mensaje con fondo verde, PHP se está ejecutando.</p>
        <p><strong>Hora del servidor:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        <p><strong>Versión de PHP:</strong> <?php echo phpversion(); ?></p>
    </div>

    <div class="box">
        <h2>📁 Información del Archivo</h2>
        <p><strong>Archivo actual:</strong> <code><?php echo __FILE__; ?></code></p>
        <p><strong>Directorio:</strong> <code><?php echo __DIR__; ?></code></p>
        <p><strong>URL solicitada:</strong> <code><?php echo $_SERVER['REQUEST_URI'] ?? 'N/A'; ?></code></p>
    </div>

    <div class="box">
        <h2>🔧 Configuración del Servidor</h2>
        <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></p>
        <p><strong>Document Root:</strong> <code><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></code></p>
        <p><strong>Script Filename:</strong> <code><?php echo $_SERVER['SCRIPT_FILENAME'] ?? 'N/A'; ?></code></p>
    </div>

    <div class="box">
        <h2>📝 Prueba de Conexión a Base de Datos</h2>
        <?php
        $configPath = '../api/config.php';
        if (file_exists($configPath)) {
            echo "<p>✅ Archivo config.php encontrado en: <code>$configPath</code></p>";
            try {
                require_once $configPath;
                $pdo = getDBConnection();
                echo "<p class='success'>✅ Conexión a base de datos exitosa</p>";
                
                // Test query
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM accommodations");
                $result = $stmt->fetch();
                echo "<p>📊 Total de alojamientos en BD: <strong>{$result['total']}</strong></p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error de conexión: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p class='error'>❌ No se encuentra el archivo config.php en: <code>$configPath</code></p>";
        }
        ?>
    </div>

    <div class="box">
        <h2>🎯 Siguiente Paso</h2>
        <p>Si ves este mensaje correctamente formateado con PHP ejecutándose:</p>
        <ol>
            <li>El problema NO es el .htaccess</li>
            <li>El problema está en el archivo <code>editar-mi-alojamiento.php</code></li>
            <li>Puede ser un error de sintaxis PHP que hace que se muestre como texto</li>
        </ol>
        <p><a href="editar-mi-alojamiento.php?id=119" style="display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px;">Probar editar-mi-alojamiento.php</a></p>
    </div>
</body>
</html>

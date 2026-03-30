<?php
/**
 * SCRIPT FINAL - Actualizar get_nearby_content.php
 * SUBE ESTE ARCHIVO AL SERVIDOR Y EJECÚTALO
 */

$targetFile = __DIR__ . '/api/get_nearby_content.php';

$newContent = file_get_contents(__DIR__ . '/api/get_nearby_content.php');

// Intentar escribir el archivo
$result = file_put_contents($targetFile, $newContent);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✅ Actualización FINAL</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; padding: 20px; background: #d4edda; border: 2px solid #c3e6cb; border-radius: 8px; margin: 20px 0; }
        .error { color: #dc3545; padding: 20px; background: #f8d7da; border: 2px solid #f5c6cb; border-radius: 8px; margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px; font-weight: bold; }
        .btn:hover { background: #218838; }
        code { background: #f4f4f4; padding: 3px 8px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Actualización FINAL - get_nearby_content.php</h1>
        
        <?php if ($result !== false): ?>
            <div class="success">
                <h2>🎉 ¡Archivo actualizado correctamente!</h2>
                <p><strong>Bytes escritos:</strong> <?php echo number_format($result); ?></p>
                <h3>✅ Correcciones aplicadas:</h3>
                <ul>
                    <li>✅ Parámetros SQL posicionales</li>
                    <li>✅ start_date/end_date en eventos</li>
                    <li>✅ Columna price eliminada</li>
                    <li>✅ Validación mejorada (trim)</li>
                </ul>
            </div>
            
            <h3>🧪 Prueba la API:</h3>
            <a href="/api/get_nearby_content.php?accommodation_id=50" class="btn" target="_blank">Probar con ID=50</a>
            <a href="/api/get_nearby_content.php?accommodation_id=4758" class="btn" target="_blank">Probar con ID=4758</a>
            
        <?php else: ?>
            <div class="error">
                <h2>❌ Error al actualizar</h2>
                <p>Sube manualmente <code>api/get_nearby_content.php</code> por FTP</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
/**
 * Script de Importación de CSV a Base de Datos
 * Ejecutar UNA SOLA VEZ para importar los 148 alojamientos
 * 
 * INSTRUCCIONES:
 * 1. Sube el archivo "Alojamientos 148.csv" a la carpeta api/
 * 2. Accede a: https://rutasrurales.io/api/importar_csv.php
 * 3. El script importará todos los alojamientos
 * 4. ELIMINA este archivo después de usarlo por seguridad
 */

require_once 'config.php';

// Configuración
$csvFile = __DIR__ . '/Alojamientos 148.csv';
$delimiter = ';';
$encoding = 'UTF-8';

// Verificar que el archivo existe
if (!file_exists($csvFile)) {
    die(json_encode([
        'success' => false,
        'error' => 'Archivo CSV no encontrado. Sube "Alojamientos 148.csv" a la carpeta api/'
    ]));
}

try {
    $pdo = getDBConnection();
    
    // Abrir archivo CSV
    $handle = fopen($csvFile, 'r');
    if (!$handle) {
        throw new Exception('No se pudo abrir el archivo CSV');
    }
    
    // Leer encabezados
    $headers = fgetcsv($handle, 0, $delimiter);
    
    // Convertir encabezados a UTF-8 si es necesario
    $headers = array_map(function($header) {
        return mb_convert_encoding(trim($header), 'UTF-8', 'auto');
    }, $headers);
    
    $importados = 0;
    $errores = [];
    $duplicados = 0;
    
    // Leer cada línea del CSV
    while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
        try {
            // Convertir datos a UTF-8
            $data = array_map(function($value) {
                return mb_convert_encoding(trim($value), 'UTF-8', 'auto');
            }, $data);
            
            // Crear array asociativo con los datos
            $row = array_combine($headers, $data);
            
            // Verificar si el ID ya existe
            $sqlCheck = "SELECT ID FROM " . DB_TABLE . " WHERE ID = :id";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->bindValue(':id', $row['ID']);
            $stmtCheck->execute();
            
            if ($stmtCheck->fetch()) {
                $duplicados++;
                continue; // Saltar si ya existe
            }
            
            // Preparar INSERT
            $campos = array_keys($row);
            $camposStr = implode(', ', $campos);
            $placeholders = ':' . implode(', :', $campos);
            
            $sql = "INSERT INTO " . DB_TABLE . " ($camposStr) VALUES ($placeholders)";
            $stmt = $pdo->prepare($sql);
            
            // Bind valores
            foreach ($row as $campo => $valor) {
                // Convertir valores vacíos a NULL
                $valorFinal = ($valor === '' || $valor === 'NULL') ? null : $valor;
                $stmt->bindValue(':' . $campo, $valorFinal);
            }
            
            $stmt->execute();
            $importados++;
            
        } catch (PDOException $e) {
            $errores[] = [
                'id' => $row['ID'] ?? 'desconocido',
                'nombre' => $row['Nombre'] ?? 'desconocido',
                'error' => $e->getMessage()
            ];
        }
    }
    
    fclose($handle);
    
    // Respuesta
    $resultado = [
        'success' => true,
        'mensaje' => 'Importación completada',
        'estadisticas' => [
            'total_procesados' => $importados + $duplicados + count($errores),
            'importados' => $importados,
            'duplicados' => $duplicados,
            'errores' => count($errores)
        ]
    ];
    
    if (!empty($errores)) {
        $resultado['detalles_errores'] = $errores;
    }
    
    // Mostrar resultado en HTML
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Importación CSV - Rutas</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1 {
                color: #2c5f2d;
            }
            .success {
                background: #d4edda;
                color: #155724;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
                margin: 20px 0;
            }
            .stat-card {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                text-align: center;
            }
            .stat-number {
                font-size: 2rem;
                font-weight: bold;
                color: #2c5f2d;
            }
            .stat-label {
                color: #666;
                font-size: 0.9rem;
            }
            .warning {
                background: #fff3cd;
                color: #856404;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .error-list {
                max-height: 300px;
                overflow-y: auto;
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>✅ Importación Completada</h1>
            
            <div class="success">
                <strong>¡Éxito!</strong> Los alojamientos han sido importados a la base de datos.
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $resultado['estadisticas']['importados']; ?></div>
                    <div class="stat-label">Importados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $resultado['estadisticas']['duplicados']; ?></div>
                    <div class="stat-label">Duplicados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $resultado['estadisticas']['errores']; ?></div>
                    <div class="stat-label">Errores</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $resultado['estadisticas']['total_procesados']; ?></div>
                    <div class="stat-label">Total Procesados</div>
                </div>
            </div>
            
            <?php if (!empty($errores)): ?>
            <div class="warning">
                <strong>⚠️ Atención:</strong> Algunos registros no pudieron importarse.
            </div>
            <div class="error-list">
                <h3>Detalles de Errores:</h3>
                <?php foreach ($errores as $error): ?>
                    <p>
                        <strong>ID:</strong> <?php echo htmlspecialchars($error['id']); ?><br>
                        <strong>Nombre:</strong> <?php echo htmlspecialchars($error['nombre']); ?><br>
                        <strong>Error:</strong> <?php echo htmlspecialchars($error['error']); ?>
                    </p>
                    <hr>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="warning" style="margin-top: 30px;">
                <strong>🔒 IMPORTANTE:</strong> Por seguridad, elimina este archivo (importar_csv.php) después de la importación.
            </div>
            
            <p style="text-align: center; margin-top: 30px;">
                <a href="../dashboard.html" style="background: #2c5f2d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                    Ir al Dashboard
                </a>
            </p>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    die(json_encode([
        'success' => false,
        'error' => 'Error durante la importación: ' . $e->getMessage()
    ]));

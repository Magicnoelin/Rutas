<?php
/**
 * SINCRONIZAR ALOJAMIENTOS CON RESOURCE_STATS
 * 
 * Este script inicializa registros en resource_stats para todos los alojamientos
 * que aún no tienen estadísticas registradas.
 * 
 * Ejecutar una vez para migrar datos existentes.
 * Los nuevos alojamientos ya se crearán automáticamente con el nuevo tracking.
 */

require_once 'config.php';

// Solo permitir ejecución desde línea de comandos o con parámetro especial
if (php_sapi_name() !== 'cli' && empty($_GET['force_sync'])) {
    die('❌ Este script debe ejecutarse desde línea de comandos o con ?force_sync=1');
}

$isWebExecution = php_sapi_name() !== 'cli';

if ($isWebExecution) {
    echo '<!DOCTYPE html><html><head><title>Sincronización Alojamientos Stats</title></head><body>';
    echo '<h1>🔄 Sincronización de Estadísticas de Alojamientos</h1>';
    echo '<div style="font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 8px;">';
}

function logMessage($message, $isError = false) {
    global $isWebExecution;
    
    if ($isWebExecution) {
        $color = $isError ? '#d32f2f' : '#2e7d32';
        echo "<div style='color: $color; margin: 5px 0;'>$message</div>";
        echo str_repeat(' ', 4096); // Force output flush
        flush();
    } else {
        echo $message . "\n";
    }
}

try {
    $pdo = getDBConnection();
    
    logMessage("🚀 Iniciando sincronización de alojamientos con resource_stats...");
    
    // 1. Verificar que la tabla resource_stats existe
    $tableExists = false;
    try {
        $pdo->query("SELECT 1 FROM resource_stats LIMIT 1");
        $tableExists = true;
        logMessage("✅ Tabla resource_stats verificada");
    } catch (Exception $e) {
        logMessage("❌ Error: La tabla resource_stats no existe. Debe crearse primero.", true);
        if ($isWebExecution) echo '</div></body></html>';
        exit(1);
    }
    
    // 2. Contar alojamientos totales activos
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM accommodations WHERE is_active = 1");
    $totalAlojamientos = $stmtTotal->fetchColumn();
    logMessage("📊 Total de alojamientos activos: $totalAlojamientos");
    
    // 3. Contar alojamientos que ya tienen estadísticas
    $stmtExistentes = $pdo->query("SELECT COUNT(*) FROM resource_stats WHERE resource_type = 'accommodation'");
    $alojamientosConStats = $stmtExistentes->fetchColumn();
    logMessage("📈 Alojamientos con estadísticas existentes: $alojamientosConStats");
    
    $pendientesSync = $totalAlojamientos - $alojamientosConStats;
    logMessage("⏳ Alojamientos pendientes de sincronizar: $pendientesSync");
    
    if ($pendientesSync == 0) {
        logMessage("🎉 Todos los alojamientos ya están sincronizados. No hay nada que hacer.");
        if ($isWebExecution) echo '</div></body></html>';
        exit(0);
    }
    
    // 4. Obtener alojamientos sin estadísticas
    $sql = "
        SELECT a.id, a.name, a.slug, a.municipality, a.province
        FROM accommodations a
        WHERE a.is_active = 1
        AND NOT EXISTS (
            SELECT 1 FROM resource_stats rs 
            WHERE rs.resource_type = 'accommodation' AND rs.resource_id = a.id
        )
        ORDER BY a.id ASC
    ";
    
    $stmtPendientes = $pdo->query($sql);
    $alojamientosPendientes = $stmtPendientes->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("🔄 Creando registros en resource_stats...");
    
    // 5. Preparar statement para inserción
    $stmtInsert = $pdo->prepare("
        INSERT IGNORE INTO resource_stats 
        (resource_type, resource_id, views_count, interests_count, messages_count, favorites_count, created_at, updated_at)
        VALUES ('accommodation', ?, 0, 0, 0, 0, NOW(), NOW())
    ");
    
    $procesados = 0;
    $errores = 0;
    
    // 6. Procesar alojamientos en lotes de 50
    foreach (array_chunk($alojamientosPendientes, 50) as $lote) {
        foreach ($lote as $alojamiento) {
            try {
                $stmtInsert->execute([$alojamiento['id']]);
                $procesados++;
                
                if ($procesados % 10 == 0) {
                    $percentage = round(($procesados / $pendientesSync) * 100, 1);
                    logMessage("📊 Progreso: $procesados/$pendientesSync ($percentage%)");
                }
                
            } catch (Exception $e) {
                $errores++;
                logMessage("⚠️ Error con alojamiento ID {$alojamiento['id']}: " . $e->getMessage(), true);
            }
        }
        
        // Pequeña pausa para no sobrecargar la base de datos
        if ($isWebExecution) {
            usleep(10000); // 10ms
        }
    }
    
    // 7. Resumen final
    logMessage("✅ Sincronización completada:");
    logMessage("   • Registros creados: $procesados");
    if ($errores > 0) {
        logMessage("   • Errores: $errores", true);
    }
    
    // 8. Verificación final
    $stmtFinal = $pdo->query("SELECT COUNT(*) FROM resource_stats WHERE resource_type = 'accommodation'");
    $totalFinal = $stmtFinal->fetchColumn();
    logMessage("📈 Total de alojamientos en resource_stats después de sync: $totalFinal");
    
    // 9. Mostrar algunos registros de ejemplo
    logMessage("📋 Ejemplos de registros creados:");
    $stmtEjemplos = $pdo->query("
        SELECT rs.resource_id, rs.views_count, rs.created_at, a.name, a.municipality
        FROM resource_stats rs
        JOIN accommodations a ON a.id = rs.resource_id
        WHERE rs.resource_type = 'accommodation'
        ORDER BY rs.created_at DESC
        LIMIT 5
    ");
    
    while ($ejemplo = $stmtEjemplos->fetch(PDO::FETCH_ASSOC)) {
        $fecha = date('d/m/Y H:i', strtotime($ejemplo['created_at']));
        logMessage("   • ID {$ejemplo['resource_id']}: {$ejemplo['name']} ({$ejemplo['municipality']}) - Creado: $fecha");
    }
    
    logMessage("🎉 ¡Sincronización exitosa! El tracking de vistas ya está activo para todos los alojamientos.");
    
} catch (Exception $e) {
    logMessage("❌ Error crítico durante la sincronización: " . $e->getMessage(), true);
    if ($isWebExecution) echo '</div></body></html>';
    exit(1);
}

if ($isWebExecution) {
    echo '</div>';
    echo '<div style="margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 8px; color: #2e7d32;">';
    echo '<strong>✅ Proceso completado exitosamente</strong><br>';
    echo 'Ahora las vistas de alojamientos se registrarán automáticamente en resource_stats.<br>';
    echo 'Puedes verificar las estadísticas en el dashboard de analytics.';
    echo '</div>';
    echo '</body></html>';
}
?>
<?php
/**
 * DIAGNÓSTICO COMPLETO - RESOURCE_STATS ALOJAMIENTOS
 * Analiza el problema de estadísticas de alojamientos desde abril 2026
 */

require_once 'api/config.php';

// Solo permitir ejecución con parámetro especial
if (empty($_GET['diagnosticar']) || $_GET['diagnosticar'] !== 'abril2026') {
    die('❌ Este script debe ejecutarse con ?diagnosticar=abril2026');
}

$isWebExecution = php_sapi_name() !== 'cli';

if ($isWebExecution) {
    echo '<!DOCTYPE html><html><head><title>Diagnóstico Resource Stats - Alojamientos</title>';
    echo '<style>body { font-family: monospace; background: #f5f5f5; padding: 20px; }
          .error { color: #d32f2f; }
          .success { color: #2e7d32; }
          .warning { color: #f57c00; }
          .info { color: #1976d2; }
          .section { border: 1px solid #ddd; margin: 10px 0; padding: 15px; background: white; border-radius: 5px; }
          table { width: 100%; border-collapse: collapse; margin: 10px 0; }
          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
          th { background: #f0f0f0; }
          </style></head><body>';
}

function logMessage($message, $type = 'info') {
    global $isWebExecution;
    
    if ($isWebExecution) {
        $class = $type;
        echo "<div class='$class'>$message</div>";
        echo str_repeat(' ', 1024); // Force output flush
        flush();
    } else {
        echo $message . "\n";
    }
}

function printSection($title) {
    global $isWebExecution;
    if ($isWebExecution) {
        echo "<div class='section'><h2>$title</h2>";
    } else {
        echo "\n=== $title ===\n";
    }
}

function closeSection() {
    global $isWebExecution;
    if ($isWebExecution) {
        echo "</div>";
    }
}

try {
    $pdo = getDBConnection();
    
    logMessage("🔍 INICIANDO DIAGNÓSTICO COMPLETO DE RESOURCE_STATS - ALOJAMIENTOS", 'info');
    logMessage("Fecha: " . date('Y-m-d H:i:s'), 'info');
    
    // ==========================================
    // 1. VERIFICAR EXISTENCIA Y ESTRUCTURA DE RESOURCE_STATS
    // ==========================================
    printSection("1. Verificación de tabla resource_stats");
    
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'resource_stats'");
        if ($stmt->rowCount() > 0) {
            logMessage("✅ Tabla resource_stats existe", 'success');
            
            // Mostrar estructura
            $columns = $pdo->query("DESCRIBE resource_stats")->fetchAll();
            logMessage("📋 Estructura de la tabla:", 'info');
            if ($isWebExecution) {
                echo "<table><tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Default</th></tr>";
                foreach ($columns as $col) {
                    echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
                }
                echo "</table>";
            }
        } else {
            logMessage("❌ ERROR: La tabla resource_stats NO EXISTE", 'error');
            closeSection();
            exit(1);
        }
    } catch (Exception $e) {
        logMessage("❌ Error verificando tabla: " . $e->getMessage(), 'error');
        closeSection();
        exit(1);
    }
    
    closeSection();
    
    // ==========================================
    // 2. ANÁLISIS DE ALOJAMIENTOS TOTALES VS RESOURCE_STATS
    // ==========================================
    printSection("2. Análisis de Alojamientos vs Resource_Stats");
    
    // Total de alojamientos activos
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM accommodations WHERE is_active = 1");
    $totalAlojamientos = $stmtTotal->fetchColumn();
    logMessage("📊 Total alojamientos activos: $totalAlojamientos", 'info');
    
    // Total en resource_stats
    $stmtStats = $pdo->query("SELECT COUNT(*) FROM resource_stats WHERE resource_type = 'accommodation'");
    $totalEnStats = $stmtStats->fetchColumn();
    logMessage("📈 Total alojamientos en resource_stats: $totalEnStats", 'info');
    
    $diferencia = $totalAlojamientos - $totalEnStats;
    if ($diferencia > 0) {
        logMessage("⚠️  PROBLEMA: Faltan $diferencia alojamientos en resource_stats", 'warning');
    } else {
        logMessage("✅ Todos los alojamientos están en resource_stats", 'success');
    }
    
    closeSection();
    
    // ==========================================
    // 3. ANÁLISIS POR FECHAS - FOCUS EN ABRIL 2026
    // ==========================================
    printSection("3. Análisis por fechas de creación (Foco en Abril 2026)");
    
    // Alojamientos creados desde abril 2026
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as mes,
            COUNT(*) as total_alojamientos
        FROM accommodations 
        WHERE is_active = 1 AND created_at >= '2026-04-01'
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY mes
    ");
    $alojamientosPorMes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("📅 Alojamientos creados desde Abril 2026:", 'info');
    if ($isWebExecution) {
        echo "<table><tr><th>Mes</th><th>Alojamientos Creados</th></tr>";
        foreach ($alojamientosPorMes as $row) {
            echo "<tr><td>{$row['mes']}</td><td>{$row['total_alojamientos']}</td></tr>";
        }
        echo "</table>";
    }
    
    // Verificar cuántos de estos están en resource_stats
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(a.created_at, '%Y-%m') as mes,
            COUNT(a.id) as total_alojamientos,
            COUNT(rs.id) as en_resource_stats,
            (COUNT(a.id) - COUNT(rs.id)) as faltantes
        FROM accommodations a
        LEFT JOIN resource_stats rs ON (rs.resource_type = 'accommodation' AND rs.resource_id = a.id)
        WHERE a.is_active = 1 AND a.created_at >= '2026-04-01'
        GROUP BY DATE_FORMAT(a.created_at, '%Y-%m')
        ORDER BY mes
    ");
    $comparacionPorMes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("🔍 Comparación con resource_stats por mes:", 'info');
    if ($isWebExecution) {
        echo "<table><tr><th>Mes</th><th>Total Alojamientos</th><th>En Resource_Stats</th><th>Faltantes</th></tr>";
        $totalFaltantes = 0;
        foreach ($comparacionPorMes as $row) {
            $class = $row['faltantes'] > 0 ? 'warning' : 'success';
            echo "<tr class='$class'><td>{$row['mes']}</td><td>{$row['total_alojamientos']}</td><td>{$row['en_resource_stats']}</td><td>{$row['faltantes']}</td></tr>";
            $totalFaltantes += $row['faltantes'];
        }
        echo "</table>";
        if ($totalFaltantes > 0) {
            logMessage("❌ PROBLEMA IDENTIFICADO: $totalFaltantes alojamientos desde abril NO están en resource_stats", 'error');
        }
    }
    
    closeSection();
    
    // ==========================================
    // 4. ACTIVIDAD RECIENTE EN RESOURCE_STATS
    // ==========================================
    printSection("4. Actividad reciente en resource_stats");
    
    // Últimas actualizaciones de vistas en accommodations
    $stmt = $pdo->query("
        SELECT 
            resource_id,
            views_count,
            last_view_at,
            updated_at
        FROM resource_stats 
        WHERE resource_type = 'accommodation' 
        AND last_view_at IS NOT NULL
        ORDER BY last_view_at DESC 
        LIMIT 10
    ");
    $ultimasVistas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("👀 Últimas 10 vistas registradas en alojamientos:", 'info');
    if ($isWebExecution) {
        echo "<table><tr><th>ID Alojamiento</th><th>Vistas</th><th>Última Vista</th><th>Actualizado</th></tr>";
        foreach ($ultimasVistas as $row) {
            echo "<tr><td>{$row['resource_id']}</td><td>{$row['views_count']}</td><td>{$row['last_view_at']}</td><td>{$row['updated_at']}</td></tr>";
        }
        echo "</table>";
        
        if (empty($ultimasVistas)) {
            logMessage("❌ PROBLEMA CRÍTICO: NO HAY REGISTROS DE VISTAS RECIENTES", 'error');
        }
    }
    
    closeSection();
    
    // ==========================================
    // 5. ALOJAMIENTOS SIN ESTADÍSTICAS
    // ==========================================
    printSection("5. Alojamientos sin estadísticas desde abril");
    
    $stmt = $pdo->query("
        SELECT 
            a.id,
            a.name,
            a.slug,
            a.municipality,
            a.created_at
        FROM accommodations a
        LEFT JOIN resource_stats rs ON (rs.resource_type = 'accommodation' AND rs.resource_id = a.id)
        WHERE a.is_active = 1 
        AND a.created_at >= '2026-04-01'
        AND rs.id IS NULL
        ORDER BY a.created_at DESC
        LIMIT 20
    ");
    $sinEstadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($sinEstadisticas) {
        logMessage("❌ Alojamientos sin estadísticas (primeros 20):", 'error');
        if ($isWebExecution) {
            echo "<table><tr><th>ID</th><th>Nombre</th><th>Slug</th><th>Municipio</th><th>Creado</th></tr>";
            foreach ($sinEstadisticas as $row) {
                echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['slug']}</td><td>{$row['municipality']}</td><td>{$row['created_at']}</td></tr>";
            }
            echo "</table>";
        }
    } else {
        logMessage("✅ Todos los alojamientos desde abril tienen estadísticas", 'success');
    }
    
    closeSection();
    
    // ==========================================
    // 6. VERIFICAR API track_resource_stat.php
    // ==========================================
    printSection("6. Verificación de API track_resource_stat.php");
    
    $trackApiPath = 'api/track_resource_stat.php';
    if (file_exists($trackApiPath)) {
        logMessage("✅ API track_resource_stat.php existe", 'success');
        $fileModTime = date('Y-m-d H:i:s', filemtime($trackApiPath));
        logMessage("📅 Última modificación: $fileModTime", 'info');
    } else {
        logMessage("❌ ERROR: API track_resource_stat.php NO EXISTE", 'error');
    }
    
    closeSection();
    
    // ==========================================
    // 7. RECOMENDACIONES Y SOLUCIONES
    // ==========================================
    printSection("7. Recomendaciones y Soluciones");
    
    if ($diferencia > 0) {
        logMessage("🔧 SOLUCIÓN RECOMENDADA:", 'warning');
        logMessage("1. Ejecutar sincronización: /api/sincronizar_alojamientos_stats.php?force_sync=1", 'info');
        logMessage("2. Verificar que las funciones JavaScript trackAccommodationView() se ejecuten", 'info');
        logMessage("3. Revisar logs del servidor para errores en track_resource_stat.php", 'info');
    }
    
    if (empty($ultimasVistas)) {
        logMessage("🔧 PROBLEMA CRÍTICO - TRACKING NO FUNCIONA:", 'error');
        logMessage("1. Verificar que track_resource_stat.php funcione correctamente", 'error');
        logMessage("2. Revisar JavaScript en las páginas de alojamientos", 'error');
        logMessage("3. Verificar configuración de base de datos", 'error');
    }
    
    closeSection();
    
    logMessage("✅ Diagnóstico completado", 'success');
    
    if ($isWebExecution) {
        echo "<div class='section'>";
        echo "<h2>Acciones Rápidas</h2>";
        echo "<p><a href='/api/sincronizar_alojamientos_stats.php?force_sync=1' target='_blank'>🔧 Ejecutar Sincronización</a></p>";
        echo "<p><a href='/admin_tablas/analytics-dashboard.php' target='_blank'>📊 Ver Dashboard Analytics</a></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    logMessage("❌ Error crítico: " . $e->getMessage(), 'error');
    exit(1);
}

if ($isWebExecution) {
    echo '</body></html>';
}
?>
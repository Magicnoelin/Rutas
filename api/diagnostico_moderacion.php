<?php
/**
 * Script de Diagnóstico - Sistema de Moderación
 * Verifica el estado del sistema y muestra información útil
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = getDBConnection();
    
    echo "<h1>🔍 Diagnóstico del Sistema de Moderación</h1>";
    echo "<style>body{font-family:Arial;padding:20px;} table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#4CAF50;color:white;} .error{color:red;} .success{color:green;} .warning{color:orange;}</style>";
    
    // 1. Verificar si existen las tablas
    echo "<h2>1. Verificación de Tablas</h2>";
    $tables = ['accommodation_pending_changes', 'accommodation_moderation_history', 'moderation_notifications'];
    $tablesExist = true;
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
            echo "<p class='success'>✅ Tabla '$table' existe</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Tabla '$table' NO existe - Debes ejecutar el SQL principal</p>";
            $tablesExist = false;
        }
    }
    
    // 2. Verificar columnas en accommodations
    echo "<h2>2. Verificación de Columnas en 'accommodations'</h2>";
    $stmt = $pdo->query("DESCRIBE accommodations");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['moderation_status', 'has_pending_changes', 'rejection_reason', 'reviewed_by', 'reviewed_at', 'published_at', 'last_submitted_at'];
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "<p class='success'>✅ Columna '$col' existe</p>";
        } else {
            echo "<p class='error'>❌ Columna '$col' NO existe - Debes ejecutar el SQL principal</p>";
            $tablesExist = false;
        }
    }
    
    if (!$tablesExist) {
        echo "<div style='background:#ffebee;padding:20px;margin:20px 0;border-left:4px solid #f44336;'>";
        echo "<h3>⚠️ ACCIÓN REQUERIDA</h3>";
        echo "<p><strong>Debes ejecutar el script SQL principal:</strong></p>";
        echo "<ol>";
        echo "<li>Ve a phpMyAdmin</li>";
        echo "<li>Selecciona la base de datos: u412199647_Rutas</li>";
        echo "<li>Pestaña 'SQL'</li>";
        echo "<li>Copia y pega el contenido de: <code>api/crear_sistema_moderacion.sql</code></li>";
        echo "<li>Ejecuta</li>";
        echo "</ol>";
        echo "</div>";
        exit;
    }
    
    // 3. Verificar alojamiento ID 184
    echo "<h2>3. Estado del Alojamiento ID 184</h2>";
    $stmt = $pdo->prepare("SELECT id, name, moderation_status, has_pending_changes, is_active, last_submitted_at, created_at FROM accommodations WHERE id = 184");
    $stmt->execute();
    $aloj = $stmt->fetch();
    
    if ($aloj) {
        echo "<table>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        foreach ($aloj as $key => $value) {
            if (!is_numeric($key)) {
                echo "<tr><td><strong>$key</strong></td><td>" . ($value ?? 'NULL') . "</td></tr>";
            }
        }
        echo "</table>";
        
        if ($aloj['moderation_status'] === 'pending' || $aloj['has_pending_changes']) {
            echo "<p class='success'>✅ Este alojamiento DEBERÍA aparecer en el panel de moderación</p>";
        } else {
            echo "<p class='warning'>⚠️ Este alojamiento NO aparecerá en moderación porque:</p>";
            echo "<ul>";
            echo "<li>moderation_status = '{$aloj['moderation_status']}' (debe ser 'pending')</li>";
            echo "<li>has_pending_changes = " . ($aloj['has_pending_changes'] ? 'TRUE' : 'FALSE') . " (debe ser TRUE)</li>";
            echo "</ul>";
            
            echo "<h3>🔧 Solución: Marcar como pendiente</h3>";
            echo "<p>Ejecuta este SQL en phpMyAdmin:</p>";
            echo "<pre style='background:#f5f5f5;padding:10px;'>UPDATE accommodations SET moderation_status = 'pending', last_submitted_at = NOW() WHERE id = 184;</pre>";
        }
    } else {
        echo "<p class='error'>❌ No existe alojamiento con ID 184</p>";
    }
    
    // 4. Estadísticas generales
    echo "<h2>4. Estadísticas del Sistema</h2>";
    try {
        $stmt = $pdo->query("SELECT * FROM v_moderation_stats");
        $stats = $stmt->fetch();
        
        if ($stats) {
            echo "<table>";
            echo "<tr><th>Métrica</th><th>Valor</th></tr>";
            echo "<tr><td>Pendientes</td><td><strong>{$stats['pending_count']}</strong></td></tr>";
            echo "<tr><td>Aprobados</td><td><strong>{$stats['approved_count']}</strong></td></tr>";
            echo "<tr><td>Rechazados</td><td><strong>{$stats['rejected_count']}</strong></td></tr>";
            echo "<tr><td>Borradores</td><td><strong>{$stats['draft_count']}</strong></td></tr>";
            echo "<tr><td>Con cambios pendientes</td><td><strong>{$stats['pending_changes_count']}</strong></td></tr>";
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Vista v_moderation_stats no existe</p>";
    }
    
    // 5. Lista de alojamientos pendientes
    echo "<h2>5. Alojamientos Pendientes de Moderación</h2>";
    $stmt = $pdo->query("
        SELECT id, name, moderation_status, has_pending_changes, last_submitted_at 
        FROM accommodations 
        WHERE moderation_status = 'pending' OR has_pending_changes = 1
        ORDER BY last_submitted_at DESC
        LIMIT 10
    ");
    $pendientes = $stmt->fetchAll();
    
    if (count($pendientes) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Estado</th><th>Cambios Pendientes</th><th>Enviado</th></tr>";
        foreach ($pendientes as $p) {
            echo "<tr>";
            echo "<td>{$p['id']}</td>";
            echo "<td>{$p['name']}</td>";
            echo "<td>{$p['moderation_status']}</td>";
            echo "<td>" . ($p['has_pending_changes'] ? 'Sí' : 'No') . "</td>";
            echo "<td>" . ($p['last_submitted_at'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No hay alojamientos pendientes de moderación</p>";
        echo "<p>Para crear uno de prueba, ejecuta:</p>";
        echo "<pre style='background:#f5f5f5;padding:10px;'>UPDATE accommodations SET moderation_status = 'pending', last_submitted_at = NOW() WHERE id = 184;</pre>";
    }
    
    echo "<hr>";
    echo "<h2>✅ Diagnóstico Completado</h2>";
    echo "<p><a href='../admin_tablas/moderacion_alojamientos.php' style='background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Ir al Panel de Moderación</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error de conexión: " . $e->getMessage() . "</p>";
}

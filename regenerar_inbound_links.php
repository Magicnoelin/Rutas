<?php
/**
 * REGENERAR INBOUND LINKS - Script Simple
 * =====================================
 * Este script regenera todos los enlaces automáticos (description_linked)
 * para que aparezcan los inbound links como "Castillo de Zamora"
 */

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/inbound_links_helper.php';

echo "<h1>🔄 Regenerando Inbound Links</h1>\n";
echo "<p>Este proceso puede tardar unos minutos...</p>\n";

try {
    $pdo = getDBConnection();
    
    echo "<h2>✅ Conectado a la base de datos</h2>\n";
    
    // Regenerar para cada tabla
    $tablas = ['places_of_interest', 'cultural_events', 'accommodations'];
    $totalProcesados = 0;
    
    foreach ($tablas as $tabla) {
        echo "<h3>📝 Procesando tabla: $tabla</h3>\n";
        
        $resultado = regenerarInboundLinksTodos($pdo, $tabla);
        
        echo "<p>✅ <strong>$tabla:</strong> {$resultado['procesados']} registros procesados";
        if ($resultado['errores'] > 0) {
            echo ", {$resultado['errores']} errores";
        }
        echo "</p>\n";
        
        $totalProcesados += $resultado['procesados'];
    }
    
    echo "<hr>\n";
    echo "<h2>🎉 ¡COMPLETADO!</h2>\n";
    echo "<p><strong>Total procesados: $totalProcesados registros</strong></p>\n";
    echo "<p>Ahora todos los textos que mencionen <strong>'Castillo de Zamora'</strong> tendrán enlaces automáticos.</p>\n";
    
} catch (Exception $e) {
    echo "<div style='background:#ffebee;color:#c62828;padding:15px;border-radius:5px;'>\n";
    echo "<h2>❌ Error</h2>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
    echo "</div>\n";
}

echo "<hr><p><em>Proceso terminado - " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
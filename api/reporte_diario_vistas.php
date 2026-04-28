<?php
/**
 * REPORTE DIARIO DE VISTAS
 * Ejecutar via cron: 0 8 * * * php /path/to/reporte_diario_vistas.php
 * 
 * Configurar email de destino abajo
 */

// === CONFIGURACIÓN ===
$email_destino = 'tu-email@ejemplo.com';  // <-- CAMBIAR
$asunto = '📊 Reporte Diario de Vistas - RutasRurales';
// ====================

require_once 'db.php'; // Conexión a la base de datos

// Obtener fecha de ayer
$ayer = date('Y-m-d', strtotime('yesterday'));
$fecha_formato = date('d/m/Y', strtotime('yesterday'));

// Inicializar contenido del email
$html = "<html><body>";
$html .= "<h2>📊 Reporte de Vistas - {$fecha_formato}</h2>";

// === EVENTOS ===
$stmt = $pdo->prepare("
    SELECT 
        ce.id, ce.name, ce.slug,
        COUNT(pvl.id) as vistas
    FROM cultural_events ce
    LEFT JOIN page_views_log pvl ON pvl.resource_type = 'event' 
        AND pvl.resource_id = ce.id 
        AND DATE(pvl.viewed_at) = ?
    WHERE ce.is_active = 1
    GROUP BY ce.id, ce.name, ce.slug
    HAVING vistas > 0
    ORDER BY vistas DESC
    LIMIT 20
");
$stmt->execute([$ayer]);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_eventos = array_sum(array_column($eventos, 'vistas'));

$html .= "<h3>🎭 Eventos ({$total_eventos} vistas)</h3>";
if ($eventos) {
    $html .= "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse;'>";
    $html .= "<tr style='background:#f0f0f0;'><th>Evento</th><th>Vistas</th></tr>";
    foreach ($eventos as $e) {
        $html .= "<tr>";
        $html .= "<td><a href='https://rutasrurales.io/evento/{$e['slug']}'>{$e['name']}</a></td>";
        $html .= "<td style='text-align:center;'><strong>{$e['vistas']}</strong></td>";
        $html .= "</tr>";
    }
    $html .= "</table>";
} else {
    $html .= "<p>Sin visitas ayer</p>";
}

// === ALOJAMIENTOS ===
$stmt = $pdo->prepare("
    SELECT 
        a.id, a.name, a.slug,
        COUNT(pvl.id) as vistas
    FROM accommodations a
    LEFT JOIN page_views_log pvl ON pvl.resource_type = 'accommodation' 
        AND pvl.resource_id = a.id 
        AND DATE(pvl.viewed_at) = ?
    WHERE a.is_active = 1
    GROUP BY a.id, a.name, a.slug
    HAVING vistas > 0
    ORDER BY vistas DESC
    LIMIT 20
");
$stmt->execute([$ayer]);
$alojamientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_alojamientos = array_sum(array_column($alojamientos, 'vistas'));

$html .= "<h3>🏡 Alojamientos ({$total_alojamientos} vistas)</h3>";
if ($alojamientos) {
    $html .= "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse;'>";
    $html .= "<tr style='background:#f0f0f0;'><th>Alojamiento</th><th>Vistas</th></tr>";
    foreach ($alojamientos as $a) {
        $html .= "<tr>";
        $html .= "<td><a href='https://rutasrurales.io/alojamiento/{$a['slug']}'>{$a['name']}</a></td>";
        $html .= "<td style='text-align:center;'><strong>{$a['vistas']}</strong></td>";
        $html .= "</tr>";
    }
    $html .= "</table>";
} else {
    $html .= "<p>Sin visitas ayer</p>";
}

// === RESUMEN GENERAL ===
$stmt = $pdo->prepare("
    SELECT resource_type, COUNT(*) as total
    FROM page_views_log
    WHERE DATE(viewed_at) = ?
    GROUP BY resource_type
");
$stmt->execute([$ayer]);
$resumen = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_general = array_sum(array_column($resumen, 'total'));

$html .= "<h3>📈 Resumen General: {$total_general} vistas totales</h3>";
$html .= "<ul>";
foreach ($resumen as $r) {
    $html .= "<li><strong>{$r['resource_type']}</strong>: {$r['total']} vistas</li>";
}
$html .= "</ul>";

$html .= "<hr><p style='color:#666;font-size:12px;'>Generado automáticamente el " . date('d/m/Y H:i:s') . "</p>";
$html .= "</body></html>";

// === ENVIAR EMAIL ===
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

if (mail($email_destino, $asunto, $html, $headers)) {
    echo "✅ Reporte enviado a {$email_destino}\n";
} else {
    echo "❌ Error al enviar email\n";
}
?>

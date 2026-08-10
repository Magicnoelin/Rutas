<?php
/**
 * EJEMPLO DE USO - Sistema Unificado de Analytics
 * 
 * Este archivo muestra cómo implementar el tracking en páginas de eventos
 */

// Simular datos de un evento
$evento = [
    'id' => 123,
    'name' => 'Festival de Música Rural 2026',
    'slug' => 'festival-musica-rural-2026',
    'municipality' => 'Soria',
    'views_count' => 0 // Se actualizará automáticamente
];

// Incluir header con analytics integrado
include 'header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $evento['name']; ?> - Rutas Rurales</title>
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .stats-bar { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .stat-item { display: inline-block; margin: 0 15px; }
        .stat-number { font-weight: bold; color: #2F5233; font-size: 1.2em; }
        .example-code { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .success { color: #28a745; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
    </style>
</head>
<body>

<!-- IMPORTANTE: Este div indica al sistema qué recurso trackear -->
<div data-resource-type="event" data-resource-id="<?php echo $evento['id']; ?>" style="display: none;"></div>

<div class="container">
    <h1><?php echo $evento['name']; ?></h1>
    
    <p>📍 <strong>Ubicación:</strong> <?php echo $evento['municipality']; ?></p>
    
    <!-- Barra de estadísticas que se actualiza automáticamente -->
    <div class="stats-bar">
        <div class="stat-item">
            👁️ <span class="stat-number" id="view-count">—</span> visitas
        </div>
        <div class="stat-item">
            ❤️ <span class="stat-number" id="likes-count">—</span> likes
        </div>
        <div class="stat-item">
            💬 <span class="stat-number" id="messages-count">—</span> consultas
        </div>
    </div>

    <h2>🔧 Demostración del Sistema Analytics</h2>
    
    <div class="example-code">
        <h3 class="success">✅ Tracking Automático Activado</h3>
        <p>El sistema detectó automáticamente:</p>
        <ul>
            <li><strong>Tipo:</strong> event</li>
            <li><strong>ID:</strong> <?php echo $evento['id']; ?></li>
            <li><strong>URL:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></li>
        </ul>
        <p>La visita fue registrada automáticamente al cargar la página.</p>
    </div>
    
    <h3>🧪 Funciones Disponibles</h3>
    
    <button onclick="trackManualView()">📊 Trackear Vista Manual</button>
    <button onclick="getStats()">📈 Obtener Estadísticas</button>
    <button onclick="trackInterest()">❤️ Marcar como Interesante</button>
    
    <div id="results" style="margin-top: 20px;"></div>
    
    <h3>📋 Ejemplos de Código</h3>
    
    <div class="example-code">
        <h4>1. Tracking Automático (método recomendado)</h4>
        <pre>&lt;!-- Solo agregar este div oculto en la página --&gt;
&lt;div data-resource-type="event" data-resource-id="123" style="display: none;"&gt;&lt;/div&gt;</pre>
    </div>
    
    <div class="example-code">
        <h4>2. Tracking Manual en JavaScript</h4>
        <pre>// Trackear vista
window.rutasAnalytics.trackView('event', 123);

// Obtener estadísticas
const stats = await window.rutasAnalytics.getStats('event', 123);
console.log('Visitas:', stats.views_count);</pre>
    </div>
    
    <div class="example-code">
        <h4>3. Tracking via API (PHP/Backend)</h4>
        <pre>// POST a /api/unified-analytics.php
{
    "action": "track_view",
    "resource_type": "event", 
    "resource_id": 123
}</pre>
    </div>
    
    <h3>🎯 Sincronización con Google Search Console</h3>
    
    <div class="example-code">
        <div class="success">✅ Google Analytics Detectado</div>
        <p>El sistema está enviando eventos a Google Analytics con las dimensiones personalizadas:</p>
        <ul>
            <li><strong>dimension1:</strong> event</li>
            <li><strong>dimension2:</strong> 123</li>
        </ul>
        <p>Estos datos aparecerán en Google Search Console en 24-48 horas.</p>
    </div>
    
    <h3>📊 Comparativa de Sistemas</h3>
    
    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr style="background: #f8f9fa;">
            <th style="padding: 10px; border: 1px solid #ddd;">Sistema</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Estado Anterior</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Estado Actual</th>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #ddd;">Contador Simple (archivo txt)</td>
            <td style="padding: 10px; border: 1px solid #ddd;" class="warning">❌ Desactualizado</td>
            <td style="padding: 10px; border: 1px solid #ddd;" class="success">✅ Integrado</td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #ddd;">resource_stats (base de datos)</td>
            <td style="padding: 10px; border: 1px solid #ddd;" class="warning">❌ Inconsistente</td>
            <td style="padding: 10px; border: 1px solid #ddd;" class="success">✅ Centralizado</td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #ddd;">Google Analytics</td>
            <td style="padding: 10px; border: 1px solid #ddd;" class="warning">❌ Desconectado</td>
            <td style="padding: 10px; border: 1px solid #ddd;" class="success">✅ Sincronizado</td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #ddd;">views_count en tablas</td>
            <td style="padding: 10px; border: 1px solid #ddd;" class="warning">❌ Duplicados</td>
            <td style="padding: 10px; border: 1px solid #ddd;" class="success">✅ Unificado</td>
        </tr>
    </table>
    
</div>

<script>
// Funciones de demostración
async function trackManualView() {
    const result = await window.rutasAnalytics.trackView('event', <?php echo $evento['id']; ?>);
    document.getElementById('results').innerHTML = '<div class="example-code"><strong>Resultado:</strong><pre>' + JSON.stringify(result, null, 2) + '</pre></div>';
}

async function getStats() {
    const stats = await window.rutasAnalytics.getStats('event', <?php echo $evento['id']; ?>);
    document.getElementById('results').innerHTML = '<div class="example-code"><strong>Estadísticas:</strong><pre>' + JSON.stringify(stats, null, 2) + '</pre></div>';
    
    // Actualizar contadores visuales
    if (stats) {
        document.getElementById('view-count').textContent = window.rutasAnalytics.formatNumber(stats.views_count || 0);
        document.getElementById('likes-count').textContent = window.rutasAnalytics.formatNumber(stats.favorites_count || 0);
        document.getElementById('messages-count').textContent = window.rutasAnalytics.formatNumber(stats.messages_count || 0);
    }
}

async function trackInterest() {
    // Simular tracking de interés (like)
    try {
        const response = await fetch('/api/track_resource_stat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                resource_type: 'event',
                resource_id: <?php echo $evento['id']; ?>,
                stat_type: 'favorite'
            })
        });
        const result = await response.json();
        document.getElementById('results').innerHTML = '<div class="example-code"><strong>Interés registrado:</strong><pre>' + JSON.stringify(result, null, 2) + '</pre></div>';
    } catch (error) {
        document.getElementById('results').innerHTML = '<div class="example-code warning"><strong>Error:</strong> ' + error.message + '</div>';
    }
}

// Auto-cargar estadísticas al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(getStats, 2000); // Esperar 2 segundos para que se procese la vista
});

// Escuchar eventos del sistema de analytics
document.addEventListener('rutas-analytics', function(event) {
    console.log('Evento de analytics:', event.detail);
    
    // Mostrar notificación
    const notification = document.createElement('div');
    notification.style.cssText = 'position: fixed; top: 10px; right: 10px; background: #28a745; color: white; padding: 10px; border-radius: 4px; z-index: 10000;';
    notification.textContent = `✅ ${event.detail.eventType} registrado para ${event.detail.resourceType} #${event.detail.resourceId}`;
    document.body.appendChild(notification);
    
    setTimeout(() => notification.remove(), 3000);
});
</script>

</body>
</html>
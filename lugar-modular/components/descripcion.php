<?php
/**
 * descripcion.php — Descripción, info práctica, contacto y mapa
 * Variables requeridas: $lugar, $t
 */
if (empty($lugar)) return;

// Acceso seguro a claves de $t con fallback
$_t = [
    'descripcion'   => isset($t['descripcion'])   ? $t['descripcion']   : '📋 Descripción',
    'info_practica' => isset($t['info_practica']) ? $t['info_practica'] : 'ℹ️ Información práctica',
    'contacto'      => isset($t['contacto'])      ? $t['contacto']      : '📞 Contacto y acceso',
    'horario'       => isset($t['horario'])       ? $t['horario']       : 'Horario',
    'entrada'       => isset($t['entrada'])       ? $t['entrada']       : 'Entrada',
    'accesibilidad' => isset($t['accesibilidad']) ? $t['accesibilidad'] : 'Accesibilidad',
    'instalaciones' => isset($t['instalaciones']) ? $t['instalaciones'] : 'Instalaciones',
    'llamar'        => isset($t['llamar'])        ? $t['llamar']        : '📞 Llamar',
    'whatsapp'      => isset($t['whatsapp'])      ? $t['whatsapp']      : '💬 WhatsApp',
    'email'         => isset($t['email'])         ? $t['email']         : '✉️ Email',
    'web_oficial'   => isset($t['web_oficial'])   ? $t['web_oficial']   : '🌐 Web oficial',
    'ver_mapa'      => isset($t['ver_mapa'])      ? $t['ver_mapa']      : 'Ver en el mapa',
    'click_mapa'    => isset($t['click_mapa'])    ? $t['click_mapa']    : 'Haz clic para cargar el mapa interactivo',
    'leer_mas'      => isset($t['leer_mas'])      ? $t['leer_mas']      : '↓ Leer más',
    'leer_menos'    => isset($t['leer_menos'])    ? $t['leer_menos']    : '↑ Leer menos',
];

// Usar description_linked (con inbound links pre-generados) si existe
// Si está vacío, generar los links al vuelo con fallback a description
$descripcionRaw = '';
$descHtml = '';

if (!empty($lugar['description_linked'])) {
    // Ya tiene inbound links procesados (modo óptimo)
    $descripcionRaw = $lugar['description_linked'];
} elseif (!empty($lugar['description'])) {
    // Fallback: procesar inbound links al vuelo
    $descripcionRaw = $lugar['description'];
    
    // Aplicar inbound links dinámicamente
    if (isset($pdo) && $pdo !== null) {
        require_once dirname(__DIR__) . '/api/inbound_links_helper.php';
        $descripcionRaw = procesarInboundLinks($descripcionRaw, $pdo);
    }
}

// Sanitizar descripción: permitir solo HTML seguro (sin scripts)
if ($descripcionRaw) {
    // Permite etiquetas de formato y enlaces pero elimina scripts
    $descHtml = strip_tags($descripcionRaw, '<p><br><a><strong><em><ul><ol><li><h2><h3><h4><blockquote><span>');
    
    // EVITAR AUTO-ENLACE: Remover enlaces que apunten al propio lugar (Google lo penaliza)
    if (!empty($lugar['slug'])) {
        $selfUrl = '/lugar/' . $lugar['slug'];
        $descHtml = preg_replace('/<a[^>]+href="' . preg_quote($selfUrl, '/') . '"[^>]*>(.*?)<\/a>/i', '$1', $descHtml);
    }
}

$hayInfoPractica = !empty($lugar['opening_hours'])
    || !empty($lugar['visit_duration'])
    || !empty($lugar['accessibility'])
    || !empty($lugar['facilities'])
    || !empty($lugar['best_season']);

$hayContacto = !empty($lugar['phone'])
    || !empty($lugar['email'])
    || !empty($lugar['website'])
    || (!empty($lugar['latitude']) && !empty($lugar['longitude']));
?>

<!-- ▸ DESCRIPCIÓN -->
<?php if (!empty($descHtml)): ?>
<div class="lug-card">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($_t['descripcion'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="desc-text" id="desc-text">
            <?php echo $descHtml; ?>
        </div>
        <button class="desc-toggle" id="desc-toggle"
                onclick="toggleDesc()"
                aria-expanded="false">
            <?php echo htmlspecialchars($_t['leer_mas'], ENT_QUOTES, 'UTF-8'); ?>
        </button>
    </div>
</div>
<?php endif; ?>

<!-- ▸ INFORMACIÓN PRÁCTICA -->
<?php if ($hayInfoPractica): ?>
<div class="lug-card">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($_t['info_practica'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <dl class="info-dl">

            <?php if (!empty($lugar['opening_hours'])): ?>
            <div class="info-dl-row">
                <dt><?php echo htmlspecialchars($_t['horario'], ENT_QUOTES, 'UTF-8'); ?></dt>
                <dd><?php echo htmlspecialchars($lugar['opening_hours'], ENT_QUOTES, 'UTF-8'); ?></dd>
            </div>
            <?php endif; ?>

            <?php if (!empty($lugar['entry_fee']) || !empty($lugar['entry_fee_details'])): ?>
            <div class="info-dl-row">
                <dt><?php echo htmlspecialchars($_t['entrada'], ENT_QUOTES, 'UTF-8'); ?></dt>
                <dd>
                    <?php if (!empty($lugar['entry_fee'])): ?>
                        <?php echo htmlspecialchars($lugar['entry_fee'], ENT_QUOTES, 'UTF-8'); ?>€
                    <?php endif; ?>
                    <?php if (!empty($lugar['entry_fee_details'])): ?>
                        <?php echo htmlspecialchars($lugar['entry_fee_details'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </dd>
            </div>
            <?php endif; ?>

            <?php if (!empty($lugar['visit_duration'])): ?>
            <div class="info-dl-row">
                <dt>⏱️ Duración</dt>
                <dd><?php echo htmlspecialchars($lugar['visit_duration'], ENT_QUOTES, 'UTF-8'); ?></dd>
            </div>
            <?php endif; ?>

            <?php if (!empty($lugar['best_season'])): ?>
            <div class="info-dl-row">
                <dt>🌸 Mejor época</dt>
                <dd><?php echo htmlspecialchars($lugar['best_season'], ENT_QUOTES, 'UTF-8'); ?></dd>
            </div>
            <?php endif; ?>

            <?php if (!empty($lugar['accessibility'])): ?>
            <div class="info-dl-row">
                <dt><?php echo htmlspecialchars($_t['accesibilidad'], ENT_QUOTES, 'UTF-8'); ?></dt>
                <dd><?php echo htmlspecialchars($lugar['accessibility'], ENT_QUOTES, 'UTF-8'); ?></dd>
            </div>
            <?php endif; ?>

            <?php if (!empty($lugar['facilities'])): ?>
            <div class="info-dl-row">
                <dt><?php echo htmlspecialchars($_t['instalaciones'], ENT_QUOTES, 'UTF-8'); ?></dt>
                <dd><?php echo htmlspecialchars($lugar['facilities'], ENT_QUOTES, 'UTF-8'); ?></dd>
            </div>
            <?php endif; ?>

        </dl>
    </div>
</div>
<?php endif; ?>

<!-- ▸ CONTACTO Y MAPA -->
<?php
// Solo mostramos este bloque si hay teléfono, email, web O coordenadas de mapa
$hayContactoBloq = !empty($lugar['phone']) || !empty($lugar['email']) || !empty($lugar['website'])
    || (!empty($lugar['latitude']) && !empty($lugar['longitude']));
?>
<?php if ($hayContactoBloq): ?>
<div class="lug-card">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($_t['contacto'], ENT_QUOTES, 'UTF-8'); ?></h2>

        <!-- Botones de contacto en fila (flex-wrap) — segunda aparición: evita duplicar con sidebar -->
        <?php if (!empty($lugar['phone']) || !empty($lugar['email']) || !empty($lugar['website'])): ?>
        <div class="contact-row">
            <?php if (!empty($lugar['phone'])): ?>
            <a href="tel:<?php echo htmlspecialchars($lugar['phone'], ENT_QUOTES, 'UTF-8'); ?>"
               class="contact-btn contact-phone">
                📞 <?php echo htmlspecialchars($lugar['phone'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <a href="https://api.whatsapp.com/send?phone=<?php echo urlencode(preg_replace('/\D/', '', $lugar['phone'])); ?>"
               target="_blank" rel="noopener noreferrer"
               class="contact-btn contact-whatsapp">
                <?php echo htmlspecialchars($_t['whatsapp'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php endif; ?>
            <?php if (!empty($lugar['email'])): ?>
            <a href="mailto:<?php echo htmlspecialchars($lugar['email'], ENT_QUOTES, 'UTF-8'); ?>"
               class="contact-btn contact-email">
                <?php echo htmlspecialchars($_t['email'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php endif; ?>
            <?php if (!empty($lugar['website'])): ?>
            <a href="<?php echo htmlspecialchars($lugar['website'], ENT_QUOTES, 'UTF-8'); ?>"
               target="_blank" rel="noopener noreferrer"
               class="contact-btn contact-web">
                <?php echo htmlspecialchars($_t['web_oficial'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($lugar['latitude']) && !empty($lugar['longitude'])): ?>
        <!-- Mapa Leaflet simplificado -->
        <div id="map-container" class="lug-card" style="margin-bottom:24px;">
            <div class="lug-card-body">
                <h2 class="lug-card-title">🗺️ <?php echo htmlspecialchars($_t['ubicacion'] ?? 'Ubicación', ENT_QUOTES, 'UTF-8'); ?></h2>
                <div id="map-placeholder" class="map-placeholder" onclick="initMap()">
                    <div class="map-icon">🗺️</div>
                    <strong style="font-size:1rem;"><?php echo htmlspecialchars($_t['ver_mapa'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <p><?php echo htmlspecialchars($_t['click_mapa'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div id="map" style="height:280px; display:none; border-radius:var(--lug-r);"></div>
                <div class="map-links" style="margin-top:15px; display:flex; flex-wrap:wrap; gap:10px; justify-content:center;">
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lugar['latitude'] . ',' . $lugar['longitude']); ?>" target="_blank" rel="noopener noreferrer" class="map-link-btn">Ver en Google Maps</a>
                    <a href="/alojamientos/cerca-de-<?php echo htmlspecialchars($lugar['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="map-link-btn">🏠 Alojamientos cerca</a>
                    <a href="/actividades/cerca-de-<?php echo htmlspecialchars($lugar['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="map-link-btn">🎯 Actividades cerca</a>
                    <a href="/lugares/cerca-de-<?php echo htmlspecialchars($lugar['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="map-link-btn">🏛️ Otros lugares cerca</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
<?php endif; ?>

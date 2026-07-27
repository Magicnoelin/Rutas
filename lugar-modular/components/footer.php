<?php
/**
 * footer.php — Footer, lightbox, toasts y scripts
 * Variables requeridas: $lugar, $t
 */
?>

<!-- ══════════════════════════════════════════════════════
     LIGHTBOX — Overlay de galería de fotos
     ══════════════════════════════════════════════════════ -->
<div class="lbox-overlay" id="lightbox" onclick="closeLightboxOnOverlay(event)" role="dialog" aria-modal="true" aria-label="Galería de fotos">
    <button class="lbox-close" onclick="closeLightbox()" type="button" aria-label="Cerrar galería">✕</button>
    <button class="lbox-nav lbox-prev" onclick="lightboxNav(-1)" type="button" aria-label="Foto anterior">‹</button>
    <img class="lbox-img" id="lightbox-img" src="" alt="">
    <button class="lbox-nav lbox-next" onclick="lightboxNav(1)" type="button" aria-label="Foto siguiente">›</button>
    <div class="lbox-caption" id="lightbox-caption"></div>
</div>

<!-- ══════════════════════════════════════════════════════
     TOAST — Notificaciones emergentes
     ══════════════════════════════════════════════════════ -->
<div class="toast" id="toast" role="status" aria-live="polite"></div>

<!-- ══════════════════════════════════════════════════════
     LEAFLET CSS diferido — se inyecta sólo si hay mapa
     Technique: media="print" onload="this.media='all'"
     (no bloquea renderizado inicial)
     ══════════════════════════════════════════════════════ -->
<?php if (!empty($lugar['latitude']) && !empty($lugar['longitude'])): ?>
<link rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      media="print"
      onload="this.media='all'"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin="anonymous">
<noscript>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</noscript>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════
     JS PRINCIPAL — lugar.js (defer para no bloquear render)
     ══════════════════════════════════════════════════════ -->
<script src="/lugar-modular/js/lugar.js" defer></script>

<!-- GTM noscript (debe ir justo al abrir body; se incluye aquí como fallback) -->
<noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-XXXXXXX"
            height="1" width="1" style="display:none;visibility:hidden"
            title="Google Tag Manager"></iframe>
</noscript>

</body>
</html>

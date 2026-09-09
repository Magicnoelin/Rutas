<?php
/**
 * galeria.php — Galería de fotos con miniaturas, lightbox y créditos
 * Variables requeridas: $lugar, $fotos, $t, $fotosCredits (opcional)
 */
if (empty($lugar)) return;
if (!isset($fotos) || !is_array($fotos) || empty($fotos)) return;

// fotosCredits es un array asociativo [url_foto => credito]
if (!isset($fotosCredits) || !is_array($fotosCredits)) {
    $fotosCredits = [];
}

if (!function_exists('fixUrl')) {
    function fixUrl(string $url): string {
        if (!$url) return '';
        return preg_match('/^https?:\/\//', $url) ? $url : '/' . ltrim($url, '/');
    }
}

$_t_fotos    = isset($t['fotos'])    ? $t['fotos']    : '📸 Galería de fotos';
$_t_vertodas = isset($t['ver_todas']) ? $t['ver_todas'] : '🔍 Ver todas';
$_nombre     = isset($lugar['name']) ? $lugar['name'] : '';

// Función para obtener crédito de una foto
function getFotoCredito(string $url, array $credits): string {
    // Buscar crédito exacto o por URL normalizada
    $urlNormalizada = fixUrl($url);
    if (isset($credits[$urlNormalizada]) && !empty($credits[$urlNormalizada])) {
        return $credits[$urlNormalizada];
    }
    // Buscar por URL original
    foreach ($credits as $key => $credit) {
        if (strpos($urlNormalizada, $key) !== false || strpos($key, $urlNormalizada) !== false) {
            return $credit;
        }
    }
    return '';
}
?>

<!-- ▸ GALERÍA -->
<div class="lug-card" style="margin-top:20px;">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($_t_fotos, ENT_QUOTES, 'UTF-8'); ?></h2>

        <!-- Imagen principal con onclick para lightbox -->
        <div class="gallery-main" id="gallery-main" onclick="openLightbox(currentGalleryIdx)">
            <img id="gallery-main-img"
                 src="<?php echo htmlspecialchars(fixUrl($fotos[0]), ENT_QUOTES, 'UTF-8'); ?>"
                 alt="<?php echo htmlspecialchars($_nombre, ENT_QUOTES, 'UTF-8'); ?>"
                 class="gallery-main-img"
                 loading="eager"
                 width="800" height="380">

            <?php if (count($fotos) > 1): ?>
            <span class="gallery-counter" id="gallery-counter">1 / <?php echo count($fotos); ?></span>
            <button class="gallery-expand-btn"
                    onclick="event.stopPropagation();openLightbox(currentGalleryIdx)"
                    type="button"
                    aria-label="<?php echo htmlspecialchars($_t_vertodas, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($_t_vertodas, ENT_QUOTES, 'UTF-8'); ?>
            </button>
            <?php endif; ?>
            
            <!-- Crédito de la foto (debajo de la imagen principal) -->
            <?php 
            $creditoPrincipal = getFotoCredito($fotos[0], $fotosCredits);
            if (!empty($creditoPrincipal)): ?>
            <div class="photo-credit" style="position:absolute;bottom:10px;left:10px;background:rgba(0,0,0,0.7);color:#fff;padding:4px 8px;border-radius:4px;font-size:0.75rem;">
                📷 <?php echo htmlspecialchars($creditoPrincipal, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Miniaturas (thumbnails) -->
        <?php if (count($fotos) > 1): ?>
        <div class="gallery-thumbs" id="gallery-thumbs">
            <?php foreach ($fotos as $i => $foto): ?>
            <div class="gallery-thumb <?php echo $i === 0 ? 'active' : ''; ?>"
                 data-index="<?php echo $i; ?>"
                 onclick="setGalleryPhoto(<?php echo $i; ?>)"
                 role="button"
                 tabindex="0"
                 aria-label="<?php echo htmlspecialchars($_nombre, ENT_QUOTES, 'UTF-8'); ?> — foto <?php echo $i + 1; ?>">
                <img src="<?php echo htmlspecialchars(fixUrl($foto), ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?php echo htmlspecialchars($_nombre, ENT_QUOTES, 'UTF-8'); ?> — foto <?php echo $i + 1; ?>"
                     loading="<?php echo $i < 3 ? 'eager' : 'lazy'; ?>">
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /.lug-card-body -->
</div><!-- /.lug-card (galería) -->

<?php
/**
 * galeria.php — Galería de fotos con miniaturas y lightbox
 * Variables requeridas: $lugar, $fotos, $t
 */
if (empty($lugar) || empty($fotos)) return;

function fixUrl(string $url): string {
    if (!$url) return '';
    return preg_match('/^https?:\/\//', $url) ? $url : '/' . ltrim($url, '/');
}
?>

<!-- ▸ GALERÍA -->
<div class="lug-card">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($t['fotos'], ENT_QUOTES, 'UTF-8'); ?></h2>

        <!-- Imagen principal con onclick para lightbox -->
        <div class="gallery-main" id="gallery-main" onclick="openLightbox(currentGalleryIdx)">
            <img id="gallery-main-img"
                 src="<?php echo htmlspecialchars(fixUrl($fotos[0]), ENT_QUOTES, 'UTF-8'); ?>"
                 alt="<?php echo htmlspecialchars($lugar['name'], ENT_QUOTES, 'UTF-8'); ?>"
                 class="gallery-main-img"
                 loading="eager"
                 width="800" height="380">

            <?php if (count($fotos) > 1): ?>
            <span class="gallery-counter" id="gallery-counter">1 / <?php echo count($fotos); ?></span>
            <button class="gallery-expand-btn"
                    onclick="event.stopPropagation();openLightbox(currentGalleryIdx)"
                    type="button"
                    aria-label="<?php echo htmlspecialchars($t['ver_todas'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($t['ver_todas'], ENT_QUOTES, 'UTF-8'); ?>
            </button>
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
                 aria-label="<?php echo htmlspecialchars($lugar['name'], ENT_QUOTES, 'UTF-8'); ?> — foto <?php echo $i + 1; ?>">
                <img src="<?php echo htmlspecialchars(fixUrl($foto), ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?php echo htmlspecialchars($lugar['name'], ENT_QUOTES, 'UTF-8'); ?> — foto <?php echo $i + 1; ?>"
                     loading="<?php echo $i < 3 ? 'eager' : 'lazy'; ?>">
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /.lug-card-body -->
</div><!-- /.lug-card (galería) -->

<?php
/**
 * MODULE: galeria.php — REFACTORIZADO (SEO interlinking)
 *
 * CAMBIOS vs versión legacy:
 * - Los <img onclick="..."> de las miniaturas han sido reemplazados por <a href>
 *   que envuelven cada thumbnail → semánticamente correctos y accesibles.
 * - El onclick inline que modificaba el src de la imagen principal se elimina
 *   del HTML y se delega al JS mediante data-index + event listener en index.php.
 * - Se mantiene el atributo data-src en las miniaturas para que el JS sepa
 *   cuál foto cargar en la imagen principal al hacer clic.
 * - La imagen principal sigue siendo un <img> normal (no es un enlace de
 *   navegación, sino un control de UI → correcto mantenerlo en JS).
 */
if (isset($alojamiento) && $alojamiento && isset($t) && isset($fotos)):

    // Helper para generar alt text descriptivo a partir de la URL
    function getPhotoAltDev($url, $name, $index) {
        $labels = [
            'salon'      => 'Salón',
            'cocina'     => 'Cocina',
            'jardin'     => 'Jardín',
            'habitacion' => 'Habitación',
            'bano'       => 'Baño',
            'exterior'   => 'Exterior',
            'piscina'    => 'Piscina',
            'comedor'    => 'Comedor',
            'terraza'    => 'Terraza',
            'fachada'    => 'Fachada',
        ];
        $basename = basename($url);
        if (preg_match('/^([a-z]+)-\d+\.webp$/i', $basename, $m)) {
            $cat = strtolower($m[1]);
            if (isset($labels[$cat])) {
                return htmlspecialchars($labels[$cat] . ' — ' . $name, ENT_QUOTES, 'UTF-8');
            }
        }
        return htmlspecialchars('Foto ' . ($index + 1) . ' de ' . $name, ENT_QUOTES, 'UTF-8');
    }
?>
<div class="alojamiento-gallery">
    <h2 class="section-title">📸 <?php echo isset($t['fotos']) ? $t['fotos'] : 'Galería de fotos'; ?></h2>

    <?php if (!empty($fotos)): ?>

    <!-- Imagen principal — al hacer clic abre el lightbox (JS) -->
    <div class="gallery-main" id="galleryMain" role="button" tabindex="0"
         aria-label="<?php echo isset($t['ver_todas']) ? $t['ver_todas'] : 'Ver todas las fotos'; ?>">
        <img id="galleryMainImg"
             src="<?php echo htmlspecialchars($fotos[0]); ?>"
             alt="<?php echo getPhotoAltDev($fotos[0], $alojamiento['name'] ?? '', 0); ?>"
             class="main-image"
             loading="eager"
             width="800" height="480">
        <?php if (count($fotos) > 1): ?>
        <span class="gallery-counter" id="galleryCounter">1 / <?php echo count($fotos); ?></span>
        <!-- Botón "ver todas" — abre lightbox vía JS, no es navegación externa -->
        <button class="gallery-expand-btn" id="galleryExpandBtn" type="button">
            🔍 <?php echo isset($t['ver_todas']) ? $t['ver_todas'] : 'Ver todas las fotos'; ?>
        </button>
        <?php endif; ?>
    </div>

    <!-- Miniaturas: cada una es un <a> con href a la URL de la foto -->
    <!-- El href permite que Google indexe las imágenes como recursos enlazados -->
    <!-- El JS intercepta el click y actualiza la imagen principal sin navegar -->
    <?php if (count($fotos) > 1): ?>
    <div class="gallery-thumbnails">
        <?php foreach ($fotos as $i => $foto): ?>
        <a href="<?php echo htmlspecialchars($foto); ?>"
           class="gallery-thumb <?php echo $i === 0 ? 'active' : ''; ?>"
           data-index="<?php echo $i; ?>"
           data-src="<?php echo htmlspecialchars($foto); ?>"
           aria-label="<?php echo getPhotoAltDev($foto, $alojamiento['name'] ?? '', $i); ?>"
           onclick="event.preventDefault(); setGalleryPhoto(<?php echo $i; ?>);">
            <img src="<?php echo htmlspecialchars($foto); ?>"
                 alt="<?php echo getPhotoAltDev($foto, $alojamiento['name'] ?? '', $i); ?>"
                 loading="<?php echo $i < 3 ? 'eager' : 'lazy'; ?>"
                 width="80" height="68">
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="no-photos" style="text-align:center;padding:40px 20px;color:#999;">
        <span style="font-size:3rem;display:block;margin-bottom:12px;">📷</span>
        <p>No hay fotos disponibles</p>
    </div>
    <?php endif; ?>

</div><!-- /.alojamiento-gallery -->
<?php endif; ?>

<?php 
// Verificar que todas las variables necesarias existan
if (isset($alojamiento) && $alojamiento && isset($t) && isset($fotos)):
?>
<div class="alojamiento-gallery">
    <h2 class="section-title"><i class="fas fa-camera"></i> <?php echo isset($t['fotos']) ? $t['fotos'] : 'Fotos'; ?></h2>
    
    <?php if (!empty($fotos)): ?>
    <?php
        // Extraer categoría del nombre del archivo para SEO (ej: "salon-1.webp" → "Salón")
        $categoryLabels = [
            'salon' => 'Salón', 'cocina' => 'Cocina', 'jardin' => 'Jardín',
            'habitacion' => 'Habitación', 'bano' => 'Baño', 'exterior' => 'Exterior',
            'piscina' => 'Piscina', 'comedor' => 'Comedor', 'terraza' => 'Terraza',
            'fachada' => 'Fachada', 'otro' => ''
        ];
        function getPhotoAlt($url, $name, $index) {
            global $categoryLabels;
            $basename = basename($url);
            // Intentar extraer categoría del nombre (ej: "salon-1.webp")
            if (preg_match('/^([a-z]+)-\d+\.webp$/i', $basename, $m)) {
                $cat = strtolower($m[1]);
                if (isset($categoryLabels[$cat])) {
                    return htmlspecialchars(($categoryLabels[$cat] ? $categoryLabels[$cat] . ' - ' : '') . $name, ENT_QUOTES, 'UTF-8');
                }
            }
            return htmlspecialchars('Foto ' . ($index+1) . ' de ' . $name, ENT_QUOTES, 'UTF-8');
        }
    ?>
    <div class="gallery-main">
        <img id="galleryMainImage" src="<?php echo $fotos[0]; ?>" 
             alt="<?php echo getPhotoAlt($fotos[0], $alojamiento['name'] ?? '', 0); ?>" 
             class="main-image" loading="eager">
    </div>
    
    <?php if (count($fotos) > 1): ?>
    <div class="gallery-thumbnails">
        <?php foreach ($fotos as $i => $foto): ?>
        <img src="<?php echo $foto; ?>" 
             alt="<?php echo getPhotoAlt($foto, $alojamiento['name'] ?? '', $i); ?>" 
             class="thumbnail <?php echo $i === 0 ? 'active' : ''; ?>" 
             loading="lazy"
             onclick="document.getElementById('galleryMainImage').src='<?php echo $foto; ?>'; 
                      document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                      this.classList.add('active');">
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="no-photos">
        <i class="fas fa-image" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
        <p>No hay fotos disponibles</p>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

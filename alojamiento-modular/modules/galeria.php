<?php if ($alojamiento): ?>
<div class="alojamiento-gallery">
    <h2 class="section-title"><i class="fas fa-camera"></i> <?php echo $t['fotos']; ?></h2>
    
    <?php if (!empty($fotos)): ?>
    <div class="gallery-main">
        <img id="galleryMainImage" src="<?php echo $fotos[0]; ?>" 
             alt="<?php echo htmlspecialchars($alojamiento['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
             class="main-image" loading="eager">
    </div>
    
    <?php if (count($fotos) > 1): ?>
    <div class="gallery-thumbnails">
        <?php foreach ($fotos as $i => $foto): ?>
        <img src="<?php echo $foto; ?>" 
             alt="Foto <?php echo $i+1; ?> de <?php echo htmlspecialchars($alojamiento['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
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

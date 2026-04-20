<?php 
// Verificar que todas las variables necesarias existan
if (isset($alojamiento) && $alojamiento && isset($t)):
?>
<div class="alojamiento-contact">
    <h2 class="section-title"><i class="fas fa-phone-alt"></i> <?php echo isset($t['contacto']) ? $t['contacto'] : 'Contacto'; ?></h2>
    
    <div class="contact-buttons">
        <?php if (!empty($alojamiento['phone'])): ?>
        <a href="tel:<?php echo htmlspecialchars($alojamiento['phone'], ENT_QUOTES, 'UTF-8'); ?>" 
           class="btn-contact btn-phone">
            <i class="fas fa-phone"></i>
            <?php echo isset($t['llamar']) ? $t['llamar'] : 'Llamar'; ?>
        </a>
        
        <a href="https://wa.me/34<?php echo preg_replace('/[^0-9]/', '', $alojamiento['phone']); ?>" 
           target="_blank" class="btn-contact btn-whatsapp">
            <i class="fab fa-whatsapp"></i>
            <?php echo isset($t['whatsapp']) ? $t['whatsapp'] : 'WhatsApp'; ?>
        </a>
        <?php endif; ?>
        
        <?php if (!empty($alojamiento['email'])): ?>
        <a href="mailto:<?php echo htmlspecialchars($alojamiento['email'], ENT_QUOTES, 'UTF-8'); ?>" 
           class="btn-contact btn-email">
            <i class="fas fa-envelope"></i>
            <?php echo isset($t['email']) ? $t['email'] : 'Email'; ?>
        </a>
        <?php endif; ?>
        
        <?php if (!empty($alojamiento['website'])): ?>
        <a href="<?php echo htmlspecialchars($alojamiento['website'], ENT_QUOTES, 'UTF-8'); ?>" 
           target="_blank" class="btn-contact btn-website">
            <i class="fas fa-globe"></i>
            Visitar web
        </a>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($alojamiento['address'])): ?>
    <div class="contact-address">
        <i class="fas fa-map-marker-alt"></i>
        <span><?php echo htmlspecialchars($alojamiento['address'], ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

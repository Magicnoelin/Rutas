<?php
/**
 * MODULE: contacto.php — REVISADO (sin cambios de interlinking necesarios)
 *
 * Este módulo ya usaba <a href> correctamente en la versión original.
 * Se añade rel="noopener noreferrer" a los enlaces externos por seguridad
 * y se añade el parámetro ?text= al enlace de WhatsApp para mayor conversión.
 */
if (isset($alojamiento) && $alojamiento && isset($t)):
?>
<div class="alojamiento-contact">
    <h2 class="section-title">📞 <?php echo isset($t['contacto']) ? $t['contacto'] : 'Contacto'; ?></h2>

    <div class="contact-buttons">
        <?php if (!empty($alojamiento['phone'])): ?>
        <!-- Teléfono: <a href="tel:"> ✅ -->
        <a href="tel:<?php echo htmlspecialchars($alojamiento['phone'], ENT_QUOTES, 'UTF-8'); ?>"
           class="btn-contact btn-phone">
            📞 <?php echo isset($t['llamar']) ? $t['llamar'] : 'Llamar'; ?>
        </a>

        <!-- WhatsApp: <a href> con mensaje pre-rellenado ✅ -->
        <a href="https://wa.me/34<?php echo preg_replace('/[^0-9]/', '', $alojamiento['phone']); ?>?text=<?php echo urlencode('Hola, me interesa el alojamiento ' . ($alojamiento['name'] ?? '')); ?>"
           target="_blank" rel="noopener noreferrer"
           class="btn-contact btn-whatsapp">
            💬 <?php echo isset($t['whatsapp']) ? $t['whatsapp'] : 'WhatsApp'; ?>
        </a>
        <?php endif; ?>

        <?php if (!empty($alojamiento['email'])): ?>
        <!-- Email: <a href="mailto:"> ✅ -->
        <a href="mailto:<?php echo htmlspecialchars($alojamiento['email'], ENT_QUOTES, 'UTF-8'); ?>"
           class="btn-contact btn-email">
            ✉️ <?php echo isset($t['email']) ? $t['email'] : 'Email'; ?>
        </a>
        <?php endif; ?>

        <?php if (!empty($alojamiento['website'])): ?>
        <!-- Web externa: <a href> con rel="noopener" ✅ -->
        <a href="<?php echo htmlspecialchars($alojamiento['website'], ENT_QUOTES, 'UTF-8'); ?>"
           target="_blank" rel="noopener noreferrer"
           class="btn-contact btn-website">
            🌐 <?php echo isset($t['web']) ? $t['web'] : 'Visitar web'; ?>
        </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($alojamiento['address'])): ?>
    <div class="contact-address">
        <span>📍</span>
        <span>
            <?php echo htmlspecialchars($alojamiento['address'], ENT_QUOTES, 'UTF-8'); ?>
            <?php if (!empty($alojamiento['municipality'])): ?>, <?php echo htmlspecialchars($alojamiento['municipality'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
            <?php if (!empty($alojamiento['province'])): ?> (<?php echo htmlspecialchars($alojamiento['province'], ENT_QUOTES, 'UTF-8'); ?>)<?php endif; ?>
        </span>
    </div>
    <?php endif; ?>
</div><!-- /.alojamiento-contact -->
<?php endif; ?>

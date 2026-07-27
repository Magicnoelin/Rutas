<?php
/**
 * descripcion.php — Descripción, información práctica, contacto y mapa
 * Variables requeridas: $lugar, $t
 */
if (empty($lugar)) return;

if (!function_exists('esLugarGastronomico')) {
    function esLugarGastronomico(string $categoryName): bool {
        if (empty($categoryName)) return false;
        $lower = mb_strtolower($categoryName, 'UTF-8');
        foreach (['restauran','gastronom','enotur','bodega','cafeter','restauraci','taberna','hosteleria','hostelería'] as $kw) {
            if (strpos($lower, $kw) !== false) return true;
        }
        return false;
    }
}
$esGastronomico = esLugarGastronomico($lugar['category_name'] ?? '');
?>

<!-- ▸ DESCRIPCIÓN -->
<div class="lug-card">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($t['descripcion'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <?php if (!empty($lugar['description'])): ?>
        <?php
        // Usar description_linked (pre-generado con inbound links internos) si está disponible
        // IMPORTANTE: description_linked ya es HTML seguro (generado internamente)
        $desc_raw = !empty($lugar['description_linked']) ? $lugar['description_linked'] : $lugar['description'];
        $desc     = strip_tags($desc_raw, '<strong><b><em><i><u><p><br><ul><ol><li><a><h2><h3><h4>');
        $long     = strlen(strip_tags($desc)) > 350;
        ?>
        <div class="desc-text <?php echo $long ? 'collapsed' : ''; ?>" id="desc-text">
            <?php echo nl2br($desc); ?>
        </div>
        <?php if ($long): ?>
        <button class="desc-expand-btn" id="desc-expand-btn" onclick="expandDesc()" type="button">
            <?php echo htmlspecialchars($t['leer_mas'], ENT_QUOTES, 'UTF-8'); ?>
        </button>
        <?php endif; ?>
        <?php else: ?>
        <p style="color:var(--lug-text-l);font-style:italic;">Descripción no disponible.</p>
        <?php endif; ?>
    </div>
</div>

<!-- ▸ INFORMACIÓN PRÁCTICA -->
<?php
$hayInfo = !empty($lugar['opening_hours']) || !empty($lugar['entry_fee']) || !empty($lugar['entry_fee_details'])
        || !empty($lugar['visit_duration']) || !empty($lugar['best_season']) || !empty($lugar['accessibility'])
        || !empty($lugar['pet_friendly']) || !empty($lugar['suitable_for_children']);
if ($hayInfo):
?>
<div class="lug-card">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($t['info_practica'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="info-grid">

            <?php if (!empty($lugar['opening_hours'])): ?>
            <div class="info-item">
                <div class="info-icon" aria-hidden="true">🕐</div>
                <div class="info-label"><?php echo htmlspecialchars($t['horario'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="info-value"><?php echo htmlspecialchars($lugar['opening_hours'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($lugar['entry_fee']) || !empty($lugar['entry_fee_details']) || (!$esGastronomico && isset($lugar['entry_fee']))): ?>
            <div class="info-item">
                <div class="info-icon" aria-hidden="true">🎫</div>
                <div class="info-label"><?php echo htmlspecialchars($t['entrada'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="info-value"><?php
                    if (!empty($lugar['entry_fee'])) {
                        echo htmlspecialchars($lugar['entry_fee'], ENT_QUOTES, 'UTF-8') . '€';
                    } elseif (!empty($lugar['entry_fee_details'])) {
                        echo htmlspecialchars($lugar['entry_fee_details'], ENT_QUOTES, 'UTF-8');
                    } else {
                        echo htmlspecialchars($t['entrada_gratuita'], ENT_QUOTES, 'UTF-8');
                    }
                    if (!empty($lugar['entry_fee']) && !empty($lugar['entry_fee_details'])):
                ?><br><small style="color:var(--lug-text-l);font-weight:400;"><?php echo htmlspecialchars($lugar['entry_fee_details'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($lugar['visit_duration'])): ?>
            <div class="info-item">
                <div class="info-icon" aria-hidden="true">⏱️</div>
                <div class="info-label"><?php echo htmlspecialchars($t['duracion_visita'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="info-value"><?php echo htmlspecialchars($lugar['visit_duration'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($lugar['best_season'])): ?>
            <div class="info-item">
                <div class="info-icon" aria-hidden="true">🌸</div>
                <div class="info-label"><?php echo htmlspecialchars($t['mejor_epoca'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="info-value"><?php echo htmlspecialchars($lugar['best_season'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($lugar['accessibility'])): ?>
            <div class="info-item">
                <div class="info-icon" aria-hidden="true">♿</div>
                <div class="info-label"><?php echo htmlspecialchars($t['accesibilidad'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="info-value"><?php echo htmlspecialchars($lugar['accessibility'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($lugar['pet_friendly'])): ?>
            <div class="info-item">
                <div class="info-icon" aria-hidden="true">🐾</div>
                <div class="info-label"><?php echo htmlspecialchars($t['mascotas'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="info-value"><?php echo htmlspecialchars($t['admite_mascotas'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($lugar['suitable_for_children'])): ?>
            <div class="info-item">
                <div class="info-icon" aria-hidden="true">👶</div>
                <div class="info-label"><?php echo htmlspecialchars($t['ninos'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="info-value"><?php echo htmlspecialchars($t['apto_ninos'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <?php endif; ?>

        </div><!-- /.info-grid -->

        <?php
        $facilities = [];
        if (!empty($lugar['facilities'])) {
            $dec = json_decode($lugar['facilities'], true);
            if (is_array($dec)) $facilities = $dec;
        }
        if (!empty($facilities)):
        ?>
        <div style="margin-top:20px;">
            <div style="font-size:0.82rem;font-weight:700;color:var(--lug-text-l);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;">
                <?php echo htmlspecialchars($t['instalaciones'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <?php foreach ($facilities as $f): ?>
                <span style="background:#e8f5e9;color:#2F5233;font-size:0.75rem;font-weight:600;padding:4px 10px;border-radius:20px;border:1px solid #c8e6c9;">
                    ✓ <?php echo htmlspecialchars((string)$f, ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.lug-card-body -->
</div><!-- /.lug-card (info práctica) -->
<?php endif; ?>

<!-- ▸ CONTACTO -->
<?php if (!empty($lugar['phone']) || !empty($lugar['email']) || !empty($lugar['website'])): ?>
<div class="lug-card">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($t['contacto'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="contact-btns">
            <?php if (!empty($lugar['phone'])): ?>
            <a href="tel:<?php echo htmlspecialchars($lugar['phone'], ENT_QUOTES, 'UTF-8'); ?>"
               class="btn-contact btn-phone">
                <?php echo htmlspecialchars($t['llamar'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <a href="https://wa.me/34<?php echo preg_replace('/[^0-9]/', '', $lugar['phone']); ?>"
               target="_blank" rel="noopener noreferrer"
               class="btn-contact btn-whatsapp">
                <?php echo htmlspecialchars($t['whatsapp'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php endif; ?>

            <?php if (!empty($lugar['email'])): ?>
            <a href="mailto:<?php echo htmlspecialchars($lugar['email'], ENT_QUOTES, 'UTF-8'); ?>"
               class="btn-contact btn-email">
                <?php echo htmlspecialchars($t['email'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php endif; ?>

            <?php if (!empty($lugar['website'])): ?>
            <a href="<?php echo htmlspecialchars($lugar['website'], ENT_QUOTES, 'UTF-8'); ?>"
               target="_blank" rel="noopener noreferrer"
               class="btn-contact btn-website">
                <?php echo htmlspecialchars($t['web_oficial'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($lugar['address'])): ?>
        <div class="contact-addr">
            <span aria-hidden="true">📍</span>
            <span>
                <?php echo htmlspecialchars($lugar['address'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!empty($lugar['municipality'])): ?>, <?php echo htmlspecialchars($lugar['municipality'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                <?php if (!empty($lugar['province'])): ?> (<?php echo htmlspecialchars($lugar['province'], ENT_QUOTES, 'UTF-8'); ?>)<?php endif; ?>
                <?php if (!empty($lugar['postal_code'])): ?> · CP: <?php echo htmlspecialchars($lugar['postal_code'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
</div><!-- /.lug-card (contacto) -->
<?php endif; ?>

<!-- ▸ MAPA diferido (IntersectionObserver en lugar.js) -->
<?php if (!empty($lugar['latitude']) && !empty($lugar['longitude'])): ?>
<div id="lug-map-container" class="lug-card">
    <div id="map-placeholder" class="map-placeholder" onclick="initMap()">
        <div style="font-size:3rem" aria-hidden="true">🗺️</div>
        <strong style="font-size:1rem;"><?php echo htmlspecialchars($t['ver_mapa'], ENT_QUOTES, 'UTF-8'); ?></strong>
        <p style="font-size:0.9rem;color:var(--lug-text-l);">
            <?php echo htmlspecialchars(implode(', ', array_filter([$lugar['municipality'] ?? '', $lugar['province'] ?? ''])), ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <span class="map-ph-hint"><?php echo htmlspecialchars($t['click_mapa'], ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div id="lug-map" aria-label="Mapa interactivo de <?php echo htmlspecialchars($lugar['name'], ENT_QUOTES, 'UTF-8'); ?>"></div>
</div>
<?php endif; ?>

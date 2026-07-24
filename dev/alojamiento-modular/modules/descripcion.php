<?php
/**
 * MODULE: descripcion.php — REVISADO (sin cambios de interlinking necesarios)
 *
 * Este módulo no tenía elementos onclick. Se mantiene igual que el original
 * con una pequeña mejora: el botón "Leer más" usa el mismo patrón JS del
 * index.php (función expandDesc() que ya existe en el script principal).
 */
if (isset($alojamiento) && $alojamiento && isset($t) && isset($tipo_display) && isset($capacidad_display)):
    $allowed_tags = '<strong><b><em><i><u><p><br><ul><ol><li><h2><h3><h4><span><a>';
    $desc_raw  = !empty($alojamiento['description_linked'])
        ? $alojamiento['description_linked']
        : ($alojamiento['description'] ?? '');
    $desc_safe = strip_tags($desc_raw, $allowed_tags);
    $longDesc  = strlen(strip_tags($desc_safe)) > 350;
?>
<div class="alojamiento-description">
    <h2 class="section-title">📋 <?php echo isset($t['descripcion']) ? $t['descripcion'] : 'Descripción'; ?></h2>

    <?php if (!empty($desc_safe)): ?>
    <div class="desc-text <?php echo $longDesc ? 'collapsed' : ''; ?>" id="descText">
        <?php echo nl2br($desc_safe); ?>
    </div>
    <?php if ($longDesc): ?>
    <!-- Botón expandir: no es navegación, JS correcto para esta acción de UI -->
    <button class="desc-expand-btn" id="descExpandBtn" type="button"
            onclick="expandDesc()">
        ↓ <?php echo isset($t['leer_mas']) ? $t['leer_mas'] : 'Leer más'; ?>
    </button>
    <?php endif; ?>
    <?php else: ?>
    <p style="color:#999;font-style:italic;">No hay descripción disponible.</p>
    <?php endif; ?>

    <!-- Grid de características -->
    <div class="features-grid">
        <?php if (!empty($tipo_display)): ?>
        <div class="feature-item">
            <span style="font-size:1.4rem;">🏠</span>
            <div>
                <strong><?php echo isset($t['tipo']) ? $t['tipo'] : 'Tipo'; ?></strong>
                <p><?php echo htmlspecialchars($tipo_display); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($capacidad_display)): ?>
        <div class="feature-item">
            <span style="font-size:1.4rem;">👥</span>
            <div>
                <strong><?php echo isset($t['capacidad']) ? $t['capacidad'] : 'Capacidad'; ?></strong>
                <p><?php echo htmlspecialchars($capacidad_display); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($alojamiento['check_in_time'])): ?>
        <div class="feature-item">
            <span style="font-size:1.4rem;">🔑</span>
            <div>
                <strong><?php echo isset($t['checkin']) ? $t['checkin'] : 'Check-in'; ?></strong>
                <p><?php echo htmlspecialchars($alojamiento['check_in_time'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($alojamiento['check_out_time'])): ?>
        <div class="feature-item">
            <span style="font-size:1.4rem;">🚪</span>
            <div>
                <strong><?php echo isset($t['checkout']) ? $t['checkout'] : 'Check-out'; ?></strong>
                <p><?php echo htmlspecialchars($alojamiento['check_out_time'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($alojamiento['services'])): ?>
        <div class="feature-item">
            <span style="font-size:1.4rem;">⭐</span>
            <div>
                <strong><?php echo isset($t['servicios']) ? $t['servicios'] : 'Servicios'; ?></strong>
                <p><?php echo htmlspecialchars($alojamiento['services'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div><!-- /.alojamiento-description -->
<?php endif; ?>

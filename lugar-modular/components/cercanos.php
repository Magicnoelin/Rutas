<?php
/**
 * cercanos.php — Secciones de contenido cercano con skeleton screens
 * Variables requeridas: $lugar, $t, $slug
 * El contenido real se carga por AJAX desde lugar.js
 */
if (empty($lugar)) return;
$nombre = htmlspecialchars($lugar['name'], ENT_QUOTES, 'UTF-8');
?>

<!-- ▸ ALOJAMIENTOS CERCANOS -->
<div id="nearby-alojamientos-section" class="lug-card" style="display:none;">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($t['dormir_cerca'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <p style="color:var(--lug-text-l);font-size:0.88rem;margin-bottom:16px;">
            <?php echo htmlspecialchars($t['dormir_desc'], ENT_QUOTES, 'UTF-8'); ?> <?php echo $nombre; ?>.
        </p>
        <div id="nearby-alojamientos" class="nearby-grid">
            <div class="skeleton skeleton-card"></div>
            <div class="skeleton skeleton-card"></div>
            <div class="skeleton skeleton-card"></div>
            <div class="skeleton skeleton-card"></div>
        </div>
        <div class="nearby-show-more" id="more-alojamientos">
            <button onclick="showMoreNearby('alojamientos')" type="button">
                <?php echo htmlspecialchars($t['ver_mas_aloj'], ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </div>
    </div>
</div>

<!-- ▸ ACTIVIDADES CERCANAS -->
<div id="nearby-actividades-section" class="lug-card" style="display:none;">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($t['activ_cercanas'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div id="nearby-actividades" class="nearby-grid">
            <div class="skeleton skeleton-card"></div>
            <div class="skeleton skeleton-card"></div>
            <div class="skeleton skeleton-card"></div>
        </div>
        <div class="nearby-show-more" id="more-actividades">
            <button onclick="showMoreNearby('actividades')" type="button">
                <?php echo htmlspecialchars($t['ver_mas_activ'], ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </div>
    </div>
</div>

<!-- ▸ EVENTOS CERCANOS -->
<div id="nearby-eventos-section" class="lug-card" style="display:none;">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($t['eventos_cercanos'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div id="nearby-eventos" class="nearby-grid">
            <div class="skeleton skeleton-card"></div>
            <div class="skeleton skeleton-card"></div>
            <div class="skeleton skeleton-card"></div>
        </div>
        <div class="nearby-show-more" id="more-eventos">
            <button onclick="showMoreNearby('eventos')" type="button">
                <?php echo htmlspecialchars($t['ver_mas_eventos'], ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </div>
    </div>
</div>

<!-- ▸ LUGARES CERCANOS -->
<div id="nearby-lugares-section" class="lug-card" style="display:none;">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($t['lugares_cercanos'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div id="nearby-lugares" class="nearby-grid">
            <div class="skeleton skeleton-card"></div>
            <div class="skeleton skeleton-card"></div>
            <div class="skeleton skeleton-card"></div>
        </div>
        <div class="nearby-show-more" id="more-lugares">
            <button onclick="showMoreNearby('lugares')" type="button">
                <?php echo htmlspecialchars($t['ver_mas_lugares'], ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </div>
    </div>
</div>

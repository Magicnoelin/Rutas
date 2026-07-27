<?php
/**
 * cercanos.php — Secciones de contenido cercano (carga AJAX diferida)
 * Variables requeridas: $lugar, $t
 */
if (empty($lugar)) return;

// Acceso seguro a claves de $t con fallback
$_t = [
    'dormir_cerca'     => isset($t['dormir_cerca'])     ? $t['dormir_cerca']     : '🏠 ¿Dónde dormir cerca?',
    'dormir_desc'      => isset($t['dormir_desc'])      ? $t['dormir_desc']      : 'Alojamientos rurales a pocos kilómetros',
    'activ_cercanas'   => isset($t['activ_cercanas'])   ? $t['activ_cercanas']   : '🎯 Actividades turísticas cercanas',
    'eventos_cercanos' => isset($t['eventos_cercanos']) ? $t['eventos_cercanos'] : '🎭 Eventos culturales próximos',
    'lugares_cercanos' => isset($t['lugares_cercanos']) ? $t['lugares_cercanos'] : '🏛️ Otros lugares de interés cerca',
    'ver_mas_aloj'     => isset($t['ver_mas_aloj'])     ? $t['ver_mas_aloj']     : 'Ver más alojamientos',
    'ver_mas_activ'    => isset($t['ver_mas_activ'])    ? $t['ver_mas_activ']    : 'Ver más actividades',
    'ver_mas_eventos'  => isset($t['ver_mas_eventos'])  ? $t['ver_mas_eventos']  : 'Ver más eventos',
    'ver_mas_lugares'  => isset($t['ver_mas_lugares'])  ? $t['ver_mas_lugares']  : 'Ver más lugares',
];

$prov = isset($lugar['province'])     ? $lugar['province']     : '';
$muni = isset($lugar['municipality']) ? $lugar['municipality'] : '';

// Generar skeleton HTML reutilizable
function skeletonCards(int $n = 4): string {
    $html = '<div class="nearby-grid">';
    for ($i = 0; $i < $n; $i++) {
        $html .= '<div class="nearby-card skeleton-card">
            <div class="skeleton skeleton-img"></div>
            <div class="skeleton-body">
                <div class="skeleton skeleton-title"></div>
                <div class="skeleton skeleton-text"></div>
            </div>
        </div>';
    }
    $html .= '</div>';
    return $html;
}
?>

<!-- ══ CONTENIDO CERCANO (cargado por AJAX en lugar.js) ══ -->

<!-- ▸ Alojamientos cercanos -->
<section class="lug-card nearby-section" id="nearby-aloj" aria-label="<?php echo htmlspecialchars($_t['dormir_cerca'], ENT_QUOTES, 'UTF-8'); ?>">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($_t['dormir_cerca'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <p class="nearby-subtitle"><?php echo htmlspecialchars($_t['dormir_desc'], ENT_QUOTES, 'UTF-8'); ?></p>
        <div id="nearby-aloj-content" data-loaded="false">
            <?php echo skeletonCards(4); ?>
        </div>
        <div id="nearby-aloj-more" style="display:none;text-align:center;margin-top:16px;">
            <a href="/alojamientos?provincia=<?php echo urlencode($prov); ?>"
               class="nearby-ver-mas">
                <?php echo htmlspecialchars($_t['ver_mas_aloj'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </div>
</section>

<!-- ▸ Actividades cercanas -->
<section class="lug-card nearby-section" id="nearby-activ" aria-label="<?php echo htmlspecialchars($_t['activ_cercanas'], ENT_QUOTES, 'UTF-8'); ?>">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($_t['activ_cercanas'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div id="nearby-activ-content" data-loaded="false">
            <?php echo skeletonCards(4); ?>
        </div>
        <div id="nearby-activ-more" style="display:none;text-align:center;margin-top:16px;">
            <a href="/actividades?provincia=<?php echo urlencode($prov); ?>"
               class="nearby-ver-mas">
                <?php echo htmlspecialchars($_t['ver_mas_activ'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </div>
</section>

<!-- ▸ Eventos cercanos -->
<section class="lug-card nearby-section" id="nearby-eventos" aria-label="<?php echo htmlspecialchars($_t['eventos_cercanos'], ENT_QUOTES, 'UTF-8'); ?>">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($_t['eventos_cercanos'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div id="nearby-eventos-content" data-loaded="false">
            <?php echo skeletonCards(4); ?>
        </div>
        <div id="nearby-eventos-more" style="display:none;text-align:center;margin-top:16px;">
            <a href="/eventos-culturales?provincia=<?php echo urlencode($prov); ?>"
               class="nearby-ver-mas">
                <?php echo htmlspecialchars($_t['ver_mas_eventos'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </div>
</section>

<!-- ▸ Lugares cercanos -->
<section class="lug-card nearby-section" id="nearby-lugares" aria-label="<?php echo htmlspecialchars($_t['lugares_cercanos'], ENT_QUOTES, 'UTF-8'); ?>">
    <div class="lug-card-body">
        <h2 class="lug-card-title"><?php echo htmlspecialchars($_t['lugares_cercanos'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div id="nearby-lugares-content" data-loaded="false">
            <?php echo skeletonCards(4); ?>
        </div>
        <div id="nearby-lugares-more" style="display:none;text-align:center;margin-top:16px;">
            <a href="/lugares-de-interes?provincia=<?php echo urlencode($prov); ?>"
               class="nearby-ver-mas">
                <?php echo htmlspecialchars($_t['ver_mas_lugares'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </div>
</section>

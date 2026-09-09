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
        <div id="nearby-aloj-content" data-loaded="true">
            <?php if (!empty($ssr_nearby_alojamientos)): ?>
                <div class="nearby-grid">
                    <?php foreach ($ssr_nearby_alojamientos as $item): ?>
                        <a href="/alojamiento/<?php echo esc($item['slug']); ?>" class="nearby-card">
                            <img src="<?php echo esc($item['photo1'] ?? '/menu_images/turismo_rural.webp'); ?>" alt="<?php echo esc($item['name']); ?>" loading="lazy" class="nearby-card-img">
                            <div class="nearby-card-body">
                                <h3 class="nearby-card-title"><?php echo esc($item['name']); ?></h3>
                                <p class="nearby-card-meta"><?php echo esc($item['municipality']); ?> <?php echo !empty($item['dist']) ? '(' . round($item['dist']) . 'km)' : ''; ?></p>
                                <?php if (!empty($item['price_per_night']) && (float)$item['price_per_night'] > 0): ?>
                                <p class="nearby-card-price" style="color:var(--lug-primary);font-weight:600;font-size:0.9rem;margin-top:4px;">
                                    <?php echo number_format((float)$item['price_per_night'], 0, ',', '.'); ?> €/noche
                                </p>
                                <?php endif; ?>
                                <?php if (!empty($item['short_description'])): ?>
                                <p class="nearby-card-desc" style="font-size:0.8rem;color:var(--text-light);margin-top:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                    <?php echo esc($item['short_description']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-nearby-results">No se encontraron alojamientos cercanos.</p>
            <?php endif; ?>
        </div>
        <div id="nearby-aloj-more" style="text-align:center;margin-top:16px;<?php echo empty($ssr_nearby_alojamientos) ? 'display:none;' : ''; ?>">
            <a href="/rutas.php?provincia=<?php echo urlencode($prov); ?>&alojamientos=1&lat=<?php echo esc($lugar['latitude']); ?>&lng=<?php echo esc($lugar['longitude']); ?>"
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
        <div id="nearby-activ-content" data-loaded="true">
            <?php if (!empty($ssr_nearby_actividades)): ?>
                <div class="nearby-grid">
                    <?php foreach ($ssr_nearby_actividades as $item): ?>
                        <a href="/actividad/<?php echo esc($item['slug']); ?>" class="nearby-card">
                            <img src="<?php echo esc($item['photo1'] ?? '/tourist_activities_images/Patrocinio.webp'); ?>" alt="<?php echo esc($item['name']); ?>" loading="lazy" class="nearby-card-img">
                            <div class="nearby-card-body">
                                <h3 class="nearby-card-title"><?php echo esc($item['name']); ?></h3>
                                <p class="nearby-card-meta"><?php echo esc($item['municipality']); ?> <?php echo !empty($item['dist']) ? '(' . round($item['dist']) . 'km)' : ''; ?></p>
                                <?php if (!empty($item['price_adult']) && (float)$item['price_adult'] > 0): ?>
                                <p class="nearby-card-price" style="color:var(--lug-primary);font-weight:600;font-size:0.9rem;margin-top:4px;">
                                    <?php echo number_format((float)$item['price_adult'], 0, ',', '.'); ?> €/persona
                                </p>
                                <?php endif; ?>
                                <?php if (!empty($item['description'])): ?>
                                <p class="nearby-card-desc" style="font-size:0.8rem;color:var(--text-light);margin-top:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                    <?php echo esc(strip_tags($item['description'])); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-nearby-results">No se encontraron actividades cercanas.</p>
            <?php endif; ?>
        </div>
        <div id="nearby-activ-more" style="text-align:center;margin-top:16px;<?php echo empty($ssr_nearby_actividades) ? 'display:none;' : ''; ?>">
            <a href="/rutas.php?provincia=<?php echo urlencode($prov); ?>&actividades=1&lat=<?php echo esc($lugar['latitude']); ?>&lng=<?php echo esc($lugar['longitude']); ?>"
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
        <div id="nearby-eventos-content" data-loaded="true">
            <?php if (!empty($ssr_nearby_eventos)): ?>
                <div class="nearby-grid">
                    <?php foreach ($ssr_nearby_eventos as $item): ?>
                        <a href="/evento/<?php echo esc($item['slug']); ?>" class="nearby-card">
                            <img src="<?php echo esc($item['photo1'] ?? $item['poster_image'] ?? '/cultural_events_images/evento_default.webp'); ?>" alt="<?php echo esc($item['name']); ?>" loading="lazy" class="nearby-card-img">
                            <div class="nearby-card-body">
                                <h3 class="nearby-card-title"><?php echo esc($item['name']); ?></h3>
                                <p class="nearby-card-meta"><?php echo esc($item['municipality']); ?> <?php echo !empty($item['dist']) ? '(' . round($item['dist']) . 'km)' : ''; ?></p>
                                <?php if (isset($item['is_free']) && $item['is_free']): ?>
                                <p class="nearby-card-price" style="color:#27ae60;font-weight:600;font-size:0.9rem;margin-top:4px;">
                                    Entrada gratuita
                                </p>
                                <?php elseif (!empty($item['ticket_price']) && (float)$item['ticket_price'] > 0): ?>
                                <p class="nearby-card-price" style="color:var(--lug-primary);font-weight:600;font-size:0.9rem;margin-top:4px;">
                                    <?php echo number_format((float)$item['ticket_price'], 0, ',', '.'); ?> €
                                </p>
                                <?php endif; ?>
                                <?php if (!empty($item['description'])): ?>
                                <p class="nearby-card-desc" style="font-size:0.8rem;color:var(--text-light);margin-top:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                    <?php echo esc(strip_tags($item['description'])); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-nearby-results">No se encontraron eventos cercanos.</p>
            <?php endif; ?>
        </div>
        <div id="nearby-eventos-more" style="text-align:center;margin-top:16px;<?php echo empty($ssr_nearby_eventos) ? 'display:none;' : ''; ?>">
            <a href="/rutas.php?provincia=<?php echo urlencode($prov); ?>&eventos=1&lat=<?php echo esc($lugar['latitude']); ?>&lng=<?php echo esc($lugar['longitude']); ?>"
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
        <div id="nearby-lugares-content" data-loaded="true">
            <?php if (!empty($ssr_nearby_lugares)): ?>
                <div class="nearby-grid">
                    <?php foreach ($ssr_nearby_lugares as $item): ?>
                        <a href="/lugar/<?php echo esc($item['slug']); ?>" class="nearby-card">
                            <img src="<?php echo esc($item['photo1'] ?? '/menu_images/turismo_rural.webp'); ?>" alt="<?php echo esc($item['name']); ?>" loading="lazy" class="nearby-card-img">
                            <div class="nearby-card-body">
                                <h3 class="nearby-card-title"><?php echo esc($item['name']); ?></h3>
                                <p class="nearby-card-meta"><?php echo esc($item['municipality']); ?> <?php echo !empty($item['dist']) ? '(' . round($item['dist']) . 'km)' : ''; ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-nearby-results">No se encontraron lugares de interés cercanos.</p>
            <?php endif; ?>
        </div>
        <div id="nearby-lugares-more" style="text-align:center;margin-top:16px;<?php echo empty($ssr_nearby_lugares) ? 'display:none;' : ''; ?>">
            <a href="/rutas.php?provincia=<?php echo urlencode($prov); ?>&lugares=1&lat=<?php echo esc($lugar['latitude']); ?>&lng=<?php echo esc($lugar['longitude']); ?>"
               class="nearby-ver-mas">
                <?php echo htmlspecialchars($_t['ver_mas_lugares'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </div>
</section>

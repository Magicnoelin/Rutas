<?php
/**
 * MODULE: cercanos.php — REFACTORIZADO (SEO interlinking)
 * 
 * CAMBIOS vs versión legacy:
 * - Los divs con loading spinners han sido reemplazados por renderizado SSR (PHP).
 * - Las 4 secciones (alojamientos, lugares, actividades, eventos) se renderizan
 *   directamente como <a href> nativos → Google los indexa sin JS.
 * - Variables $ssr_nearby_* se pasan desde index.php (igual que en producción).
 * - Se eliminan los divs #nearby-* con display:none que se pueblan vía fetch().
 */
if (isset($alojamiento) && $alojamiento && isset($t)):

    // Estas variables deben venir del index.php padre (SSR queries)
    $nearby_alojamientos = $ssr_nearby_alojamientos ?? [];
    $nearby_lugares      = $ssr_nearby_lugares      ?? [];
    $nearby_actividades  = $ssr_nearby_actividades  ?? [];
    $nearby_eventos      = $ssr_nearby_eventos      ?? [];
?>
<div class="alojamiento-nearby">
    <h2 class="section-title">🧭 <?php echo isset($t['cercanos']) ? $t['cercanos'] : '¿Qué hay cerca?'; ?></h2>

    <div class="nearby-sections">

        <!-- ── Alojamientos cercanos (SSR: <a href> nativos, Google los indexa) ── -->
        <?php if (!empty($nearby_alojamientos)): ?>
        <div class="nearby-section">
            <h3><?php echo isset($t['aloj_cercanos']) ? $t['aloj_cercanos'] : '🏠 Alojamientos cercanos'; ?></h3>
            <div class="nearby-grid">
                <?php foreach ($nearby_alojamientos as $nr): ?>
                <?php
                    $nr_url   = '/alojamiento/' . htmlspecialchars($nr['slug'] ?? '');
                    $nr_name  = htmlspecialchars($nr['name'] ?? '');
                    $nr_munic = htmlspecialchars($nr['municipality'] ?? '');
                    $nr_price = !empty($nr['price_per_night']) && $nr['price_per_night'] > 0
                        ? number_format($nr['price_per_night'], 0, ',', '.') . '€/noche' : '';
                    $nr_img   = !empty($nr['photo1']) ? htmlspecialchars($nr['photo1']) : '';
                    if ($nr_img && !preg_match('/^https?:\/\//', $nr_img)) $nr_img = '/' . ltrim($nr_img, '/');
                    $nr_dist  = isset($nr['dist']) && $nr['dist'] > 0 ? round($nr['dist'], 1) . ' km' : '';
                ?>
                <a href="<?php echo $nr_url; ?>" class="nearby-card" title="<?php echo $nr_name; ?><?php echo $nr_munic ? ' en ' . $nr_munic : ''; ?>">
                    <div class="nearby-card-img">
                        <?php if ($nr_img): ?>
                        <img src="<?php echo $nr_img; ?>" alt="<?php echo $nr_name; ?>" loading="lazy" width="200" height="120">
                        <?php else: ?>
                        <div class="nearby-card-img-placeholder">🏠</div>
                        <?php endif; ?>
                        <?php if ($nr_dist): ?><span class="nearby-card-dist"><?php echo $nr_dist; ?></span><?php endif; ?>
                    </div>
                    <div class="nearby-card-body">
                        <div class="nearby-card-name"><?php echo $nr_name; ?></div>
                        <?php if ($nr_munic): ?><div class="nearby-card-meta">📍 <?php echo $nr_munic; ?></div><?php endif; ?>
                        <?php if ($nr_price): ?><div class="nearby-card-price"><?php echo $nr_price; ?></div><?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($alojamiento['province'])): ?>
            <div class="nearby-show-more">
                <a href="/alojamientos/<?php echo strtolower(htmlspecialchars($alojamiento['province'])); ?>"
                   class="nearby-ver-mas-link">
                    <?php echo isset($t['ver_mas_aloj']) ? $t['ver_mas_aloj'] : 'Ver más alojamientos'; ?> →
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── Lugares de interés cercanos (SSR) ── -->
        <?php if (!empty($nearby_lugares)): ?>
        <div class="nearby-section">
            <h3><?php echo isset($t['lugares_cercanos']) ? $t['lugares_cercanos'] : '🏛️ Lugares de interés cercanos'; ?></h3>
            <div class="nearby-grid">
                <?php foreach ($nearby_lugares as $nl): ?>
                <?php
                    $nl_url   = '/lugar/' . htmlspecialchars($nl['slug'] ?? '');
                    $nl_name  = htmlspecialchars($nl['name'] ?? '');
                    $nl_munic = htmlspecialchars($nl['municipality'] ?? '');
                    $nl_img   = !empty($nl['photo1']) ? htmlspecialchars($nl['photo1']) : '';
                    if ($nl_img && !preg_match('/^https?:\/\//', $nl_img)) $nl_img = '/' . ltrim($nl_img, '/');
                    $nl_dist  = isset($nl['dist']) && $nl['dist'] > 0 ? round($nl['dist'], 1) . ' km' : '';
                ?>
                <a href="<?php echo $nl_url; ?>" class="nearby-card" title="<?php echo $nl_name; ?>">
                    <div class="nearby-card-img">
                        <?php if ($nl_img): ?>
                        <img src="<?php echo $nl_img; ?>" alt="<?php echo $nl_name; ?>" loading="lazy" width="200" height="120">
                        <?php else: ?>
                        <div class="nearby-card-img-placeholder">🏛️</div>
                        <?php endif; ?>
                        <?php if ($nl_dist): ?><span class="nearby-card-dist"><?php echo $nl_dist; ?></span><?php endif; ?>
                    </div>
                    <div class="nearby-card-body">
                        <div class="nearby-card-name"><?php echo $nl_name; ?></div>
                        <?php if ($nl_munic): ?><div class="nearby-card-meta">📍 <?php echo $nl_munic; ?></div><?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($alojamiento['province'])): ?>
            <div class="nearby-show-more">
                <a href="/lugares-de-interes?provincia=<?php echo urlencode($alojamiento['province']); ?>"
                   class="nearby-ver-mas-link">
                    <?php echo isset($t['ver_mas_lugares']) ? $t['ver_mas_lugares'] : 'Ver más lugares'; ?> →
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── Actividades turísticas cercanas (SSR) ── -->
        <?php if (!empty($nearby_actividades)): ?>
        <div class="nearby-section">
            <h3><?php echo isset($t['activ_cercanas']) ? $t['activ_cercanas'] : '🎯 Actividades turísticas cercanas'; ?></h3>
            <div class="nearby-grid">
                <?php foreach ($nearby_actividades as $na): ?>
                <?php
                    $na_url   = '/actividad/' . htmlspecialchars($na['slug'] ?? '');
                    $na_name  = htmlspecialchars($na['name'] ?? '');
                    $na_munic = htmlspecialchars($na['municipality'] ?? '');
                    $na_img   = !empty($na['photo1']) ? htmlspecialchars($na['photo1']) : '';
                    if ($na_img && !preg_match('/^https?:\/\//', $na_img)) $na_img = '/' . ltrim($na_img, '/');
                    $na_dist  = isset($na['dist']) && $na['dist'] > 0 ? round($na['dist'], 1) . ' km' : '';
                ?>
                <a href="<?php echo $na_url; ?>" class="nearby-card" title="<?php echo $na_name; ?>">
                    <div class="nearby-card-img">
                        <?php if ($na_img): ?>
                        <img src="<?php echo $na_img; ?>" alt="<?php echo $na_name; ?>" loading="lazy" width="200" height="120">
                        <?php else: ?>
                        <div class="nearby-card-img-placeholder">🎯</div>
                        <?php endif; ?>
                        <?php if ($na_dist): ?><span class="nearby-card-dist"><?php echo $na_dist; ?></span><?php endif; ?>
                    </div>
                    <div class="nearby-card-body">
                        <div class="nearby-card-name"><?php echo $na_name; ?></div>
                        <?php if ($na_munic): ?><div class="nearby-card-meta">📍 <?php echo $na_munic; ?></div><?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($alojamiento['province'])): ?>
            <div class="nearby-show-more">
                <a href="/actividades-turisticas?provincia=<?php echo urlencode($alojamiento['province']); ?>"
                   class="nearby-ver-mas-link">
                    <?php echo isset($t['ver_mas_activ']) ? $t['ver_mas_activ'] : 'Ver más actividades'; ?> →
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── Eventos culturales cercanos (SSR) ── -->
        <?php if (!empty($nearby_eventos)): ?>
        <div class="nearby-section">
            <h3><?php echo isset($t['eventos_cercanos']) ? $t['eventos_cercanos'] : '🎭 Eventos culturales cercanos'; ?></h3>
            <div class="nearby-grid">
                <?php foreach ($nearby_eventos as $ne): ?>
                <?php
                    $ne_url   = '/evento/' . htmlspecialchars($ne['slug'] ?? '');
                    $ne_name  = htmlspecialchars($ne['name'] ?? '');
                    $ne_munic = htmlspecialchars($ne['municipality'] ?? '');
                    $ne_img   = !empty($ne['poster_image']) ? $ne['poster_image'] : ($ne['photo1'] ?? '');
                    if ($ne_img && !preg_match('/^https?:\/\//', $ne_img)) $ne_img = '/' . ltrim($ne_img, '/');
                    $ne_img   = htmlspecialchars($ne_img);
                    $ne_dist  = isset($ne['dist']) && $ne['dist'] > 0 ? round($ne['dist'], 1) . ' km' : '';
                    $ne_fecha = '';
                    if (!empty($ne['start_date'])) {
                        try { $dt = new DateTime($ne['start_date']); $ne_fecha = $dt->format('d/m/Y'); } catch (Exception $e) {}
                    }
                    $ne_gratis = !empty($ne['is_free']) && $ne['is_free'] == 1;
                    $ne_precio = !$ne_gratis && !empty($ne['ticket_price']) && $ne['ticket_price'] > 0
                        ? number_format($ne['ticket_price'], 0, ',', '.') . '€' : '';
                ?>
                <a href="<?php echo $ne_url; ?>" class="nearby-card" title="<?php echo $ne_name; ?>">
                    <div class="nearby-card-img">
                        <?php if ($ne_img): ?>
                        <img src="<?php echo $ne_img; ?>" alt="<?php echo $ne_name; ?>" loading="lazy" width="200" height="120">
                        <?php else: ?>
                        <div class="nearby-card-img-placeholder">🎭</div>
                        <?php endif; ?>
                        <?php if ($ne_dist): ?><span class="nearby-card-dist"><?php echo $ne_dist; ?></span><?php endif; ?>
                    </div>
                    <div class="nearby-card-body">
                        <div class="nearby-card-name"><?php echo $ne_name; ?></div>
                        <?php if ($ne_munic): ?><div class="nearby-card-meta">📍 <?php echo $ne_munic; ?></div><?php endif; ?>
                        <?php if ($ne_fecha): ?><div class="nearby-card-meta">📅 <?php echo $ne_fecha; ?></div><?php endif; ?>
                        <?php if ($ne_gratis): ?>
                        <span class="nearby-card-free"><?php echo isset($t['gratis']) ? $t['gratis'] : 'Gratis'; ?></span>
                        <?php elseif ($ne_precio): ?>
                        <div class="nearby-card-price"><?php echo $ne_precio; ?></div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($alojamiento['province'])): ?>
            <div class="nearby-show-more">
                <a href="/eventos-culturales?provincia=<?php echo urlencode($alojamiento['province']); ?>"
                   class="nearby-ver-mas-link">
                    <?php echo isset($t['ver_mas_eventos']) ? $t['ver_mas_eventos'] : 'Ver más eventos'; ?> →
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div><!-- /.nearby-sections -->
</div><!-- /.alojamiento-nearby -->
<?php endif; ?>

<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  LISTING — Grid de Tarjetas de Alojamientos
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Características de rendimiento:
 *  - Lazy loading estricto (loading="lazy" + decoding="async") en todas
 *    las imágenes EXCEPTO la primera (LCP candidate → loading="eager")
 *  - Dimensiones explícitas width/height en cada img → sin CLS
 *  - Sin JS para el renderizado de las tarjetas (SSR puro)
 *
 *  $ctx esperado:
 *    items (array de alojamientos), total, pages, page (int),
 *    t (traducciones), lang, canonical, h2_listing (string)
 */

function renderLandingListing(array $ctx): void
{
    $items    = $ctx['items']      ?? [];
    $total    = $ctx['total']      ?? 0;
    $pages    = $ctx['pages']      ?? 1;
    $page     = $ctx['page']       ?? 1;
    $t        = $ctx['t']          ?? [];
    $lang     = $ctx['lang']       ?? 'es';
    $canonical= $ctx['canonical']  ?? '';
    $h2       = $ctx['h2_listing'] ?? ($t['h2_listing'] ?? 'Alojamientos disponibles');
    $province = $ctx['province_label'] ?? '';
    $prov_key = $ctx['province_key']   ?? '';

    $base_url = 'https://rutasrurales.io';
?>
<!-- ════════════════════════════════════════════════════ LISTING ══ -->
<section class="lnd-listing" id="alojamientos" aria-labelledby="lnd-listing-title">

    <div class="lnd-listing__header">
        <h2 class="lnd-listing__title" id="lnd-listing-title">
            <?= htmlspecialchars($h2) ?>
        </h2>
        <?php if ($total > 0): ?>
        <p class="lnd-listing__count">
            <?= $total ?> <?= htmlspecialchars($t['stat_count'] ?? 'alojamientos') ?>
            <?php if ($pages > 1): ?>
            &nbsp;·&nbsp; <?= $t['page_of'] ?? 'Página' ?> <?= $page ?> <?= $t['page_of'] ?? 'de' ?> <?= $pages ?>
            <?php endif; ?>
        </p>
        <?php endif; ?>
    </div>

    <?php if (empty($items)): ?>
    <!-- Sin resultados -->
    <div class="lnd-no-results">
        <p class="lnd-no-results__icon" aria-hidden="true">🔍</p>
        <h3 class="lnd-no-results__h3"><?= htmlspecialchars($t['no_results_h2'] ?? 'Sin resultados') ?></h3>
        <p class="lnd-no-results__p">
            <?= htmlspecialchars(str_replace('{PROVINCE}', $province, $t['no_results_p'] ?? '')) ?>
        </p>
        <?php if (!empty($prov_key)): ?>
        <a href="<?= $base_url . ($lang !== 'es' ? "/$lang" : '') ?>/alojamientos/turismo-rural-<?= htmlspecialchars($prov_key) ?>"
           class="lnd-btn lnd-btn--secondary">
            <?= htmlspecialchars(str_replace('{PROVINCE}', $province, $t['no_results_cta'] ?? 'Ver todos los alojamientos')) ?>
        </a>
        <?php endif; ?>
    </div>

    <?php else: ?>

    <!-- Grid de tarjetas -->
    <ul class="lnd-grid" role="list">
        <?php foreach ($items as $i => $alo):
            $isFirst   = ($i === 0);
            $imgLoad   = $isFirst ? 'eager' : 'lazy';
            $imgDecode = $isFirst ? 'sync'  : 'async';
            $photoUrl  = htmlspecialchars($alo['photo_url'] ?? '');
            $aloUrl    = htmlspecialchars($alo['url'] ?? '#');
            $name      = htmlspecialchars($alo['name'] ?? '');
            $munic     = htmlspecialchars(trim(($alo['municipality'] ?? '') . ', ' . ($alo['province'] ?? ''), ', '));
            $tipo      = htmlspecialchars($alo['category_name'] ?? $alo['accommodation_type'] ?? '');
            $capacity  = (int)($alo['capacity'] ?? 0);
            $precio    = $alo['precio_display'] ?? null;

            // Badges de características
            $badges = [];
            if (!empty($alo['pet_friendly']))         $badges[] = $t['badge_pet']     ?? '🐾 Mascotas';
            if (!empty($alo['wifi']))                  $badges[] = $t['badge_wifi']    ?? '📶 WiFi';
            if (!empty($alo['suitable_for_children'])) $badges[] = $t['badge_kids']    ?? '👶 Niños';
            // Badges de amenities
            $amenStr = strtolower($alo['amenities'] ?? '');
            if (str_contains($amenStr, 'piscina') || str_contains($amenStr, 'pool'))
                $badges[] = $t['badge_pool']    ?? '🏊 Piscina';
            if (str_contains($amenStr, 'chimenea') || str_contains($amenStr, 'fireplace'))
                $badges[] = $t['badge_chimney'] ?? '🔥 Chimenea';
            if (str_contains($amenStr, 'jacuzzi'))
                $badges[] = $t['badge_jacuzzi'] ?? '♨️ Jacuzzi';
            if (str_contains($amenStr, 'terraza'))
                $badges[] = $t['badge_terrace'] ?? '🌅 Terraza';
            if (str_contains($amenStr, 'barbacoa') || str_contains($amenStr, 'barbecue'))
                $badges[] = $t['badge_bbq']     ?? '🍖 Barbacoa';
            $badges = array_slice($badges, 0, 4); // máx 4 badges
        ?>
        <li class="lnd-card" itemscope itemtype="https://schema.org/LodgingBusiness">

            <!-- Foto — dimensiones fijas para evitar CLS (ratio 3:2 = 600x400) -->
            <a href="<?= $aloUrl ?>" class="lnd-card__img-wrap" tabindex="-1" aria-hidden="true">
                <img
                    src="<?= $photoUrl ?>"
                    alt="<?= $name ?><?= $munic ? ' en ' . $munic : '' ?>"
                    width="600" height="400"
                    loading="<?= $imgLoad ?>"
                    decoding="<?= $imgDecode ?>"
                    class="lnd-card__img"
                    itemprop="image"
                    onerror="this.src='https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&h=400&fit=crop&auto=format&q=60'"
                >
                <?php if (!empty($tipo)): ?>
                <span class="lnd-card__type-badge"><?= $tipo ?></span>
                <?php endif; ?>
            </a>

            <!-- Contenido textual -->
            <div class="lnd-card__body">

                <!-- Municipio -->
                <?php if ($munic): ?>
                <p class="lnd-card__location">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                        <span itemprop="addressLocality"><?= htmlspecialchars($alo['municipality'] ?? '') ?></span><?php if (!empty($alo['province'])): ?>,
                        <span itemprop="addressRegion"><?= htmlspecialchars($alo['province']) ?></span>
                        <meta itemprop="addressCountry" content="ES">
                        <?php endif; ?>
                    </span>
                </p>
                <?php endif; ?>

                <!-- Nombre del alojamiento — H3 semántico -->
                <h3 class="lnd-card__name" itemprop="name">
                    <a href="<?= $aloUrl ?>" itemprop="url"><?= $name ?></a>
                </h3>

                <!-- Descripción corta -->
                <?php if (!empty($alo['short_description'])): ?>
                <p class="lnd-card__desc" itemprop="description">
                    <?= htmlspecialchars(mb_substr(strip_tags($alo['short_description']), 0, 100)) ?>…
                </p>
                <?php endif; ?>

                <!-- Badges de características -->
                <?php if (!empty($badges)): ?>
                <ul class="lnd-card__badges" aria-label="Características" role="list">
                    <?php foreach ($badges as $badge): ?>
                    <li class="lnd-badge lnd-badge--amenity"><?= htmlspecialchars($badge) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <!-- Footer de la tarjeta: capacidad + precio + CTA -->
                <div class="lnd-card__footer">
                    <div class="lnd-card__meta">
                        <?php if ($capacity > 0): ?>
                        <span class="lnd-card__cap">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            <?= $capacity ?> <?= htmlspecialchars($t['card_personas'] ?? 'personas') ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($precio): ?>
                        <span class="lnd-card__price" itemprop="priceRange">
                            <?= htmlspecialchars($t['card_desde'] ?? 'Desde') ?>
                            <strong><?= htmlspecialchars($precio) ?></strong>
                            <small>/<?= htmlspecialchars($t['card_noche'] ?? 'noche') ?></small>
                        </span>
                        <?php else: ?>
                        <span class="lnd-card__price lnd-card__price--consult">
                            <?= htmlspecialchars($t['card_consultar'] ?? 'Consultar precio') ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= $aloUrl ?>" class="lnd-btn lnd-btn--primary lnd-card__cta">
                        <?= htmlspecialchars($t['card_ver'] ?? 'Ver alojamiento') ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </div>

            </div><!-- /lnd-card__body -->
        </li>
        <?php endforeach; ?>
    </ul>

    <!-- Paginación accesible -->
    <?php if ($pages > 1): ?>
    <nav class="lnd-pagination" aria-label="Paginación de resultados">
        <ul class="lnd-pagination__list" role="list">
            <?php if ($page > 1): ?>
            <li>
                <a href="<?= htmlspecialchars($canonical) ?>?p=<?= $page - 1 ?>"
                   class="lnd-page-btn lnd-page-btn--prev"
                   rel="prev"
                   aria-label="<?= htmlspecialchars($t['page_prev'] ?? '← Anterior') ?>">
                    <?= htmlspecialchars($t['page_prev'] ?? '← Anterior') ?>
                </a>
            </li>
            <?php endif; ?>

            <?php
            // Ventana de paginación: máx 5 páginas visibles
            $startP = max(1, $page - 2);
            $endP   = min($pages, $page + 2);
            for ($p = $startP; $p <= $endP; $p++):
            ?>
            <li>
                <a href="<?= htmlspecialchars($canonical) ?>?p=<?= $p ?>"
                   class="lnd-page-btn<?= ($p === $page) ? ' lnd-page-btn--active' : '' ?>"
                   <?= ($p === $page) ? 'aria-current="page"' : '' ?>>
                    <?= $p ?>
                </a>
            </li>
            <?php endfor; ?>

            <?php if ($page < $pages): ?>
            <li>
                <a href="<?= htmlspecialchars($canonical) ?>?p=<?= $page + 1 ?>"
                   class="lnd-page-btn lnd-page-btn--next"
                   rel="next"
                   aria-label="<?= htmlspecialchars($t['page_next'] ?? 'Siguiente →') ?>">
                    <?= htmlspecialchars($t['page_next'] ?? 'Siguiente →') ?>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <?php endif; // !empty($items) ?>

</section>
<!-- ══════════════════════════════════════════════════ /LISTING ══ -->
<?php
}

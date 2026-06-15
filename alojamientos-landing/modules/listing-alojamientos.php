<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * LISTING — Grid de Tarjetas de Alojamientos Rurales
 * ════════════════════════════════════════════════════════════════════════════
 */

function renderAlojamientosLandingListing(array $ctx): void
{
    $items     = $ctx['items']          ?? [];
    $total     = $ctx['total']          ?? 0;
    $pages     = $ctx['pages']          ?? 1;
    $page      = $ctx['page']           ?? 1;
    $t         = $ctx['t']              ?? [];
    $lang      = $ctx['lang']           ?? 'es';
    $canonical = $ctx['canonical']      ?? '';
    $h2        = $ctx['h2_listing']     ?? ($t['h2_listing'] ?? 'Alojamientos disponibles');
    $province  = $ctx['province_label'] ?? '';
    $prov_key  = $ctx['province_key']   ?? '';
    $pdo       = $ctx['pdo']            ?? null;

    $base_url = 'https://rutasrurales.io';
?>
<section class="lnd-listing" id="alojamientos" aria-labelledby="lnd-listing-title">

    <div class="lnd-listing__header">
        <h2 class="lnd-listing__title" id="lnd-listing-title">
            <?= htmlspecialchars($h2) ?>
        </h2>
        <?php if ($total > 0): ?>
        <p class="lnd-listing__count">
            <?= $total ?> <?= htmlspecialchars($lang === 'es' ? 'alojamientos' : 'accommodations') ?>
            <?php if ($pages > 1): ?>
            &nbsp;·&nbsp; <?= $page ?> <?= $t['page_of'] ?? 'de' ?> <?= $pages ?>
            <?php endif; ?>
        </p>
        <?php endif; ?>
    </div>

    <?php if (empty($items)): ?>
    <div class="lnd-no-results">
        <p class="lnd-no-results__icon" aria-hidden="true">🔍</p>
        <h3 class="lnd-no-results__h3"><?= htmlspecialchars($t['no_results_h2'] ?? 'Sin resultados') ?></h3>
        <p class="lnd-no-results__p">
            <?= htmlspecialchars(str_replace('{PROVINCE}', $province, $t['no_results_p'] ?? '')) ?>
        </p>
    </div>

    <?php else: ?>

    <ul class="lnd-grid" role="list">
        <?php foreach ($items as $i => $aloj):
            $isFirst   = ($i === 0);
            $imgLoad   = $isFirst ? 'eager' : 'lazy';
            $imgDecode = $isFirst ? 'sync'  : 'async';
            
            // Usamos la propiedad exacta de la imagen que tu head precarga con éxito
            $photoUrl  = htmlspecialchars($aloj['photo_url'] ?? '');
            $alojUrl   = htmlspecialchars($aloj['url'] ?? '#');
            $name      = htmlspecialchars($aloj['name'] ?? $aloj['titulo'] ?? '');
            $munic     = htmlspecialchars($aloj['municipality'] ?? $aloj['municipio'] ?? '');
            $precio    = $aloj['precio_display'] ?? $aloj['precio_min'] ?? null;
        ?>
        <li class="lnd-card" itemscope itemtype="https://schema.org/Accommodation">

            <a href="<?= $alojUrl ?>" class="lnd-card__img-wrap" tabindex="-1" aria-hidden="true">
                <img
                    src="<?= $photoUrl ?>"
                    alt="<?= $name ?><?= $munic ? ' en ' . $munic : '' ?>"
                    width="600" height="400"
                    loading="<?= $imgLoad ?>"
                    decoding="<?= $imgDecode ?>"
                    class="lnd-card__img"
                    itemprop="image"
                    onerror="this.src='https://images.unsplash.com/photo-1546548970-71785318a17b?w=600&h=400&fit=crop&auto=format&q=60'"
                >
                <?php if ($precio): ?>
                <span class="lnd-card__price-badge">
                    <?= htmlspecialchars($t['card_precio'] ?? 'Desde') ?> <?= htmlspecialchars($precio) ?>€
                </span>
                <?php endif; ?>
            </a>

            <div class="lnd-card__body">
                <?php if ($munic): ?>
                <p class="lnd-card__location">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span><?= $munic ?></span>
                </p>
                <?php endif; ?>

                <h3 class="lnd-card__name" itemprop="name">
                    <a href="<?= $alojUrl ?>" itemprop="url"><?= $name ?></a>
                </h3>

                <div class="lnd-card__footer" style="margin-top: 15px;">
                    <a href="<?= $alojUrl ?>" class="lnd-btn lnd-btn--primary lnd-card__cta">
                        <?= htmlspecialchars($t['card_ver'] ?? 'Ver alojamiento') ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </div>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($pages > 1): ?>
    <nav class="lnd-pagination" aria-label="Paginación de resultados">
        <ul class="lnd-pagination__list" role="list">
            <?php if ($page > 1): ?>
            <li>
                <a href="<?= htmlspecialchars($canonical) ?>?p=<?= $page - 1 ?>" class="lnd-page-btn" rel="prev">
                    <?= htmlspecialchars($t['page_prev'] ?? '← Anterior') ?>
                </a>
            </li>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $pages; $p++): ?>
            <li>
                <a href="<?= htmlspecialchars($canonical) ?>?p=<?= $p ?>" class="lnd-page-btn<?= ($p === $page) ? ' lnd-page-btn--active' : '' ?>">
                    <?= $p ?>
                </a>
            </li>
            <?php endfor; ?>
            <?php if ($page < $pages): ?>
            <li>
                <a href="<?= htmlspecialchars($canonical) ?>?p=<?= $page + 1 ?>" class="lnd-page-btn" rel="next">
                    <?= htmlspecialchars($t['page_next'] ?? 'Siguiente →') ?>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <?php endif; ?>
</section>
<?php
}
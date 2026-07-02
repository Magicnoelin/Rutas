<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  LISTING — Grid de Tarjetas de Eventos Culturales
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  - SSR puro (sin JS para el renderizado)
 *  - Lazy loading en todas las imágenes EXCEPTO la primera (LCP)
 *  - Schema.org Event mediante microdata (complementa el JSON-LD)
 *  - Tarjetas con fecha destacada, badge gratuito/precio, municipio
 *
 *  $ctx esperado:
 *    items (array de eventos), total, pages, page (int),
 *    t (traducciones), lang, canonical, h2_listing (string)
 */

function renderEventosLandingListing(array $ctx): void
{
    $items     = $ctx['items']          ?? [];
    $total     = $ctx['total']          ?? 0;
    $pages     = $ctx['pages']          ?? 1;
    $page      = $ctx['page']           ?? 1;
    $t         = $ctx['t']              ?? [];
    $lang      = $ctx['lang']           ?? 'es';
    $canonical = $ctx['canonical']      ?? '';
    $h2        = $ctx['h2_listing']     ?? ($t['h2_listing'] ?? 'Eventos disponibles');
    $province  = $ctx['province_label'] ?? '';
    $prov_key  = $ctx['province_key']   ?? '';
    $pdo       = $ctx['pdo']            ?? null;
    $stats     = $ctx['stats']          ?? [];
    $towns     = (int)($stats['towns']  ?? 0);

    $base_url = 'https://rutasrurales.io';

    // Nombres de meses por idioma para la fecha visual
    $meses = [
        'es' => ['','ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'],
        'en' => ['','JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'],
        'fr' => ['','JAN','FÉV','MAR','AVR','MAI','JUN','JUL','AOÛ','SEP','OCT','NOV','DÉC'],
        'de' => ['','JAN','FEB','MÄR','APR','MAI','JUN','JUL','AUG','SEP','OKT','NOV','DEZ'],
        'zh' => ['','1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'],
    ];
    $mesLabels = $meses[$lang] ?? $meses['es'];
?>
<!-- ════════════════════════════════════════════════════ LISTING ══ -->
<section class="lnd-listing" id="eventos" aria-labelledby="lnd-listing-title">

    <div class="lnd-listing__header">
        <h2 class="lnd-listing__title" id="lnd-listing-title">
            <?= htmlspecialchars($h2) ?>
        </h2>
        <?php if ($total > 0): ?>
        <p class="lnd-listing__count">
            <?= $total ?> <?= htmlspecialchars($t['stat_count'] ?? 'eventos') ?>
            <?php if ($pages > 1): ?>
            &nbsp;·&nbsp; <?= $page ?> <?= $t['page_of'] ?? 'de' ?> <?= $pages ?>
            <?php endif; ?>
        </p>
        <?php endif; ?>
    </div>

    <!-- ── CTA: ¿Tu municipio también tiene agenda? ─────────────────── -->
    <?php if (!empty($province)): ?>
    <style>
    .lnd-munic-cta{display:flex;flex-wrap:wrap;align-items:center;gap:12px 20px;
      background:linear-gradient(135deg,#fff8e1 0%,#fffde7 100%);
      border:1px solid #ffe082;border-left:4px solid #F9A825;border-radius:12px;
      padding:14px 20px;margin:0 0 28px;font-size:.88rem}
    .lnd-munic-cta__icon{font-size:1.4rem;flex-shrink:0;line-height:1}
    .lnd-munic-cta__text{flex:1;min-width:180px;color:#555;line-height:1.5;margin:0}
    .lnd-munic-cta__text strong{color:#1a3d1e;font-weight:700}
    .lnd-munic-cta__btn{display:inline-flex;align-items:center;gap:6px;
      background:#2F5233;color:#fff!important;padding:9px 18px;border-radius:8px;
      font-weight:700;font-size:.82rem;white-space:nowrap;
      transition:background .18s ease;text-decoration:none!important}
    .lnd-munic-cta__btn:hover{background:#1a3d1e}
    @media(max-width:600px){.lnd-munic-cta{flex-direction:column;text-align:center}}
    </style>
    <div class="lnd-munic-cta" role="note" aria-label="¿Tu municipio tiene agenda cultural?">
        <span class="lnd-munic-cta__icon" aria-hidden="true">📍</span>
        <p class="lnd-munic-cta__text">
            <?php if ($towns > 1): ?>
                <strong><?= $towns ?> municipios</strong> ya tienen su agenda cultural
                en <?= htmlspecialchars($province) ?>. ¿Falta el tuyo?
                No dejes que tus vecinos se lleven todo el protagonismo.
            <?php elseif ($towns === 1): ?>
                <strong>1 municipio</strong> ya tiene su agenda cultural
                en <?= htmlspecialchars($province) ?>. ¿Por qué no el tuyo también?
            <?php else: ?>
                ¿Tu municipio de <?= htmlspecialchars($province) ?> tiene eventos y
                todavía no aparece aquí? <strong>¡Aún estás a tiempo!</strong>
                Publica vuestra agenda cultural y que os encuentren.
            <?php endif; ?>
        </p>
        <a href="https://rutasrurales.io/ofertas/organismos/organismos.html"
           class="lnd-munic-cta__btn"
           title="Publica la agenda cultural de tu municipio en Rutas Rurales"
           rel="noopener noreferrer">
            ¡Ponlo en el mapa!
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
            </svg>
        </a>
    </div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
    <!-- Sin resultados -->
    <div class="lnd-no-results">
        <p class="lnd-no-results__icon" aria-hidden="true">🔍</p>
        <h3 class="lnd-no-results__h3"><?= htmlspecialchars($t['no_results_h2'] ?? 'Sin resultados') ?></h3>
        <p class="lnd-no-results__p">
            <?= htmlspecialchars(str_replace('{PROVINCE}', $province, $t['no_results_p'] ?? '')) ?>
        </p>
        <?php if (!empty($prov_key)): ?>
        <a href="<?= $base_url . ($lang !== 'es' ? "/$lang" : '') ?>/eventos/<?= htmlspecialchars($prov_key) ?>"
           class="lnd-btn lnd-btn--secondary">
            <?= htmlspecialchars(str_replace('{PROVINCE}', $province, $t['no_results_cta'] ?? 'Ver todos los eventos')) ?>
        </a>
        <?php endif; ?>
    </div>

    <?php else: ?>

    <!-- Grid de tarjetas de eventos -->
    <ul class="lnd-grid lnd-grid--eventos" role="list">
        <?php foreach ($items as $i => $ev):
            $isFirst   = ($i === 0);
            $imgLoad   = $isFirst ? 'eager' : 'lazy';
            $imgDecode = $isFirst ? 'sync'  : 'async';
            $photoUrl  = htmlspecialchars($ev['photo_url'] ?? '');
            $evUrl     = htmlspecialchars($ev['url'] ?? '#');
            $name      = htmlspecialchars($ev['name'] ?? '');
            $munic     = htmlspecialchars($ev['municipality'] ?? '');
            $venue     = htmlspecialchars($ev['venue_name']   ?? '');
            $isFree    = !empty($ev['is_free']) && $ev['is_free'];
            $precio    = $ev['precio_display'] ?? null;
            $desc      = mb_substr(strip_tags($ev['short_description'] ?? $ev['description'] ?? ''), 0, 100);
            $desc_html = (!empty($desc) && $pdo !== null)
                ? procesarInboundLinks(htmlspecialchars($desc), $pdo)
                : htmlspecialchars($desc);

            // Fecha inicio formateada
            $fechaStart  = '';
            $diaStart    = '';
            $mesStart    = '';
            $dateIso     = '';
            if (!empty($ev['start_date'])) {
                $dt = DateTime::createFromFormat('Y-m-d', $ev['start_date']);
                if ($dt) {
                    $diaStart = $dt->format('d');
                    $mesStart = $mesLabels[(int)$dt->format('n')] ?? '';
                    $fechaStart = $dt->format($lang === 'en' ? 'd M Y' : 'd/m/Y');
                    $dateIso  = $ev['start_date'];
                }
            }

            // Fecha fin (si existe y es diferente)
            $fechaEnd = '';
            $dateIsoEnd = '';
            if (!empty($ev['fecha_fin'])) {
                $dtEnd = DateTime::createFromFormat('Y-m-d', $ev['fecha_fin']);
                if ($dtEnd) {
                    $fechaEnd   = $dtEnd->format($lang === 'en' ? 'd M Y' : 'd/m/Y');
                    $dateIsoEnd = $ev['fecha_fin'];
                }
            }
        ?>
        <li class="lnd-card lnd-card--evento" itemscope itemtype="https://schema.org/Event">

            <!-- Bloque de fecha visual (calendario) -->
            <?php if ($diaStart): ?>
            <div class="lnd-card__date-badge" aria-hidden="true">
                <span class="lnd-card__date-dia"><?= $diaStart ?></span>
                <span class="lnd-card__date-mes"><?= $mesStart ?></span>
            </div>
            <?php endif; ?>

            <!-- Foto del evento -->
            <a href="<?= $evUrl ?>" class="lnd-card__img-wrap" tabindex="-1" aria-hidden="true">
                <img
                    src="<?= $photoUrl ?>"
                    alt="<?= $name ?><?= $munic ? ' en ' . $munic : '' ?>"
                    width="600" height="400"
                    loading="<?= $imgLoad ?>"
                    decoding="<?= $imgDecode ?>"
                    class="lnd-card__img"
                    itemprop="image"
                    onerror="this.src='https://images.unsplash.com/photo-1514320291840-2e0a9bf2a9ae?w=600&h=400&fit=crop&auto=format&q=60'"
                >
                <!-- Badge gratuito o precio -->
                <span class="lnd-card__price-badge <?= $isFree ? 'lnd-card__price-badge--free' : '' ?>">
                    <?php if ($isFree): ?>
                        <?= htmlspecialchars($t['card_gratis'] ?? 'Entrada gratuita') ?>
                    <?php elseif ($precio): ?>
                        <?= htmlspecialchars($t['card_precio'] ?? 'Desde') ?> <?= htmlspecialchars($precio) ?>
                    <?php endif; ?>
                </span>
            </a>

            <!-- Contenido textual -->
            <div class="lnd-card__body">

                <!-- Municipio / Venue -->
                <?php if ($munic || $venue): ?>
                <p class="lnd-card__location">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span itemprop="location" itemscope itemtype="https://schema.org/Place">
                        <?php if ($venue): ?>
                        <span itemprop="name"><?= $venue ?></span><?php if ($munic): ?> · <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($munic): ?>
                        <span itemprop="address"><?= $munic ?></span>
                        <?php endif; ?>
                    </span>
                </p>
                <?php endif; ?>

                <!-- Nombre del evento — H3 semántico -->
                <h3 class="lnd-card__name" itemprop="name">
                    <a href="<?= $evUrl ?>" itemprop="url"><?= $name ?></a>
                </h3>

                <!-- Fecha(s) -->
                <?php if ($fechaStart): ?>
                <div class="lnd-card__dates">
                    <time class="lnd-card__date-text" datetime="<?= htmlspecialchars($dateIso) ?>" itemprop="startDate">
                        📅 <?= htmlspecialchars($fechaStart) ?>
                    </time>
                    <?php if ($fechaEnd): ?>
                    <time class="lnd-card__date-text lnd-card__date-text--end" datetime="<?= htmlspecialchars($dateIsoEnd) ?>" itemprop="endDate">
                        → <?= htmlspecialchars($fechaEnd) ?>
                    </time>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Descripción corta -->
                <?php if ($desc): ?>
                <p class="lnd-card__desc" itemprop="description">
                    <?= $desc_html ?>…
                </p>
                <?php endif; ?>

                <!-- Metadatos Schema.org requeridos (ocultos) -->
                <?php
                    $organizerName = htmlspecialchars(!empty($ev['organizer']) ? $ev['organizer'] : 'Rutas Rurales');
                    $ticketPrice   = isset($ev['ticket_price']) && $ev['ticket_price'] > 0 ? $ev['ticket_price'] : null;
                ?>
                <meta itemprop="isAccessibleForFree" content="<?= $isFree ? 'true' : 'false' ?>">
                <!-- organizer -->
                <span itemprop="organizer" itemscope itemtype="https://schema.org/Organization" hidden>
                    <meta itemprop="name" content="<?= $organizerName ?>">
                    <meta itemprop="url" content="<?= $evUrl ?>">
                </span>
                <!-- performer -->
                <span itemprop="performer" itemscope itemtype="https://schema.org/Organization" hidden>
                    <meta itemprop="name" content="<?= $organizerName ?>">
                    <meta itemprop="url" content="<?= $evUrl ?>">
                </span>
                <!-- offers -->
                <span itemprop="offers" itemscope itemtype="https://schema.org/Offer" hidden>
                    <?php if ($isFree): ?>
                    <meta itemprop="price" content="0">
                    <meta itemprop="priceCurrency" content="EUR">
                    <?php elseif ($ticketPrice): ?>
                    <meta itemprop="price" content="<?= number_format((float)$ticketPrice, 2, '.', '') ?>">
                    <meta itemprop="priceCurrency" content="EUR">
                    <?php endif; ?>
                    <meta itemprop="availability" content="https://schema.org/InStock">
                    <meta itemprop="url" content="<?= $evUrl ?>">
                </span>

                <!-- Footer: CTA -->
                <div class="lnd-card__footer">
                    <a href="<?= $evUrl ?>" class="lnd-btn lnd-btn--primary lnd-card__cta">
                        <?= htmlspecialchars($t['card_ver'] ?? 'Ver evento') ?>
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

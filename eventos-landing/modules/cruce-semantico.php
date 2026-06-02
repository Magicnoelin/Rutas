<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  CRUCE SEMÁNTICO — Landing de Eventos Culturales
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Muestra en la landing de eventos:
 *   1. Alojamientos rurales de la provincia (cruce INVERSO al de alojamientos)
 *   2. Lugares de interés de la provincia
 *   3. Rutas temáticas de la provincia
 *
 *  Valor SEO: señala a Google que EVENTOS y ALOJAMIENTOS son verticales
 *  distintas pero complementarias geográficamente.
 *  Valor usuario: contexto completo del destino en una sola página.
 *
 *  $ctx esperado:
 *    t, lang, province_label, accommodations, places, routes
 */

function renderEventosCruceSemantico(array $ctx): void
{
    $t              = $ctx['t']              ?? [];
    $lang           = $ctx['lang']           ?? 'es';
    $province       = $ctx['province_label'] ?? '';
    $accommodations = $ctx['accommodations'] ?? [];
    $places         = $ctx['places']         ?? [];
    $routes         = $ctx['routes']         ?? [];

    // Si no hay nada que mostrar, salir silenciosamente
    if (empty($accommodations) && empty($places) && empty($routes)) return;

    $base_url    = 'https://rutasrurales.io';
    $lang_prefix = $lang !== 'es' ? "/$lang" : '';
?>
<!-- ════════════════════════════════════════ CRUCE SEMÁNTICO ══ -->
<section class="lnd-semantico" aria-labelledby="semantico-title">

    <div class="lnd-semantico__intro">
        <p class="lnd-semantico__claim">
            <?= htmlspecialchars(str_replace('{PROVINCE}', $province, $t['semantic_intro'] ?? '')) ?>
        </p>
    </div>

    <h2 class="lnd-semantico__h2" id="semantico-title">
        <?= htmlspecialchars(str_replace('{PROVINCE}', $province, $t['h2_semantico'] ?? "Alojamientos y lugares cerca de los eventos en $province")) ?>
    </h2>

    <!-- ── ALOJAMIENTOS RURALES ────────────────────────────────────────────── -->
    <?php if (!empty($accommodations)): ?>
    <article class="lnd-semantico__block" aria-labelledby="sem-stays-title">

        <h3 class="lnd-semantico__h3" id="sem-stays-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <?= htmlspecialchars(str_replace('{PROVINCE}', $province, $t['semantic_stays'] ?? "Alojamientos rurales en $province")) ?>
        </h3>

        <ul class="lnd-sem-grid" role="list">
            <?php foreach ($accommodations as $alo):
                $imgUrl   = htmlspecialchars($alo['photo_url'] ?? '');
                $aloUrl   = htmlspecialchars($alo['url'] ?? '#');
                $aloName  = htmlspecialchars($alo['name'] ?? '');
                $aloMunic = htmlspecialchars($alo['municipality'] ?? '');
                $aloCat   = htmlspecialchars($alo['category_name'] ?? $alo['accommodation_type'] ?? '');
                $aloDesc  = mb_substr(strip_tags($alo['short_description'] ?? ''), 0, 80);
                $aloPrecio= $alo['precio_display'] ?? null;
            ?>
            <li class="lnd-sem-card" itemscope itemtype="https://schema.org/LodgingBusiness">
                <a href="<?= $aloUrl ?>" class="lnd-sem-card__link">
                    <?php if ($imgUrl): ?>
                    <div class="lnd-sem-card__img-wrap">
                        <img
                            src="<?= $imgUrl ?>"
                            alt="<?= $aloName ?>"
                            width="320" height="200"
                            loading="lazy"
                            decoding="async"
                            class="lnd-sem-card__img"
                            itemprop="image"
                            onerror="this.closest('.lnd-sem-card__img-wrap').style.display='none'"
                        >
                    </div>
                    <?php endif; ?>
                    <div class="lnd-sem-card__body">
                        <?php if ($aloCat): ?>
                        <span class="lnd-sem-card__type"><?= $aloCat ?></span>
                        <?php endif; ?>
                        <h4 class="lnd-sem-card__name" itemprop="name"><?= $aloName ?></h4>
                        <?php if ($aloMunic): ?>
                        <p class="lnd-sem-card__loc">
                            📍 <span itemprop="address"><?= $aloMunic ?></span>
                        </p>
                        <?php endif; ?>
                        <?php if ($aloDesc): ?>
                        <p class="lnd-sem-card__desc" itemprop="description"><?= htmlspecialchars($aloDesc) ?>…</p>
                        <?php endif; ?>
                        <?php if ($aloPrecio): ?>
                        <span class="lnd-sem-card__fee" itemprop="priceRange">
                            <?= htmlspecialchars($t['card_precio'] ?? 'Desde') ?>
                            <strong><?= htmlspecialchars($aloPrecio) ?></strong>
                            <small><?= htmlspecialchars($t['price_per_night'] ?? '/noche') ?></small>
                        </span>
                        <?php else: ?>
                        <span class="lnd-sem-card__fee lnd-sem-card__fee--free">
                            <?= htmlspecialchars($lang === 'es' ? 'Consultar precio' : ($lang === 'en' ? 'Price on request' : ($lang === 'fr' ? 'Prix sur demande' : ($lang === 'de' ? 'Preis auf Anfrage' : '询价')))) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <!-- CTA ver más alojamientos -->
        <?php if (!empty($province)): ?>
        <div class="lnd-semantico__cta-wrap">
            <a href="<?= $base_url . $lang_prefix ?>/alojamientos/turismo-rural-<?= urlencode(strtolower(str_replace(' ', '-', $province))) ?>"
               class="lnd-btn lnd-btn--outline">
                <?= htmlspecialchars(str_replace('{PROVINCE}', $province, $t['semantic_cta_alo'] ?? 'Ver todos los alojamientos')) ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </a>
        </div>
        <?php endif; ?>
    </article>
    <?php endif; ?>

    <!-- ── LUGARES DE INTERÉS ──────────────────────────────────────────────── -->
    <?php if (!empty($places)): ?>
    <article class="lnd-semantico__block" aria-labelledby="sem-places-title">

        <h3 class="lnd-semantico__h3" id="sem-places-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
            <?= htmlspecialchars(str_replace('{PROVINCE}', $province, $t['semantic_places'] ?? "Qué visitar en $province")) ?>
        </h3>

        <ul class="lnd-sem-grid" role="list">
            <?php foreach ($places as $place):
                $imgUrl  = htmlspecialchars($place['photo_url'] ?? '');
                $pUrl    = htmlspecialchars($place['url'] ?? '#');
                $pName   = htmlspecialchars($place['name'] ?? '');
                $pMunic  = htmlspecialchars($place['municipality'] ?? '');
                $pDesc   = mb_substr(strip_tags($place['short_description'] ?? $place['description'] ?? ''), 0, 80);
                $free    = ($place['entry_display'] === null);
            ?>
            <li class="lnd-sem-card" itemscope itemtype="https://schema.org/TouristAttraction">
                <a href="<?= $pUrl ?>" class="lnd-sem-card__link">
                    <?php if ($imgUrl): ?>
                    <div class="lnd-sem-card__img-wrap">
                        <img
                            src="<?= $imgUrl ?>"
                            alt="<?= $pName ?>"
                            width="320" height="200"
                            loading="lazy"
                            decoding="async"
                            class="lnd-sem-card__img"
                            itemprop="image"
                            onerror="this.closest('.lnd-sem-card__img-wrap').style.display='none'"
                        >
                    </div>
                    <?php endif; ?>
                    <div class="lnd-sem-card__body">
                        <h4 class="lnd-sem-card__name" itemprop="name"><?= $pName ?></h4>
                        <?php if ($pMunic): ?>
                        <p class="lnd-sem-card__loc">
                            📍 <span itemprop="address"><?= $pMunic ?></span>
                        </p>
                        <?php endif; ?>
                        <?php if ($pDesc): ?>
                        <p class="lnd-sem-card__desc" itemprop="description"><?= htmlspecialchars($pDesc) ?>…</p>
                        <?php endif; ?>
                        <span class="lnd-sem-card__fee <?= $free ? 'lnd-sem-card__fee--free' : '' ?>">
                            <?= $free
                                ? htmlspecialchars($t['entry_fee_free'] ?? 'Entrada gratuita')
                                : htmlspecialchars($place['entry_display'])
                            ?>
                        </span>
                    </div>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <!-- CTA ver más lugares -->
        <?php if (!empty($province)): ?>
        <div class="lnd-semantico__cta-wrap">
            <a href="<?= $base_url . $lang_prefix ?>/lugares-de-interes?provincia=<?= urlencode($province) ?>"
               class="lnd-btn lnd-btn--outline">
                <?= htmlspecialchars($t['semantic_cta_poi'] ?? 'Ver todos los lugares de interés') ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </a>
        </div>
        <?php endif; ?>
    </article>
    <?php endif; ?>

    <!-- ── RUTAS TEMÁTICAS ─────────────────────────────────────────────────── -->
    <?php if (!empty($routes)): ?>
    <article class="lnd-semantico__block" aria-labelledby="sem-routes-title">

        <h3 class="lnd-semantico__h3" id="sem-routes-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            <?= htmlspecialchars(str_replace('{PROVINCE}', $province, $t['h2_rutas'] ?? "Rutas en $province")) ?>
        </h3>

        <ul class="lnd-routes-list" role="list">
            <?php foreach ($routes as $route):
                $rUrl  = htmlspecialchars($route['url'] ?? '#');
                $rName = htmlspecialchars($route['name'] ?? '');
                $rDays = (int)($route['duration_days'] ?? 0);
                $rDiff = htmlspecialchars($route['difficulty_level'] ?? '');
                $rImg  = !empty($route['hero_image'])
                    ? htmlspecialchars($route['hero_image'])
                    : 'https://images.unsplash.com/photo-1528360983277-13d401cdc186?w=320&h=200&fit=crop&auto=format&q=60';
            ?>
            <li class="lnd-route-card" itemscope itemtype="https://schema.org/Trip">
                <a href="<?= $rUrl ?>" class="lnd-route-card__link">
                    <div class="lnd-route-card__img-wrap">
                        <img
                            src="<?= $rImg ?>"
                            alt="<?= $rName ?>"
                            width="320" height="200"
                            loading="lazy"
                            decoding="async"
                            class="lnd-route-card__img"
                        >
                    </div>
                    <div class="lnd-route-card__body">
                        <h4 class="lnd-route-card__name" itemprop="name"><?= $rName ?></h4>
                        <div class="lnd-route-card__meta">
                            <?php if ($rDays > 0): ?>
                            <span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <?= $rDays ?> <?= $lang === 'es' ? ($rDays === 1 ? 'día' : 'días') : ($rDays === 1 ? 'day' : 'days') ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($rDiff): ?>
                            <span class="lnd-route-diff lnd-route-diff--<?= strtolower($rDiff) ?>">
                                <?= $rDiff ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="lnd-semantico__cta-wrap">
            <a href="<?= $base_url . $lang_prefix ?>/rutas/"
               class="lnd-btn lnd-btn--outline">
                <?= htmlspecialchars($t['semantic_cta_rt'] ?? 'Ver todas las rutas') ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </a>
        </div>
    </article>
    <?php endif; ?>

</section>
<!-- ══════════════════════════════════════ /CRUCE SEMÁNTICO ══ -->
<?php
}

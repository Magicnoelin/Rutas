<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  CRUCE SEMÁNTICO — El módulo que Booking nunca tendrá
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Muestra en la landing de alojamientos:
 *   1. Lugares de interés de la provincia (places_of_interest)
 *   2. Rutas temáticas de la provincia (routes)
 *   3. Próximos eventos culturales (cultural_events)
 *
 *  Valor SEO: añade entidades relacionadas → señales de autoridad temática.
 *  Valor usuario: contexto completo del destino en una sola página.
 *
 *  $ctx esperado:
 *    t, lang, province_label, places, routes, events
 */

function renderCruceSemantico(array $ctx): void
{
    $t        = $ctx['t']              ?? [];
    $lang     = $ctx['lang']           ?? 'es';
    $province = $ctx['province_label'] ?? '';
    $places   = $ctx['places']         ?? [];
    $routes   = $ctx['routes']         ?? [];
    $events   = $ctx['events']         ?? [];

    // Si no hay nada que mostrar, salir silenciosamente
    if (empty($places) && empty($routes) && empty($events)) return;

    $base_url = 'https://rutasrurales.io';
    $lang_prefix = $lang !== 'es' ? "/$lang" : '';
?>
<!-- ════════════════════════════════════════ CRUCE SEMÁNTICO ══ -->
<section class="lnd-semantico" aria-labelledby="semantico-title">

    <div class="lnd-semantico__intro">
        <p class="lnd-semantico__claim">
            <?= htmlspecialchars(str_replace('{PROVINCE}', $province, $t['semantic_intro'] ?? '')) ?>
        </p>
    </div>

    <!-- H2 -->
    <h2 class="lnd-semantico__h2" id="semantico-title">
        <?= htmlspecialchars(str_replace('{PROVINCE}', $province, $t['h2_semantico'] ?? "Qué visitar en $province")) ?>
    </h2>

    <!-- ── LUGARES DE INTERÉS ──────────────────────────────────────────── -->
    <?php if (!empty($places)): ?>
    <article class="lnd-semantico__block" aria-labelledby="sem-places-title">

        <h3 class="lnd-semantico__h3" id="sem-places-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <?= htmlspecialchars($t['semantic_places'] ?? 'Monumentos y lugares de interés') ?>
        </h3>

        <ul class="lnd-sem-grid" role="list">
            <?php foreach ($places as $i => $place):
                $imgUrl = htmlspecialchars($place['photo_url'] ?? '');
                $pUrl   = htmlspecialchars($place['url'] ?? '#');
                $pName  = htmlspecialchars($place['name'] ?? '');
                $pDesc  = mb_substr(strip_tags($place['short_description'] ?? $place['description'] ?? ''), 0, 90);
                $free   = ($place['entry_display'] === null);
            ?>
            <li class="lnd-sem-card" itemscope itemtype="https://schema.org/TouristAttraction">
                <a href="<?= $pUrl ?>" class="lnd-sem-card__link">
                    <?php if (!empty($imgUrl)): ?>
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
                        <?php if (!empty($place['municipality'])): ?>
                        <p class="lnd-sem-card__loc">
                            📍 <span itemprop="address"><?= htmlspecialchars($place['municipality']) ?></span>
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
                <?= htmlspecialchars($t['semantic_cta'] ?? 'Ver todos los lugares de interés') ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </a>
        </div>
        <?php endif; ?>
    </article>
    <?php endif; ?>

    <!-- ── RUTAS TEMÁTICAS ─────────────────────────────────────────────── -->
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

    <!-- ── EVENTOS CULTURALES ──────────────────────────────────────────── -->
    <?php if (!empty($events)): ?>
    <article class="lnd-semantico__block" aria-labelledby="sem-events-title">

        <h3 class="lnd-semantico__h3" id="sem-events-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <?= htmlspecialchars(str_replace('{PROVINCE}', $province, $t['semantic_events'] ?? "Eventos en $province")) ?>
        </h3>

        <ul class="lnd-events-list" role="list">
            <?php foreach ($events as $ev):
                $evUrl   = htmlspecialchars($ev['url'] ?? '#');
                $evTitle = htmlspecialchars($ev['title'] ?? '');
                $evMunic = htmlspecialchars($ev['municipality'] ?? '');
                $evDate  = '';
                $evEndDate = '';
                if (!empty($ev['start_date'])) {
                    $dt = DateTime::createFromFormat('Y-m-d', $ev['start_date']);
                    if ($dt) {
                        $evDate = $dt->format($lang === 'en' ? 'd M Y' : 'd/m/Y');
                    }
                }
                if (!empty($ev['end_date']) && $ev['end_date'] !== $ev['start_date']) {
                    $dtEnd = DateTime::createFromFormat('Y-m-d', $ev['end_date']);
                    if ($dtEnd) {
                        $evEndDate = $dtEnd->format($lang === 'en' ? 'd M Y' : 'd/m/Y');
                    }
                }
                $evFree = empty($ev['precio_display']);
            ?>
            <li class="lnd-event-card" itemscope itemtype="https://schema.org/Event">
                <a href="<?= $evUrl ?>" class="lnd-event-card__link">
                    <?php if (!empty($ev['photo_url'])): ?>
                    <div class="lnd-event-card__img-wrap">
                        <img
                            src="<?= htmlspecialchars($ev['photo_url']) ?>"
                            alt="<?= $evTitle ?>"
                            width="200" height="150"
                            loading="lazy"
                            decoding="async"
                            class="lnd-event-card__img"
                        >
                    </div>
                    <?php endif; ?>
                    <div class="lnd-event-card__body">
                        <h4 class="lnd-event-card__name" itemprop="name"><?= $evTitle ?></h4>
                        <div class="lnd-event-card__meta">
                            <?php if ($evDate): ?>
                            <time class="lnd-event-card__date"
                                  datetime="<?= htmlspecialchars($ev['start_date'] ?? '') ?>"
                                  itemprop="startDate">
                                📅 <?= $evDate ?>
                            </time>
                            <?php endif; ?>
                            <?php if ($evEndDate): ?>
                            <time class="lnd-event-card__date lnd-event-card__date--end"
                                  datetime="<?= htmlspecialchars($ev['end_date'] ?? '') ?>"
                                  itemprop="endDate">
                                ➡ <?= $evEndDate ?>
                            </time>
                            <?php endif; ?>
                            <?php if ($evMunic): ?>
                            <span class="lnd-event-card__loc" itemprop="location">📍 <?= $evMunic ?></span>
                            <?php endif; ?>
                            <?php if ($evFree): ?>
                            <span class="lnd-event-card__free"><?= htmlspecialchars($t['entry_fee_free'] ?? 'Gratuito') ?></span>
                            <?php else: ?>
                            <span class="lnd-event-card__price"><?= htmlspecialchars($ev['precio_display']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </article>
    <?php endif; ?>

</section>
<!-- ══════════════════════════════════════ /CRUCE SEMÁNTICO ══ -->
<?php
}

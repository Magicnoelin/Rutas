<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  HUB-EVENTOS.PHP — Módulo de enlazado interno para Eventos Culturales
 *
 *  Arquitectura SEO:
 *    - Grid estacional dinámico (detecta la temporada actual)
 *    - <details>/<summary> semánticos para acordeones
 *    - Sección 1: Temporada actual destacada (visual con cards)
 *    - Sección 2: Por categoría (música, gastronomía, tradiciones...)
 *    - Sección 3: Por provincia (agenda completa de cada provincia)
 *    - Sección 4: Combinaciones estrella (categoría×provincia)
 * ════════════════════════════════════════════════════════════════════════════
 *
 * @param array $ctx  [lang, t, temporada]
 */
function renderHubEventos(array $ctx): void {
    $lang      = $ctx['lang'];
    $t         = $ctx['t'];
    $temporada = $ctx['temporada'] ?? getTemporadaActual();
    $base      = 'https://rutasrurales.io';

    // Filtros de la temporada actual para mostrar en el bloque destacado
    $filtroTemp = match($temporada) {
        'primavera' => 'primavera',
        'verano'    => 'verano',
        'otono'     => 'otono',
        'invierno'  => 'invierno',
        default     => 'otono',
    };

    // Label de la temporada actual en el idioma activo
    $seasonLabels = $t['hub_evt_season_label'] ?? [];
    $seasonLabel  = $seasonLabels[$temporada] ?? ucfirst($temporada);

    // Provincias destacadas para el bloque estacional (las 6 más activas)
    $provDestacadas = ['soria', 'zamora', 'burgos', 'salamanca', 'valladolid', 'leon',
                       'palencia', 'segovia', 'avila'];
    ?>
<!-- ══════════════════════════════════ HUB EVENTOS ═══════════════════════════ -->
<section class="hub-section hub-section--events hub-section--alt" id="eventos" aria-labelledby="hub-evt-heading">
<div class="hub-container">

    <header class="hub-section__header">
        <h2 class="hub-section__h2" id="hub-evt-heading">
            <span class="hub-section__icon" aria-hidden="true">🎭</span>
            <?= htmlspecialchars($t['hub_evt_h2']) ?>
        </h2>
        <p class="hub-section__intro"><?= htmlspecialchars($t['hub_evt_intro']) ?></p>
    </header>

    <!-- ── BLOQUE TEMPORADA ACTUAL (siempre visible, visual destacado) ───────── -->
    <div class="hub-season-block">
        <h3 class="hub-season-block__title">
            <?= htmlspecialchars($seasonLabel) ?>
        </h3>
        <ul class="hub-season-grid" role="list" aria-label="Eventos de temporada por provincia">
            <?php foreach ($provDestacadas as $provKey):
                if (!isset(HUB_PROVINCIAS[$provKey])) continue;
                $provData = HUB_PROVINCIAS[$provKey];
                $slug = $filtroTemp . '-' . $provKey;
                $url  = hubUrl($slug, $lang, 'eventos');
                $labelEvt = HUB_FILTROS_EVT[$filtroTemp][$lang] ?? HUB_FILTROS_EVT[$filtroTemp]['es'];
            ?>
            <li class="hub-season-item">
                <a href="<?= htmlspecialchars($url) ?>"
                   class="hub-season-card"
                   title="<?= htmlspecialchars($labelEvt . ' en ' . $provData['label']) ?>">
                    <span class="hub-season-card__emoji" aria-hidden="true"><?= $provData['emoji'] ?></span>
                    <span class="hub-season-card__prov"><?= htmlspecialchars($provData['label']) ?></span>
                    <span class="hub-season-card__cat"><?= htmlspecialchars($labelEvt) ?></span>
                </a>
                <a href="<?= $base ?>/rutas.php?provincia=<?= urlencode($provData['label']) ?>&alojamientos=0&lugares=0&actividades=0&eventos=1"
                   class="hub-prov-map-btn"
                   title="Ver eventos en <?= htmlspecialchars($provData['label']) ?> en el mapa"
                   aria-label="Mapa de eventos en <?= htmlspecialchars($provData['label']) ?>">
                    🗺️
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- ── ACORDEÓN 1: Por categoría ─────────────────────────────────────────── -->
    <details class="hub-accordion">
        <summary class="hub-accordion__summary">
            <span class="hub-accordion__icon" aria-hidden="true">🗂️</span>
            <?= htmlspecialchars($t['hub_evt_by_cat']) ?>
            <svg class="hub-accordion__chevron" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </summary>
        <div class="hub-accordion__body">
            <ul class="hub-chip-grid" role="list" aria-label="Categorías de eventos culturales">
                <?php foreach (HUB_FILTROS_EVT as $filtroKey => $filtroData):
                    // Enlace solo-filtro (sin provincia) → todos los eventos de España
                    $slug  = $filtroKey;
                    $url   = hubUrl($slug, $lang, 'eventos');
                    $label = $filtroData[$lang] ?? $filtroData['es'];
                ?>
                <li>
                    <a href="<?= htmlspecialchars($url) ?>"
                       class="hub-chip hub-chip--events"
                       title="<?= htmlspecialchars($label . ' en España') ?>">
                        <span aria-hidden="true"><?= $filtroData['icon'] ?></span>
                        <?= htmlspecialchars($label) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </details>

    <!-- ── ACORDEÓN 2: Por provincia (agenda completa) ────────────────────────── -->
    <details class="hub-accordion">
        <summary class="hub-accordion__summary">
            <span class="hub-accordion__icon" aria-hidden="true">📍</span>
            <?= htmlspecialchars($t['hub_evt_by_prov']) ?>
            <svg class="hub-accordion__chevron" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </summary>
        <div class="hub-accordion__body">
            <ul class="hub-prov-grid" role="list" aria-label="Provincias con agenda cultural">
                <?php foreach (HUB_PROVINCIAS as $provKey => $provData):
                    // Slug: solo la provincia → agenda completa de esa provincia
                    $url = hubUrl($provKey, $lang, 'eventos');
                ?>
                <li>
                    <a href="<?= htmlspecialchars($url) ?>"
                       class="hub-prov-card hub-prov-card--events"
                       title="<?= htmlspecialchars('Agenda cultural en ' . $provData['label']) ?>">
                        <span class="hub-prov-card__emoji" aria-hidden="true"><?= $provData['emoji'] ?></span>
                        <span class="hub-prov-card__label"><?= htmlspecialchars($provData['label']) ?></span>
                        <span class="hub-prov-card__region"><?= htmlspecialchars($provData['region']) ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </details>

    <!-- ── ACORDEÓN 3: Combinaciones estrella (categoría×provincia) ───────────── -->
    <details class="hub-accordion">
        <summary class="hub-accordion__summary">
            <span class="hub-accordion__icon" aria-hidden="true">⭐</span>
            <?= htmlspecialchars($t['hub_alo_combis'] ?? 'Combinaciones destacadas') ?>
            <svg class="hub-accordion__chevron" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </summary>
        <div class="hub-accordion__body">
            <ul class="hub-link-grid" role="list" aria-label="Combinaciones destacadas de eventos">
                <?php foreach (HUB_COMBIS_EVT as $combi):
                    [$filtroKey, $provKey] = $combi;
                    if (!isset(HUB_FILTROS_EVT[$filtroKey], HUB_PROVINCIAS[$provKey])) continue;
                    $slug  = $filtroKey . '-' . $provKey;
                    $url   = hubUrl($slug, $lang, 'eventos');
                    $fLabel = HUB_FILTROS_EVT[$filtroKey][$lang] ?? HUB_FILTROS_EVT[$filtroKey]['es'];
                    $pLabel = HUB_PROVINCIAS[$provKey]['label'];
                    $label  = $fLabel . ' · ' . $pLabel;
                    $icon   = HUB_FILTROS_EVT[$filtroKey]['icon'];
                ?>
                <li>
                    <a href="<?= htmlspecialchars($url) ?>"
                       class="hub-link-item hub-link-item--events"
                       title="<?= htmlspecialchars($label) ?>">
                        <span class="hub-link-item__icon" aria-hidden="true"><?= $icon ?></span>
                        <?= htmlspecialchars($label) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </details>

    <!-- ── CTA Ver agenda completa + Mapa ────────────────────────────────────── -->
    <div class="hub-section__cta">
        <a href="<?= $base ?>/eventos-culturales-paginacion.html"
           class="hub-btn hub-btn--outline hub-btn--events">
            <?= htmlspecialchars($t['hub_evt_all']) ?>
        </a>
        <a href="<?= $base ?>/rutas.php?alojamientos=0&lugares=0&actividades=0&eventos=1"
           class="hub-btn hub-btn--map">
            🗺️ <?= $lang === 'es' ? 'Ver en el mapa' : ($lang === 'en' ? 'View on map' : ($lang === 'fr' ? 'Voir sur la carte' : ($lang === 'de' ? 'Auf der Karte' : '在地图上查看'))) ?>
        </a>
    </div>

</div><!-- /.hub-container -->
</section>
    <?php
}

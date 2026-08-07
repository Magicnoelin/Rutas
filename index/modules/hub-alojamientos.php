<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  HUB-ALOJAMIENTOS.PHP — Módulo de enlazado interno para Alojamientos
 *
 *  Arquitectura SEO:
 *    - <details>/<summary> semánticos: visibles al crawler de Google,
 *      colapsados visualmente para no saturar al usuario
 *    - Sección 1: Grid de características (filtros rápidos)
 *    - Sección 2: Grid de provincias (enlace directo a cada provincia)
 *    - Sección 3: Acordeón "Combinaciones más buscadas" (filtro×provincia)
 *    - Total enlaces: ~150 URLs distribuidas de forma orgánica
 * ════════════════════════════════════════════════════════════════════════════
 *
 * @param array $ctx  [lang, t]
 */
function renderHubAlojamientos(array $ctx): void {
    $lang = $ctx['lang'];
    $t    = $ctx['t'];
    $base = 'https://rutasrurales.io';
    ?>
<!-- ══════════════════════════════════ HUB ALOJAMIENTOS ══════════════════════ -->
<section class="hub-section hub-section--stays" id="alojamientos" aria-labelledby="hub-alo-heading">
<div class="hub-container">

    <header class="hub-section__header">
        <h2 class="hub-section__h2" id="hub-alo-heading">
            <span class="hub-section__icon" aria-hidden="true">🏡</span>
            <?= htmlspecialchars($t['hub_alo_h2']) ?>
        </h2>
        <p class="hub-section__intro"><?= htmlspecialchars($t['hub_alo_intro']) ?></p>
    </header>

    <!-- ── ACORDEÓN 1: Por características ──────────────────────────────────── -->
    <details class="hub-accordion" open>
        <summary class="hub-accordion__summary">
            <span class="hub-accordion__icon" aria-hidden="true">✨</span>
            <?= htmlspecialchars($t['hub_alo_by_feat']) ?>
            <svg class="hub-accordion__chevron" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </summary>
        <div class="hub-accordion__body">
            <ul class="hub-chip-grid" role="list" aria-label="Filtros de alojamientos">
                <?php foreach (HUB_FILTROS_ALO as $filtroKey => $filtroData):
                    // Slug: solo el filtro (sin provincia) → landing filtra por toda España
                    $slug = $filtroKey;
                    $url  = hubUrl($slug, $lang, 'alojamientos');
                    $label = $filtroData[$lang] ?? $filtroData['es'];
                ?>
                <li>
                    <a href="<?= htmlspecialchars($url) ?>"
                       class="hub-chip"
                       title="<?= htmlspecialchars($label . ' en España') ?>">
                        <span aria-hidden="true"><?= $filtroData['icon'] ?></span>
                        <?= htmlspecialchars($label) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </details>

    <!-- ── ACORDEÓN 2: Por provincia ─────────────────────────────────────────── -->
    <details class="hub-accordion">
        <summary class="hub-accordion__summary">
            <span class="hub-accordion__icon" aria-hidden="true">📍</span>
            <?= htmlspecialchars($t['hub_alo_by_prov']) ?>
            <svg class="hub-accordion__chevron" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </summary>
        <div class="hub-accordion__body">
            <ul class="hub-prov-grid" role="list" aria-label="Provincias con alojamientos">
                <?php foreach (HUB_PROVINCIAS as $provKey => $provData):
                    // Slug: solo la provincia → todos los alojamientos de esa provincia
                    $slug = 'turismo-rural-' . $provKey;
                    $url  = hubUrl($slug, $lang, 'alojamientos');
                ?>
                <li class="hub-prov-item">
                    <a href="<?= htmlspecialchars($url) ?>"
                       class="hub-prov-card"
                       title="<?= htmlspecialchars('Alojamientos en ' . $provData['label']) ?>">
                        <span class="hub-prov-card__emoji" aria-hidden="true"><?= $provData['emoji'] ?></span>
                        <span class="hub-prov-card__label"><?= htmlspecialchars($provData['label']) ?></span>
                        <span class="hub-prov-card__region"><?= htmlspecialchars($provData['region']) ?></span>
                    </a>
                    <a href="<?= $base ?>/rutas.php?provincia=<?= urlencode($provData['label']) ?>&alojamientos=1&lugares=0&actividades=0&eventos=0"
                       class="hub-prov-map-btn"
                       title="Ver alojamientos en <?= htmlspecialchars($provData['label']) ?> en el mapa"
                       aria-label="Mapa de <?= htmlspecialchars($provData['label']) ?>">
                        🗺️
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </details>

    <!-- ── ACORDEÓN 3: Combinaciones más buscadas (link juice concentrado) ────── -->
    <details class="hub-accordion">
        <summary class="hub-accordion__summary">
            <span class="hub-accordion__icon" aria-hidden="true">🔥</span>
            <?= htmlspecialchars($t['hub_alo_combis']) ?>
            <svg class="hub-accordion__chevron" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </summary>
        <div class="hub-accordion__body">
            <ul class="hub-link-grid" role="list" aria-label="Combinaciones populares de alojamientos">
                <?php foreach (HUB_COMBIS_ALO as $combi):
                    $slug  = buildAloSlug($combi);
                    $url   = hubUrl($slug, $lang, 'alojamientos');
                    // Construir label legible
                    $parts = [];
                    foreach ($combi as $part) {
                        if (isset(HUB_FILTROS_ALO[$part])) {
                            $parts[] = HUB_FILTROS_ALO[$part][$lang] ?? HUB_FILTROS_ALO[$part]['es'];
                        } elseif (isset(HUB_PROVINCIAS[$part])) {
                            $parts[] = HUB_PROVINCIAS[$part]['label'];
                        } else {
                            $parts[] = ucfirst(str_replace('-', ' ', $part));
                        }
                    }
                    $label = implode(' · ', $parts);
                    // Icono del primer filtro
                    $icon = '';
                    foreach ($combi as $part) {
                        if (isset(HUB_FILTROS_ALO[$part])) {
                            $icon = HUB_FILTROS_ALO[$part]['icon'];
                            break;
                        }
                    }
                ?>
                <li>
                    <a href="<?= htmlspecialchars($url) ?>"
                       class="hub-link-item"
                       title="<?= htmlspecialchars($label) ?>">
                        <?php if ($icon): ?>
                        <span class="hub-link-item__icon" aria-hidden="true"><?= $icon ?></span>
                        <?php endif; ?>
                        <?= htmlspecialchars($label) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </details>

    <!-- ── CTA Ver todos + Mapa ────────────────────────────────────────────────── -->
    <div class="hub-section__cta">
        <a href="<?= $base ?>/alojamientos/turismo-rural"
           class="hub-btn hub-btn--outline">
            <?= htmlspecialchars($t['hub_alo_all']) ?>
        </a>
        <a href="<?= $base ?>/rutas.php?alojamientos=1&lugares=0&actividades=0&eventos=0"
           class="hub-btn hub-btn--map">
            🗺️ <?= $lang === 'es' ? 'Ver en el mapa' : ($lang === 'en' ? 'View on map' : ($lang === 'fr' ? 'Voir sur la carte' : ($lang === 'de' ? 'Auf der Karte' : '在地图上查看'))) ?>
        </a>
    </div>

</div><!-- /.hub-container -->
</section>
    <?php
}

<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  HERO.PHP — Hero Section del Index Hub
 *  - H1 semántico con propuesta de valor
 *  - 4 CTAs principales (verticales)
 *  - Estadísticas dinámicas (desde BD o cache)
 *  - Buscador visual que enlaza a las verticales
 *  - Botón Antonio
 * ════════════════════════════════════════════════════════════════════════════
 *
 * @param array $ctx  [lang, t, stats, base_url]
 */
function renderHubHero(array $ctx): void {
    $lang     = $ctx['lang'];
    $t        = $ctx['t'];
    $stats    = $ctx['stats'] ?? [];
    $base     = 'https://rutasrurales.io';
    $langPfx  = ($lang !== 'es') ? "/$lang" : '';

    // URLs de las verticales con soporte multilingüe
    $url_stays  = $base . $langPfx . '/alojamientos/turismo-rural';
    $url_events = $base . $langPfx . '/eventos-culturales-paginacion.html';
    $url_places = $base . '/lugares-de-interes'; // Solo ES por ahora
    $url_activ  = $base . '/actividades-turisticas'; // Solo ES por ahora

    // Estadísticas (fallback a valores ilustrativos si BD no disponible)
    $total_stays  = $stats['total_stays']  ?? '+500';
    $total_events = $stats['total_events'] ?? '+1.200';
    $total_prov   = $stats['total_prov']   ?? '12';
    ?>
<section class="hub-hero" id="inicio" aria-label="Inicio">

    <!-- Imagen de fondo optimizada para LCP -->
    <div class="hub-hero__bg" aria-hidden="true">
        <img
            src="/menu_images/hero_main.webp"
            alt=""
            class="hub-hero__bg-img"
            width="1200" height="600"
            loading="eager"
            fetchpriority="high"
            decoding="async">
        <div class="hub-hero__overlay" aria-hidden="true"></div>
    </div>

    <div class="hub-container hub-hero__inner">

        <!-- H1 principal -->
        <h1 class="hub-hero__h1">
            <?= htmlspecialchars($t['hero_h1']) ?>
        </h1>
        <p class="hub-hero__sub">
            <?= htmlspecialchars($t['hero_sub']) ?>
        </p>

        <!-- 4 CTAs de verticales -->
        <nav class="hub-hero__ctas" aria-label="Secciones principales">
            <a href="<?= $url_stays ?>"
               class="hub-cta hub-cta--primary"
               aria-label="<?= htmlspecialchars($t['hero_cta_stays']) ?>">
                <span class="hub-cta__icon" aria-hidden="true">🏡</span>
                <span class="hub-cta__text"><?= htmlspecialchars($t['hero_cta_stays']) ?></span>
            </a>
            <a href="<?= $url_events ?>"
               class="hub-cta hub-cta--secondary"
               aria-label="<?= htmlspecialchars($t['hero_cta_events']) ?>">
                <span class="hub-cta__icon" aria-hidden="true">🎭</span>
                <span class="hub-cta__text"><?= htmlspecialchars($t['hero_cta_events']) ?></span>
            </a>
            <a href="<?= $url_places ?>"
               class="hub-cta hub-cta--secondary"
               aria-label="<?= htmlspecialchars($t['hero_cta_places']) ?>">
                <span class="hub-cta__icon" aria-hidden="true">🏛️</span>
                <span class="hub-cta__text"><?= htmlspecialchars($t['hero_cta_places']) ?></span>
            </a>
            <a href="<?= $url_activ ?>"
               class="hub-cta hub-cta--secondary"
               aria-label="<?= htmlspecialchars($t['hero_cta_activ']) ?>">
                <span class="hub-cta__icon" aria-hidden="true">🥾</span>
                <span class="hub-cta__text"><?= htmlspecialchars($t['hero_cta_activ']) ?></span>
            </a>
        </nav>

        <!-- Estadísticas dinámicas -->
        <div class="hub-hero__stats" aria-label="Estadísticas de la plataforma">
            <div class="hub-stat">
                <span class="hub-stat__value"><?= htmlspecialchars((string)$total_stays) ?></span>
                <span class="hub-stat__label"><?= htmlspecialchars($t['hero_stat_stays']) ?></span>
            </div>
            <div class="hub-stat">
                <span class="hub-stat__value"><?= htmlspecialchars((string)$total_events) ?></span>
                <span class="hub-stat__label"><?= htmlspecialchars($t['hero_stat_events']) ?></span>
            </div>
            <div class="hub-stat">
                <span class="hub-stat__value"><?= htmlspecialchars((string)$total_prov) ?></span>
                <span class="hub-stat__label"><?= htmlspecialchars($t['hero_stat_prov']) ?></span>
            </div>
        </div>

    </div><!-- /.hub-hero__inner -->
</section>
    <?php
}

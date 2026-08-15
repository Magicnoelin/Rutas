<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  HERO SECTION — Landing de Alojamientos
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Renderiza: breadcrumb > badges > H1 > stats > filtros activos
 *
 *  $ctx esperado:
 *    h1, province_label, filter_icons, filter_labels,
 *    stats (total, avg_price, towns), t (array traducciones),
 *    canonical, lang, slug, parsed (province, filters)
 */

function renderLandingHero(array $ctx): void
{
    $h1           = $ctx['h1']            ?? 'Alojamientos rurales';
    $province     = $ctx['province_label']?? '';
    $stats        = $ctx['stats']         ?? [];
    $t            = $ctx['t']             ?? [];
    $canonical    = $ctx['canonical']     ?? '#';
    $lang         = $ctx['lang']          ?? 'es';
    $parsed       = $ctx['parsed']        ?? ['province'=>null,'filters'=>[]];
    $filter_icons = $ctx['filter_icons']  ?? [];
    $filter_labs  = $ctx['filter_labels'] ?? [];

    // Imagen Hero
    $hero_image_url = $ctx['hero_image_url'] ?? '';
    $hero_image_alt = $ctx['hero_image_alt'] ?? htmlspecialchars($h1);
    $has_hero_img = !empty($hero_image_url);

    $base_url = 'https://rutasrurales.io';
    $list_url = $lang !== 'es' ? "$base_url/$lang/alojamientos/turismo-rural" : "$base_url/alojamientos/turismo-rural";

    // Enlace "ver todos en provincia" — slug solo-provincia (sin filtros)
    // p.ej. /de/alojamientos/zamora  (no turismo-rural-zamora, que sería la misma página)
    $prov_url = '';
    if (!empty($parsed['province'])) {
        $prov_slug = $parsed['province'];
        $prov_url  = $lang !== 'es'
            ? "$base_url/$lang/alojamientos/$prov_slug"
            : "$base_url/alojamientos/$prov_slug";
    }
?>
<!-- ══════════════════════════════════════════════════════════ HERO ══ -->
<section class="lnd-hero<?= $has_hero_img ? ' has-hero-img' : '' ?>" aria-label="Cabecera de búsqueda">

    <?php if ($has_hero_img): ?>
    <div class="lnd-hero__bg-wrap" aria-hidden="true">
        <img
            src="<?= htmlspecialchars($hero_image_url) ?>"
            alt="<?= htmlspecialchars($hero_image_alt) ?>"
            width="1440"
            height="500"
            fetchpriority="high"
            decoding="sync"
            class="lnd-hero__bg-img"
        >
    </div>
    <?php endif; ?>

    <div class="lnd-hero__content">

    
    <!-- Breadcrumb semántico (también en Schema, esto es para usuarios) -->
    <nav class="lnd-breadcrumb" aria-label="Breadcrumb">
        <ol itemscope itemtype="https://schema.org/BreadcrumbList">
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a href="<?= $base_url ?>/" itemprop="item">
                    <span itemprop="name"><?= htmlspecialchars($t['bc_home'] ?? 'Inicio') ?></span>
                </a>
                <meta itemprop="position" content="1">
                <span class="lnd-bc-sep" aria-hidden="true">›</span>
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a href="<?= $list_url ?>" itemprop="item">
                    <span itemprop="name"><?= htmlspecialchars($t['bc_listings'] ?? 'Alojamientos rurales') ?></span>
                </a>
                <meta itemprop="position" content="2">
                <?php if (!empty($province) || !empty($filter_labs)): ?>
                <span class="lnd-bc-sep" aria-hidden="true">›</span>
                <?php endif; ?>
            </li>
            <?php if (!empty($province) || !empty($filter_labs)): ?>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" aria-current="page">
                <span itemprop="name"><?= htmlspecialchars($h1) ?></span>
                <meta itemprop="position" content="3">
            </li>
            <?php endif; ?>
        </ol>
    </nav>

    <!-- Badges de filtros activos -->
    <?php if (!empty($filter_icons)): ?>
    <div class="lnd-hero__badges" aria-label="Filtros activos">
        <?php foreach ($filter_icons as $i => $icon): ?>
        <span class="lnd-badge lnd-badge--filter">
            <?= $icon ?> <?= htmlspecialchars($filter_labs[$i] ?? '') ?>
        </span>
        <?php endforeach; ?>
        <?php if (!empty($province)): ?>
        <span class="lnd-badge lnd-badge--province">
            📍 <?= htmlspecialchars($province) ?>
        </span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- H1 — único, explícito, con keyword principal -->
    <h1 class="lnd-hero__h1"><?= htmlspecialchars($h1) ?></h1>

    <!-- Stats en tiempo real (de BD) -->
    <?php if (!empty($stats['total']) && $stats['total'] > 0): ?>
    <dl class="lnd-hero__stats" aria-label="Estadísticas de alojamientos">
        <div class="lnd-stat">
            <dt class="lnd-stat__value"><?= $stats['total'] ?></dt>
            <dd class="lnd-stat__label"><?= htmlspecialchars($t['stat_count'] ?? 'alojamientos') ?></dd>
        </div>
        <?php if (!empty($stats['avg_price'])): ?>
        <div class="lnd-stat">
            <dt class="lnd-stat__value"><?= $stats['avg_price'] ?> €</dt>
            <dd class="lnd-stat__label"><?= htmlspecialchars($t['stat_price'] ?? 'precio medio/noche') ?></dd>
        </div>
        <?php endif; ?>
        <?php if (!empty($stats['towns'])): ?>
        <div class="lnd-stat">
            <dt class="lnd-stat__value"><?= $stats['towns'] ?></dt>
            <dd class="lnd-stat__label"><?= htmlspecialchars($t['stat_towns'] ?? 'municipios') ?></dd>
        </div>
        <?php endif; ?>
    </dl>
    <?php endif; ?>

    <!-- Enlace rápido a todos los alojamientos de la provincia (sin filtros) -->
    <?php if (!empty($prov_url) && !empty($parsed['filters'])): ?>
    <p class="lnd-hero__sublink">
        <a href="<?= htmlspecialchars($prov_url) ?>">
            <?= htmlspecialchars(t($t['hero_all_prov'] ?? 'Ver todos los alojamientos en {PROVINCE}', ['PROVINCE' => $province])) ?> →
        </a>
    </p>
    <?php endif; ?>

    <!-- Botón compartir (móvil: Web Share API, desktop: clipboard) -->
    <div class="lnd-hero__share">
        <button type="button" class="lnd-share-btn" id="btnCompartirAlojamientos"
                aria-label="<?= htmlspecialchars($t['share_btn'] ?? 'Compartir esta página') ?>"
                onclick="compartirPaginaAlojamientos(this)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <circle cx="18" cy="5" r="3"/>
                <circle cx="6" cy="12" r="3"/>
                <circle cx="18" cy="19" r="3"/>
                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
            </svg>
            <span><?= htmlspecialchars($t['share_btn'] ?? 'Compartir esta página') ?></span>
        </button>
    </div>

    </div><!-- /.lnd-hero__content -->

</section>
<!-- ════════════════════════════════════════════════════════ /HERO ══ -->

<!-- Estilos para el botón compartir -->
<style>
.lnd-hero__share { margin-top: 16px; }
.lnd-share-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.25);
    color: var(--white); padding: 8px 16px; border-radius: 25px;
    font-family: inherit; font-size: .85rem; font-weight: 600;
    cursor: pointer; transition: var(--transition);
    backdrop-filter: blur(4px); line-height: 1.4;
}
.lnd-share-btn:hover,
.lnd-share-btn:focus-visible { background: rgba(255,255,255,.22); outline: none; }
.lnd-share-btn:active { transform: scale(.96); }
.lnd-share-btn--copied { background: rgba(129,199,132,.25); border-color: var(--accent); }
@media (max-width: 480px) {
    .lnd-share-btn { width: 100%; justify-content: center; padding: 10px 18px; font-size: .88rem; }
}
</style>

<!-- JavaScript para el botón compartir -->
<script>
function compartirPaginaAlojamientos(btn) {
    var url   = window.location.href;
    var title = '<?= htmlspecialchars($t['share_title'] ?? '¡Mira estos alojamientos rurales!', ENT_QUOTES) ?>';
    var span  = btn.querySelector('span');
    
    if (navigator.share) {
        // Web Share API (móviles)
        navigator.share({ title: title, url: url }).catch(function() {});
    } else {
        // Clipboard API (desktop)
        var orig = span.textContent;
        var copied = '<?= htmlspecialchars($t['share_copy'] ?? 'Enlace copiado ✓', ENT_QUOTES) ?>';
        
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function() {
                span.textContent = copied;
                btn.classList.add('lnd-share-btn--copied');
                setTimeout(function() {
                    span.textContent = orig;
                    btn.classList.remove('lnd-share-btn--copied');
                }, 2500);
            }).catch(function() {
                // Fallback para navegadores que no soportan clipboard
                span.textContent = copied;
                btn.classList.add('lnd-share-btn--copied');
                setTimeout(function() {
                    span.textContent = orig;
                    btn.classList.remove('lnd-share-btn--copied');
                }, 2500);
            });
        } else {
            // Fallback para navegadores muy antiguos
            span.textContent = copied;
            btn.classList.add('lnd-share-btn--copied');
            setTimeout(function() {
                span.textContent = orig;
                btn.classList.remove('lnd-share-btn--copied');
            }, 2500);
        }
    }
}
</script>
<?php
}

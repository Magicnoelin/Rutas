<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  HERO SECTION — Landing de Eventos Culturales
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Renderiza: breadcrumb > badges de filtros > H1 > stats
 *
 *  $ctx esperado:
 *    h1, province_label, filter_icons, filter_labels,
 *    stats (total, free_count, towns), t (array traducciones),
 *    canonical, lang, slug, parsed (province, filters)
 */

function renderEventosLandingHero(array $ctx): void
{
    $h1           = $ctx['h1']             ?? 'Eventos culturales';
    $province     = $ctx['province_label'] ?? '';
    $stats        = $ctx['stats']          ?? [];
    $t            = $ctx['t']              ?? [];
    $canonical    = $ctx['canonical']      ?? '#';
    $lang         = $ctx['lang']           ?? 'es';
    $parsed       = $ctx['parsed']         ?? ['province' => null, 'filters' => []];
    $filter_icons = $ctx['filter_icons']   ?? [];
    $filter_labs  = $ctx['filter_labels']  ?? [];

    $base_url = 'https://rutasrurales.io';
    $list_url = $lang !== 'es' ? "$base_url/$lang/eventos-culturales" : "$base_url/eventos-culturales";

    // Enlace "ver todos los eventos en provincia"
    $prov_url = '';
    if (!empty($parsed['province'])) {
        $prov_slug = $parsed['province'];
        $prov_url  = $lang !== 'es'
            ? "$base_url/$lang/eventos/$prov_slug"
            : "$base_url/eventos/$prov_slug";
    }
?>
<!-- ══════════════════════════════════════════════════════════ HERO ══ -->
<section class="lnd-hero" aria-label="Cabecera de búsqueda de eventos">

    <!-- Breadcrumb semántico -->
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
                    <span itemprop="name"><?= htmlspecialchars($t['bc_listings'] ?? 'Eventos culturales') ?></span>
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
    <dl class="lnd-hero__stats" aria-label="Estadísticas de eventos">
        <div class="lnd-stat">
            <dt class="lnd-stat__value"><?= $stats['total'] ?></dt>
            <dd class="lnd-stat__label"><?= htmlspecialchars($t['stat_count'] ?? 'eventos') ?></dd>
        </div>
        <?php if (!empty($stats['free_count']) && $stats['free_count'] > 0): ?>
        <div class="lnd-stat">
            <dt class="lnd-stat__value"><?= $stats['free_count'] ?></dt>
            <dd class="lnd-stat__label"><?= htmlspecialchars($t['stat_free'] ?? 'gratuitos') ?></dd>
        </div>
        <?php endif; ?>
        <?php if (!empty($stats['towns']) && $stats['towns'] > 0): ?>
        <div class="lnd-stat">
            <dt class="lnd-stat__value"><?= $stats['towns'] ?></dt>
            <dd class="lnd-stat__label"><?= htmlspecialchars($t['stat_towns'] ?? 'municipios') ?></dd>
        </div>
        <?php endif; ?>
    </dl>
    <?php endif; ?>

    <!-- Enlace rápido a todos los eventos de la provincia -->
    <?php if (!empty($prov_url) && !empty($parsed['filters'])): ?>
    <p class="lnd-hero__sublink">
        <a href="<?= htmlspecialchars($prov_url) ?>">
            <?= htmlspecialchars($lang === 'es' ? "Ver todos los eventos en $province →" : "All events in $province →") ?>
        </a>
    </p>
    <?php endif; ?>

    <!-- Botón compartir (móvil: Web Share API, desktop: clipboard) -->
    <div class="lnd-hero__share">
        <button type="button" class="lnd-share-btn" id="btnCompartir"
                aria-label="<?= htmlspecialchars($t['share_btn'] ?? 'Compartir esta página') ?>"
                onclick="compartirPagina(this)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
            </svg>
            <span><?= htmlspecialchars($t['share_btn'] ?? 'Compartir esta página') ?></span>
        </button>
    </div>

    <style>
    .lnd-hero__share{margin-top:16px}
    .lnd-share-btn{display:inline-flex;align-items:center;gap:8px;
      background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);
      color:#fff;padding:8px 18px;border-radius:24px;font-size:.82rem;font-weight:600;
      cursor:pointer;transition:background .18s ease,transform .12s ease;
      font-family:inherit;line-height:1.4;backdrop-filter:blur(4px)}
    .lnd-share-btn:hover,.lnd-share-btn:focus-visible{background:rgba(255,255,255,.22);outline:none}
    .lnd-share-btn:active{transform:scale(.96)}
    .lnd-share-btn--copied{background:rgba(129,199,132,.25);border-color:var(--accent)}
    @media(max-width:480px){.lnd-share-btn{width:100%;justify-content:center;padding:10px 18px;font-size:.88rem}}
    </style>

    <script>
    function compartirPagina(btn){
      var url = window.location.href;
      var title = '<?= htmlspecialchars($t['share_title'] ?? '¡Mira estos eventos!', ENT_QUOTES) ?>';
      if(navigator.share){
        navigator.share({title:title,url:url}).catch(function(){});
      }else{
        if(navigator.clipboard && navigator.clipboard.writeText){
          navigator.clipboard.writeText(url).then(function(){
            var span = btn.querySelector('span');
            var orig = span.textContent;
            span.textContent = '<?= htmlspecialchars($t['share_copy'] ?? 'Enlace copiado ✓', ENT_QUOTES) ?>';
            btn.classList.add('lnd-share-btn--copied');
            setTimeout(function(){
              span.textContent = orig;
              btn.classList.remove('lnd-share-btn--copied');
            },2500);
          }).catch(function(){});
        }else{
          // Fallback: seleccionar la URL manualmente
          var input = document.createElement('input');
          input.value = url;
          document.body.appendChild(input);
          input.select();
          document.execCommand('copy');
          document.body.removeChild(input);
          var span = btn.querySelector('span');
          var orig = span.textContent;
          span.textContent = '<?= htmlspecialchars($t['share_copy'] ?? 'Enlace copiado ✓', ENT_QUOTES) ?>';
          btn.classList.add('lnd-share-btn--copied');
          setTimeout(function(){
            span.textContent = orig;
            btn.classList.remove('lnd-share-btn--copied');
          },2500);
        }
      }
    }
    </script>

</section>
<!-- ════════════════════════════════════════════════════════ /HERO ══ -->
<?php
}

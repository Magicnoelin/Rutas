<?php
/**
 * MÓDULO: DESCRIPCIÓN SEO LARGA (ES / FR)
 * Solo renderiza si la ruta tiene descripcion_larga_es (generada por LLM n8n).
 * Llamar desde index.php: renderDescripcionSeo($ruta)
 */

function renderDescripcionSeo(array $ruta): void
{
if (empty($ruta['descripcion_larga_es'])) { return; }

$desc_es   = $ruta['descripcion_larga_es'];
$desc_fr   = $ruta['descripcion_larga_fr'] ?? '';
$titulo    = htmlspecialchars($ruta['name']     ?? '', ENT_QUOTES, 'UTF-8');
$provincia = htmlspecialchars($ruta['province'] ?? '', ENT_QUOTES, 'UTF-8');
$tiene_fr  = !empty($desc_fr);

$desc_to_html = static function (string $t): string {
    $t     = htmlspecialchars(trim($t), ENT_QUOTES, 'UTF-8');
    $parts = preg_split('/\n{2,}/', $t);
    return implode('', array_map(fn($p) => '<p>' . nl2br(trim($p)) . '</p>', array_filter($parts)));
};
$html_es = $desc_to_html($desc_es);
$html_fr = $tiene_fr ? $desc_to_html($desc_fr) : '';

$distancia_km  = $ruta['distancia_total_km'] ?? null;
$dur_min       = intval($ruta['duracion_min'] ?? 0);
$paradas       = intval($ruta['total_paradas'] ?? 0);
$dur_txt       = $dur_min > 0 ? (floor($dur_min/60) > 0 ? floor($dur_min/60).'h '.($dur_min%60).'min' : ($dur_min%60).' min') : '';
$dif_map       = ['facil' => '🟢 Fácil', 'moderada' => '🟡 Moderada', 'dificil' => '🔴 Difícil'];
$dif           = $dif_map[$ruta['difficulty_level'] ?? ''] ?? '';
?>

<section class="rt-desc-seo rt-section rt-section--alt" id="descripcion-ruta"
         aria-label="Descripción de la ruta <?= $titulo ?>">
    <div class="rt-container">
        <div class="rt-desc-seo__inner">

            <div class="rt-desc-seo__texto">
                <div class="rt-desc-seo__header">
                    <h2 class="rt-desc-seo__title">
                        <span aria-hidden="true">📖</span> Sobre esta ruta
                    </h2>
                    <?php if ($tiene_fr): ?>
                    <div class="rt-lang-toggle" role="group" aria-label="Cambiar idioma">
                        <button class="rt-lang-btn rt-lang-btn--active" id="btn-lang-es"
                                onclick="rtLangSwitch('es')" aria-pressed="true">🇪🇸 ES</button>
                        <button class="rt-lang-btn" id="btn-lang-fr"
                                onclick="rtLangSwitch('fr')" aria-pressed="false">🇫🇷 FR</button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="rt-desc-seo__content" id="rt-desc-es" lang="es"><?= $html_es ?></div>
                <?php if ($tiene_fr): ?>
                <div class="rt-desc-seo__content rt-desc-seo__content--hidden" id="rt-desc-fr" lang="fr" aria-hidden="true">
                    <?= $html_fr ?>
                </div>
                <?php endif; ?>
            </div>

            <aside class="rt-desc-seo__aside" aria-label="Datos rápidos de la ruta">
                <div class="rt-desc-seo__ficha">
                    <h3 class="rt-desc-seo__ficha-title">Datos rápidos</h3>

                    <?php $datos = [
                        $distancia_km ? ['📏', number_format(floatval($distancia_km),1,',','.').' km', 'Distancia total'] : null,
                        $dur_txt      ? ['🚗', $dur_txt,                                               'Tiempo en coche'] : null,
                        $paradas > 0  ? ['📍', $paradas.' paradas',                                    'Lugares incluidos'] : null,
                        !empty($ruta['duration_days']) ? ['📅', intval($ruta['duration_days']).' días','Duración recomendada'] : null,
                        $dif          ? ['⚡',  $dif,                                                  'Dificultad'] : null,
                        $provincia    ? ['🗺️',  $provincia,                                            'Provincia'] : null,
                    ];
                    foreach (array_filter($datos) as [$icon, $val, $lbl]): ?>
                    <div class="rt-desc-seo__dato">
                        <span class="rt-desc-seo__dato-icon" aria-hidden="true"><?= $icon ?></span>
                        <div>
                            <span class="rt-desc-seo__dato-val"><?= htmlspecialchars($val) ?></span>
                            <span class="rt-desc-seo__dato-label"><?= htmlspecialchars($lbl) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="rt-desc-seo__cta">
                        <a href="#alojamientos" class="rt-btn rt-btn--card-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            Ver alojamientos
                        </a>
                        <a href="#mapa-ruta" class="rt-btn rt-btn--outline" style="margin-top:8px;">🗺️ Ver en el mapa</a>
                    </div>
                </div>
            </aside>

        </div>
    </div>
</section>
<?php if ($tiene_fr): ?>
<script>
function rtLangSwitch(lang) {
    var es=document.getElementById('rt-desc-es'),fr=document.getElementById('rt-desc-fr');
    var bEs=document.getElementById('btn-lang-es'),bFr=document.getElementById('btn-lang-fr');
    if (!es||!fr) return;
    var isEs=(lang==='es');
    es.classList.toggle('rt-desc-seo__content--hidden',!isEs); fr.classList.toggle('rt-desc-seo__content--hidden',isEs);
    es.setAttribute('aria-hidden',isEs?'false':'true'); fr.setAttribute('aria-hidden',isEs?'true':'false');
    bEs.classList.toggle('rt-lang-btn--active', isEs); bEs.setAttribute('aria-pressed',isEs?'true':'false');
    bFr.classList.toggle('rt-lang-btn--active',!isEs); bFr.setAttribute('aria-pressed',isEs?'false':'true');
}
</script>
<?php endif; ?>
<?php
} // end function renderDescripcionSeo()

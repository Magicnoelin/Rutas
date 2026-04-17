<?php
/**
 * Módulo: Hero de la ruta temática
 * Incluye: imagen de fondo, título, descripción, cuenta atrás y badges
 */

function renderHero(array $ruta): void
{
    $titulo      = htmlspecialchars($ruta['name'] ?? '');
    $descripcion = htmlspecialchars(substr(strip_tags($ruta['description'] ?? ''), 0, 220));
    $heroImg     = $ruta['hero_image'] ?? '';
    $coverColor  = $ruta['cover_color'] ?? '#2F5233';
    $provincia   = htmlspecialchars($ruta['province'] ?? 'Soria');
    $duracion    = (int)($ruta['duration_days'] ?? 3);
    $dificultad  = $ruta['difficulty_level'] ?? 'facil';
    $season      = $ruta['season'] ?? '';

    // Calcular si hay cuenta atrás (solo para rutas temporales con itinerario)
    $fechaInicio = null;
    $diasRestantes = null;
    if (!empty($ruta['itinerary_json'])) {
        $dias = $ruta['itinerary_json'];
        if (!empty($dias[0]['fecha'])) {
            $fechaInicio = $dias[0]['fecha'];
            $hoy = new DateTime('today');
            $inicio = new DateTime($fechaInicio);
            $diff = $hoy->diff($inicio);
            if (!$diff->invert && $diff->days > 0) {
                $diasRestantes = $diff->days;
            }
        }
    }

    $dificultadLabel = ['facil' => 'Fácil', 'moderado' => 'Moderado', 'dificil' => 'Difícil'][$dificultad] ?? 'Fácil';
    $seasonLabel = [
        'primavera'   => '🌸 Primavera',
        'verano'      => '☀️ Verano',
        'otoño'       => '🍂 Otoño',
        'invierno'    => '❄️ Invierno',
        'todo-el-año' => '📅 Todo el año',
    ][$season] ?? '';

    $heroStyle = $heroImg
        ? "background-image: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.55)), url('" . htmlspecialchars($heroImg) . "');"
        : "background-color: {$coverColor};";
?>
<section class="rt-hero" style="<?= $heroStyle ?>">
    <div class="rt-hero__inner">

        <!-- Breadcrumb SEO -->
        <nav class="rt-breadcrumb" aria-label="Ruta de navegación">
            <ol>
                <li><a href="https://rutasrurales.io/">Inicio</a></li>
                <li aria-hidden="true">›</li>
                <li><a href="https://rutasrurales.io/rutas/">Rutas</a></li>
                <li aria-hidden="true">›</li>
                <li aria-current="page"><?= $titulo ?></li>
            </ol>
        </nav>

        <!-- Badges -->
        <div class="rt-hero__badges">
            <span class="rt-badge rt-badge--provincia">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?= $provincia ?>
            </span>
            <span class="rt-badge rt-badge--duracion">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <?= $duracion ?> <?= $duracion === 1 ? 'día' : 'días' ?>
            </span>
            <span class="rt-badge rt-badge--dificultad">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                <?= $dificultadLabel ?>
            </span>
            <?php if ($seasonLabel): ?>
            <span class="rt-badge rt-badge--season"><?= $seasonLabel ?></span>
            <?php endif; ?>
        </div>

        <!-- Título principal H1 -->
        <h1 class="rt-hero__title"><?= $titulo ?></h1>
        <p class="rt-hero__desc"><?= $descripcion ?>…</p>

        <!-- Cuenta atrás -->
        <?php if ($diasRestantes !== null && $diasRestantes > 0): ?>
        <div class="rt-countdown" id="rtCountdown" data-fecha="<?= htmlspecialchars($fechaInicio) ?>">
            <span class="rt-countdown__label">⏳ Faltan</span>
            <span class="rt-countdown__num" id="cdDias"><?= $diasRestantes ?></span>
            <span class="rt-countdown__unit">días</span>
            <span class="rt-countdown__sep">:</span>
            <span class="rt-countdown__num" id="cdHoras">--</span>
            <span class="rt-countdown__unit">h</span>
            <span class="rt-countdown__sep">:</span>
            <span class="rt-countdown__num" id="cdMin">--</span>
            <span class="rt-countdown__unit">min</span>
        </div>
        <?php elseif ($diasRestantes === 0): ?>
        <div class="rt-countdown rt-countdown--hoy">
            🎉 <strong>¡El puente empieza hoy!</strong> Disfruta de Soria.
        </div>
        <?php endif; ?>

        <!-- CTAs -->
        <div class="rt-hero__ctas">
            <a href="#alojamientos" class="rt-btn rt-btn--primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Ver alojamientos
            </a>
            <a href="#itinerario" class="rt-btn rt-btn--secondary">
                Ver itinerario completo
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
            </a>
        </div>

    </div>
</section>

<?php if ($diasRestantes !== null && $diasRestantes > 0): ?>
<script>
(function() {
    var target = new Date('<?= $fechaInicio ?>T00:00:00');
    function tick() {
        var now = new Date();
        var diff = target - now;
        if (diff <= 0) { document.getElementById('rtCountdown').innerHTML = '🎉 ¡El puente empieza hoy!'; return; }
        var d = Math.floor(diff / 86400000);
        var h = Math.floor((diff % 86400000) / 3600000);
        var m = Math.floor((diff % 3600000) / 60000);
        var el = document.getElementById('rtCountdown');
        if (!el) return;
        document.getElementById('cdDias').textContent = d;
        document.getElementById('cdHoras').textContent = String(h).padStart(2,'0');
        document.getElementById('cdMin').textContent = String(m).padStart(2,'0');
    }
    tick();
    setInterval(tick, 60000);
})();
</script>
<?php endif; ?>
<?php
}

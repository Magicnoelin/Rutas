<?php
/**
 * Módulo: Eventos culturales del período de la ruta
 */

function renderEventos(array $eventos, array $ruta): void
{
    if (empty($eventos)) return;
    $provincia = htmlspecialchars($ruta['province'] ?? 'Soria');
    $meses = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
?>
<section class="rt-section rt-section--alt" id="eventos">
    <div class="rt-container">
        <div class="rt-section__header">
            <h2 class="rt-section__title">
                <span class="rt-section__icon" aria-hidden="true">🎭</span>
                Eventos durante el puente
            </h2>
            <p class="rt-section__subtitle">
                Lo que pasa en <?= $provincia ?> mientras estás de escapada
            </p>
        </div>

        <div class="rt-eventos">
            <?php foreach ($eventos as $e):
                $titulo    = htmlspecialchars($e['title'] ?? '');
                $localidad = htmlspecialchars($e['municipality'] ?? $e['venue_name'] ?? '');
                $prov      = htmlspecialchars($e['province'] ?? $provincia);
                $desc      = htmlspecialchars(substr(strip_tags($e['short_description'] ?? $e['description'] ?? ''), 0, 130));
                $precio    = htmlspecialchars($e['precio_display'] ?? 'Consultar precio');
                $url       = $e['url'] ?? '#';
                $imagen    = $e['imagen'] ?? null;
                $ticketUrl = $e['ticket_url'] ?? null;
                $organizer = htmlspecialchars($e['organizer'] ?? '');

                // Formatear fecha
                $fechaLabel = '';
                if (!empty($e['start_date'])) {
                    $dt = DateTime::createFromFormat('Y-m-d', substr($e['start_date'], 0, 10));
                    if ($dt) {
                        $fechaLabel = $dt->format('d') . ' de ' . $meses[(int)$dt->format('n')];
                    }
                }
                $horaLabel = '';
                if (!empty($e['start_time'])) {
                    $horaLabel = substr($e['start_time'], 0, 5) . 'h';
                }
            ?>
            <article class="rt-evento">
                <!-- Fecha destacada -->
                <div class="rt-evento__fecha" aria-label="Fecha del evento">
                    <?php if (!empty($e['start_date'])): ?>
                    <?php $dt2 = DateTime::createFromFormat('Y-m-d', substr($e['start_date'],0,10)); ?>
                    <span class="rt-evento__dia"><?= $dt2 ? $dt2->format('d') : '' ?></span>
                    <span class="rt-evento__mes"><?= $dt2 ? strtoupper($meses[(int)$dt2->format('n')]) : '' ?></span>
                    <?php endif; ?>
                </div>

                <!-- Imagen -->
                <?php if ($imagen): ?>
                <div class="rt-evento__img-wrap">
                    <img
                        src="<?= htmlspecialchars($imagen) ?>"
                        alt="<?= $titulo ?>"
                        width="320" height="180"
                        loading="lazy"
                        decoding="async"
                        class="rt-evento__img"
                        onerror="this.closest('.rt-evento__img-wrap').style.display='none'"
                    >
                </div>
                <?php endif; ?>

                <!-- Contenido -->
                <div class="rt-evento__body">
                    <div class="rt-evento__meta">
                        <span class="rt-evento__location">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?= $localidad ?>
                        </span>
                        <?php if ($horaLabel): ?>
                        <span class="rt-evento__hora">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?= $horaLabel ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <h3 class="rt-evento__title">
                        <a href="<?= htmlspecialchars($url) ?>"><?= $titulo ?></a>
                    </h3>

                    <?php if ($desc): ?>
                    <p class="rt-evento__desc"><?= $desc ?>…</p>
                    <?php endif; ?>

                    <div class="rt-evento__footer">
                        <span class="rt-evento__precio <?= ($e['is_free'] ?? false) ? 'rt-evento__precio--free' : '' ?>">
                            <?= $precio ?>
                        </span>
                        <div class="rt-evento__ctas">
                            <a href="<?= htmlspecialchars($url) ?>" class="rt-btn rt-btn--evento">
                                Más info
                            </a>
                            <?php if ($ticketUrl): ?>
                            <a href="<?= htmlspecialchars($ticketUrl) ?>" class="rt-btn rt-btn--ticket" target="_blank" rel="noopener">
                                Entradas
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="rt-section__cta">
            <a href="https://rutasrurales.io/eventos-culturales-paginacion.html" class="rt-btn rt-btn--outline">
                Ver todos los eventos
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>
</section>
<?php
}

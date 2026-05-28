<?php
/**
 * Módulo: Lugares de interés de la ruta
 * Muestra los lugares con thumbnail, al estilo de los eventos
 */

function renderLugares(array $lugares, array $ruta): void
{
    if (empty($lugares)) return;
    $provincia = htmlspecialchars($ruta['province'] ?? 'Soria');
?>
<section class="rt-section" id="lugares">
    <div class="rt-container">
        <div class="rt-section__header">
            <h2 class="rt-section__title">
                <span class="rt-section__icon" aria-hidden="true">🏛️</span>
                Qué ver en <?= $provincia ?>
            </h2>
            <p class="rt-section__subtitle">
                Los lugares imprescindibles que no puedes perderte en esta escapada
            </p>
        </div>

        <div class="rt-eventos">
            <?php foreach ($lugares as $l):
                $nombre    = htmlspecialchars($l['name'] ?? '');
                $localidad = htmlspecialchars($l['municipality'] ?? '');
                $prov      = htmlspecialchars($l['province'] ?? $provincia);
                $nota      = htmlspecialchars($l['editorial_note'] ?? '');
                $desc      = $nota ?: htmlspecialchars(substr(strip_tags($l['short_description'] ?? $l['description'] ?? ''), 0, 130));
                $precio    = htmlspecialchars($l['precio_entrada'] ?? 'Entrada gratuita');
                $url       = $l['url'] ?? '#';
                $foto      = $l['fotos'][0] ?? null;
                $highlight = !empty($l['is_highlight']);
                $timeSlot  = $l['time_slot'] ?? null;
                $slotLabel = [
                    'mañana'      => '🌅 Mejor por la mañana',
                    'tarde'       => '🌇 Mejor por la tarde',
                    'noche'       => '🌙 Visita nocturna',
                    'todo-el-dia' => '📅 Todo el día',
                ][$timeSlot] ?? null;
            ?>
            <article class="rt-evento <?= $highlight ? 'rt-evento--highlight' : '' ?>">
                <!-- Imagen thumbnail -->
                <?php if ($foto): ?>
                <div class="rt-evento__img-wrap">
                    <img
                        src="<?= htmlspecialchars($foto) ?>"
                        alt="<?= $nombre ?> — <?= $localidad ?>, <?= $prov ?>"
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
                            <?= $localidad ?>, <?= $prov ?>
                        </span>
                        <?php if ($slotLabel): ?>
                        <span class="rt-evento__hora"><?= $slotLabel ?></span>
                        <?php endif; ?>
                        <?php if ($highlight): ?>
                        <span class="rt-lugar-card__badge">⭐ Imprescindible</span>
                        <?php endif; ?>
                    </div>

                    <h3 class="rt-evento__title">
                        <a href="<?= htmlspecialchars($url) ?>"><?= $nombre ?></a>
                    </h3>

                    <?php if ($desc): ?>
                    <p class="rt-evento__desc"><?= $desc ?>…</p>
                    <?php endif; ?>

                    <div class="rt-evento__footer">
                        <span class="rt-evento__precio">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            <?= $precio ?>
                        </span>
                        <div class="rt-evento__ctas">
                            <a href="<?= htmlspecialchars($url) ?>" class="rt-btn rt-btn--evento">
                                Más info
                            </a>
                        </div>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="rt-section__cta">
            <a href="https://rutasrurales.io/lugares-interes-paginacion.html?provincia=<?= urlencode($provincia) ?>" class="rt-btn rt-btn--outline">
                Ver todos los lugares en <?= $provincia ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>
</section>
<?php
}

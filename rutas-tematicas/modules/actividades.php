<?php
/**
 * Módulo: Actividades turísticas de la ruta
 */

function renderActividades(array $actividades, array $ruta): void
{
    if (empty($actividades)) return;
    $provincia = htmlspecialchars($ruta['province'] ?? 'Soria');
?>
<section class="rt-section" id="actividades">
    <div class="rt-container">
        <div class="rt-section__header">
            <h2 class="rt-section__title">
                <span class="rt-section__icon" aria-hidden="true">🥾</span>
                Qué hacer en <?= $provincia ?>
            </h2>
            <p class="rt-section__subtitle">
                Actividades y experiencias para completar tu escapada perfecta
            </p>
        </div>

        <div class="rt-grid rt-grid--3">
            <?php foreach ($actividades as $act):
                $nombre    = htmlspecialchars($act['name'] ?? '');
                $localidad = htmlspecialchars($act['municipality'] ?? '');
                $prov      = htmlspecialchars($act['province'] ?? $provincia);
                $nota      = htmlspecialchars($act['editorial_note'] ?? '');
                $desc      = $nota ?: htmlspecialchars(substr(strip_tags($act['short_description'] ?? $act['description'] ?? ''), 0, 130));
                $precio    = htmlspecialchars($act['precio_display'] ?? 'Consultar precio');
                $duracion  = htmlspecialchars($act['duration'] ?? '');
                $dificultad = $act['difficulty_level'] ?? null;
                $url       = $act['url'] ?? '#';
                $foto      = $act['fotos'][0] ?? null;
                $highlight = !empty($act['is_highlight']);
                $booking   = $act['booking_url'] ?? null;
                $telefono  = $act['contact_phone'] ?? null;
                $timeSlot  = $act['time_slot'] ?? null;

                $difLabel = [
                    'facil'   => ['label' => 'Fácil',   'color' => 'green'],
                    'media'   => ['label' => 'Media',   'color' => 'orange'],
                    'dificil' => ['label' => 'Difícil', 'color' => 'red'],
                ][$dificultad] ?? null;

                $slotLabel = [
                    'mañana'      => '🌅 Mañana',
                    'tarde'       => '🌇 Tarde',
                    'noche'       => '🌙 Noche',
                    'todo-el-dia' => '📅 Todo el día',
                ][$timeSlot] ?? null;
            ?>
            <article class="rt-card rt-card--actividad <?= $highlight ? 'rt-card--highlight' : '' ?>">
                <?php if ($highlight): ?>
                <div class="rt-card__badge">⭐ Recomendado</div>
                <?php endif; ?>

                <div class="rt-card__img-wrap">
                    <?php if ($foto): ?>
                    <img
                        src="<?= htmlspecialchars($foto) ?>"
                        alt="<?= $nombre ?> en <?= $localidad ?>"
                        width="400" height="240"
                        loading="lazy"
                        decoding="async"
                        class="rt-card__img"
                        onerror="this.closest('.rt-card__img-wrap').style.display='none'"
                    >
                    <?php else: ?>
                    <div class="rt-card__img-placeholder" aria-hidden="true">🥾</div>
                    <?php endif; ?>
                    <?php if ($difLabel): ?>
                    <span class="rt-card__dificultad rt-card__dificultad--<?= $difLabel['color'] ?>">
                        <?= $difLabel['label'] ?>
                    </span>
                    <?php endif; ?>
                </div>

                <div class="rt-card__body">
                    <div class="rt-card__location">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?= $localidad ?>, <?= $prov ?>
                        <?php if ($slotLabel): ?>
                        · <span><?= $slotLabel ?></span>
                        <?php endif; ?>
                    </div>

                    <h3 class="rt-card__title">
                        <a href="<?= htmlspecialchars($url) ?>"><?= $nombre ?></a>
                    </h3>

                    <p class="rt-card__desc"><?= $desc ?>…</p>

                    <div class="rt-card__features">
                        <?php if ($duracion): ?>
                        <span class="rt-feature">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?= $duracion ?>
                        </span>
                        <?php endif; ?>
                        <span class="rt-feature rt-feature--precio"><?= $precio ?></span>
                    </div>

                    <div class="rt-card__ctas">
                        <?php if ($booking): ?>
                        <a href="<?= htmlspecialchars($booking) ?>" class="rt-btn rt-btn--card-primary" target="_blank" rel="noopener">
                            Reservar
                        </a>
                        <?php else: ?>
                        <a href="<?= htmlspecialchars($url) ?>" class="rt-btn rt-btn--card-primary">
                            Más info
                        </a>
                        <?php endif; ?>
                        <?php if ($telefono): ?>
                        <a href="tel:<?= preg_replace('/\s+/', '', $telefono) ?>" class="rt-btn rt-btn--card-tel" aria-label="Llamar para reservar <?= $nombre ?>">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            Llamar
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="rt-section__cta">
            <a href="https://rutasrurales.io/actividades-turisticas.html" class="rt-btn rt-btn--outline">
                Ver todas las actividades
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>
</section>
<?php
}

<?php
/**
 * Módulo: Itinerario día a día
 * Renderiza los días del itinerario con sus items agrupados
 */

function renderItinerario(array $ruta, array $alojamientos, array $lugares, array $actividades, array $eventos): void
{
    $dias = $ruta['itinerary_json'] ?? [];
    if (empty($dias)) return;

    // Agrupar items por día
    $itemsPorDia = [];
    foreach (array_merge($alojamientos, $lugares, $actividades) as $item) {
        $dia = (int)($item['day_number'] ?? 1);
        $itemsPorDia[$dia][] = $item;
    }

    $iconosTipo = [
        'name'        => ['alojamiento' => '🏠', 'lugar' => '🏛️', 'actividad' => '🥾', 'evento' => '🎭'],
        'label'       => ['alojamiento' => 'Alojamiento', 'lugar' => 'Lugar de interés', 'actividad' => 'Actividad', 'evento' => 'Evento'],
    ];
?>
<section class="rt-section" id="itinerario">
    <div class="rt-container">
        <div class="rt-section__header">
            <h2 class="rt-section__title">
                <span class="rt-section__icon" aria-hidden="true">🗓️</span>
                Itinerario día a día
            </h2>
            <p class="rt-section__subtitle">
                Tu escapada perfecta de <?= count($dias) ?> días, organizada para que no te pierdas nada
            </p>
        </div>

        <div class="rt-itinerario">
            <?php foreach ($dias as $i => $dia):
                $numDia    = (int)($dia['dia'] ?? ($i + 1));
                $fecha     = $dia['fecha'] ?? null;
                $tituloDia = htmlspecialchars($dia['titulo'] ?? "Día $numDia");
                $descDia   = htmlspecialchars($dia['descripcion'] ?? '');
                $iconoDia  = $dia['icono'] ?? '📍';
                $itemsDia  = $itemsPorDia[$numDia] ?? [];

                // Formatear fecha
                $fechaLabel = '';
                if ($fecha) {
                    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
                    if ($dt) {
                        setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish');
                        $fechaLabel = strftime('%A %d de %B', $dt->getTimestamp());
                        // Fallback si strftime no funciona
                        if (!$fechaLabel || $fechaLabel === $fecha) {
                            $dias_semana = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
                            $meses = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
                            $fechaLabel = $dias_semana[(int)$dt->format('w')] . ' ' . $dt->format('d') . ' de ' . $meses[(int)$dt->format('n')];
                        }
                        $fechaLabel = ucfirst($fechaLabel);
                    }
                }
            ?>
            <article class="rt-dia <?= $i === 0 ? 'rt-dia--first' : '' ?>" id="dia-<?= $numDia ?>">
                <!-- Marcador de día -->
                <div class="rt-dia__marker" aria-hidden="true">
                    <span class="rt-dia__num"><?= $numDia ?></span>
                </div>

                <div class="rt-dia__content">
                    <!-- Cabecera del día -->
                    <header class="rt-dia__header">
                        <div class="rt-dia__meta">
                            <?php if ($fechaLabel): ?>
                            <time class="rt-dia__fecha" datetime="<?= htmlspecialchars($fecha ?? '') ?>">
                                <?= htmlspecialchars($fechaLabel) ?>
                            </time>
                            <?php endif; ?>
                            <span class="rt-dia__icono" aria-hidden="true"><?= $iconoDia ?></span>
                        </div>
                        <h3 class="rt-dia__titulo"><?= $tituloDia ?></h3>
                        <p class="rt-dia__desc"><?= $descDia ?></p>
                    </header>

                    <!-- Items del día -->
                    <?php if (!empty($itemsDia)): ?>
                    <div class="rt-dia__items">
                        <?php foreach ($itemsDia as $item):
                            $tipo       = $item['item_type'] ?? 'lugar';
                            $icono      = $iconosTipo['name'][$tipo] ?? '📍';
                            $tipoLabel  = $iconosTipo['label'][$tipo] ?? 'Punto de interés';
                            $nombre     = htmlspecialchars($item['name'] ?? '');
                            $nota       = htmlspecialchars($item['editorial_note'] ?? $item['short_description'] ?? '');
                            $nota       = $nota ?: htmlspecialchars(substr(strip_tags($item['description'] ?? ''), 0, 120));
                            $url        = $item['url'] ?? '#';
                            $foto       = $item['fotos'][0] ?? null;
                            $timeSlot   = $item['time_slot'] ?? null;
                            $highlight  = !empty($item['is_highlight']);

                            $slotLabel = [
                                'mañana'      => '🌅 Mañana',
                                'tarde'       => '🌇 Tarde',
                                'noche'       => '🌙 Noche',
                                'todo-el-dia' => '📅 Todo el día',
                            ][$timeSlot] ?? null;
                        ?>
                        <div class="rt-dia__item <?= $highlight ? 'rt-dia__item--highlight' : '' ?>">
                            <?php if ($foto): ?>
                            <div class="rt-dia__item-img">
                                <img
                                    src="<?= htmlspecialchars($foto) ?>"
                                    alt="<?= $nombre ?>"
                                    width="120" height="80"
                                    loading="lazy"
                                    decoding="async"
                                    onerror="this.closest('.rt-dia__item-img').style.display='none'"
                                >
                            </div>
                            <?php endif; ?>
                            <div class="rt-dia__item-body">
                                <div class="rt-dia__item-meta">
                                    <span class="rt-tag rt-tag--<?= $tipo ?>"><?= $icono ?> <?= $tipoLabel ?></span>
                                    <?php if ($slotLabel): ?>
                                    <span class="rt-tag rt-tag--slot"><?= $slotLabel ?></span>
                                    <?php endif; ?>
                                    <?php if ($highlight): ?>
                                    <span class="rt-tag rt-tag--highlight">⭐ Imprescindible</span>
                                    <?php endif; ?>
                                </div>
                                <h4 class="rt-dia__item-nombre">
                                    <a href="<?= htmlspecialchars($url) ?>"><?= $nombre ?></a>
                                </h4>
                                <?php if ($nota): ?>
                                <p class="rt-dia__item-nota"><?= $nota ?>…</p>
                                <?php endif; ?>
                                <?php if ($tipo === 'alojamiento' && !empty($item['price_per_night'])): ?>
                                <p class="rt-dia__item-precio">
                                    Desde <strong><?= number_format((float)$item['price_per_night'], 0) ?>€/noche</strong>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div><!-- /.rt-itinerario -->
    </div>
</section>
<?php
}

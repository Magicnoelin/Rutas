<?php
/**
 * Módulo: FAQ + bloque SEO de texto enriquecido
 * 
 * PRIORIDAD 1: FAQs desde BD (tabla route_faqs) si existen
 * PRIORIDAD 2: Fallback automático con lógica temporal dinámica
 * 
 * El marcado JSON-LD Schema.org FAQPage se genera en schema.php
 */

function renderFaq(array $ruta, array $alojamientos, array $lugares, array $actividades, array $faqs = []): void
{
    $titulo   = htmlspecialchars($ruta['name'] ?? '');
    $prov     = htmlspecialchars($ruta['province'] ?? 'Soria');
    $duracion = (int)($ruta['duration_days'] ?? 3);
    $precio   = 350; // precio estimado editorial (no está en BD)

    // Extraer nombres de lugares y actividades para generar textos dinámicos
    $nombresLugares = array_slice(array_map(fn($l) => htmlspecialchars($l['name'] ?? $l['nombre'] ?? ''), $lugares), 0, 3);
    $nombresActividades = array_slice(array_map(fn($a) => htmlspecialchars($a['name'] ?? $a['nombre'] ?? ''), $actividades), 0, 2);

    // Construir cadenas dinámicas para los textos
    $lugaresDestacados = !empty($nombresLugares) ? implode(', ', $nombresLugares) : 'sus monumentos y parajes naturales';
    $actividadesDestacadas = !empty($nombresActividades) ? ' realizar ' . implode(' o ', $nombresActividades) : 'hacer senderismo y rutas por la zona';
    $gastronomiaLocal = ($prov === 'Soria') 
        ? 'con torreznos, migas y vinos de la tierra' 
        : 'con sus platos típicos y productos locales de proximidad';

    // ── Determinar época del año automáticamente ──────────────────────────
    $mes_actual = (int)date('m');
    $evento_proximo = match(true) {
        $mes_actual >= 3 && $mes_actual <= 5  => 'el puente de mayo o primavera',
        $mes_actual >= 6 && $mes_actual <= 8  => 'las vacaciones de verano',
        $mes_actual >= 9 && $mes_actual <= 11 => 'el puente de diciembre u otoño',
        default                               => 'las vacaciones de Navidad o invierno',
    };
    $mes_nombre = match($mes_actual) {
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        default => '',
    };

    // ── PRIORIDAD 1: FAQs desde BD ────────────────────────────────────────
    if (!empty($faqs)) {
        $faqsRender = [];
        foreach ($faqs as $f) {
            $faqsRender[] = [
                'q' => $f['question'],
                'a' => $f['answer'],
            ];
        }
    } else {
        // ── PRIORIDAD 2: Fallback automático con lógica temporal ──────────
        $faqsRender = [
            [
                'q' => '¿Qué hacer en ' . $prov . ' durante ' . $evento_proximo . '?',
                'a' => 'En ' . $prov . ' durante ' . $evento_proximo . ' tienes un sinfín de opciones: visitar lugares emblemáticos como ' . $lugaresDestacados . ',' . $actividadesDestacadas . ' y disfrutar de la gastronomía local ' . $gastronomiaLocal . '. Es uno de los destinos rurales más auténticos y menos masificados de España.',
            ],
            [
                'q' => '¿Cuántos días necesito para visitar ' . $prov . '?',
                'a' => 'Con ' . $duracion . ' días tienes tiempo suficiente para disfrutar de los principales atractivos de ' . $prov . '. El itinerario recomendado incluye tiempo para descubrir su rico patrimonio histórico, explorar sus parajes naturales y sumergirse en la cultura local.',
            ],
            [
                'q' => '¿Hay casas rurales disponibles en ' . $prov . ' para ' . $evento_proximo . '?',
                'a' => 'Sí, en rutasrurales.io encontrarás casas rurales y apartamentos turísticos en ' . $prov . ' disponibles para ' . $evento_proximo . '. Te recomendamos reservar con al menos 3-4 semanas de antelación, ya que es una de las fechas más demandadas del año en turismo rural.',
            ],
            [
                'q' => '¿Cuánto cuesta una escapada rural a ' . $prov . '?',
                'a' => 'Una escapada de ' . $duracion . ' días a ' . $prov . ' tiene un coste estimado de ' . number_format((float)$precio, 0, ',', '.') . '€ por persona, incluyendo alojamiento en casa rural, actividades y gastronomía local. Los precios varían según el tipo de alojamiento y las actividades elegidas.',
            ],
            [
                'q' => '¿Es ' . $prov . ' un buen destino para ' . $evento_proximo . ' con niños?',
                'a' => $prov . ' es un destino ideal para familias con niños durante ' . $evento_proximo . '. Sus rutas de senderismo de dificultad baja, los centros de interpretación de la zona y los amplios espacios naturales hacen de esta provincia un lugar perfecto para el turismo familiar. Además, es una zona tranquila y segura, sin las aglomeraciones de otros destinos.',
            ],
            [
                'q' => '¿Qué tiempo hace en ' . $prov . ' en ' . $mes_nombre . '?',
                'a' => 'En ' . $mes_nombre . ', ' . $prov . ' disfruta de un clima con temperaturas agradables ideales para el senderismo y las actividades al aire libre. Se recomienda llevar ropa de abrigo y calzado adecuado para el campo.',
            ],
        ];

        // Añadir FAQ dinámica con lugares destacados
        if (!empty($lugares) && !empty($nombresLugares)) {
            $faqsRender[] = [
                'q' => '¿Cuáles son los lugares imprescindibles de ' . $prov . '?',
                'a' => 'Los lugares imprescindibles de ' . $prov . ' que no puedes perderte son: ' . $lugaresDestacados . '. Todos ellos están incluidos en este itinerario y puedes encontrar información detallada de cada uno en rutasrurales.io.',
            ];
        }
    }
?>
<section class="rt-section" id="faq">
    <div class="rt-container">
        <div class="rt-section__header">
            <h2 class="rt-section__title">
                <span class="rt-section__icon" aria-hidden="true">❓</span>
                Preguntas frecuentes
            </h2>
            <p class="rt-section__subtitle">
                Todo lo que necesitas saber antes de tu escapada a <?= $prov ?>
            </p>
        </div>

        <div class="rt-faq">
            <?php foreach ($faqsRender as $i => $faq): ?>
            <details class="rt-faq__item" <?= $i === 0 ? 'open' : '' ?>>
                <summary class="rt-faq__question">
                    <span><?= htmlspecialchars($faq['q']) ?></span>
                    <svg class="rt-faq__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="rt-faq__answer">
                    <p><?= htmlspecialchars($faq['a']) ?></p>
                </div>
            </details>
            <?php endforeach; ?>
        </div>

        <!-- Bloque SEO de texto enriquecido -->
        <div class="rt-seo-text">
            <h2>Escapada rural a <?= $prov ?> para <?= $evento_proximo ?></h2>
            <p>
                <strong><?= ucfirst($evento_proximo) ?></strong> es una de las mejores oportunidades del año para escaparse al campo y desconectar de la rutina. <strong><?= $prov ?></strong>, con su riqueza paisajística, castillos medievales y cielos despejados, se ha convertido en uno de los destinos favoritos para el <strong>turismo rural</strong>.
            </p>
            <p>
                A diferencia de otros destinos más masificados, <?= $prov ?> ofrece una experiencia auténtica y tranquila. Sus <strong>casas rurales</strong> tienen encanto propio, su gastronomía destaca por la calidad de sus productos autóctonos y sus parajes naturales invitan al descanso y la desconexión total.
            </p>
            <p>
                Este itinerario de <strong><?= $duracion ?> días</strong> ha sido diseñado para que puedas disfrutar de lo mejor de <?= $prov ?> sin prisas: visitando <strong><?= $lugaresDestacados ?></strong>, participando en actividades locales, conociendo su cultura y saboreando su gastronomía en los mejores establecimientos de la provincia.
            </p>
            <p>
                <strong>Reserva tu alojamiento cuanto antes</strong>: <?= $evento_proximo ?> es una de las fechas más demandadas del año y las mejores casas rurales de <?= $prov ?> se agotan semanas antes.
            </p>
        </div>
    </div>
</section>
<?php
}

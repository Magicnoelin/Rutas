<?php
/**
 * Módulo: FAQ + bloque SEO de texto enriquecido
 * Las preguntas se generan dinámicamente según la ruta
 */

function renderFaq(array $ruta, array $alojamientos, array $lugares, array $actividades): void
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

    // Construir preguntas dinámicas
    $faqs = [
        [
            'q' => '¿Qué hacer en ' . $prov . ' el puente del 1 de mayo?',
            'a' => 'En ' . $prov . ' el puente del 1 de mayo tienes un sinfín de opciones: visitar lugares emblemáticos como ' . $lugaresDestacados . ',' . $actividadesDestacadas . ' y disfrutar de la gastronomía local ' . $gastronomiaLocal . '. Es uno de los destinos rurales más auténticos y menos masificados de España.',
        ],
        [
            'q' => '¿Cuántos días necesito para visitar ' . $prov . ' el puente de mayo?',
            'a' => 'Con ' . $duracion . ' días (del 29 de abril al 2 de mayo) tienes tiempo suficiente para disfrutar de los principales atractivos de ' . $prov . '. El itinerario recomendado incluye tiempo para descubrir su rico patrimonio histórico, explorar sus parajes naturales y sumergirse en la cultura local.',
        ],
        [
            'q' => '¿Hay casas rurales disponibles en ' . $prov . ' para el puente del 1 de mayo?',
            'a' => 'Sí, en rutasrurales.io encontrarás casas rurales y apartamentos turísticos en ' . $prov . ' disponibles para el puente del 1 de mayo. Te recomendamos reservar con al menos 3-4 semanas de antelación, ya que el puente de mayo es una de las fechas más demandadas del año en turismo rural.',
        ],
        [
            'q' => '¿Cuánto cuesta una escapada rural a ' . $prov . ' el puente de mayo?',
            'a' => 'Una escapada de ' . $duracion . ' días a ' . $prov . ' el puente de mayo tiene un coste estimado de ' . number_format((float)$precio, 0, ',', '.') . '€ por persona, incluyendo alojamiento en casa rural (2 noches), actividades y gastronomía local. Los precios varían según el tipo de alojamiento y las actividades elegidas.',
        ],
        [
            'q' => '¿Es ' . $prov . ' un buen destino para el puente del 1 de mayo con niños?',
            'a' => $prov . ' es un destino ideal para familias con niños el puente de mayo. Sus rutas de senderismo de dificultad baja, los centros de interpretación de la zona y los amplios espacios naturales hacen de esta provincia un lugar perfecto para el turismo familiar. Además, es una zona tranquila y segura, sin las aglomeraciones de otros destinos.',
        ],
        [
            'q' => '¿Qué tiempo hace en ' . $prov . ' en mayo?',
            'a' => 'En mayo, ' . $prov . ' disfruta de un clima primaveral con temperaturas agradables entre 10°C y 20°C. Es la época ideal para el senderismo y las actividades al aire libre. Puede haber algún día de lluvia, por lo que se recomienda llevar ropa de abrigo y calzado adecuado para el campo.',
        ],
    ];

    // Añadir FAQ dinámica con lugares destacados
    if (!empty($lugares) && count($faqs) < 7) {
        if (!empty($nombresLugares)) {
            $faqs[] = [
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
            <?php foreach ($faqs as $i => $faq): ?>
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
            <h2>Escapada rural a <?= $prov ?> el puente del 1 de mayo 2026</h2>
            <p>
                El <strong>puente del 1 de mayo</strong> es una de las mejores oportunidades del año para escaparse al campo y desconectar de la rutina. <strong><?= $prov ?></strong>, con su riqueza paisajística, castillos medievales y cielos despejados, se ha convertido en uno de los destinos favoritos para el <strong>turismo rural</strong>.
            </p>
            <p>
                A diferencia de otros destinos más masificados, <?= $prov ?> ofrece una experiencia auténtica y tranquila. Sus <strong>casas rurales</strong> tienen encanto propio, su gastronomía destaca por la calidad de sus productos autóctonos y sus parajes naturales invitan al descanso y la desconexión total.
            </p>
            <p>
                Este itinerario de <strong><?= $duracion ?> días</strong> ha sido diseñado para que puedas disfrutar de lo mejor de <?= $prov ?> sin prisas: visitando <strong><?= $lugaresDestacados ?></strong>, participando en actividades locales, conociendo su cultura y saboreando su gastronomía en los mejores establecimientos de la provincia.
            </p>
            <p>
                <strong>Reserva tu alojamiento cuanto antes</strong>: el puente del 1 de mayo es una de las fechas más demandadas del año y las mejores casas rurales de <?= $prov ?> se agotan semanas antes.
            </p>
        </div>
    </div>
</section>
<?php
}

<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  AUTORIDAD-SEO.PHP — Sección de texto semántico para autoridad temática
 *
 *  Propósito SEO:
 *    - Señalizar a Google las entidades semánticas clave del dominio
 *    - Incluir enlaces internos a páginas de lugares y verticales
 *    - Texto optimizado para las queries informacionales de turismo rural
 *    - Keywords de long-tail: enoturismo, gastronomía, escapadas, Castilla
 * ════════════════════════════════════════════════════════════════════════════
 *
 * @param array $ctx  [lang, t]
 */
function renderAutoridadSeo(array $ctx): void {
    $lang = $ctx['lang'];
    $t    = $ctx['t'];
    $base = 'https://rutasrurales.io';
    ?>
<!-- ══════════════════════════════════ SECCIÓN AUTORIDAD ═════════════════════ -->
<section class="hub-section hub-authority" id="sobre-turismo-rural" aria-labelledby="auth-heading">
<div class="hub-container">

    <div class="hub-authority__inner">

        <!-- Columna principal: texto SEO -->
        <div class="hub-authority__text">
            <h2 class="hub-authority__h2" id="auth-heading">
                <?= htmlspecialchars($t['auth_h2']) ?>
            </h2>

            <p class="hub-authority__p">
                <?= $t['auth_p1'] /* HTML seguro: definido en translations.php */ ?>
            </p>

            <p class="hub-authority__p">
                <?= $t['auth_p2'] ?>
            </p>

            <p class="hub-authority__p">
                <?= $t['auth_p3'] ?>
            </p>

            <!-- Keywords semánticas visibles (no spam: son útiles para el usuario) -->
            <p class="hub-authority__keywords" aria-label="Destinos y temáticas">
                <small><?= htmlspecialchars($t['auth_keywords']) ?></small>
            </p>
        </div>

        <!-- Columna lateral: tarjetas de acceso rápido a verticales -->
        <aside class="hub-authority__sidebar" aria-label="Acceso rápido a secciones">
            <ul class="hub-quick-links" role="list">
                <li>
                    <a href="<?= $base ?>/alojamientos-turisticos"
                       class="hub-quick-link"
                       aria-label="<?= htmlspecialchars($t['footer_nav_stays']) ?>">
                        <span class="hub-quick-link__icon" aria-hidden="true">🏡</span>
                        <div>
                            <strong class="hub-quick-link__title">
                                <?= htmlspecialchars($t['footer_nav_stays']) ?>
                            </strong>
                            <span class="hub-quick-link__desc">
                                <?php if ($lang === 'es'): ?>Casas, apartamentos y hoteles rurales con encanto
                                <?php elseif ($lang === 'en'): ?>Cottages, apartments and charming rural hotels
                                <?php elseif ($lang === 'fr'): ?>Maisons, appartements et hôtels ruraux de charme
                                <?php elseif ($lang === 'de'): ?>Häuser, Apartments und charmante Landhotels
                                <?php else: ?>民宿、公寓和迷人乡村酒店<?php endif; ?>
                            </span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>/eventos-culturales-paginacion.html"
                       class="hub-quick-link"
                       aria-label="<?= htmlspecialchars($t['footer_nav_events']) ?>">
                        <span class="hub-quick-link__icon" aria-hidden="true">🎭</span>
                        <div>
                            <strong class="hub-quick-link__title">
                                <?= htmlspecialchars($t['footer_nav_events']) ?>
                            </strong>
                            <span class="hub-quick-link__desc">
                                <?php if ($lang === 'es'): ?>Festivales, tradiciones y agenda cultural
                                <?php elseif ($lang === 'en'): ?>Festivals, traditions and cultural calendar
                                <?php elseif ($lang === 'fr'): ?>Festivals, traditions et agenda culturel
                                <?php elseif ($lang === 'de'): ?>Festivals, Traditionen und Kulturkalender
                                <?php else: ?>节日、传统和文化日历<?php endif; ?>
                            </span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>/lugares-de-interes"
                       class="hub-quick-link"
                       aria-label="<?= htmlspecialchars($t['footer_nav_places']) ?>">
                        <span class="hub-quick-link__icon" aria-hidden="true">🏛️</span>
                        <div>
                            <strong class="hub-quick-link__title">
                                <?= htmlspecialchars($t['footer_nav_places']) ?>
                            </strong>
                            <span class="hub-quick-link__desc">
                                <?php if ($lang === 'es'): ?>Patrimonio, naturaleza y monumentos
                                <?php elseif ($lang === 'en'): ?>Heritage, nature and monuments
                                <?php elseif ($lang === 'fr'): ?>Patrimoine, nature et monuments
                                <?php elseif ($lang === 'de'): ?>Kulturerbe, Natur und Denkmäler
                                <?php else: ?>遗产、自然和纪念碑<?php endif; ?>
                            </span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>/actividades-turisticas"
                       class="hub-quick-link"
                       aria-label="<?= htmlspecialchars($t['footer_nav_activ']) ?>">
                        <span class="hub-quick-link__icon" aria-hidden="true">🥾</span>
                        <div>
                            <strong class="hub-quick-link__title">
                                <?= htmlspecialchars($t['footer_nav_activ']) ?>
                            </strong>
                            <span class="hub-quick-link__desc">
                                <?php if ($lang === 'es'): ?>Senderismo, rutas y experiencias al aire libre
                                <?php elseif ($lang === 'en'): ?>Hiking, trails and outdoor experiences
                                <?php elseif ($lang === 'fr'): ?>Randonnée, itinéraires et expériences en plein air
                                <?php elseif ($lang === 'de'): ?>Wandern, Routen und Outdoor-Erlebnisse
                                <?php else: ?>徒步、路线和户外体验<?php endif; ?>
                            </span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>/rutas/"
                       class="hub-quick-link"
                       aria-label="Rutas temáticas">
                        <span class="hub-quick-link__icon" aria-hidden="true">🗺️</span>
                        <div>
                            <strong class="hub-quick-link__title">
                                <?php if ($lang === 'es'): ?>Rutas Temáticas
                                <?php elseif ($lang === 'en'): ?>Themed Routes
                                <?php elseif ($lang === 'fr'): ?>Itinéraires Thématiques
                                <?php elseif ($lang === 'de'): ?>Themenrouten
                                <?php else: ?>主题路线<?php endif; ?>
                            </strong>
                            <span class="hub-quick-link__desc">
                                <?php if ($lang === 'es'): ?>Del Románico, del Vino, del Cid y más
                                <?php elseif ($lang === 'en'): ?>Romanesque, Wine, El Cid routes and more
                                <?php elseif ($lang === 'fr'): ?>Roman, du Vin, du Cid et plus encore
                                <?php elseif ($lang === 'de'): ?>Romanik, Wein, El Cid und mehr
                                <?php else: ?>罗马式、葡萄酒、熙德路线等<?php endif; ?>
                            </span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </li>
            </ul>
        </aside>

    </div><!-- /.hub-authority__inner -->
</div><!-- /.hub-container -->
</section>
    <?php
}

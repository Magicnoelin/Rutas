<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  GENERADOR DE SITEMAP — Landings Long-Tail de Eventos Culturales
 *  rutasrurales.io/eventos-landing/generar-sitemap.php
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Genera SOLO las URLs con contenido real (≥1 evento activo y aprobado).
 *  Nunca incluye páginas vacías → evita soft-404 en Google Search Console.
 *
 *  USO:
 *    php eventos-landing/generar-sitemap.php
 *    → Escribe sitemap-eventos-landing.xml en la raíz del proyecto
 *
 *    Con parámetro GET (desde el servidor):
 *    https://rutasrurales.io/eventos-landing/generar-sitemap.php?token=TU_TOKEN
 *    → Devuelve el XML directamente (útil para auto-regeneración con cron)
 *
 *  ESTRATEGIA:
 *    1. Para cada PROVINCIA → verificar si hay eventos activos
 *    2. Para cada FILTRO INDIVIDUAL → verificar por provincia
 *    3. Para combinaciones (filtro+provincia) más frecuentes → verificar
 *    4. URLs de solo-filtro (sin provincia) → verificar globalmente
 *    Excluye: filtros con 'sitemap'=>false (ej: este-mes)
 *
 *  PRIORIDADES SEO:
 *    1.0 — Solo provincia (todas las categorías)
 *    0.8 — Filtro + provincia (long-tail)
 *    0.6 — Solo filtro (sin provincia)
 *
 *  FRECUENCIAS:
 *    Eventos → weekly (agenda cambia con frecuencia)
 * ════════════════════════════════════════════════════════════════════════════
 */

// ── Seguridad: token para llamadas HTTP ───────────────────────────────────────
$IS_CLI = (php_sapi_name() === 'cli');

if (!$IS_CLI) {
    $token   = $_GET['token'] ?? '';
    $envToken = getenv('SITEMAP_TOKEN') ?: 'rutasrurales_sitemap_2026';
    if (!hash_equals($envToken, $token)) {
        http_response_code(403);
        die('Forbidden');
    }

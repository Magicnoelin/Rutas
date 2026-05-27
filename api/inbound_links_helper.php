<?php
/**
 * HELPER: Procesado de Inbound Links
 * ====================================
 * Reemplaza la PRIMERA ocurrencia de cada keyword activa en un texto
 * por un enlace HTML interno con título SEO.
 *
 * Características:
 *  - Case-insensitive (respeta mayúsculas originales del texto)
 *  - No rompe tags HTML existentes (nunca reemplaza dentro de <a ...>)
 *  - Una sola query a BD (cache en memoria para la misma request)
 *  - Links internos limpios: sin rel="nofollow", misma pestaña
 *  - Ordena por prioridad (menor número = se aplica primero)
 *
 * Uso:
 *   require_once __DIR__ . '/inbound_links_helper.php';
 *   $texto_con_links = procesarInboundLinks($texto_original, $pdo);
 */

// Cache en memoria para no repetir la query en la misma request
$_inbound_links_cache = null;

/**
 * Obtiene todas las keywords activas ordenadas por prioridad.
 * Cachea el resultado en memoria (una sola query por request).
 *
 * @param PDO $pdo
 * @return array  [ ['keyword'=>..., 'url'=>..., 'link_title'=>...], ... ]
 */
function getInboundLinks(PDO $pdo): array
{
    global $_inbound_links_cache;

    if ($_inbound_links_cache !== null) {
        return $_inbound_links_cache;
    }

    try {
        $stmt = $pdo->query("
            SELECT keyword, url, link_title
            FROM inbound_links
            WHERE is_active = 1
            ORDER BY priority ASC, LENGTH(keyword) DESC
        ");
        $_inbound_links_cache = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Si la tabla aún no existe, devolver array vacío sin romper nada
        error_log('[inbound_links_helper] Error cargando keywords: ' . $e->getMessage());
        $_inbound_links_cache = [];
    }

    return $_inbound_links_cache;
}

/**
 * Procesa un texto HTML e inserta inbound links.
 *
 * Estrategia:
 *   1. Divide el texto en "tokens": etiquetas HTML (<...>) y texto plano
 *   2. Solo procesa los tokens de texto plano
 *   3. En cada token de texto plano, reemplaza la PRIMERA ocurrencia global
 *      de cada keyword (la primera vez que aparece en todo el texto)
 *   4. Una keyword ya enlazada no se vuelve a enlazar
 *
 * @param string|null $texto  Texto HTML a procesar
 * @param PDO         $pdo    Conexión a base de datos
 * @return string             Texto con inbound links insertados
 */
function procesarInboundLinks(?string $texto, PDO $pdo, array &$yaEnlazadas = []): string
{
    if (empty($texto)) {
        return (string)$texto;
    }

    $keywords = getInboundLinks($pdo);

    if (empty($keywords)) {
        return $texto;
    }

    // Tokenizar: separar etiquetas HTML del texto plano
    // Patrón: cualquier cosa que no sea una etiqueta | una etiqueta completa
    $tokens = preg_split('/(<[^>]+>)/s', $texto, -1, PREG_SPLIT_DELIM_CAPTURE);

    if ($tokens === false) {
        return $texto;
    }

    $resultado = '';
    $dentroDeEnlace = false; // Rastrear si estamos dentro de <a ...>...</a>

    foreach ($tokens as $token) {
        // Si es una etiqueta HTML
        if (isset($token[0]) && $token[0] === '<') {
            $tagLower = strtolower(trim($token));
            if (strncmp($tagLower, '<a ', 3) === 0 || $tagLower === '<a>') {
                $dentroDeEnlace = true;
            } elseif ($tagLower === '</a>') {
                $dentroDeEnlace = false;
            }
            $resultado .= $token;
            continue;
        }

        // Es texto plano
        if ($dentroDeEnlace) {
            // Dentro de <a>: no tocar
            $resultado .= $token;
            continue;
        }

        // Aplicar keywords sobre el texto plano
        foreach ($keywords as $entry) {
            $kw    = $entry['keyword'];
            $kwKey = mb_strtolower($kw, 'UTF-8');

            // Si ya enlazamos esta keyword, saltarla
            if (isset($yaEnlazadas[$kwKey])) {
                continue;
            }

            // Buscar la keyword de forma case-insensitive
            // Usamos preg_replace_callback con límite 1 para reemplazar solo la primera ocurrencia en este token
            $pattern = '/(' . preg_quote($kw, '/') . ')/iu';

            $reemplazado = false;
            $nuevoToken = preg_replace_callback(
                $pattern,
                function ($m) use ($entry, &$reemplazado) {
                    $reemplazado = true;
                    // Normalizar URL: siempre con / inicial para evitar rutas relativas rotas
                    $rawUrl = $entry['url'];
                    if (!empty($rawUrl) && $rawUrl[0] !== '/' && !preg_match('#^https?://#i', $rawUrl)) {
                        $rawUrl = '/' . $rawUrl;
                    }
                    $url   = htmlspecialchars($rawUrl, ENT_QUOTES, 'UTF-8');
                    $title = htmlspecialchars($entry['link_title'], ENT_QUOTES, 'UTF-8');
                    // Preservar el texto tal como aparece en el documento original
                    return '<a href="' . $url . '" title="' . $title . '">' . $m[1] . '</a>';
                },
                $token,
                1 // Límite: solo la primera ocurrencia en este token
            );

            if ($reemplazado) {
                $yaEnlazadas[$kwKey] = true;
                // ═══ IMPORTANTE: Al crear un enlace dentro de un token de texto plano,
                // ese token ahora contiene HTML (<a>). Debemos re-tokenizar el resultado
                // para evitar que keywords posteriores coincidan dentro de los atributos
                // del nuevo enlace (ej: "soria" dentro de href="/evento/...soria...").
                // Procesamos el nuevo token recursivamente y luego continuamos.
                $resultado .= procesarInboundLinks($nuevoToken, $pdo, $yaEnlazadas);
                continue 2; // Salta al siguiente token principal
            }
        }

        $resultado .= $token;
    }

    return $resultado;
}

/**
 * Regenera description_linked para TODOS los registros de una tabla.
 * Útil cuando se añaden nuevas keywords y queremos reprocesar el contenido existente.
 *
 * @param PDO    $pdo
 * @param string $tabla   'cultural_events' | 'accommodations'
 * @return array          ['procesados' => N, 'errores' => N]
 */
function regenerarInboundLinksTodos(PDO $pdo, string $tabla): array
{
    $tablasPermitidas = ['cultural_events', 'accommodations', 'places_of_interest'];
    if (!in_array($tabla, $tablasPermitidas, true)) {
        return ['procesados' => 0, 'errores' => 1, 'mensaje' => 'Tabla no permitida'];
    }

    // Invalidar cache para forzar recarga
    global $_inbound_links_cache;
    $_inbound_links_cache = null;

    $procesados = 0;
    $errores    = 0;

    try {
        $stmt = $pdo->query("SELECT id, description FROM `{$tabla}` WHERE description IS NOT NULL AND description != ''");
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filas as $fila) {
            // Invalidar cache por cada fila para resetear el registro yaEnlazadas
            $_inbound_links_cache = null;
            $linked = procesarInboundLinks($fila['description'], $pdo);

            $upd = $pdo->prepare("UPDATE `{$tabla}` SET description_linked = ? WHERE id = ?");
            $upd->execute([$linked, $fila['id']]);
            $procesados++;
        }
    } catch (Exception $e) {
        error_log('[inbound_links_helper] Error en regenerarInboundLinksTodos: ' . $e->getMessage());
        $errores++;
    }

    return ['procesados' => $procesados, 'errores' => $errores];
}

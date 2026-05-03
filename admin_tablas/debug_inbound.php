<?php
/**
 * DIAGNÓSTICO: Sistema Inbound Links
 * Acceder a: https://rutasrurales.io/admin_tablas/debug_inbound.php?slug=fiestas-san-juan-soria-2026
 * Borrar después de usarlo.
 */
include 'db.php';
$slug = $_GET['slug'] ?? 'fiestas-san-juan-soria-2026';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Debug Inbound Links</title>
<style>
body{font-family:monospace;background:#f5f5f5;padding:24px;font-size:13px;}
h2{color:#2F5233;border-bottom:2px solid #81C784;padding-bottom:6px;margin:20px 0 10px;}
.ok{color:#2e7d32;font-weight:700}
.err{color:#c62828;font-weight:700}
.warn{color:#e65100;font-weight:700}
pre{background:#fff;border:1px solid #ddd;padding:12px;border-radius:6px;overflow:auto;white-space:pre-wrap;word-break:break-all;}
table{border-collapse:collapse;width:100%;background:#fff;}
th,td{padding:7px 10px;border:1px solid #ddd;text-align:left;}
th{background:#e8f5e9;}
</style>
</head>
<body>
<h1>🔍 Debug Inbound Links</h1>
<p>Slug analizado: <strong><?php echo htmlspecialchars($slug); ?></strong></p>

<?php

// ── 1. ¿Existe la tabla inbound_links? ────────────────────────────────────────
echo '<h2>1. Tabla inbound_links</h2>';
try {
    $res = $pdo->query("SHOW TABLES LIKE 'inbound_links'")->fetchAll();
    if ($res) {
        echo '<span class="ok">✅ La tabla inbound_links EXISTE</span><br>';
        $links = $pdo->query("SELECT * FROM inbound_links ORDER BY priority ASC")->fetchAll(PDO::FETCH_ASSOC);
        echo '<p>Keywords guardadas (' . count($links) . '):</p>';
        if ($links) {
            echo '<table><tr><th>ID</th><th>Keyword</th><th>URL</th><th>Activo</th><th>Prioridad</th></tr>';
            foreach ($links as $l) {
                echo '<tr><td>'.$l['id'].'</td><td>'.htmlspecialchars($l['keyword']).'</td><td>'.htmlspecialchars($l['url']).'</td><td>'.($l['is_active']?'✅':'❌').'</td><td>'.$l['priority'].'</td></tr>';
            }
            echo '</table>';
        } else {
            echo '<span class="warn">⚠️ Tabla vacía</span>';
        }
    } else {
        echo '<span class="err">❌ La tabla inbound_links NO EXISTE.</span>';
    }
} catch(Exception $e) {
    echo '<span class="err">❌ Error: '.htmlspecialchars($e->getMessage()).'</span>';
}

// ── 2. ¿Existe la columna description_linked en cultural_events? ──────────────
echo '<h2>2. Columna description_linked en cultural_events</h2>';
try {
    $cols = $pdo->query("SHOW COLUMNS FROM cultural_events LIKE 'description_linked'")->fetchAll();
    echo $cols
        ? '<span class="ok">✅ Columna description_linked EXISTE</span>'
        : '<span class="err">❌ Columna description_linked NO EXISTE — ejecuta el ALTER TABLE del SQL</span>';
} catch(Exception $e) {
    echo '<span class="err">❌ Error: '.htmlspecialchars($e->getMessage()).'</span>';
}

// ── 3. Datos del evento ────────────────────────────────────────────────────────
echo '<h2>3. Evento: <em>' . htmlspecialchars($slug) . '</em></h2>';
try {
    $stmt = $pdo->prepare("SELECT id, name, slug, is_active,
        LEFT(description, 500) AS desc_preview,
        LEFT(description_linked, 800) AS desc_linked_preview,
        LENGTH(description) AS desc_len,
        LENGTH(description_linked) AS desc_linked_len
        FROM cultural_events WHERE slug = ?");
    $stmt->execute([$slug]);
    $ev = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ev) {
        echo '<span class="err">❌ Evento NO encontrado con slug "'.htmlspecialchars($slug).'"</span>';
    } else {
        echo '<table>';
        echo '<tr><th>ID</th><td>'.$ev['id'].'</td></tr>';
        echo '<tr><th>Nombre</th><td>'.htmlspecialchars($ev['name']).'</td></tr>';
        echo '<tr><th>Activo</th><td>'.($ev['is_active']?'<span class="ok">Sí</span>':'<span class="err">No</span>').'</td></tr>';
        echo '<tr><th>description (longitud)</th><td>'.$ev['desc_len'].' caracteres</td></tr>';
        echo '<tr><th>description_linked (longitud)</th><td>'.($ev['desc_linked_len'] ?? '<span class="err">NULL</span>').' caracteres</td></tr>';
        echo '</table>';

        echo '<h2>3a. Primeros 500 chars de description</h2>';
        echo '<pre>'.htmlspecialchars($ev['desc_preview'] ?? '(vacío)').'</pre>';

        echo '<h2>3b. Primeros 800 chars de description_linked</h2>';
        if (empty($ev['desc_linked_preview'])) {
            echo '<span class="err">❌ description_linked está VACÍO o NULL.</span>';
        } else {
            echo '<pre>'.htmlspecialchars($ev['desc_linked_preview']).'</pre>';
        }

        // ── 4. ¿Aparece la keyword en la descripción? ─────────────────────
        echo '<h2>4. Búsqueda de keywords en description</h2>';
        try {
            $allLinks = $pdo->query("SELECT keyword FROM inbound_links WHERE is_active=1")->fetchAll(PDO::FETCH_COLUMN);
            if ($allLinks) {
                $stmtFull = $pdo->prepare("SELECT description FROM cultural_events WHERE slug=?");
                $stmtFull->execute([$slug]);
                $fullDesc = $stmtFull->fetchColumn() ?? '';
                foreach ($allLinks as $kw) {
                    $found = (stripos($fullDesc, $kw) !== false);
                    $icon = $found ? '<span class="ok">✅ ENCONTRADA</span>' : '<span class="err">❌ NO encontrada</span>';
                    echo "Keyword <strong>".htmlspecialchars($kw)."</strong>: $icon<br>";
                }
            } else {
                echo '<span class="warn">⚠️ No hay keywords activas</span>';
            }
        } catch(Exception $e) {
            echo '<span class="err">Error: '.htmlspecialchars($e->getMessage()).'</span>';
        }
    }
} catch(Exception $e) {
    echo '<span class="err">❌ Error: '.htmlspecialchars($e->getMessage()).'</span>';
}

// ── 5. Test en vivo del helper ─────────────────────────────────────────────────
echo '<h2>5. Test en vivo: procesarInboundLinks()</h2>';
try {
    require_once __DIR__ . '/../api/inbound_links_helper.php';
    $stmtTest = $pdo->prepare("SELECT description FROM cultural_events WHERE slug=?");
    $stmtTest->execute([$slug]);
    $rawDesc = $stmtTest->fetchColumn();
    if ($rawDesc) {
        global $_inbound_links_cache;
        $_inbound_links_cache = null;
        $result = procesarInboundLinks($rawDesc, $pdo);
        echo ($result === $rawDesc)
            ? '<span class="warn">⚠️ Resultado idéntico — keyword no encontrada en el texto</span>'
            : '<span class="ok">✅ Se insertaron links correctamente.</span>';
        echo '<h3>Resultado (primeros 1000 chars):</h3>';
        echo '<pre>'.htmlspecialchars(substr($result, 0, 1000)).'</pre>';
    } else {
        echo '<span class="err">No se pudo leer description</span>';
    }
} catch(Exception $e) {
    echo '<span class="err">Error: '.htmlspecialchars($e->getMessage()).'</span>';
}

// ── 6. ¿El evento-detalle.php del SERVIDOR tiene description_linked? ──────────
echo '<h2>6. Versión de evento-detalle.php en el servidor</h2>';
$edFile = __DIR__ . '/../evento-detalle.php';
if (file_exists($edFile)) {
    $edContent = file_get_contents($edFile);
    $hasCol  = (strpos($edContent, 'description_linked') !== false);
    $hasEcho = (strpos($edContent, "description_linked") !== false && strpos($edContent, 'echo !empty') !== false);
    echo $hasCol
        ? '<span class="ok">✅ evento-detalle.php contiene "description_linked" → archivo actualizado</span><br>'
        : '<span class="err">❌ evento-detalle.php NO contiene "description_linked" → ARCHIVO DESACTUALIZADO en servidor. Súbelo desde git.</span><br>';
    echo $hasEcho
        ? '<span class="ok">✅ El echo con fallback está presente</span>'
        : '<span class="err">❌ El echo con fallback NO está presente</span>';
} else {
    echo '<span class="err">❌ No se encontró evento-detalle.php en ' . htmlspecialchars($edFile) . '</span>';
}

// ── 7. Fetch del HTML de la página en vivo (curl) ─────────────────────────────
echo '<h2>7. HTML de la página en vivo (curl)</h2>';
if (function_exists('curl_init')) {
    $urlVivo = 'https://rutasrurales.io/evento/' . rawurlencode($slug);
    $ch = curl_init($urlVivo);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 Debug');
    $html    = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$html) {
        echo '<span class="warn">⚠️ curl falló. Comprueba el HTML con Ctrl+U en el navegador.</span>';
    } else {
        echo "HTTP: <strong>{$httpCode}</strong><br>";
        $target = 'fiestas-san-juan-soria-2026-la-saca-programa';
        if (strpos($html, $target) !== false) {
            echo '<span class="ok">✅ El link aparece en el HTML servido → Sistema funcionando correctamente.</span><br>';
            echo '<span class="warn">Si no lo ves visualmente: el &lt;a&gt; puede estar dentro de un &lt;h3&gt; sin estilos visibles. Prueba Ctrl+U para ver el fuente.</span>';
        } else {
            echo '<span class="err">❌ El link NO aparece en el HTML servido.</span><br>';
            echo '<span class="warn">→ Causa más probable: evento-detalle.php en el servidor NO está actualizado (ver sección 6).</span>';
        }
        $pos = strpos($html, 'event-description');
        if ($pos !== false) {
            echo '<h3>Fragmento del div.event-description en el HTML servido:</h3>';
            echo '<pre>' . htmlspecialchars(substr($html, $pos, 700)) . '</pre>';
        }
    }
} else {
    echo '<span class="warn">⚠️ curl no disponible en este servidor. Verifica el HTML con Ctrl+U en el navegador y busca "La Saca".</span>';
}

?>

<br><br>
<p style="color:#999;font-size:0.85rem;">⚠️ Elimina este archivo del servidor una vez hayas diagnosticado el problema.</p>
</body>
</html>

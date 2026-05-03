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
        // Mostrar keywords
        $links = $pdo->query("SELECT * FROM inbound_links ORDER BY priority ASC")->fetchAll(PDO::FETCH_ASSOC);
        echo '<p>Keywords guardadas (' . count($links) . '):</p>';
        if ($links) {
            echo '<table><tr><th>ID</th><th>Keyword</th><th>URL</th><th>Activo</th><th>Prioridad</th></tr>';
            foreach ($links as $l) {
                echo '<tr><td>'.$l['id'].'</td><td>'.htmlspecialchars($l['keyword']).'</td><td>'.htmlspecialchars($l['url']).'</td><td>'.($l['is_active']?'✅':'❌').'</td><td>'.$l['priority'].'</td></tr>';
            }
            echo '</table>';
        } else {
            echo '<span class="warn">⚠️ Tabla vacía — añade keywords en el panel Inbound Links</span>';
        }
    } else {
        echo '<span class="err">❌ La tabla inbound_links NO EXISTE. Ejecuta api/inbound_links_crear_tablas.sql en la BD</span>';
    }
} catch(Exception $e) {
    echo '<span class="err">❌ Error: '.htmlspecialchars($e->getMessage()).'</span>';
}

// ── 2. ¿Existe la columna description_linked en cultural_events? ──────────────
echo '<h2>2. Columna description_linked en cultural_events</h2>';
try {
    $cols = $pdo->query("SHOW COLUMNS FROM cultural_events LIKE 'description_linked'")->fetchAll();
    if ($cols) {
        echo '<span class="ok">✅ Columna description_linked EXISTE</span>';
    } else {
        echo '<span class="err">❌ La columna description_linked NO EXISTE en cultural_events. Ejecuta el ALTER TABLE del SQL</span>';
    }
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
        echo '<tr><th>description_linked (longitud)</th><td>'.($ev['desc_linked_len']??'<span class="err">columna no existe o NULL</span>').' caracteres</td></tr>';
        echo '</table>';

        echo '<h2>3a. Primeros 500 chars de description</h2>';
        echo '<pre>'.htmlspecialchars($ev['desc_preview'] ?? '(vacío)').'</pre>';

        echo '<h2>3b. Primeros 800 chars de description_linked</h2>';
        if (empty($ev['desc_linked_preview'])) {
            echo '<span class="err">❌ description_linked está VACÍO o NULL. Posibles causas:<br>
            &nbsp;→ La columna no existe en la BD (ejecuta el SQL)<br>
            &nbsp;→ La regeneración falló (prueba a regenerar de nuevo desde el panel Inbound Links)<br>
            &nbsp;→ La keyword no aparece en el texto de description</span>';
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
    echo '<span class="err">❌ Error consultando evento: '.htmlspecialchars($e->getMessage()).'</span>';
}

// ── 5. Test en vivo del helper ─────────────────────────────────────────────────
echo '<h2>5. Test en vivo: procesarInboundLinks()</h2>';
try {
    require_once __DIR__ . '/../api/inbound_links_helper.php';
    $stmtTest = $pdo->prepare("SELECT description FROM cultural_events WHERE slug=?");
    $stmtTest->execute([$slug]);
    $rawDesc = $stmtTest->fetchColumn();
    if ($rawDesc) {
        // Resetear cache
        global $_inbound_links_cache;
        $_inbound_links_cache = null;
        $result = procesarInboundLinks($rawDesc, $pdo);
        if ($result === $rawDesc) {
            echo '<span class="warn">⚠️ El resultado es IDÉNTICO al original — ninguna keyword fue encontrada en el texto</span>';
        } else {
            echo '<span class="ok">✅ Se insertaron links. Diferencia detectada.</span>';
        }
        echo '<h3>Resultado procesado (primeros 1000 chars):</h3>';
        echo '<pre>'.htmlspecialchars(substr($result, 0, 1000)).'</pre>';
    } else {
        echo '<span class="err">No se pudo leer description del evento</span>';
    }
} catch(Exception $e) {
    echo '<span class="err">Error: '.htmlspecialchars($e->getMessage()).'</span>';
}
?>

<br><br>
<p style="color:#999;font-size:0.85rem;">⚠️ Elimina este archivo del servidor una vez hayas diagnosticado el problema.</p>
</body>
</html>

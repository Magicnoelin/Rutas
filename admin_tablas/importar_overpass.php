<?php
// Cargar la conexión de la carpeta local (admin_tablas/db.php)
require_once __DIR__ . '/db.php';

$paso = 'formulario'; 
$mensaje = '';
$elementos_borrador = [];

function crearSlug($texto) {
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $texto);
    $texto = preg_replace('/[\s-]+/', '-', $texto);
    return trim($texto, '-');
}

// -----------------------------------------------------------------------
// PASO 2: Confirmación e Inserción Final en auxiliar_poi
// -----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirmar_insercion') {
    $seleccionados = $_POST['elementos'] ?? [];
    $insertados = 0;

    if (!empty($seleccionados) && is_array($seleccionados)) {
        // Mapeo exacto de las columnas de auxiliar_poi
        $stmt = $pdo->prepare("
            INSERT INTO auxiliar_poi (
                osm_id, nombre, slug, categoria_label, latitud, longitud, origen_datos, datos_json
            ) VALUES (
                :osm_id, :nombre, :slug, :categoria_label, :latitud, :longitud, 'open_data', :datos_json
            )
            ON DUPLICATE KEY UPDATE 
                nombre = VALUES(nombre),
                slug = VALUES(slug),
                categoria_label = VALUES(categoria_label),
                latitud = VALUES(latitud),
                longitud = VALUES(longitud),
                datos_json = VALUES(datos_json)
        ");

        foreach ($seleccionados as $osm_id) {
            $nombre = trim($_POST['nombre'][$osm_id] ?? '');
            $categoria_label = trim($_POST['categoria_label'][$osm_id] ?? '');
            $latitud = $_POST['latitud'][$osm_id] ?? null;
            $longitud = $_POST['longitud'][$osm_id] ?? null;
            $telefono = $_POST['telefono'][$osm_id] ?? null;
            $web = $_POST['web'][$osm_id] ?? null;

            if (!empty($nombre) && $latitud && $longitud) {
                $slug = crearSlug($nombre);
                
                // Guardamos información extra (teléfono, web) dentro de datos_json
                $datos_json = json_encode([
                    'telefono' => $telefono,
                    'web'      => $web
                ], JSON_UNESCAPED_UNICODE);

                $stmt->execute([
                    ':osm_id'          => $osm_id,
                    ':nombre'          => $nombre,
                    ':slug'            => $slug,
                    ':categoria_label' => $categoria_label ?: null,
                    ':latitud'         => $latitud,
                    ':longitud'        => $longitud,
                    ':datos_json'      => $datos_json
                ]);
                $insertados++;
            }
        }
        $mensaje = "<div class='alert success'>¡Éxito! Se han importado/actualizado <strong>{$insertados}</strong> registros en <code>auxiliar_poi</code>.</div>";
        $paso = 'completado';
    } else {
        $mensaje = "<div class='alert warning'>No has seleccionado ningún registro para guardar.</div>";
    }
}

// -----------------------------------------------------------------------
// PASO 1: Cargar JSON y Generar Borrador
// -----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cargar_borrador') {
    $raw_json = '';

    if (isset($_FILES['archivo_json']) && $_FILES['archivo_json']['error'] === UPLOAD_ERR_OK) {
        $raw_json = file_get_contents($_FILES['archivo_json']['tmp_name']);
    } elseif (!empty($_POST['json_data'])) {
        $raw_json = trim($_POST['json_data']);
    }

    if (!empty($raw_json)) {
        $data = json_decode($raw_json, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($data['elements'])) {
            foreach ($data['elements'] as $element) {
                $tags = $element['tags'] ?? [];

                $nombre = $tags['name'] ?? null;
                if (!$nombre) continue;

                $lat = $element['lat'] ?? ($element['center']['lat'] ?? null);
                $lon = $element['lon'] ?? ($element['center']['lon'] ?? null);

                // Sugerencia inicial para categoria_label desde OSM
                $categoria_sugerida = $tags['cuisine'] ?? ($tags['amenity'] ?? ($tags['tourism'] ?? ''));

                if ($lat && $lon) {
                    $elementos_borrador[] = [
                        'osm_id'          => $element['id'],
                        'nombre'          => $nombre,
                        'categoria_label' => $categoria_sugerida,
                        'latitud'         => $lat,
                        'longitud'        => $lon,
                        'telefono'        => $tags['phone'] ?? ($tags['contact:phone'] ?? null),
                        'web'             => $tags['website'] ?? ($tags['contact:website'] ?? null)
                    ];
                }
            }

            if (!empty($elementos_borrador)) {
                $paso = 'borrador';
            } else {
                $mensaje = "<div class='alert warning'>El JSON no contiene elementos válidos con nombre y coordenadas.</div>";
            }
        } else {
            $mensaje = "<div class='alert danger'>Error al decodificar el JSON de Overpass.</div>";
        }
    } else {
        $mensaje = "<div class='alert warning'>No se han recibido datos. Adjunta un archivo .json o pega el JSON.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador Overpass - Admin Tablas</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f4f6f9; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h1 { margin-top: 0; color: #2c3e50; }
        textarea { width: 100%; height: 180px; font-family: monospace; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 15px; }
        .btn-success { background: #28a745; }
        .btn-secondary { background: #6c757d; text-decoration: none; display: inline-block; color: white; padding: 10px 20px; border-radius: 4px; }
        .alert { padding: 12px 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert.success { background: #d4edda; color: #155724; }
        .alert.danger { background: #f8d7da; color: #721c24; }
        .alert.warning { background: #fff3cd; color: #856404; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 14px; }
        th { background: #f8f9fa; }
        input[type="text"] { width: 95%; padding: 6px; border: 1px solid #ccc; border-radius: 4px; }
        .actions-bar { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; }
        .field-group { margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Importador Overpass -> <code>auxiliar_poi</code></h1>
    <?= $mensaje ?>

    <?php if ($paso === 'formulario' || $paso === 'completado'): ?>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="cargar_borrador">
            
            <div class="field-group">
                <label for="archivo_json"><strong>Opción A: Subir archivo .json exportado (Recomendado)</strong></label><br><br>
                <input type="file" name="archivo_json" id="archivo_json" accept=".json">
            </div>

            <div class="field-group">
                <label for="json_data"><strong>Opción B: Pegar el JSON directamente</strong></label><br><br>
                <textarea name="json_data" id="json_data" placeholder='{"version": 0.6, "elements": [...]}'></textarea>
            </div>

            <button type="submit" class="btn">Procesar y Generar Borrador</button>
        </form>
    <?php endif; ?>

    <?php if ($paso === 'borrador'): ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="confirmar_insercion">
            <h2>Borrador Previo (<?= count($elementos_borrador) ?> elementos)</h2>
            <p>Puedes modificar el <strong>Nombre</strong> o la <strong>Categoría Label</strong> directamente en los cuadros antes de confirmar.</p>
            
            <!-- Herramienta para rellenar la categoría masivamente -->
            <div style="background: #f8f9fa; padding: 12px 16px; border: 1px solid #dee2e6; border-radius: 6px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <label for="categoria_maestra" style="font-weight: bold; margin: 0;">Categoría rápida para todos:</label>
                <input type="text" id="categoria_maestra" placeholder="Ej: Bodega, Vinoteca, Asador..." style="padding: 6px 12px; border: 1px solid #ccc; border-radius: 4px; width: 280px;">
                <button type="button" onclick="aplicarCategoriaATodos()" style="background-color: #0d6efd; color: white; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer;">
                    Aplicar a todos
                </button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" checked onclick="toggleCheckboxes(this)"></th>
                        <th>OSM ID</th>
                        <th>Nombre</th>
                        <th>Categoría Label</th>
                        <th>Coordenadas</th>
                        <th>Teléfono</th>
                        <th>Web</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($elementos_borrador as $item): ?>
                        <?php $osm_id = $item['osm_id']; ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="elementos[]" value="<?= $osm_id ?>" checked class="item-checkbox">
                                <input type="hidden" name="latitud[<?= $osm_id ?>]" value="<?= htmlspecialchars($item['latitud']) ?>">
                                <input type="hidden" name="longitud[<?= $osm_id ?>]" value="<?= htmlspecialchars($item['longitud']) ?>">
                                <input type="hidden" name="telefono[<?= $osm_id ?>]" value="<?= htmlspecialchars($item['telefono'] ?? '') ?>">
                                <input type="hidden" name="web[<?= $osm_id ?>]" value="<?= htmlspecialchars($item['web'] ?? '') ?>">
                            </td>
                            <td><?= htmlspecialchars($osm_id) ?></td>
                            <td>
                                <input type="text" name="nombre[<?= $osm_id ?>]" value="<?= htmlspecialchars($item['nombre']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="categoria_label[<?= $osm_id ?>]" value="<?= htmlspecialchars($item['categoria_label']) ?>" class="input-categoria" placeholder="ej. Restaurante, Asador, Bar">
                            </td>
                            <td><small><?= htmlspecialchars($item['latitud']) ?>, <?= htmlspecialchars($item['longitud']) ?></small></td>
                            <td><small><?= htmlspecialchars($item['telefono'] ?? '-') ?></small></td>
                            <td>
                                <?php if ($item['web']): ?>
                                    <a href="<?= htmlspecialchars($item['web']) ?>" target="_blank" rel="noopener">Web</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="actions-bar">
                <a href="importar_overpass.php" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success">Confirmar e Insertar en auxiliar_poi</button>
            </div>
        </form>

        <script>
            function toggleCheckboxes(master) {
                const checkboxes = document.querySelectorAll('.item-checkbox');
                checkboxes.forEach(cb => cb.checked = master.checked);
            }

            function aplicarCategoriaATodos() {
                const valor = document.getElementById('categoria_maestra').value;
                const inputs = document.querySelectorAll('.input-categoria');
                inputs.forEach(input => {
                    input.value = valor;
                });
            }
        </script>
    <?php endif; ?>
</div>

</body>
</html>
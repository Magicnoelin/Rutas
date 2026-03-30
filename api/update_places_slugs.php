<?php
/**
 * Script para actualizar slugs de lugares de interés
 * Genera slugs únicos basados en nombre + municipio
 * Verifica y resuelve duplicados
 */

require_once 'config.php';

function generarSlug($texto) {
    if (!$texto) return '';
    // Convertir caracteres acentuados a su equivalente sin acento
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    // Convertir a minúsculas
    $texto = strtolower($texto);
    // Reemplazar espacios por guiones
    $texto = preg_replace('/\s+/', '-', $texto);
    // Eliminar caracteres no alfanuméricos
    $texto = preg_replace('/[^a-z0-9-]/', '', $texto);
    // Trim hyphens
    return trim($texto, '-');
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");

    // Obtener todos los lugares activos
    $stmt = $pdo->query("SELECT id, name, municipality FROM places_of_interest WHERE is_active = 1 ORDER BY id");
    $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $actualizados = 0;
    $duplicados_resueltos = 0;
    $errores = [];
    $redirects = [];

    echo "🔄 Iniciando actualización de slugs para " . count($lugares) . " lugares...\n\n";

    // Primero obtener los slugs actuales
    $stmtCurrent = $pdo->query("SELECT id, slug FROM places_of_interest WHERE is_active = 1");
    $currentSlugs = [];
    while ($row = $stmtCurrent->fetch(PDO::FETCH_ASSOC)) {
        $currentSlugs[$row['id']] = $row['slug'];
    }

    foreach ($lugares as $lugar) {
        try {
            $oldSlug = $currentSlugs[$lugar['id']] ?? '';

            // Generar slug base: nombre + municipio
            $baseSlug = generarSlug($lugar['name'] . ' ' . $lugar['municipality']);

            if (empty($baseSlug)) {
                $baseSlug = 'lugar-' . $lugar['id']; // Fallback si no se puede generar
            }

            // Verificar si el slug ya existe
            $slug = $baseSlug;
            $counter = 1;

            do {
                $stmtCheck = $pdo->prepare("SELECT id FROM places_of_interest WHERE slug = :slug AND id != :id");
                $stmtCheck->execute(['slug' => $slug, 'id' => $lugar['id']]);

                if ($stmtCheck->rowCount() > 0) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                    $duplicados_resueltos++;
                } else {
                    break;
                }
            } while (true);

            // Si el slug cambió, agregar a redirecciones
            if ($oldSlug !== $slug) {
                $redirects[] = [
                    'old' => $oldSlug,
                    'new' => $slug,
                    'id' => $lugar['id'],
                    'name' => $lugar['name']
                ];
            }

            // Actualizar el slug
            $stmtUpdate = $pdo->prepare("UPDATE places_of_interest SET slug = :slug WHERE id = :id");
            $stmtUpdate->execute(['slug' => $slug, 'id' => $lugar['id']]);

            $actualizados++;
            echo "✅ ID {$lugar['id']}: '{$lugar['name']}' -> '$slug'\n";

        } catch (Exception $e) {
            $errores[] = [
                'id' => $lugar['id'],
                'name' => $lugar['name'],
                'error' => $e->getMessage()
            ];
            echo "❌ Error en ID {$lugar['id']}: {$e->getMessage()}\n";
        }
    }

    // Generar redirecciones si hay cambios
    if (!empty($redirects)) {
        $htaccessContent = "# Redirecciones de slugs antiguos a nuevos para lugares de interés\n";
        $htaccessContent .= "# Generado el " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($redirects as $redirect) {
            $htaccessContent .= "Redirect 301 /lugar/{$redirect['old']} /lugar/{$redirect['new']}\n";
        }

        // Mostrar el contenido para copiar
        echo "\n📋 Redirecciones generadas. Copia las siguientes reglas y pégalas en tu archivo .htaccess principal:\n\n";
        echo "<pre>\n";
        echo htmlspecialchars($htaccessContent);
        echo "</pre>\n\n";

        // Intentar guardar el archivo
        $htaccessFile = __DIR__ . '/../.htaccess.redirects';
        if (file_put_contents($htaccessFile, $htaccessContent)) {
            echo "✅ Archivo .htaccess.redirects guardado en: " . realpath($htaccessFile) . "\n\n";
        } else {
            echo "⚠️ No se pudo guardar el archivo .htaccess.redirects\n\n";
        }
    }

    // Resultado final
    echo "\n📊 Resumen:\n";
    echo "✅ Lugares actualizados: $actualizados\n";
    echo "🔄 Duplicados resueltos: $duplicados_resueltos\n";
    echo "🔀 Redirecciones generadas: " . count($redirects) . "\n";
    echo "❌ Errores: " . count($errores) . "\n";

    if (!empty($errores)) {
        echo "\nDetalles de errores:\n";
        foreach ($errores as $error) {
            echo "- ID {$error['id']} ({$error['name']}): {$error['error']}\n";
        }
    }

    echo "\n🎉 Actualización completada!\n";

} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
}

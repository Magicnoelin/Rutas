<?php
/**
 * Script para generar redirecciones de slugs antiguos a nuevos
 * Crea reglas para .htaccess
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

    // Obtener todos los lugares activos con sus slugs actuales
    $stmt = $pdo->query("SELECT id, slug, name, municipality FROM places_of_interest WHERE is_active = 1 ORDER BY id");
    $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $redirects = [];
    $errores = [];

    echo "🔄 Generando redirecciones para " . count($lugares) . " lugares...\n\n";

    foreach ($lugares as $lugar) {
        try {
            // Generar nuevo slug: nombre + municipio
            $newSlug = generarSlug($lugar['name'] . ' ' . $lugar['municipality']);

            if (empty($newSlug)) {
                $newSlug = 'lugar-' . $lugar['id'];
            }

            // Verificar si hay duplicados en los nuevos slugs
            $counter = 1;
            $finalNewSlug = $newSlug;
            while (isset($redirects[$finalNewSlug]) && $redirects[$finalNewSlug]['id'] != $lugar['id']) {
                $finalNewSlug = $newSlug . '-' . $counter;
                $counter++;
            }

            $oldSlug = $lugar['slug'];

            if ($oldSlug !== $finalNewSlug) {
                $redirects[$finalNewSlug] = [
                    'old' => $oldSlug,
                    'new' => $finalNewSlug,
                    'id' => $lugar['id'],
                    'name' => $lugar['name']
                ];
            }

        } catch (Exception $e) {
            $errores[] = [
                'id' => $lugar['id'],
                'name' => $lugar['name'],
                'error' => $e->getMessage()
            ];
        }
    }

    // Generar contenido .htaccess con redirecciones
    $htaccessContent = "# Redirecciones de slugs antiguos a nuevos para lugares de interés\n";
    $htaccessContent .= "# Generado el " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($redirects as $newSlug => $data) {
        $oldSlug = $data['old'];
        $htaccessContent .= "Redirect 301 /lugar/{$oldSlug} /lugar/{$newSlug}\n";
    }

    // Mostrar el contenido para copiar
    echo "📋 Copia las siguientes reglas y pégalas en tu archivo .htaccess principal:\n\n";
    echo "<pre>\n";
    echo htmlspecialchars($htaccessContent);
    echo "</pre>\n\n";

    // Intentar guardar el archivo (opcional)
    $htaccessFile = __DIR__ . '/../.htaccess.redirects';
    if (file_put_contents($htaccessFile, $htaccessContent)) {
        echo "✅ Archivo .htaccess.redirects también guardado en: " . realpath($htaccessFile) . "\n\n";
    } else {
        echo "⚠️ No se pudo guardar el archivo .htaccess.redirects (revisa permisos)\n\n";
    }

    // Mostrar resumen
    echo "📊 Resumen:\n";
    echo "🔄 Redirecciones generadas: " . count($redirects) . "\n";
    echo "❌ Errores: " . count($errores) . "\n";

    if (!empty($errores)) {
        echo "\nDetalles de errores:\n";
        foreach ($errores as $error) {
            echo "- ID {$error['id']} ({$error['name']}): {$error['error']}\n";
        }
    }

    echo "\n📝 Instrucciones:\n";
    echo "1. Revisa el archivo .htaccess.redirects generado\n";
    echo "2. Copia las reglas Redirect 301 al archivo .htaccess principal\n";
    echo "3. O incluye el archivo con: Include .htaccess.redirects\n";

    echo "\n🎉 Generación completada!\n";

} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
}

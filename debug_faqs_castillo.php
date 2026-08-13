<?php
/**
 * Script de diagnóstico: Verificar FAQs del Castillo de Turégano
 * =============================================================
 * Comprueba si existen FAQs personalizadas en BD y si el helper funciona correctamente.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar configuración de BD
if (!defined('API_NO_HEADERS')) {
    define('API_NO_HEADERS', true);
}
require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/includes/faq-helper.php';

try {
    $pdo = getDBConnection();
    
    echo "<h1>🔍 Diagnóstico FAQs - Castillo de Turégano</h1>\n";
    echo "<pre style='background:#f5f5f5; padding:15px; border:1px solid #ddd;'>\n";
    
    // 1. Buscar el lugar por slug
    echo "1️⃣ BUSCANDO LUGAR POR SLUG...\n";
    echo "═══════════════════════════════\n";
    
    $slugs = ['castillo-de-turegano-segovia-horarios-visitas', 'castillo-turegano', 'turegano'];
    $lugar = null;
    
    foreach ($slugs as $slug) {
        $stmt = $pdo->prepare("SELECT id, name, slug, municipality, province FROM places_of_interest WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $lugar = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lugar) {
            echo "✅ Encontrado con slug: '$slug'\n";
            break;
        } else {
            echo "❌ No encontrado con slug: '$slug'\n";
        }
    }
    
    if (!$lugar) {
        echo "\n🔍 BUSCANDO POR NOMBRE...\n";
        $stmt = $pdo->prepare("SELECT id, name, slug, municipality, province FROM places_of_interest WHERE name LIKE '%Turégano%' OR name LIKE '%Turegano%' LIMIT 5");
        $stmt->execute();
        $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($lugares)) {
            echo "📍 Lugares encontrados con 'Turégano':\n";
            foreach ($lugares as $l) {
                echo "   - ID: {$l['id']} | {$l['name']} | {$l['slug']} | {$l['municipality']}\n";
            }
            $lugar = $lugares[0]; // Tomar el primero para las pruebas
        }
    }
    
    if (!$lugar) {
        echo "\n❌ No se pudo encontrar el lugar. Saliendo...\n";
        exit;
    }
    
    $lugarId = (int)$lugar['id'];
    echo "\n📋 DATOS DEL LUGAR:\n";
    echo "   ID: {$lugarId}\n";
    echo "   Nombre: {$lugar['name']}\n";
    echo "   Slug: {$lugar['slug']}\n";
    echo "   Municipio: {$lugar['municipality']}\n";
    echo "   Provincia: {$lugar['province']}\n";
    
    // 2. Consultar tabla faqs directamente
    echo "\n\n2️⃣ CONSULTA DIRECTA A TABLA 'faqs'...\n";
    echo "═══════════════════════════════════════\n";
    
    $stmt = $pdo->prepare("
        SELECT id, entity_type, entity_id, lang, question, LEFT(answer, 60) as answer_preview, 
               is_active, sort_order, created_at
        FROM faqs 
        WHERE entity_id = ? 
        ORDER BY entity_type, lang, sort_order
    ");
    $stmt->execute([$lugarId]);
    $faqsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($faqsRaw)) {
        echo "❌ No se encontraron FAQs en la tabla para entity_id = $lugarId\n";
        
        // Buscar también por variaciones de entity_type
        echo "\n🔍 BUSCANDO CON VARIACIONES DE ENTITY_TYPE...\n";
        $stmt = $pdo->prepare("
            SELECT id, entity_type, entity_id, lang, question, LEFT(answer, 60) as answer_preview, 
                   is_active, sort_order
            FROM faqs 
            WHERE entity_id = ? AND entity_type IN ('place', 'places_of_interest')
        ");
        $stmt->execute([$lugarId]);
        $faqsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if (!empty($faqsRaw)) {
        echo "✅ Encontradas " . count($faqsRaw) . " FAQs en BD:\n\n";
        foreach ($faqsRaw as $f) {
            echo "   📝 ID: {$f['id']}\n";
            echo "      Entity Type: {$f['entity_type']}\n";
            echo "      Lang: " . ($f['lang'] ?: '[NULL]') . "\n";
            echo "      Active: " . ($f['is_active'] ? 'SI' : 'NO') . "\n";
            echo "      Question: {$f['question']}\n";
            echo "      Answer Preview: {$f['answer_preview']}...\n";
            echo "      Created: {$f['created_at']}\n";
            echo "   ────────────────────────────────────────\n";
        }
    } else {
        echo "❌ No se encontraron FAQs para este lugar.\n";
    }
    
    // 3. Probar helper getFaqs()
    echo "\n3️⃣ PRUEBA HELPER getFaqs()...\n";
    echo "══════════════════════════════\n";
    
    $idiomas = ['es', 'en', 'fr'];
    foreach ($idiomas as $lang) {
        echo "🔸 Idioma: $lang\n";
        $faqs = getFaqs($pdo, 'place', $lugarId, $lang);
        
        if (!empty($faqs)) {
            echo "  ✅ Devuelve " . count($faqs) . " FAQs:\n";
            foreach ($faqs as $idx => $faq) {
                echo "     " . ($idx + 1) . ". {$faq['question']}\n";
                echo "        Resp: " . substr(strip_tags($faq['answer']), 0, 80) . "...\n";
            }
        } else {
            echo "  ❌ No devuelve FAQs\n";
        }
        echo "\n";
    }
    
    // 4. Verificar estructura de tabla faqs
    echo "\n4️⃣ ESTRUCTURA TABLA 'faqs'...\n";
    echo "══════════════════════════════\n";
    
    try {
        $stmt = $pdo->query("DESCRIBE faqs");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📊 Columnas de la tabla 'faqs':\n";
        foreach ($columns as $col) {
            echo "   • {$col['Field']} ({$col['Type']}) " . ($col['Null'] === 'NO' ? '[NOT NULL]' : '[NULL OK]') . "\n";
        }
        
        echo "\n📈 Total registros en tabla faqs:\n";
        $stmt = $pdo->query("SELECT COUNT(*) as total, entity_type, COUNT(DISTINCT entity_id) as unique_entities FROM faqs GROUP BY entity_type");
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($stats as $stat) {
            echo "   • {$stat['entity_type']}: {$stat['total']} FAQs para {$stat['unique_entities']} entidades\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error consultando estructura: " . $e->getMessage() . "\n";
    }
    
    // 5. Sugerencia de consulta SQL para verificar
    echo "\n\n5️⃣ CONSULTA SQL RECOMENDADA...\n";
    echo "═══════════════════════════════════\n";
    echo "Para verificar manualmente, ejecuta:\n\n";
    echo "SELECT f.*, p.name, p.slug FROM faqs f\n";
    echo "JOIN places_of_interest p ON f.entity_id = p.id\n";
    echo "WHERE p.slug LIKE '%turegano%' OR p.name LIKE '%Turégano%'\n";
    echo "ORDER BY f.sort_order;\n";
    
    echo "</pre>\n";
    
} catch (Exception $e) {
    echo "<pre style='color:red; background:#ffe6e6; padding:10px;'>";
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString();
    echo "</pre>\n";
}

echo "\n<hr>\n";
echo "<p><strong>🎯 Conclusión:</strong> Si ves FAQs personalizadas arriba, el bug era solo que no se pasaban al schema. Si no hay FAQs, necesitas crearlas en la tabla <code>faqs</code>.</p>\n";
?>
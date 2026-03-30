<?php
/**
 * Generador de Slugs para URLs Amigables
 * Rutas - Sistema de Gestión de Alojamientos Turísticos
 */

/**
 * Función para generar slug a partir de texto
 * Convierte nombres de alojamientos en URLs amigables
 */
function generarSlug($texto, $tabla = 'accommodations', $campo = 'name') {
    // Convertir a minúsculas
    $texto = mb_strtolower($texto, 'UTF-8');
    
    // Eliminar acentos y caracteres especiales
    $texto = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü'],
        ['a', 'e', 'i', 'o', 'u', 'n', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'u'],
        $texto
    );
    
    // Eliminar caracteres especiales excepto espacios y guiones
    $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);
    
    // Reemplazar múltiples espacios o guiones con un solo guión
    $texto = preg_replace('/[\s_-]+/', '-', $texto);
    
    // Eliminar guiones al inicio y final
    $texto = trim($texto, '-');
    
    // Verificar unicidad en la base de datos
    $slugUnico = verificarSlugUnico($texto, $tabla, $campo);
    
    return $slugUnico;
}

/**
 * Verificar que el slug sea único en la base de datos
 */
function verificarSlugUnico($slug, $tabla = 'accommodations', $campo = 'name') {
    try {
        require_once 'config_updated.php';
        $pdo = getDBConnection();
        
        $originalSlug = $slug;
        $contador = 1;
        
        // Verificar si el slug ya existe
        while (true) {
            $stmt = $pdo->prepare("SELECT id FROM $tabla WHERE slug = ? LIMIT 1");
            $stmt->execute([$slug]);
            $existe = $stmt->fetch();
            
            if (!$existe) {
                break; // Slug único encontrado
            }
            
            // Si existe, agregar contador
            $slug = $originalSlug . '-' . $contador;
            $contador++;
            
            // Evitar loops infinitos
            if ($contador > 100) {
                // Agregar timestamp como fallback
                $slug = $originalSlug . '-' . time();
                break;
            }
        }
        
        return $slug;
        
    } catch (PDOException $e) {
        // En caso de error, retornar slug original con timestamp
        error_log('Error verificando slug único: ' . $e->getMessage());
        return $slug . '-' . time();
    }
}

/**
 * Generar slug automáticamente para un alojamiento
 */
function generarSlugParaAlojamiento($nombre, $municipality = '', $province = '') {
    $base = $nombre;
    
    // Agregar ubicación si se proporciona
    if (!empty($municipality)) {
        $base .= '-' . $municipality;
    }
    
    if (!empty($province)) {
        $base .= '-' . $province;
    }
    
    return generarSlug($base);
}

/**
 * Función para actualizar slug de alojamiento existente
 */
function actualizarSlugAlojamiento($id, $nuevoNombre = null) {
    try {
        require_once 'config_updated.php';
        $pdo = getDBConnection();
        
        // Si no se proporciona nombre, obtener el actual
        if ($nuevoNombre === null) {
            $stmt = $pdo->prepare("SELECT name, municipality, province FROM accommodations WHERE id = ?");
            $stmt->execute([$id]);
            $alojamiento = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$alojamiento) {
                return false;
            }
            
            $nuevoNombre = $alojamiento['name'];
            $municipality = $alojamiento['municipality'];
            $province = $alojamiento['province'];
        } else {
            // Obtener datos actuales para ubicación
            $stmt = $pdo->prepare("SELECT municipality, province FROM accommodations WHERE id = ?");
            $stmt->execute([$id]);
            $alojamiento = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $municipality = $alojamiento['municipality'] ?? '';
            $province = $alojamiento['province'] ?? '';
        }
        
        // Generar nuevo slug
        $nuevoSlug = generarSlugParaAlojamiento($nuevoNombre, $municipality, $province);
        
        // Actualizar en la base de datos
        $stmt = $pdo->prepare("UPDATE accommodations SET slug = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $resultado = $stmt->execute([$nuevoSlug, $id]);
        
        return $resultado ? $nuevoSlug : false;
        
    } catch (PDOException $e) {
        error_log('Error actualizando slug: ' . $e->getMessage());
        return false;
    }
}

/**
 * Función para asignar slugs a alojamientos existentes sin slug
 */
function asignarSlugsExistentes() {
    try {
        require_once 'config_updated.php';
        $pdo = getDBConnection();
        
        // Buscar alojamientos sin slug
        $stmt = $pdo->prepare("SELECT id, name, municipality, province FROM accommodations WHERE slug IS NULL OR slug = ''");
        $stmt->execute();
        $alojamientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $actualizados = 0;
        
        foreach ($alojamientos as $alojamiento) {
            $slug = generarSlugParaAlojamiento(
                $alojamiento['name'], 
                $alojamiento['municipality'], 
                $alojamiento['province']
            );
            
            // Actualizar slug
            $stmtUpdate = $pdo->prepare("UPDATE accommodations SET slug = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $resultado = $stmtUpdate->execute([$slug, $alojamiento['id']]);
            
            if ($resultado) {
                $actualizados++;
            }
        }
        
        return $actualizados;
        
    } catch (PDOException $e) {
        error_log('Error asignando slugs: ' . $e->getMessage());
        return false;
    }
}

/**
 * API Endpoint para generar slug en tiempo real
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $nombre = $input['nombre'] ?? '';
    $municipality = $input['municipality'] ?? '';
    $province = $input['province'] ?? '';
    
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'error' => 'Nombre es requerido']);
        exit;
    }
    
    $slug = generarSlugParaAlojamiento($nombre, $municipality, $province);
    
    echo json_encode([
        'success' => true,
        'slug' => $slug,
        'url_amigable' => '/alojamientos/' . $slug
    ]);
    exit;
}

/**
 * API Endpoint para asignar slugs a alojamientos existentes
 */
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    header('Content-Type: application/json; charset=utf-8');
    
    $actualizados = asignarSlugsExistentes();
    
    if ($actualizados !== false) {
        echo json_encode([
            'success' => true,
            'mensaje' => "Se asignaron slugs a $actualizados alojamientos",
            'actualizados' => $actualizados
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Error al asignar slugs'
        ]);
    }
    exit;
}
?>

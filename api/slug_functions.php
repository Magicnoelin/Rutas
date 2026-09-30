<?php
/**
 * Funciones para Generación de Slugs UTF-8
 * Convierte texto con tildes, eñes y caracteres especiales a URLs amigables ASCII
 * 
 * Ejemplos:
 *   "León" → "leon"
 *   "España" → "espana"
 *   "Café & Résidencia" → "cafe-residencia"
 *   "Niño groß" → "nino-gross"
 */

if (!function_exists('generarSlug')) {
    /**
     * Genera un slug URL-friendly a partir de texto
     * Convierte caracteres UTF-8 (tildes, ñ, etc.) a ASCII plano
     * 
     * @param string $texto Texto a convertir en slug
     * @return string Slug limpio en minúsculas con guiones
     */
    function generarSlug($texto) {
        if (!$texto || !is_string($texto)) {
            return '';
        }
        
        // 1. Convertir a minúsculas (UTF-8 seguro)
        $slug = mb_strtolower(trim($texto), 'UTF-8');
        
        // 2. Reemplazar caracteres acentuados y especiales con equivalentes ASCII
        // Primero intentamos con iconv (más completo)
        if (function_exists('iconv')) {
            // Eliminar caracteres no transliterables silenciosamente
            $slug = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        }
        
        // 3. Si iconv no está disponible o falló, usar str_replace manual
        if ($slug === false || $slug === '') {
            $slug = mb_strtolower(trim($texto), 'UTF-8');
            $replacements = [
                // Minúsculas
                'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
                'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
                'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
                'ã' => 'a', 'õ' => 'o', 'ñ' => 'n', 'ç' => 'c',
                'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
                'å' => 'a', 'ø' => 'o', 'œ' => 'oe', 'æ' => 'ae',
                'ß' => 'ss', 'đ' => 'd', 'ł' => 'l', 'ś' => 's', 'ž' => 'z',
                'č' => 'c', 'ě' => 'e', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ž' => 'z',
                'ń' => 'n', 'ę' => 'e', 'ą' => 'a', 'ł' => 'l', 'ó' => 'o',
                // Mayúsculas
                'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
                'À' => 'a', 'È' => 'e', 'Ì' => 'i', 'Ò' => 'o', 'Ù' => 'u',
                'Â' => 'a', 'Ê' => 'e', 'Î' => 'i', 'Ô' => 'o', 'Û' => 'u',
                'Ã' => 'a', 'Õ' => 'o', 'Ñ' => 'n', 'Ç' => 'c',
                'Ä' => 'a', 'Ë' => 'e', 'Ï' => 'i', 'Ö' => 'o', 'Ü' => 'u',
                'Å' => 'a', 'Ø' => 'o', 'Œ' => 'oe', 'Æ' => 'ae',
                'ẞ' => 'ss', 'Đ' => 'd', 'Ł' => 'l', 'Ś' => 's', 'Ž' => 'z',
                'Č' => 'c', 'Ě' => 'e', 'Ř' => 'r', 'Š' => 'S', 'Ť' => 't', 'Ž' => 'z',
                'Ń' => 'n', 'Ę' => 'e', 'Ą' => 'a', 'Ó' => 'o'
            ];
            $slug = str_replace(array_keys($replacements), array_values($replacements), $slug);
        }
        
        // 4. Eliminar caracteres no deseados (solo letras ASCII, números y guiones)
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        
        // 5. Reemplazar espacios y guiones múltiples con un solo guión
        $slug = preg_replace('/[\s_]+/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        
        // 6. Eliminar guiones al inicio y final
        $slug = trim($slug, '-');
        
        return $slug;
    }
}

if (!function_exists('normalizarSlug')) {
    /**
     * Normaliza un slug para comparación o redirección
     * Convierte cualquier versión del slug (con o sin tildes) a versión limpia
     * 
     * @param string $slug Slug a normalizar
     * @return string Slug normalizado
     */
    function normalizarSlug($slug) {
        if (!$slug || !is_string($slug)) {
            return '';
        }
        
        // Decodificar URL encoding primero (para %C3%A9 -> é -> e)
        $slug = urldecode($slug);
        
        // Aplicar la función de generación de slug
        return generarSlug($slug);
    }
}

if (!function_exists('esSlugValido')) {
    /**
     * Valida que un slug contenga solo caracteres permitidos
     * 
     * @param string $slug Slug a validar
     * @return bool True si es válido
     */
    function esSlugValido($slug) {
        if (!$slug || !is_string($slug)) {
            return false;
        }
        
        // Solo permite letras ASCII, números y guiones
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }
}

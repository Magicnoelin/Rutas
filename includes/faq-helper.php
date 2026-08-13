<?php
/**
 * Helper para gestión de FAQs unificadas
 * Optimizado para rendimiento (caché estático por request + índices SQL)
 */

if (!function_exists('getFaqs')) {
    /**
     * Obtiene las FAQs de una entidad específica (lugar, alojamiento, evento, etc.)
     * 
     * @param PDO $pdo Conexión a la base de datos
     * @param string $entityType Tipo de entidad ('place', 'accommodation', 'event', 'activity', 'route')
     * @param int $entityId ID del registro
     * @param string $lang Idioma (por defecto 'es')
     * @return array Lista de preguntas y respuestas
     */
    function getFaqs($pdo, $entityType, $entityId, $lang = 'es') {
        // Mapeo de alias por si se pasa el nombre completo de la tabla
        $typeMap = [
            'places_of_interest' => 'place',
            'accommodations'     => 'accommodation',
            'cultural_events'    => 'event',
            'tourist_activities' => 'activity',
            'routes'             => 'route'
        ];
        
        if (isset($typeMap[$entityType])) {
            $entityType = $typeMap[$entityType];
        }

        // Validación básica de tipo para evitar queries innecesarias
        $allowedTypes = ['place', 'accommodation', 'event', 'activity', 'route'];
        if (!in_array($entityType, $allowedTypes) || (int)$entityId <= 0) {
            return [];
        }

        // 1. Caché en memoria por cada Request (si se llama varias veces en la misma página, no repite la query)
        static $staticCache = [];
        $cacheKey = "{$entityType}_{$entityId}_{$lang}";

        if (isset($staticCache[$cacheKey])) {
            return $staticCache[$cacheKey];
        }

        // 2. Intentar APCu si está disponible en el servidor (caché persistente entre peticiones)
        if (function_exists('apcu_fetch')) {
            $apcuKey = "faq_{$cacheKey}";
            $success = false;
            $cachedData = apcu_fetch($apcuKey, $success);
            if ($success) {
                $staticCache[$cacheKey] = $cachedData;
                return $cachedData;
            }
        }

        // 3. Consulta a Base de Datos (tolerante a variaciones)
        try {
            // Primero: consulta exacta por idioma
            $sql = "SELECT question, answer 
                    FROM faqs 
                    WHERE entity_type = :entity_type 
                      AND entity_id = :entity_id 
                      AND lang = :lang 
                      AND is_active = 1
                    ORDER BY sort_order ASC 
                    LIMIT 20";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':entity_type' => $entityType,
                ':entity_id'   => (int)$entityId,
                ':lang'        => $lang
            ]);

            $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si no encuentra y entityType es 'place', probar con 'places_of_interest'
            if (empty($faqs) && $entityType === 'place') {
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':entity_type' => 'places_of_interest',
                    ':entity_id'   => (int)$entityId,
                    ':lang'        => $lang
                ]);
                $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Si no encuentra y entityType es 'places_of_interest', probar con 'place'
            if (empty($faqs) && $entityType === 'places_of_interest') {
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':entity_type' => 'place',
                    ':entity_id'   => (int)$entityId,
                    ':lang'        => $lang
                ]);
                $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Si no hay resultados y el idioma no es 'es', reintentar en español
            if (empty($faqs) && $lang !== 'es') {
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':entity_type' => $entityType,
                    ':entity_id'   => (int)$entityId,
                    ':lang'        => 'es'
                ]);
                $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Si aún no encuentra, probar entity_type alternativo en español
                if (empty($faqs)) {
                    $altType = $entityType === 'place' ? 'places_of_interest' : 'place';
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':entity_type' => $altType,
                        ':entity_id'   => (int)$entityId,
                        ':lang'        => 'es'
                    ]);
                    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }

            // Guardar en caché estático
            $staticCache[$cacheKey] = $faqs;

            // Guardar en APCu por 1 hora si está disponible
            if (function_exists('apcu_store')) {
                apcu_store("faq_{$cacheKey}", $faqs, 3600);
            }

            return $faqs;

        } catch (PDOException $e) {
            // Silencioso: si la tabla no existe o falla la BD, devuelve array vacío sin romper la web
            error_log("Error cargando FAQs: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('buildFaqSchema')) {
    /**
     * Construye el array de Schema.org FAQPage para inyectar en el JSON-LD
     * 
     * @param array $faqs Array de FAQs obtenido con getFaqs()
     * @return array|null Array estructurado Schema.org o null si está vacío
     */
    function buildFaqSchema($faqs) {
        if (empty($faqs)) {
            return null;
        }

        $mainEntity = [];
        foreach ($faqs as $faq) {
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => strip_tags($faq['question']),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($faq['answer'])
                ]
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity
        ];
    }
}
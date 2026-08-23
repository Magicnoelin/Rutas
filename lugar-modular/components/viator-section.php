<?php
/**
 * lugar-modular/components/viator-section.php
 * Integración con la API v2 de Viator (/partner/search/freetext)
 */

if (!function_exists('obtener_actividades_viator_api')) {
    function obtener_actividades_viator_api($provincia_nombre, $limite = 3) {
        // ⚠️ PEGA AQUÍ TU API KEY REAL DE VIATOR
        $apiKeyReal = '9ddfa435-ac58-452a-a525-af2e36d956cf';

        if (defined('VIATOR_API_KEY') && VIATOR_API_KEY !== 'TU_VIATOR_API_KEY_AQUI' && !empty(VIATOR_API_KEY)) {
            $apiKey = trim(VIATOR_API_KEY);
        } else {
            $apiKey = trim($apiKeyReal);
        }

        if (empty($apiKey) || $apiKey === 'AQUI_TU_API_KEY_REAL' || $apiKey === 'TU_VIATOR_API_KEY_AQUI') {
            return [];
        }

        $url = 'https://api.viator.com/partner/search/freetext';

        $payload = json_encode([
            'searchTerm' => trim($provincia_nombre) . ', España',
            'searchTypes' => [
                [
                    'searchType' => 'PRODUCTS',
                    'pagination' => [
                        'start' => 1,
                        'count' => 15 // Traemos más para poder filtrar estrictamente la provincia
                    ]
                ]
            ],
            'currency' => 'EUR'
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json;version=2.0',
            'Accept-Language: es',
            'Content-Type: application/json',
            'exp-api-key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            
            $raw_results = [];
            if (!empty($data['products']['results'])) {
                $raw_results = $data['products']['results'];
            } elseif (!empty($data['products'])) {
                $raw_results = $data['products'];
            } elseif (!empty($data['results'])) {
                $raw_results = $data['results'];
            }

            $filtrados = [];
            $provincia_lc = mb_strtolower(trim($provincia_nombre), 'UTF-8');

            foreach ($raw_results as $item) {
                $titulo = $item['title'] ?? $item['productTitle'] ?? $item['name'] ?? '';
                $titulo_lc = mb_strtolower($titulo, 'UTF-8');

                // FILTRO ESTRICTO: El título DEBE mencionar la provincia elegida
                if (mb_strpos($titulo_lc, $provincia_lc) !== false) {
                    $filtrados[] = $item;
                }

                if (count($filtrados) >= $limite) {
                    break;
                }
            }

            return $filtrados;
        }

        return [];
    }
}

if (!function_exists('mostrar_actividades_viator')) {
    function mostrar_actividades_viator($provincia_nombre = '', $limite = 3) {
        $prov_clean = !empty($provincia_nombre) ? trim($provincia_nombre) : 'Zamora';
        
        $res_api = obtener_actividades_viator_api($prov_clean, $limite);
        $productos = [];

        if (is_array($res_api) && !empty($res_api)) {
            foreach ($res_api as $prod) {
                $title = $prod['title'] ?? $prod['productTitle'] ?? $prod['name'] ?? 'Tour en ' . $prov_clean;
                
                $img = '/menu_images/turismo_rural.webp';
                if (!empty($prod['images'][0]['variants'])) {
                    foreach ($prod['images'][0]['variants'] as $v) {
                        if (isset($v['width']) && $v['width'] >= 400) {
                            $img = $v['url'];
                            break;
                        }
                    }
                } elseif (!empty($prod['primaryPhoto']['small'])) {
                    $img = $prod['primaryPhoto']['small'];
                }

                $price = '15.00';
                if (isset($prod['pricing']['summary']['fromPrice'])) {
                    $price = number_format((float)$prod['pricing']['summary']['fromPrice'], 2, '.', '');
                }

                $rating = '4.8';
                if (isset($prod['reviews']['combinedAverageRating'])) {
                    $rating = number_format((float)$prod['reviews']['combinedAverageRating'], 1, '.', '');
                }

                // 1. Usa productUrl si la API lo manda directamente
                // 2. O construimos el enlace nativo directo a la ficha del producto viator.com
                // 3. Fallback a la búsqueda de la provincia si nada funciona
                $productCode = $prod['productCode'] ?? $prod['code'] ?? '';
                if (!empty($prod['productUrl'])) {
                    $url = $prod['productUrl'];
                } elseif (!empty($productCode)) {
                    $url = "https://www.viator.com/es-ES/tours/a/p-" . $productCode;
                } else {
                    $url = "https://www.viator.com/es-ES/search/" . urlencode('tours ' . $prov_clean);
                }

                $productos[] = [
                    'title'  => $title,
                    'cover'  => $img,
                    'rating' => $rating,
                    'price'  => $price,
                    'url'    => $url
                ];
            }
        }

        // Fallback genérico para provincias con poco/ningún inventario en Viator
        if (empty($productos)) {
            $prov_escaped = htmlspecialchars($prov_clean, ENT_QUOTES, 'UTF-8');
            $productos = [
                [
                    'title' => 'Visita Guiada y Tour Histórico en ' . $prov_escaped,
                    'cover' => '/menu_images/turismo_rural.webp',
                    'rating' => '4.8',
                    'price' => '15.00',
                    'url' => 'https://www.viator.com/es-ES/search/' . urlencode('tours ' . $prov_clean)
                ],
                [
                    'title' => 'Paseo Cultural y Monumentos de ' . $prov_escaped,
                    'cover' => '/menu_images/turismo_rural.webp',
                    'rating' => '4.9',
                    'price' => '22.00',
                    'url' => 'https://www.viator.com/es-ES/search/' . urlencode('visitas ' . $prov_clean)
                ],
                [
                    'title' => 'Ruta de Experiencias y Gastronomía Local',
                    'cover' => '/menu_images/turismo_rural.webp',
                    'rating' => '4.7',
                    'price' => '35.00',
                    'url' => 'https://www.viator.com/es-ES/search/' . urlencode('excursiones ' . $prov_clean)
                ]
            ];
        }

        ?>
        <style>
            .viator-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 20px;
                margin-top: 15px;
            }
            .viator-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                display: flex;
                flex-direction: column;
            }
            .viator-card-img {
                width: 100%;
                height: 160px;
                object-fit: cover;
                background: #f1f5f9;
            }
            .viator-card-body {
                padding: 15px;
                display: flex;
                flex-direction: column;
                flex-grow: 1;
                justify-content: space-between;
            }
            .viator-card-title {
                font-size: 0.95rem;
                font-weight: 600;
                color: #1e293b;
                margin: 0 0 10px 0;
                line-height: 1.35;
                height: 2.7em;
                overflow: hidden;
            }
            .viator-card-meta {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 10px;
            }
            .viator-btn {
                display: block;
                width: 100%;
                text-align: center;
                background: #00875a;
                color: #fff;
                padding: 10px 0;
                border-radius: 6px;
                font-weight: 600;
                text-decoration: none;
                margin-top: 12px;
            }
        </style>

        <section class="viator-activities-section" style="margin-top: 35px; margin-bottom: 35px;">
            <h2 style="font-size: 1.35rem; font-weight: 700; margin-bottom: 15px; color: #2c3e50;">
                Actividades y Tours recomendados <?php echo !empty($provincia_nombre) ? 'en ' . htmlspecialchars($provincia_nombre, ENT_QUOTES, 'UTF-8') : ''; ?>
            </h2>

            <div class="viator-grid">
                <?php foreach ($productos as $prod): ?>
                    <div class="viator-card">
                        <img src="<?php echo htmlspecialchars($prod['cover'], ENT_QUOTES, 'UTF-8'); ?>" alt="" class="viator-card-img" loading="lazy">
                        <div class="viator-card-body">
                            <h3 class="viator-card-title"><?php echo htmlspecialchars($prod['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="viator-card-meta">
                                <span style="color: #f59e0b; font-weight: bold;">★ <?php echo htmlspecialchars($prod['rating'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span style="font-size: 1.05rem; font-weight: 700; color: #00875a;">Desde <?php echo htmlspecialchars($prod['price'], ENT_QUOTES, 'UTF-8'); ?>€</span>
                            </div>
                            <a href="<?php echo htmlspecialchars($prod['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener sponsored" class="viator-btn">
                                Ver disponibilidad
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}
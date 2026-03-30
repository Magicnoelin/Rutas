<?php
/**
 * API Endpoint: Log de conversaciones con Antonio
 * Guarda las preguntas de los usuarios para análisis y mejora continua
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Directorio de logs (fuera del webroot si es posible, o protegido con .htaccess)
$logDir = __DIR__ . '/logs/antonio/';
$logFile = $logDir . 'conversaciones_' . date('Y-m') . '.json';
$summaryFile = $logDir . 'resumen.json';

// Crear directorio si no existe
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
    // Crear .htaccess para proteger los logs
    file_put_contents($logDir . '.htaccess', "Order deny,allow\nDeny from all\n");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos']);
        exit;
    }

    // Sanitizar datos
    $entrada = [
        'timestamp'  => date('Y-m-d H:i:s'),
        'fecha'      => date('Y-m-d'),
        'hora'       => date('H:i'),
        'pregunta'   => substr(strip_tags($data['pregunta'] ?? ''), 0, 500),
        'respuesta'  => substr(strip_tags($data['respuesta'] ?? ''), 0, 1000),
        'idioma'     => substr(strip_tags($data['idioma'] ?? 'es'), 0, 5),
        'pagina'     => substr(strip_tags($data['pagina'] ?? ''), 0, 200),
        'intereses'  => array_slice((array)($data['intereses'] ?? []), 0, 10),
        'dias'       => (int)($data['dias'] ?? 0),
        'dispositivo'=> (strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Mobile') !== false) ? 'mobile' : 'desktop',
        'ip_hash'    => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? ''), // Anonimizado
    ];

    // Leer log existente
    $logs = [];
    if (file_exists($logFile)) {
        $existing = file_get_contents($logFile);
        $logs = json_decode($existing, true) ?? [];
    }

    // Añadir nueva entrada
    $logs[] = $entrada;

    // Guardar (máximo 5000 entradas por archivo mensual)
    if (count($logs) > 5000) {
        array_shift($logs);
    }
    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Actualizar resumen estadístico
    actualizarResumen($summaryFile, $entrada);

    echo json_encode(['ok' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Verificar acceso con clave simple (cambiar por autenticación real)
    $clave = $_GET['clave'] ?? '';
    $claveCorrecta = 'antonio2026rutas'; // Cambiar esta clave
    
    if ($clave !== $claveCorrecta) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        exit;
    }

    $accion = $_GET['accion'] ?? 'resumen';

    if ($accion === 'resumen') {
        if (file_exists($summaryFile)) {
            echo file_get_contents($summaryFile);
        } else {
            echo json_encode(['mensaje' => 'Sin datos aún']);
        }
    } elseif ($accion === 'logs') {
        $mes = $_GET['mes'] ?? date('Y-m');
        $archivo = $logDir . 'conversaciones_' . $mes . '.json';
        if (file_exists($archivo)) {
            echo file_get_contents($archivo);
        } else {
            echo json_encode([]);
        }
    } elseif ($accion === 'meses') {
        $archivos = glob($logDir . 'conversaciones_*.json');
        $meses = [];
        foreach ($archivos as $a) {
            preg_match('/conversaciones_(\d{4}-\d{2})\.json/', $a, $m);
            if ($m) $meses[] = $m[1];
        }
        echo json_encode($meses);
    }
    exit;
}

function actualizarResumen($summaryFile, $entrada) {
    $resumen = [];
    if (file_exists($summaryFile)) {
        $resumen = json_decode(file_get_contents($summaryFile), true) ?? [];
    }

    $resumen['total_conversaciones'] = ($resumen['total_conversaciones'] ?? 0) + 1;
    $resumen['ultima_actualizacion'] = date('Y-m-d H:i:s');

    // Top preguntas (palabras clave)
    $palabras = preg_split('/\s+/', strtolower($entrada['pregunta']));
    $stopwords = ['de', 'la', 'el', 'en', 'y', 'a', 'que', 'es', 'un', 'una', 'los', 'las', 'me', 'mi', 'con', 'por', 'para', 'como', 'qué', 'hay', 'the', 'and', 'is', 'in', 'to', 'of'];
    foreach ($palabras as $p) {
        $p = trim($p, '.,?!¿¡');
        if (strlen($p) > 3 && !in_array($p, $stopwords)) {
            $resumen['palabras_clave'][$p] = ($resumen['palabras_clave'][$p] ?? 0) + 1;
        }
    }
    // Ordenar y mantener top 50
    if (!empty($resumen['palabras_clave'])) {
        arsort($resumen['palabras_clave']);
        $resumen['palabras_clave'] = array_slice($resumen['palabras_clave'], 0, 50, true);
    }

    // Intereses más populares
    foreach ($entrada['intereses'] as $interes) {
        $resumen['intereses'][$interes] = ($resumen['intereses'][$interes] ?? 0) + 1;
    }

    // Dispositivos
    $resumen['dispositivos'][$entrada['dispositivo']] = ($resumen['dispositivos'][$entrada['dispositivo']] ?? 0) + 1;

    // Idiomas
    $resumen['idiomas'][$entrada['idioma']] = ($resumen['idiomas'][$entrada['idioma']] ?? 0) + 1;

    // Por día de la semana
    $diaSemana = date('N'); // 1=Lunes, 7=Domingo
    $resumen['por_dia_semana'][$diaSemana] = ($resumen['por_dia_semana'][$diaSemana] ?? 0) + 1;

    // Por hora
    $hora = date('H');
    $resumen['por_hora'][$hora] = ($resumen['por_hora'][$hora] ?? 0) + 1;

    file_put_contents($summaryFile, json_encode($resumen, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
?>

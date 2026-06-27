<?php
/**
 * PASAPORTE RURAL — API de Estadísticas (JSON)
 * GET ?rol=gestor  → stats del propietario Premium
 * GET ?rol=turista → stats del turista
 */
declare(strict_types=1);

define('API_NO_HEADERS', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$rol     = $_GET['rol'] ?? 'turista';

try { $pdo = getDBConnection(); }
catch (Exception $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'BD no disponible']);
    exit;
}

// =============================================================================
// ROL GESTOR
// =============================================================================
if ($rol === 'gestor') {
    $info_premium = verificar_propietario_premium($pdo, $user_id);

    if (!$info_premium) {
        echo json_encode(['success' => true, 'is_premium' => false]);
        exit;
    }

    $alo_id = (int) $info_premium['alojamiento_id'];

    // Contadores
    $st = $pdo->prepare('SELECT COUNT(*) FROM historico_sellos WHERE alojamiento_id = ?');
    $st->execute([$alo_id]); $total_sellos = (int)$st->fetchColumn();

    $st = $pdo->prepare('SELECT COUNT(*) FROM historico_sellos WHERE alojamiento_id = ? AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())');
    $st->execute([$alo_id]); $sellos_mes = (int)$st->fetchColumn();

    $st = $pdo->prepare('SELECT ROUND(AVG((puntuacion_limpieza+puntuacion_civismo)/2.0),1) FROM historico_sellos WHERE alojamiento_id = ?');
    $st->execute([$alo_id]); $media = $st->fetchColumn();

    // Últimos 8 sellos
    $st = $pdo->prepare(
        'SELECT hs.created_at, hs.puntuacion_limpieza, hs.puntuacion_civismo,
                hs.puntos_sumados, hs.descuento_nuevo, hs.subio_nivel,
                CONCAT(u.first_name," ",u.last_name) AS nombre_turista, u.avatar_url
           FROM historico_sellos hs
           JOIN pasaporte_turistas pt ON pt.id = hs.pasaporte_id
           JOIN users u ON u.id = pt.user_id
          WHERE hs.alojamiento_id = ?
          ORDER BY hs.created_at DESC LIMIT 8'
    );
    $st->execute([$alo_id]);
    $sellos = $st->fetchAll();

    foreach ($sellos as &$s) {
        $dt = new DateTime($s['created_at']);
        $s['fecha_formateada'] = $dt->format('d/m/Y');
        $s['hora_formateada']  = $dt->format('H:i');
        $s['subio_nivel']      = (bool)$s['subio_nivel'];
        $s['puntos_sumados']   = (int)$s['puntos_sumados'];
        $s['descuento_nuevo']  = (int)$s['descuento_nuevo'];
    }
    unset($s);

    echo json_encode([
        'success'     => true,
        'is_premium'  => true,
        'nombre_alojamiento' => $info_premium['nombre_alojamiento'],
        'stats'       => [
            'total_sellos' => $total_sellos,
            'sellos_mes'   => $sellos_mes,
            'media'        => $media !== null ? (float)$media : null,
        ],
        'ultimos_sellos' => $sellos,
    ]);
    exit;
}

// =============================================================================
// ROL TURISTA
// =============================================================================
$st = $pdo->prepare(
    'SELECT descuento_actual, puntos_totales, nivel, total_sellos, ultimo_sello_at
       FROM pasaporte_turistas WHERE user_id = ? AND estado = "activo" LIMIT 1'
);
$st->execute([$user_id]);
$p = $st->fetch();

if (!$p) {
    echo json_encode(['success' => true, 'tiene_pasaporte' => false]);
    exit;
}

$puntos    = (int)$p['puntos_totales'];
$descuento = (int)$p['descuento_actual'];
$en_esc    = $puntos % PUNTOS_POR_DESCUENTO;
$para_subir= ($descuento >= DESCUENTO_MAXIMO) ? 0 : PUNTOS_POR_DESCUENTO - $en_esc;
$progreso  = ($descuento >= DESCUENTO_MAXIMO) ? 100 : round(($en_esc / PUNTOS_POR_DESCUENTO) * 100);

echo json_encode([
    'success'         => true,
    'tiene_pasaporte' => true,
    'descuento'       => $descuento,
    'puntos'          => $puntos,
    'nivel'           => $p['nivel'],
    'nivel_emoji'     => NIVELES_EMOJI[$p['nivel']] ?? '🌱',
    'total_sellos'    => (int)$p['total_sellos'],
    'para_subir'      => $para_subir,
    'progreso_pct'    => $progreso,
    'ultimo_sello_at' => $p['ultimo_sello_at'],
]);

<?php
/**
 * =============================================================================
 * PASAPORTE RURAL — Configuración central del módulo
 * =============================================================================
 * Archivo  : pasaporte_rural/config.php
 * Proyecto : rutasrurales.io
 *
 * Carga la configuración del proyecto principal (api/config.php) y define
 * las constantes específicas del módulo Pasaporte Rural.
 *
 * Patrón de protección con if(!defined()) para evitar problemas si el archivo
 * se incluye varias veces (auto_prepend, includes anidados, etc.)
 * =============================================================================
 */

declare(strict_types=1);

// ── Cargar configuración central del proyecto (PDO, constantes de BD, etc.) ──
// La ruta es relativa al directorio raíz del proyecto
require_once __DIR__ . '/../api/config.php';

// Marca de carga del módulo
if (!defined('PASAPORTE_LOADED')) {
    define('PASAPORTE_LOADED', true);
}

// =============================================================================
// CONSTANTES DE NEGOCIO
// =============================================================================

/** URL base del módulo (ajustar si se mueve el directorio) */
if (!defined('PASAPORTE_URL')) {
    define('PASAPORTE_URL', 'https://rutasrurales.io/pasaporte_rural');
}

/** Segundos de validez de cada token QR (el propietario tiene esta ventana) */
if (!defined('QR_TTL_SEGUNDOS')) {
    define('QR_TTL_SEGUNDOS', 60);
}

/** Cada cuántos segundos el frontend del turista rota el QR (debe ser < QR_TTL) */
if (!defined('QR_ROTACION_SEGUNDOS')) {
    define('QR_ROTACION_SEGUNDOS', 45);
}

/** Descuento porcentual inicial al crear el pasaporte */
if (!defined('DESCUENTO_BASE')) {
    define('DESCUENTO_BASE', 5);
}

/** Descuento porcentual máximo alcanzable */
if (!defined('DESCUENTO_MAXIMO')) {
    define('DESCUENTO_MAXIMO', 10);
}

/**
 * Umbrales de puntos para cada punto porcentual de descuento.
 * Índice = puntos_totales necesarios para pasar de DESCUENTO_BASE+i → DESCUENTO_BASE+i+1
 * Con DESCUENTO_BASE=5 y DESCUENTO_MAXIMO=10 → 5 escalones de 50 puntos cada uno.
 */
if (!defined('PUNTOS_POR_DESCUENTO')) {
    define('PUNTOS_POR_DESCUENTO', 50);
}

/**
 * Umbrales de puntos para cada nivel de gamificación.
 * Formato: [nombre_nivel => puntos_mínimos]
 * Escalable: añadir nuevos niveles aquí sin tocar la lógica de cálculo.
 */
if (!defined('NIVELES_GAMIFICACION')) {
    define('NIVELES_GAMIFICACION', [
        'Viajero'    => 0,     // Nivel inicial
        'Explorador' => 101,   // Nivel medio
        'Embajador'  => 301,   // Nivel alto
    ]);
}

/** Emojis asociados a cada nivel (para las vistas) */
if (!defined('NIVELES_EMOJI')) {
    define('NIVELES_EMOJI', [
        'Viajero'    => '🌱',
        'Explorador' => '🗺️',
        'Embajador'  => '🏅',
    ]);
}

/** Puntos bonus cuando ambas puntuaciones son >= 4 ("Huésped Excelente") */
if (!defined('BONUS_EXCELENCIA')) {
    define('BONUS_EXCELENCIA', 2);
}

/** Puntuación mínima en ambas dimensiones para obtener el bonus de excelencia */
if (!defined('UMBRAL_EXCELENCIA')) {
    define('UMBRAL_EXCELENCIA', 4);
}

/** Slug del rol en la tabla 'roles' que identifica a los alojamientos Premium */
if (!defined('ROL_ALOJAMIENTO_PREMIUM')) {
    define('ROL_ALOJAMIENTO_PREMIUM', 'alojamiento_premium');
}

/** Slug del rol de turista registrado (para verificar que quien pide el QR es turista) */
if (!defined('ROL_TURISTA')) {
    define('ROL_TURISTA', 'turista');
}

/** Zona horaria del proyecto */
date_default_timezone_set('Europe/Madrid');

// =============================================================================
// FUNCIONES DE UTILIDAD DEL MÓDULO
// (protegidas contra doble declaración)
// =============================================================================

/**
 * Iniciar sesión segura con parámetros adecuados.
 * Si ya hay sesión activa no hace nada.
 */
if (!function_exists('pasaporte_iniciar_sesion')) {
    function pasaporte_iniciar_sesion(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $es_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                        || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

            session_set_cookie_params([
                'lifetime' => 7200,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $es_https,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);

            session_start();
        }
    }
}

/**
 * Obtener la IP real del visitante (soporta proxies y CloudFlare).
 */
if (!function_exists('pasaporte_obtener_ip')) {
    function pasaporte_obtener_ip(): string
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($headers as $h) {
            if (!empty($_SERVER[$h])) {
                $ip = trim(explode(',', $_SERVER[$h])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return 'desconocida';
    }
}

/**
 * Escapar HTML para salida segura en vistas.
 */
if (!function_exists('esc_p')) {
    function esc_p(string $val): string
    {
        return htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/**
 * Calcular el nivel de gamificación según los puntos totales.
 *
 * @param  int    $puntos  Puntos totales del turista
 * @return string          Nombre del nivel ('Viajero', 'Explorador', 'Embajador')
 */
if (!function_exists('calcular_nivel')) {
    function calcular_nivel(int $puntos): string
    {
        $nivel_actual = 'Viajero';
        foreach (NIVELES_GAMIFICACION as $nombre => $umbral) {
            if ($puntos >= $umbral) {
                $nivel_actual = $nombre;
            }
        }
        return $nivel_actual;
    }
}

/**
 * Calcular el descuento según los puntos totales.
 * Fórmula: 5% base + 1% por cada PUNTOS_POR_DESCUENTO puntos, máximo 10%.
 *
 * @param  int  $puntos  Puntos totales
 * @return int           Porcentaje de descuento (5-10)
 */
if (!function_exists('calcular_descuento')) {
    function calcular_descuento(int $puntos): int
    {
        $incremento = (int) floor($puntos / PUNTOS_POR_DESCUENTO);
        return min(DESCUENTO_MAXIMO, DESCUENTO_BASE + $incremento);
    }
}

/**
 * Calcular los puntos que suma un sello concreto.
 * Base: limpieza + civismo (máx 10).
 * Bonus: +BONUS_EXCELENCIA si ambas >= UMBRAL_EXCELENCIA.
 *
 * @param  int  $limpieza  Puntuación 1-5
 * @param  int  $civismo   Puntuación 1-5
 * @return array           ['base', 'bonus', 'total']
 */
if (!function_exists('calcular_puntos_sello')) {
    function calcular_puntos_sello(int $limpieza, int $civismo): array
    {
        $base  = $limpieza + $civismo;
        $bonus = ($limpieza >= UMBRAL_EXCELENCIA && $civismo >= UMBRAL_EXCELENCIA)
                    ? BONUS_EXCELENCIA
                    : 0;
        return [
            'base'  => $base,
            'bonus' => $bonus,
            'total' => $base + $bonus,
        ];
    }
}

/**
 * Verificar que el usuario logueado es un turista con pasaporte activo.
 * Devuelve el array del pasaporte o lanza una respuesta JSON de error.
 *
 * @param  PDO    $pdo
 * @param  int    $user_id
 * @return array  Fila de pasaporte_turistas
 */
if (!function_exists('verificar_pasaporte_activo')) {
    function verificar_pasaporte_activo(PDO $pdo, int $user_id): array
    {
        $stmt = $pdo->prepare(
            'SELECT * FROM pasaporte_turistas WHERE user_id = ? AND estado = "activo" LIMIT 1'
        );
        $stmt->execute([$user_id]);
        $pasaporte = $stmt->fetch();

        if (!$pasaporte) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error'   => 'No tienes un Pasaporte Rural activo.',
            ]);
            exit;
        }

        return $pasaporte;
    }
}

/**
 * Verificar que el usuario logueado es propietario de un alojamiento Premium.
 * Comprueba is_premium en accommodations + vinculación en user_resources.
 *
 * @param  PDO  $pdo
 * @param  int  $user_id
 * @return array|false  Array con [alojamiento_id, nombre_alojamiento] o false
 */
if (!function_exists('verificar_propietario_premium')) {
    function verificar_propietario_premium(PDO $pdo, int $user_id): array|false
    {
        $stmt = $pdo->prepare(
            'SELECT a.id AS alojamiento_id, a.name AS nombre_alojamiento
               FROM accommodations a
               JOIN user_resources ur ON ur.resource_id = a.id
                    AND ur.resource_type = "accommodation"
                    AND ur.role = "owner"
              WHERE ur.user_id = ?
                AND a.is_premium = 1
              LIMIT 1'
        );
        $stmt->execute([$user_id]);
        return $stmt->fetch() ?: false;
    }
}

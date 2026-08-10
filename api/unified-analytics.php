<?php
/**
 * Sistema Unificado de Analytics - Sincronización con Google Search Console
 * 
 * Este sistema centraliza el conteo de visitas para garantizar coherencia
 * entre los datos internos y Google Search Console
 */

require_once 'config.php';

class UnifiedAnalytics {
    
    private $pdo;
    private $enableGoogleAnalytics;
    
    public function __construct() {
        $this->pdo = getDBConnection();
        $this->enableGoogleAnalytics = true; // Configurar según necesidad
    }
    
    /**
     * Registra una vista de forma unificada
     */
    public function trackView($resourceType, $resourceId, $userAgent = null, $ip = null) {
        try {
            // Evitar conteo múltiple del mismo usuario en un período corto
            $sessionKey = "view_{$resourceType}_{$resourceId}";
            
            if (isset($_SESSION[$sessionKey]) && 
                time() - $_SESSION[$sessionKey] < 300) { // 5 minutos
                return ['success' => true, 'counted' => false, 'reason' => 'duplicate_session'];
            }
            
            // También revisar por cookie
            $cookieKey = "view_{$resourceType}_{$resourceId}";
            if (isset($_COOKIE[$cookieKey])) {
                return ['success' => true, 'counted' => false, 'reason' => 'duplicate_cookie'];
            }
            
            // 1. Actualizar resource_stats (sistema principal)
            $this->updateResourceStats($resourceType, $resourceId);
            
            // 2. Actualizar contador específico de la tabla correspondiente
            $this->updateTableViewCounter($resourceType, $resourceId);
            
            // 3. Registrar en log de analytics para debugging
            $this->logAnalyticsEvent($resourceType, $resourceId, $userAgent, $ip);
            
            // 4. Marcar sesión y cookie para evitar duplicados
            $_SESSION[$sessionKey] = time();
            setcookie($cookieKey, '1', time() + 3600, '/'); // 1 hora
            
            // 5. Enviar evento a Google Analytics si está habilitado
            if ($this->enableGoogleAnalytics) {
                $this->sendToGoogleAnalytics($resourceType, $resourceId);
            }
            
            return ['success' => true, 'counted' => true];
            
        } catch (Exception $e) {
            error_log('UnifiedAnalytics Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Actualiza la tabla resource_stats
     */
    private function updateResourceStats($resourceType, $resourceId) {
        // Crear registro si no existe
        $stmtUpsert = $this->pdo->prepare("
            INSERT INTO resource_stats (resource_type, resource_id, views_count, last_view_at)
            VALUES (:resource_type, :resource_id, 1, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE 
                views_count = views_count + 1,
                last_view_at = CURRENT_TIMESTAMP
        ");
        
        $stmtUpsert->execute([
            'resource_type' => $resourceType,
            'resource_id' => $resourceId
        ]);
    }
    
    /**
     * Actualiza contador en tabla específica
     */
    private function updateTableViewCounter($resourceType, $resourceId) {
        $table = $this->getTableName($resourceType);
        if (!$table) return;
        
        $stmt = $this->pdo->prepare("
            UPDATE {$table} 
            SET views_count = COALESCE(views_count, 0) + 1,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        
        $stmt->execute(['id' => $resourceId]);
    }
    
    /**
     * Obtiene nombre de tabla según tipo de recurso
     */
    private function getTableName($resourceType) {
        $tables = [
            'accommodation' => 'accommodations',
            'event' => 'cultural_events',
            'place' => 'places_of_interest',
            'activity' => 'tourist_activities',
            'route' => 'routes'
        ];
        
        return $tables[$resourceType] ?? null;
    }
    
    /**
     * Registra evento en log de analytics
     */
    private function logAnalyticsEvent($resourceType, $resourceId, $userAgent, $ip) {
        $stmtLog = $this->pdo->prepare("
            INSERT INTO analytics_log (
                resource_type, resource_id, event_type, 
                user_agent, ip_address, created_at
            ) VALUES (?, ?, 'view', ?, ?, CURRENT_TIMESTAMP)
        ");
        
        $stmtLog->execute([
            $resourceType, 
            $resourceId, 
            substr($userAgent, 0, 255), 
            $ip
        ]);
    }
    
    /**
     * Envía evento a Google Analytics
     */
    private function sendToGoogleAnalytics($resourceType, $resourceId) {
        // Enviar via gtag si está disponible
        $js = "
        if (typeof gtag !== 'undefined') {
            gtag('event', 'page_view', {
                'resource_type': '{$resourceType}',
                'resource_id': {$resourceId},
                'custom_map': {
                    'dimension1': '{$resourceType}',
                    'dimension2': '{$resourceId}'
                }
            });
        }
        ";
        
        // Guardar para incluir en el HTML
        if (!isset($_SESSION['ga_events'])) {
            $_SESSION['ga_events'] = [];
        }
        $_SESSION['ga_events'][] = $js;
    }
    
    /**
     * Obtiene estadísticas de un recurso
     */
    public function getResourceStats($resourceType, $resourceId) {
        $stmt = $this->pdo->prepare("
            SELECT views_count, interests_count, messages_count, 
                   favorites_count, last_view_at
            FROM resource_stats 
            WHERE resource_type = ? AND resource_id = ?
        ");
        
        $stmt->execute([$resourceType, $resourceId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$stats) {
            // Crear registro inicial
            $this->pdo->prepare("
                INSERT INTO resource_stats (resource_type, resource_id, views_count)
                VALUES (?, ?, 0)
            ")->execute([$resourceType, $resourceId]);
            
            return [
                'views_count' => 0,
                'interests_count' => 0,
                'messages_count' => 0,
                'favorites_count' => 0,
                'last_view_at' => null
            ];
        }
        
        return $stats;
    }
    
    /**
     * Sincroniza contadores existentes
     */
    public function syncExistingCounters() {
        $tables = [
            'accommodations' => 'accommodation',
            'cultural_events' => 'event',
            'places_of_interest' => 'place',
            'tourist_activities' => 'activity',
            'routes' => 'route'
        ];
        
        $synced = [];
        
        foreach ($tables as $table => $resourceType) {
            $stmt = $this->pdo->query("
                SELECT id, COALESCE(views_count, 0) as views_count 
                FROM {$table} 
                WHERE views_count > 0
            ");
            
            $count = 0;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Actualizar resource_stats con el contador existente
                $stmtSync = $this->pdo->prepare("
                    INSERT INTO resource_stats (resource_type, resource_id, views_count)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE views_count = GREATEST(views_count, ?)
                ");
                
                $stmtSync->execute([
                    $resourceType, 
                    $row['id'], 
                    $row['views_count'],
                    $row['views_count']
                ]);
                $count++;
            }
            
            $synced[$resourceType] = $count;
        }
        
        return $synced;
    }
    
    /**
     * Crea tabla de analytics_log si no existe
     */
    public function createAnalyticsLogTable() {
        $sql = "
        CREATE TABLE IF NOT EXISTS analytics_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            resource_type VARCHAR(50) NOT NULL,
            resource_id INT NOT NULL,
            event_type ENUM('view', 'interest', 'favorite', 'message') NOT NULL,
            user_agent VARCHAR(255),
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_resource (resource_type, resource_id),
            INDEX idx_date (created_at)
        )";
        
        return $this->pdo->exec($sql);
    }
    
    /**
     * Reporte diario para comparar con Google Search Console
     */
    public function getDailyReport($date = null) {
        if (!$date) $date = date('Y-m-d');
        
        $stmt = $this->pdo->prepare("
            SELECT 
                resource_type,
                COUNT(*) as total_views,
                COUNT(DISTINCT resource_id) as unique_resources
            FROM analytics_log 
            WHERE event_type = 'view' 
            AND DATE(created_at) = ?
            GROUP BY resource_type
            ORDER BY total_views DESC
        ");
        
        $stmt->execute([$date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// API Endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    
    $analytics = new UnifiedAnalytics();
    
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'track_view':
            $result = $analytics->trackView(
                $data['resource_type'],
                $data['resource_id'],
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            break;
            
        case 'get_stats':
            $result = $analytics->getResourceStats(
                $data['resource_type'],
                $data['resource_id']
            );
            break;
            
        case 'sync_counters':
            $result = $analytics->syncExistingCounters();
            break;
            
        case 'daily_report':
            $result = $analytics->getDailyReport($data['date'] ?? null);
            break;
            
        default:
            $result = ['error' => 'Invalid action'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Si se accede directamente, mostrar información
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['info'])) {
    $analytics = new UnifiedAnalytics();
    
    // Crear tabla de logs si no existe
    $analytics->createAnalyticsLogTable();
    
    echo "<h2>Sistema Unificado de Analytics</h2>";
    echo "<p>Tabla analytics_log creada/verificada correctamente.</p>";
    
    // Mostrar reporte del día
    $report = $analytics->getDailyReport();
    echo "<h3>Reporte de hoy:</h3>";
    echo "<pre>" . print_r($report, true) . "</pre>";
}
?>
<?php
include 'db.php';
require_once __DIR__ . '/../api/inbound_links_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    
    // 1. Campos que NO queremos incluir en el bucle dinámico del UPDATE
    $exclude = ['id']; 

    // 2. Construcción dinámica de la consulta
    $fields = [];
    $values = [];

    foreach ($_POST as $key => $value) {
        if (!in_array($key, $exclude)) {
            // Manejo de valores vacíos para evitar errores de integridad
            if ($value === '' || $value === null) {
                $fields[] = "`$key` = NULL";
            } else {
                $fields[] = "`$key` = ?";
                $values[] = $value;
            }
        }
    }

    // ─── INBOUND LINKS: generar description_linked ───────────────────────────
    // Se procesa el texto de description y se almacena ya con los links
    // Para que las páginas modulares lo sirvan directamente (SSR, sin overhead)
    $description_raw = isset($_POST['description']) ? $_POST['description'] : '';
    $description_linked = procesarInboundLinks($description_raw, $pdo);
    $fields[] = '`description_linked` = ?';
    $values[] = $description_linked;
    // ─────────────────────────────────────────────────────────────────────────

    // Añadimos el ID al final para el WHERE
    $values[] = $id;

    // 3. Preparar y ejecutar la sentencia
    $sql = "UPDATE cultural_events SET " . implode(', ', $fields) . " WHERE id = ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        // --- REGENERAR SITEMAP i18n AUTOMÁTICAMENTE ---
        // Cada vez que se guarda un evento, regeneramos el sitemap de traducciones
        try {
            define('REGENERAR_SITEMAP_DESDE_ADMIN', true);
            include __DIR__ . '/cron/regenerar_sitemap_i18n.php';
        } catch (Exception $e) {
            // Si falla la regeneración del sitemap, no bloqueamos el guardado
            error_log("Error regenerando sitemap i18n: " . $e->getMessage());
        }
        
        // --- REDIRECCIÓN AL ÍNDICE DE EVENTOS ---
        // Cambiado a eventos_index.php según tu petición
        header("Location: eventos_index.php?msg=updated&id=" . $id);
        exit;
        // ----------------------------------------
        
    } catch (PDOException $e) {
        // En caso de error, lo mostramos para depurar
        die("Error al actualizar el evento: " . $e->getMessage());
    }
} else {
    die("Acceso no permitido.");
}
?>
<?php
// Solución de emergencia para el problema de contact_email
// Este script fuerza la conversión de campos problemáticos a NULL

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    if (!$id) {
        die("ID de actividad no proporcionado.");
    }

    $datos = $_POST;
    unset($datos['id']);

    $campos = "";
    $params = [];

    // Obtener columnas reales
    try {
        $q = $pdo->query("DESCRIBE tourist_activities");
        $columnas_reales = $q->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        die("Error al verificar la estructura de la tabla: " . $e->getMessage());
    }

    // Lista de campos problemáticos conocidos
    $campos_problematicos = [
        'schedule', 'available_days', 'available_seasons', 'languages_available',
        'provided_equipment', 'accessibility', 'gallery', 'price_details',
        'contact_email', 'contact_phone', 'website'
    ];
    
    foreach ($datos as $columna => $valor) {
        if (in_array($columna, $columnas_reales)) {
            
            // SOLUCIÓN DE EMERGENCIA: Convertir campos problemáticos a NULL si no están vacíos
            if (is_string($valor)) {
                $valor_trim = trim($valor);
                
                if ($valor_trim === '') {
                    $valor = null;
                }
                // Para campos problemáticos, forzar NULL temporalmente
                elseif (in_array($columna, $campos_problematicos)) {
                    echo "<p><strong>⚠️ ADVERTENCIA:</strong> Campo '$columna' convertido a NULL temporalmente</p>";
                    $valor = null;
                }
            }

            $campos .= "`$columna` = :$columna, ";
            $params[$columna] = $valor;
        }
    }
    
    $campos = rtrim($campos, ", ");

    if (empty($campos)) {
        die("No hay campos válidos para actualizar.");
    }

    try {
        $sql = "UPDATE `tourist_activities` SET $campos WHERE `id` = :id_param";
        $stmt = $pdo->prepare($sql);
        $params['id_param'] = $id;

        echo "<h3>Ejecutando solución de emergencia</h3>";
        echo "<p><strong>SQL:</strong> $sql</p>";
        echo "<p><strong>Parámetros (campos problemáticos = NULL):</strong><br>";
        foreach ($params as $key => $value) {
            echo "  $key = " . (is_null($value) ? 'NULL' : "'$value'") . "<br>";
        }
        echo "</p>";

        $stmt->execute($params);

        echo "<h3 style='color: green;'>✅ Guardado exitoso con solución de emergencia</h3>";
        echo "<p>Los campos problemáticos se han convertido a NULL temporalmente.</p>";
        echo "<p><a href='actividades_editar.php?id=$id'>Volver a editar</a></p>";

    } catch (PDOException $e) {
        echo "<h3 style='color: red;'>❌ Error incluso con solución de emergencia</h3>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "<p>El problema es más grave de lo esperado.</p>";
    }
} else {
    echo "<form method='POST'>";
    echo "<h3>Solución de emergencia para contact_email</h3>";
    echo "<p>Esta solución convierte temporalmente los campos problemáticos a NULL.</p>";
    echo "<p>ID de actividad: <input type='text' name='id' value='15'></p>";
    echo "<p><input type='submit' value='Ejecutar solución de emergencia'></p>";
    echo "</form>";
}
?>
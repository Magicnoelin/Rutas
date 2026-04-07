<?php
include 'db.php';

// Función auxiliar para verificar si un string es JSON válido
function isValidJson($string) {
    if (empty($string)) return false;
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    if (!$id) {
        die("ID de actividad no proporcionado.");
    }

    $datos = $_POST;
    unset($datos['id']); // Quitamos el ID para que no intente hacer "SET id = :id"

    $campos = "";
    $params = [];

    // 1. Obtener la lista de columnas reales de la tabla para evitar errores
    // Si el formulario envía algo que no está en la tabla, lo ignoramos.
    try {
        $q = $pdo->query("DESCRIBE tourist_activities");
        $columnas_reales = $q->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        die("Error al verificar la estructura de la tabla: " . $e->getMessage());
    }

    // Lista de campos que pueden tener restricciones CHECK de JSON
    $campos_json_con_restricciones = [
        'schedule', 'available_days', 'available_seasons', 'languages_available',
        'provided_equipment', 'accessibility', 'gallery', 'price_details'
    ];
    
    foreach ($datos as $columna => $valor) {
        // Solo procesamos si la columna existe en la base de datos
        if (in_array($columna, $columnas_reales)) {
            
            // Tratamiento de valores según su contenido
            if (is_string($valor)) {
                $valor_trim = trim($valor);
                
                // Si está vacío, convertir a NULL
                if ($valor_trim === '') {
                    $valor = null;
                }
                // Si es un campo con restricción JSON
                elseif (in_array($columna, $campos_json_con_restricciones)) {
                    // Si parece JSON vacío, convertir a NULL
                    if ($valor_trim === '[]' || $valor_trim === '{}' || $valor_trim === '""' || 
                        $valor_trim === 'null' || $valor_trim === 'NULL') {
                        $valor = null;
                    }
                    // Si no es JSON válido, intentar convertirlo a JSON simple
                    elseif (!isValidJson($valor_trim)) {
                        // Para texto simple como "9:00-14:00", convertirlo a JSON simple
                        if ($columna === 'schedule') {
                            // Si es texto de horario, crear un JSON simple
                            $valor = json_encode(['horario' => $valor_trim]);
                        } else {
                            // Para otros campos, crear un array simple
                            $valor = json_encode([$valor_trim]);
                        }
                    }
                    // Si ya es JSON válido, mantenerlo
                }
            }

            $campos .= "`$columna` = :$columna, ";
            $params[$columna] = $valor;
        }
    }
    
    // Quitamos la última coma
    $campos = rtrim($campos, ", ");

    if (empty($campos)) {
        die("No hay campos válidos para actualizar.");
    }

    try {
        // Usamos comillas invertidas `` en el nombre de la tabla y campos por si hay nombres reservados
        $sql = "UPDATE `tourist_activities` SET $campos WHERE `id` = :id_param";
        $stmt = $pdo->prepare($sql);
        
        // Añadimos el ID con un nombre diferente para evitar conflictos si existiera una columna llamada 'id' en el loop
        $params['id_param'] = $id;

        $stmt->execute($params);

        // Redirección con éxito
        header("Location: actividades_index.php?status=ok");
        exit();

    } catch (PDOException $e) {
        // En producción, sería mejor loguear el error y mostrar un mensaje genérico
        die("Error crítico al guardar en la base de datos: " . $e->getMessage());
    }
} else {
    header("Location: actividades_index.php");
    exit();
}
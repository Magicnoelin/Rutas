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
                // Validación para teléfono (evitar caracteres problemáticos)
                elseif ($columna === 'contact_phone' && $valor_trim !== '') {
                    // Limpiar teléfono: mantener solo números, +, espacios, paréntesis y guiones
                    $valor = preg_replace('/[^\d\s\+\-\(\)]/', '', $valor_trim);
                    // Limitar longitud
                    if (strlen($valor) > 50) {
                        $valor = substr($valor, 0, 50);
                    }
                }
                // Si es un campo con restricción JSON
                elseif (in_array($columna, $campos_json_con_restricciones)) {
                    // Si está vacío o parece JSON vacío, convertir a NULL
                    if ($valor_trim === '' || $valor_trim === '[]' || $valor_trim === '{}' || 
                        $valor_trim === '""' || $valor_trim === 'null' || $valor_trim === 'NULL') {
                        $valor = null;
                    }
                    // Si no es JSON válido, FORZAR conversión a JSON simple
                    elseif (!isValidJson($valor_trim)) {
                        // Para schedule específicamente
                        if ($columna === 'schedule') {
                            // Intentar detectar si es texto de horario simple
                            if (preg_match('/\d+[:\.]\d+/', $valor_trim)) {
                                $valor = json_encode(['horario' => $valor_trim]);
                            } else {
                                // Si no parece horario, crear array simple
                                $valor = json_encode([$valor_trim]);
                            }
                        } 
                        // Para available_days, available_seasons, languages_available
                        elseif (in_array($columna, ['available_days', 'available_seasons', 'languages_available'])) {
                            // Si tiene comas, separar en array
                            if (strpos($valor_trim, ',') !== false) {
                                $items = array_map('trim', explode(',', $valor_trim));
                                $valor = json_encode($items);
                            } else {
                                $valor = json_encode([$valor_trim]);
                            }
                        }
                        // Para otros campos
                        else {
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

        // DEBUG: Mostrar información antes de ejecutar
        echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px; border: 1px solid #ccc;'>";
        echo "<h3>DEBUG - Información antes de guardar</h3>";
        echo "<p><strong>SQL:</strong> $sql</p>";
        echo "<p><strong>Parámetros:</strong><br>";
        foreach ($params as $key => $value) {
            echo "  $key = " . (is_null($value) ? 'NULL' : "'" . htmlspecialchars($value) . "'") . "<br>";
        }
        echo "</p>";
        echo "<p><strong>Campos con restricciones JSON:</strong> " . implode(', ', $campos_json_con_restricciones) . "</p>";
        echo "</div>";

        $stmt->execute($params);

        echo "<div style='background: #d4edda; padding: 20px; margin: 20px; border: 1px solid #c3e6cb;'>";
        echo "<h3>✅ Guardado exitoso</h3>";
        echo "<p>Los cambios se han guardado correctamente.</p>";
        echo "<p><a href='actividades_index.php'>Volver al listado</a> | <a href='actividades_editar.php?id=$id'>Seguir editando</a></p>";
        echo "</div>";
        exit();

    } catch (PDOException $e) {
        // Mostrar información detallada del error para debugging
        $error_info = $stmt->errorInfo();
        echo "<div style='background: #f8d7da; padding: 20px; margin: 20px; border: 1px solid #f5c6cb;'>";
        echo "<h3>❌ Error al guardar en la base de datos</h3>";
        echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Código error:</strong> " . $e->getCode() . "</p>";
        if (!empty($error_info)) {
            echo "<p><strong>Info error PDO:</strong><br>";
            echo "SQLSTATE: " . $error_info[0] . "<br>";
            echo "Código: " . $error_info[1] . "<br>";
            echo "Mensaje: " . $error_info[2] . "</p>";
        }
        
        // Información adicional sobre el error
        if (strpos($e->getMessage(), 'CHECK') !== false) {
            echo "<p><strong>⚠️ ERROR DE RESTRICCIÓN CHECK:</strong></p>";
            echo "<p>La base de datos tiene una restricción que valida el formato de los datos.</p>";
            echo "<p>Posibles causas:</p>";
            echo "<ul>";
            echo "<li>Campo 'schedule' debe ser JSON válido</li>";
            echo "<li>Campo 'available_days' debe ser JSON válido</li>";
            echo "<li>Campo 'contact_email' debe tener formato de email válido</li>";
            echo "<li>Otros campos con restricciones de formato</li>";
            echo "</ul>";
        }
        
        echo "<p><strong>SQL ejecutado:</strong><br><code>$sql</code></p>";
        echo "<p><strong>Valores de parámetros:</strong><br>";
        foreach ($params as $key => $value) {
            $display_value = is_null($value) ? 'NULL' : "'" . htmlspecialchars($value) . "'";
            echo "<code>$key = $display_value</code><br>";
        }
        echo "</p>";
        
        echo "<p><a href='actividades_index.php'>Volver al listado</a> | <a href='actividades_editar.php?id=$id'>Corregir datos</a></p>";
        echo "</div>";
        exit();
    }
} else {
    header("Location: actividades_index.php");
    exit();
}
<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $datos = $_POST;
    unset($datos['id']);

    $campos = "";
    $params = [];

    foreach ($datos as $columna => $valor) {
        // 1. Si el valor es una cadena vacía, lo convertimos en NULL real
        // Esto evita errores en fechas, números y campos JSON
        if (trim($valor) === '') {
            $valor = null;
        }

        $campos .= "$columna = :$columna, ";
        $params[$columna] = $valor;
    }
    
    $campos = rtrim($campos, ", ");

    try {
        $sql = "UPDATE cultural_events SET $campos WHERE id = :id"; // Asegúrate de poner el nombre correcto aquí
        $stmt = $pdo->prepare($sql);
        
        $params['id'] = $id;
        $stmt->execute($params);

        header("Location: eventos_index.php?status=ok");
    } catch (PDOException $e) {
        // Esto es VITAL: Si falla, nos dirá EXACTAMENTE por qué columna es
        die("Error al guardar: " . $e->getMessage() . " | Revisa la columna que causa el fallo.");
    }
}
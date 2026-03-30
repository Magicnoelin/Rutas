<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $datos = $_POST;
    unset($datos['id']);

    $campos = "";
    $params = [];

    foreach ($datos as $columna => $valor) {
        // Manejo de valores vacíos para que no den error en campos numéricos o JSON
        if (trim($valor) === '') {
            $valor = null;
        }
        $campos .= "$columna = :$columna, ";
        $params[$columna] = $valor;
    }
    
    $campos = rtrim($campos, ", ");

    try {
        $sql = "UPDATE tourist_activities SET $campos WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $params['id'] = $id;
        $stmt->execute($params);

        header("Location: actividades_index.php?status=ok");
    } catch (PDOException $e) {
        die("Error al guardar actividad: " . $e->getMessage());
    }
}
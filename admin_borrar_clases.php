<?php
require_once 'auth.php';
requerir_rol(['administrador']);
require_once 'dbconn.php';

// Debug - ver qué llega
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['clases_ids'])) {
        $ids = array_map('intval', (array)$_POST['clases_ids']);
        $ids = array_filter($ids); // sacar ceros
        if (!empty($ids)) {
            $ids_str = implode(',', $ids);
            mysqli_query($conex, "DELETE FROM inscripciones WHERE clase_id IN ($ids_str)");
            mysqli_query($conex, "DELETE FROM asistencias WHERE clase_id IN ($ids_str)");
            $result = mysqli_query($conex, "DELETE FROM clases WHERE id IN ($ids_str)");
        }
    }
}
header('Location: acceso_administrador.php');
exit();

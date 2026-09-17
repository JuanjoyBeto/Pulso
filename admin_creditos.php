<?php
require_once 'auth.php';
requerir_rol(['administrador']);
require_once 'dbconn.php';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $alumno_id = (int)$_POST['alumno_id'];
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'agregar' && !empty($_POST['creditos_agregar'])) {
        $cant = (int)$_POST['creditos_agregar'];
        
        // Usar la fecha que puso Dami, si no puso ninguna usar fin de mes
        if (!empty($_POST['vencimiento']) && $_POST['vencimiento'] !== '') {
            $venc = $_POST['vencimiento']; // viene como YYYY-MM-DD del input date
        } else {
            $venc = date('Y-m-t'); // fin del mes actual
        }
        
        $venc = mysqli_real_escape_string($conex, $venc);
        
        mysqli_query($conex, "UPDATE registronuevo SET creditos = COALESCE(creditos,0) + $cant, vencimiento_creditos='$venc' WHERE id=$alumno_id");
        mysqli_query($conex, "INSERT INTO abonos (alumno_id, creditos_total, creditos_usados, fecha_inicio, fecha_fin) VALUES ($alumno_id, $cant, 0, CURDATE(), '$venc')");
        
    } elseif ($accion === 'quitar' && !empty($_POST['creditos_quitar'])) {
        $cant = (int)$_POST['creditos_quitar'];
        mysqli_query($conex, "UPDATE registronuevo SET creditos = GREATEST(0, COALESCE(creditos,0) - $cant) WHERE id=$alumno_id");
    }
}

// Volver al perfil del alumno si viene de ahí
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (strpos($referer, 'admin_alumno.php') !== false) {
    header('Location: ' . $referer);
} else {
    header('Location: acceso_administrador.php');
}
exit();

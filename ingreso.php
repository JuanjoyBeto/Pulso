<?php
require_once 'auth.php';
require_once 'dbconn.php';

if (isset($_POST['Enviar'])) {
    $usuario = mysqli_real_escape_string($conex, trim($_POST['usuario']));
    $password = $_POST['password'];

    $sql = "SELECT * FROM registronuevo WHERE user = '$usuario' LIMIT 1";
    $result = mysqli_query($conex, $sql);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row['pass'])) {
            $_SESSION['usuario']         = $row['user'];
            $_SESSION['usuario_id']      = $row['id'];
            $_SESSION['nombre_completo'] = $row['nombre'] . ' ' . $row['apellido'];
            $_SESSION['rol']             = $row['rol'] ?? 'alumno';
            mysqli_close($conex);
            redirigir_por_rol($_SESSION['rol']);
        }
    }
    mysqli_close($conex);
    header('Location: index.php?error=1');
    exit();
}
header('Location: index.php');
exit();

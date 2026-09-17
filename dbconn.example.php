<?php
// dbconn.example.php
// Copiar este archivo como dbconn.php y completar con los datos reales.
// El archivo dbconn.php NO se sube al repositorio.

$host     = "mysql-TUUSUARIO.alwaysdata.net";
$user     = "TUUSUARIO";
$password = "TU_CONTRASEÑA";
$database = "TUUSUARIO_nombrebase";

$conex = mysqli_connect($host, $user, $password, $database);

if (!$conex) {
    die("
    <!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Error</title>
    <link rel='stylesheet' href='styles.css'></head>
    <body><div class='auth-bg'><div class='auth-card'>
    <div class='alert alert-danger'>No se pudo conectar a la base de datos.<br>
    <small>" . mysqli_connect_error() . "</small></div>
    </div></div></body></html>");
}
?>

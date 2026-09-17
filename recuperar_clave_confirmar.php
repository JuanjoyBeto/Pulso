<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'dbconn.php';
session_start();

if (!isset($_GET['e']) || !isset($_GET['t'])) {
    header('Location: index.php');
    exit();
}

$email = urldecode($_GET['e']);
$token = $_GET['t'];
$email_s = mysqli_real_escape_string($conex, $email);
$token_s = mysqli_real_escape_string($conex, $token);

$res = mysqli_query($conex, "SELECT * FROM recuperar WHERE email='$email_s' AND TOKEN='$token_s' LIMIT 1");
if (mysqli_num_rows($res) == 0) {
    header('Location: index.php?error=token');
    exit();
}
$row = mysqli_fetch_assoc($res);
$nueva = password_hash($row['CLAVE_NUEVA'], PASSWORD_DEFAULT);
mysqli_query($conex, "UPDATE registronuevo SET pass='$nueva' WHERE email='$email_s'");
mysqli_query($conex, "DELETE FROM recuperar WHERE email='$email_s'");
mysqli_close($conex);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Point - Contraseña restablecida</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="auth-bg">
  <div class="auth-card">
    <div class="auth-logo"><div class="logo-icon">📍</div><h1>TRAINING <span>POINT</span></h1></div>
    <div class="alert alert-success" style="text-align:center">
      ✅ ¡Contraseña restablecida!<br>
      <small>Ya podés ingresar con tu nueva contraseña temporal.</small>
    </div>
    <a href="index.php" class="btn btn-blue" style="display:block;text-align:center;text-decoration:none">Ir al login</a>
  </div>
</div>
</body>
</html>

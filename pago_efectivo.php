<?php
require_once 'auth.php';
requerir_login();
require_once 'dbconn.php';
$pagina_activa = 'home';
$uid = $_SESSION['usuario_id'];
$pack = (int)($_GET['pack'] ?? 0);

if (!in_array($pack, [4, 8, 12])) {
    header('Location: acceso_alumno.php');
    exit();
}

// Registrar solicitud de pago en efectivo
$nombre = mysqli_real_escape_string($conex, nombre_usuario_actual());
$msg = mysqli_real_escape_string($conex, "$nombre solicitó pago en efectivo de $pack créditos.");
mysqli_query($conex, "INSERT INTO notificaciones (alumno_id, mensaje, fecha) VALUES (NULL, '$msg', NOW())");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Point - Pago en efectivo</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body" style="text-align:center;padding-top:60px">
  <div style="font-size:48px;margin-bottom:16px">💵</div>
  <div class="page-title">¡Solicitud enviada!</div>
  <p style="color:var(--text2);font-size:14px;margin:12px 0 30px;max-width:280px;margin-left:auto;margin-right:auto">
    Dami recibió tu solicitud de <strong style="color:#fff"><?= $pack ?> créditos</strong> por efectivo. 
    Una vez que abones en el gym, Dami te carga los créditos y ya podés anotarte a clases.
  </p>
  <a href="acceso_alumno.php" class="btn btn-blue" style="display:block;text-align:center;text-decoration:none;max-width:300px;margin:0 auto">Volver al inicio</a>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

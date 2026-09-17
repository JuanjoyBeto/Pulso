<?php
require_once 'auth.php';
requerir_login();
$pagina_activa = 'home';
$pack = (int)($_GET['pack'] ?? 0);
$medio = $_GET['medio'] ?? 'transferencia';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Point - Aviso enviado</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body" style="text-align:center;padding-top:60px">
  <div style="font-size:64px;margin-bottom:16px"><?= $medio === 'efectivo' ? '💵' : '✅' ?></div>
  <div class="page-title" style="text-align:center">¡Aviso enviado!</div>
  <p style="color:var(--text2);font-size:14px;margin:12px auto 30px;max-width:280px;line-height:1.6">
    <?php if ($medio === 'efectivo'): ?>
      Le avisamos a Dami que querés pagar <strong style="color:#fff"><?= $pack ?> créditos en efectivo</strong>. Coordiná con él cuándo pasás a abonar.
    <?php else: ?>
      Le avisamos a Dami que transferiste por <strong style="color:#fff"><?= $pack ?> créditos</strong>. En cuanto confirme el pago te carga los créditos.
    <?php endif; ?>
  </p>
  <a href="acceso_alumno.php" class="btn btn-blue" style="display:block;text-align:center;text-decoration:none;max-width:300px;margin:0 auto">
    Volver al inicio
  </a>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

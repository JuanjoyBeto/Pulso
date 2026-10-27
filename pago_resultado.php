<?php
// MercadoPago devuelve al alumno acá después de pagar
require_once 'auth.php';
requerir_login();
require_once 'mp.php';
$pagina_activa = 'home';
$uid = (int)$_SESSION['usuario_id'];

$estado = $_GET['collection_status'] ?? $_GET['status'] ?? '';
$mp_id  = $_GET['payment_id'] ?? $_GET['collection_id'] ?? '';
$ref    = (int)($_GET['external_reference'] ?? 0);

// Verificamos con MP (por si el aviso automático todavía no llegó)
if ($mp_id !== '' && $mp_id !== 'null') {
    $real = mp_verificar_pago($mp_id);
    if ($real) $estado = $real;
}

$pago = null;
if ($ref) {
    $r = mysqli_query($conex, "SELECT * FROM pagos WHERE id=$ref AND alumno_id=$uid LIMIT 1");
    $pago = mysqli_fetch_assoc($r);
}
$r = mysqli_query($conex, "SELECT creditos, vencimiento_creditos FROM registronuevo WHERE id=$uid");
$yo = mysqli_fetch_assoc($r);

if ($estado === 'approved')                        { $icono = '✅'; $titulo = '¡Pago aprobado!'; }
elseif (in_array($estado, ['pending', 'in_process'])) { $icono = '⏳'; $titulo = 'Pago en proceso'; }
else                                                  { $icono = '❌'; $titulo = 'El pago no se completó'; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= gym_nombre() ?> - <?= $titulo ?></title>
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" type="image/png" href="icono_pulso.png">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body" style="text-align:center;padding-top:60px">
  <div style="font-size:64px;margin-bottom:16px"><?= $icono ?></div>
  <div class="page-title" style="text-align:center"><?= $titulo ?></div>
  <p style="color:var(--text2);font-size:14px;margin:12px auto 30px;max-width:300px;line-height:1.6">
    <?php if ($estado === 'approved'): ?>
      Ya se cargaron <?= $pago ? '<strong style="color:#fff">' . (int)$pago['creditos'] . ' créditos</strong>' : 'tus créditos' ?>.
      Ahora tenés <strong style="color:#fff"><?= (int)$yo['creditos'] ?></strong> en total
      <?php if ($yo['vencimiento_creditos']): ?>y vencen el <strong style="color:#fff"><?= date('d/m/Y', strtotime($yo['vencimiento_creditos'])) ?></strong><?php endif; ?>.
    <?php elseif ($icono === '⏳'): ?>
      MercadoPago está procesando el pago. Apenas se apruebe, los créditos se cargan solos y te avisamos.
    <?php else: ?>
      No se cobró nada. Podés intentar de nuevo o elegir otro medio de pago.
    <?php endif; ?>
  </p>
  <a href="<?= $estado === 'approved' ? 'clases.php' : 'acceso_alumno.php' ?>" class="btn btn-blue" style="display:block;text-align:center;text-decoration:none;max-width:300px;margin:0 auto">
    <?= $estado === 'approved' ? 'Anotarme a una clase' : 'Volver al inicio' ?>
  </a>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

<?php
require_once 'auth.php';
requerir_login();
require_once 'dbconn.php';
$pagina_activa = 'home';
$uid = (int)$_SESSION['usuario_id'];
$pack_id = (int)($_GET['pack'] ?? 0);

$res = mysqli_query($conex, "SELECT * FROM packs WHERE id=$pack_id AND activo=1 LIMIT 1");
$pack = mysqli_fetch_assoc($res);
if (!$pack) {
    header('Location: acceso_alumno.php');
    exit();
}

$dias  = (int)cfg('dias_vencimiento', 31);
$nombre_alumno = nombre_usuario_actual();
$precio_txt = '$' . number_format($pack['precio'], 0, ',', '.');
$error = isset($_GET['error']);

// Motivo del error de MercadoPago (si lo hubo)
$motivo_error = $_SESSION['mp_error'] ?? '';
unset($_SESSION['mp_error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= gym_nombre() ?> - Comprar créditos</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" type="image/png" href="icono_pulso.png">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title"><?= htmlspecialchars($pack['nombre']) ?></div>

  <div class="credits-card" style="margin-bottom:24px">
    <div class="credits-label">PACK SELECCIONADO</div>
    <div class="credits-number"><?= (int)$pack['creditos'] ?></div>
    <div class="credits-sub">créditos · <?= $precio_txt ?> · vencen a los <?= $dias ?> días de la compra</div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger">
      No se pudo iniciar el pago con MercadoPago. Probá de nuevo en un rato.
      <?php if ($motivo_error): ?><br><small style="opacity:.8">Detalle: <?= htmlspecialchars($motivo_error) ?></small><?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="section-title">Pagá con MercadoPago</div>

  <div class="card-tp" style="margin-bottom:16px;border-color:#009ee3">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
      <span style="font-size:28px">💳</span>
      <div>
        <div style="font-size:15px;font-weight:800;color:#fff">MercadoPago <span style="font-size:10px;background:#009ee3;color:#fff;border-radius:6px;padding:2px 6px;margin-left:4px;vertical-align:middle">AUTOMÁTICO</span></div>
        <div style="font-size:12px;color:var(--text2)">Los créditos se cargan solos apenas se aprueba el pago</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;margin-bottom:16px;font-size:12px">
      <div style="background:var(--bg3);border-radius:10px;padding:10px 12px"><strong style="color:#fff">💳 Tarjeta</strong><br><span style="color:var(--text2)">Crédito o débito · al instante</span></div>
      <div style="background:var(--bg3);border-radius:10px;padding:10px 12px"><strong style="color:#fff">📱 Dinero en cuenta</strong><br><span style="color:var(--text2)">De tu MercadoPago · al instante</span></div>
      <div style="background:var(--bg3);border-radius:10px;padding:10px 12px"><strong style="color:#fff">💵 Efectivo</strong><br><span style="color:var(--text2)">Pago Fácil o Rapipago · se acredita cuando pagás</span></div>
    </div>

    <form method="post" action="pago_mp.php">
      <input type="hidden" name="pack" value="<?= (int)$pack['id'] ?>">
      <button type="submit" class="btn btn-blue" style="background:#009ee3">Pagar <?= $precio_txt ?> con MercadoPago</button>
    </form>
    <p style="font-size:11px;color:var(--text2);margin:10px 0 0;text-align:center">
      Para pagar en Pago Fácil o Rapipago, elegí <strong>"Efectivo"</strong> dentro de MercadoPago: te da un código, pagás en el local y los créditos se cargan solos (puede tardar unas horas).
    </p>
  </div>

  <div class="card-tp" style="margin-bottom:20px;display:flex;gap:12px;align-items:flex-start">
    <span style="font-size:22px">🏋️</span>
    <div style="font-size:13px;color:var(--text2);line-height:1.5">
      <strong style="color:#fff">¿Querés pagar en efectivo en el gimnasio?</strong><br>
      Consultá con el administrador: cuando le pagues, te carga los créditos desde su panel.
    </div>
  </div>
  <?= tarjeta_contacto('📍 Contacto del gimnasio') ?>

  <a href="acceso_alumno.php" style="display:block;text-align:center;color:var(--text2);font-size:13px;text-decoration:none">Cancelar</a>
</div>

<?php include '_layout_nav_close.php'; ?>
</body>
</html>

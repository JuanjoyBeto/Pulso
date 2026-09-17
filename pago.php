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

$alias = "damiracing.mp";
$nombre_alumno = nombre_usuario_actual();

// Manejar aviso de transferencia bancaria
if (isset($_POST['aviso_transferencia'])) {
    require_once 'mailer.php';
    $res_admin = mysqli_query($conex, "SELECT id, nombre, apellido, email FROM registronuevo WHERE rol='administrador' LIMIT 1");
    $admin = mysqli_fetch_assoc($res_admin);
    $admin_id = $admin ? (int)$admin['id'] : null;

    $msg_notif = mysqli_real_escape_string($conex, "💰 $nombre_alumno quiere comprar $pack créditos por TRANSFERENCIA. Verificá en tu banco (alias: $alias) y cargale los créditos.");
    if ($admin_id) {
        mysqli_query($conex, "INSERT INTO notificaciones (alumno_id, mensaje, fecha) VALUES ($admin_id, '$msg_notif', NOW())");
    }
    if ($admin && !empty($admin['email'])) {
        enviarMail($admin['email'], $admin['nombre'].' '.$admin['apellido'],
            '💰 Nueva transferencia - Training Point',
            "<strong>$nombre_alumno</strong> realizó una transferencia por <strong>$pack créditos</strong>.<br><br>
            Verificá el pago en tu banco (alias: <strong>$alias</strong>) y cargale los créditos desde el panel admin.");
    }
    header('Location: pago_confirmado.php?pack='.$pack.'&medio=transferencia');
    exit();
}

// Manejar aviso de efectivo
if (isset($_POST['aviso_efectivo'])) {
    require_once 'mailer.php';
    $res_admin = mysqli_query($conex, "SELECT id, nombre, apellido, email FROM registronuevo WHERE rol='administrador' LIMIT 1");
    $admin = mysqli_fetch_assoc($res_admin);
    $admin_id = $admin ? (int)$admin['id'] : null;

    $msg_notif = mysqli_real_escape_string($conex, "💵 $nombre_alumno quiere pagar $pack créditos en EFECTIVO. Acordá con él/ella cuándo pasa a abonar.");
    if ($admin_id) {
        mysqli_query($conex, "INSERT INTO notificaciones (alumno_id, mensaje, fecha) VALUES ($admin_id, '$msg_notif', NOW())");
    }
    if ($admin && !empty($admin['email'])) {
        enviarMail($admin['email'], $admin['nombre'].' '.$admin['apellido'],
            '💵 Pago en efectivo - Training Point',
            "<strong>$nombre_alumno</strong> quiere pagar <strong>$pack créditos en efectivo</strong>.<br><br>
            Acordá con él/ella cuándo pasa a abonar y luego cargale los créditos desde el panel admin.");
    }
    header('Location: pago_confirmado.php?pack='.$pack.'&medio=efectivo');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Point - Comprar créditos</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title">Comprar <?= $pack ?> créditos</div>

  <div class="credits-card" style="margin-bottom:24px">
    <div class="credits-label">PACK SELECCIONADO</div>
    <div class="credits-number"><?= $pack ?></div>
    <div class="credits-sub">créditos · vencen a fin de mes</div>
  </div>

  <div class="section-title">Elegí cómo pagar</div>

  <!-- OPCIÓN 1: TRANSFERENCIA -->
  <div class="card-tp" style="margin-bottom:16px">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
      <span style="font-size:28px">🏦</span>
      <div>
        <div style="font-size:15px;font-weight:800;color:#fff">Transferencia bancaria</div>
        <div style="font-size:12px;color:var(--text2)">Sin comisiones · Acreditación manual</div>
      </div>
    </div>

    <div style="background:var(--bg3);border:1px solid var(--blue);border-radius:12px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <div>
        <div style="font-size:11px;color:var(--text2);margin-bottom:2px">ALIAS</div>
        <div style="font-size:18px;font-weight:900;color:var(--blue);letter-spacing:1px"><?= $alias ?></div>
      </div>
      <button onclick="copiarAlias()" id="btn-copiar" style="background:var(--blue);border:none;color:#fff;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit">Copiar</button>
    </div>

    <p style="font-size:12px;color:var(--text2);margin-bottom:14px">
      Hacé la transferencia y luego tocá el botón para avisarle a Dami.
    </p>

    <form method="post">
      <input type="hidden" name="aviso_transferencia" value="1">
      <button type="submit" class="btn btn-blue">✅ Ya transferí, avisarle a Dami</button>
    </form>
  </div>

  <!-- OPCIÓN 2: EFECTIVO -->
  <div class="card-tp" style="margin-bottom:20px">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
      <span style="font-size:28px">💵</span>
      <div>
        <div style="font-size:15px;font-weight:800;color:#fff">Efectivo en el gym</div>
        <div style="font-size:12px;color:var(--text2)">Abonás en mano · Dami te carga los créditos</div>
      </div>
    </div>

    <p style="font-size:12px;color:var(--text2);margin-bottom:14px">
      Tocá el botón y le avisamos a Dami que querés pagar en efectivo para coordinar.
    </p>

    <form method="post">
      <input type="hidden" name="aviso_efectivo" value="1">
      <button type="submit" class="btn btn-outline" style="border-color:var(--green);color:var(--green)">
        💵 Quiero pagar en efectivo
      </button>
    </form>
  </div>

  <a href="acceso_alumno.php" style="display:block;text-align:center;color:var(--text2);font-size:13px;text-decoration:none">Cancelar</a>
</div>

<script>
function copiarAlias() {
  navigator.clipboard.writeText('<?= $alias ?>').then(() => {
    const btn = document.getElementById('btn-copiar');
    btn.textContent = '¡Copiado!';
    btn.style.background = '#4ade80';
    setTimeout(() => { btn.textContent = 'Copiar'; btn.style.background = 'var(--blue)'; }, 2000);
  });
}
</script>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

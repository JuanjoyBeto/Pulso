<?php
require_once 'auth.php';
requerir_rol(['administrador']);
require_once 'mp.php';
$pagina_activa = 'pagos';

// Aprobar / rechazar pagos en efectivo o transferencia
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int)($_POST['pago_id'] ?? 0);
    if (isset($_POST['aprobar'])) {
        acreditar_pago($pid);
    } elseif (isset($_POST['rechazar'])) {
        mysqli_query($conex, "UPDATE pagos SET estado='rechazado' WHERE id=$pid AND estado='pendiente'");
        $r = mysqli_query($conex, "SELECT alumno_id FROM pagos WHERE id=$pid");
        if ($p = mysqli_fetch_assoc($r)) {
            $aid = (int)$p['alumno_id'];
            notificar($aid, '❌ El gimnasio no pudo confirmar tu pago. Consultá en recepción.');
        }
    }
    header('Location: admin_pagos.php');
    exit();
}

$sql = "SELECT p.*, r.nombre, r.apellido FROM pagos p JOIN registronuevo r ON r.id = p.alumno_id";
$pendientes = mysqli_query($conex, "$sql WHERE p.estado='pendiente' AND p.metodo<>'mercadopago' ORDER BY p.fecha_creado DESC");
$historial  = mysqli_query($conex, "$sql WHERE p.estado<>'pendiente' ORDER BY p.fecha_creado DESC LIMIT 30");

// Resumen del mes
$r = mysqli_query($conex, "SELECT COUNT(*) c, COALESCE(SUM(monto),0) t FROM pagos WHERE estado='aprobado'
                           AND YEAR(fecha_aprobado)=YEAR(CURDATE()) AND MONTH(fecha_aprobado)=MONTH(CURDATE())");
$mes = mysqli_fetch_assoc($r);

function plata($n) { return '$' . number_format($n, 0, ',', '.'); }
$iconos = ['mercadopago' => '💳', 'transferencia' => '🏦', 'efectivo' => '💵'];
$colores = ['aprobado' => 'var(--green)', 'rechazado' => 'var(--red)', 'pendiente' => 'var(--text2)'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= gym_nombre() ?> - Pagos</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" type="image/png" href="icono_pulso.png">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title">💳 Pagos</div>

  <div class="credits-card" style="margin-bottom:22px">
    <div class="credits-label">COBRADO ESTE MES</div>
    <div class="credits-number"><?= plata($mes['t']) ?></div>
    <div class="credits-sub"><?= (int)$mes['c'] ?> pagos aprobados</div>
  </div>

  <div class="section-title">Pendientes de confirmar</div>
  <?php if (mysqli_num_rows($pendientes) === 0): ?>
    <p style="color:var(--text2);font-size:13px;margin-bottom:22px">No hay pagos pendientes. Los de MercadoPago se aprueban solos. 🙌</p>
  <?php endif; ?>
  <?php while ($p = mysqli_fetch_assoc($pendientes)): ?>
    <div class="card-tp" style="margin-bottom:12px">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:12px">
        <div>
          <div style="font-weight:800;color:#fff"><?= $iconos[$p['metodo']] ?> <?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></div>
          <div style="font-size:12px;color:var(--text2)"><?= ucfirst($p['metodo']) ?> · <?= (int)$p['creditos'] ?> créditos · <?= date('d/m H:i', strtotime($p['fecha_creado'])) ?></div>
        </div>
        <div style="font-size:18px;font-weight:900;color:var(--blue)"><?= plata($p['monto']) ?></div>
      </div>
      <form method="post" style="display:flex;gap:8px">
        <input type="hidden" name="pago_id" value="<?= (int)$p['id'] ?>">
        <button name="aprobar" class="btn btn-blue" style="flex:2">✅ Aprobar y cargar créditos</button>
        <button name="rechazar" class="btn btn-outline" style="flex:1;border-color:var(--red);color:var(--red)">Rechazar</button>
      </form>
    </div>
  <?php endwhile; ?>

  <div class="section-title" style="margin-top:10px">Últimos pagos</div>
  <div class="card-tp">
    <?php if (mysqli_num_rows($historial) === 0): ?>
      <p style="color:var(--text2);font-size:13px;margin:0">Todavía no hay pagos.</p>
    <?php endif; ?>
    <?php while ($p = mysqli_fetch_assoc($historial)): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);font-size:13px">
        <div>
          <div style="color:#fff;font-weight:600"><?= $iconos[$p['metodo']] ?> <?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></div>
          <div style="color:var(--text2);font-size:11px"><?= date('d/m/Y', strtotime($p['fecha_creado'])) ?> · <?= (int)$p['creditos'] ?> créditos</div>
        </div>
        <div style="text-align:right">
          <div style="font-weight:800;color:#fff"><?= plata($p['monto']) ?></div>
          <div style="font-size:11px;font-weight:700;color:<?= $colores[$p['estado']] ?>"><?= strtoupper($p['estado']) ?></div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

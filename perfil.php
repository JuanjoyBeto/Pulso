<?php
require_once 'auth.php';
requerir_login();
require_once 'dbconn.php';
$pagina_activa = 'perfil';
$uid = $_SESSION['usuario_id'];

$res = mysqli_query($conex, "SELECT * FROM registronuevo WHERE id=$uid LIMIT 1");
$u = mysqli_fetch_assoc($res);
$res_ab = mysqli_query($conex, "SELECT * FROM abonos WHERE alumno_id=$uid ORDER BY fecha_inicio DESC LIMIT 10");

$inicial = strtoupper(substr($u['nombre'], 0, 1));
$meses_es = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$tiene_apto = !empty($u['apto_medico_archivo']);

// Formateo correcto de la fecha de vencimiento
function fechaLarga($fecha, $meses_es) {
    if (empty($fecha) || $fecha === '0000-00-00') return '-';
    $ts = strtotime($fecha);
    if (!$ts) return '-';
    return date('j', $ts) . ' de ' . $meses_es[(int)date('n', $ts)] . ' de ' . date('Y', $ts);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Point - Mi Perfil</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include '_layout_nav.php'; ?>

<div class="profile-header">
  <div class="avatar-lg"><?= htmlspecialchars($inicial) ?></div>
  <div class="profile-name" style="text-transform:capitalize"><?= htmlspecialchars(strtolower($u['nombre'].' '.$u['apellido'])) ?></div>
  <div class="profile-gender"><?= htmlspecialchars($u['genero'] ?? '') ?></div>
</div>

<div class="page-body">
  <div class="card-tp">
    <div class="info-row"><span class="info-label">Correo</span><span class="info-val" style="font-size:12px"><?= htmlspecialchars($u['email'] ?? '') ?></span></div>
    <div class="info-row"><span class="info-label">Teléfono</span><span class="info-val"><?= htmlspecialchars($u['telefono'] ?? '-') ?></span></div>
    <div class="info-row"><span class="info-label">Créditos</span><span class="info-val" style="color:var(--blue);font-size:18px;font-weight:900"><?= (int)($u['creditos'] ?? 0) ?></span></div>
    <div class="info-row"><span class="info-label">Vencimiento</span><span class="info-val"><?= fechaLarga($u['vencimiento_creditos'] ?? null, $meses_es) ?></span></div>
  </div>

  <a href="apto_medico.php" class="card-tp" style="display:flex;align-items:center;justify-content:space-between;text-decoration:none;margin-bottom:14px">
    <div style="display:flex;align-items:center;gap:12px">
      <span style="font-size:24px"><?= $tiene_apto ? '✅' : '📋' ?></span>
      <div>
        <div style="font-size:14px;font-weight:700;color:#fff">Apto Médico</div>
        <div style="font-size:12px;color:<?= $tiene_apto ? 'var(--green)' : 'var(--red)' ?>"><?= $tiene_apto ? 'Cargado ✓' : 'Sin cargar — tocá para subir' ?></div>
      </div>
    </div>
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text2)" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
  </a>

  <div class="section-title">Registro de abonos</div>
  <?php if (mysqli_num_rows($res_ab) == 0): ?>
    <div class="notif-card" style="text-align:center;padding:20px"><div style="font-size:13px;color:var(--text2)">Sin abonos registrados.</div></div>
  <?php else: while ($ab = mysqli_fetch_assoc($res_ab)): ?>
  <div class="abono-item" onclick="this.querySelector('.abono-expanded').classList.toggle('show')">
    <div class="abono-date"><?= fechaLarga($ab['fecha_inicio'], $meses_es) ?></div>
    <div class="abono-detail"><?= (int)$ab['creditos_total'] ?> créditos · Ver detalle ▾</div>
    <div class="abono-expanded">
      <div class="abono-row"><span>Total créditos:</span><span><?= (int)$ab['creditos_total'] ?></span></div>
      <div class="abono-row"><span>Usados:</span><span><?= (int)$ab['creditos_usados'] ?></span></div>
      <div class="abono-row"><span>Vencimiento:</span><span><?= fechaLarga($ab['fecha_fin'], $meses_es) ?></span></div>
    </div>
  </div>
  <?php endwhile; endif; ?>

  <div style="margin-top:24px">
    <a href="cambiar_contra.php" class="btn btn-outline" style="display:block;text-align:center;text-decoration:none;margin-bottom:10px">Cambiar contraseña</a>
    <a href="cerrar_sesion.php" class="btn btn-danger" style="display:block;text-align:center;text-decoration:none">Cerrar sesión</a>
  </div>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

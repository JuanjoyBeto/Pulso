<?php
require_once 'auth.php';
requerir_login();
require_once 'dbconn.php';
$pagina_activa = 'home';
$uid = $_SESSION['usuario_id'];

$res_cred = mysqli_query($conex, "SELECT creditos, vencimiento_creditos FROM registronuevo WHERE id=$uid LIMIT 1");
$alumno = mysqli_fetch_assoc($res_cred);
$creditos = (int)($alumno['creditos'] ?? 0);
$vencimiento = $alumno['vencimiento_creditos'] ?? null;

$meses_es = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$dias_es = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
$dias_corto = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

$sql_prox = "SELECT c.nombre, c.fecha, c.hora_inicio, c.hora_fin
             FROM inscripciones i
             JOIN clases c ON i.clase_id = c.id
             WHERE i.alumno_id = $uid AND c.fecha >= CURDATE()
             ORDER BY c.fecha ASC, c.hora_inicio ASC LIMIT 1";
$res_prox = mysqli_query($conex, $sql_prox);
$prox = mysqli_fetch_assoc($res_prox);

$sql_proximas = "SELECT c.nombre, c.fecha, c.hora_inicio, c.hora_fin
                 FROM inscripciones i
                 JOIN clases c ON i.clase_id = c.id
                 WHERE i.alumno_id = $uid AND c.fecha >= CURDATE()
                 ORDER BY c.fecha ASC LIMIT 5";
$res_proximas = mysqli_query($conex, $sql_proximas);
$proximas = [];
while ($p = mysqli_fetch_assoc($res_proximas)) {
    $proximas[] = $p;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Point - Inicio</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <p style="font-size:13px;color:var(--text2);margin-bottom:16px;">
    Hola, <strong style="color:#fff;text-transform:capitalize"><?= htmlspecialchars(strtolower(explode(' ', nombre_usuario_actual())[0])) ?> 👋</strong>
  </p>

  <!-- CRÉDITOS -->
  <div class="credits-card">
    <div class="credits-label">TUS CRÉDITOS</div>
    <div class="credits-number"><?= $creditos ?></div>
    <div class="credits-sub">
      <?php if ($vencimiento): ?>
        Vencen el <?= date('d', strtotime($vencimiento)) ?> de <?= $meses_es[(int)date('m', strtotime($vencimiento))] ?>
      <?php else: ?>
        Sin abono activo
      <?php endif; ?>
    </div>
    <a href="#" class="credits-add" onclick="document.getElementById('modal-pack').classList.add('show');return false;">+</a>
  </div>

  <!-- PRÓXIMA CLASE -->
  <?php if ($prox): $ts = strtotime($prox['fecha']); ?>
  <div class="next-class">
    <div>
      <div class="next-label">Próxima clase</div>
      <div class="next-name"><?= htmlspecialchars($prox['nombre']) ?></div>
      <div class="next-date">
        <?= ucfirst($dias_es[date('w',$ts)]) ?> <?= date('d',$ts) ?>/<?= date('m',$ts) ?>
        · <?= substr($prox['hora_inicio'],0,5) ?> - <?= substr($prox['hora_fin'],0,5) ?>
      </div>
    </div>
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b9eff" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
  </div>
  <?php else: ?>
  <div class="next-class">
    <div>
      <div class="next-label">Próxima clase</div>
      <div class="next-name" style="color:var(--text2);font-size:14px;">Sin clases reservadas</div>
    </div>
    <a href="clases.php" style="color:var(--blue);font-size:12px;font-weight:700;text-decoration:none">Ver clases →</a>
  </div>
  <?php endif; ?>

  <!-- LISTA PRÓXIMAS -->
  <div class="section-title">Mis próximas clases</div>
  <?php if (!empty($proximas)): ?>
  <?php foreach ($proximas as $c):
    $ts = strtotime($c['fecha']);
  ?>
  <div class="class-card">
    <div class="date-box">
      <div class="date-num"><?= date('d', $ts) ?></div>
      <div class="date-day"><?= $dias_corto[date('w',$ts)] ?></div>
    </div>
    <div class="class-info">
      <div class="class-name"><?= htmlspecialchars($c['nombre']) ?></div>
      <div class="class-time"><?= substr($c['hora_inicio'],0,5) ?> - <?= substr($c['hora_fin'],0,5) ?></div>
    </div>
    <span class="btn-class active">Inscripto ✓</span>
  </div>
  <?php endforeach; ?>
  <?php else: ?>
  <div class="notif-card" style="text-align:center;padding:20px">
    <div style="font-size:13px;color:var(--text2)">No tenés clases agendadas.</div>
    <a href="clases.php" style="color:var(--blue);font-size:13px;font-weight:700;text-decoration:none;display:block;margin-top:8px">Ver clases disponibles →</a>
  </div>
  <?php endif; ?>

  <!-- WATERMARK CON LOGO REAL -->
  <div style="text-align:center;padding:40px 0 10px;opacity:0.15">
    <img src="logo_tp.png" alt="Training Point" style="height:50px;width:auto;object-fit:contain">
  </div>
</div>

<!-- MODAL PACK -->
<div class="modal-overlay" id="modal-pack" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="modal-box">
    <button class="modal-close" onclick="document.getElementById('modal-pack').classList.remove('show')">✕</button>
    <div class="modal-handle"></div>
    <div class="modal-title">Renovar abono</div>
    <div class="pack-option">
      <div><div class="pack-name">4 créditos</div><div class="pack-sub">Transferencia bancaria</div></div>
      <a href="pago.php?pack=4" class="btn-pack">Comprar</a>
    </div>
    <div class="pack-option">
      <div><div class="pack-name">8 créditos</div><div class="pack-sub">Transferencia bancaria</div></div>
      <a href="pago.php?pack=8" class="btn-pack">Comprar</a>
    </div>
    <div class="pack-option">
      <div><div class="pack-name">12 créditos</div><div class="pack-sub">Transferencia bancaria</div></div>
      <a href="pago.php?pack=12" class="btn-pack">Comprar</a>
    </div>
  </div>
</div>

<?php include '_layout_nav_close.php'; ?>
</body>
</html>

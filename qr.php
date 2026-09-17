<?php
require_once 'auth.php';
requerir_login();
require_once 'dbconn.php';
$pagina_activa = 'qr';
$uid = $_SESSION['usuario_id'];

$res = mysqli_query($conex, "SELECT creditos, nombre, apellido FROM registronuevo WHERE id=$uid LIMIT 1");
$alumno = mysqli_fetch_assoc($res);
$creditos = (int)($alumno['creditos'] ?? 0);

// Traer clases proximas en las que esta anotado
$sql_clases = "SELECT c.id, c.nombre, c.fecha, c.hora_inicio, c.hora_fin
               FROM inscripciones i
               JOIN clases c ON i.clase_id = c.id
               WHERE i.alumno_id = $uid AND c.fecha >= CURDATE()
               ORDER BY c.fecha ASC, c.hora_inicio ASC
               LIMIT 5";
$res_clases = mysqli_query($conex, $sql_clases);
$clases = [];
while ($c = mysqli_fetch_assoc($res_clases)) {
    $clases[] = $c;
}

// Clase seleccionada (por defecto la primera)
$clase_sel_id = (int)($_GET['clase_id'] ?? ($clases[0]['id'] ?? 0));

$dias_es = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
$meses_es = ['','ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Point - Mi QR</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">

<?php if (empty($clases)): ?>
  <!-- SIN CLASES -->
  <div style="text-align:center;padding:60px 20px">
    <div style="font-size:48px;margin-bottom:16px">📅</div>
    <div class="page-title" style="text-align:center">Sin clases agendadas</div>
    <p style="color:var(--text2);font-size:14px;margin:10px 0 24px">
      Anotate a una clase para que se genere tu QR de asistencia.
    </p>
    <a href="clases.php" class="btn btn-blue" style="display:block;text-align:center;text-decoration:none;max-width:280px;margin:0 auto">
      Ver clases disponibles →
    </a>
  </div>

<?php else: ?>

  <?php if (count($clases) > 1): ?>
  <!-- SELECTOR DE CLASE -->
  <div style="margin-bottom:20px">
    <div class="section-title">Seleccioná tu clase</div>
    <div style="display:flex;flex-direction:column;gap:8px">
      <?php foreach ($clases as $c):
        $ts = strtotime($c['fecha']);
        $activa = $c['id'] == $clase_sel_id;
      ?>
      <a href="qr.php?clase_id=<?= $c['id'] ?>"
         style="display:flex;align-items:center;gap:14px;padding:14px 16px;
                background:<?= $activa ? 'rgba(59,158,255,0.1)' : 'var(--card)' ?>;
                border:1px solid <?= $activa ? 'var(--blue)' : 'var(--border)' ?>;
                border-radius:14px;text-decoration:none;color:#fff;transition:all .2s">
        <div style="background:<?= $activa ? 'var(--blue)' : 'var(--bg3)' ?>;border-radius:10px;padding:8px 12px;text-align:center;min-width:48px">
          <div style="font-size:18px;font-weight:900;line-height:1"><?= date('d',$ts) ?></div>
          <div style="font-size:9px;font-weight:700;text-transform:uppercase"><?= $dias_es[date('w',$ts)] ?></div>
        </div>
        <div>
          <div style="font-size:14px;font-weight:800"><?= htmlspecialchars($c['nombre']) ?></div>
          <div style="font-size:12px;color:<?= $activa ? 'var(--blue)' : 'var(--text2)' ?>">
            <?= substr($c['hora_inicio'],0,5) ?> - <?= substr($c['hora_fin'],0,5) ?>
          </div>
        </div>
        <?php if ($activa): ?>
        <div style="margin-left:auto;color:var(--blue);font-size:18px">✓</div>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php
  // Buscar la clase seleccionada
  $clase_actual = null;
  foreach ($clases as $c) {
    if ($c['id'] == $clase_sel_id) { $clase_actual = $c; break; }
  }
  if (!$clase_actual) $clase_actual = $clases[0];
  $ts = strtotime($clase_actual['fecha']);

  // Generar QR con alumno_id + clase_id + token
  $qr_data = $uid . ':' . $clase_actual['id'] . ':' . md5($uid . ':' . $clase_actual['id'] . 'trainingpoint2025');
  $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode($qr_data);
  ?>

  <!-- INFO CLASE ACTUAL -->
  <div style="text-align:center;margin-bottom:16px">
    <div style="font-size:13px;color:var(--text2);margin-bottom:4px">QR para la clase</div>
    <div style="font-size:18px;font-weight:800"><?= htmlspecialchars($clase_actual['nombre']) ?></div>
    <div style="font-size:13px;color:var(--blue)">
      <?= $dias_es[date('w',$ts)] ?> <?= date('d',$ts) ?>/<?= date('m',$ts) ?>
      · <?= substr($clase_actual['hora_inicio'],0,5) ?> - <?= substr($clase_actual['hora_fin'],0,5) ?>
    </div>
  </div>

  <!-- QR -->
  <div style="display:flex;justify-content:center;margin-bottom:20px">
    <div style="background:#fff;padding:18px;border-radius:18px">
      <img src="<?= $qr_url ?>" width="220" height="220" alt="QR <?= htmlspecialchars($alumno['nombre']) ?>">
    </div>
  </div>

  <!-- CRÉDITOS -->
  <div class="card-tp" style="text-align:center">
    <div style="font-size:13px;font-weight:700;color:var(--text2);margin-bottom:6px;text-transform:capitalize">
      <?= htmlspecialchars(strtolower($alumno['nombre'] . ' ' . $alumno['apellido'])) ?>
    </div>
    <div style="font-size:40px;font-weight:900;color:<?= $creditos>0?'var(--blue)':'var(--red)' ?>">
      <?= $creditos ?>
    </div>
    <div style="font-size:12px;color:var(--text2)">créditos disponibles</div>
    <?php if ($creditos == 0): ?>
      <a href="acceso_alumno.php" style="display:block;color:var(--blue);font-size:13px;font-weight:700;text-decoration:none;margin-top:10px">
        Comprá un pack →
      </a>
    <?php endif; ?>
  </div>

<?php endif; ?>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

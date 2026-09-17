<?php
require_once 'auth.php';
requerir_rol(['administrador']);
require_once 'dbconn.php';
$pagina_activa = 'admin';

$alumno_id = (int)($_GET['id'] ?? 0);
if (!$alumno_id) { header('Location: acceso_administrador.php'); exit(); }

$res = mysqli_query($conex, "SELECT * FROM registronuevo WHERE id=$alumno_id AND rol='alumno' LIMIT 1");
$al = mysqli_fetch_assoc($res);
if (!$al) { header('Location: acceso_administrador.php'); exit(); }

// Manejar agregar/quitar créditos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'agregar' && !empty($_POST['creditos_agregar'])) {
        $cant = (int)$_POST['creditos_agregar'];
        $venc = date('Y-m-t');
        mysqli_query($conex, "UPDATE registronuevo SET creditos = COALESCE(creditos,0) + $cant, vencimiento_creditos='$venc' WHERE id=$alumno_id");
        mysqli_query($conex, "INSERT INTO abonos (alumno_id, creditos_total, creditos_usados, fecha_inicio, fecha_fin) VALUES ($alumno_id, $cant, 0, CURDATE(), '$venc')");
    } elseif ($accion === 'quitar' && !empty($_POST['creditos_quitar'])) {
        $cant = (int)$_POST['creditos_quitar'];
        mysqli_query($conex, "UPDATE registronuevo SET creditos = GREATEST(0, COALESCE(creditos,0) - $cant) WHERE id=$alumno_id");
    }
    header("Location: admin_alumno.php?id=$alumno_id");
    exit();
}

// Recargar datos actualizados
$res = mysqli_query($conex, "SELECT * FROM registronuevo WHERE id=$alumno_id LIMIT 1");
$al = mysqli_fetch_assoc($res);

// Historial de clases (todas las inscripciones)
$res_clases = mysqli_query($conex, "SELECT c.*, 
    (SELECT presente FROM asistencias WHERE alumno_id=$alumno_id AND clase_id=c.id LIMIT 1) as presente
    FROM inscripciones i
    JOIN clases c ON i.clase_id = c.id
    WHERE i.alumno_id = $alumno_id
    ORDER BY c.fecha DESC, c.hora_inicio DESC");

// Historial de abonos
$res_abonos = mysqli_query($conex, "SELECT * FROM abonos WHERE alumno_id=$alumno_id ORDER BY fecha_inicio DESC");

$dias_es = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
$meses_es = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$ini = strtoupper(substr($al['nombre'],0,1));
$creditos = (int)($al['creditos'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Point - <?= htmlspecialchars($al['nombre'].' '.$al['apellido']) ?></title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include '_layout_nav.php'; ?>

<!-- HEADER ALUMNO -->
<div class="profile-header">
  <div class="avatar-lg"><?= $ini ?></div>
  <div class="profile-name" style="text-transform:capitalize">
    <?= htmlspecialchars(strtolower($al['nombre'].' '.$al['apellido'])) ?>
  </div>
  <div class="profile-gender"><?= htmlspecialchars($al['genero'] ?? '') ?></div>
</div>

<div class="page-body">

  <!-- DATOS -->
  <div class="card-tp" style="margin-bottom:16px">
    <div class="info-row"><span class="info-label">Correo</span><span class="info-val" style="font-size:12px"><?= htmlspecialchars($al['email'] ?? '-') ?></span></div>
    <div class="info-row"><span class="info-label">Teléfono</span><span class="info-val"><?= htmlspecialchars($al['telefono'] ?? '-') ?></span></div>
    <div class="info-row">
      <span class="info-label">Créditos</span>
      <span class="info-val" style="color:<?= $creditos>0?'var(--blue)':'var(--red)' ?>;font-size:20px;font-weight:900"><?= $creditos ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Vencimiento</span>
      <span class="info-val">
        <?php
          $fv = $al['vencimiento_creditos'] ?? null;
          if (!empty($fv) && $fv !== '0000-00-00' && ($tsv = strtotime($fv))) {
              echo date('j', $tsv) . ' de ' . $meses_es[(int)date('n', $tsv)] . ' de ' . date('Y', $tsv);
          } else { echo '-'; }
        ?>
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Apto médico</span>
      <span class="info-val">
        <?php if (!empty($al['apto_medico_archivo'])): ?>
          <a href="uploads/aptos/<?= htmlspecialchars($al['apto_medico_archivo']) ?>" target="_blank"
             style="color:var(--green);font-weight:700;text-decoration:none">
            ✓ Ver apto →
          </a>
        <?php else: ?>
          <span style="color:var(--red);font-weight:700">✗ Sin cargar</span>
        <?php endif; ?>
      </span>
    </div>
  </div>

    <!-- CARGAR CRÉDITOS -->
  <a href="admin_cargar_creditos.php?id=<?= $alumno_id ?>" class="btn btn-blue" style="display:block;text-align:center;text-decoration:none;margin-bottom:20px;padding:16px;font-size:15px">
    💳 Cargar / gestionar créditos
  </a>

<!-- HISTORIAL DE CLASES -->
  <div class="section-title">Historial de clases</div>
  <?php
  $total_clases = 0; $presentes = 0; $ausentes = 0; $proximas = 0;
  $clases_arr = [];
  while ($c = mysqli_fetch_assoc($res_clases)) { $clases_arr[] = $c; }

  foreach ($clases_arr as $c) {
    $total_clases++;
    $hoy = date('Y-m-d');
    if ($c['fecha'] > $hoy) $proximas++;
    elseif ($c['presente'] == 1) $presentes++;
    else $ausentes++;
  }
  ?>

  <!-- STATS RÁPIDOS -->
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:16px">
    <div class="stat-box" style="text-align:center;padding:12px 8px">
      <div style="font-size:24px;font-weight:900;color:var(--blue)"><?= $presentes ?></div>
      <div style="font-size:10px;color:var(--text2)">Presente</div>
    </div>
    <div class="stat-box" style="text-align:center;padding:12px 8px">
      <div style="font-size:24px;font-weight:900;color:var(--red)"><?= $ausentes ?></div>
      <div style="font-size:10px;color:var(--text2)">Ausente</div>
    </div>
    <div class="stat-box" style="text-align:center;padding:12px 8px">
      <div style="font-size:24px;font-weight:900;color:var(--green)"><?= $proximas ?></div>
      <div style="font-size:10px;color:var(--text2)">Próximas</div>
    </div>
  </div>

  <?php if (empty($clases_arr)): ?>
    <div class="notif-card" style="text-align:center;padding:20px">
      <div style="font-size:13px;color:var(--text2)">Sin clases registradas.</div>
    </div>
  <?php else: foreach ($clases_arr as $c):
    $ts = strtotime($c['fecha']);
    $hoy = date('Y-m-d');
    $es_futura = $c['fecha'] > $hoy;
    $es_hoy = $c['fecha'] == $hoy;
    if ($es_futura) { $color = 'var(--blue)'; $badge = 'Próxima'; }
    elseif ($c['presente'] == 1) { $color = 'var(--green)'; $badge = '✓ Presente'; }
    else { $color = 'var(--red)'; $badge = '✗ Ausente'; }
  ?>
  <div class="class-card" style="margin-bottom:8px">
    <div class="date-box" style="background:<?= $es_futura?'var(--blue)':($c['presente']==1?'#1a3a2a':'#3a1a1a') ?>">
      <div class="date-num"><?= date('d',$ts) ?></div>
      <div class="date-day"><?= $dias_es[date('w',$ts)] ?></div>
    </div>
    <div class="class-info">
      <div class="class-name"><?= htmlspecialchars($c['nombre']) ?></div>
      <div class="class-time"><?= substr($c['hora_inicio'],0,5) ?> - <?= substr($c['hora_fin'],0,5) ?></div>
      <div style="font-size:11px;color:var(--text3)"><?= date('d',$ts).' de '.$meses_es[(int)date('m',$ts)] ?></div>
    </div>
    <span style="font-size:12px;font-weight:700;color:<?= $color ?>;white-space:nowrap"><?= $badge ?></span>
  </div>
  <?php endforeach; endif; ?>

  <!-- HISTORIAL ABONOS -->
  <div class="section-title" style="margin-top:8px">Historial de abonos</div>
  <?php if (mysqli_num_rows($res_abonos) == 0): ?>
    <div class="notif-card" style="text-align:center;padding:20px">
      <div style="font-size:13px;color:var(--text2)">Sin abonos registrados.</div>
    </div>
  <?php else: while ($ab = mysqli_fetch_assoc($res_abonos)):
    $ts = strtotime($ab['fecha_inicio']);
  ?>
  <div class="abono-item" onclick="this.querySelector('.abono-expanded').classList.toggle('show')">
    <div class="abono-date"><?= date('d',$ts).' de '.$meses_es[(int)date('m',$ts)].' '.date('Y',$ts) ?></div>
    <div class="abono-detail"><?= (int)$ab['creditos_total'] ?> créditos · Ver detalle ▾</div>
    <div class="abono-expanded">
      <div class="abono-row"><span>Total:</span><span><?= (int)$ab['creditos_total'] ?></span></div>
      <div class="abono-row"><span>Usados:</span><span><?= (int)$ab['creditos_usados'] ?></span></div>
      <div class="abono-row"><span>Vencimiento:</span><span><?= date('d/m/Y',strtotime($ab['fecha_fin'])) ?></span></div>
    </div>
  </div>
  <?php endwhile; endif; ?>

  <a href="acceso_administrador.php" class="btn btn-outline" style="display:block;text-align:center;text-decoration:none;margin-top:20px">
    ← Volver al panel
  </a>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

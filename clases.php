<?php
require_once 'auth.php';
requerir_login();
require_once 'dbconn.php';
$pagina_activa = 'clases';
$uid = $_SESSION['usuario_id'];

$res_cred = mysqli_query($conex, "SELECT creditos FROM registronuevo WHERE id=$uid LIMIT 1");
$alumno = mysqli_fetch_assoc($res_cred);
$creditos = (int)($alumno['creditos'] ?? 0);

if (isset($_POST['inscribir'])) {
    $clase_id = (int)$_POST['clase_id'];
    if ($creditos <= 0) {
        header('Location: clases.php?error=sincreditos');
        exit();
    }
    $ya = mysqli_query($conex, "SELECT id FROM inscripciones WHERE alumno_id=$uid AND clase_id=$clase_id LIMIT 1");
    if (mysqli_num_rows($ya) == 0) {
        mysqli_query($conex, "INSERT INTO inscripciones (alumno_id, clase_id) VALUES ($uid, $clase_id)");
        mysqli_query($conex, "UPDATE registronuevo SET creditos = GREATEST(0, creditos - 1) WHERE id=$uid");
        recordatorio_si_es_pronto($uid, $clase_id);
        avisar_creditos_bajos($uid);
    }
    header('Location: clases.php?ok=1');
    exit();
}

// Horas de anticipación para que se devuelva el crédito (se cambia en Configuración)
$horas_baja = max(0, (int)cfg('horas_cancelacion', 20));

// Darse de baja de una clase
if (isset($_POST['cancelar'])) {
    $clase_id = (int)$_POST['clase_id'];
    $c = mysqli_fetch_assoc(mysqli_query($conex, "SELECT c.fecha, c.hora_inicio FROM inscripciones i JOIN clases c ON c.id = i.clase_id
                                                  WHERE i.alumno_id=$uid AND i.clase_id=$clase_id LIMIT 1"));
    if (!$c) { header('Location: clases.php'); exit(); }
    $asist = mysqli_query($conex, "SELECT id FROM asistencias WHERE alumno_id=$uid AND clase_id=$clase_id LIMIT 1");
    $inicio = strtotime($c['fecha'] . ' ' . $c['hora_inicio']);

    if (mysqli_num_rows($asist) > 0) {
        header('Location: clases.php?error=presente'); exit();
    }
    if ($inicio <= time()) {
        header('Location: clases.php?error=empezada'); exit();
    }
    mysqli_query($conex, "DELETE FROM inscripciones WHERE alumno_id=$uid AND clase_id=$clase_id");
    $borrada = mysqli_affected_rows($conex) === 1;
    $con_devolucion = ($inicio - time()) >= $horas_baja * 3600;
    if ($borrada && $con_devolucion) {
        mysqli_query($conex, "UPDATE registronuevo SET creditos = creditos + 1 WHERE id=$uid");
    }
    header('Location: clases.php?cancelado=' . ($con_devolucion ? '1' : 'sin'));
    exit();
}

// Solo clases de los próximos 30 días
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM inscripciones WHERE clase_id=c.id) as inscriptos,
        (SELECT COUNT(*) FROM inscripciones WHERE clase_id=c.id AND alumno_id=$uid) as yo_inscripto
        FROM clases c 
        WHERE c.fecha >= CURDATE() 
        AND c.fecha <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY c.fecha ASC, c.hora_inicio ASC";
$res = mysqli_query($conex, $sql);

$dias_es = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
$dias_corto = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= gym_nombre() ?> - Clases</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" type="image/png" href="icono_pulso.png">
  <style>
    .acciones-clase { display:flex; flex-direction:column; align-items:stretch; gap:6px; flex-shrink:0; }
    .acciones-clase form { margin:0; }
    .btn-baja { width:100%; background:transparent; border:1px solid rgba(248,113,113,.5); color:var(--red); border-radius:8px;
                padding:6px 10px; font-size:11px; font-weight:700; font-family:inherit; cursor:pointer; white-space:nowrap; }
    .btn-baja:hover { background:rgba(248,113,113,.12); border-color:var(--red); }
    .acciones-clase .btn-class { text-align:center; cursor:default; }
  </style>
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title">Clases</div>

  <?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success">¡Te anotaste! Se descontó 1 crédito. Si te das de baja con <?= $horas_baja ?> horas de anticipación o más, te lo devolvemos.</div>
  <?php endif; ?>
  <?php if (($_GET['cancelado'] ?? '') === '1'): ?>
    <div class="alert alert-info">Te diste de baja de la clase. Se te devolvió el crédito.</div>
  <?php elseif (($_GET['cancelado'] ?? '') === 'sin'): ?>
    <div class="alert alert-info">Te diste de baja y liberaste tu lugar. Como faltaban menos de <?= $horas_baja ?> horas, el crédito no se devuelve.</div>
  <?php endif; ?>
  <?php if (($_GET['error'] ?? '') === 'presente'): ?>
    <div class="alert alert-danger">Ya diste presente en esa clase, así que no te podés dar de baja.</div>
  <?php elseif (($_GET['error'] ?? '') === 'empezada'): ?>
    <div class="alert alert-danger">Esa clase ya empezó, así que no te podés dar de baja.</div>
  <?php endif; ?>
  <?php if (isset($_GET['error']) && $_GET['error']==='sincreditos'): ?>
    <div class="alert alert-danger">No tenés créditos. <a href="acceso_alumno.php" style="color:var(--blue);font-weight:700">Comprá un pack →</a></div>
  <?php endif; ?>

  <!-- CRÉDITOS -->
  <div class="next-class" style="margin-bottom:20px">
    <div>
      <div class="next-label">Tus créditos disponibles</div>
      <div class="next-name" style="color:<?= $creditos>0?'var(--blue)':'var(--red)' ?>"><?= $creditos ?> créditos</div>
    </div>
    <?php if ($creditos == 0): ?>
      <a href="acceso_alumno.php" style="color:var(--blue);font-size:12px;font-weight:700;text-decoration:none">Comprar +</a>
    <?php endif; ?>
  </div>

  <!-- AVISO 30 DÍAS -->
  <div style="font-size:12px;color:var(--text3);margin-bottom:16px;text-align:center">
    Mostrando clases de los próximos 30 días
  </div>

  <?php
  $clases = [];
  while ($c = mysqli_fetch_assoc($res)) {
      $clases[date('Y-m-d', strtotime($c['fecha']))][] = $c;
  }
  if (empty($clases)): ?>
    <div class="notif-card" style="text-align:center;padding:30px;">
      <div style="font-size:14px;color:var(--text2)">No hay clases disponibles en los próximos 30 días.</div>
    </div>
  <?php else: foreach ($clases as $fecha => $items):
    $ts = strtotime($fecha);
    $label = $dias_es[date('w',$ts)] . ' ' . date('d', $ts);
  ?>
  <div style="margin-bottom:20px">
    <div class="day-label"><?= $label ?></div>
    <?php foreach ($items as $c):
      $yo = (bool)$c['yo_inscripto'];
      $disponibles = max(0, ($c['capacidad'] ?? 20) - $c['inscriptos']);
    ?>
    <div class="class-card">
      <div class="date-box">
        <div class="date-num"><?= date('d', $ts) ?></div>
        <div class="date-day"><?= $dias_corto[date('w',$ts)] ?></div>
      </div>
      <div class="class-info">
        <div class="class-name"><?= htmlspecialchars($c['nombre']) ?></div>
        <div class="class-time"><?= substr($c['hora_inicio'],0,5) ?> - <?= substr($c['hora_fin'],0,5) ?></div>
        <div class="class-spots"><?= $disponibles ?> lugares disponibles</div>
      </div>
      <?php if ($yo):
        $inicio = strtotime($c['fecha'] . ' ' . $c['hora_inicio']);
        $faltan = $inicio - time();
        $devuelve = $faltan >= $horas_baja * 3600;
        $msj = $devuelve
          ? '¿Darte de baja de ' . $c['nombre'] . '? Se te devuelve el crédito.'
          : 'Faltan menos de ' . $horas_baja . ' horas para la clase. Si te das de baja liberás tu lugar, pero el crédito NO se devuelve. ¿Darte de baja igual?';
      ?>
        <div class="acciones-clase">
          <span class="btn-class active">Inscripto ✓</span>
          <?php if ($faltan > 0): ?>
          <form method="post" onsubmit="return confirm(<?= htmlspecialchars(json_encode($msj), ENT_QUOTES) ?>)">
            <input type="hidden" name="clase_id" value="<?= $c['id'] ?>">
            <button type="submit" name="cancelar" class="btn-baja">Darme de baja</button>
          </form>
          <?php endif; ?>
        </div>
      <?php elseif ($creditos > 0 && $disponibles > 0): ?>
        <form method="post">
          <input type="hidden" name="clase_id" value="<?= $c['id'] ?>">
          <button type="submit" name="inscribir" class="btn-class">Anotarme</button>
        </form>
      <?php elseif ($creditos == 0): ?>
        <span class="btn-class" style="opacity:.4;cursor:default;font-size:10px">Sin créditos</span>
      <?php else: ?>
        <span class="btn-class" style="opacity:.4;cursor:default">Completa</span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; endif; ?>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

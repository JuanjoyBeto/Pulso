<?php
require_once 'auth.php';
requerir_rol(['administrador']);
require_once 'dbconn.php';
$pagina_activa = 'admin';
$error = '';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: acceso_administrador.php'); exit(); }

$res = mysqli_query($conex, "SELECT * FROM clases WHERE id=$id LIMIT 1");
$clase = mysqli_fetch_assoc($res);
if (!$clase) { header('Location: acceso_administrador.php'); exit(); }

// Manejar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'eliminar') {
        mysqli_query($conex, "DELETE FROM inscripciones WHERE clase_id=$id");
        mysqli_query($conex, "DELETE FROM asistencias WHERE clase_id=$id");
        mysqli_query($conex, "DELETE FROM clases WHERE id=$id");
        header('Location: acceso_administrador.php');
        exit();
    }

    $nombre = mysqli_real_escape_string($conex, trim($_POST['nombre'] ?? 'Clase'));
    $fecha  = mysqli_real_escape_string($conex, $_POST['fecha'] ?? '');
    $hi     = mysqli_real_escape_string($conex, $_POST['hora_inicio'] ?? '');
    $hf     = mysqli_real_escape_string($conex, $_POST['hora_fin'] ?? '');
    $cap    = (int)($_POST['capacidad'] ?? 20);

    if ($nombre && $fecha && $hi && $hf) {
        mysqli_query($conex, "UPDATE clases SET nombre='$nombre', fecha='$fecha', hora_inicio='$hi', hora_fin='$hf', capacidad=$cap WHERE id=$id");
        header('Location: acceso_administrador.php?clase_ok=1');
        exit();
    } else {
        $error = 'Completá todos los campos.';
    }
}

// Inscriptos en esta clase
$res_ins = mysqli_query($conex, "SELECT r.nombre, r.apellido, a.presente
    FROM inscripciones i
    JOIN registronuevo r ON i.alumno_id = r.id
    LEFT JOIN asistencias a ON a.alumno_id=i.alumno_id AND a.clase_id=$id
    WHERE i.clase_id = $id ORDER BY r.apellido ASC");

$dias_es = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
$ts = strtotime($clase['fecha']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= gym_nombre() ?> - Editar clase</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" type="image/png" href="icono_pulso.png">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title">Editar clase</div>

  <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

  <form method="post">
    <div class="field"><label>Nombre</label><input type="text" name="nombre" value="<?= htmlspecialchars($clase['nombre']) ?>" required></div>
    <div class="field"><label>Fecha</label><input type="date" name="fecha" value="<?= $clase['fecha'] ?>" required></div>
    <div class="field"><label>Hora inicio</label><input type="time" name="hora_inicio" value="<?= substr($clase['hora_inicio'],0,5) ?>" required></div>
    <div class="field"><label>Hora fin</label><input type="time" name="hora_fin" value="<?= substr($clase['hora_fin'],0,5) ?>" required></div>
    <div class="field"><label>Capacidad</label><input type="number" name="capacidad" value="<?= (int)($clase['capacidad'] ?? 20) ?>" min="1"></div>
    <button type="submit" class="btn btn-blue">Guardar cambios</button>
  </form>

  <!-- INSCRIPTOS -->
  <?php $cant_ins = mysqli_num_rows($res_ins); ?>
  <div class="section-title" style="margin-top:24px">
    Inscriptos (<?= $cant_ins ?>)
  </div>

  <?php if ($cant_ins == 0): ?>
    <div class="notif-card" style="text-align:center;padding:20px">
      <div style="font-size:13px;color:var(--text2)">Nadie anotado todavía.</div>
    </div>
  <?php else: while ($a = mysqli_fetch_assoc($res_ins)):
    $estado = is_null($a['presente']) ? 'nd' : ($a['presente'] ? 'ok' : 'no');
  ?>
  <div class="info-row">
    <span class="info-label"><?= htmlspecialchars($a['nombre'].' '.$a['apellido']) ?></span>
    <?php if ($estado==='ok'): ?><span style="color:var(--green);font-weight:700">✓ Presente</span>
    <?php elseif ($estado==='no'): ?><span style="color:var(--red);font-weight:700">✗ Ausente</span>
    <?php else: ?><span style="color:var(--text3)">— Sin registrar</span>
    <?php endif; ?>
  </div>
  <?php endwhile; endif; ?>

  <!-- ELIMINAR CLASE -->
  <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border)">
    <div style="font-size:13px;color:var(--text2);margin-bottom:12px">
      ⚠️ Eliminar esta clase borrará también todas las inscripciones y asistencias relacionadas.
    </div>
    <form method="post" data-confirm-tipo="peligro" data-confirm-titulo="¿Eliminar esta clase?" data-confirm-texto="Se borran también sus inscripciones y asistencias." data-confirm-nota="Esta acción no se puede deshacer." data-confirm-boton="Sí, eliminar">
      <input type="hidden" name="accion" value="eliminar">
      <button type="submit" class="btn btn-danger">Eliminar clase</button>
    </form>
  </div>

  <a href="acceso_administrador.php" class="btn btn-outline" style="display:block;text-align:center;text-decoration:none;margin-top:12px">Volver al panel</a>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

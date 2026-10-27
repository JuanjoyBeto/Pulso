<?php
require_once 'auth.php';
requerir_rol(['administrador']);
require_once 'dbconn.php';
$pagina_activa = 'admin';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre     = mysqli_real_escape_string($conex, trim($_POST['nombre'] ?? 'Clase'));
    $hi         = $_POST['hora_inicio'] ?? '';
    $hf         = $_POST['hora_fin'] ?? '';
    $cap        = (int)($_POST['capacidad'] ?? 20);
    $dias       = $_POST['dias'] ?? []; // array de días: 1=Lun, 2=Mar, etc.
    $fecha_desde = $_POST['fecha_desde'] ?? '';
    $fecha_hasta = $_POST['fecha_hasta'] ?? '';

    if (!$hi || !$hf || empty($dias) || !$fecha_desde || !$fecha_hasta) {
        $error = 'Completá todos los campos.';
    } else {
        $hi_s = mysqli_real_escape_string($conex, $hi);
        $hf_s = mysqli_real_escape_string($conex, $hf);
        
        $creadas = 0;
        $current = strtotime($fecha_desde);
        $hasta   = strtotime($fecha_hasta);
        
        while ($current <= $hasta) {
            $dow = (int)date('N', $current); // 1=Lun, 7=Dom
            if (in_array($dow, $dias)) {
                $fecha_sql = date('Y-m-d', $current);
                // Verificar que no exista ya esa clase
                $existe = mysqli_query($conex, "SELECT id FROM clases WHERE fecha='$fecha_sql' AND hora_inicio='$hi_s' AND nombre='$nombre' LIMIT 1");
                if (mysqli_num_rows($existe) == 0) {
                    mysqli_query($conex, "INSERT INTO clases (nombre, fecha, hora_inicio, hora_fin, capacidad) VALUES ('$nombre','$fecha_sql','$hi_s','$hf_s',$cap)");
                    $creadas++;
                }
            }
            $current = strtotime('+1 day', $current);
        }
        $success = "✅ Se crearon $creadas clases correctamente.";
    }
}

$dias_nombres = ['1'=>'Lunes','2'=>'Martes','3'=>'Miércoles','4'=>'Jueves','5'=>'Viernes','6'=>'Sábado','7'=>'Domingo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= gym_nombre() ?> - Clases recurrentes</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" type="image/png" href="icono_pulso.png">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title">Clases recurrentes</div>
  <p style="font-size:13px;color:var(--text2);margin-bottom:20px">
    Creá clases que se repitan automáticamente en los días que elijas durante un período.
  </p>

  <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

  <form method="post">
    <div class="field">
      <label>Nombre de la clase</label>
      <input type="text" name="nombre" placeholder="Ej: Funcional, Boxeo, Yoga..." required>
    </div>

    <div class="field">
      <label>Hora inicio</label>
      <input type="time" name="hora_inicio" required>
    </div>
    <div class="field">
      <label>Hora fin</label>
      <input type="time" name="hora_fin" required>
    </div>
    <div class="field">
      <label>Capacidad máxima</label>
      <input type="number" name="capacidad" value="20" min="1">
    </div>

    <div class="field">
      <label>Días de la semana</label>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:6px">
        <?php foreach ($dias_nombres as $num => $nombre_dia): ?>
        <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;background:var(--bg3);border:1px solid var(--border);border-radius:10px;cursor:pointer">
          <input type="checkbox" name="dias[]" value="<?= $num ?>" style="accent-color:var(--blue);width:16px;height:16px">
          <span style="font-size:13px;font-weight:600"><?= $nombre_dia ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="field">
      <label>Desde</label>
      <input type="date" name="fecha_desde" min="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="field">
      <label>Hasta</label>
      <input type="date" name="fecha_hasta" min="<?= date('Y-m-d') ?>" required>
    </div>

    <button type="submit" class="btn btn-blue" style="margin-top:8px">Crear clases</button>
    <a href="acceso_administrador.php" class="btn btn-outline" style="display:block;text-align:center;text-decoration:none;margin-top:10px">Cancelar</a>
  </form>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

<?php
require_once 'auth.php';
requerir_rol(['administrador']);
require_once 'dbconn.php';
$pagina_activa = 'admin';
$error = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nombre = mysqli_real_escape_string($conex, trim($_POST['nombre'] ?? 'FUNCIONAL'));
    $fecha  = mysqli_real_escape_string($conex, $_POST['fecha'] ?? '');
    $hi     = mysqli_real_escape_string($conex, $_POST['hora_inicio'] ?? '');
    $hf     = mysqli_real_escape_string($conex, $_POST['hora_fin'] ?? '');
    $cap    = (int)($_POST['capacidad'] ?? 20);

    if ($nombre && $fecha && $hi && $hf) {
        $sql = "INSERT INTO clases (nombre, fecha, hora_inicio, hora_fin, capacidad) VALUES ('$nombre','$fecha','$hi','$hf',$cap)";
        $result = mysqli_query($conex, $sql);
        if ($result) {
            header('Location: acceso_administrador.php?clase_ok=1');
            exit();
        } else {
            $error = 'Error al crear la clase: ' . mysqli_error($conex);
        }
    } else {
        $error = 'Completá todos los campos obligatorios.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Point - Nueva Clase</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title">Nueva Clase</div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <div class="field">
      <label>Nombre de la clase</label>
      <input type="text" name="nombre" value="FUNCIONAL" required>
    </div>
    <div class="field">
      <label>Fecha *</label>
      <input type="date" name="fecha" min="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="field">
      <label>Hora inicio *</label>
      <input type="time" name="hora_inicio" required>
    </div>
    <div class="field">
      <label>Hora fin *</label>
      <input type="time" name="hora_fin" required>
    </div>
    <div class="field">
      <label>Capacidad máxima</label>
      <input type="number" name="capacidad" value="20" min="1">
    </div>
    <button type="submit" class="btn btn-blue" style="margin-top:8px">Crear clase</button>
    <a href="acceso_administrador.php" class="btn btn-outline" style="display:block;text-align:center;text-decoration:none;margin-top:10px">Cancelar</a>
    <a href="admin_clase_recurrente.php" class="btn btn-outline" style="display:block;text-align:center;text-decoration:none;margin-top:10px;border-color:var(--blue);color:var(--blue)">🔄 Crear clases recurrentes</a>
  </form>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

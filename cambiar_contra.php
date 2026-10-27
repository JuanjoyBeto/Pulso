<?php
require_once 'auth.php';
requerir_login();
require_once 'dbconn.php';
$pagina_activa = 'perfil';

$mensaje = '';
$tipo = '';
$uid = (int)$_SESSION['usuario_id'];

if (isset($_POST['Cambiar'])) {
    $pass_actual = $_POST['pass_actual'] ?? '';
    $pass_nueva  = $_POST['pass_nueva'] ?? '';
    $pass_conf   = $_POST['pass_conf'] ?? '';

    $res = mysqli_query($conex, "SELECT pass FROM registronuevo WHERE id = $uid LIMIT 1");
    $row = mysqli_fetch_assoc($res);

    if ($pass_actual === '' || $pass_nueva === '' || $pass_conf === '') {
        $mensaje = 'Completá todos los campos.'; $tipo = 'danger';
    } elseif ($pass_nueva !== $pass_conf) {
        $mensaje = 'Las contraseñas nuevas no coinciden.'; $tipo = 'danger';
    } elseif (strlen($pass_nueva) < 6) {
        $mensaje = 'La contraseña nueva tiene que tener al menos 6 caracteres.'; $tipo = 'danger';
    } elseif (!$row || !password_verify($pass_actual, $row['pass'])) {
        $mensaje = 'La contraseña actual es incorrecta.'; $tipo = 'danger';
    } else {
        $hash = mysqli_real_escape_string($conex, password_hash($pass_nueva, PASSWORD_DEFAULT));
        mysqli_query($conex, "UPDATE registronuevo SET pass = '$hash' WHERE id = $uid");
        $mensaje = '✅ ¡Listo! Tu contraseña se cambió correctamente.'; $tipo = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= gym_nombre() ?> - Cambiar contraseña</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" type="image/png" href="icono_pulso.png">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title">🔒 Cambiar contraseña</div>

  <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
  <?php endif; ?>

  <form method="post" class="card-tp" style="max-width:460px">
    <div class="field">
      <label>Contraseña actual</label>
      <input type="password" name="pass_actual" placeholder="••••••••" required>
    </div>
    <div class="field">
      <label>Contraseña nueva</label>
      <input type="password" name="pass_nueva" placeholder="Mínimo 6 caracteres" minlength="6" required>
    </div>
    <div class="field">
      <label>Repetí la contraseña nueva</label>
      <input type="password" name="pass_conf" placeholder="••••••••" minlength="6" required>
    </div>
    <button type="submit" name="Cambiar" class="btn btn-blue">Guardar cambios</button>
  </form>

  <a href="perfil.php" style="display:block;margin-top:16px;color:var(--text2);font-size:13px;text-decoration:none">← Volver a mi perfil</a>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

<?php
require_once 'auth.php';
require_once 'dbconn.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $genero   = trim($_POST['genero'] ?? '');

    if (!$nombre || !$apellido || !$email || !$usuario || !$password) {
        $error = 'Completá todos los campos obligatorios.';
    } else {
        $u = mysqli_real_escape_string($conex, $usuario);
        $e = mysqli_real_escape_string($conex, $email);
        $check = mysqli_query($conex, "SELECT id FROM registronuevo WHERE user='$u' OR email='$e' LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            $error = 'El usuario o email ya están en uso.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $n = mysqli_real_escape_string($conex, $nombre);
            $a = mysqli_real_escape_string($conex, $apellido);
            $t = mysqli_real_escape_string($conex, $telefono);
            $g = mysqli_real_escape_string($conex, $genero);
            $sql = "INSERT INTO registronuevo (nombre, apellido, email, user, pass, telefono, genero, rol)
                    VALUES ('$n','$a','$e','$u','$hash','$t','$g','alumno')";
            if (mysqli_query($conex, $sql)) {
                header('Location: index.php?registro=1');
                exit();
            } else {
                $error = 'Error al crear la cuenta. Intentá de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= gym_nombre() ?> - Registrarse</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" type="image/png" href="icono_pulso.png">
</head>
<body>
<div class="auth-bg">
  <div class="auth-card">
    <div class="auth-logo">
      <img src="<?= gym_logo() ?>" alt="<?= gym_nombre() ?>" style="height:75px;width:auto;object-fit:contain;display:block;margin:0 auto 16px">
    </div>
    <p class="auth-subtitle">Creá tu cuenta</p>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form action="alta.php" method="post">
      <div class="field"><label>Nombre *</label><input type="text" name="nombre" placeholder="Juan" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required></div>
      <div class="field"><label>Apellido *</label><input type="text" name="apellido" placeholder="Pérez" value="<?= htmlspecialchars($_POST['apellido'] ?? '') ?>" required></div>
      <div class="field"><label>Email *</label><input type="email" name="email" placeholder="juan@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required></div>
      <div class="field"><label>Usuario *</label><input type="text" name="usuario" placeholder="juanperez99" value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" required></div>
      <div class="field"><label>Contraseña *</label><input type="password" name="password" placeholder="Mínimo 6 caracteres" required></div>
      <div class="field"><label>Celular (para avisos por WhatsApp)</label><input type="tel" name="telefono" placeholder="+54 9 11 2345 6789" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"></div>
      <div class="field">
        <label>Género</label>
        <select name="genero">
          <option value="">Seleccionar</option>
          <option value="Masculino">Masculino</option>
          <option value="Femenino">Femenino</option>
          <option value="Otro">Otro</option>
        </select>
      </div>
      <button type="submit" class="btn btn-blue">Registrarse</button>
    </form>
    <div class="auth-link">¿Ya tenés cuenta? <a href="index.php">Ingresar</a></div>
  </div>
</div>
</body>
</html>

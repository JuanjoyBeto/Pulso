<?php
require_once 'auth.php';
if (usuario_autenticado()) {
    redirigir_por_rol(rol_actual());
}
require_once 'config.php';
$gym  = htmlspecialchars(cfg('nombre_gimnasio', 'Pulso'));
$logo = htmlspecialchars(cfg('logo', 'logo_pulso.png'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $gym ?> - Iniciar Sesión</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" type="image/png" href="icono_pulso.png">
</head>
<body>
<div class="auth-bg">
  <div class="auth-card">
    <div class="auth-logo">
      <img src="<?= $logo ?>" alt="<?= $gym ?>" style="height:75px;width:auto;object-fit:contain;display:block;margin:0 auto 16px">
    </div>
    <p class="auth-subtitle">Bienvenido a <strong style="color:#fff"><?= $gym ?></strong></p>

    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-danger">Usuario o contraseña incorrectos.</div>
    <?php endif; ?>
    <?php if (isset($_GET['registro'])): ?>
      <div class="alert alert-success">¡Cuenta creada! Ya podés iniciar sesión.</div>
    <?php endif; ?>
    <?php if (isset($_GET['logout'])): ?>
      <div class="alert alert-info">Sesión cerrada correctamente.</div>
    <?php endif; ?>

    <form action="ingreso.php" method="post">
      <div class="field">
        <label>Usuario</label>
        <input type="text" name="usuario" placeholder="Tu usuario" required>
      </div>
      <div class="field">
        <label>Contraseña</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <div class="forgot"><a href="recuperar_clave.php">¿Olvidaste tu contraseña?</a></div>
      <button type="submit" name="Enviar" class="btn btn-blue">Ingresar</button>
    </form>

    <div class="divider"><span>o</span></div>
    <a href="google_login.php" class="google-btn">
      <svg width="18" height="18" viewBox="0 0 48 48">
        <path fill="#FFC107" d="M43.6 20H24v8h11.3C33.6 32.6 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.8 1.1 7.9 3l5.7-5.7C34.1 6.5 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c11 0 19.7-8 19.7-20 0-1.3-.1-2.7-.1-4z"/>
        <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.5 15.1 18.9 12 24 12c3 0 5.8 1.1 7.9 3l5.7-5.7C34.1 6.5 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
        <path fill="#4CAF50" d="M24 44c5.2 0 9.9-1.9 13.5-5.1l-6.2-5.2C29.5 35.5 26.9 36 24 36c-5.2 0-9.6-3.3-11.3-8H6.1C9.5 37.4 16.3 44 24 44z"/>
        <path fill="#1976D2" d="M43.6 20H24v8h11.3c-.8 2.3-2.3 4.3-4.2 5.7l6.2 5.2C41 35.5 44 30.2 44 24c0-1.3-.1-2.7-.4-4z"/>
      </svg>
      Ingresar con Google
    </a>
    <div class="auth-link">¿No tenés cuenta? <a href="alta.php">Registrate</a></div>
    <?php if (strcasecmp(cfg('nombre_gimnasio', 'Pulso'), 'Pulso') !== 0): ?>
    <div style="text-align:center;margin-top:22px;font-size:11px;color:var(--text2);opacity:.6">Funciona con <strong>Pulso</strong></div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Point - Recuperar contraseña</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="auth-bg">
  <div class="auth-card">
    <div class="auth-logo">
      <img src="logo_tp.png" alt="Training Point" style="height:75px;width:auto;object-fit:contain;display:block;margin:0 auto 16px">
    </div>
    <p class="auth-subtitle">Recuperá tu contraseña</p>
    <p style="font-size:13px;color:var(--text2);text-align:center;margin-bottom:20px;">
      Ingresá tu correo y te enviamos un enlace para restablecerla.
    </p>
    <form action="procesar_recupero.php" method="post">
      <div class="field">
        <label>Correo electrónico</label>
        <input type="email" name="email" placeholder="juan@email.com" required>
      </div>
      <button type="submit" class="btn btn-blue">Enviar correo</button>
    </form>
    <div class="auth-link"><a href="index.php">← Volver al login</a></div>
  </div>
</div>
</body>
</html>

<?php
require_once 'auth.php';
requerir_login();
require_once 'dbconn.php';
$pagina_activa = 'notif';
$uid = $_SESSION['usuario_id'];
$es_admin = (rol_actual() === 'administrador');

if ($es_admin) {
    // Admin ve todas las notificaciones
    $sql = "SELECT * FROM notificaciones ORDER BY fecha DESC LIMIT 50";
} else {
    // Alumno solo ve las que son para él
    $sql = "SELECT * FROM notificaciones WHERE alumno_id = $uid ORDER BY fecha DESC LIMIT 30";
}
$res = mysqli_query($conex, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Point - Notificaciones</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title">Notificaciones</div>
  <?php if (mysqli_num_rows($res) == 0): ?>
    <div class="notif-card" style="text-align:center;padding:30px;">
      <div style="font-size:14px;color:var(--text2)">No tenés notificaciones.</div>
    </div>
  <?php else: while ($n = mysqli_fetch_assoc($res)): ?>
    <div class="notif-card">
      <div class="notif-from"><?= $es_admin ? 'Aviso' : 'Mensaje de Dami' ?></div>
      <div class="notif-msg"><?= htmlspecialchars($n['mensaje']) ?></div>
      <div class="notif-time"><?= date('d/m/Y H:i', strtotime($n['fecha'])) ?></div>
    </div>
  <?php endwhile; endif; ?>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

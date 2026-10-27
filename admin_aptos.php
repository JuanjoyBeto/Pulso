<?php
require_once 'auth.php';
requerir_rol(['administrador']);
require_once 'dbconn.php';
$pagina_activa = 'aptos';

// Aprobar / rechazar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aid = (int)($_POST['alumno_id'] ?? 0);
    $volver = ($_POST['volver'] ?? '') === 'ficha' ? "admin_alumno.php?id=$aid" : 'admin_aptos.php';
    if (isset($_POST['aprobar'])) {
        mysqli_query($conex, "UPDATE registronuevo SET apto_estado='aprobado', apto_medico=1, apto_motivo=NULL, apto_fecha_revision=NOW() WHERE id=$aid");
        notificar($aid, msg_apto_aprobado());
    } elseif (isset($_POST['rechazar'])) {
        $motivo = trim($_POST['motivo'] ?? '') ?: 'No se pudo validar el apto.';
        $motivo_s = mysqli_real_escape_string($conex, mb_substr($motivo, 0, 250));
        mysqli_query($conex, "UPDATE registronuevo SET apto_estado='rechazado', apto_medico=0, apto_motivo='$motivo_s', apto_fecha_revision=NOW() WHERE id=$aid");
        notificar($aid, '❌ Tu apto médico fue rechazado: ' . mb_substr($motivo, 0, 250) . '. Subí uno nuevo desde tu perfil.');
    }
    header("Location: $volver");
    exit();
}

$pendientes = mysqli_query($conex, "SELECT id, nombre, apellido, apto_medico_archivo, apto_fecha_subida FROM registronuevo
                                    WHERE rol='alumno' AND apto_estado='pendiente' ORDER BY apto_fecha_subida ASC");
$revisados  = mysqli_query($conex, "SELECT id, nombre, apellido, apto_estado, apto_motivo, apto_fecha_revision FROM registronuevo
                                    WHERE rol='alumno' AND apto_estado IN ('aprobado','rechazado') ORDER BY apto_fecha_revision DESC LIMIT 30");
$r = mysqli_query($conex, "SELECT apto_estado, COUNT(*) c FROM registronuevo WHERE rol='alumno' GROUP BY apto_estado");
$cuenta = ['sin_cargar' => 0, 'pendiente' => 0, 'aprobado' => 0, 'rechazado' => 0];
while ($f = mysqli_fetch_assoc($r)) $cuenta[$f['apto_estado']] = (int)$f['c'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= gym_nombre() ?> - Aptos médicos</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" type="image/png" href="icono_pulso.png">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title">🩺 Aptos médicos</div>

  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:22px">
    <?php foreach ($cuenta as $est => $n): list($ic, $tx, $co) = apto_info($est); ?>
    <div class="card-tp" style="text-align:center;padding:12px 6px">
      <div style="font-size:22px;font-weight:900;color:<?= $co ?>"><?= $n ?></div>
      <div style="font-size:11px;color:var(--text2)"><?= $ic ?> <?= $tx ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="section-title">Pendientes de validación</div>
  <?php if (mysqli_num_rows($pendientes) === 0): ?>
    <p style="color:var(--text2);font-size:13px;margin-bottom:22px">No hay aptos para revisar. 🙌</p>
  <?php endif; ?>
  <?php while ($a = mysqli_fetch_assoc($pendientes)):
      $arch = 'uploads/aptos/' . $a['apto_medico_archivo'];
      $pdf = strtolower(pathinfo($arch, PATHINFO_EXTENSION)) === 'pdf'; ?>
    <div class="card-tp" style="margin-bottom:14px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:10px">
        <a href="admin_alumno.php?id=<?= $a['id'] ?>" style="font-weight:800;color:#fff;text-decoration:none"><?= htmlspecialchars($a['nombre'] . ' ' . $a['apellido']) ?></a>
        <span style="font-size:11px;color:var(--text2)">Subido <?= $a['apto_fecha_subida'] ? date('d/m H:i', strtotime($a['apto_fecha_subida'])) : '' ?></span>
      </div>
      <a href="<?= htmlspecialchars($arch) ?>" target="_blank" style="display:block;margin-bottom:12px;text-align:center">
        <?php if ($pdf): ?>
          <span class="btn btn-outline" style="display:inline-block;width:auto;padding:10px 20px">📄 Abrir PDF</span>
        <?php else: ?>
          <img src="<?= htmlspecialchars($arch) ?>" alt="Apto" style="max-width:100%;max-height:320px;border-radius:10px">
        <?php endif; ?>
      </a>
      <form method="post">
        <input type="hidden" name="alumno_id" value="<?= $a['id'] ?>">
        <div style="display:flex;gap:8px;margin-bottom:8px">
          <button name="aprobar" class="btn btn-blue" style="flex:1">✅ Aprobar</button>
          <button name="rechazar" class="btn btn-outline" style="flex:1;border-color:var(--red);color:var(--red)">❌ Rechazar</button>
        </div>
        <div class="field" style="margin:0"><input type="text" name="motivo" placeholder="Motivo si lo rechazás (ej: está vencido, no se lee, falta la firma)" style="width:100%;box-sizing:border-box;font-size:12px"></div>
      </form>
    </div>
  <?php endwhile; ?>

  <div class="section-title" style="margin-top:10px">Revisados últimamente</div>
  <div class="card-tp">
    <?php if (mysqli_num_rows($revisados) === 0): ?><p style="color:var(--text2);font-size:13px;margin:0">Todavía no revisaste ninguno.</p><?php endif; ?>
    <?php while ($a = mysqli_fetch_assoc($revisados)): list($ic, $tx, $co) = apto_info($a['apto_estado']); ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);font-size:13px;gap:10px">
        <div>
          <a href="admin_alumno.php?id=<?= $a['id'] ?>" style="color:#fff;font-weight:600;text-decoration:none"><?= htmlspecialchars($a['nombre'] . ' ' . $a['apellido']) ?></a>
          <?php if ($a['apto_motivo']): ?><div style="font-size:11px;color:var(--text2)"><?= htmlspecialchars($a['apto_motivo']) ?></div><?php endif; ?>
        </div>
        <div style="text-align:right;white-space:nowrap">
          <div style="font-size:12px;font-weight:700;color:<?= $co ?>"><?= $ic ?> <?= $tx ?></div>
          <div style="font-size:11px;color:var(--text2)"><?= $a['apto_fecha_revision'] ? date('d/m/Y', strtotime($a['apto_fecha_revision'])) : '' ?></div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

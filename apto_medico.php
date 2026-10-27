<?php
require_once 'auth.php';
requerir_login();
require_once 'dbconn.php';
$pagina_activa = 'perfil';
$uid = (int)$_SESSION['usuario_id'];

$error = '';
$success = '';

// Subida del apto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['apto_archivo'])) {
    $archivo = $_FILES['apto_archivo'];
    // Tipo real del archivo (no confiamos en lo que dice el navegador)
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
    $tipo_real = '';
    if ($archivo['error'] === UPLOAD_ERR_OK) {
        if (function_exists('mime_content_type')) {
            $tipo_real = mime_content_type($archivo['tmp_name']);
        } elseif (($img = @getimagesize($archivo['tmp_name'])) !== false) {
            $tipo_real = $img['mime'];
        } elseif (file_get_contents($archivo['tmp_name'], false, null, 0, 4) === '%PDF') {
            $tipo_real = 'application/pdf';
        }
    }

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error al subir el archivo. Intentá de nuevo.';
    } elseif (!isset($permitidos[$tipo_real])) {
        $error = 'Solo se permiten archivos JPG, PNG o PDF.';
    } elseif ($archivo['size'] > 5 * 1024 * 1024) {
        $error = 'El archivo no puede superar los 5MB.';
    } else {
        $upload_dir = 'uploads/aptos/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $nombre_archivo = 'apto_' . $uid . '_' . time() . '.' . $permitidos[$tipo_real];

        if (move_uploaded_file($archivo['tmp_name'], $upload_dir . $nombre_archivo)) {
            mysqli_query($conex, "UPDATE registronuevo SET apto_medico_archivo='$nombre_archivo', apto_medico=0,
                                  apto_estado='pendiente', apto_motivo=NULL, apto_fecha_subida=NOW(), apto_fecha_revision=NULL
                                  WHERE id=$uid");
            // Aviso a los admins
            $res_admin = mysqli_query($conex, "SELECT id FROM registronuevo WHERE rol='administrador'");
            while ($ad = mysqli_fetch_assoc($res_admin)) {
                notificar($ad['id'], '🩺 ' . nombre_usuario_actual() . ' subió su apto médico. Revisalo en Aptos.');
            }
            $success = '¡Apto subido! Ahora el gimnasio tiene que validarlo.';
        } else {
            $error = 'No se pudo guardar el archivo. Verificá los permisos de la carpeta.';
        }
    }
}

$res = mysqli_query($conex, "SELECT apto_medico_archivo, apto_estado, apto_motivo, apto_fecha_revision FROM registronuevo WHERE id=$uid LIMIT 1");
$alumno = mysqli_fetch_assoc($res);
$archivo_actual = $alumno['apto_medico_archivo'] ?? '';
$estado = $archivo_actual ? ($alumno['apto_estado'] ?: 'pendiente') : 'sin_cargar';
list($icono, $texto, $color) = apto_info($estado);
$es_pdf = $archivo_actual && strtolower(pathinfo($archivo_actual, PATHINFO_EXTENSION)) === 'pdf';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= gym_nombre() ?> - Apto Médico</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" type="image/png" href="icono_pulso.png">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title">Apto Médico</div>
  <p style="font-size:13px;color:var(--text2);margin-bottom:20px">
    Subí tu apto médico firmado por un médico (foto o PDF). El gimnasio lo revisa y lo valida.
  </p>

  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <?php if ($archivo_actual): ?>
  <div class="card-tp" style="margin-bottom:20px;text-align:center">
    <div style="font-size:32px;margin-bottom:8px"><?= $icono ?></div>
    <div style="font-size:16px;font-weight:800;color:<?= $color ?>;margin-bottom:6px"><?= $texto ?></div>
    <div style="font-size:12px;color:var(--text2);margin-bottom:16px">
      <?php if ($estado === 'pendiente'): ?>
        El gimnasio todavía no lo revisó. Te avisamos cuando esté validado.
      <?php elseif ($estado === 'aprobado'): ?>
        Tu apto fue validado<?= $alumno['apto_fecha_revision'] ? ' el ' . date('d/m/Y', strtotime($alumno['apto_fecha_revision'])) : '' ?>. ¡Ya está todo en regla!
      <?php else: ?>
        <?php if ($alumno['apto_motivo']): ?>
          <span style="display:block;background:var(--bg3);border-radius:10px;padding:10px;margin:0 auto 6px;max-width:420px;color:#fff">Motivo: <?= htmlspecialchars($alumno['apto_motivo']) ?></span>
        <?php endif; ?>
        Subí un apto nuevo para que lo vuelvan a revisar.
      <?php endif; ?>
    </div>
    <?php if ($es_pdf): ?>
      <a href="uploads/aptos/<?= htmlspecialchars($archivo_actual) ?>" target="_blank" class="btn btn-blue" style="display:inline-block;text-decoration:none;padding:10px 24px;width:auto">📄 Ver PDF</a>
    <?php else: ?>
      <img src="uploads/aptos/<?= htmlspecialchars($archivo_actual) ?>" style="max-width:100%;max-height:420px;border-radius:10px" alt="Apto médico">
    <?php endif; ?>
  </div>
  <div class="card-tp">
    <div style="font-size:13px;color:var(--text2);margin-bottom:12px"><?= $estado === 'rechazado' ? 'Subí el apto corregido:' : '¿Querés reemplazarlo por uno nuevo?' ?></div>
    <form method="post" enctype="multipart/form-data">
      <input type="file" name="apto_archivo" accept=".jpg,.jpeg,.png,.pdf" style="display:none" id="file-input" onchange="this.form.submit()">
      <label for="file-input" class="btn <?= $estado === 'rechazado' ? 'btn-blue' : 'btn-outline' ?>" style="display:block;text-align:center;cursor:pointer">📎 <?= $estado === 'rechazado' ? 'Subir apto nuevo' : 'Reemplazar apto' ?></label>
    </form>
  </div>

  <?php else: ?>
  <div class="card-tp" style="text-align:center;padding:30px 20px">
    <div style="font-size:48px;margin-bottom:12px">📋</div>
    <div style="font-size:15px;font-weight:700;color:#fff;margin-bottom:8px">No tenés apto médico cargado</div>
    <div style="font-size:13px;color:var(--text2);margin-bottom:24px">Subí una foto o PDF de tu apto médico firmado por un médico.</div>
    <form method="post" enctype="multipart/form-data">
      <input type="file" name="apto_archivo" accept=".jpg,.jpeg,.png,.pdf" style="display:none" id="file-input" onchange="this.form.submit()">
      <label for="file-input" class="btn btn-blue" style="display:block;text-align:center;cursor:pointer">📎 Subir apto médico</label>
    </form>
    <div style="font-size:11px;color:var(--text3);margin-top:12px">JPG, PNG o PDF · Máximo 5MB</div>
  </div>
  <?php endif; ?>

  <a href="perfil.php" style="display:block;text-align:center;color:var(--text2);font-size:13px;margin-top:20px;text-decoration:none">← Volver al perfil</a>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

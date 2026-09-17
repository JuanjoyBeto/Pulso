<?php
require_once 'auth.php';
requerir_login();
require_once 'dbconn.php';
$pagina_activa = 'perfil';
$uid = $_SESSION['usuario_id'];

$error = '';
$success = '';

// Manejar subida de archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['apto_archivo'])) {
    $archivo = $_FILES['apto_archivo'];
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error al subir el archivo. Intentá de nuevo.';
    } elseif (!in_array($archivo['type'], $tipos_permitidos)) {
        $error = 'Solo se permiten archivos JPG, PNG o PDF.';
    } elseif ($archivo['size'] > $max_size) {
        $error = 'El archivo no puede superar los 5MB.';
    } else {
        $upload_dir = 'uploads/aptos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombre_archivo = 'apto_' . $uid . '_' . time() . '.' . $extension;
        $destino = $upload_dir . $nombre_archivo;

        if (move_uploaded_file($archivo['tmp_name'], $destino)) {
            $nombre_s = mysqli_real_escape_string($conex, $nombre_archivo);
            mysqli_query($conex, "UPDATE registronuevo SET apto_medico_archivo='$nombre_s', apto_medico=1 WHERE id=$uid");
            $success = '¡Apto médico subido correctamente!';
        } else {
            $error = 'No se pudo guardar el archivo. Verificá los permisos de la carpeta.';
        }
    }
}

// Obtener datos del alumno
$res = mysqli_query($conex, "SELECT apto_medico, apto_medico_archivo FROM registronuevo WHERE id=$uid LIMIT 1");
$alumno = mysqli_fetch_assoc($res);
$tiene_apto = !empty($alumno['apto_medico_archivo']);
$archivo_actual = $alumno['apto_medico_archivo'] ?? '';
$es_pdf = $tiene_apto && strtolower(pathinfo($archivo_actual, PATHINFO_EXTENSION)) === 'pdf';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Point - Apto Médico</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title">Apto Médico</div>
  <p style="font-size:13px;color:var(--text2);margin-bottom:20px">
    Subí tu apto médico firmado por un médico. Puede ser una foto o un PDF. Se guarda de forma permanente en tu perfil.
  </p>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if ($tiene_apto): ?>
  <!-- APTO YA CARGADO -->
  <div class="card-tp" style="margin-bottom:20px;text-align:center">
    <div style="font-size:32px;margin-bottom:8px">✅</div>
    <div style="font-size:15px;font-weight:700;color:var(--green);margin-bottom:6px">Apto médico cargado</div>
    <div style="font-size:12px;color:var(--text2);margin-bottom:16px">Tu apto médico está guardado en tu perfil.</div>
    <?php if ($es_pdf): ?>
      <a href="uploads/aptos/<?= htmlspecialchars($archivo_actual) ?>" target="_blank"
         class="btn btn-blue" style="display:inline-block;text-decoration:none;padding:10px 24px;width:auto">
        📄 Ver PDF
      </a>
    <?php else: ?>
      <img src="uploads/aptos/<?= htmlspecialchars($archivo_actual) ?>" 
           style="max-width:100%;border-radius:10px;margin-bottom:12px" alt="Apto médico">
    <?php endif; ?>
  </div>
  <div class="card-tp">
    <div style="font-size:13px;color:var(--text2);margin-bottom:12px">¿Querés reemplazarlo por uno nuevo?</div>
    <form method="post" enctype="multipart/form-data">
      <input type="file" name="apto_archivo" accept=".jpg,.jpeg,.png,.pdf"
             style="display:none" id="file-input" onchange="this.form.submit()">
      <label for="file-input" class="btn btn-outline" style="display:block;text-align:center;cursor:pointer">
        📎 Reemplazar apto
      </label>
    </form>
  </div>

  <?php else: ?>
  <!-- SIN APTO -->
  <div class="card-tp" style="text-align:center;padding:30px 20px">
    <div style="font-size:48px;margin-bottom:12px">📋</div>
    <div style="font-size:15px;font-weight:700;color:#fff;margin-bottom:8px">No tenés apto médico cargado</div>
    <div style="font-size:13px;color:var(--text2);margin-bottom:24px">
      Subí una foto o PDF de tu apto médico firmado por un médico.
    </div>
    <form method="post" enctype="multipart/form-data">
      <input type="file" name="apto_archivo" accept=".jpg,.jpeg,.png,.pdf"
             style="display:none" id="file-input" onchange="this.form.submit()">
      <label for="file-input" class="btn btn-blue" style="display:block;text-align:center;cursor:pointer;text-decoration:none">
        📎 Subir apto médico
      </label>
    </form>
    <div style="font-size:11px;color:var(--text3);margin-top:12px">JPG, PNG o PDF · Máximo 5MB</div>
  </div>
  <?php endif; ?>

  <a href="perfil.php" style="display:block;text-align:center;color:var(--text2);font-size:13px;margin-top:20px;text-decoration:none">
    ← Volver al perfil
  </a>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

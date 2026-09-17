<?php
require_once 'auth.php';
requerir_rol(['administrador']);
require_once 'dbconn.php';
$pagina_activa = 'qr';

$mensaje = '';
$tipo = '';
$alumno_encontrado = null;
$clase_encontrada = null;

if (isset($_POST['qr_data']) && !empty($_POST['qr_data'])) {
    $qr_raw = trim($_POST['qr_data']);
    $parts = explode(':', $qr_raw);

    if (count($parts) === 3) {
        // Nuevo formato: alumno_id:clase_id:token
        $alumno_id = (int)$parts[0];
        $clase_id  = (int)$parts[1];
        $token     = $parts[2];
        $token_esperado = md5($alumno_id . ':' . $clase_id . 'trainingpoint2025');

        if ($token === $token_esperado) {
            $res = mysqli_query($conex, "SELECT * FROM registronuevo WHERE id=$alumno_id LIMIT 1");
            $alumno_encontrado = mysqli_fetch_assoc($res);

            $res_c = mysqli_query($conex, "SELECT * FROM clases WHERE id=$clase_id LIMIT 1");
            $clase_encontrada = mysqli_fetch_assoc($res_c);

            if ($alumno_encontrado && $clase_encontrada) {
                // Verificar que esté inscripto
                $inscripto = mysqli_query($conex, "SELECT id FROM inscripciones WHERE alumno_id=$alumno_id AND clase_id=$clase_id LIMIT 1");
                if (mysqli_num_rows($inscripto) > 0) {
                    $ya = mysqli_query($conex, "SELECT id FROM asistencias WHERE alumno_id=$alumno_id AND clase_id=$clase_id LIMIT 1");
                    if (mysqli_num_rows($ya) == 0) {
                        mysqli_query($conex, "INSERT INTO asistencias (alumno_id, clase_id, presente, fecha) VALUES ($alumno_id, $clase_id, 1, NOW())");
                        $mensaje = '✅ Asistencia registrada';
                        $tipo = 'success';
                    } else {
                        $mensaje = 'ℹ️ Ya registró asistencia para esta clase';
                        $tipo = 'info';
                    }
                } else {
                    $mensaje = '⚠️ El alumno no está anotado a esta clase';
                    $tipo = 'danger';
                }
            } else {
                $mensaje = '❌ Alumno o clase no encontrados';
                $tipo = 'danger';
            }
        } else {
            $mensaje = '❌ QR inválido';
            $tipo = 'danger';
        }
    } elseif (count($parts) === 2) {
        // Formato viejo: alumno_id:token (compatibilidad)
        $alumno_id = (int)$parts[0];
        $token = $parts[1];
        $token_esperado = md5($alumno_id . 'trainingpoint2025');
        if ($token === $token_esperado) {
            $res = mysqli_query($conex, "SELECT * FROM registronuevo WHERE id=$alumno_id LIMIT 1");
            $alumno_encontrado = mysqli_fetch_assoc($res);
            $hoy = date('Y-m-d');
            $hora = date('H:i:s');
            $clase_res = mysqli_query($conex,
                "SELECT c.* FROM clases c
                 JOIN inscripciones i ON i.clase_id = c.id
                 WHERE i.alumno_id = $alumno_id AND c.fecha = '$hoy'
                 ORDER BY ABS(TIMEDIFF(c.hora_inicio, '$hora')) ASC LIMIT 1");
            $clase_encontrada = mysqli_fetch_assoc($clase_res);
            if ($clase_encontrada) {
                $clase_id = $clase_encontrada['id'];
                $ya = mysqli_query($conex, "SELECT id FROM asistencias WHERE alumno_id=$alumno_id AND clase_id=$clase_id LIMIT 1");
                if (mysqli_num_rows($ya) == 0) {
                    mysqli_query($conex, "INSERT INTO asistencias (alumno_id, clase_id, presente, fecha) VALUES ($alumno_id, $clase_id, 1, NOW())");
                    $mensaje = '✅ Asistencia registrada';
                    $tipo = 'success';
                } else {
                    $mensaje = 'ℹ️ Ya registró asistencia hoy';
                    $tipo = 'info';
                }
            } else {
                $mensaje = '⚠️ El alumno no tiene clase hoy';
                $tipo = 'danger';
            }
        } else {
            $mensaje = '❌ QR inválido';
            $tipo = 'danger';
        }
    } else {
        $mensaje = '❌ QR inválido';
        $tipo = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Point - Lector QR</title>
  <link rel="stylesheet" href="styles.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title">Lector QR</div>
  <p style="font-size:13px;color:var(--text2);margin-bottom:20px">
    Apuntá la cámara al QR del alumno para registrar su asistencia.
  </p>

  <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo ?>" style="text-align:center;padding:20px;margin-bottom:20px">
      <?php if ($alumno_encontrado): ?>
        <div style="font-size:20px;font-weight:900;margin-bottom:4px">
          <?= htmlspecialchars($alumno_encontrado['nombre'] . ' ' . $alumno_encontrado['apellido']) ?>
        </div>
      <?php endif; ?>
      <?php if ($clase_encontrada): ?>
        <div style="font-size:13px;margin-bottom:8px;opacity:0.8">
          <?= htmlspecialchars($clase_encontrada['nombre']) ?> · <?= date('d/m', strtotime($clase_encontrada['fecha'])) ?> · <?= substr($clase_encontrada['hora_inicio'],0,5) ?>
        </div>
      <?php endif; ?>
      <div style="font-size:16px;font-weight:700"><?= $mensaje ?></div>
    </div>
  <?php endif; ?>

  <div id="reader" style="width:100%;max-width:400px;margin:0 auto 20px;border-radius:14px;overflow:hidden;border:2px solid var(--border)"></div>

  <div class="card-tp" style="margin-top:16px">
    <div style="font-size:12px;color:var(--text2);margin-bottom:10px">Ingreso manual (si la cámara no funciona):</div>
    <form method="post">
      <input type="text" name="qr_data" class="input-tp" placeholder="Pegá el código QR aquí" style="margin-bottom:10px">
      <button type="submit" class="btn btn-blue">Registrar</button>
    </form>
  </div>

  <a href="acceso_administrador.php" class="btn btn-outline" style="display:block;text-align:center;text-decoration:none;margin-top:12px">Volver al panel</a>
</div>

<script>
let escaneando = false;
function onScanSuccess(decodedText) {
  if (escaneando) return;
  escaneando = true;
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'admin_escanear_qr.php';
  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = 'qr_data';
  input.value = decodedText;
  form.appendChild(input);
  document.body.appendChild(form);
  form.submit();
}
const html5QrCode = new Html5Qrcode("reader");
html5QrCode.start(
  { facingMode: "environment" },
  { fps: 10, qrbox: { width: 250, height: 250 } },
  onScanSuccess
).catch(() => {
  document.getElementById('reader').innerHTML =
    '<div style="padding:20px;text-align:center;color:var(--text2);font-size:13px">No se pudo acceder a la cámara.<br>Usá el ingreso manual de abajo.</div>';
});
</script>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

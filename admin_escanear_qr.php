<?php
require_once 'auth.php';
requerir_rol(['administrador']);
require_once 'dbconn.php';
$pagina_activa = 'qr';

$mensaje = '';
$tipo = '';
$alumno_encontrado = null;
$clase_encontrada = null;

// Respuesta para el escaneo sin recargar la página (la cámara queda prendida)
if (isset($_POST['qr_data']) && isset($_POST['ajax'])) {
    procesar();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'tipo'    => $tipo,
        'mensaje' => $mensaje,
        'alumno'  => $alumno_encontrado ? $alumno_encontrado['nombre'] . ' ' . $alumno_encontrado['apellido'] : '',
        'clase'   => $clase_encontrada ? $clase_encontrada['nombre'] . ' · ' . date('d/m', strtotime($clase_encontrada['fecha'])) . ' · ' . substr($clase_encontrada['hora_inicio'], 0, 5) : '',
    ));
    exit();
}
if (isset($_POST['qr_data'])) procesar();

function procesar()
{
    global $conex, $mensaje, $tipo, $alumno_encontrado, $clase_encontrada;
    if (isset($_POST['qr_data']) && !empty($_POST['qr_data'])) {
        $qr_raw = trim($_POST['qr_data']);
        $parts = explode(':', $qr_raw);

        if (count($parts) === 3) {
            // Nuevo formato: alumno_id:clase_id:token
            $alumno_id = (int)$parts[0];
            $clase_id  = (int)$parts[1];
            $token     = $parts[2];
            $token_esperado = md5($alumno_id . ':' . $clase_id . 'pulso2025');

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
            $token_esperado = md5($alumno_id . 'pulso2025');
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
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= gym_nombre() ?> - Lector QR</title>
  <link rel="stylesheet" href="styles.css">
  <script src="html5-qrcode.min.js"></script>
  <link rel="icon" type="image/png" href="icono_pulso.png">
  <style>
    #reader video { border-radius: 12px; }
    #resultado { display:none; text-align:center; padding:18px; margin-bottom:18px; }
    .qr-btns { display:grid; grid-template-columns:1fr 1fr; gap:8px; max-width:400px; margin:0 auto 14px; }
  </style>
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title">Lector QR</div>
  <p style="font-size:13px;color:var(--text2);margin-bottom:16px">Escaneá el QR del alumno para registrar su asistencia. La cámara queda prendida para escanear uno atrás del otro.</p>

  <div id="resultado" class="alert">
    <div id="r-alumno" style="font-size:20px;font-weight:900;margin-bottom:4px"></div>
    <div id="r-clase" style="font-size:13px;margin-bottom:8px;opacity:.8"></div>
    <div id="r-mensaje" style="font-size:16px;font-weight:700"></div>
  </div>
  <?php if ($mensaje): ?>
  <script>window.RESULTADO_INICIAL = <?= json_encode(array('tipo' => $tipo, 'mensaje' => $mensaje,
      'alumno' => $alumno_encontrado ? $alumno_encontrado['nombre'] . ' ' . $alumno_encontrado['apellido'] : '',
      'clase' => $clase_encontrada ? $clase_encontrada['nombre'] : '')) ?>;</script>
  <?php endif; ?>

  <div id="reader" style="width:100%;max-width:400px;margin:0 auto 14px;border-radius:14px;overflow:hidden;border:2px solid var(--border);background:#000;min-height:60px"></div>

  <div id="cam-error" class="alert alert-danger" style="display:none;max-width:400px;margin:0 auto 14px;font-size:13px;line-height:1.5"></div>

  <div class="qr-btns">
    <button id="btn-camara" class="btn btn-blue" type="button">📷 Activar cámara</button>
    <label class="btn btn-outline" style="text-align:center;cursor:pointer;border-color:var(--blue);color:var(--blue)">
      📸 Sacar foto al QR
      <input id="foto-qr" type="file" accept="image/*" capture="environment" style="display:none">
    </label>
  </div>

  <div class="card-tp" style="margin-top:16px;max-width:400px;margin-left:auto;margin-right:auto">
    <div style="font-size:12px;color:var(--text2);margin-bottom:10px">Ingreso manual (si nada de lo anterior funciona):</div>
    <form id="form-manual" method="post">
      <div class="field"><input type="text" name="qr_data" id="manual" placeholder="Pegá el código del QR (ej: 12:45:ab12…)"></div>
      <button type="submit" class="btn btn-blue">Registrar</button>
    </form>
  </div>

  <a href="acceso_administrador.php" class="btn btn-outline" style="display:block;text-align:center;text-decoration:none;margin:12px auto 0;max-width:400px">Volver al panel</a>
</div>

<script>
(function () {
  const btnCam = document.getElementById('btn-camara');
  const camErr = document.getElementById('cam-error');
  let lector = null, camaraPrendida = false, ocupado = false;

  function mostrarResultado(r) {
    const box = document.getElementById('resultado');
    box.className = 'alert alert-' + (r.tipo || 'info');
    box.style.display = 'block';
    document.getElementById('r-alumno').textContent = r.alumno || '';
    document.getElementById('r-clase').textContent = r.clase || '';
    document.getElementById('r-mensaje').textContent = r.mensaje || '';
    if (navigator.vibrate) navigator.vibrate(r.tipo === 'success' ? 120 : [80, 60, 80]);
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
  if (window.RESULTADO_INICIAL) mostrarResultado(window.RESULTADO_INICIAL);

  function errorCamara(texto) {
    camErr.innerHTML = texto;
    camErr.style.display = 'block';
  }

  // Manda el código al servidor sin recargar la página
  let ultimoCodigo = '', ultimoMomento = 0;
  function registrar(codigo, manual) {
    if (ocupado) return;
    // El mismo QR frente a la cámara: no lo volvemos a mandar por 8 segundos
    if (!manual && codigo === ultimoCodigo && Date.now() - ultimoMomento < 8000) return;
    ultimoCodigo = codigo; ultimoMomento = Date.now();
    ocupado = true;
    const datos = new FormData();
    datos.append('qr_data', codigo.trim());
    datos.append('ajax', '1');
    fetch('admin_escanear_qr.php', { method: 'POST', body: datos, credentials: 'same-origin' })
      .then(r => r.json())
      .then(mostrarResultado)
      .catch(() => mostrarResultado({ tipo: 'danger', mensaje: 'No se pudo conectar con el servidor. Revisá internet.' }))
      .finally(() => setTimeout(() => { ocupado = false; }, 2500)); // pausa para no leer el mismo QR mil veces
  }

  function explicarError(e) {
    const n = (e && (e.name || e)) + '';
    if (!window.isSecureContext) return 'La cámara solo funciona con <b>https://</b>. Entrá a la página con https.';
    if (n.includes('NotAllowed') || n.includes('Permission'))
      return '<b>El navegador bloqueó la cámara.</b><br>Tocá el 🔒 candado al lado de la dirección → <b>Permisos</b> → <b>Cámara: Permitir</b>, y recargá la página.<br>Mientras tanto podés usar <b>📸 Sacar foto al QR</b>.';
    if (n.includes('NotFound') || n.includes('Overconstrained')) return 'No se encontró ninguna cámara en este dispositivo. Usá <b>📸 Sacar foto al QR</b>.';
    if (n.includes('NotReadable') || n.includes('TrackStart')) return 'La cámara la está usando otra app. Cerrala y tocá <b>Activar cámara</b> de nuevo.';
    return 'No se pudo abrir la cámara (' + n + '). Probá con <b>📸 Sacar foto al QR</b>.';
  }

  async function prenderCamara() {
    camErr.style.display = 'none';
    if (typeof Html5Qrcode === 'undefined') { errorCamara('No cargó el lector de QR. Recargá la página.'); return; }
    if (camaraPrendida) { await lector.stop(); camaraPrendida = false; btnCam.textContent = '📷 Activar cámara'; return; }
    lector = lector || new Html5Qrcode('reader');
    const config = { fps: 10, qrbox: (w, h) => { const t = Math.floor(Math.min(w, h) * 0.7); return { width: t, height: t }; } };
    btnCam.textContent = 'Abriendo cámara…';
    try {
      await lector.start({ facingMode: 'environment' }, config, txt => registrar(txt), () => {});
    } catch (e1) {
      // Plan B: elegir a mano la cámara trasera de la lista
      try {
        const camaras = await Html5Qrcode.getCameras();
        if (!camaras.length) throw e1;
        const trasera = camaras.find(c => /back|rear|trasera|environment/i.test(c.label)) || camaras[camaras.length - 1];
        await lector.start(trasera.id, config, txt => registrar(txt), () => {});
      } catch (e2) {
        btnCam.textContent = '📷 Activar cámara';
        errorCamara(explicarError(e2));
        return;
      }
    }
    camaraPrendida = true;
    btnCam.textContent = '⏹️ Apagar cámara';
  }
  btnCam.addEventListener('click', prenderCamara);

  // Si ya diste permiso antes, la cámara arranca sola
  if (navigator.permissions && navigator.permissions.query) {
    navigator.permissions.query({ name: 'camera' }).then(p => { if (p.state === 'granted') prenderCamara(); }).catch(() => {});
  }

  // Plan C: sacar una foto con la cámara del celular y leer el QR de la foto
  document.getElementById('foto-qr').addEventListener('change', async function () {
    if (!this.files.length) return;
    const archivo = this.files[0];
    this.value = '';
    try {
      const temp = new Html5Qrcode('reader-temp');
      const codigo = await temp.scanFile(archivo, false);
      registrar(codigo, true);
    } catch (e) {
      mostrarResultado({ tipo: 'danger', mensaje: 'No se encontró un QR en la foto. Probá de nuevo, más cerca y con buena luz.' });
    }
  });

  // Ingreso manual también sin recargar
  document.getElementById('form-manual').addEventListener('submit', function (ev) {
    ev.preventDefault();
    const v = document.getElementById('manual').value;
    if (v.trim()) { ocupado = false; registrar(v, true); document.getElementById('manual').value = ''; }
  });
})();
</script>
<div id="reader-temp" style="display:none"></div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

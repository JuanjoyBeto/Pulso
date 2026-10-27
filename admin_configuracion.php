<?php
require_once 'auth.php';
requerir_rol(['administrador']);
require_once 'dbconn.php';
$pagina_activa = 'config';

$aviso = array();   // mensajes por sección: ['gym'|'packs'|'wa' => [tipo, texto]]

// ---------- 1) Datos del gimnasio ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['seccion'] ?? '') === 'gym') {
    $nombre    = trim($_POST['nombre_gimnasio'] ?? '');
    $email     = trim($_POST['email_contacto'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $logo      = cfg('logo', 'logo_pulso.png');
    $error     = '';

    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $img = @getimagesize($_FILES['logo']['tmp_name']);
        $ext = array('image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp');
        if (!$img || !isset($ext[$img['mime']])) {
            $error = 'El logo tiene que ser una imagen PNG, JPG o WEBP.';
        } elseif ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
            $error = 'El logo pesa más de 2 MB.';
        } else {
            if (!is_dir('uploads')) mkdir('uploads', 0755);
            $destino = 'uploads/logo_' . time() . '.' . $ext[$img['mime']];
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $destino)) $logo = $destino;
            else $error = 'No se pudo guardar el logo.';
        }
    }
    if ($nombre === '') $error = 'El nombre del gimnasio es obligatorio.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'El email de contacto no es válido.';

    if ($error === '') {
        $stmt = mysqli_prepare($conex, "UPDATE configuracion SET nombre_gimnasio=?, logo=?, email_contacto=?, telefono=?, direccion=? WHERE id=1");
        mysqli_stmt_bind_param($stmt, 'sssss', $nombre, $logo, $email, $telefono, $direccion);
        mysqli_stmt_execute($stmt);
        header('Location: admin_configuracion.php?ok=gym#gym');
        exit();
    }
    $aviso['gym'] = array('danger', $error);
}

// ---------- 2) Packs y precios ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['seccion'] ?? '') === 'packs') {
    $dias = max(1, min(365, (int)($_POST['dias_vencimiento'] ?? 31)));
    mysqli_query($conex, "UPDATE configuracion SET dias_vencimiento=$dias WHERE id=1");
    $hc = max(0, min(168, (int)($_POST['horas_cancelacion'] ?? 20)));
    @mysqli_query($conex, "UPDATE configuracion SET horas_cancelacion=$hc WHERE id=1");
    foreach (($_POST['pack'] ?? array()) as $id => $p) {
        $id  = (int)$id;
        $nom = mysqli_real_escape_string($conex, trim($p['nombre'] ?? ''));
        $cr  = max(1, (int)($p['creditos'] ?? 1));
        $pr  = max(0, (float)str_replace(array('.', ','), array('', '.'), $p['precio'] ?? '0'));
        $act = isset($p['activo']) ? 1 : 0;
        if ($nom !== '') mysqli_query($conex, "UPDATE packs SET nombre='$nom', creditos=$cr, precio=$pr, activo=$act WHERE id=$id");
    }
    $n = $_POST['nuevo'] ?? array();
    if (trim($n['nombre'] ?? '') !== '' && (int)($n['creditos'] ?? 0) > 0) {
        $nom = mysqli_real_escape_string($conex, trim($n['nombre']));
        $cr  = (int)$n['creditos'];
        $pr  = max(0, (float)str_replace(array('.', ','), array('', '.'), $n['precio'] ?? '0'));
        mysqli_query($conex, "INSERT INTO packs (nombre, creditos, precio) VALUES ('$nom', $cr, $pr)");
    }
    header('Location: admin_configuracion.php?ok=packs#packs');
    exit();
}

// ---------- 3) WhatsApp y avisos ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['seccion'] ?? '') === 'wa') {
    $acc = $_POST['accion'] ?? '';
    if ($acc === 'guardar') {
        $act = isset($_POST['whatsapp_activo']) ? 1 : 0;
        $dav = max(1, min(15, (int)($_POST['dias_aviso_vencimiento'] ?? 3)));
        $cav = max(0, min(20, (int)($_POST['creditos_aviso'] ?? 3)));
        mysqli_query($conex, "UPDATE configuracion SET whatsapp_activo=$act, dias_aviso_vencimiento=$dav WHERE id=1");
        @mysqli_query($conex, "UPDATE configuracion SET creditos_aviso=$cav WHERE id=1");
        header('Location: admin_configuracion.php?ok=wa#whatsapp');
        exit();
    }
    if ($acc === 'simular') {
        $aid = (int)($_POST['alumno_id'] ?? 0);
        $tipo = $_POST['tipo'] ?? '';
        $al = mysqli_fetch_assoc(mysqli_query($conex, "SELECT nombre, apellido FROM registronuevo WHERE id=$aid"));
        if (!$al) {
            $aviso['wa'] = array('danger', 'Elegí un alumno para mandarle el aviso.');
        } else {
            list($ok, $det) = simular_aviso($tipo, $aid);
            $quien = htmlspecialchars($al['nombre'] . ' ' . $al['apellido']);
            if ($ok === true)      $aviso['wa'] = array('success', "✅ Aviso enviado a <b>$quien</b> por WhatsApp y en la app.");
            elseif ($ok === null)  $aviso['wa'] = array('info', "📱 Aviso enviado a <b>$quien</b> en la app (no por WhatsApp: " . htmlspecialchars($det) . ').');
            else                   $aviso['wa'] = array('danger', "Se mandó en la app, pero el WhatsApp falló: " . htmlspecialchars($det));
        }
    }
    if ($acc === 'probar') {
        $tel = trim($_POST['tel_prueba'] ?? '');
        if ($tel === '') {
            $aviso['wa'] = array('danger', 'Escribí un celular para mandar la prueba.');
        } elseif (!wa_claves_ok()) {
            $aviso['wa'] = array('danger', 'El servicio de WhatsApp no está configurado (ver "Detalles técnicos").');
        } else {
            list($ok, $det) = wa_enviar($tel, '*' . cfg('nombre_gimnasio', 'Pulso') . "*\n✅ ¡Funciona! Este es un mensaje de prueba del sistema.", (int)$_SESSION['usuario_id']);
            $aviso['wa'] = $ok ? array('success', '✅ Mensaje de prueba enviado a ' . htmlspecialchars($tel) . '.')
                               : array('danger', 'No se pudo enviar: ' . htmlspecialchars($det));
        }
    }
    if ($acc === 'correr') {
        $r = correr_tareas_diarias();
        $aviso['wa'] = array('success', "✅ Avisos del día enviados: {$r['vencimientos']} de vencimiento y {$r['recordatorios']} recordatorios de clase.");
    }
}

$ok = $_GET['ok'] ?? '';
if ($ok && empty($aviso[$ok])) $aviso[$ok] = array('success', '✅ Cambios guardados.');
function v($c) { return htmlspecialchars(cfg($c)); }
function aviso_de($sec) {
    global $aviso;
    if (empty($aviso[$sec])) return;
    echo '<div class="alert alert-' . $aviso[$sec][0] . '">' . $aviso[$sec][1] . '</div>';
}

$wa_activo  = cfg('whatsapp_activo', '0') == '1';
$wa_listo   = wa_claves_ok();
$packs      = mysqli_query($conex, "SELECT * FROM packs ORDER BY activo DESC, creditos");
$alumnos    = mysqli_query($conex, "SELECT id, nombre, apellido, telefono, wa_avisos FROM registronuevo WHERE rol='alumno' ORDER BY (telefono IS NULL OR telefono=''), nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= gym_nombre() ?> - Configuración</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" type="image/png" href="icono_pulso.png">
  <style>
    .cfg-sec { margin-bottom: 30px; scroll-margin-top: 80px; }
    .cfg-head { display:flex; align-items:center; gap:12px; margin-bottom:12px; }
    .cfg-ico { width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
    .cfg-head h2 { font-size:17px; font-weight:800; color:#fff; margin:0; }
    .cfg-head p { font-size:12px; color:var(--text2); margin:2px 0 0; }
    .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:0 14px; }
    @media (max-width: 640px) { .grid2 { grid-template-columns:1fr; } }
    .hint { font-size:11px; color:var(--text3); margin-top:-6px; margin-bottom:12px; }
    .sub { font-size:12px; font-weight:700; color:var(--text2); text-transform:uppercase; letter-spacing:.06em; margin:4px 0 10px; }

    /* interruptor */
    .switch { position:relative; display:inline-block; width:46px; height:26px; flex-shrink:0; }
    .switch input { opacity:0; width:0; height:0; }
    .switch span { position:absolute; inset:0; background:var(--bg3); border:1px solid var(--border); border-radius:26px; transition:.2s; cursor:pointer; }
    .switch span:before { content:''; position:absolute; width:20px; height:20px; left:2px; top:2px; background:#888; border-radius:50%; transition:.2s; }
    .switch input:checked + span { background:var(--green); border-color:var(--green); }
    .switch input:checked + span:before { transform:translateX(20px); background:#fff; }

    /* packs */
    .pack { display:grid; grid-template-columns: 1fr 110px 150px auto; gap:10px; align-items:end; padding:14px; border:1px solid var(--border); border-radius:14px; margin-bottom:10px; background:var(--bg2); }
    .pack.off { opacity:.5; }
    .pack .field { margin:0; }
    .pack .xclase { font-size:11px; color:var(--text3); margin-top:4px; }
    .pack .act { display:flex; flex-direction:column; align-items:center; gap:4px; font-size:11px; color:var(--text2); padding-bottom:6px; }
    .peso { position:relative; } .peso input { padding-left:24px !important; } .peso:before { content:'$'; position:absolute; left:11px; bottom:12px; color:var(--text2); font-size:14px; }
    @media (max-width: 640px) { .pack { grid-template-columns:1fr 1fr; } .pack .nom { grid-column:1 / -1; } }
    details.nuevo summary { cursor:pointer; color:var(--blue); font-size:13px; font-weight:700; padding:6px 0; list-style:none; }

    /* whatsapp */
    .estado { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:12px; font-size:13px; margin-bottom:16px; }
    .estado .dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
    .fila { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 0; border-bottom:1px solid var(--border); }
    .fila:last-of-type { border-bottom:none; }
    .fila b { color:#fff; font-size:14px; } .fila small { display:block; color:var(--text2); font-size:12px; margin-top:2px; }
    .num { width:70px !important; text-align:center; }
    .auto { display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:8px; }
    .auto div { background:var(--bg2); border:1px solid var(--border); border-radius:12px; padding:10px 12px; font-size:12px; color:var(--text2); line-height:1.4; }
    .auto b { display:block; color:#fff; font-size:13px; margin-bottom:2px; }
    .sim { display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:8px; }
    .sim button { background:var(--bg2); border:1px solid var(--border); color:#fff; border-radius:12px; padding:12px 10px; font-family:inherit; font-size:13px; font-weight:700; cursor:pointer; text-align:left; transition:.15s; }
    .sim button:hover { border-color:#25d366; background:rgba(37,211,102,.08); }
    .sim button small { display:block; font-weight:400; color:var(--text2); font-size:11px; margin-top:3px; }
    details.tec summary { cursor:pointer; color:var(--text2); font-size:13px; padding:8px 0; }
    .log { font-size:11px; padding:6px 0; border-top:1px solid var(--border); display:flex; gap:8px; }
  </style>
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title">⚙️ Configuración</div>
  <p style="color:var(--text2);font-size:13px;margin:-6px 0 24px">Todo lo que se cambia acá se aplica al instante en el sistema, sin tocar código.</p>

  <!-- ===================== 1) DATOS DEL GIMNASIO ===================== -->
  <section class="cfg-sec" id="gym">
    <div class="cfg-head">
      <div class="cfg-ico" style="background:rgba(59,158,255,.15)">🏋️</div>
      <div><h2>Datos del gimnasio</h2><p>Nombre, logo y cómo te contactan los alumnos</p></div>
    </div>
    <?php aviso_de('gym'); ?>
    <form method="post" action="admin_configuracion.php#gym" enctype="multipart/form-data" class="card-tp">
      <input type="hidden" name="seccion" value="gym">
      <div style="display:flex;align-items:center;gap:16px;margin-bottom:18px;flex-wrap:wrap">
        <img src="<?= gym_logo() ?>" alt="Logo" id="logo-prev" style="height:72px;width:auto;max-width:150px;object-fit:contain;background:var(--bg3);border-radius:14px;padding:8px">
        <div style="flex:1;min-width:200px">
          <div class="field" style="margin-bottom:6px">
            <label>Nombre del gimnasio *</label>
            <input type="text" name="nombre_gimnasio" value="<?= v('nombre_gimnasio') ?>" required>
          </div>
          <label class="btn btn-outline" style="display:inline-block;width:auto;padding:8px 14px;font-size:12px;cursor:pointer">
            🖼️ Cambiar logo <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" style="display:none"
              onchange="if(this.files[0]){document.getElementById('logo-prev').src=URL.createObjectURL(this.files[0])}">
          </label>
          <span style="font-size:11px;color:var(--text3);margin-left:6px">PNG, JPG o WEBP · máx 2 MB</span>
        </div>
      </div>

      <div class="sub">Contacto</div>
      <p class="hint" style="margin-top:-4px">Se muestra a los alumnos en su inicio ("Tu gimnasio"), en la pantalla de pago y al pie de los mails.</p>
      <div class="grid2">
        <div class="field"><label>📞 Teléfono / WhatsApp</label><input type="tel" name="telefono" value="<?= v('telefono') ?>" placeholder="+54 9 11 2345 6789"></div>
        <div class="field"><label>✉️ Email</label><input type="email" name="email_contacto" value="<?= v('email_contacto') ?>" placeholder="hola@migimnasio.com"></div>
      </div>
      <div class="field"><label>📍 Dirección</label><input type="text" name="direccion" value="<?= v('direccion') ?>" placeholder="Av. Siempre Viva 742, Caseros"></div>
      <button class="btn btn-blue">Guardar datos</button>
    </form>
  </section>

  <!-- ===================== 2) PACKS Y PRECIOS ===================== -->
  <section class="cfg-sec" id="packs">
    <div class="cfg-head">
      <div class="cfg-ico" style="background:rgba(74,222,128,.15)">💳</div>
      <div><h2>Packs y precios</h2><p>Lo que ven los alumnos cuando compran créditos</p></div>
    </div>
    <?php aviso_de('packs'); ?>
    <form method="post" action="admin_configuracion.php#packs" class="card-tp">
      <input type="hidden" name="seccion" value="packs">

      <?php while ($pk = mysqli_fetch_assoc($packs)): $i = (int)$pk['id']; $xc = $pk['creditos'] > 0 ? $pk['precio'] / $pk['creditos'] : 0; ?>
      <div class="pack <?= $pk['activo'] ? '' : 'off' ?>">
        <div class="field nom"><label>Nombre</label><input type="text" name="pack[<?= $i ?>][nombre]" value="<?= htmlspecialchars($pk['nombre']) ?>"></div>
        <div class="field"><label>Clases</label><input type="number" name="pack[<?= $i ?>][creditos]" min="1" value="<?= (int)$pk['creditos'] ?>"></div>
        <div class="field">
          <label>Precio</label>
          <div class="peso"><input type="text" inputmode="numeric" name="pack[<?= $i ?>][precio]" value="<?= number_format($pk['precio'], 0, ',', '.') ?>"></div>
          <div class="xclase">$<?= number_format($xc, 0, ',', '.') ?> por clase</div>
        </div>
        <div class="act">
          <label class="switch"><input type="checkbox" name="pack[<?= $i ?>][activo]" <?= $pk['activo'] ? 'checked' : '' ?> onchange="this.closest('.pack').classList.toggle('off', !this.checked)"><span></span></label>
          <?= $pk['activo'] ? 'Visible' : 'Oculto' ?>
        </div>
      </div>
      <?php endwhile; ?>

      <details class="nuevo">
        <summary>➕ Agregar un pack nuevo</summary>
        <div class="pack" style="margin-top:8px">
          <div class="field nom"><label>Nombre</label><input type="text" name="nuevo[nombre]" placeholder="Ej: Pase libre"></div>
          <div class="field"><label>Clases</label><input type="number" name="nuevo[creditos]" min="1" placeholder="30"></div>
          <div class="field"><label>Precio</label><div class="peso"><input type="text" inputmode="numeric" name="nuevo[precio]" placeholder="40.000"></div></div>
          <div></div>
        </div>
      </details>

      <div class="fila" style="margin-top:10px">
        <div><b>⏳ Los créditos vencen a los</b><small>Se cuentan desde el día de la compra</small></div>
        <div style="display:flex;align-items:center;gap:8px"><div class="field" style="margin:0"><input class="num" type="number" name="dias_vencimiento" min="1" max="365" value="<?= (int)cfg('dias_vencimiento', 31) ?>"></div><span style="color:var(--text2);font-size:13px">días</span></div>
      </div>
      <div class="fila">
        <div><b>↩️ Devolver el crédito si se da de baja con</b><small>Horas de anticipación. Con menos, puede darse de baja pero pierde el crédito</small></div>
        <div style="display:flex;align-items:center;gap:8px"><div class="field" style="margin:0"><input class="num" type="number" name="horas_cancelacion" min="0" max="168" value="<?= (int)cfg('horas_cancelacion', 20) ?>"></div><span style="color:var(--text2);font-size:13px">horas</span></div>
      </div>
      <button class="btn btn-blue" style="margin-top:12px">Guardar packs</button>
    </form>
  </section>

  <!-- ===================== 3) WHATSAPP Y AVISOS ===================== -->
  <section class="cfg-sec" id="whatsapp">
    <div class="cfg-head">
      <div class="cfg-ico" style="background:rgba(37,211,102,.15)">💬</div>
      <div><h2>WhatsApp y avisos automáticos</h2><p>El sistema avisa solo a cada alumno, en la app y por WhatsApp</p></div>
    </div>
    <?php aviso_de('wa'); ?>

    <?php if ($wa_listo && $wa_activo): ?>
      <div class="estado" style="background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.35);color:#25d366"><span class="dot" style="background:#25d366"></span><b>WhatsApp conectado y activado.</b>&nbsp;Los avisos salen solos.</div>
    <?php elseif ($wa_listo): ?>
      <div class="estado" style="background:rgba(250,204,21,.08);border:1px solid rgba(250,204,21,.35);color:#facc15"><span class="dot" style="background:#facc15"></span><b>WhatsApp apagado.</b>&nbsp;Los avisos llegan solo a la app. Activalo abajo.</div>
    <?php else: ?>
      <div class="estado" style="background:rgba(248,113,113,.08);border:1px solid rgba(248,113,113,.35);color:var(--red)"><span class="dot" style="background:var(--red)"></span><b>WhatsApp sin configurar.</b>&nbsp;Los avisos llegan solo a la app.</div>
    <?php endif; ?>

    <!-- Ajustes -->
    <form method="post" action="admin_configuracion.php#whatsapp" class="card-tp" style="margin-bottom:14px">
      <input type="hidden" name="seccion" value="wa"><input type="hidden" name="accion" value="guardar">
      <div class="fila">
        <div><b>Mandar avisos por WhatsApp</b><small>Si está apagado, los avisos igual llegan a la campanita de la app</small></div>
        <label class="switch"><input type="checkbox" name="whatsapp_activo" <?= $wa_activo ? 'checked' : '' ?>><span></span></label>
      </div>
      <div class="fila">
        <div><b>Avisar que vencen los créditos</b><small>Días antes del vencimiento</small></div>
        <div class="field" style="margin:0"><input class="num" type="number" name="dias_aviso_vencimiento" min="1" max="15" value="<?= (int)cfg('dias_aviso_vencimiento', 3) ?>"></div>
      </div>
      <div class="fila">
        <div><b>Avisar que le quedan pocos créditos</b><small>Cuando le quedan esta cantidad</small></div>
        <div class="field" style="margin:0"><input class="num" type="number" name="creditos_aviso" min="0" max="20" value="<?= (int)cfg('creditos_aviso', 3) ?>"></div>
      </div>
      <button class="btn btn-blue" style="margin-top:12px">Guardar</button>
    </form>

    <!-- Qué se avisa -->
    <div class="card-tp" style="margin-bottom:14px">
      <div class="sub">Lo que el sistema avisa solo</div>
      <div class="auto">
        <div><b>📅 Clase de mañana</b>Un día antes de cada clase a la que está anotado</div>
        <div><b>⏰ Créditos por vencer</b><?= (int)cfg('dias_aviso_vencimiento', 3) ?> días antes del vencimiento</div>
        <div><b>⚠️ Pocos créditos</b>Cuando le quedan <?= (int)cfg('creditos_aviso', 3) ?></div>
        <div><b>🔴 Sin créditos</b>Cuando usa el último</div>
        <div><b>✅ Pago aprobado</b>Apenas MercadoPago confirma el pago</div>
        <div><b>🩺 Apto médico</b>Cuando lo aprobás o lo rechazás</div>
        <div><b>📣 Avisos del gimnasio</b>Los que mandás desde Panel Admin → Avisos</div>
      </div>
    </div>

    <!-- Simular -->
    <form method="post" action="admin_configuracion.php#whatsapp" class="card-tp" style="margin-bottom:14px">
      <input type="hidden" name="seccion" value="wa"><input type="hidden" name="accion" value="simular">
      <div class="sub">Simular un aviso</div>
      <p style="font-size:12px;color:var(--text2);margin:-4px 0 12px">Estos avisos salen solos. Con estos botones podés mandar uno ahora para ver cómo le llega al alumno (por ejemplo, para mostrarlo en una presentación). No cambia sus créditos ni sus clases.</p>
      <div class="field">
        <label>Alumno</label>
        <select name="alumno_id" required>
          <option value="">Elegí un alumno…</option>
          <?php while ($a = mysqli_fetch_assoc($alumnos)): ?>
            <option value="<?= $a['id'] ?>" <?= (isset($_POST['alumno_id']) && $_POST['alumno_id'] == $a['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($a['nombre'] . ' ' . $a['apellido']) ?><?= !empty($a['telefono']) && $a['wa_avisos'] ? '  📱 ' . htmlspecialchars($a['telefono']) : '  (sin WhatsApp)' ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="sim">
        <button name="tipo" value="clase">📅 Clase de mañana<small>Recordatorio de su próxima clase</small></button>
        <button name="tipo" value="vencimiento">⏰ Créditos por vencer<small>"Vencen en <?= (int)cfg('dias_aviso_vencimiento', 3) ?> días"</small></button>
        <button name="tipo" value="pocos">⚠️ Pocos créditos<small>"Te quedan solo <?= (int)cfg('creditos_aviso', 3) ?>"</small></button>
        <button name="tipo" value="sin">🔴 Sin créditos<small>"Te quedaste sin créditos"</small></button>
        <button name="tipo" value="pago">✅ Pago aprobado<small>Como si pagara con MercadoPago</small></button>
        <button name="tipo" value="apto">🩺 Apto aprobado<small>Como si validaras su apto</small></button>
      </div>
    </form>

    <!-- Técnico -->
    <div class="card-tp">
      <details class="tec">
        <summary>🔧 Detalles técnicos (para el técnico que instala el sistema)</summary>
        <div style="font-size:12px;color:var(--text2);margin:8px 0 14px;line-height:1.8">
          Servicio: <b style="color:#fff"><?= array('greenapi' => 'Green-API', 'twilio' => 'Twilio', 'meta' => 'Meta (API oficial)')[wa_proveedor()] ?></b> ·
          <?php foreach (wa_claves() as $k): ?><?= env_valor($k) !== '' ? '✅' : '❌' ?> <?= $k ?> · <?php endforeach; ?>
          Último aviso diario: <b style="color:#fff"><?= htmlspecialchars(@file_get_contents(__DIR__ . '/uploads/.ultima_tarea_diaria') ?: 'nunca') ?></b>
        </div>
        <form method="post" action="admin_configuracion.php#whatsapp" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
          <input type="hidden" name="seccion" value="wa"><input type="hidden" name="accion" value="probar">
          <div class="field" style="flex:1;min-width:180px;margin:0"><input type="tel" name="tel_prueba" placeholder="Celular para mensaje de prueba"></div>
          <button class="btn btn-outline" style="width:auto;padding:10px 16px;border-color:#25d366;color:#25d366">📲 Mandar prueba</button>
        </form>
        <form method="post" action="admin_configuracion.php#whatsapp" style="margin-bottom:14px">
          <input type="hidden" name="seccion" value="wa"><input type="hidden" name="accion" value="correr">
          <button class="btn btn-outline" style="border-color:var(--blue);color:var(--blue)">⏰ Correr ahora los avisos reales del día</button>
        </form>
        <div style="font-size:12px;color:var(--text2);margin-bottom:6px">Últimos WhatsApp enviados</div>
        <?php $lg = @mysqli_query($conex, "SELECT * FROM whatsapp_log ORDER BY id DESC LIMIT 10");
        if (!$lg || mysqli_num_rows($lg) === 0): ?>
          <div style="font-size:12px;color:var(--text3)">Todavía no se mandó ninguno.</div>
        <?php else: while ($l = mysqli_fetch_assoc($lg)): ?>
          <div class="log">
            <span><?= $l['ok'] ? '✅' : '❌' ?></span>
            <span style="color:var(--text2);white-space:nowrap"><?= date('d/m H:i', strtotime($l['fecha'])) ?> · +<?= htmlspecialchars($l['telefono']) ?></span>
            <span style="color:<?= $l['ok'] ? 'var(--text2)' : 'var(--red)' ?>"><?= htmlspecialchars($l['ok'] ? mb_substr(str_replace("\n", ' ', $l['mensaje']), 0, 80) : $l['detalle']) ?></span>
          </div>
        <?php endwhile; endif; ?>
      </details>
    </div>
  </section>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

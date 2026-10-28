<?php
require_once 'auth.php';
requerir_rol(['administrador']);
require_once 'dbconn.php';
$pagina_activa = 'admin';

$total_alumnos = mysqli_fetch_assoc(mysqli_query($conex, "SELECT COUNT(*) as n FROM registronuevo WHERE rol='alumno'"))['n'];
$sin_creditos  = mysqli_fetch_assoc(mysqli_query($conex, "SELECT COUNT(*) as n FROM registronuevo WHERE rol='alumno' AND (creditos IS NULL OR creditos=0)"))['n'];
$clases_semana = mysqli_fetch_assoc(mysqli_query($conex, "SELECT COUNT(*) as n FROM clases WHERE fecha >= CURDATE() AND fecha <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)"))['n'];
$asist_mes     = mysqli_fetch_assoc(mysqli_query($conex, "SELECT COUNT(*) as n FROM asistencias WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())"))['n'];

$res_alumnos = mysqli_query($conex, "SELECT * FROM registronuevo WHERE rol='alumno' ORDER BY apellido ASC");
$res_clases  = mysqli_query($conex, "SELECT c.*, (SELECT COUNT(*) FROM inscripciones WHERE clase_id=c.id) as inscriptos FROM clases c WHERE c.fecha >= CURDATE() ORDER BY c.fecha ASC, c.hora_inicio ASC LIMIT 20");

// Para asistencia: todas las clases con inscriptos
$res_clases_asist = mysqli_query($conex, "SELECT c.* FROM clases c 
    WHERE c.fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY c.fecha DESC, c.hora_inicio DESC LIMIT 10");

$dias_es = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

// Clase seleccionada para ver asistencia
$clase_asist_id = (int)($_GET['clase_asist'] ?? 0);
if (!$clase_asist_id) {
    // Por defecto la más próxima
    $res_default = mysqli_query($conex, "SELECT id FROM clases WHERE fecha >= CURDATE() ORDER BY fecha ASC LIMIT 1");
    $def = mysqli_fetch_assoc($res_default);
    $clase_asist_id = $def ? (int)$def['id'] : 0;
}

$res_asist = null;
$clase_asist_info = null;
if ($clase_asist_id) {
    $res_info = mysqli_query($conex, "SELECT * FROM clases WHERE id=$clase_asist_id LIMIT 1");
    $clase_asist_info = mysqli_fetch_assoc($res_info);
    $res_asist = mysqli_query($conex, "SELECT r.nombre, r.apellido, a.presente
        FROM inscripciones i
        JOIN registronuevo r ON i.alumno_id = r.id
        LEFT JOIN asistencias a ON a.alumno_id=i.alumno_id AND a.clase_id=$clase_asist_id
        WHERE i.clase_id = $clase_asist_id
        ORDER BY r.apellido ASC");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= gym_nombre() ?> - Admin</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .admin-tabs-mobile { display:flex;overflow-x:auto;gap:8px;padding:0 0 12px;margin-bottom:16px;scrollbar-width:none; }
    .admin-tabs-mobile::-webkit-scrollbar { display:none; }
    .admin-tab-btn { flex-shrink:0;padding:9px 18px;border-radius:20px;font-size:13px;font-weight:600;cursor:pointer;color:var(--text2);border:1px solid var(--border);background:transparent;font-family:inherit;transition:all .2s;white-space:nowrap; }
    .admin-tab-btn.active { background:var(--blue);color:#fff;border-color:var(--blue); }
    .admin-panel { display:none; }
    .admin-panel.active { display:block; }
    .alumno-row { background:var(--card);border:1px solid var(--border);border-radius:14px;padding:14px 16px;margin-bottom:10px;display:flex;align-items:center;gap:12px;cursor:pointer;transition:border-color .2s; }
    .alumno-row:active { border-color:var(--blue); }
    .alumno-av { width:42px;height:42px;border-radius:50%;background:var(--bg3);border:2px solid var(--blue);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800;color:var(--blue);flex-shrink:0; }
    .alumno-info { flex:1;min-width:0; }
    .alumno-name { font-size:14px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
    .alumno-sub { font-size:11px;color:var(--text2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
    .badge { font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;background:rgba(59,158,255,0.15);color:var(--blue);white-space:nowrap; }
    .badge-red { background:rgba(248,113,113,0.15);color:var(--red); }
    .search-input { background:var(--bg3);border:1px solid var(--border);border-radius:12px;padding:12px 16px;width:100%;color:#fff;font-size:14px;font-family:inherit;outline:none;margin-bottom:14px; }
    .search-input:focus { border-color:var(--blue); }
    .search-input::placeholder { color:var(--text3); }
    .clase-selector { background:var(--card);border:1px solid var(--border);border-radius:12px;padding:12px 16px;width:100%;color:#fff;font-size:14px;font-family:inherit;outline:none;margin-bottom:16px;cursor:pointer; }
    .clase-selector:focus { border-color:var(--blue); }
  </style>
  <link rel="icon" type="image/png" href="icono_pulso.png">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">
  <div class="page-title" style="display:flex;justify-content:space-between;align-items:center;gap:10px">Panel Admin <a href="admin_configuracion.php" class="btn btn-outline" style="width:auto;padding:8px 14px;font-size:13px;text-decoration:none;border-color:var(--blue);color:var(--blue)">⚙️ Configuración</a></div>

  <div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-box"><div class="stat-num"><?= $total_alumnos ?></div><div class="stat-lbl">Alumnos</div></div>
    <div class="stat-box"><div class="stat-num"><?= $clases_semana ?></div><div class="stat-lbl">Clases esta semana</div></div>
    <div class="stat-box"><div class="stat-num"><?= $sin_creditos ?></div><div class="stat-lbl">Sin créditos</div></div>
    <div class="stat-box"><div class="stat-num"><?= $asist_mes ?></div><div class="stat-lbl">Asistencias mes</div></div>
  </div>

  <div class="admin-tabs-mobile">
    <button class="admin-tab-btn active" onclick="showTab('t-alumnos',this)">👥 Alumnos</button>
    <button class="admin-tab-btn" onclick="showTab('t-clases',this)">📅 Clases</button>
    <button class="admin-tab-btn" onclick="showTab('t-asist',this)">✅ Asistencia</button>
    <button class="admin-tab-btn" onclick="showTab('t-avisos',this)">🔔 Avisos</button>
    <?php $pp = mysqli_fetch_assoc(mysqli_query($conex, "SELECT COUNT(*) c FROM pagos WHERE estado='pendiente' AND metodo<>'mercadopago'")); ?>
    <?php $pa = mysqli_fetch_assoc(mysqli_query($conex, "SELECT COUNT(*) c FROM registronuevo WHERE rol='alumno' AND apto_estado='pendiente'")); ?>
    <a href="admin_aptos.php" class="admin-tab-btn" style="text-decoration:none">🩺 Aptos<?= $pa['c'] ? ' <span style="background:#facc15;color:#000;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px">'.(int)$pa['c'].'</span>' : '' ?></a>
    <a href="admin_pagos.php" class="admin-tab-btn" style="text-decoration:none">💳 Pagos<?= $pp['c'] ? ' <span style="background:var(--red);color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px">'.(int)$pp['c'].'</span>' : '' ?></a>
  </div>

  <!-- ALUMNOS -->
  <div id="t-alumnos" class="admin-panel active">
    <input class="search-input" placeholder="🔍 Buscar alumno..." oninput="filtrar(this.value)">
    <div id="lista-alumnos">
    <?php while ($al = mysqli_fetch_assoc($res_alumnos)):
      $ini = strtoupper(substr($al['nombre'],0,1).substr($al['apellido'],0,1));
      $cred = (int)($al['creditos'] ?? 0);
    ?>
    <a class="alumno-row" href="admin_alumno.php?id=<?= $al['id'] ?>" style="text-decoration:none;color:inherit">
      <div class="alumno-av"><?= $ini ?></div>
      <div class="alumno-info">
        <div class="alumno-name"><?= htmlspecialchars($al['nombre'].' '.$al['apellido']) ?></div>
        <div class="alumno-sub"><?= htmlspecialchars($al['email'] ?? '') ?></div>
      </div>
      <span class="badge <?= $cred==0?'badge-red':'' ?>"><?= $cred ?> créditos</span>
    </a>
    <?php endwhile; ?>
    </div>
  </div>

  <!-- CLASES -->
  <div id="t-clases" class="admin-panel">
    <a href="admin_nueva_clase.php" class="btn btn-blue" style="display:block;text-align:center;text-decoration:none;margin-bottom:8px">+ Nueva clase</a>
    <a href="admin_clase_recurrente.php" class="btn btn-outline" style="display:block;text-align:center;text-decoration:none;margin-bottom:14px;border-color:var(--blue);color:var(--blue)">🔄 Clases recurrentes</a>

    <form method="post" action="admin_borrar_clases.php" id="form-clases" data-confirm-tipo="peligro" data-confirm-titulo="¿Eliminar las clases seleccionadas?" data-confirm-texto="Se borran también sus inscripciones y asistencias." data-confirm-nota="Esta acción no se puede deshacer." data-confirm-boton="Sí, eliminar">
      <!-- BARRA DE SELECCIÓN -->
      <div id="barra-seleccion" style="display:none;background:rgba(248,113,113,0.1);border:1px solid var(--red);border-radius:12px;padding:12px 14px;margin-bottom:12px;align-items:center;justify-content:space-between">
        <span id="contador-sel" style="font-size:13px;color:var(--red);font-weight:700">0 seleccionadas</span>
        <button type="submit" style="background:var(--red);border:none;color:#fff;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit">
          🗑️ Eliminar seleccionadas
        </button>
      </div>

      <div style="display:flex;justify-content:flex-end;margin-bottom:8px">
        <button type="button" onclick="seleccionarTodas()" style="background:none;border:none;color:var(--text2);font-size:12px;cursor:pointer;font-family:inherit;text-decoration:underline">
          Seleccionar todas
        </button>
      </div>

    <?php while ($c = mysqli_fetch_assoc($res_clases)):
      $ts = strtotime($c['fecha']);
    ?>
    <div class="class-card" style="position:relative">
      <input type="checkbox" name="clases_ids[]" value="<?= $c['id'] ?>"
             style="width:18px;height:18px;accent-color:var(--red);flex-shrink:0;cursor:pointer"
             onchange="actualizarSeleccion()">
      <div class="date-box">
        <div class="date-num"><?= date('d',$ts) ?></div>
        <div class="date-day"><?= $dias_es[date('w',$ts)] ?></div>
      </div>
      <div class="class-info">
        <div class="class-name"><?= htmlspecialchars($c['nombre']) ?></div>
        <div class="class-time"><?= substr($c['hora_inicio'],0,5) ?> - <?= substr($c['hora_fin'],0,5) ?> · <?= $c['inscriptos'] ?> anotados</div>
      </div>
      <a href="admin_editar_clase.php?id=<?= $c['id'] ?>" class="btn-class">Editar</a>
    </div>
    <?php endwhile; ?>
    </form>
  </div>

  <!-- ASISTENCIA -->
  <div id="t-asist" class="admin-panel">
    <!-- Selector de clase -->
    <form method="get" action="acceso_administrador.php">
      <input type="hidden" name="tab" value="asist">
      <label style="font-size:12px;color:var(--text2);display:block;margin-bottom:6px">Seleccioná la clase</label>
      <select name="clase_asist" class="clase-selector" onchange="this.form.submit()">
        <?php
        $res_todas = mysqli_query($conex, "SELECT * FROM clases ORDER BY fecha DESC, hora_inicio DESC LIMIT 30");
        while ($cl = mysqli_fetch_assoc($res_todas)):
          $ts2 = strtotime($cl['fecha']);
          $sel = ($cl['id'] == $clase_asist_id) ? 'selected' : '';
        ?>
        <option value="<?= $cl['id'] ?>" <?= $sel ?>>
          <?= $dias_es[date('w',$ts2)] ?> <?= date('d/m',$ts2) ?> · <?= substr($cl['hora_inicio'],0,5) ?> - <?= htmlspecialchars($cl['nombre']) ?>
        </option>
        <?php endwhile; ?>
      </select>
    </form>

    <?php if ($clase_asist_info): ?>
    <div style="font-size:13px;color:var(--text2);margin-bottom:12px">
      <strong style="color:#fff"><?= htmlspecialchars($clase_asist_info['nombre']) ?></strong>
      · <?= $dias_es[date('w',strtotime($clase_asist_info['fecha']))] ?>
      <?= date('d/m',strtotime($clase_asist_info['fecha'])) ?>
      · <?= substr($clase_asist_info['hora_inicio'],0,5) ?>
    </div>

    <div class="card-tp" style="margin-bottom:16px">
      <?php if ($res_asist && mysqli_num_rows($res_asist) > 0):
        while ($a = mysqli_fetch_assoc($res_asist)):
          $estado = is_null($a['presente']) ? 'nd' : ($a['presente'] ? 'ok' : 'no');
      ?>
      <div class="info-row">
        <span class="info-label"><?= htmlspecialchars($a['nombre'].' '.$a['apellido']) ?></span>
        <?php if ($estado==='ok'): ?><span style="color:var(--green);font-weight:700">✓ Presente</span>
        <?php elseif ($estado==='no'): ?><span style="color:var(--red);font-weight:700">✗ Ausente</span>
        <?php else: ?><span style="color:var(--text3)">— Sin registrar</span>
        <?php endif; ?>
      </div>
      <?php endwhile;
      else: ?>
        <div style="font-size:13px;color:var(--text2);text-align:center;padding:20px">Sin inscriptos en esta clase.</div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <a href="admin_escanear_qr.php" class="btn btn-blue" style="display:block;text-align:center;text-decoration:none">
      📷 Escanear QR
    </a>
  </div>

  <!-- AVISOS -->
  <div id="t-avisos" class="admin-panel">
    <form action="admin_enviar_aviso.php" method="post" id="form-aviso">
      
      <!-- TIPO DE DESTINATARIO -->
      <div style="margin-bottom:14px">
        <label style="font-size:12px;color:var(--text2);display:block;margin-bottom:8px">Enviar a</label>
        <div style="display:flex;flex-direction:column;gap:8px">
          <label style="display:flex;align-items:center;gap:10px;padding:12px 14px;background:var(--bg3);border:1px solid var(--border);border-radius:12px;cursor:pointer">
            <input type="radio" name="tipo" value="todos" checked onchange="mostrarDestino(this.value)" style="accent-color:var(--blue)">
            <div>
              <div style="font-size:13px;font-weight:700;color:#fff">Todos los alumnos</div>
              <div style="font-size:11px;color:var(--text2)">Le llega a todos</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:10px;padding:12px 14px;background:var(--bg3);border:1px solid var(--border);border-radius:12px;cursor:pointer">
            <input type="radio" name="tipo" value="alumno" onchange="mostrarDestino(this.value)" style="accent-color:var(--blue)">
            <div>
              <div style="font-size:13px;font-weight:700;color:#fff">Un alumno específico</div>
              <div style="font-size:11px;color:var(--text2)">Elegís a quién</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:10px;padding:12px 14px;background:var(--bg3);border:1px solid var(--border);border-radius:12px;cursor:pointer">
            <input type="radio" name="tipo" value="clase" onchange="mostrarDestino(this.value)" style="accent-color:var(--blue)">
            <div>
              <div style="font-size:13px;font-weight:700;color:#fff">Una clase específica</div>
              <div style="font-size:11px;color:var(--text2)">Solo los anotados a esa clase</div>
            </div>
          </label>
        </div>
      </div>

      <!-- SELECTOR ALUMNO -->
      <div id="sel-alumno" style="display:none;margin-bottom:14px">
        <label style="font-size:12px;color:var(--text2);display:block;margin-bottom:6px">Seleccioná el alumno</label>
        <select name="alumno_id" class="clase-selector">
          <?php
          $res_al = mysqli_query($conex, "SELECT id, nombre, apellido FROM registronuevo WHERE rol='alumno' ORDER BY apellido ASC");
          while ($a = mysqli_fetch_assoc($res_al)):
          ?>
          <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre'].' '.$a['apellido']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- SELECTOR CLASE -->
      <div id="sel-clase" style="display:none;margin-bottom:14px">
        <label style="font-size:12px;color:var(--text2);display:block;margin-bottom:6px">Seleccioná la clase</label>
        <select name="clase_id" class="clase-selector">
          <?php
          $res_cl = mysqli_query($conex, "SELECT c.id, c.nombre, c.fecha, c.hora_inicio, (SELECT COUNT(*) FROM inscripciones WHERE clase_id=c.id) as ins FROM clases c WHERE c.fecha >= CURDATE() ORDER BY c.fecha ASC LIMIT 20");
          $dias_es2 = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
          while ($cl = mysqli_fetch_assoc($res_cl)):
            $ts2 = strtotime($cl['fecha']);
          ?>
          <option value="<?= $cl['id'] ?>">
            <?= $dias_es2[date('w',$ts2)] ?> <?= date('d/m',$ts2) ?> · <?= substr($cl['hora_inicio'],0,5) ?> - <?= htmlspecialchars($cl['nombre']) ?> (<?= $cl['ins'] ?> anotados)
          </option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- MENSAJE -->
      <div style="margin-bottom:14px">
        <label style="font-size:12px;color:var(--text2);display:block;margin-bottom:6px">Mensaje</label>
        <textarea name="mensaje" class="search-input" rows="4" placeholder="Ej: La clase del miércoles está suspendida." style="resize:vertical;height:auto" required></textarea>
      </div>

      <button type="submit" class="btn btn-blue">Enviar aviso</button>
    </form>
  </div>
</div>


<?php include '_layout_nav_close.php'; ?>
<script>
function showTab(id, el) {
  document.querySelectorAll('.admin-panel').forEach(p => p.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  document.querySelectorAll('.admin-tab-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  // Si tab=asist en URL, activar asistencia
  if (id === 't-asist') {
    const url = new URL(window.location);
    url.searchParams.set('tab', 'asist');
    window.history.replaceState({}, '', url);
  }
}



function actualizarSeleccion() {
  const checks = document.querySelectorAll('#form-clases input[type=checkbox]:checked');
  const barra = document.getElementById('barra-seleccion');
  const contador = document.getElementById('contador-sel');
  if (checks.length > 0) {
    barra.style.display = 'flex';
    barra.style.alignItems = 'center';
    barra.style.justifyContent = 'space-between';
    contador.textContent = checks.length + ' seleccionada' + (checks.length > 1 ? 's' : '');
  } else {
    barra.style.display = 'none';
  }
}
function seleccionarTodas() {
  const checks = document.querySelectorAll('#form-clases input[type=checkbox]');
  const todas = Array.from(checks).every(c => c.checked);
  checks.forEach(c => { c.checked = !todas; });
  actualizarSeleccion();
}
function mostrarDestino(tipo) {
  document.getElementById('sel-alumno').style.display = tipo === 'alumno' ? 'block' : 'none';
  document.getElementById('sel-clase').style.display = tipo === 'clase' ? 'block' : 'none';
}
function filtrar(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#lista-alumnos .alumno-row').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? 'flex' : 'none';
  });
}
// Si viene con tab=asist en URL, activar esa pestaña
window.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('tab') === 'asist') {
    showTab('t-asist', document.querySelectorAll('.admin-tab-btn')[2]);
  }
});
</script>
</body>
</html>

<?php
require_once 'auth.php';
requerir_rol(['administrador']);
require_once 'dbconn.php';
$pagina_activa = 'admin';

$alumno_id = (int)($_GET['id'] ?? 0);
if (!$alumno_id) { header('Location: acceso_administrador.php'); exit(); }

$mensaje = '';
$tipo_msg = '';

// PROCESAR FORMULARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'agregar') {
        $cant = (int)($_POST['cantidad'] ?? 0);
        $venc = trim($_POST['vencimiento'] ?? '');

        if ($cant > 0) {
            if ($venc === '') {
                $venc = date('Y-m-d', strtotime('+' . (int)cfg('dias_vencimiento', 31) . ' days'));
            }
            $venc_esc = mysqli_real_escape_string($conex, $venc);

            mysqli_query($conex, "UPDATE registronuevo SET creditos = COALESCE(creditos,0) + $cant, vencimiento_creditos = '$venc_esc' WHERE id = $alumno_id");
            mysqli_query($conex, "INSERT INTO abonos (alumno_id, creditos_total, creditos_usados, fecha_inicio, fecha_fin) VALUES ($alumno_id, $cant, 0, CURDATE(), '$venc_esc')");

            $mensaje = "✅ Se agregaron $cant créditos. Vencen el " . date('d/m/Y', strtotime($venc));
            $tipo_msg = 'success';
        } else {
            $mensaje = 'Poné una cantidad válida.';
            $tipo_msg = 'danger';
        }
    }
    elseif ($accion === 'quitar') {
        $cant = (int)($_POST['cantidad_quitar'] ?? 0);
        if ($cant > 0) {
            mysqli_query($conex, "UPDATE registronuevo SET creditos = GREATEST(0, COALESCE(creditos,0) - $cant) WHERE id = $alumno_id");
            $mensaje = "Se descontaron $cant créditos.";
            $tipo_msg = 'info';
        }
    }
    elseif ($accion === 'cambiar_venc') {
        $venc = trim($_POST['nuevo_vencimiento'] ?? '');
        if ($venc !== '') {
            $venc_esc = mysqli_real_escape_string($conex, $venc);
            mysqli_query($conex, "UPDATE registronuevo SET vencimiento_creditos = '$venc_esc' WHERE id = $alumno_id");
            $mensaje = "✅ Vencimiento actualizado al " . date('d/m/Y', strtotime($venc));
            $tipo_msg = 'success';
        }
    }
}

// Traer datos actualizados
$res = mysqli_query($conex, "SELECT * FROM registronuevo WHERE id = $alumno_id LIMIT 1");
$al = mysqli_fetch_assoc($res);
if (!$al) { header('Location: acceso_administrador.php'); exit(); }

$creditos = (int)($al['creditos'] ?? 0);
$meses_es = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$ini = strtoupper(substr($al['nombre'],0,1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cargar créditos - <?= htmlspecialchars($al['nombre']) ?></title>
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" type="image/png" href="icono_pulso.png">
</head>
<body>
<?php include '_layout_nav.php'; ?>
<div class="page-body">

  <!-- HEADER ALUMNO -->
  <div class="card-tp" style="display:flex;align-items:center;gap:14px;margin-bottom:20px">
    <div style="width:48px;height:48px;border-radius:50%;background:var(--bg3);border:2px solid var(--blue);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:900;color:var(--blue);flex-shrink:0">
      <?= $ini ?>
    </div>
    <div style="flex:1">
      <div style="font-size:16px;font-weight:800;text-transform:capitalize">
        <?= htmlspecialchars(strtolower($al['nombre'].' '.$al['apellido'])) ?>
      </div>
      <div style="font-size:12px;color:var(--text2)"><?= htmlspecialchars($al['email'] ?? '') ?></div>
    </div>
  </div>

  <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo_msg ?>"><?= $mensaje ?></div>
  <?php endif; ?>

  <!-- ESTADO ACTUAL -->
  <div class="credits-card" style="margin-bottom:22px">
    <div class="credits-label">CRÉDITOS ACTUALES</div>
    <div class="credits-number"><?= $creditos ?></div>
    <div class="credits-sub">
      <?php if ($al['vencimiento_creditos']):
        $tsv = strtotime($al['vencimiento_creditos']);
        echo 'Vencen el ' . date('d', $tsv) . ' de ' . $meses_es[(int)date('m',$tsv)] . ' de ' . date('Y',$tsv);
      else: echo 'Sin vencimiento definido'; endif; ?>
    </div>
  </div>

  <!-- AGREGAR CRÉDITOS -->
  <div class="section-title">Agregar créditos</div>
  <div class="card-tp" style="margin-bottom:20px">
    <form method="post">
      <input type="hidden" name="accion" value="agregar">

      <div class="field">
        <label>Cantidad de créditos</label>
        <input type="number" name="cantidad" min="1" placeholder="Ej: 8" required
               style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:14px 16px;color:#fff;font-size:16px;font-family:inherit;outline:none">
      </div>

      <div class="field">
        <label>Fecha de vencimiento</label>
        <input type="date" name="vencimiento" min="<?= date('Y-m-d') ?>"
               value="<?= date('Y-m-d', strtotime('+' . (int)cfg('dias_vencimiento', 31) . ' days')) ?>"
               style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:14px 16px;color:#fff;font-size:16px;font-family:inherit;outline:none">
        <div style="font-size:11px;color:var(--text3);margin-top:6px">
          Por defecto viene el último día del mes actual. Cambialo si querés otra fecha.
        </div>
      </div>

      <button type="submit" class="btn btn-blue" style="margin-top:8px">+ Agregar créditos</button>
    </form>
  </div>

  <!-- CAMBIAR SOLO EL VENCIMIENTO -->
  <div class="section-title">Cambiar fecha de vencimiento</div>
  <div class="card-tp" style="margin-bottom:20px">
    <p style="font-size:12px;color:var(--text2);margin-bottom:12px">
      Modificá el vencimiento de los créditos que ya tiene, sin agregar ni quitar.
    </p>
    <form method="post">
      <input type="hidden" name="accion" value="cambiar_venc">
      <div class="field">
        <input type="date" name="nuevo_vencimiento" min="<?= date('Y-m-d') ?>"
               value="<?= $al['vencimiento_creditos'] ?? date('Y-m-d', strtotime('+' . (int)cfg('dias_vencimiento', 31) . ' days')) ?>" required
               style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:14px 16px;color:#fff;font-size:16px;font-family:inherit;outline:none">
      </div>
      <button type="submit" class="btn btn-outline" style="border-color:var(--blue);color:var(--blue)">
        Actualizar vencimiento
      </button>
    </form>
  </div>

  <!-- QUITAR CRÉDITOS -->
  <div class="section-title">Descontar créditos</div>
  <div class="card-tp" style="margin-bottom:20px">
    <form method="post" data-confirm-tipo="aviso" data-confirm-titulo="¿Descontar créditos?" data-confirm-texto="Se le van a restar los créditos que indicaste a este alumno." data-confirm-boton="Sí, descontar">
      <input type="hidden" name="accion" value="quitar">
      <div class="field">
        <label>Cantidad a descontar</label>
        <input type="number" name="cantidad_quitar" min="1" max="<?= $creditos ?>" placeholder="Ej: 1" required
               style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:14px 16px;color:#fff;font-size:16px;font-family:inherit;outline:none">
      </div>
      <button type="submit" class="btn btn-danger">− Descontar créditos</button>
    </form>
  </div>

  <a href="admin_alumno.php?id=<?= $alumno_id ?>" class="btn btn-outline" style="display:block;text-align:center;text-decoration:none">
    ← Volver al perfil del alumno
  </a>
</div>
<?php include '_layout_nav_close.php'; ?>
</body>
</html>

<?php
// =============================================
// config.php — Datos del gimnasio (salen de la tabla "configuracion")
// Uso: echo gym_nombre();  /  echo cfg('alias_transferencia');
// =============================================
// Hora de Argentina para todo el sistema (el servidor está en Europa)
date_default_timezone_set('America/Argentina/Buenos_Aires');

require_once __DIR__ . '/dbconn.php';

// La base de datos también en hora argentina (afecta CURDATE() y NOW())
if (isset($conex) && $conex) {
    @mysqli_query($conex, "SET time_zone = '-03:00'");
}

function cfg($clave, $por_defecto = '')
{
    global $conex;
    static $datos = null;

    // Se lee la base una sola vez por página
    if ($datos === null) {
        $res = mysqli_query($conex, "SELECT * FROM configuracion WHERE id = 1");
        $datos = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : array();
    }

    return (isset($datos[$clave]) && $datos[$clave] !== '') ? $datos[$clave] : $por_defecto;
}

// Atajos ya escapados para usar en el HTML
function gym_nombre() { return htmlspecialchars(cfg('nombre_gimnasio', 'Pulso')); }
function gym_logo()   { return htmlspecialchars(cfg('logo', 'logo_pulso.png')); }

// Estado del apto médico: devuelve [ícono, texto, color]
function apto_info($estado)
{
    switch ($estado) {
        case 'pendiente': return array('🟡', 'Pendiente de validación', '#facc15');
        case 'aprobado':  return array('✅', 'Aprobado', 'var(--green)');
        case 'rechazado': return array('❌', 'Rechazado', 'var(--red)');
        default:          return array('📋', 'Sin cargar', 'var(--red)');
    }
}

// Lee una clave del archivo .env (ej: MP_ACCESS_TOKEN)
function env_valor($clave)
{
    // 1) Si ya la cargó otra parte del sistema (Dotenv)
    if (!empty($_ENV[$clave]))    return trim($_ENV[$clave]);
    if (!empty($_SERVER[$clave])) return trim($_SERVER[$clave]);
    if (getenv($clave))           return trim(getenv($clave));

    // 2) Leer el archivo .env a mano
    static $env = null;
    if ($env === null) {
        $env = array();
        $archivo = __DIR__ . '/.env';
        if (is_readable($archivo)) {
            $texto = file_get_contents($archivo);
            $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);   // saca el BOM de Windows
            foreach (preg_split('/\r\n|\r|\n/', $texto) as $linea) {
                $linea = trim($linea);
                if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) continue;
                if (stripos($linea, 'export ') === 0) $linea = substr($linea, 7);
                list($k, $v) = explode('=', $linea, 2);
                $env[trim($k)] = trim($v, " \t\"'");
            }
        }
    }
    return isset($env[$clave]) ? $env[$clave] : '';
}

// Explica por qué no se encontró una clave (para el mensaje de error)
function env_diagnostico($clave)
{
    $archivo = __DIR__ . '/.env';
    if (!file_exists($archivo)) return "No existe el archivo .env en $archivo";
    if (!is_readable($archivo)) return "El archivo .env existe pero no se puede leer (permisos)";
    $claves = array();
    foreach (file($archivo) as $l) {
        if (strpos($l, '=') !== false) $claves[] = trim(explode('=', preg_replace('/^\xEF\xBB\xBF/', '', $l), 2)[0]);
    }
    return "Falta $clave en el .env del servidor. El .env que hay en el servidor tiene: " . implode(', ', $claves)
         . " (modificado el " . date('d/m/Y H:i', filemtime($archivo)) . ")";
}

// Notificaciones (app + WhatsApp)
require_once __DIR__ . '/notificar.php';

// Avisos diarios automáticos (una vez por día)
tareas_diarias_automaticas();

// Tarjeta "Tu gimnasio" con los datos de contacto (solo muestra lo que esté cargado)
function tarjeta_contacto($titulo = '📍 Tu gimnasio')
{
    $tel = cfg('telefono'); $mail = cfg('email_contacto'); $dir = cfg('direccion');
    if ($tel === '' && $mail === '' && $dir === '') return '';
    $wa = preg_replace('/\D/', '', function_exists('wa_normalizar') ? wa_normalizar($tel) : $tel);
    $b = 'display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:10px;border:1px solid var(--border);background:var(--bg2);color:#fff;text-decoration:none;font-size:12px;font-weight:600';
    $h  = '<div class="card-tp" style="margin-top:18px">';
    $h .= '<div style="font-size:14px;font-weight:800;color:#fff;margin-bottom:4px">' . $titulo . '</div>';
    $h .= '<div style="font-size:13px;color:var(--text2);margin-bottom:12px">' . gym_nombre() . ($dir !== '' ? ' · ' . htmlspecialchars($dir) : '') . '</div>';
    $h .= '<div style="display:flex;gap:8px;flex-wrap:wrap">';
    if ($tel !== '') {
        $h .= '<a style="' . $b . ';border-color:#25d366;color:#25d366" target="_blank" href="https://wa.me/' . $wa . '">💬 WhatsApp</a>';
        $h .= '<a style="' . $b . '" href="tel:+' . $wa . '">📞 Llamar</a>';
    }
    if ($mail !== '') $h .= '<a style="' . $b . '" href="mailto:' . htmlspecialchars($mail) . '">✉️ ' . htmlspecialchars($mail) . '</a>';
    if ($dir !== '')  $h .= '<a style="' . $b . '" target="_blank" href="https://www.google.com/maps/search/?api=1&query=' . urlencode($dir) . '">🗺️ Cómo llegar</a>';
    return $h . '</div></div>';
}

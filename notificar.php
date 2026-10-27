<?php
// =============================================
// notificar.php — Notificaciones en la app + WhatsApp (API oficial de Meta)
// Se carga solo desde config.php
// =============================================

// Qué servicio de WhatsApp se usa: 'meta' (API oficial) o 'twilio' (sandbox de prueba)
function wa_proveedor()
{
    $p = strtolower(env_valor('WA_PROVEEDOR'));
    if (in_array($p, array('twilio', 'meta', 'greenapi'))) return $p;
    if (env_valor('GREEN_ID_INSTANCE') !== '') return 'greenapi';
    return env_valor('TWILIO_SID') !== '' ? 'twilio' : 'meta';
}

// Qué claves necesita cada servicio en el .env
function wa_claves()
{
    switch (wa_proveedor()) {
        case 'greenapi': return array('GREEN_API_URL', 'GREEN_ID_INSTANCE', 'GREEN_API_TOKEN');
        case 'twilio':   return array('TWILIO_SID', 'TWILIO_TOKEN', 'TWILIO_FROM');
        default:         return array('WA_TOKEN', 'WA_PHONE_ID');
    }
}

function wa_claves_ok()
{
    foreach (wa_claves() as $k) if (env_valor($k) === '') return false;
    return true;
}

// ¿Está todo listo para mandar WhatsApp?
function wa_configurado()
{
    return cfg('whatsapp_activo', '0') == '1' && wa_claves_ok();
}

// Convierte "011 2345-6789" o "+54 9 11 2345 6789" al formato 5491123456789
function wa_normalizar($tel)
{
    $d = preg_replace('/\D/', '', (string)$tel);
    if ($d === '') return '';
    if (substr($d, 0, 2) === '00') $d = substr($d, 2);
    if (substr($d, 0, 2) !== '54') {
        if ($d[0] === '0') $d = substr($d, 1);
        $d = '54' . $d;
    }
    if (substr($d, 2, 1) !== '9') $d = '549' . substr($d, 2);   // celulares argentinos llevan 9
    return $d;
}

// Envío por Green-API (WhatsApp vinculado por QR, como WhatsApp Web). Devuelve [ok, código, detalle]
function wa_llamar_greenapi($numero, $texto)
{
    $base = rtrim(env_valor('GREEN_API_URL'), '/');
    if ($base !== '' && strpos($base, 'http') !== 0) $base = 'https://' . $base;
    $url = $base . '/waInstance' . env_valor('GREEN_ID_INSTANCE') . '/sendMessage/' . env_valor('GREEN_API_TOKEN');
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
        CURLOPT_POSTFIELDS => json_encode(array('chatId' => $numero . '@c.us', 'message' => $texto)),
    ));
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp === false) return array(false, 0, 'Sin conexión con Green-API: ' . $err . ' (revisá GREEN_API_URL)');
    $j = json_decode($resp, true);
    if ($code < 400 && !empty($j['idMessage'])) return array(true, 0, 'Enviado');
    $explicacion = array(
        401 => 'GREEN_ID_INSTANCE o GREEN_API_TOKEN están mal',
        403 => 'GREEN_ID_INSTANCE o GREEN_API_TOKEN están mal',
        404 => 'GREEN_API_URL o GREEN_ID_INSTANCE están mal',
        466 => 'Se pasó el límite del plan gratis (máximo 3 contactos distintos por mes)',
        400 => 'El WhatsApp no está vinculado (escaneá el QR en la consola de Green-API) o el número está mal',
    );
    $msj = is_array($j) ? ($j['message'] ?? json_encode($j)) : substr((string)$resp, 0, 150);
    return array(false, $code, (isset($explicacion[$code]) ? $explicacion[$code] . ' · ' : '') . "Error $code: $msj");
}

// Envío por Twilio (sandbox de WhatsApp). Devuelve [ok, código, detalle]
function wa_llamar_twilio($numero, $texto)
{
    $sid  = env_valor('TWILIO_SID');
    $from = preg_replace('/[^0-9+]/', '', env_valor('TWILIO_FROM'));
    if ($from !== '' && $from[0] !== '+') $from = '+' . $from;
    $ch = curl_init('https://api.twilio.com/2010-04-01/Accounts/' . $sid . '/Messages.json');
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERPWD => $sid . ':' . env_valor('TWILIO_TOKEN'),
        CURLOPT_POSTFIELDS => http_build_query(array(
            'From' => 'whatsapp:' . $from,
            'To'   => 'whatsapp:+' . $numero,
            'Body' => $texto,
        )),
    ));
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp === false) return array(false, 0, 'Sin conexión con Twilio: ' . $err);
    $j = json_decode($resp, true);
    if ($code < 400 && !empty($j['sid'])) {
        if (in_array($j['status'] ?? '', array('failed', 'undelivered'))) return array(false, (int)($j['error_code'] ?? 0), 'Twilio no pudo entregarlo');
        return array(true, 0, 'Enviado');
    }
    $num = (int)($j['code'] ?? 0);
    $msj = $j['message'] ?? substr($resp, 0, 150);
    $explicacion = array(
        20003 => 'TWILIO_SID o TWILIO_TOKEN están mal',
        20404 => 'TWILIO_SID está mal',
        21211 => 'El número del alumno no es válido',
        21606 => 'TWILIO_FROM está mal (tiene que ser el número del sandbox)',
        63007 => 'TWILIO_FROM está mal (tiene que ser el número del sandbox)',
        63015 => 'Ese celular no se unió al sandbox: tiene que mandar el mensaje "join ..." al número de Twilio',
        63016 => 'Pasaron más de 24 hs desde que esa persona le escribió al número de Twilio',
        572002 => 'Ese celular no está en "Verified Caller IDs" de Twilio (cuenta de prueba): agregalo y verificalo en la consola de Twilio',
        21608 => 'Ese celular no está verificado en la cuenta de prueba de Twilio',
    );
    return array(false, $num, (isset($explicacion[$num]) ? $explicacion[$num] . ' · ' : '') . "Error $num: $msj");
}

// Llamada a la API de WhatsApp. Devuelve [ok, código, detalle]
function wa_llamar($numero, $texto)
{
    if (wa_proveedor() === 'greenapi') return wa_llamar_greenapi($numero, $texto);
    if (wa_proveedor() === 'twilio')   return wa_llamar_twilio($numero, $texto);

    $url = 'https://graph.facebook.com/v21.0/' . env_valor('WA_PHONE_ID') . '/messages';
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . env_valor('WA_TOKEN'), 'Content-Type: application/json'),
        CURLOPT_POSTFIELDS => json_encode(array(
            'messaging_product' => 'whatsapp',
            'to'   => $numero,
            'type' => 'text',
            'text' => array('body' => $texto),
        )),
    ));
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp === false) return array(false, 0, 'Sin conexión con WhatsApp: ' . $err);
    $j = json_decode($resp, true);
    if ($code < 400 && !empty($j['messages'][0]['id'])) return array(true, 0, 'Enviado');

    $num = (int)($j['error']['code'] ?? 0);
    $msj = $j['error']['message'] ?? substr($resp, 0, 150);
    $explicacion = array(
        190    => 'El WA_TOKEN es incorrecto o venció',
        131030 => 'Ese número no está en la lista de números permitidos de Meta (modo prueba)',
        131047 => 'Pasaron más de 24 hs desde que esa persona le escribió al número del gimnasio',
        131026 => 'Ese número no tiene WhatsApp',
        100    => 'Algún dato está mal (revisá WA_PHONE_ID)',
    );
    return array(false, $num, (isset($explicacion[$num]) ? $explicacion[$num] . ' · ' : '') . "Error $num: $msj");
}

// Manda un WhatsApp y lo registra en whatsapp_log. Devuelve [ok, detalle]
function wa_enviar($telefono, $texto, $usuario_id = null)
{
    global $conex;
    $numero = wa_normalizar($telefono);
    if ($numero === '') return array(false, 'No tiene teléfono cargado');

    list($ok, $cod, $detalle) = wa_llamar($numero, $texto);
    // Particularidad de Argentina: a veces Meta tiene el número guardado sin el 9
    // Particularidad de Argentina: a veces el número quedó registrado sin el 9
    $sin9 = (substr($numero, 0, 3) === '549') ? '54' . substr($numero, 3) : '';
    if (!$ok && $sin9 && wa_proveedor() === 'meta' && $cod == 131030) {
        list($ok, $cod, $detalle) = wa_llamar($sin9, $texto);
    }
    if (!$ok && $sin9 && wa_proveedor() === 'twilio' && in_array($cod, array(572002, 21608, 63015))) {
        list($ok2, $cod2, $detalle2) = wa_llamar($sin9, $texto);
        if ($ok2) { $ok = true; $detalle = $detalle2; $numero = $sin9; }
    }

    $uid = $usuario_id ? (int)$usuario_id : 'NULL';
    $t = mysqli_real_escape_string($conex, $numero);
    $m = mysqli_real_escape_string($conex, mb_substr($texto, 0, 1000));
    $d = mysqli_real_escape_string($conex, mb_substr($detalle, 0, 250));
    mysqli_query($conex, "INSERT INTO whatsapp_log (usuario_id, telefono, mensaje, ok, detalle) VALUES ($uid, '$t', '$m', " . ($ok ? 1 : 0) . ", '$d')");
    return array($ok, $detalle);
}

// Notifica a un usuario: campanita de la app + WhatsApp (si corresponde)
function notificar($usuario_id, $mensaje)
{
    global $conex;
    $uid = (int)$usuario_id;
    $m = mysqli_real_escape_string($conex, $mensaje);
    mysqli_query($conex, "INSERT INTO notificaciones (alumno_id, mensaje, fecha) VALUES ($uid, '$m', NOW())");

    if (!wa_configurado()) return array(null, 'WhatsApp está apagado');
    $r = mysqli_query($conex, "SELECT telefono, wa_avisos FROM registronuevo WHERE id=$uid LIMIT 1");
    $u = $r ? mysqli_fetch_assoc($r) : null;
    if (!$u || empty($u['telefono'])) return array(null, 'el alumno no tiene celular cargado');
    if (!$u['wa_avisos']) return array(null, 'el alumno desactivó los avisos por WhatsApp');
    return wa_enviar($u['telefono'], '*' . cfg('nombre_gimnasio', 'Pulso') . "*\n" . $mensaje, $uid);
}

// ---------- Textos de los avisos (los usan los avisos reales y los simulados) ----------
function msg_recordatorio_clase($clase, $hora, $cuando = 'mañana')
{
    return "📅 Recordatorio: $cuando tenés *$clase* a las " . substr($hora, 0, 5) . ' hs. ¡Te esperamos!';
}
function msg_vencimiento($creditos, $fecha)
{
    $faltan = (int)round((strtotime($fecha) - strtotime(date('Y-m-d'))) / 86400);
    $cuando = $faltan <= 0 ? 'hoy' : ($faltan == 1 ? 'mañana' : "en $faltan días (" . date('d/m', strtotime($fecha)) . ')');
    return "⏰ Te quedan $creditos créditos y vencen $cuando. ¡Aprovechalos o renová tu pack!";
}
function msg_pocos_creditos($creditos, $fecha = null)
{
    $venc = $fecha ? ' (vencen el ' . date('d/m', strtotime($fecha)) . ')' : '';
    return "⚠️ Te quedan solo $creditos créditos$venc. Renová tu pack desde la app para no quedarte sin clases.";
}
function msg_sin_creditos()
{
    return '🔴 Te quedaste sin créditos. Comprá un pack nuevo desde la app para seguir anotándote a clases.';
}
function msg_pago_aprobado($creditos, $fecha)
{
    return "✅ Pago aprobado: se cargaron $creditos créditos. Vencen el " . date('d/m/Y', strtotime($fecha)) . '.';
}
function msg_apto_aprobado()
{
    return '✅ Tu apto médico fue aprobado. ¡Ya está todo en regla!';
}

// Manda un aviso de ejemplo a un alumno (para mostrar cómo llegan los avisos automáticos)
function simular_aviso($tipo, $alumno_id)
{
    global $conex;
    $uid = (int)$alumno_id;
    $a = mysqli_fetch_assoc(mysqli_query($conex, "SELECT creditos, vencimiento_creditos FROM registronuevo WHERE id=$uid"));
    if (!$a) return array(false, 'Alumno no encontrado');
    $dias_venc = (int)cfg('dias_vencimiento', 31);
    switch ($tipo) {
        case 'clase':
            $c = mysqli_fetch_assoc(mysqli_query($conex, "SELECT c.nombre, c.hora_inicio FROM inscripciones i JOIN clases c ON c.id=i.clase_id
                                                          WHERE i.alumno_id=$uid AND c.fecha >= CURDATE() ORDER BY c.fecha, c.hora_inicio LIMIT 1"));
            $msg = $c ? msg_recordatorio_clase($c['nombre'], $c['hora_inicio']) : msg_recordatorio_clase('Funcional', '18:30');
            break;
        case 'vencimiento':
            $msg = msg_vencimiento((int)$a['creditos'] > 0 ? (int)$a['creditos'] : 4, date('Y-m-d', strtotime('+' . (int)cfg('dias_aviso_vencimiento', 3) . ' days')));
            break;
        case 'pocos':
            $msg = msg_pocos_creditos((int)cfg('creditos_aviso', 3), $a['vencimiento_creditos'] ?: date('Y-m-d', strtotime("+$dias_venc days")));
            break;
        case 'sin':
            $msg = msg_sin_creditos();
            break;
        case 'pago':
            $p = mysqli_fetch_assoc(mysqli_query($conex, "SELECT creditos FROM packs WHERE activo=1 ORDER BY creditos LIMIT 1"));
            $msg = msg_pago_aprobado($p ? (int)$p['creditos'] : 8, date('Y-m-d', strtotime("+$dias_venc days")));
            break;
        case 'apto':
            $msg = msg_apto_aprobado();
            break;
        default:
            return array(false, 'Tipo de aviso desconocido');
    }
    return notificar($uid, $msg);
}

// Avisos automáticos del día (los corre la tarea programada o el botón del admin)
function correr_tareas_diarias()
{
    global $conex;
    $resumen = array('vencimientos' => 0, 'recordatorios' => 0);
    $dias = max(1, (int)cfg('dias_aviso_vencimiento', 3));

    // 1) Créditos por vencer (un solo aviso por cada vencimiento)
    $r = mysqli_query($conex, "SELECT id, creditos, vencimiento_creditos FROM registronuevo
        WHERE creditos > 0 AND vencimiento_creditos BETWEEN CURDATE() AND CURDATE() + INTERVAL $dias DAY
          AND (aviso_venc_enviado IS NULL OR aviso_venc_enviado <> vencimiento_creditos)");
    while ($u = mysqli_fetch_assoc($r)) {
        notificar($u['id'], msg_vencimiento($u['creditos'], $u['vencimiento_creditos']));
        mysqli_query($conex, "UPDATE registronuevo SET aviso_venc_enviado = vencimiento_creditos WHERE id = {$u['id']}");
        $resumen['vencimientos']++;
    }

    // 2) Recordatorio de las clases de mañana
    $r = mysqli_query($conex, "SELECT i.id, i.alumno_id, c.nombre, c.hora_inicio FROM inscripciones i
        JOIN clases c ON c.id = i.clase_id
        WHERE c.fecha = CURDATE() + INTERVAL 1 DAY AND i.recordatorio_enviado = 0");
    while ($i = mysqli_fetch_assoc($r)) {
        notificar($i['alumno_id'], msg_recordatorio_clase($i['nombre'], $i['hora_inicio']));
        mysqli_query($conex, "UPDATE inscripciones SET recordatorio_enviado = 1 WHERE id = {$i['id']}");
        $resumen['recordatorios']++;
    }
    return $resumen;
}

// Avisa cuando al alumno le quedan pocos créditos (3) o se quedó sin créditos (0)
function avisar_creditos_bajos($usuario_id)
{
    global $conex;
    $uid = (int)$usuario_id;
    $r = mysqli_query($conex, "SELECT creditos, vencimiento_creditos FROM registronuevo WHERE id=$uid LIMIT 1");
    $u = $r ? mysqli_fetch_assoc($r) : null;
    if (!$u) return;
    $c = (int)$u['creditos'];
    $limite = (int)cfg('creditos_aviso', 3);

    if ($c == $limite && $limite > 0) {
        $msg = msg_pocos_creditos($c, $u['vencimiento_creditos']);
    } elseif ($c == 0) {
        $msg = msg_sin_creditos();
    } else {
        return;
    }
    // No repetir el mismo aviso si ya se mandó en las últimas 12 horas (ej: cancela y se vuelve a anotar)
    $m = mysqli_real_escape_string($conex, $msg);
    $ya = mysqli_fetch_row(mysqli_query($conex, "SELECT COUNT(*) FROM notificaciones WHERE alumno_id=$uid AND mensaje='$m' AND fecha > NOW() - INTERVAL 12 HOUR"));
    if ((int)$ya[0] === 0) notificar($uid, $msg);
}

// Si se anota a una clase de hoy o de mañana, el recordatorio sale en el momento
// (los de días siguientes los manda el aviso diario automático)
function recordatorio_si_es_pronto($inscripcion_alumno_id, $clase_id)
{
    global $conex;
    $uid = (int)$inscripcion_alumno_id; $cid = (int)$clase_id;
    $r = mysqli_query($conex, "SELECT i.id, c.nombre, c.fecha, c.hora_inicio FROM inscripciones i JOIN clases c ON c.id=i.clase_id
                               WHERE i.alumno_id=$uid AND i.clase_id=$cid AND i.recordatorio_enviado=0
                                 AND c.fecha IN (CURDATE(), CURDATE() + INTERVAL 1 DAY) LIMIT 1");
    $i = $r ? mysqli_fetch_assoc($r) : null;
    if (!$i) return;
    $cuando = ($i['fecha'] === date('Y-m-d')) ? 'hoy' : 'mañana';
    notificar($uid, "📅 ¡Listo! Te anotaste a *{$i['nombre']}* $cuando a las " . substr($i['hora_inicio'], 0, 5) . ' hs. ¡Te esperamos!');
    mysqli_query($conex, "UPDATE inscripciones SET recordatorio_enviado=1 WHERE id={$i['id']}");
}

// Corre los avisos del día solo, una vez por día, la primera vez que alguien entra después de las 9 hs.
// Así funciona aunque no esté configurada la tarea programada de alwaysdata.
function tareas_diarias_automaticas()
{
    if (php_sapi_name() === 'cli' || (int)date('G') < 9) return;
    $marca = __DIR__ . '/uploads/.ultima_tarea_diaria';
    $hoy = date('Y-m-d');
    if (@file_get_contents($marca) === $hoy) return;

    if (!is_dir(__DIR__ . '/uploads')) @mkdir(__DIR__ . '/uploads', 0755, true);
    $f = @fopen($marca, 'c+');
    if (!$f || !flock($f, LOCK_EX | LOCK_NB)) return;          // otro visitante ya la está corriendo
    $leido = stream_get_contents($f);
    if (trim($leido) === $hoy) { flock($f, LOCK_UN); fclose($f); return; }
    ftruncate($f, 0); rewind($f); fwrite($f, $hoy); fflush($f);
    flock($f, LOCK_UN); fclose($f);

    // Se ejecuta después de mandar la página, para que el visitante no espere
    register_shutdown_function(function () {
        global $conex;
        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
        ignore_user_abort(true);
        try {
            try { mysqli_query($conex, 'SELECT 1'); }
            catch (Throwable $e) { include __DIR__ . '/dbconn.php'; @mysqli_query($conex, "SET time_zone = '-03:00'"); }
            correr_tareas_diarias();
        } catch (Throwable $e) {
            // nunca romper la página por esto
        }
    });
}


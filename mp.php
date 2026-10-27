<?php
// =============================================
// mp.php — MercadoPago + acreditación automática de créditos
// =============================================
require_once __DIR__ . '/config.php';

// Llamada a la API de MercadoPago. Devuelve el JSON como array (o null si falla).
// Si falla, el motivo queda en $GLOBALS['mp_error'] para poder mostrarlo.
function mp_api($metodo, $ruta, $datos = null)
{
    $GLOBALS['mp_error'] = '';
    $token = env_valor('MP_ACCESS_TOKEN');
    if ($token === '') {
        $GLOBALS['mp_error'] = env_diagnostico('MP_ACCESS_TOKEN');
        return null;
    }
    $ch = curl_init('https://api.mercadopago.com' . $ruta);
    $headers = array(
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    );
    if ($metodo === 'POST') {
        $headers[] = 'X-Idempotency-Key: ' . bin2hex(random_bytes(16));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        $GLOBALS['mp_error'] = 'No se pudo conectar con MercadoPago (' . $curl_err . ').';
        return null;
    }
    $json = json_decode($resp, true);
    if ($code >= 400) {
        $detalle = $json['message'] ?? ($json['error'] ?? substr($resp, 0, 200));
        if ($code == 401) $detalle = 'El MP_ACCESS_TOKEN es incorrecto o está vencido (' . $detalle . ').';
        $GLOBALS['mp_error'] = 'MercadoPago respondió error ' . $code . ': ' . $detalle;
        return null;
    }
    return $json;
}

// URL base del sitio (ej: https://newlogin.alwaysdata.net)
function url_base()
{
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return 'https://' . $_SERVER['HTTP_HOST'] . $dir;
}

// Consulta un pago en MercadoPago y, si está aprobado, acredita los créditos.
// Devuelve el estado de MP ('approved', 'pending', 'rejected'...) o null.
function mp_verificar_pago($mp_payment_id)
{
    $mp_payment_id = preg_replace('/[^0-9]/', '', (string)$mp_payment_id);
    if ($mp_payment_id === '') return null;

    $pago_mp = mp_api('GET', '/v1/payments/' . $mp_payment_id);
    if (!$pago_mp) return null;

    $pago_id = (int)($pago_mp['external_reference'] ?? 0);
    if ($pago_mp['status'] === 'approved' && $pago_id > 0) {
        acreditar_pago($pago_id, $mp_payment_id, (float)$pago_mp['transaction_amount']);
    } elseif (in_array($pago_mp['status'], array('rejected', 'cancelled')) && $pago_id > 0) {
        global $conex;
        mysqli_query($conex, "UPDATE pagos SET estado='rechazado' WHERE id=$pago_id AND estado='pendiente'");
    }
    return $pago_mp['status'];
}

// Marca el pago como aprobado y le suma los créditos al alumno.
// Es seguro llamarla varias veces: solo acredita la primera vez.
function acreditar_pago($pago_id, $mp_payment_id = null, $monto_pagado = null)
{
    global $conex;
    $pago_id = (int)$pago_id;

    $res = mysqli_query($conex, "SELECT * FROM pagos WHERE id=$pago_id LIMIT 1");
    $pago = mysqli_fetch_assoc($res);
    if (!$pago || $pago['estado'] === 'aprobado') return false;

    // Si viene de MP, controlar que hayan pagado el monto correcto
    if ($monto_pagado !== null && $monto_pagado + 0.01 < (float)$pago['monto']) return false;

    $mp_id = $mp_payment_id ? "'" . mysqli_real_escape_string($conex, $mp_payment_id) . "'" : 'NULL';
    mysqli_query($conex, "UPDATE pagos SET estado='aprobado', fecha_aprobado=NOW(), mp_payment_id=$mp_id
                          WHERE id=$pago_id AND estado<>'aprobado'");
    if (mysqli_affected_rows($conex) !== 1) return false; // ya lo acreditó otro proceso

    $alumno_id = (int)$pago['alumno_id'];
    $cant      = (int)$pago['creditos'];
    $dias      = (int)cfg('dias_vencimiento', 31);
    $venc      = date('Y-m-d', strtotime("+$dias days"));

    mysqli_query($conex, "UPDATE registronuevo SET creditos = COALESCE(creditos,0) + $cant, vencimiento_creditos='$venc' WHERE id=$alumno_id");
    mysqli_query($conex, "INSERT INTO abonos (alumno_id, creditos_total, creditos_usados, fecha_inicio, fecha_fin) VALUES ($alumno_id, $cant, 0, CURDATE(), '$venc')");

    $fecha_txt = date('d/m/Y', strtotime($venc));
    notificar($alumno_id, msg_pago_aprobado($cant, $venc));

    // Mail al alumno
    $r = mysqli_query($conex, "SELECT nombre, apellido, email FROM registronuevo WHERE id=$alumno_id LIMIT 1");
    $al = mysqli_fetch_assoc($r);
    if ($al && !empty($al['email'])) {
        require_once __DIR__ . '/mailer.php';
        enviarMail($al['email'], $al['nombre'] . ' ' . $al['apellido'],
            '✅ Pago aprobado - ' . cfg('nombre_gimnasio', 'Gimnasio'),
            "Recibimos tu pago. Ya tenés <strong>$cant créditos</strong> nuevos para anotarte a clases.<br><br>Vencen el <strong>$fecha_txt</strong>.");
    }
    return true;
}
?>

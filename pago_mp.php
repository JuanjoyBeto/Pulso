<?php
// Crea el pago en la base y manda al alumno a pagar a MercadoPago
require_once 'auth.php';
requerir_login();
require_once 'mp.php';
$uid = (int)$_SESSION['usuario_id'];
$pack_id = (int)($_POST['pack'] ?? 0);

$res = mysqli_query($conex, "SELECT * FROM packs WHERE id=$pack_id AND activo=1 LIMIT 1");
$pack = mysqli_fetch_assoc($res);
if (!$pack) { header('Location: acceso_alumno.php'); exit(); }

$cred  = (int)$pack['creditos'];
$monto = (float)$pack['precio'];
mysqli_query($conex, "INSERT INTO pagos (alumno_id, pack_id, creditos, monto, metodo) VALUES ($uid, $pack_id, $cred, $monto, 'mercadopago')");
$pago_id = mysqli_insert_id($conex);

$base = url_base();
$pref = mp_api('POST', '/checkout/preferences', array(
    'items' => array(array(
        'title'       => $pack['nombre'] . ' - ' . cfg('nombre_gimnasio', 'Gimnasio'),
        'quantity'    => 1,
        'unit_price'  => $monto,
        'currency_id' => 'ARS',
    )),
    'external_reference' => (string)$pago_id,
    'back_urls' => array(
        'success' => $base . '/pago_resultado.php',
        'pending' => $base . '/pago_resultado.php',
        'failure' => $base . '/pago_resultado.php',
    ),
    'auto_return'      => 'approved',
    'statement_descriptor' => substr(cfg('nombre_gimnasio', 'Pulso'), 0, 22),
    'date_of_expiration'   => date('Y-m-d\TH:i:s.000P', strtotime('+3 days')),
    'notification_url' => $base . '/mp_webhook.php',
));

if (!$pref || empty($pref['init_point'])) {
    mysqli_query($conex, "UPDATE pagos SET estado='rechazado' WHERE id=$pago_id");
    $_SESSION['mp_error'] = $GLOBALS['mp_error'] ?: 'MercadoPago no devolvió el link de pago.';
    header('Location: pago.php?pack=' . $pack_id . '&error=1');
    exit();
}
header('Location: ' . $pref['init_point']);
exit();

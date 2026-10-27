<?php
// =============================================
// MercadoPago avisa acá cada vez que cambia un pago.
// No confiamos en lo que llega: consultamos el pago a MP con nuestro token.
// =============================================
require_once 'mp.php';

$body = json_decode(file_get_contents('php://input'), true) ?: array();
$tipo = $_GET['type'] ?? $_GET['topic'] ?? ($body['type'] ?? '');
$id   = $_GET['data_id'] ?? $_GET['id'] ?? ($body['data']['id'] ?? '');

if ($tipo === 'payment' && $id !== '') {
    mp_verificar_pago($id);
}
http_response_code(200);
echo 'OK';

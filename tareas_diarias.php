<?php
// =============================================
// Avisos automáticos del día (créditos por vencer + recordatorio de clases)
// Se programa en alwaysdata → Tareas programadas, una vez por día:
//   Comando:  php /home/newlogin/www/tareas_diarias.php
// =============================================
require_once __DIR__ . '/config.php';

// Solo se puede correr desde el servidor (tarea programada), no desde el navegador
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Solo se ejecuta como tarea programada.');
}

if (!is_dir(__DIR__ . '/uploads')) @mkdir(__DIR__ . '/uploads', 0755, true);
@file_put_contents(__DIR__ . '/uploads/.ultima_tarea_diaria', date('Y-m-d'));
$r = correr_tareas_diarias();
echo date('d/m/Y H:i') . " · Avisos de vencimiento: {$r['vencimientos']} · Recordatorios de clase: {$r['recordatorios']}\n";

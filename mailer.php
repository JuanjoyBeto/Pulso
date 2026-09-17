<?php
// mailer.php - Función para enviar mails con PHPMailer
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarMail($destinatario_email, $destinatario_nombre, $asunto, $mensaje_html) {
    // Cargar .env
    $env = [];
    if (file_exists(__DIR__ . '/.env')) {
        foreach (file(__DIR__ . '/.env') as $linea) {
            $linea = trim($linea);
            if (!$linea || str_starts_with($linea, '#')) continue;
            [$key, $val] = explode('=', $linea, 2);
            $env[trim($key)] = trim($val, " \"'");
        }
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $env['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $env['SMTP_USER'] ?? '';
        $mail->Password   = $env['SMTP_PASS'] ?? '';
        $mail->SMTPSecure = $env['SMTP_SECURE'] ?? 'ssl';
        $mail->Port       = (int)($env['SMTP_PORT'] ?? 465);
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($env['SMTP_USER'] ?? '', 'Training Point');
        $mail->addAddress($destinatario_email, $destinatario_nombre);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = "
        <div style='font-family:Arial,sans-serif;max-width:520px;margin:0 auto;background:#0a0a0a;color:#fff;border-radius:14px;overflow:hidden'>
          <div style='background:#0d1a2e;padding:24px;text-align:center;border-bottom:2px solid #3b9eff'>
            <h2 style='margin:0;color:#fff;font-size:20px'>📍 TRAINING <span style='color:#3b9eff'>POINT</span></h2>
          </div>
          <div style='padding:24px'>
            <p style='color:#aaa;font-size:14px;margin-bottom:16px'>Hola <strong style='color:#fff'>{$destinatario_nombre}</strong>,</p>
            <div style='background:#161616;border:1px solid #2a2a2a;border-radius:12px;padding:18px;font-size:14px;line-height:1.6;color:#ddd'>
              {$mensaje_html}
            </div>
          </div>
          <div style='background:#111;padding:16px;text-align:center;font-size:11px;color:#555'>
            Training Point · newlogin.alwaysdata.net
          </div>
        </div>";
        $mail->AltBody = strip_tags($mensaje_html);

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

// Cargar variables desde el archivo .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Muestra una pantalla con el mismo diseño que el login
function pantalla($icono, $titulo, $texto, $link, $link_txt)
{
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= gym_nombre() ?> - <?= htmlspecialchars($titulo) ?></title>
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" type="image/png" href="icono_pulso.png">
</head>
<body>
<div class="auth-bg">
  <div class="auth-card" style="text-align:center">
    <img src="<?= gym_logo() ?>" alt="<?= gym_nombre() ?>" style="height:60px;width:auto;object-fit:contain;display:block;margin:0 auto 20px">
    <div style="font-size:48px;margin-bottom:10px"><?= $icono ?></div>
    <div style="font-size:20px;font-weight:800;color:#fff;margin-bottom:10px"><?= htmlspecialchars($titulo) ?></div>
    <p style="color:var(--text2);font-size:14px;line-height:1.6;margin-bottom:24px"><?= $texto ?></p>
    <a href="<?= $link ?>" class="btn btn-blue" style="display:block;text-decoration:none"><?= htmlspecialchars($link_txt) ?></a>
  </div>
</div>
</body>
</html>
    <?php
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['email'])) {

    $email = mysqli_real_escape_string($conex, trim($_POST['email']));

    // Verificar si el usuario realmente existe
    $f = mysqli_query($conex, "SELECT * FROM registronuevo WHERE email='$email' LIMIT 1");
    $a = mysqli_fetch_assoc($f);

    if (!$a) {
        pantalla('❌', 'Correo no registrado',
            'El correo que ingresaste no pertenece a ningún usuario. Revisá que esté bien escrito.',
            'recuperar_clave.php', 'Volver a intentar');
        exit();
    }

    // Generar token y clave temporal aleatoria
    $token = bin2hex(random_bytes(16));
    $clave_nueva = random_int(10000000, 99999999);

    // Guardar o actualizar la solicitud de recuperación
    mysqli_query($conex, "INSERT INTO recuperar SET email='$email', TOKEN='$token', FECHA_ALTA=NOW(), CLAVE_NUEVA='$clave_nueva'
                          ON DUPLICATE KEY UPDATE TOKEN='$token', FECHA_ALTA=NOW(), CLAVE_NUEVA='$clave_nueva'");

    // Enlace de confirmación (sirve en cualquier dominio)
    $dir  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    $link = 'https://' . $_SERVER['HTTP_HOST'] . $dir . '/recuperar_clave_confirmar.php?e=' . urlencode($_POST['email']) . '&t=' . $token;
    $gym  = htmlspecialchars(cfg('nombre_gimnasio', 'Pulso'));

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->SMTPAuth   = true;
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->Port       = $_ENV['SMTP_PORT'];
        $mail->SMTPSecure = $_ENV['SMTP_SECURE'];
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['SMTP_USER'], cfg('nombre_gimnasio', 'Pulso'));
        $mail->addAddress($_POST['email']);

        $mail->isHTML(true);
        $mail->Subject = 'Restablecer tu contraseña - ' . cfg('nombre_gimnasio', 'Pulso');
        $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:520px;margin:0 auto;background:#0a0a0a;color:#fff;border-radius:14px;overflow:hidden'>
          <div style='background:#0d1a2e;padding:24px;text-align:center;border-bottom:2px solid #3b9eff'>
            <h2 style='margin:0;color:#fff;font-size:20px'>🔑 $gym</h2>
          </div>
          <div style='padding:24px;font-size:14px;line-height:1.6;color:#ddd'>
            <p>Pediste recuperar el acceso a tu cuenta. Esta es tu contraseña temporal:</p>
            <div style='text-align:center;margin:20px 0'>
              <span style='background:#161616;border:1px solid #3b9eff;color:#3b9eff;font-size:22px;font-weight:bold;padding:10px 22px;border-radius:10px;letter-spacing:2px'>$clave_nueva</span>
            </div>
            <p>Para activarla, tocá este botón:</p>
            <div style='text-align:center;margin:24px 0'>
              <a href='$link' style='background:#3b9eff;color:#fff;padding:12px 26px;text-decoration:none;font-weight:bold;border-radius:10px;display:inline-block'>Activar contraseña temporal</a>
            </div>
            <p style='font-size:11px;color:#777'>Si el botón no funciona, copiá este enlace en el navegador:<br><span style='color:#3b9eff;word-break:break-all'>$link</span></p>
            <p style='font-size:11px;color:#777'>Si no pediste este cambio, ignorá este correo.</p>
          </div>
        </div>";

        $mail->send();
        pantalla('📩', '¡Correo enviado!',
            'Te mandamos un mail a <strong style="color:#fff">' . htmlspecialchars($_POST['email']) . '</strong> con tu contraseña temporal.<br>Revisá la bandeja de entrada (y el spam).',
            'index.php', 'Volver al inicio de sesión');

    } catch (Exception $e) {
        pantalla('⚠️', 'No se pudo enviar el correo',
            'Hubo un problema al enviar el mail. Probá de nuevo en unos minutos.',
            'recuperar_clave.php', 'Volver');
    }
} else {
    header('Location: recuperar_clave.php');
    exit();
}

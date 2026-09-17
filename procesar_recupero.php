<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

require_once __DIR__ . '/vendor/autoload.php';

// Cargar variables desde el archivo .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['email'])) {
    
  // Incluimos el archivo de conexión externo que acabamos de crear
    require_once 'dbconn.php';


    $email = mysqli_real_escape_string($conex, $_POST['email']);
    
    // Verificar si el usuario realmente existe
    $c = "SELECT * FROM registronuevo WHERE email='$email' LIMIT 1";
    $f = mysqli_query($conex, $c);
    $a = mysqli_fetch_assoc($f);

    if (!$a) {
        echo "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Error de recuperación</title>
            <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css'>
            <link rel='stylesheet' href='styles.css'>
        </head>
        <body class='error-page'>
            <div class='error-box'>
                <h3 class='message-title-error'>❌ Correo no registrado</h3>
                <p class='mt-3 text-muted'>El correo electrónico ingresado no pertenece a ningún usuario registrado.</p>
                <a href='recuperar_clave.php' class='btn btn-netflix btn-block'>Volver a intentar</a>
            </div>
        </body>
        </html>";
        mysqli_close($conex);
        die();
    }

    // Generar token y clave temporal aleatoria
    $token = md5($email . time() . rand(1000, 9999));
    $clave_nueva = rand(10000000, 99999999);

    // Guardar o actualizar la solicitud de recuperación
    $c2 = "INSERT INTO recuperar SET email='$email', TOKEN='$token', FECHA_ALTA=NOW(), CLAVE_NUEVA='$clave_nueva' 
           ON DUPLICATE KEY UPDATE TOKEN='$token', CLAVE_NUEVA='$clave_nueva'";
    mysqli_query($conex, $c2);

    // Construir enlace de confirmación
    $link = "https://newlogin.alwaysdata.net/recuperar_clave_confirmar.php?e=" . urlencode($email) . "&t=$token";

    // --- CONFIGURACIÓN Y ENVÍO CON PHPMAILER ---
    $mail = new PHPMailer(true);

    try {
        // Ajustes del Servidor SMTP (Cargados desde tu .env)
        $mail->isSMTP();
        $mail->SMTPAuth   = true;
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->Port       = $_ENV['SMTP_PORT'];
        $mail->SMTPSecure = $_ENV['SMTP_SECURE'];
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        
        // Evitar problemas con tildes o caracteres especiales
        $mail->CharSet = 'UTF-8'; 

        // Remitente y Destinatario
        $mail->setFrom($_ENV['SMTP_USER'], 'Sistema de Soporte');
        $mail->addAddress($email); 

        // Diseño del Correo (HTML Estilizado)
        $mail->isHTML(true);
        $mail->Subject = 'Instrucciones para restablecer tu contraseña';
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px; background-color: #ffffff;'>
            <h2 style='color: #ff6600; text-align: center; margin-bottom: 20px;'>Restablecer Contraseña</h2>
            <p>Hola,</p>
            <p>Has solicitado recuperar el acceso a tu cuenta. El sistema ha generado una nueva contraseña temporal para ti:</p>
            
            <div style='text-align: center; margin: 20px 0;'>
                <span style='background-color: #fff3cd; color: #856404; font-size: 18px; font-weight: bold; padding: 10px 20px; border: 1px solid #ffeeba; border-radius: 4px; letter-spacing: 1px;'>
                    $clave_nueva
                </span>
            </div>
            
            <p>Para activar esta contraseña temporal y poder usarla, por favor haz clic en el siguiente botón de confirmación:</p>
            
            <div style='text-align: center; margin: 25px 0;'>
                <a href='$link' style='background-color: #ff6600; color: white; padding: 12px 25px; text-decoration: none; font-weight: bold; border-radius: 5px; display: inline-block;'>
                    Confirmar Cambio de Clave
                </a>
            </div>
            
            <p style='font-size: 11px; color: #777;'>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
            <p style='font-size: 11px; color: #0066cc; word-break: break-all; background: #f9f9f9; padding: 8px;'>$link</p>
            
            <hr style='border: 0; border-top: 1px solid #eeeeee; margin: 20px 0;'>
            <p style='font-size: 11px; color: #999; text-align: center;'>Si no solicitaste este cambio, puedes ignorar este correo de forma segura.</p>
        </div>
        ";

        $mail->send();
        
        // Mensaje de éxito amigable
        echo "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Correo enviado</title>
            <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css'>
            <link rel='stylesheet' href='styles.css'>
        </head>
        <body class='message-page'>
            <div class='message-box'>
                <h2 class='message-title-success'>✅ ¡Correo Enviado!</h2>
                <p>Hemos enviado un enlace de confirmación a <strong>$email</strong> con tu nueva contraseña temporal.</p>
                <p>Por favor revisa tu bandeja de entrada (y la carpeta de spam).</p>
                <a href='index.php' class='message-link'>Volver al login</a>
            </div>
        </body>
        </html>";

    } catch (Exception $e) {
        echo "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Error de envío</title>
            <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css'>
            <link rel='stylesheet' href='styles.css'>
        </head>
        <body class='error-page'>
            <div class='error-box'>
                <h3 class='message-title-error'>❌ Error al enviar el mensaje</h3>
                <p class='mt-3 text-muted'>Detalles: {$mail->ErrorInfo}</p>
                <a href='recuperar_clave.php' class='btn btn-netflix btn-block'>Volver</a>
            </div>
        </body>
        </html>";
    }

    mysqli_close($conex);
} else {
    echo "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Acceso no válido</title>
        <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css'>
        <link rel='stylesheet' href='styles.css'>
    </head>
    <body class='error-page'>
        <div class='error-box'>
            <h3 class='message-title-error'>Acceso no válido</h3>
            <a href='index.php' class='btn btn-netflix btn-block'>Volver al login</a>
        </div>
    </body>
    </html>";
}
?>
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/dbconn.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

session_start();

if (isset($_GET['error'])) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['code'])) {
    header('Location: index.php');
    exit();
}

$client = new Google\Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

if (isset($token['error'])) {
    header('Location: index.php');
    exit();
}

$client->setAccessToken($token);

$google_oauth = new Google\Service\Oauth2($client);
$google_user  = $google_oauth->userinfo->get();

$email    = $google_user->email;
$nombre   = $google_user->givenName  ?? '';
$apellido = $google_user->familyName ?? '';

$email_safe = mysqli_real_escape_string($conex, $email);

$sql = "SELECT * FROM registronuevo WHERE email = '$email_safe' LIMIT 1";
$res = mysqli_query($conex, $sql);

if (mysqli_num_rows($res) == 1) {
    // Usuario ya existe — iniciar sesión directo
    $row = mysqli_fetch_assoc($res);

    $_SESSION['usuario']         = $row['user'];
    $_SESSION['usuario_id']      = $row['id'];
    $_SESSION['nombre_completo'] = $row['nombre'] . ' ' . $row['apellido'];
    $_SESSION['rol']             = $row['rol'];

    require_once __DIR__ . '/auth.php';
    redirigir_por_rol($_SESSION['rol']);

} else {
    // Usuario nuevo — se registra como alumno directo
    $nombre_safe   = mysqli_real_escape_string($conex, $nombre);
    $apellido_safe = mysqli_real_escape_string($conex, $apellido);

    $user_base = strtolower(explode('@', $email)[0]);
    $user_base = preg_replace('/[^a-z0-9]/', '', $user_base);
    $username  = $user_base;

    $check = "SELECT id FROM registronuevo WHERE user = '" . mysqli_real_escape_string($conex, $username) . "' LIMIT 1";
    $i = 1;
    while (mysqli_num_rows(mysqli_query($conex, $check)) > 0) {
        $username = $user_base . $i;
        $check = "SELECT id FROM registronuevo WHERE user = '" . mysqli_real_escape_string($conex, $username) . "' LIMIT 1";
        $i++;
    }

    $pass_random = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

    $insert = "INSERT INTO registronuevo (nombre, apellido, email, user, pass, rol)
               VALUES ('$nombre_safe', '$apellido_safe', '$email_safe',
                       '" . mysqli_real_escape_string($conex, $username) . "',
                       '$pass_random', 'alumno')";

    mysqli_query($conex, $insert);

    // Iniciar sesión directamente como alumno
    $_SESSION['usuario']         = $username;
    $_SESSION['usuario_id']      = mysqli_insert_id($conex);
    $_SESSION['nombre_completo'] = $nombre . ' ' . $apellido;
    $_SESSION['rol']             = 'alumno';

    require_once __DIR__ . '/auth.php';
    redirigir_por_rol('alumno');
}
?>

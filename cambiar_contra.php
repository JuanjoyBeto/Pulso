<?php
require_once 'auth.php';
// Conexión centralizada a la base de datos
require_once 'dbconn.php';

// Validar si el usuario realmente ha iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$mensaje_feedback = "";
$usuario_activo = $_SESSION['usuario'];

if (isset($_POST['Cambiar'])) {
    // Validar que ningún campo llegue vacío
    if (!empty($_POST['pass_actual']) && !empty($_POST['pass_nueva']) && !empty($_POST['pass_conf'])) {
        
        $pass_actual = $_POST['pass_actual'];
        $pass_nueva  = $_POST['pass_nueva'];
        $pass_conf   = $_POST['pass_conf'];

        // 1. Verificar si las nuevas contraseñas coinciden entre sí
        if ($pass_nueva !== $pass_conf) {
            $mensaje_feedback = "<div class='alert alert-danger'>❌ Las nuevas contraseñas no coinciden.</div>";
        } else {
            // Sanitizar el nombre de usuario de la sesión
            $usuario_clean = mysqli_real_escape_string($conex, $usuario_activo);
            
            // 2. Obtener el hash actual de la base de datos para verificarlo
            $sql = "SELECT pass FROM registronuevo WHERE user = '$usuario_clean' LIMIT 1";
            $resultado = mysqli_query($conex, $sql);

            if ($resultado && mysqli_num_rows($resultado) == 1) {
                $row = mysqli_fetch_assoc($resultado);
                $hash_actual = $row['pass'];

                // 3. Verificar si la contraseña actual ingresada es correcta
                if (password_verify($pass_actual, $hash_actual)) {
                    
                    // 4. Cifrar la nueva contraseña de forma segura
                    $nuevo_hash = password_hash($pass_nueva, PASSWORD_DEFAULT, array("cost" => 10));
                    
                    // 5. Actualizar en la base de datos
                    $update_sql = "UPDATE registronuevo SET pass = '$nuevo_hash' WHERE user = '$usuario_clean'";
                    
                    if (mysqli_query($conex, $update_sql)) {
                        $mensaje_feedback = "
                        <div class='alert alert-success text-center'>
                            <h4>¡Contraseña actualizada! 🎉</h4>
                            <p>Serás redirigido al panel en 3 segundos...</p>
                        </div>
                        <script>
                            setTimeout(function(){
                                window.location.href = 'accesocorrecto.php';
                            }, 3000);
                        </script>";
                    } else {
                        $mensaje_feedback = "<div class='alert alert-danger'>❌ Error interno al actualizar la base de datos.</div>";
                    }
                } else {
                    $mensaje_feedback = "<div class='alert alert-danger'>❌ La contraseña actual introducida es incorrecta.</div>";
                }
            } else {
                $mensaje_feedback = "<div class='alert alert-danger'>❌ Error al identificar el usuario activo.</div>";
            }
        }
    } else {
        $mensaje_feedback = "<div class='alert alert-danger'>❌ Por favor, complete todos los campos obligatorios.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Netflix Style</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="password-page">

    <div class="crypto-container">
        <h2>Cambiar Contraseña</h2>
        
        <?php echo $mensaje_feedback; ?>

        <form action="cambiar_contra.php" method="post">
            
            <div class="form-group m-0">
                <label class="text-muted small">Contraseña Actual</label>
                <input type="password" name="pass_actual" class="form-control-netflix w-100" placeholder="Introduce tu clave actual" required>
            </div>
            
            <div class="form-group m-0">
                <label class="text-muted small">Nueva Contraseña</label>
                <input type="password" name="pass_nueva" class="form-control-netflix w-100" placeholder="Nueva contraseña" required>
            </div>

            <div class="form-group m-0">
                <label class="text-muted small">Confirmar Nueva Contraseña</label>
                <input type="password" name="pass_conf" class="form-control-netflix w-100" placeholder="Repite la nueva contraseña" required>
            </div>
            
            <button type="submit" name="Cambiar" class="btn btn-netflix btn-block">Guardar Cambios</button>
        </form>

        <a href="accesocorrecto.php" class="back-link">Volver al Panel</a>
    </div>

</body>
</html>

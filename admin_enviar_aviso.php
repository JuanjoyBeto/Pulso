<?php
require_once 'auth.php';
requerir_rol(['administrador']);
require_once 'dbconn.php';
require_once 'mailer.php';

if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['mensaje'])) {
    $mensaje = mysqli_real_escape_string($conex, trim($_POST['mensaje']));
    $tipo    = $_POST['tipo'] ?? 'todos';
    $msg_txt = trim($_POST['mensaje']);

    if ($tipo === 'todos') {
        $res = mysqli_query($conex, "SELECT id, nombre, apellido, email FROM registronuevo WHERE rol='alumno'");
        while ($al = mysqli_fetch_assoc($res)) {
            $aid = (int)$al['id'];
            mysqli_query($conex, "INSERT INTO notificaciones (alumno_id, mensaje, fecha) VALUES ($aid, '$mensaje', NOW())");
            // Mail
            if (!empty($al['email'])) {
                enviarMail(
                    $al['email'],
                    $al['nombre'] . ' ' . $al['apellido'],
                    '📍 Mensaje de Training Point',
                    nl2br(htmlspecialchars($msg_txt))
                );
            }
        }
    } elseif ($tipo === 'alumno' && !empty($_POST['alumno_id'])) {
        $aid = (int)$_POST['alumno_id'];
        mysqli_query($conex, "INSERT INTO notificaciones (alumno_id, mensaje, fecha) VALUES ($aid, '$mensaje', NOW())");
        $res = mysqli_query($conex, "SELECT nombre, apellido, email FROM registronuevo WHERE id=$aid LIMIT 1");
        $al = mysqli_fetch_assoc($res);
        if ($al && !empty($al['email'])) {
            enviarMail(
                $al['email'],
                $al['nombre'] . ' ' . $al['apellido'],
                '📍 Mensaje de Training Point',
                nl2br(htmlspecialchars($msg_txt))
            );
        }
    } elseif ($tipo === 'clase' && !empty($_POST['clase_id'])) {
        $cid = (int)$_POST['clase_id'];
        $res = mysqli_query($conex, "SELECT r.id, r.nombre, r.apellido, r.email FROM inscripciones i JOIN registronuevo r ON i.alumno_id=r.id WHERE i.clase_id=$cid");
        while ($al = mysqli_fetch_assoc($res)) {
            $aid = (int)$al['id'];
            mysqli_query($conex, "INSERT INTO notificaciones (alumno_id, mensaje, fecha) VALUES ($aid, '$mensaje', NOW())");
            if (!empty($al['email'])) {
                enviarMail(
                    $al['email'],
                    $al['nombre'] . ' ' . $al['apellido'],
                    '📍 Mensaje de Training Point',
                    nl2br(htmlspecialchars($msg_txt))
                );
            }
        }
    }
}
header('Location: acceso_administrador.php');
exit();

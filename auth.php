<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

// Créditos vencidos: se ponen en 0 solos (una vez por día alcanza, pero es una consulta rápida)
mysqli_query($conex, "UPDATE registronuevo SET creditos = 0 WHERE creditos > 0 AND vencimiento_creditos IS NOT NULL AND vencimiento_creditos < CURDATE()");

function usuario_autenticado()
{
    return isset($_SESSION['usuario']);
}

function rol_actual()
{
    return isset($_SESSION['rol']) ? $_SESSION['rol'] : 'alumno';
}

function redirigir_por_rol($rol)
{
    $destinos = array(
        'alumno'         => 'acceso_alumno.php',
        'administrador'  => 'acceso_administrador.php'
    );

    $destino = isset($destinos[$rol]) ? $destinos[$rol] : $destinos['alumno'];
    header("Location: " . $destino);
    exit();
}

function requerir_login()
{
    if (!usuario_autenticado()) {
        header("Location: index.php");
        exit();
    }
}

function requerir_rol($roles_permitidos)
{
    requerir_login();

    if (!in_array(rol_actual(), $roles_permitidos)) {
        redirigir_por_rol(rol_actual());
    }
}

function nombre_usuario_actual()
{
    return isset($_SESSION['nombre_completo']) ? $_SESSION['nombre_completo'] : $_SESSION['usuario'];
}

function inicial_usuario_actual()
{
    return strtoupper(substr($_SESSION['usuario'], 0, 1));
}
?>

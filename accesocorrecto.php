<?php
require_once 'auth.php';
requerir_login();
redirigir_por_rol(rol_actual());

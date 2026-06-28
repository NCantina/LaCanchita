<?php
session_start();
require_once 'config/dist/script/php/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$input    = trim($_POST['username'] ?? '');
$password = $_POST['password']      ?? '';

if (!$input || !$password) {
    $_SESSION['login_error'] = 'Ingresá tu usuario/email y contraseña.';
    header('Location: login.php');
    exit;
}

$stmt = mysqli_prepare($link,
    "SELECT USUARIOS_ID, USUARIOS_NOMBRE, USUARIOS_APELLIDO, USUARIOS_EMAIL, USUARIOS_PASSWORD, PERFIL_ID, ACTIVO
     FROM usuarios
     WHERE USUARIOS_EMAIL = ? OR USUARIOS_DNI = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'ss', $input, $input);
mysqli_stmt_execute($stmt);
$rs   = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$user || !password_verify($password, $user['USUARIOS_PASSWORD'])) {
    $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
    header('Location: login.php');
    exit;
}

if ((int)$user['ACTIVO'] === 0) {
    $_SESSION['login_error'] = 'Tu cuenta está pendiente de aprobación. El administrador la activará en breve.';
    header('Location: login.php');
    exit;
}

// Evitar fijación de sesión: nuevo ID al elevar privilegios (login)
session_regenerate_id(true);

$_SESSION['usuario_id']       = $user['USUARIOS_ID'];
$_SESSION['usuario_nombre']   = $user['USUARIOS_NOMBRE'];
$_SESSION['usuario_apellido'] = $user['USUARIOS_APELLIDO'];
$_SESSION['usuario_email']    = $user['USUARIOS_EMAIL'];
$_SESSION['usuario_perfil']   = (int)$user['PERFIL_ID'];

// Redirigir según perfil
if ((int)$user['PERFIL_ID'] === 5) {
    header('Location: view/maquetaCliente/LaCanchitaCliente.php');
} elseif (in_array((int)$user['PERFIL_ID'], [3, 4])) {
    header('Location: view/maquetaEncargado/PanelEncargado.php');
} else {
    header('Location: view/maquetaAdmin/Dashboard.php');
}
exit;

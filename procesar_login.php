<?php
session_start();
require_once 'config/dist/script/php/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$input    = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!$input || !$password) {
    $_SESSION['login_error'] = 'Ingresá tu usuario/email y contraseña.';
    header('Location: login.php');
    exit;
}

$input_e = mysqli_real_escape_string($link, $input);

$sql = "SELECT USUARIOS_ID, USUARIOS_NOMBRE, USUARIOS_APELLIDO, USUARIOS_EMAIL, USUARIOS_PASSWORD
        FROM usuarios
        WHERE USUARIOS_EMAIL = '$input_e' OR USUARIOS_NOMBRE = '$input_e'
        LIMIT 1";

$rs = mysqli_query($link, $sql);

if (!$rs || mysqli_num_rows($rs) === 0) {
    $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
    header('Location: login.php');
    exit;
}

$user = mysqli_fetch_assoc($rs);

if (!password_verify($password, $user['USUARIOS_PASSWORD'])) {
    $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
    header('Location: login.php');
    exit;
}

$_SESSION['usuario_id']       = $user['USUARIOS_ID'];
$_SESSION['usuario_nombre']   = $user['USUARIOS_NOMBRE'];
$_SESSION['usuario_apellido'] = $user['USUARIOS_APELLIDO'];
$_SESSION['usuario_email']    = $user['USUARIOS_EMAIL'];

header('Location: view/maquetaCliente/LaCanchitaCliente.php');
exit;

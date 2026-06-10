<?php
session_start();
require_once 'config/dist/script/php/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

$nombre    = trim($_POST['nombre'] ?? '');
$apellido  = trim($_POST['apellido'] ?? '');
$dni       = trim($_POST['dni'] ?? '');
$email     = trim($_POST['email'] ?? '');
$telefono  = trim($_POST['telefono'] ?? '');
$password  = $_POST['password'] ?? '';
$password2 = $_POST['password2'] ?? '';

$errors = [];

if (!$nombre || !$apellido || !$dni || !$email || !$telefono || !$password || !$password2) {
    $errors[] = 'Todos los campos son obligatorios.';
}
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'El email ingresado no es válido.';
}
if ($password && strlen($password) < 6) {
    $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
}
if ($password && $password !== $password2) {
    $errors[] = 'Las contraseñas no coinciden.';
}

if ($errors) {
    $_SESSION['registro_error'] = implode('<br>', $errors);
    $_SESSION['registro_data']  = array_diff_key($_POST, ['password' => '', 'password2' => '']);
    header('Location: register.php');
    exit;
}

$nombre_e   = mysqli_real_escape_string($link, $nombre);
$apellido_e = mysqli_real_escape_string($link, $apellido);
$dni_e      = mysqli_real_escape_string($link, $dni);
$email_e    = mysqli_real_escape_string($link, $email);
$telefono_e = mysqli_real_escape_string($link, $telefono);
$hash       = password_hash($password, PASSWORD_DEFAULT);
$perfil     = 2; // perfil cliente por defecto

$sql = "CALL sp_insertar_usuario('$nombre_e', '$apellido_e', '$dni_e', '$email_e', '$telefono_e', '$hash', '$perfil')";
$rs  = mysqli_query($link, $sql);

if (!$rs) {
    $_SESSION['registro_error'] = 'Error al registrar el usuario. Intentá nuevamente.';
    $_SESSION['registro_data']  = array_diff_key($_POST, ['password' => '', 'password2' => '']);
    header('Location: register.php');
    exit;
}

$_SESSION['registro_ok'] = 'Registro exitoso. Ya podés iniciar sesión.';
header('Location: login.php');
exit;

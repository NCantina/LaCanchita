<?php
session_start();
require_once 'config/dist/script/php/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php'); exit;
}

$nombre   = trim($_POST['nombre']   ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$dni      = trim($_POST['dni']      ?? '');
$email    = trim($_POST['email']    ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$password  = $_POST['password']  ?? '';
$password2 = $_POST['password2'] ?? '';

$errors = [];
if (!$nombre || !$apellido || !$dni || !$email || !$telefono || !$password || !$password2)
    $errors[] = 'Todos los campos son obligatorios.';
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors[] = 'El email ingresado no es válido.';
if ($password && strlen($password) < 6)
    $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
if ($password && $password !== $password2)
    $errors[] = 'Las contraseñas no coinciden.';
if ($dni && !preg_match('/^\d{7,8}$/', $dni))
    $errors[] = 'El DNI debe tener entre 7 y 8 dígitos.';

if ($errors) {
    $_SESSION['registro_error'] = implode('<br>', $errors);
    $_SESSION['registro_data']  = array_diff_key($_POST, ['password'=>'','password2'=>'']);
    header('Location: register.php'); exit;
}

$eNombre   = mysqli_real_escape_string($link, $nombre);
$eApellido = mysqli_real_escape_string($link, $apellido);
$eDni      = mysqli_real_escape_string($link, $dni);
$eEmail    = mysqli_real_escape_string($link, $email);
$eTel      = mysqli_real_escape_string($link, $telefono);

// Verificar duplicados
$dup = mysqli_fetch_assoc(mysqli_query($link,
    "SELECT USUARIOS_ID,
            USUARIOS_EMAIL='$eEmail' AS es_email
     FROM usuarios
     WHERE USUARIOS_EMAIL='$eEmail' OR USUARIOS_DNI='$eDni'
     LIMIT 1"));

if ($dup) {
    $_SESSION['registro_error'] = $dup['es_email']
        ? 'Ya existe una cuenta con ese email.'
        : 'Ya existe una cuenta con ese DNI.';
    $_SESSION['registro_data'] = array_diff_key($_POST, ['password'=>'','password2'=>'']);
    header('Location: register.php'); exit;
}

$hash = mysqli_real_escape_string($link, password_hash($password, PASSWORD_DEFAULT));

// Clientes (perfil 5) se activan automáticamente — no requieren aprobación del SA
$ok = mysqli_query($link,
    "INSERT INTO usuarios
        (USUARIOS_NOMBRE, USUARIOS_APELLIDO, USUARIOS_DNI, USUARIOS_EMAIL,
         USUARIOS_TELEFONO, USUARIOS_PASSWORD, PERFIL_ID, ACTIVO)
     VALUES
        ('$eNombre','$eApellido','$eDni','$eEmail','$eTel','$hash', 5, 1)"
);

if (!$ok) {
    $_SESSION['registro_error'] = 'Error al registrar. Intentá nuevamente.';
    $_SESSION['registro_data']  = array_diff_key($_POST, ['password'=>'','password2'=>'']);
    header('Location: register.php'); exit;
}

// Auto-login después del registro
$newId = mysqli_insert_id($link);
$user  = mysqli_fetch_assoc(mysqli_query($link,
    "SELECT u.USUARIOS_ID, u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO,
            u.PERFIL_ID, u.ACTIVO
     FROM usuarios u WHERE u.USUARIOS_ID=$newId"));

$_SESSION['usuario_id']       = $user['USUARIOS_ID'];
$_SESSION['usuario_nombre']   = $user['USUARIOS_NOMBRE'];
$_SESSION['usuario_apellido'] = $user['USUARIOS_APELLIDO'];
$_SESSION['usuario_perfil']   = (int)$user['PERFIL_ID'];
$_SESSION['registro_ok']      = '¡Bienvenido/a ' . $user['USUARIOS_NOMBRE'] . '! Tu cuenta está activa.';

header('Location: view/maquetaCliente/LaCanchitaCliente.php');
exit;

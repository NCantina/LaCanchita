<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok'=>false,'msg'=>'Sin sesión.']); exit;
}

$uid    = (int)$_SESSION['usuario_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
function resp($ok,$msg,$data=null){ echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data]); exit; }
function e($link,$v){ return mysqli_real_escape_string($link,trim($v??'')); }

switch($action) {

case 'get':
    $r = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT USUARIOS_ID, USUARIOS_NOMBRE, USUARIOS_APELLIDO,
                USUARIOS_EMAIL, USUARIOS_TELEFONO, USUARIOS_DNI, PERFIL_ID
         FROM usuarios WHERE USUARIOS_ID=$uid"));
    if (!$r) resp(false,'Usuario no encontrado.');
    resp(true,'',$r);

case 'update':
    $nombre   = e($link,$_POST['nombre']   ?? '');
    $apellido = e($link,$_POST['apellido'] ?? '');
    $telefono = e($link,$_POST['telefono'] ?? '');
    $email    = e($link,$_POST['email']    ?? '');
    $pass     = $_POST['password']         ?? '';
    $pass2    = $_POST['password2']        ?? '';

    if (!$nombre)   resp(false,'El nombre es obligatorio.');
    if (!$apellido) resp(false,'El apellido es obligatorio.');
    if (!$email || !filter_var($_POST['email']??'', FILTER_VALIDATE_EMAIL))
        resp(false,'Email inválido.');

    // Verificar email duplicado en otro usuario
    $dup = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT USUARIOS_ID FROM usuarios
         WHERE USUARIOS_EMAIL='$email' AND USUARIOS_ID!=$uid LIMIT 1"));
    if ($dup) resp(false,'Ese email ya está en uso por otra cuenta.');

    $passSQL = '';
    if ($pass !== '') {
        if (strlen($pass) < 6) resp(false,'La contraseña debe tener al menos 6 caracteres.');
        if ($pass !== $pass2)  resp(false,'Las contraseñas no coinciden.');
        $hash    = e($link, password_hash($pass, PASSWORD_DEFAULT));
        $passSQL = ", USUARIOS_PASSWORD='$hash'";
    }

    mysqli_query($link,
        "UPDATE usuarios SET
            USUARIOS_NOMBRE='$nombre', USUARIOS_APELLIDO='$apellido',
            USUARIOS_EMAIL='$email', USUARIOS_TELEFONO='$telefono'
            $passSQL
         WHERE USUARIOS_ID=$uid");

    // Actualizar nombre en sesión
    $_SESSION['usuario_nombre']   = $nombre;
    $_SESSION['usuario_apellido'] = $apellido;

    resp(true,'Perfil actualizado correctamente.');

default:
    resp(false,'Acción no reconocida.');
}

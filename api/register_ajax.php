<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/dist/script/php/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit; }

$b        = json_decode(file_get_contents('php://input'), true) ?? [];
$nombre   = trim($b['nombre']   ?? '');
$apellido = trim($b['apellido'] ?? '');
$dni      = trim($b['dni']      ?? '');
$email    = trim($b['email']    ?? '');
$telefono = trim($b['telefono'] ?? '');
$password = $b['password']  ?? '';
$password2= $b['password2'] ?? '';

if (!$nombre || !$apellido || !$dni || !$email || !$telefono || !$password || !$password2)
    { echo json_encode(['ok'=>false,'msg'=>'Todos los campos son obligatorios.']); exit; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    { echo json_encode(['ok'=>false,'msg'=>'Email no válido.']); exit; }
if (strlen($password) < 6)
    { echo json_encode(['ok'=>false,'msg'=>'La contraseña debe tener al menos 6 caracteres.']); exit; }
if ($password !== $password2)
    { echo json_encode(['ok'=>false,'msg'=>'Las contraseñas no coinciden.']); exit; }
if (!preg_match('/^\d{7,8}$/', $dni))
    { echo json_encode(['ok'=>false,'msg'=>'El DNI debe tener 7 u 8 dígitos.']); exit; }

$eNombre   = mysqli_real_escape_string($link, $nombre);
$eApellido = mysqli_real_escape_string($link, $apellido);
$eDni      = mysqli_real_escape_string($link, $dni);
$eEmail    = mysqli_real_escape_string($link, $email);
$eTel      = mysqli_real_escape_string($link, $telefono);

$dup = mysqli_fetch_assoc(mysqli_query($link,
    "SELECT USUARIOS_EMAIL='$eEmail' AS es_email FROM usuarios
     WHERE USUARIOS_EMAIL='$eEmail' OR USUARIOS_DNI='$eDni' LIMIT 1"
));
if ($dup) {
    $msg = $dup['es_email'] ? 'Ya existe una cuenta con ese email.' : 'Ya existe una cuenta con ese DNI.';
    echo json_encode(['ok'=>false,'msg'=>$msg]); exit;
}

$hash = mysqli_real_escape_string($link, password_hash($password, PASSWORD_DEFAULT));
$ok = mysqli_query($link,
    "INSERT INTO usuarios (USUARIOS_NOMBRE,USUARIOS_APELLIDO,USUARIOS_DNI,USUARIOS_EMAIL,
      USUARIOS_TELEFONO,USUARIOS_PASSWORD,PERFIL_ID,ACTIVO)
     VALUES ('$eNombre','$eApellido','$eDni','$eEmail','$eTel','$hash',5,1)"
);
if (!$ok) { echo json_encode(['ok'=>false,'msg'=>'Error al registrar. Intentá de nuevo.']); exit; }

$newId = mysqli_insert_id($link);
$user = mysqli_fetch_assoc(mysqli_query($link,
    "SELECT USUARIOS_ID,USUARIOS_NOMBRE,USUARIOS_APELLIDO,PERFIL_ID FROM usuarios WHERE USUARIOS_ID=$newId"
));
$_SESSION['usuario_id']       = $user['USUARIOS_ID'];
$_SESSION['usuario_nombre']   = $user['USUARIOS_NOMBRE'];
$_SESSION['usuario_apellido'] = $user['USUARIOS_APELLIDO'];
$_SESSION['usuario_email']    = $eEmail;
$_SESSION['usuario_perfil']   = 5;

echo json_encode(['ok'=>true,'nombre'=>$user['USUARIOS_NOMBRE'],'perfil'=>5]);

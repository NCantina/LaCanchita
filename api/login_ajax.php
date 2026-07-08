<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/dist/script/php/conn.php';
require_once __DIR__ . '/../config/dist/script/php/ratelimit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit; }

// Anti fuerza-bruta: máx 15 intentos por IP cada 5 minutos
rate_limit_guard_json($link, 'login', 15, 300);

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$input    = trim($body['username'] ?? '');
$password = $body['password'] ?? '';

if (!$input || !$password) { echo json_encode(['ok'=>false,'msg'=>'Completá usuario y contraseña.']); exit; }

$stmt = mysqli_prepare($link,
    "SELECT USUARIOS_ID, USUARIOS_NOMBRE, USUARIOS_APELLIDO, USUARIOS_EMAIL,
            USUARIOS_PASSWORD, PERFIL_ID, ACTIVO
     FROM usuarios WHERE USUARIOS_EMAIL=? OR USUARIOS_DNI=? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'ss', $input, $input);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user || !password_verify($password, $user['USUARIOS_PASSWORD'])) {
    echo json_encode(['ok'=>false,'msg'=>'Usuario o contraseña incorrectos.']); exit;
}
if ((int)$user['ACTIVO'] === 0) {
    echo json_encode(['ok'=>false,'msg'=>'Tu cuenta está pendiente de aprobación.']); exit;
}

// Evitar fijación de sesión: nuevo ID al elevar privilegios (login)
session_regenerate_id(true);

$_SESSION['usuario_id']       = $user['USUARIOS_ID'];
$_SESSION['usuario_nombre']   = $user['USUARIOS_NOMBRE'];
$_SESSION['usuario_apellido'] = $user['USUARIOS_APELLIDO'];
$_SESSION['usuario_email']    = $user['USUARIOS_EMAIL'];
$_SESSION['usuario_perfil']   = (int)$user['PERFIL_ID'];

echo json_encode(['ok'=>true,'nombre'=>$user['USUARIOS_NOMBRE'],'perfil'=>(int)$user['PERFIL_ID']]);

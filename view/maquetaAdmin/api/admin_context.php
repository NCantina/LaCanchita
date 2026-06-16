<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';

require_perfil(1); // solo SuperAdmin

$action = $_POST['action'] ?? $_GET['action'] ?? '';
function resp($ok, $msg, $data = null) { echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data]); exit; }

switch ($action) {

case 'set':
    $id = (int)($_POST['dueno_id'] ?? 0);
    if (!$id) resp(false, 'ID de dueño requerido.');

    $r = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT USUARIOS_ID, USUARIOS_NOMBRE, USUARIOS_APELLIDO
         FROM usuarios WHERE USUARIOS_ID=$id AND PERFIL_ID=2"));
    if (!$r) resp(false, 'Dueño no encontrado.');

    // tenancy.php llama session_write_close(); hay que reabrir para escribir
    session_start();
    $_SESSION['admin_as_dueno']        = $id;
    $_SESSION['admin_as_dueno_nombre'] = $r['USUARIOS_NOMBRE'] . ' ' . $r['USUARIOS_APELLIDO'];
    session_write_close();

    resp(true, 'Contexto establecido.', [
        'dueno_id'     => $id,
        'dueno_nombre' => $_SESSION['admin_as_dueno_nombre'],
    ]);

case 'clear':
    session_start();
    unset($_SESSION['admin_as_dueno'], $_SESSION['admin_as_dueno_nombre']);
    session_write_close();
    resp(true, 'Contexto limpiado.');

case 'current':
    $id = $_SESSION['admin_as_dueno'] ?? null;
    resp(true, '', [
        'dueno_id'     => $id ? (int)$id : null,
        'dueno_nombre' => $_SESSION['admin_as_dueno_nombre'] ?? null,
    ]);

default:
    resp(false, 'Acción no reconocida.');
}

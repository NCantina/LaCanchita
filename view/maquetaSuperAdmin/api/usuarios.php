<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';

require_perfil(1);

function resp($ok,$msg,$data=null){ echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE); exit; }
function e($l,$v){ return mysqli_real_escape_string($l,trim($v??'')); }

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch($action) {

// ── LISTAR (paginado, filtrable) ─────────────────────────────────────────
case 'listar':
    $page    = max(1,(int)($_GET['page']??1));
    $perPage = 20;
    $offset  = ($page-1)*$perPage;
    $q       = e($link, $_GET['q']??'');
    $perfil  = (int)($_GET['perfil_id']??0);
    $activo  = $_GET['activo']??'';

    $where = ['1=1'];
    if ($q) $where[] = "(u.USUARIOS_NOMBRE LIKE '%$q%' OR u.USUARIOS_APELLIDO LIKE '%$q%'
                         OR u.USUARIOS_EMAIL LIKE '%$q%' OR u.USUARIOS_DNI LIKE '%$q%'
                         OR u.USUARIOS_TELEFONO LIKE '%$q%')";
    if ($perfil) $where[] = "u.PERFIL_ID=$perfil";
    if ($activo !== '') $where[] = "u.ACTIVO=".(int)$activo;
    $w = implode(' AND ', $where);

    $total = (int)(mysqli_fetch_assoc(mysqli_query($link,"SELECT COUNT(*) AS n FROM usuarios u WHERE $w"))['n'] ?? 0);

    $rows = [];
    $qr = mysqli_query($link,"
        SELECT u.USUARIOS_ID, u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO,
               u.USUARIOS_EMAIL, u.USUARIOS_TELEFONO, u.USUARIOS_DNI,
               u.PERFIL_ID, u.ACTIVO, u.DUENO_ID, p.PERFIL_NOMBRE,
               CONCAT(du.USUARIOS_NOMBRE,' ',du.USUARIOS_APELLIDO) AS DUENO_FULL,
               (SELECT COUNT(*) FROM complejo c WHERE c.USUARIOS_ID=u.USUARIOS_ID AND c.ACTIVO=1) AS TOTAL_PREDIOS
        FROM usuarios u
        JOIN perfil p ON p.PERFIL_ID=u.PERFIL_ID
        LEFT JOIN usuarios du ON du.USUARIOS_ID=u.DUENO_ID
        WHERE $w
        ORDER BY u.USUARIOS_ID DESC
        LIMIT $perPage OFFSET $offset
    ");
    while ($r=mysqli_fetch_assoc($qr)) $rows[]=$r;

    resp(true,'',[
        'users'    => $rows,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => max(1,(int)ceil($total/$perPage)),
    ]);

// ── GET SINGLE ───────────────────────────────────────────────────────────
case 'get':
    $id = (int)($_GET['id']??0);
    if (!$id) resp(false,'ID inválido.');
    $u = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT u.USUARIOS_ID,u.USUARIOS_NOMBRE,u.USUARIOS_APELLIDO,u.USUARIOS_EMAIL,
                u.USUARIOS_TELEFONO,u.USUARIOS_DNI,u.PERFIL_ID,u.ACTIVO,u.DUENO_ID,p.PERFIL_NOMBRE
         FROM usuarios u JOIN perfil p ON p.PERFIL_ID=u.PERFIL_ID
         WHERE u.USUARIOS_ID=$id"
    ));
    if (!$u) resp(false,'Usuario no encontrado.');
    resp(true,'',$u);

// ── CREAR ────────────────────────────────────────────────────────────────
case 'crear':
    $nombre   = e($link,$_POST['nombre']??'');
    $apellido = e($link,$_POST['apellido']??'');
    $email    = trim($_POST['email']??'');
    $tel      = e($link,$_POST['telefono']??'');
    $dni      = e($link,$_POST['dni']??'');
    $perfilId = (int)($_POST['perfil_id']??5);
    $duenoId  = (int)($_POST['dueno_id']??0);
    $pass     = $_POST['password']??'';

    if (!$nombre)  resp(false,'Nombre obligatorio.');
    if (!$apellido) resp(false,'Apellido obligatorio.');
    if (!$email || !filter_var($email,FILTER_VALIDATE_EMAIL)) resp(false,'Email inválido.');
    if (!in_array($perfilId,[1,2,3,4,5])) resp(false,'Perfil inválido.');
    if (strlen($pass)<6) resp(false,'La contraseña debe tener al menos 6 caracteres.');
    if (in_array($perfilId,[3,4]) && !$duenoId) resp(false,'El staff requiere un dueño asignado.');

    $eEmail = e($link,$email);
    if (mysqli_fetch_assoc(mysqli_query($link,"SELECT USUARIOS_ID FROM usuarios WHERE USUARIOS_EMAIL='$eEmail'")))
        resp(false,'Ya existe un usuario con ese email.');

    $eDni = $dni ? e($link,$dni) : 'SIN-'.time().'-'.rand(10,99);
    if ($dni && mysqli_fetch_assoc(mysqli_query($link,"SELECT USUARIOS_ID FROM usuarios WHERE USUARIOS_DNI='$eDni' AND USUARIOS_DNI NOT LIKE 'SIN-%'")))
        resp(false,'Ya existe un usuario con ese DNI.');

    $hash    = e($link,password_hash($pass,PASSWORD_DEFAULT));
    $duenoSQ = in_array($perfilId,[3,4]) ? $duenoId : 'NULL';

    mysqli_query($link,
        "INSERT INTO usuarios (USUARIOS_NOMBRE,USUARIOS_APELLIDO,USUARIOS_DNI,USUARIOS_EMAIL,
         USUARIOS_TELEFONO,USUARIOS_PASSWORD,PERFIL_ID,DUENO_ID,ACTIVO)
         VALUES ('$nombre','$apellido','$eDni','$eEmail','$tel','$hash',$perfilId,$duenoSQ,1)"
    );
    resp(true,'Usuario creado correctamente.',['id'=>mysqli_insert_id($link)]);

// ── EDITAR DATOS ─────────────────────────────────────────────────────────
case 'editar':
    $id       = (int)($_POST['id']??0);
    $nombre   = e($link,$_POST['nombre']??'');
    $apellido = e($link,$_POST['apellido']??'');
    $email    = trim($_POST['email']??'');
    $tel      = e($link,$_POST['telefono']??'');
    $dni      = e($link,$_POST['dni']??'');

    if (!$id || !$nombre || !$apellido || !$email) resp(false,'Datos incompletos.');
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) resp(false,'Email inválido.');

    $eEmail = e($link,$email);
    $eDni   = e($link,$dni);

    if (mysqli_fetch_assoc(mysqli_query($link,
        "SELECT USUARIOS_ID FROM usuarios WHERE USUARIOS_EMAIL='$eEmail' AND USUARIOS_ID!=$id")))
        resp(false,'Email ya en uso por otro usuario.');

    if ($dni && !str_starts_with($dni,'SIN-') && mysqli_fetch_assoc(mysqli_query($link,
        "SELECT USUARIOS_ID FROM usuarios WHERE USUARIOS_DNI='$eDni' AND USUARIOS_ID!=$id AND USUARIOS_DNI NOT LIKE 'SIN-%'")))
        resp(false,'DNI ya en uso por otro usuario.');

    mysqli_query($link,
        "UPDATE usuarios SET USUARIOS_NOMBRE='$nombre',USUARIOS_APELLIDO='$apellido',
         USUARIOS_EMAIL='$eEmail',USUARIOS_TELEFONO='$tel',USUARIOS_DNI='$eDni'
         WHERE USUARIOS_ID=$id"
    );
    resp(true,'Usuario actualizado.');

// ── CAMBIAR PERFIL ───────────────────────────────────────────────────────
case 'cambiar_perfil':
    $id       = (int)($_POST['id']??0);
    $perfilId = (int)($_POST['perfil_id']??0);
    $duenoId  = (int)($_POST['dueno_id']??0);

    if (!$id || !$perfilId) resp(false,'Datos incompletos.');
    if (!in_array($perfilId,[1,2,3,4,5])) resp(false,'Perfil inválido.');
    if ($id===current_uid()) resp(false,'No podés cambiar tu propio perfil.');
    if (in_array($perfilId,[3,4]) && !$duenoId) resp(false,'El staff requiere un dueño asignado.');

    if (!mysqli_fetch_assoc(mysqli_query($link,"SELECT USUARIOS_ID FROM usuarios WHERE USUARIOS_ID=$id")))
        resp(false,'Usuario no encontrado.');

    $duenoSQ = in_array($perfilId,[3,4]) ? $duenoId : 'NULL';
    mysqli_query($link,"UPDATE usuarios SET PERFIL_ID=$perfilId,DUENO_ID=$duenoSQ WHERE USUARIOS_ID=$id");
    resp(true,'Perfil actualizado correctamente.');

// ── RESET PASSWORD ───────────────────────────────────────────────────────
case 'reset_password':
    $id   = (int)($_POST['id']??0);
    $pass = $_POST['password']??'';
    if (!$id) resp(false,'ID inválido.');
    if (strlen($pass)<6) resp(false,'La contraseña debe tener al menos 6 caracteres.');
    if (!mysqli_fetch_assoc(mysqli_query($link,"SELECT USUARIOS_ID FROM usuarios WHERE USUARIOS_ID=$id")))
        resp(false,'Usuario no encontrado.');
    $hash = e($link,password_hash($pass,PASSWORD_DEFAULT));
    mysqli_query($link,"UPDATE usuarios SET USUARIOS_PASSWORD='$hash' WHERE USUARIOS_ID=$id");
    resp(true,'Contraseña actualizada correctamente.');

// ── TOGGLE ACTIVO ────────────────────────────────────────────────────────
case 'toggle':
    $id = (int)($_POST['id']??0);
    if (!$id) resp(false,'ID inválido.');
    if ($id===current_uid()) resp(false,'No podés desactivar tu propia cuenta.');
    $u = mysqli_fetch_assoc(mysqli_query($link,"SELECT ACTIVO FROM usuarios WHERE USUARIOS_ID=$id"));
    if (!$u) resp(false,'No encontrado.');
    $nuevo = $u['ACTIVO'] ? 0 : 1;
    mysqli_query($link,"UPDATE usuarios SET ACTIVO=$nuevo WHERE USUARIOS_ID=$id");
    resp(true,$nuevo?'Usuario activado.':'Usuario desactivado.',['activo'=>$nuevo]);

// ── LISTAR DUEÑOS (para selector en create/cambiar perfil) ───────────────
case 'listar_duenos':
    $rows=[];
    $q=mysqli_query($link,
        "SELECT USUARIOS_ID,USUARIOS_NOMBRE,USUARIOS_APELLIDO
         FROM usuarios WHERE PERFIL_ID=2 AND ACTIVO=1 ORDER BY USUARIOS_NOMBRE,USUARIOS_APELLIDO"
    );
    while ($r=mysqli_fetch_assoc($q)) $rows[]=$r;
    resp(true,'',$rows);

default:
    resp(false,'Acción no reconocida.');
}

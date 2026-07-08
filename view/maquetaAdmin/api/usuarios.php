<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Acciones operativas de mostrador que también usa el staff (encargado/empleado).
// El resto del archivo sigue siendo solo para dueño/SA.
$STAFF_ACTIONS = ['crear_cliente_rapido', 'buscar_clientes'];
require_perfil(in_array($action, $STAFF_ACTIONS, true) ? 4 : 2);
function resp($ok,$msg,$data=null){ echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function e($link,$v){ return mysqli_real_escape_string($link,trim($v??'')); }

// Modo solo-lectura por mora: bloquear escritura del tenant (no las acciones de
// plataforma del SuperAdmin, que con assert_tenant_activo no se ven afectadas).
if (in_array($action, ['crear_staff','editar','toggle','asignar_canchas','crear_cliente_rapido'], true)) assert_tenant_activo($link);

switch($action) {

// ── LISTAR STAFF (dueño: su staff; superadmin: todos los usuarios) ──────
case 'listar_staff':
    if (is_superadmin()) {
        $where = "1=1";
    } else {
        $duenoId = current_uid();
        $where   = "u.PERFIL_ID IN (3,4) AND u.DUENO_ID=$duenoId";
    }
    $rows = [];
    $q = mysqli_query($link,"
        SELECT u.USUARIOS_ID, u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO,
               u.USUARIOS_EMAIL, u.USUARIOS_TELEFONO, u.USUARIOS_DNI,
               u.PERFIL_ID, u.ACTIVO, u.DUENO_ID,
               p.PERFIL_NOMBRE,
               du.USUARIOS_NOMBRE   AS DUENO_NOMBRE,
               du.USUARIOS_APELLIDO AS DUENO_APELLIDO,
               (SELECT COUNT(*) FROM cancha_encargado ce
                WHERE ce.USUARIOS_ID=u.USUARIOS_ID AND ce.ACTIVO=1) AS CANCHAS_ASIGNADAS
        FROM usuarios u
        JOIN perfil p ON p.PERFIL_ID=u.PERFIL_ID
        LEFT JOIN usuarios du ON du.USUARIOS_ID=u.DUENO_ID
        WHERE $where
        ORDER BY u.ACTIVO DESC, p.PERFIL_ID ASC, u.USUARIOS_NOMBRE ASC
    ");
    while ($r=mysqli_fetch_assoc($q)) $rows[]=$r;
    resp(true,'',$rows);

// ── LISTAR DUEÑOS (solo SuperAdmin) ────────────────────────────────────
case 'listar_duenos':
    if (!is_superadmin()) resp(false,'Sin permisos.');
    $rows = [];
    $q = mysqli_query($link,"
        SELECT u.USUARIOS_ID, u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO,
               u.USUARIOS_EMAIL, u.USUARIOS_TELEFONO, u.USUARIOS_DNI, u.ACTIVO,
               (SELECT COUNT(*) FROM complejo c
                WHERE c.USUARIOS_ID=u.USUARIOS_ID AND c.ACTIVO=1)                   AS TOTAL_PREDIOS,
               (SELECT COUNT(*) FROM complejo c
                JOIN cancha ca ON ca.COMPLEJO_ID=c.COMPLEJO_ID
                WHERE c.USUARIOS_ID=u.USUARIOS_ID AND ca.ACTIVO=1)                  AS TOTAL_CANCHAS,
               (SELECT COUNT(*) FROM usuarios st
                WHERE st.DUENO_ID=u.USUARIOS_ID AND st.ACTIVO=1)                    AS TOTAL_STAFF,
               (SELECT COUNT(*) FROM complejo c
                JOIN cancha ca ON ca.COMPLEJO_ID=c.COMPLEJO_ID
                JOIN reserva r  ON r.CANCHA_ID=ca.CANCHA_ID
                WHERE c.USUARIOS_ID=u.USUARIOS_ID
                  AND r.RESERVA_FECHA=CURDATE() AND r.ACTIVO=1)                     AS RESERVAS_HOY
        FROM usuarios u
        WHERE u.PERFIL_ID=2
        ORDER BY u.ACTIVO DESC, u.USUARIOS_NOMBRE ASC
    ");
    while ($r=mysqli_fetch_assoc($q)) $rows[]=$r;
    resp(true,'',$rows);

// ── CREAR STAFF ─────────────────────────────────────────────────────────
case 'crear_staff':
    $nombre   = e($link,$_POST['nombre']   ?? '');
    $apellido = e($link,$_POST['apellido'] ?? '');
    $dni      = e($link,$_POST['dni']      ?? '');
    $email    = e($link,$_POST['email']    ?? '');
    $tel      = e($link,$_POST['telefono'] ?? '');
    $perfilId = (int)($_POST['perfil_id']  ?? 3);
    $pass     = $_POST['password'] ?? '';

    if (!$nombre)   resp(false,'Nombre obligatorio.');
    if (!$apellido) resp(false,'Apellido obligatorio.');
    if (!$dni)      resp(false,'DNI obligatorio.');
    if (!$email || !filter_var($_POST['email']??'',FILTER_VALIDATE_EMAIL)) resp(false,'Email inválido.');
    $perfilesPermitidos = is_superadmin() ? [3,4,5] : [3,4]; // perfil 1 (SA) y 2 (Dueño) solo desde Dev Panel
    if (!in_array($perfilId,$perfilesPermitidos)) resp(false,'El perfil Dueño solo puede asignarse desde el Panel Desarrollador.');
    if (strlen($pass)<6) resp(false,'La contraseña debe tener al menos 6 caracteres.');

    // DUENO_ID: requerido solo para staff (3,4)
    if (in_array($perfilId,[3,4])) {
        $duenoId = is_dueno() ? current_uid() : ((int)($_POST['dueno_id']??0) ?: null);
        if (!$duenoId) resp(false,'Debe asignarse un dueño.');
    } else {
        $duenoId = null;
    }

    if (mysqli_fetch_assoc(mysqli_query($link,
        "SELECT USUARIOS_ID FROM usuarios WHERE USUARIOS_EMAIL='$email' OR USUARIOS_DNI='$dni'")))
        resp(false,'Ya existe un usuario con ese email o DNI.');

    $hash    = e($link,password_hash($pass,PASSWORD_DEFAULT));
    $duenoSQ = $duenoId ? (int)$duenoId : 'NULL';
    mysqli_query($link,
        "INSERT INTO usuarios (USUARIOS_NOMBRE,USUARIOS_APELLIDO,USUARIOS_DNI,USUARIOS_EMAIL,
         USUARIOS_TELEFONO,USUARIOS_PASSWORD,PERFIL_ID,DUENO_ID,ACTIVO)
         VALUES ('$nombre','$apellido','$dni','$email','$tel','$hash',$perfilId,$duenoSQ,1)"
    );
    resp(true,'Staff creado correctamente.',['id'=>mysqli_insert_id($link)]);

// ── CREAR DUEÑO (solo SuperAdmin) ──────────────────────────────────────
case 'crear_dueno':
    if (!is_superadmin()) resp(false,'Sin permisos.');
    $nombre   = e($link,$_POST['nombre']   ?? '');
    $apellido = e($link,$_POST['apellido'] ?? '');
    $dni      = e($link,$_POST['dni']      ?? '');
    $email    = e($link,$_POST['email']    ?? '');
    $tel      = e($link,$_POST['telefono'] ?? '');
    $pass     = $_POST['password'] ?? '';

    if (!$nombre)   resp(false,'Nombre obligatorio.');
    if (!$apellido) resp(false,'Apellido obligatorio.');
    if (!$dni)      resp(false,'DNI obligatorio.');
    if (!$email || !filter_var($_POST['email']??'',FILTER_VALIDATE_EMAIL)) resp(false,'Email inválido.');
    if (strlen($pass)<6) resp(false,'La contraseña debe tener al menos 6 caracteres.');

    if (mysqli_fetch_assoc(mysqli_query($link,
        "SELECT USUARIOS_ID FROM usuarios WHERE USUARIOS_EMAIL='$email' OR USUARIOS_DNI='$dni'")))
        resp(false,'Ya existe un usuario con ese email o DNI.');

    $hash = e($link,password_hash($pass,PASSWORD_DEFAULT));
    mysqli_query($link,
        "INSERT INTO usuarios (USUARIOS_NOMBRE,USUARIOS_APELLIDO,USUARIOS_DNI,USUARIOS_EMAIL,
         USUARIOS_TELEFONO,USUARIOS_PASSWORD,PERFIL_ID,ACTIVO)
         VALUES ('$nombre','$apellido','$dni','$email','$tel','$hash',2,0)"
    );
    resp(true,'Dueño creado correctamente.',['id'=>mysqli_insert_id($link)]);

// ── ACTIVAR DUEÑO (paso final del wizard) ───────────────────────────────
case 'activar_dueno':
    if (!is_superadmin()) resp(false,'Sin permisos.');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) resp(false,'ID inválido.');
    $u = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT PERFIL_ID FROM usuarios WHERE USUARIOS_ID=$id AND PERFIL_ID=2"));
    if (!$u) resp(false,'Dueño no encontrado.');
    mysqli_query($link,"UPDATE usuarios SET ACTIVO=1 WHERE USUARIOS_ID=$id");
    resp(true,'Cuenta activada correctamente.');

// ── EDITAR ──────────────────────────────────────────────────────────────
case 'editar':
    $id       = (int)($_POST['id']       ?? 0);
    $nombre   = e($link,$_POST['nombre']   ?? '');
    $apellido = e($link,$_POST['apellido'] ?? '');
    $dni      = e($link,$_POST['dni']      ?? '');
    $email    = e($link,$_POST['email']    ?? '');
    $tel      = e($link,$_POST['telefono'] ?? '');
    $pass     = $_POST['password'] ?? '';

    if (!$id || !$nombre || !$apellido || !$email) resp(false,'Datos incompletos.');

    $target = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT PERFIL_ID,DUENO_ID FROM usuarios WHERE USUARIOS_ID=$id"));
    if (!$target) resp(false,'Usuario no encontrado.');
    if (!is_superadmin() && (int)$target['DUENO_ID']!==current_uid())
        resp(false,'Sin permisos.');

    if (mysqli_fetch_assoc(mysqli_query($link,
        "SELECT USUARIOS_ID FROM usuarios WHERE (USUARIOS_EMAIL='$email' OR USUARIOS_DNI='$dni') AND USUARIOS_ID!=$id")))
        resp(false,'Email o DNI ya en uso por otro usuario.');

    $passSQL = '';
    if ($pass!=='') {
        if (strlen($pass)<6) resp(false,'Contraseña mínimo 6 caracteres.');
        $hash = e($link,password_hash($pass,PASSWORD_DEFAULT));
        $passSQL = ",USUARIOS_PASSWORD='$hash'";
    }
    mysqli_query($link,
        "UPDATE usuarios SET USUARIOS_NOMBRE='$nombre',USUARIOS_APELLIDO='$apellido',
         USUARIOS_DNI='$dni',USUARIOS_EMAIL='$email',USUARIOS_TELEFONO='$tel'$passSQL
         WHERE USUARIOS_ID=$id");
    resp(true,'Usuario actualizado.');

// ── TOGGLE ──────────────────────────────────────────────────────────────
case 'toggle':
    $id = (int)($_POST['id']??0);
    if (!$id) resp(false,'ID inválido.');
    if ($id===current_uid()) resp(false,'No podés desactivar tu propia cuenta.');

    $target = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT PERFIL_ID,DUENO_ID,ACTIVO FROM usuarios WHERE USUARIOS_ID=$id"));
    if (!$target) resp(false,'No encontrado.');
    if (!is_superadmin() && (int)$target['DUENO_ID']!==current_uid()) resp(false,'Sin permisos.');

    $nuevo = $target['ACTIVO'] ? 0 : 1;
    mysqli_query($link,"UPDATE usuarios SET ACTIVO=$nuevo WHERE USUARIOS_ID=$id");
    resp(true,$nuevo?'Usuario activado.':'Usuario desactivado.',['activo'=>$nuevo]);

// ── ASIGNAR CANCHAS A STAFF ─────────────────────────────────────────────
case 'asignar_canchas':
    $staffId = (int)($_POST['usuario_id']??0);
    $canchas = json_decode($_POST['canchas']??'[]',true);
    if (!$staffId) resp(false,'Usuario requerido.');

    $target = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT PERFIL_ID,DUENO_ID FROM usuarios WHERE USUARIOS_ID=$staffId"));
    if (!$target || !in_array((int)$target['PERFIL_ID'],[3,4])) resp(false,'Usuario no válido.');
    if (!is_superadmin() && (int)$target['DUENO_ID']!==current_uid()) resp(false,'Sin permisos.');

    mysqli_begin_transaction($link);
    try {
        mysqli_query($link,"DELETE FROM cancha_encargado WHERE USUARIOS_ID=$staffId");
        foreach ($canchas as $cid) {
            $cid=(int)$cid;
            if ($cid && can_cancha($link,$cid))
                mysqli_query($link,
                    "INSERT IGNORE INTO cancha_encargado (CANCHA_ID,USUARIOS_ID,ACTIVO) VALUES ($cid,$staffId,1)");
        }
        mysqli_commit($link);
        resp(true,'Canchas asignadas correctamente.');
    } catch(Exception $ex){ mysqli_rollback($link); resp(false,'Error: '.$ex->getMessage()); }

// ── CANCHAS DISPONIBLES PARA ASIGNAR ────────────────────────────────────
case 'canchas_asignables':
    $staffId = (int)($_GET['usuario_id']??0);
    if (!$staffId) resp(false,'Usuario requerido.');

    $target = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT DUENO_ID FROM usuarios WHERE USUARIOS_ID=$staffId"));
    if (!$target) resp(false,'No encontrado.');

    $duenoRef  = (int)$target['DUENO_ID'];
    $complejoIds = [];
    $qc = mysqli_query($link,"SELECT COMPLEJO_ID FROM complejo WHERE USUARIOS_ID=$duenoRef AND ACTIVO=1");
    while ($r=mysqli_fetch_assoc($qc)) $complejoIds[]=(int)$r['COMPLEJO_ID'];

    if (empty($complejoIds)) resp(true,'',['canchas'=>[],'asignadas'=>[]]);

    $inStr   = implode(',',$complejoIds);
    $canchas = [];
    $qca = mysqli_query($link,
        "SELECT ca.CANCHA_ID,ca.CANCHA_NOMBRE,co.COMPLEJO_NOMBRE
         FROM cancha ca JOIN complejo co ON co.COMPLEJO_ID=ca.COMPLEJO_ID
         WHERE ca.COMPLEJO_ID IN ($inStr) AND ca.ACTIVO=1
         ORDER BY co.COMPLEJO_NOMBRE,ca.CANCHA_NOMBRE");
    while ($r=mysqli_fetch_assoc($qca)) $canchas[]=$r;

    $asignadas = [];
    $qa = mysqli_query($link,
        "SELECT CANCHA_ID FROM cancha_encargado WHERE USUARIOS_ID=$staffId AND ACTIVO=1");
    while ($r=mysqli_fetch_assoc($qa)) $asignadas[]=(int)$r['CANCHA_ID'];

    resp(true,'',compact('canchas','asignadas'));

// ── CREAR CLIENTE RÁPIDO (walk-in, desde el panel de reservas) ────────────
case 'crear_cliente_rapido':
    require_perfil(4);

    $nombre   = e($link, $_POST['nombre']   ?? '');
    $apellido = e($link, $_POST['apellido'] ?? '');
    $tel      = e($link, $_POST['telefono'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $dni      = e($link, $_POST['dni']      ?? '');

    if (!$nombre)   resp(false, 'El nombre es obligatorio.');
    if (!$apellido) resp(false, 'El apellido es obligatorio.');
    if (!$tel)      resp(false, 'El teléfono es obligatorio.');

    // Email: si no lo tienen, generamos uno único interno
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = 'cliente-' . time() . '-' . rand(100,999) . '@walkin.lacanchita.local';
    }
    $eEmail = e($link, $email);

    // DNI: si no lo tienen, generamos uno placeholder único
    if (!$dni) {
        $dni = 'SIN-' . time() . '-' . rand(10,99);
    }
    $eDni = e($link, $dni);

    // Verificar duplicados reales (solo si el usuario proveyó email/dni reales)
    $dupCheck = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT USUARIOS_ID, USUARIOS_EMAIL='$eEmail' AS es_email
         FROM usuarios
         WHERE (USUARIOS_EMAIL='$eEmail' AND USUARIOS_EMAIL NOT LIKE '%@walkin.lacanchita.local')
            OR (USUARIOS_DNI='$eDni'     AND USUARIOS_DNI   NOT LIKE 'SIN-%')
         LIMIT 1"
    ));
    if ($dupCheck) {
        resp(false, $dupCheck['es_email']
            ? 'Ya existe un cliente con ese email. Buscalo en la lista.'
            : 'Ya existe un cliente con ese DNI. Buscalo en la lista.');
    }

    // Contraseña aleatoria — el cliente puede pedirla después por email
    $pass = bin2hex(random_bytes(6));
    $hash = e($link, password_hash($pass, PASSWORD_DEFAULT));

    mysqli_query($link,
        "INSERT INTO usuarios
           (USUARIOS_NOMBRE, USUARIOS_APELLIDO, USUARIOS_DNI, USUARIOS_EMAIL,
            USUARIOS_TELEFONO, USUARIOS_PASSWORD, PERFIL_ID, ACTIVO)
         VALUES ('$nombre','$apellido','$eDni','$eEmail','$tel','$hash', 5, 1)"
    );
    $newId = mysqli_insert_id($link);
    if (!$newId) resp(false, 'Error al crear el cliente.');

    resp(true, 'Cliente creado.', [
        'id'      => $newId,
        'nombre'  => "$nombre $apellido",
        'email'   => strpos($email, '@walkin.lacanchita.local') !== false ? '' : $email,
        'telefono'=> $tel,
    ]);

// ── BUSCAR CLIENTES (para crear reservas desde el panel) ──────────────────
case 'buscar_clientes':
    require_perfil(4);
    $q = e($link, $_GET['q'] ?? '');
    if (strlen($q) < 2) resp(true, '', []);

    // Privacidad multi-tenant: solo clientes con historial en los complejos del
    // tenant actual (no toda la base de clientes de la plataforma).
    $ids   = tenant_complejo_ids($link);            // null = SA (todos)
    $scope = tenant_where($ids, 'co.COMPLEJO_ID');  // "1=1" | "1=0" | "co.COMPLEJO_ID IN (..)"

    $rows = [];
    $qr = mysqli_query($link,
        "SELECT DISTINCT u.USUARIOS_ID, u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO,
                u.USUARIOS_EMAIL, u.USUARIOS_TELEFONO, u.USUARIOS_DNI
         FROM usuarios u
         JOIN reserva r   ON r.USUARIOS_ID = u.USUARIOS_ID
         JOIN cancha c    ON c.CANCHA_ID   = r.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID = c.COMPLEJO_ID
         WHERE u.PERFIL_ID=5 AND u.ACTIVO=1 AND $scope
           AND (u.USUARIOS_NOMBRE LIKE '%$q%'
             OR u.USUARIOS_APELLIDO LIKE '%$q%'
             OR u.USUARIOS_EMAIL LIKE '%$q%'
             OR u.USUARIOS_TELEFONO LIKE '%$q%'
             OR u.USUARIOS_DNI LIKE '%$q%')
         ORDER BY u.USUARIOS_APELLIDO, u.USUARIOS_NOMBRE
         LIMIT 10"
    );
    while ($r = mysqli_fetch_assoc($qr)) $rows[] = $r;
    resp(true, '', $rows);

// ── PENDIENTES ──────────────────────────────────────────────────────────
case 'pendientes':
    if (!is_superadmin()) resp(false,'Sin permisos.');
    $rows=[];
    $q=mysqli_query($link,
        "SELECT u.USUARIOS_ID,u.USUARIOS_NOMBRE,u.USUARIOS_APELLIDO,
                u.USUARIOS_EMAIL,u.USUARIOS_DNI,u.USUARIOS_TELEFONO,p.PERFIL_NOMBRE
         FROM usuarios u JOIN perfil p ON u.PERFIL_ID=p.PERFIL_ID
         WHERE u.ACTIVO=0 ORDER BY u.USUARIOS_ID DESC");
    while ($r=mysqli_fetch_assoc($q)) $rows[]=$r;
    resp(true,'',$rows);

case 'aprobar':
    if (!is_superadmin()) resp(false,'Sin permisos.');
    $id=(int)($_POST['id']??0);
    if (!$id) resp(false,'ID inválido.');
    mysqli_query($link,"UPDATE usuarios SET ACTIVO=1 WHERE USUARIOS_ID=$id AND ACTIVO=0");
    if (!mysqli_affected_rows($link)) resp(false,'No encontrado o ya activo.');
    resp(true,'Usuario aprobado.');

case 'rechazar':
    if (!is_superadmin()) resp(false,'Sin permisos.');
    $id=(int)($_POST['id']??0);
    if (!$id) resp(false,'ID inválido.');
    mysqli_query($link,"DELETE FROM usuarios WHERE USUARIOS_ID=$id AND ACTIVO=0");
    if (!mysqli_affected_rows($link)) resp(false,'No encontrado.');
    resp(true,'Cuenta eliminada.');

// ── LISTAR PAGINADO (solo SA) ────────────────────────────────────────────
case 'listar':
    if (!is_superadmin()) resp(false,'Sin permisos.');
    $page    = max(1,(int)($_GET['page']??1));
    $perPage = 20;
    $offset  = ($page-1)*$perPage;
    $q       = e($link,$_GET['q']??'');
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
    $rows  = [];
    $qr    = mysqli_query($link,"
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
    resp(true,'',['users'=>$rows,'total'=>$total,'page'=>$page,'pages'=>max(1,(int)ceil($total/$perPage))]);

// ── RESET PASSWORD (solo SA) ─────────────────────────────────────────────
case 'reset_password':
    if (!is_superadmin()) resp(false,'Sin permisos.');
    $id   = (int)($_POST['id']??0);
    $pass = $_POST['password']??'';
    if (!$id) resp(false,'ID inválido.');
    if (strlen($pass)<6) resp(false,'Mínimo 6 caracteres.');
    if (!mysqli_fetch_assoc(mysqli_query($link,"SELECT USUARIOS_ID FROM usuarios WHERE USUARIOS_ID=$id")))
        resp(false,'Usuario no encontrado.');
    $hash = e($link,password_hash($pass,PASSWORD_DEFAULT));
    mysqli_query($link,"UPDATE usuarios SET USUARIOS_PASSWORD='$hash' WHERE USUARIOS_ID=$id");
    resp(true,'Contraseña actualizada.');

// ── CAMBIAR PERFIL (solo SA) ─────────────────────────────────────────────
case 'cambiar_perfil':
    if (!is_superadmin()) resp(false,'Sin permisos.');
    $id       = (int)($_POST['id']??0);
    $perfilId = (int)($_POST['perfil_id']??0);
    $duenoId  = (int)($_POST['dueno_id']??0);
    if (!$id || !$perfilId) resp(false,'Datos incompletos.');
    if (in_array($perfilId,[1,2])) resp(false,'Los perfiles SuperAdmin y Dueño solo pueden asignarse desde el Panel Desarrollador.');
    if (!in_array($perfilId,[3,4,5])) resp(false,'Perfil inválido.');
    if ($id===current_uid()) resp(false,'No podés cambiar tu propio perfil.');
    if (in_array($perfilId,[3,4]) && !$duenoId) resp(false,'El staff requiere un dueño asignado.');
    $u = mysqli_fetch_assoc(mysqli_query($link,"SELECT PERFIL_ID FROM usuarios WHERE USUARIOS_ID=$id"));
    if (!$u) resp(false,'Usuario no encontrado.');
    if ((int)$u['PERFIL_ID'] === 2) resp(false,'El perfil Dueño solo puede modificarse desde el Panel Desarrollador.');
    $duenoSQ = in_array($perfilId,[3,4]) ? (int)$duenoId : 'NULL';
    mysqli_query($link,"UPDATE usuarios SET PERFIL_ID=$perfilId,DUENO_ID=$duenoSQ WHERE USUARIOS_ID=$id");
    resp(true,'Perfil actualizado.');

default:
    resp(false,'Acción no reconocida.');
}

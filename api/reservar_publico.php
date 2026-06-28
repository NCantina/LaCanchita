<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/dist/script/php/conn.php';

function resp($ok, $msg, $data=null){
    $json = json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE);
    if ($json === false) $json = json_encode(['ok'=>false,'msg'=>'Error interno al serializar respuesta.','data'=>null]);
    echo $json; exit;
}
function e($l,$v){ return mysqli_real_escape_string($l,$v); }
function qfetch($link, $sql) {
    $r = mysqli_query($link, $sql);
    return ($r && $r !== true) ? mysqli_fetch_assoc($r) : null;
}

if (!isset($_SESSION['usuario_id'])) resp(false,'Tenés que iniciar sesión primero.');
$uid = (int)$_SESSION['usuario_id'];

$nombreSesion   = $_SESSION['usuario_nombre']   ?? '';
$apellidoSesion = $_SESSION['usuario_apellido'] ?? '';
$emailSesion    = $_SESSION['usuario_email']    ?? '';
session_write_close();

$b         = json_decode(file_get_contents('php://input'), true) ?? [];
$cancha_id = (int)($b['cancha_id'] ?? 0);
$fecha     = trim($b['fecha']      ?? '');
$hora      = trim($b['hora']       ?? '');   // HH:MM
$metodo    = trim($b['metodo']     ?? 'efectivo');

if (!$cancha_id || !$fecha || !$hora) resp(false,'Datos incompletos.');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) resp(false,'Fecha inválida.');
if (!preg_match('/^\d{2}:\d{2}$/', $hora)) resp(false,'Hora inválida (se esperaba HH:MM).');
if (strtotime($fecha) < strtotime(date('Y-m-d'))) resp(false,'No podés reservar fechas pasadas.');

try {
    $eFecha = e($link, $fecha);
    // Hora: el cliente envía HH:MM, MySQL TIME almacena HH:MM:SS
    $horaConSeg = $hora . ':00';
    $eHora = e($link, $horaConSeg);

    // Obtener franja exacta para esa cancha + hora
    $franja = qfetch($link,
        "SELECT fh.FRANJA_ID, fh.FRANJA_HORA_INICIO, fh.FRANJA_HORA_FIN, fh.FRANJA_PRECIO, fh.FRANJA_SENA,
                fh.CANCHA_ID, c.COMPLEJO_ID
         FROM franja_horaria fh
         JOIN cancha c ON c.CANCHA_ID = fh.CANCHA_ID
         WHERE fh.CANCHA_ID=$cancha_id
           AND TIME(fh.FRANJA_HORA_INICIO)=TIME('$eHora')
           AND fh.ACTIVO=1 LIMIT 1"
    );
    if (!$franja) resp(false,'No existe una franja para ese horario. Recargá la página y volvé a intentar.');

    $franja_id   = (int)$franja['FRANJA_ID'];
    $complejo_id = (int)$franja['COMPLEJO_ID'];
    $h_ini = $franja['FRANJA_HORA_INICIO'];
    $h_fin = $franja['FRANJA_HORA_FIN'];
    $precio= (float)$franja['FRANJA_PRECIO'];
    $sena  = (float)$franja['FRANJA_SENA'];

    // Verificar día de semana
    $diaRow = qfetch($link, "SELECT MOD(DAYOFWEEK('$eFecha')-2+7,7)+1 AS DIA_ID");
    $dia_id = (int)($diaRow['DIA_ID'] ?? 0);

    $diaOk = qfetch($link, "SELECT 1 FROM franja_dia WHERE FRANJA_ID=$franja_id AND DIA_ID=$dia_id LIMIT 1");
    if (!$diaOk) resp(false,'Esta franja no está disponible para ese día de la semana.');

    // Transacción para evitar doble reserva
    mysqli_begin_transaction($link);

    $lock = mysqli_query($link,
        "SELECT RESERVA_ID FROM reserva
         WHERE CANCHA_ID=$cancha_id AND FRANJA_ID=$franja_id
           AND RESERVA_FECHA='$eFecha'
           AND RESERVA_ESTADO IN ('pendiente','confirmada')
           AND ACTIVO=1
         LIMIT 1 FOR UPDATE"
    );
    if ($lock && mysqli_num_rows($lock) > 0) {
        mysqli_rollback($link);
        resp(false,'Este turno ya fue reservado. Elegí otro horario.');
    }

    // Turno fijo
    $eHIni = e($link,$h_ini); $eHFin = e($link,$h_fin);
    $tf = qfetch($link,
        "SELECT 1 FROM turno_fijo
         WHERE CANCHA_ID=$cancha_id AND TURNO_FIJO_DIA=$dia_id
           AND TURNO_FIJO_HORA_INICIO='$eHIni' AND TURNO_FIJO_HORA_FIN='$eHFin'
           AND ACTIVO=1
           AND TURNO_FIJO_FECHA_DESDE<='$eFecha'
           AND (TURNO_FIJO_FECHA_HASTA IS NULL OR TURNO_FIJO_FECHA_HASTA>='$eFecha')
         LIMIT 1"
    );
    if ($tf) { mysqli_rollback($link); resp(false,'Este turno es fijo y no está disponible.'); }

    // Cierre
    $cierre = qfetch($link,
        "SELECT 1 FROM cierre_cancha
         WHERE ACTIVO=1
           AND (CANCHA_ID=$cancha_id OR (CANCHA_ID IS NULL AND COMPLEJO_ID=$complejo_id))
           AND CIERRE_FECHA_DESDE<='$eFecha' AND CIERRE_FECHA_HASTA>='$eFecha'
           AND (CIERRE_HORA_DESDE IS NULL OR (CIERRE_HORA_DESDE<'$eHFin' AND CIERRE_HORA_HASTA>'$eHIni'))
         LIMIT 1"
    );
    if ($cierre) { mysqli_rollback($link); resp(false,'El complejo está cerrado en ese horario.'); }

    $stmt = mysqli_prepare($link,
        "INSERT INTO reserva
           (CANCHA_ID,FRANJA_ID,USUARIOS_ID,RESERVA_FECHA,RESERVA_HORA_INICIO,
            RESERVA_HORA_FIN,RESERVA_PRECIO,RESERVA_SENA,RESERVA_ESTADO,RESERVA_ES_FIJA,ACTIVO)
         VALUES (?,?,?,?,?,?,?,?,'pendiente',0,1)"
    );
    if (!$stmt) { mysqli_rollback($link); resp(false,'Error preparando la reserva: ' . mysqli_error($link)); }

    mysqli_stmt_bind_param($stmt,'iiisssdd',
        $cancha_id,$franja_id,$uid,$fecha,$h_ini,$h_fin,$precio,$sena
    );
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_rollback($link);
        resp(false,'Error al guardar la reserva: ' . mysqli_stmt_error($stmt));
    }
    $reserva_id = mysqli_insert_id($link);
    mysqli_commit($link);

    // Info del complejo para respuesta y email
    $comp = qfetch($link,
        "SELECT co.COMPLEJO_NOMBRE, co.COMPLEJO_TELEFONO, ca.CANCHA_NOMBRE
         FROM complejo co
         JOIN cancha ca ON ca.CANCHA_ID=$cancha_id
         WHERE co.COMPLEJO_ID=$complejo_id LIMIT 1"
    );

    resp(true,'¡Reserva enviada! El predio la confirmará pronto.',[
        'RESERVA_ID'      => $reserva_id,
        'HORA_INICIO'     => substr($h_ini,0,5),
        'HORA_FIN'        => substr($h_fin,0,5),
        'PRECIO'          => $precio,
        'SENA'            => $sena,
        'COMPLEJO_NOMBRE' => $comp['COMPLEJO_NOMBRE']  ?? '',
        'CANCHA_NOMBRE'   => $comp['CANCHA_NOMBRE']    ?? '',
        'COMPLEJO_TEL'    => $comp['COMPLEJO_TELEFONO'] ?? '',
        'METODO'          => $metodo,
    ]);

} catch (Throwable $ex) {
    if (isset($link) && mysqli_get_server_info($link)) {
        // Intentar rollback si hay transacción activa
        @mysqli_rollback($link);
    }
    resp(false, 'Error inesperado: ' . $ex->getMessage());
}

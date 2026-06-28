<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';

function resp($ok,$msg,$data=null){
    $j=json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);
    if($j===false)$j=json_encode(['ok'=>false,'msg'=>'Error interno.','data'=>null]);
    echo $j;exit;
}
function e($l,$v){return mysqli_real_escape_string($l,$v);}
function qfetch($link,$sql){$r=mysqli_query($link,$sql);return($r&&$r!==true)?mysqli_fetch_assoc($r):null;}

if (!isset($_SESSION['usuario_id'])) resp(false,'Sesión no iniciada.');
$uid = (int)$_SESSION['usuario_id'];
session_write_close(); // liberar lock de sesión antes de queries

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// ── DISPONIBILIDAD ────────────────────────────────────────────────────────────
if ($action === 'disponibilidad') {
    $cancha_id = (int)($_GET['cancha_id'] ?? 0);
    $fecha     = trim($_GET['fecha'] ?? '');
    if (!$cancha_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) resp(false,'Parámetros inválidos.');

    $eFecha = e($link, $fecha);
    $diaRow = qfetch($link,"SELECT MOD(DAYOFWEEK('$eFecha')-2+7,7)+1 AS DIA_ID");
    $diaId  = (int)($diaRow['DIA_ID'] ?? 0);

    $res = mysqli_query($link,"
        SELECT fh.FRANJA_ID, fh.FRANJA_HORA_INICIO, fh.FRANJA_HORA_FIN, fh.FRANJA_PRECIO, fh.FRANJA_SENA
        FROM franja_horaria fh
        INNER JOIN franja_dia fd ON fd.FRANJA_ID=fh.FRANJA_ID AND fd.DIA_ID=$diaId
        WHERE fh.CANCHA_ID=$cancha_id AND fh.ACTIVO=1
        ORDER BY fh.FRANJA_HORA_INICIO
    ");
    if (!$res) resp(false,'Error al cargar franjas.');

    $franjas = [];
    while ($f = mysqli_fetch_assoc($res)) {
        $fid   = (int)$f['FRANJA_ID'];
        $hIni  = $f['FRANJA_HORA_INICIO'];
        $hFin  = $f['FRANJA_HORA_FIN'];

        // Verificar reserva
        $ocup = qfetch($link,
            "SELECT RESERVA_ID FROM reserva
             WHERE CANCHA_ID=$cancha_id AND FRANJA_ID=$fid
               AND RESERVA_FECHA='$eFecha'
               AND RESERVA_ESTADO IN ('pendiente','confirmada')
               AND ACTIVO=1 LIMIT 1"
        );

        // Verificar turno fijo
        $tf = !$ocup ? qfetch($link,
            "SELECT 1 FROM turno_fijo
             WHERE CANCHA_ID=$cancha_id AND TURNO_FIJO_DIA=$diaId
               AND TURNO_FIJO_HORA_INICIO='".e($link,$hIni)."'
               AND ACTIVO=1
               AND TURNO_FIJO_FECHA_DESDE<='$eFecha'
               AND (TURNO_FIJO_FECHA_HASTA IS NULL OR TURNO_FIJO_FECHA_HASTA>='$eFecha')
             LIMIT 1"
        ) : null;

        // Verificar cierre
        $compRow = qfetch($link,"SELECT COMPLEJO_ID FROM cancha WHERE CANCHA_ID=$cancha_id LIMIT 1");
        $cmpId   = (int)($compRow['COMPLEJO_ID']??0);
        $cierre  = (!$ocup && !$tf) ? qfetch($link,
            "SELECT 1 FROM cierre_cancha
             WHERE ACTIVO=1
               AND (CANCHA_ID=$cancha_id OR (CANCHA_ID IS NULL AND COMPLEJO_ID=$cmpId))
               AND CIERRE_FECHA_DESDE<='$eFecha' AND CIERRE_FECHA_HASTA>='$eFecha'
               AND (CIERRE_HORA_DESDE IS NULL OR (CIERRE_HORA_DESDE<'".e($link,$hFin)."' AND CIERRE_HORA_HASTA>'".e($link,$hIni)."'))
             LIMIT 1"
        ) : null;

        $disponible = !$ocup && !$tf && !$cierre;
        $motivo = $ocup ? 'reservado' : ($tf ? 'turno fijo' : ($cierre ? 'cerrado' : ''));

        $f['disponible']           = $disponible;
        $f['motivo_no_disponible'] = $motivo;
        $franjas[] = $f;
    }
    resp(true,'ok',$franjas);
}

// ── CREAR RESERVA ─────────────────────────────────────────────────────────────
if ($action === 'crear') {
    $cancha_id = (int)($_POST['cancha_id'] ?? 0);
    $franja_id = (int)($_POST['franja_id'] ?? 0);
    $fecha     = trim($_POST['fecha'] ?? '');
    if (!$cancha_id || !$franja_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha))
        resp(false,'Datos incompletos.');
    if (strtotime($fecha) < strtotime(date('Y-m-d')))
        resp(false,'No podés reservar fechas pasadas.');

    $eFecha = e($link,$fecha);
    $franja = qfetch($link,
        "SELECT fh.*, c.COMPLEJO_ID FROM franja_horaria fh
         JOIN cancha c ON c.CANCHA_ID=fh.CANCHA_ID
         WHERE fh.FRANJA_ID=$franja_id AND fh.CANCHA_ID=$cancha_id AND fh.ACTIVO=1 LIMIT 1"
    );
    if (!$franja) resp(false,'Franja no válida.');

    $diaRow    = qfetch($link,"SELECT MOD(DAYOFWEEK('$eFecha')-2+7,7)+1 AS DIA_ID");
    $diaId     = (int)($diaRow['DIA_ID'] ?? 0);
    $cmpId     = (int)$franja['COMPLEJO_ID'];
    $hIni      = $franja['FRANJA_HORA_INICIO'];
    $hFin      = $franja['FRANJA_HORA_FIN'];
    $precio    = (float)$franja['FRANJA_PRECIO'];
    $sena      = (float)$franja['FRANJA_SENA'];

    $diaOk = qfetch($link,
        "SELECT 1 FROM franja_dia WHERE FRANJA_ID=$franja_id AND DIA_ID=$diaId LIMIT 1"
    );
    if (!$diaOk) resp(false,'La franja no aplica para ese día.');

    mysqli_begin_transaction($link);

    $lock = mysqli_query($link,
        "SELECT RESERVA_ID FROM reserva
         WHERE CANCHA_ID=$cancha_id AND FRANJA_ID=$franja_id
           AND RESERVA_FECHA='$eFecha'
           AND RESERVA_ESTADO IN ('pendiente','confirmada') AND ACTIVO=1
         LIMIT 1 FOR UPDATE"
    );
    if (mysqli_num_rows($lock) > 0) { mysqli_rollback($link); resp(false,'Este turno ya fue reservado.'); }

    $tf = qfetch($link,
        "SELECT 1 FROM turno_fijo
         WHERE CANCHA_ID=$cancha_id AND TURNO_FIJO_DIA=$diaId
           AND TURNO_FIJO_HORA_INICIO='".e($link,$hIni)."' AND ACTIVO=1
           AND TURNO_FIJO_FECHA_DESDE<='$eFecha'
           AND (TURNO_FIJO_FECHA_HASTA IS NULL OR TURNO_FIJO_FECHA_HASTA>='$eFecha')
         LIMIT 1"
    );
    if ($tf) { mysqli_rollback($link); resp(false,'Este turno es fijo y no está disponible.'); }

    $cierre = qfetch($link,
        "SELECT 1 FROM cierre_cancha
         WHERE ACTIVO=1
           AND (CANCHA_ID=$cancha_id OR (CANCHA_ID IS NULL AND COMPLEJO_ID=$cmpId))
           AND CIERRE_FECHA_DESDE<='$eFecha' AND CIERRE_FECHA_HASTA>='$eFecha'
           AND (CIERRE_HORA_DESDE IS NULL OR (CIERRE_HORA_DESDE<'".e($link,$hFin)."' AND CIERRE_HORA_HASTA>'".e($link,$hIni)."'))
         LIMIT 1"
    );
    if ($cierre) { mysqli_rollback($link); resp(false,'El complejo está cerrado en ese horario.'); }

    $stmt = mysqli_prepare($link,
        "INSERT INTO reserva (CANCHA_ID,FRANJA_ID,USUARIOS_ID,RESERVA_FECHA,
          RESERVA_HORA_INICIO,RESERVA_HORA_FIN,RESERVA_PRECIO,RESERVA_SENA,
          RESERVA_ESTADO,RESERVA_ES_FIJA,ACTIVO)
         VALUES (?,?,?,?,?,?,?,?,'pendiente',0,1)"
    );
    mysqli_stmt_bind_param($stmt,'iiisssdd',$cancha_id,$franja_id,$uid,$fecha,$hIni,$hFin,$precio,$sena);
    if (!mysqli_stmt_execute($stmt)) { mysqli_rollback($link); resp(false,'Error al guardar la reserva.'); }
    $rid = mysqli_insert_id($link);
    mysqli_commit($link);
    resp(true,'¡Reserva creada! El predio la confirmará pronto.',['RESERVA_ID'=>$rid]);
}

// ── CANCELAR ──────────────────────────────────────────────────────────────────
if ($action === 'cancelar') {
    $rid = (int)($_POST['reserva_id'] ?? 0);
    if (!$rid) resp(false,'ID inválido.');
    $r = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT RESERVA_ID,USUARIOS_ID,RESERVA_ESTADO FROM reserva WHERE RESERVA_ID=$rid AND ACTIVO=1 LIMIT 1"
    ));
    if (!$r) resp(false,'Reserva no encontrada.');
    if ((int)$r['USUARIOS_ID'] !== $uid) resp(false,'No tenés permiso.');
    if (!in_array($r['RESERVA_ESTADO'],['pendiente','confirmada'])) resp(false,'No se puede cancelar una reserva '.$r['RESERVA_ESTADO'].'.');
    mysqli_query($link,"UPDATE reserva SET RESERVA_ESTADO='cancelada' WHERE RESERVA_ID=$rid");
    resp(true,'Reserva cancelada correctamente.');
}

// ── MIS RESERVAS ──────────────────────────────────────────────────────────────
if ($action === 'mis_reservas') {
    $res = mysqli_query($link,"
        SELECT r.RESERVA_ID, r.RESERVA_FECHA, r.RESERVA_HORA_INICIO, r.RESERVA_HORA_FIN,
               r.RESERVA_PRECIO, r.RESERVA_SENA, r.RESERVA_ESTADO,
               c.CANCHA_NOMBRE, co.COMPLEJO_NOMBRE, co.COMPLEJO_DIRECCION,
               co.COMPLEJO_TELEFONO, l.LOCALIDAD_NOMBRE,
               tc.TIPO_CANCHA_NOMBRE,
               COALESCE(SUM(p.PAGO_MONTO),0) AS SALDO_PAGADO
        FROM reserva r
        JOIN cancha c ON c.CANCHA_ID=r.CANCHA_ID
        JOIN complejo co ON co.COMPLEJO_ID=c.COMPLEJO_ID
        LEFT JOIN localidad l ON l.LOCALIDAD_ID=co.LOCALIDAD_ID
        LEFT JOIN tipo_cancha tc ON tc.TIPO_CANCHA_ID=c.TIPO_CANCHA_ID
        LEFT JOIN pago p ON p.RESERVA_ID=r.RESERVA_ID AND p.ACTIVO=1
        WHERE r.USUARIOS_ID=$uid AND r.ACTIVO=1
        GROUP BY r.RESERVA_ID
        ORDER BY r.RESERVA_FECHA DESC, r.RESERVA_HORA_INICIO DESC
        LIMIT 50
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $row['SALDO_PENDIENTE'] = max(0, (float)$row['RESERVA_PRECIO'] - (float)$row['SALDO_PAGADO']);
        $rows[] = $row;
    }
    resp(true,'ok',$rows);
}

resp(false,'Acción no reconocida.');

<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function resp($ok, $msg, $data = null) { echo json_encode(['ok' => $ok, 'msg' => $msg, 'data' => $data]); exit; }
function e($link, $v) { return mysqli_real_escape_string($link, trim($v ?? '')); }

// Verificar sesión mínima (sin requerir perfil)
function require_sesion() {
    if (empty($_SESSION['usuario_id'])) resp(false, 'No autenticado.');
}

// Verificar que una reserva pertenece al tenant actual (por cancha → complejo)
// Devuelve la fila de reserva o aborta con resp(false,...)
function get_reserva_tenant($link, $reserva_id) {
    $id = (int)$reserva_id;
    if (!$id) resp(false, 'ID de reserva inválido.');
    $ids = tenant_complejo_ids($link);
    $scope = tenant_where($ids, 'co.COMPLEJO_ID');
    $sql = "SELECT r.*, ca.CANCHA_NOMBRE, co.COMPLEJO_ID, co.COMPLEJO_NOMBRE
            FROM reserva r
            JOIN cancha ca ON ca.CANCHA_ID = r.CANCHA_ID
            JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
            WHERE r.RESERVA_ID = $id AND r.ACTIVO = 1 AND $scope
            LIMIT 1";
    $row = mysqli_fetch_assoc(mysqli_query($link, $sql));
    if (!$row) resp(false, 'Reserva no encontrada o sin acceso.');
    return $row;
}

// Para staff: verificar que tiene asignada esa cancha
function assert_cancha_encargado($link, $cancha_id) {
    if (is_superadmin() || is_dueno()) return;
    $uid = (int)current_uid();
    $cid = (int)$cancha_id;
    $ok = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT 1 FROM cancha_encargado WHERE CANCHA_ID=$cid AND USUARIOS_ID=$uid AND ACTIVO=1 LIMIT 1"
    ));
    if (!$ok) resp(false, 'Sin acceso a esta cancha.');
}

switch ($action) {

// ── DISPONIBILIDAD ─────────────────────────────────────────────────────────
case 'disponibilidad':
    require_sesion();

    $cancha_id = (int)($_GET['cancha_id'] ?? 0);
    $fecha     = e($link, $_GET['fecha'] ?? '');

    if (!$cancha_id) resp(false, 'cancha_id requerido.');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) resp(false, 'fecha inválida (YYYY-MM-DD).');

    // Cancha activa
    $cancha = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT ca.CANCHA_ID, ca.COMPLEJO_ID FROM cancha ca WHERE ca.CANCHA_ID=$cancha_id AND ca.ACTIVO=1 LIMIT 1"
    ));
    if (!$cancha) resp(false, 'Cancha no encontrada o inactiva.');

    $complejo_id = (int)$cancha['COMPLEJO_ID'];

    // Día de semana: MySQL DAYOFWEEK 1=Dom, convertir a 1=Lun...7=Dom
    $dia_row = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT MOD(DAYOFWEEK('$fecha')-2+7,7)+1 AS DIA_ID"
    ));
    $dia_id = (int)$dia_row['DIA_ID'];

    // Franjas activas de la cancha para ese día
    $qf = mysqli_query($link,
        "SELECT fh.FRANJA_ID, fh.FRANJA_HORA_INICIO, fh.FRANJA_HORA_FIN,
                fh.FRANJA_PRECIO, fh.FRANJA_SENA
         FROM franja_horaria fh
         JOIN franja_dia fd ON fd.FRANJA_ID = fh.FRANJA_ID
         WHERE fh.CANCHA_ID = $cancha_id AND fh.ACTIVO = 1 AND fd.DIA_ID = $dia_id
         ORDER BY fh.FRANJA_HORA_INICIO ASC"
    );

    $franjas = [];
    while ($f = mysqli_fetch_assoc($qf)) {
        $fid = (int)$f['FRANJA_ID'];
        $h_ini = e($link, $f['FRANJA_HORA_INICIO']);
        $h_fin = e($link, $f['FRANJA_HORA_FIN']);

        $disponible = true;
        $motivo     = null;

        // 1. ¿Reservada?
        $res = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT 1 FROM reserva
             WHERE CANCHA_ID=$cancha_id AND FRANJA_ID=$fid
               AND RESERVA_FECHA='$fecha'
               AND RESERVA_ESTADO IN ('pendiente','confirmada')
               AND ACTIVO=1
             LIMIT 1"
        ));
        if ($res) { $disponible = false; $motivo = 'reservada'; }

        // 2. ¿Turno fijo?
        if ($disponible) {
            $tf = mysqli_fetch_assoc(mysqli_query($link,
                "SELECT 1 FROM turno_fijo
                 WHERE CANCHA_ID=$cancha_id
                   AND TURNO_FIJO_DIA=$dia_id
                   AND TURNO_FIJO_HORA_INICIO='$h_ini'
                   AND TURNO_FIJO_HORA_FIN='$h_fin'
                   AND ACTIVO=1
                   AND TURNO_FIJO_FECHA_DESDE <= '$fecha'
                   AND (TURNO_FIJO_FECHA_HASTA IS NULL OR TURNO_FIJO_FECHA_HASTA >= '$fecha')
                 LIMIT 1"
            ));
            if ($tf) { $disponible = false; $motivo = 'turno_fijo'; }
        }

        // 3. ¿Cierre de cancha o complejo?
        if ($disponible) {
            $cierre = mysqli_fetch_assoc(mysqli_query($link,
                "SELECT 1 FROM cierre_cancha
                 WHERE ACTIVO=1
                   AND (CANCHA_ID=$cancha_id OR (CANCHA_ID IS NULL AND COMPLEJO_ID=$complejo_id))
                   AND CIERRE_FECHA_DESDE <= '$fecha'
                   AND CIERRE_FECHA_HASTA >= '$fecha'
                   AND (
                     CIERRE_HORA_DESDE IS NULL
                     OR (CIERRE_HORA_DESDE < '$h_fin' AND CIERRE_HORA_HASTA > '$h_ini')
                   )
                 LIMIT 1"
            ));
            if ($cierre) { $disponible = false; $motivo = 'cierre'; }
        }

        $franjas[] = [
            'FRANJA_ID'          => $fid,
            'FRANJA_HORA_INICIO' => $f['FRANJA_HORA_INICIO'],
            'FRANJA_HORA_FIN'    => $f['FRANJA_HORA_FIN'],
            'FRANJA_PRECIO'      => (float)$f['FRANJA_PRECIO'],
            'FRANJA_SENA'        => (float)$f['FRANJA_SENA'],
            'disponible'         => $disponible,
            'motivo_no_disponible' => $motivo,
        ];
    }

    resp(true, '', $franjas);

// ── CREAR RESERVA ──────────────────────────────────────────────────────────
case 'crear':
    require_sesion();

    $cancha_id = (int)($_POST['cancha_id'] ?? 0);
    $franja_id = (int)($_POST['franja_id'] ?? 0);
    $fecha     = e($link, $_POST['fecha'] ?? '');
    $uid       = (int)current_uid();

    if (!$cancha_id || !$franja_id) resp(false, 'cancha_id y franja_id son requeridos.');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) resp(false, 'fecha inválida (YYYY-MM-DD).');

    // Cancha activa
    $cancha = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT CANCHA_ID, COMPLEJO_ID FROM cancha WHERE CANCHA_ID=$cancha_id AND ACTIVO=1 LIMIT 1"
    ));
    if (!$cancha) resp(false, 'Cancha no encontrada o inactiva.');

    // Franja pertenece a la cancha
    $franja = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT FRANJA_ID, FRANJA_HORA_INICIO, FRANJA_HORA_FIN, FRANJA_PRECIO, FRANJA_SENA
         FROM franja_horaria
         WHERE FRANJA_ID=$franja_id AND CANCHA_ID=$cancha_id AND ACTIVO=1 LIMIT 1"
    ));
    if (!$franja) resp(false, 'Franja no válida para esta cancha.');

    $complejo_id = (int)$cancha['COMPLEJO_ID'];
    $h_ini = e($link, $franja['FRANJA_HORA_INICIO']);
    $h_fin = e($link, $franja['FRANJA_HORA_FIN']);
    $precio = (float)$franja['FRANJA_PRECIO'];
    $sena   = (float)$franja['FRANJA_SENA'];

    // Día de semana
    $dia_row = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT MOD(DAYOFWEEK('$fecha')-2+7,7)+1 AS DIA_ID"
    ));
    $dia_id = (int)$dia_row['DIA_ID'];

    // Verificar que la franja aplica para ese día
    $dia_ok = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT 1 FROM franja_dia WHERE FRANJA_ID=$franja_id AND DIA_ID=$dia_id LIMIT 1"
    ));
    if (!$dia_ok) resp(false, 'Esta franja no está disponible para el día seleccionado.');

    // Transacción + SELECT FOR UPDATE para evitar doble reserva
    mysqli_begin_transaction($link);

    // Bloquear fila (si existe) o simplemente verificar con lock en tabla
    $lock = mysqli_query($link,
        "SELECT RESERVA_ID FROM reserva
         WHERE CANCHA_ID=$cancha_id AND FRANJA_ID=$franja_id
           AND RESERVA_FECHA='$fecha'
           AND RESERVA_ESTADO IN ('pendiente','confirmada')
           AND ACTIVO=1
         LIMIT 1 FOR UPDATE"
    );
    if (mysqli_num_rows($lock) > 0) {
        mysqli_rollback($link);
        resp(false, 'Este turno ya fue reservado. Elegí otro horario.');
    }

    // Verificar turno fijo
    $tf_check = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT 1 FROM turno_fijo
         WHERE CANCHA_ID=$cancha_id AND TURNO_FIJO_DIA=$dia_id
           AND TURNO_FIJO_HORA_INICIO='$h_ini' AND TURNO_FIJO_HORA_FIN='$h_fin'
           AND ACTIVO=1
           AND TURNO_FIJO_FECHA_DESDE <= '$fecha'
           AND (TURNO_FIJO_FECHA_HASTA IS NULL OR TURNO_FIJO_FECHA_HASTA >= '$fecha')
         LIMIT 1"
    ));
    if ($tf_check) {
        mysqli_rollback($link);
        resp(false, 'Este turno es fijo y no está disponible.');
    }

    // Verificar cierre
    $cierre_check = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT 1 FROM cierre_cancha
         WHERE ACTIVO=1
           AND (CANCHA_ID=$cancha_id OR (CANCHA_ID IS NULL AND COMPLEJO_ID=$complejo_id))
           AND CIERRE_FECHA_DESDE <= '$fecha' AND CIERRE_FECHA_HASTA >= '$fecha'
           AND (CIERRE_HORA_DESDE IS NULL OR (CIERRE_HORA_DESDE < '$h_fin' AND CIERRE_HORA_HASTA > '$h_ini'))
         LIMIT 1"
    ));
    if ($cierre_check) {
        mysqli_rollback($link);
        resp(false, 'El complejo o la cancha está cerrado en ese horario.');
    }

    // INSERT con prepared statement
    $stmt = mysqli_prepare($link,
        "INSERT INTO reserva
           (CANCHA_ID, FRANJA_ID, USUARIOS_ID, RESERVA_FECHA, RESERVA_HORA_INICIO,
            RESERVA_HORA_FIN, RESERVA_PRECIO, RESERVA_SENA, RESERVA_ESTADO, RESERVA_ES_FIJA, ACTIVO)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', 0, 1)"
    );
    mysqli_stmt_bind_param($stmt, 'iiisssddd',
        $cancha_id, $franja_id, $uid, $fecha,
        $franja['FRANJA_HORA_INICIO'], $franja['FRANJA_HORA_FIN'],
        $precio, $sena
    );

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_rollback($link);
        resp(false, 'Error al crear la reserva. Intentá de nuevo.');
    }
    $reserva_id = mysqli_insert_id($link);
    mysqli_commit($link);

    resp(true, 'Reserva creada. En breve te confirmamos.', ['RESERVA_ID' => $reserva_id]);

// ── AGENDA GRID (vista grilla cancha×horario) ──────────────────────────────
case 'agenda_grid':
    require_perfil(4);

    $fecha       = e($link, $_GET['fecha'] ?? date('Y-m-d'));
    $complejo_id = (int)($_GET['complejo_id'] ?? 0);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) $fecha = date('Y-m-d');

    $ids   = tenant_complejo_ids($link);
    $scope = tenant_where($ids, 'ca.COMPLEJO_ID');
    $compf = $complejo_id ? "AND ca.COMPLEJO_ID=$complejo_id" : '';

    // DIA_ID: 1=Lun,...,7=Dom (convención de franja_dia)
    $dia_row = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT MOD(DAYOFWEEK('$fecha')-2+7,7)+1 AS DIA_ID"
    ));
    $dia_id = (int)$dia_row['DIA_ID'];

    // Staff: solo sus canchas
    $join_staff = '';
    if (!is_superadmin() && !is_dueno()) {
        $uid_s = (int)current_uid();
        $join_staff = "JOIN cancha_encargado ce_s ON ce_s.CANCHA_ID=ca.CANCHA_ID AND ce_s.USUARIOS_ID=$uid_s AND ce_s.ACTIVO=1";
    }

    // Canchas
    $qca = mysqli_query($link,
        "SELECT ca.CANCHA_ID, ca.CANCHA_NOMBRE, co.COMPLEJO_ID, co.COMPLEJO_NOMBRE,
                tc.TIPO_CANCHA_ICONO
         FROM cancha ca
         $join_staff
         JOIN complejo co ON co.COMPLEJO_ID=ca.COMPLEJO_ID
         JOIN tipo_cancha tc ON tc.TIPO_CANCHA_ID=ca.TIPO_CANCHA_ID
         WHERE ca.ACTIVO=1 AND $scope $compf
         ORDER BY co.COMPLEJO_NOMBRE, ca.CANCHA_NOMBRE"
    );
    $canchas = [];
    while ($r=mysqli_fetch_assoc($qca)) $canchas[(int)$r['CANCHA_ID']] = $r;

    if (empty($canchas)) {
        resp(true,'',['canchas'=>[],'franjas'=>[],'reservas'=>[],'cierres'=>[],'dia_id'=>$dia_id]);
    }
    $cids = implode(',', array_keys($canchas));

    // Franjas del día
    $qfr = mysqli_query($link,
        "SELECT fh.FRANJA_ID, fh.CANCHA_ID, fh.FRANJA_HORA_INICIO, fh.FRANJA_HORA_FIN,
                fh.FRANJA_PRECIO, fh.FRANJA_SENA
         FROM franja_horaria fh
         JOIN franja_dia fd ON fd.FRANJA_ID=fh.FRANJA_ID AND fd.DIA_ID=$dia_id
         WHERE fh.CANCHA_ID IN ($cids) AND fh.ACTIVO=1
         ORDER BY fh.FRANJA_HORA_INICIO, fh.FRANJA_HORA_FIN"
    );
    $franjas = [];
    while ($r=mysqli_fetch_assoc($qfr)) $franjas[]=$r;

    // Reservas del día
    $qres = mysqli_query($link,
        "SELECT r.RESERVA_ID, r.CANCHA_ID, r.FRANJA_ID,
                r.RESERVA_HORA_INICIO, r.RESERVA_HORA_FIN,
                r.RESERVA_PRECIO, r.RESERVA_SENA, r.RESERVA_ESTADO,
                u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO, u.USUARIOS_TELEFONO, u.USUARIOS_EMAIL,
                COALESCE(SUM(p.PAGO_MONTO),0) AS PAGADO_TOTAL
         FROM reserva r
         JOIN usuarios u ON u.USUARIOS_ID=r.USUARIOS_ID
         LEFT JOIN pago p ON p.RESERVA_ID=r.RESERVA_ID AND p.ACTIVO=1
         WHERE r.CANCHA_ID IN ($cids)
           AND r.RESERVA_FECHA='$fecha'
           AND r.ACTIVO=1
           AND r.RESERVA_ESTADO IN ('pendiente','confirmada')
         GROUP BY r.RESERVA_ID"
    );
    $reservas = [];
    while ($r=mysqli_fetch_assoc($qres)) $reservas[]=$r;

    // Cierres del día
    $coIds = implode(',', array_unique(array_map('intval', array_column(array_values($canchas),'COMPLEJO_ID'))));
    $qci = mysqli_query($link,
        "SELECT cc.CANCHA_ID, cc.COMPLEJO_ID, cc.CIERRE_HORA_DESDE, cc.CIERRE_HORA_HASTA, cc.CIERRE_MOTIVO
         FROM cierre_cancha cc
         WHERE cc.ACTIVO=1
           AND cc.CIERRE_FECHA_DESDE<='$fecha' AND cc.CIERRE_FECHA_HASTA>='$fecha'
           AND (cc.CANCHA_ID IN ($cids) OR (cc.CANCHA_ID IS NULL AND cc.COMPLEJO_ID IN ($coIds)))"
    );
    $cierres = [];
    while ($r=mysqli_fetch_assoc($qci)) $cierres[]=$r;

    resp(true,'',['canchas'=>array_values($canchas),'franjas'=>$franjas,
                   'reservas'=>$reservas,'cierres'=>$cierres,'dia_id'=>$dia_id]);

// ── CREAR RESERVA (admin/staff, a nombre de un cliente) ───────────────────
case 'crear_admin':
    require_perfil(4);

    $cancha_id  = (int)($_POST['cancha_id']  ?? 0);
    $franja_id  = (int)($_POST['franja_id']  ?? 0);
    $fecha      = e($link, $_POST['fecha']   ?? '');
    $cliente_id = (int)($_POST['usuario_id'] ?? 0);
    $obs        = e($link, $_POST['observacion'] ?? '');
    $estado_ini = in_array($_POST['estado'] ?? '', ['pendiente','confirmada']) ? $_POST['estado'] : 'confirmada';

    if (!$cancha_id || !$franja_id) resp(false, 'cancha_id y franja_id son requeridos.');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) resp(false, 'Fecha inválida (YYYY-MM-DD).');
    if (!$cliente_id) resp(false, 'Seleccioná un cliente para la reserva.');

    assert_cancha_encargado($link, $cancha_id);

    $cancha = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT CANCHA_ID, COMPLEJO_ID FROM cancha WHERE CANCHA_ID=$cancha_id AND ACTIVO=1 LIMIT 1"
    ));
    if (!$cancha) resp(false, 'Cancha no encontrada o inactiva.');

    $franja = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT FRANJA_ID, FRANJA_HORA_INICIO, FRANJA_HORA_FIN, FRANJA_PRECIO, FRANJA_SENA
         FROM franja_horaria
         WHERE FRANJA_ID=$franja_id AND CANCHA_ID=$cancha_id AND ACTIVO=1 LIMIT 1"
    ));
    if (!$franja) resp(false, 'Franja no válida para esta cancha.');

    // Verificar que el cliente existe
    $cli = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT USUARIOS_ID FROM usuarios WHERE USUARIOS_ID=$cliente_id AND PERFIL_ID=5 AND ACTIVO=1 LIMIT 1"
    ));
    if (!$cli) resp(false, 'Cliente no encontrado.');

    $complejo_id = (int)$cancha['COMPLEJO_ID'];
    $h_ini = e($link, $franja['FRANJA_HORA_INICIO']);
    $h_fin = e($link, $franja['FRANJA_HORA_FIN']);
    $precio = (float)$franja['FRANJA_PRECIO'];
    $sena   = (float)$franja['FRANJA_SENA'];

    $dia_row = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT MOD(DAYOFWEEK('$fecha')-2+7,7)+1 AS DIA_ID"
    ));
    $dia_id = (int)$dia_row['DIA_ID'];

    $dia_ok = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT 1 FROM franja_dia WHERE FRANJA_ID=$franja_id AND DIA_ID=$dia_id LIMIT 1"
    ));
    if (!$dia_ok) resp(false, 'Esta franja no está disponible para el día seleccionado.');

    mysqli_begin_transaction($link);

    $lock = mysqli_query($link,
        "SELECT RESERVA_ID FROM reserva
         WHERE CANCHA_ID=$cancha_id AND FRANJA_ID=$franja_id
           AND RESERVA_FECHA='$fecha'
           AND RESERVA_ESTADO IN ('pendiente','confirmada') AND ACTIVO=1
         LIMIT 1 FOR UPDATE"
    );
    if (mysqli_num_rows($lock) > 0) {
        mysqli_rollback($link);
        resp(false, 'Este turno ya está reservado.');
    }

    $cierre = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT 1 FROM cierre_cancha
         WHERE ACTIVO=1
           AND (CANCHA_ID=$cancha_id OR (CANCHA_ID IS NULL AND COMPLEJO_ID=$complejo_id))
           AND CIERRE_FECHA_DESDE <= '$fecha' AND CIERRE_FECHA_HASTA >= '$fecha'
           AND (CIERRE_HORA_DESDE IS NULL OR (CIERRE_HORA_DESDE < '$h_fin' AND CIERRE_HORA_HASTA > '$h_ini'))
         LIMIT 1"
    ));
    if ($cierre) { mysqli_rollback($link); resp(false, 'La cancha tiene un cierre en ese horario.'); }

    $stmt = mysqli_prepare($link,
        "INSERT INTO reserva
           (CANCHA_ID, FRANJA_ID, USUARIOS_ID, RESERVA_FECHA, RESERVA_HORA_INICIO,
            RESERVA_HORA_FIN, RESERVA_PRECIO, RESERVA_SENA, RESERVA_ESTADO, RESERVA_ES_FIJA, ACTIVO)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1)"
    );
    mysqli_stmt_bind_param($stmt, 'iiisssdds',
        $cancha_id, $franja_id, $cliente_id, $fecha,
        $franja['FRANJA_HORA_INICIO'], $franja['FRANJA_HORA_FIN'],
        $precio, $sena, $estado_ini
    );

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_rollback($link);
        resp(false, 'Error al crear la reserva.');
    }
    $reserva_id = mysqli_insert_id($link);
    mysqli_commit($link);

    resp(true, 'Reserva creada correctamente.', ['RESERVA_ID' => $reserva_id]);

// ── MIS RESERVAS ───────────────────────────────────────────────────────────
case 'mis_reservas':
    require_sesion();
    $uid = (int)current_uid();

    $sql = "
        SELECT r.RESERVA_ID, r.RESERVA_FECHA, r.RESERVA_HORA_INICIO, r.RESERVA_HORA_FIN,
               r.RESERVA_PRECIO, r.RESERVA_SENA, r.RESERVA_ESTADO,
               ca.CANCHA_NOMBRE, co.COMPLEJO_NOMBRE, co.COMPLEJO_DIRECCION, co.COMPLEJO_TELEFONO,
               tc.TIPO_CANCHA_NOMBRE,
               COALESCE(SUM(p.PAGO_MONTO),0) AS PAGADO_TOTAL,
               (r.RESERVA_PRECIO - COALESCE(SUM(p.PAGO_MONTO),0)) AS SALDO_PENDIENTE
        FROM reserva r
        JOIN cancha ca ON ca.CANCHA_ID = r.CANCHA_ID
        JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
        JOIN tipo_cancha tc ON tc.TIPO_CANCHA_ID = ca.TIPO_CANCHA_ID
        LEFT JOIN pago p ON p.RESERVA_ID = r.RESERVA_ID AND p.ACTIVO = 1
        WHERE r.USUARIOS_ID = $uid AND r.ACTIVO = 1
        GROUP BY r.RESERVA_ID
        ORDER BY r.RESERVA_FECHA DESC, r.RESERVA_HORA_INICIO DESC
        LIMIT 50
    ";
    $q = mysqli_query($link, $sql);
    $rows = [];
    while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    resp(true, '', $rows);

// ── CANCELAR (cliente) ─────────────────────────────────────────────────────
case 'cancelar':
    require_sesion();
    $uid        = (int)current_uid();
    $reserva_id = (int)($_POST['reserva_id'] ?? 0);
    if (!$reserva_id) resp(false, 'reserva_id requerido.');

    $res = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT RESERVA_ID, USUARIOS_ID, RESERVA_ESTADO FROM reserva
         WHERE RESERVA_ID=$reserva_id AND ACTIVO=1 LIMIT 1"
    ));
    if (!$res) resp(false, 'Reserva no encontrada.');
    if ((int)$res['USUARIOS_ID'] !== $uid) resp(false, 'No tenés permiso para cancelar esta reserva.');
    if ($res['RESERVA_ESTADO'] !== 'pendiente') resp(false, 'Solo podés cancelar reservas en estado pendiente.');

    $stmt = mysqli_prepare($link,
        "UPDATE reserva SET RESERVA_ESTADO='cancelada', ACTIVO=0 WHERE RESERVA_ID=?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $reserva_id);
    mysqli_stmt_execute($stmt);

    resp(true, 'Reserva cancelada.', null);

// ── PENDIENTES COUNT (para polling de notificaciones) ─────────────────────
case 'pendientes_count':
    require_perfil(4);
    $ids   = tenant_complejo_ids($link);
    $scope = tenant_where($ids, 'co.COMPLEJO_ID');
    $since = e($link, $_GET['since'] ?? '');
    $sinceClause = $since ? "AND r.RESERVA_ID > " . (int)$since : '';
    $row = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) AS cnt, MAX(r.RESERVA_ID) AS last_id
         FROM reserva r
         JOIN cancha ca ON ca.CANCHA_ID = r.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
         WHERE r.RESERVA_ESTADO = 'pendiente' AND r.ACTIVO = 1 AND $scope $sinceClause"
    ));
    resp(true, 'ok', ['count' => (int)($row['cnt']??0), 'last_id' => (int)($row['last_id']??0)]);

// ── LISTAR (admin/staff) ───────────────────────────────────────────────────
case 'listar':
    require_perfil(4); // permite perfiles 1,2,3,4

    $fecha      = e($link, $_GET['fecha'] ?? date('Y-m-d'));
    $cancha_id  = (int)($_GET['cancha_id'] ?? 0);
    $complejo_id= (int)($_GET['complejo_id'] ?? 0);
    $estado     = e($link, $_GET['estado'] ?? '');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) $fecha = date('Y-m-d');

    // Scoping por perfil
    $join_staff  = '';
    $where_scope = '';

    if (is_superadmin()) {
        $where_scope = '1=1';
    } elseif (is_dueno()) {
        $ids = tenant_complejo_ids($link);
        $where_scope = tenant_where($ids, 'co.COMPLEJO_ID');
    } else {
        // Staff: solo canchas asignadas
        $uid_staff   = (int)current_uid();
        $join_staff  = "JOIN cancha_encargado ce ON ce.CANCHA_ID = ca.CANCHA_ID AND ce.USUARIOS_ID=$uid_staff AND ce.ACTIVO=1";
        $ids         = tenant_complejo_ids($link);
        $where_scope = tenant_where($ids, 'co.COMPLEJO_ID');
    }

    // Filtros opcionales
    $filtros = "AND r.RESERVA_FECHA = '$fecha'";
    if ($cancha_id)   $filtros .= " AND ca.CANCHA_ID = $cancha_id";
    if ($complejo_id) $filtros .= " AND co.COMPLEJO_ID = $complejo_id";
    if (in_array($estado, ['pendiente','confirmada','cancelada'])) {
        $filtros .= " AND r.RESERVA_ESTADO = '$estado'";
    }

    $sql = "
        SELECT r.RESERVA_ID, r.RESERVA_FECHA, r.RESERVA_HORA_INICIO, r.RESERVA_HORA_FIN,
               r.RESERVA_PRECIO, r.RESERVA_SENA, r.RESERVA_ESTADO, r.RESERVA_ES_FIJA,
               ca.CANCHA_ID, ca.CANCHA_NOMBRE,
               co.COMPLEJO_ID, co.COMPLEJO_NOMBRE,
               tc.TIPO_CANCHA_ICONO,
               u.USUARIOS_ID, u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO,
               u.USUARIOS_EMAIL, u.USUARIOS_TELEFONO,
               COALESCE(SUM(p.PAGO_MONTO),0) AS PAGADO_TOTAL,
               (r.RESERVA_PRECIO - COALESCE(SUM(p.PAGO_MONTO),0)) AS SALDO_PENDIENTE
        FROM reserva r
        JOIN cancha ca ON ca.CANCHA_ID = r.CANCHA_ID
        $join_staff
        JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
        JOIN tipo_cancha tc ON tc.TIPO_CANCHA_ID = ca.TIPO_CANCHA_ID
        JOIN usuarios u ON u.USUARIOS_ID = r.USUARIOS_ID
        LEFT JOIN pago p ON p.RESERVA_ID = r.RESERVA_ID AND p.ACTIVO = 1
        WHERE r.ACTIVO = 1 AND $where_scope $filtros
        GROUP BY r.RESERVA_ID
        ORDER BY r.RESERVA_HORA_INICIO ASC, ca.CANCHA_NOMBRE ASC
    ";

    $q = mysqli_query($link, $sql);
    $rows = [];
    while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    resp(true, '', $rows);

// ── CONFIRMAR (admin/staff) ────────────────────────────────────────────────
case 'confirmar':
    require_perfil(4);

    $reserva_id = (int)($_POST['reserva_id'] ?? 0);
    $res = get_reserva_tenant($link, $reserva_id);
    assert_cancha_encargado($link, $res['CANCHA_ID']);

    if ($res['RESERVA_ESTADO'] !== 'pendiente') resp(false, 'Solo se pueden confirmar reservas en estado pendiente.');

    $stmt = mysqli_prepare($link,
        "UPDATE reserva SET RESERVA_ESTADO='confirmada' WHERE RESERVA_ID=?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $reserva_id);
    mysqli_stmt_execute($stmt);

    resp(true, 'Reserva confirmada.', null);

// ── RECHAZAR (admin/staff) ─────────────────────────────────────────────────
case 'rechazar':
    require_perfil(4);

    $reserva_id = (int)($_POST['reserva_id'] ?? 0);
    $res = get_reserva_tenant($link, $reserva_id);
    assert_cancha_encargado($link, $res['CANCHA_ID']);

    if ($res['RESERVA_ESTADO'] === 'cancelada') resp(false, 'La reserva ya está cancelada.');

    $stmt = mysqli_prepare($link,
        "UPDATE reserva SET RESERVA_ESTADO='cancelada', ACTIVO=0 WHERE RESERVA_ID=?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $reserva_id);
    mysqli_stmt_execute($stmt);

    resp(true, 'Reserva rechazada y cancelada.', null);

// ── REGISTRAR PAGO (admin/staff) ───────────────────────────────────────────
case 'registrar_pago':
    require_perfil(4);

    $reserva_id  = (int)($_POST['reserva_id'] ?? 0);
    $monto       = (float)($_POST['monto'] ?? 0);
    $tipo        = e($link, $_POST['tipo'] ?? '');
    $medio       = e($link, $_POST['medio'] ?? '');
    $observacion = e($link, $_POST['observacion'] ?? '');
    $uid         = (int)current_uid();

    if ($monto <= 0) resp(false, 'El monto debe ser mayor a 0.');
    if (!in_array($tipo, ['sena','total','parcial'])) resp(false, 'Tipo de pago inválido.');
    if (!in_array($medio, ['efectivo','transferencia','tarjeta','otro'])) resp(false, 'Medio de pago inválido.');

    $res = get_reserva_tenant($link, $reserva_id);
    assert_cancha_encargado($link, $res['CANCHA_ID']);

    if ($res['RESERVA_ESTADO'] === 'cancelada') resp(false, 'No se puede registrar pago en una reserva cancelada.');

    // Total ya pagado
    $pagado_row = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COALESCE(SUM(PAGO_MONTO),0) AS TOTAL FROM pago
         WHERE RESERVA_ID=$reserva_id AND ACTIVO=1"
    ));
    $pagado_total = (float)$pagado_row['TOTAL'];
    $precio_total = (float)$res['RESERVA_PRECIO'];

    if ($pagado_total + $monto > $precio_total) {
        $disponible = $precio_total - $pagado_total;
        resp(false, "El monto excede el saldo pendiente. Máximo a cobrar: $disponible.");
    }

    $stmt = mysqli_prepare($link,
        "INSERT INTO pago
           (RESERVA_ID, PAGO_MONTO, PAGO_TIPO, PAGO_MEDIO, PAGO_OBSERVACION, USUARIOS_ID, ACTIVO)
         VALUES (?, ?, ?, ?, ?, ?, 1)"
    );
    mysqli_stmt_bind_param($stmt, 'idsssi',
        $reserva_id, $monto, $tipo, $medio, $observacion, $uid
    );
    if (!mysqli_stmt_execute($stmt)) resp(false, 'Error al registrar el pago.');

    $nuevo_total = $pagado_total + $monto;
    $saldo       = $precio_total - $nuevo_total;

    // Auto-confirmar si está saldado
    if ($nuevo_total >= $precio_total && $res['RESERVA_ESTADO'] === 'pendiente') {
        $s = mysqli_prepare($link,
            "UPDATE reserva SET RESERVA_ESTADO='confirmada' WHERE RESERVA_ID=? AND RESERVA_ESTADO='pendiente'"
        );
        mysqli_stmt_bind_param($s, 'i', $reserva_id);
        mysqli_stmt_execute($s);
    }

    resp(true, 'Pago registrado.', [
        'PAGADO_TOTAL'    => $nuevo_total,
        'SALDO_PENDIENTE' => max(0, $saldo),
        'auto_confirmada' => ($nuevo_total >= $precio_total && $res['RESERVA_ESTADO'] === 'pendiente'),
    ]);

// ── PAGOS DE UNA RESERVA (admin/staff) ────────────────────────────────────
case 'pagos':
    require_perfil(4);

    $reserva_id = (int)($_GET['reserva_id'] ?? 0);
    get_reserva_tenant($link, $reserva_id); // valida ownership

    $sql = "
        SELECT p.PAGO_ID, p.PAGO_MONTO, p.PAGO_TIPO, p.PAGO_MEDIO,
               p.PAGO_FECHA, p.PAGO_OBSERVACION,
               u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO
        FROM pago p
        JOIN usuarios u ON u.USUARIOS_ID = p.USUARIOS_ID
        WHERE p.RESERVA_ID=$reserva_id AND p.ACTIVO=1
        ORDER BY p.PAGO_FECHA ASC
    ";
    $q = mysqli_query($link, $sql);
    $rows = [];
    while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    resp(true, '', $rows);

// ── DEFAULT ────────────────────────────────────────────────────────────────
default:
    resp(false, 'Acción no reconocida.');
}

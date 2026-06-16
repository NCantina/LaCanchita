<?php
/**
 * API de gestión de clientes — Panel Desarrollador.
 * Exclusivo SuperAdmin (perfil 1).
 *
 * GET  action=stats
 * GET  action=listar          [filtro=todos|activos|prueba|vencidos|cancelados|por_cobrar]
 * GET  action=historial       usuarios_id=X
 * GET  action=mrr_historico
 * GET  action=cobros_todos    [mes=YYYY-MM] [usuarios_id=X]
 * POST action=upsert_plan
 * POST action=registrar_cobro
 * POST action=eliminar_cobro
 * POST action=toggle_cliente
 * POST action=guardar_notas
 * POST action=recordatorio
 * POST action=marcar_vencidos
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';
require_perfil(1);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function resp($ok, $msg, $data = null) { echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function e($v) { global $link; return mysqli_real_escape_string($link, trim((string)($v??''))); }

// ── AUTO MARCAR VENCIDOS ─────────────────────────────────────────────────────
if ($action === 'marcar_vencidos') {
    mysqli_query($link,
        "UPDATE suscripcion_plataforma SET ESTADO='vencido'
         WHERE ESTADO='activo' AND PROXIMO_COBRO IS NOT NULL AND PROXIMO_COBRO < CURDATE()"
    );
    resp(true, '', ['n' => mysqli_affected_rows($link)]);
}

// ── STATS ────────────────────────────────────────────────────────────────────
if ($action === 'stats') {
    $mes = date('Y-m');
    $r = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT
            (SELECT COUNT(*) FROM usuarios WHERE PERFIL_ID=2) AS total_clientes,
            (SELECT COUNT(*) FROM suscripcion_plataforma WHERE ESTADO IN ('activo','prueba')) AS activos,
            (SELECT COUNT(*) FROM suscripcion_plataforma WHERE ESTADO='vencido') AS vencidos,
            (SELECT COALESCE(SUM(
                CASE PLAN_CICLO
                    WHEN 'mensual'    THEN PLAN_PRECIO
                    WHEN 'trimestral' THEN PLAN_PRECIO/3
                    WHEN 'anual'      THEN PLAN_PRECIO/12
                END),0) FROM suscripcion_plataforma WHERE ESTADO='activo') AS mrr,
            (SELECT COALESCE(SUM(COBRO_MONTO),0) FROM cobro_plataforma
             WHERE DATE_FORMAT(COBRO_FECHA,'%Y-%m')='$mes') AS cobrado_mes,
            (SELECT COUNT(*) FROM suscripcion_plataforma
             WHERE ESTADO IN ('activo','vencido')
               AND (PROXIMO_COBRO IS NULL OR PROXIMO_COBRO <= DATE_ADD(CURDATE(), INTERVAL 7 DAY))) AS por_cobrar
        FROM DUAL"
    ));
    resp(true, '', [
        'total_clientes' => (int)$r['total_clientes'],
        'activos'        => (int)$r['activos'],
        'vencidos'       => (int)$r['vencidos'],
        'mrr'            => (float)$r['mrr'],
        'cobrado_mes'    => (float)$r['cobrado_mes'],
        'por_cobrar'     => (int)$r['por_cobrar'],
    ]);
}

// ── LISTAR CLIENTES ──────────────────────────────────────────────────────────
if ($action === 'listar') {
    $filtro = $_GET['filtro'] ?? 'todos';
    $extra  = '';
    if ($filtro === 'activos')    $extra = "AND sp.ESTADO='activo'";
    if ($filtro === 'prueba')     $extra = "AND sp.ESTADO='prueba'";
    if ($filtro === 'vencidos')   $extra = "AND sp.ESTADO='vencido'";
    if ($filtro === 'cancelados') $extra = "AND sp.ESTADO='cancelado'";
    if ($filtro === 'por_cobrar') $extra =
        "AND sp.ESTADO IN ('activo','vencido')
         AND (sp.PROXIMO_COBRO IS NULL OR sp.PROXIMO_COBRO <= DATE_ADD(CURDATE(), INTERVAL 7 DAY))";

    $q = mysqli_query($link, "
        SELECT
            u.USUARIOS_ID, u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO,
            u.USUARIOS_EMAIL, u.USUARIOS_TELEFONO, u.ACTIVO AS CUENTA_ACTIVA,
            COALESCE(sp.SUSCRIPCION_ID,0)       AS SUSCRIPCION_ID,
            COALESCE(sp.PLAN_NOMBRE,'Sin plan')  AS PLAN_NOMBRE,
            COALESCE(sp.PLAN_PRECIO,0)           AS PLAN_PRECIO,
            COALESCE(sp.PLAN_CICLO,'mensual')    AS PLAN_CICLO,
            sp.PROXIMO_COBRO, sp.ULTIMO_COBRO,
            COALESCE(sp.ESTADO,'sin_plan')       AS ESTADO,
            COALESCE(sp.MEDIO_COBRO,'—')         AS MEDIO_COBRO,
            COALESCE(sp.NOTAS,'')                AS NOTAS,
            (SELECT COUNT(*) FROM complejo WHERE USUARIOS_ID=u.USUARIOS_ID) AS TOTAL_PREDIOS,
            (SELECT COUNT(*) FROM cobro_plataforma WHERE USUARIOS_ID=u.USUARIOS_ID) AS TOTAL_COBROS,
            (SELECT COALESCE(SUM(COBRO_MONTO),0) FROM cobro_plataforma WHERE USUARIOS_ID=u.USUARIOS_ID) AS TOTAL_COBRADO
        FROM usuarios u
        LEFT JOIN suscripcion_plataforma sp ON sp.USUARIOS_ID=u.USUARIOS_ID
        WHERE u.PERFIL_ID=2 $extra
        ORDER BY
            FIELD(COALESCE(sp.ESTADO,'sin_plan'),'vencido','sin_plan','activo','prueba','cancelado'),
            sp.PROXIMO_COBRO ASC, u.USUARIOS_NOMBRE ASC
    ");
    $rows = [];
    while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    resp(true, '', $rows);
}

// ── MRR HISTÓRICO ────────────────────────────────────────────────────────────
if ($action === 'mrr_historico') {
    $q = mysqli_query($link,
        "SELECT DATE_FORMAT(COBRO_FECHA,'%Y-%m') AS mes,
                COALESCE(SUM(COBRO_MONTO),0)     AS total
         FROM cobro_plataforma
         WHERE COBRO_FECHA >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
         GROUP BY mes ORDER BY mes ASC"
    );
    $found = [];
    while ($r = mysqli_fetch_assoc($q)) $found[$r['mes']] = (float)$r['total'];

    $result = [];
    for ($i = 5; $i >= 0; $i--) {
        $mes   = date('Y-m', strtotime("-$i months"));
        $meses = ['Jan'=>'Ene','Feb'=>'Feb','Mar'=>'Mar','Apr'=>'Abr','May'=>'May','Jun'=>'Jun',
                  'Jul'=>'Jul','Aug'=>'Ago','Sep'=>'Sep','Oct'=>'Oct','Nov'=>'Nov','Dec'=>'Dic'];
        $label = $meses[date('M', strtotime("-$i months"))] ?? date('M', strtotime("-$i months"));
        $result[] = ['mes'=>$mes, 'label'=>$label, 'total'=>$found[$mes] ?? 0];
    }
    resp(true, '', $result);
}

// ── HISTORIAL DE UN CLIENTE ──────────────────────────────────────────────────
if ($action === 'historial') {
    $uid = (int)($_GET['usuarios_id'] ?? 0);
    if (!$uid) resp(false, 'usuarios_id requerido.');
    $q = mysqli_query($link,
        "SELECT COBRO_ID, COBRO_MONTO, COBRO_FECHA, COBRO_PERIODO, COBRO_MEDIO, COBRO_NOTAS
         FROM cobro_plataforma WHERE USUARIOS_ID=$uid ORDER BY COBRO_FECHA DESC LIMIT 50"
    );
    $rows = [];
    while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    resp(true, '', $rows);
}

// ── TODOS LOS COBROS ─────────────────────────────────────────────────────────
if ($action === 'cobros_todos') {
    $mes = e($_GET['mes'] ?? '');
    $uid = (int)($_GET['usuarios_id'] ?? 0);
    $where = '1=1';
    if ($mes && preg_match('/^\d{4}-\d{2}$/', $mes)) $where .= " AND DATE_FORMAT(cp.COBRO_FECHA,'%Y-%m')='$mes'";
    if ($uid) $where .= " AND cp.USUARIOS_ID=$uid";

    $q = mysqli_query($link, "
        SELECT cp.COBRO_ID, cp.COBRO_MONTO, cp.COBRO_FECHA, cp.COBRO_PERIODO, cp.COBRO_MEDIO, cp.COBRO_NOTAS,
               u.USUARIOS_ID, u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO
        FROM cobro_plataforma cp
        JOIN usuarios u ON u.USUARIOS_ID=cp.USUARIOS_ID
        WHERE $where ORDER BY cp.COBRO_FECHA DESC LIMIT 300
    ");
    $rows = [];
    while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;

    $total = array_sum(array_column($rows, 'COBRO_MONTO'));
    resp(true, '', ['cobros'=>$rows, 'total'=>$total]);
}

// ── UPSERT PLAN ──────────────────────────────────────────────────────────────
if ($action === 'upsert_plan') {
    $uid    = (int)($_POST['usuarios_id']   ?? 0);
    $nombre = e($_POST['plan_nombre']       ?? 'Estándar');
    $precio = (float)($_POST['plan_precio'] ?? 0);
    $ciclo  = $_POST['plan_ciclo']          ?? 'mensual';
    $prox   = e($_POST['proximo_cobro']     ?? '');
    $estado = $_POST['estado']              ?? 'activo';
    $medio  = e($_POST['medio_cobro']       ?? 'transferencia');
    $notas  = e($_POST['notas']             ?? '');

    if (!$uid) resp(false, 'Seleccioná un cliente.');
    if (!in_array($ciclo,  ['mensual','trimestral','anual'], true)) resp(false, 'Ciclo inválido.');
    if (!in_array($estado, ['prueba','activo','vencido','cancelado'], true)) resp(false, 'Estado inválido.');
    if ($prox && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $prox)) resp(false, 'Fecha de próximo cobro inválida.');
    if (!mysqli_fetch_assoc(mysqli_query($link, "SELECT 1 FROM usuarios WHERE USUARIOS_ID=$uid AND PERFIL_ID=2 LIMIT 1")))
        resp(false, 'Cliente no encontrado.');

    $proxVal  = $prox  ?: null;
    $notasVal = $notas ?: null;
    $stmt = mysqli_prepare($link,
        "INSERT INTO suscripcion_plataforma
           (USUARIOS_ID,PLAN_NOMBRE,PLAN_PRECIO,PLAN_CICLO,PROXIMO_COBRO,ESTADO,MEDIO_COBRO,NOTAS)
         VALUES (?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE
           PLAN_NOMBRE=VALUES(PLAN_NOMBRE), PLAN_PRECIO=VALUES(PLAN_PRECIO),
           PLAN_CICLO=VALUES(PLAN_CICLO),   PROXIMO_COBRO=VALUES(PROXIMO_COBRO),
           ESTADO=VALUES(ESTADO),           MEDIO_COBRO=VALUES(MEDIO_COBRO),
           NOTAS=VALUES(NOTAS)"
    );
    mysqli_stmt_bind_param($stmt, 'isdsssss',
        $uid, $nombre, $precio, $ciclo, $proxVal, $estado, $medio, $notasVal);
    if (!mysqli_stmt_execute($stmt)) resp(false, 'Error al guardar: ' . mysqli_error($link));
    resp(true, 'Plan guardado correctamente.');
}

// ── REGISTRAR COBRO ──────────────────────────────────────────────────────────
if ($action === 'registrar_cobro') {
    $uid     = (int)($_POST['usuarios_id'] ?? 0);
    $monto   = (float)($_POST['monto']    ?? 0);
    $fecha   = e($_POST['fecha']          ?? date('Y-m-d'));
    $periodo = e($_POST['periodo']        ?? date('Y-m'));
    $medio   = e($_POST['medio']          ?? 'transferencia');
    $notas   = e($_POST['notas']          ?? '');

    if (!$uid) resp(false, 'usuarios_id requerido.');
    if ($monto <= 0) resp(false, 'El monto debe ser mayor a 0.');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) resp(false, 'Fecha inválida.');

    $notasVal = $notas ?: null;
    $stmt = mysqli_prepare($link,
        "INSERT INTO cobro_plataforma (USUARIOS_ID,COBRO_MONTO,COBRO_FECHA,COBRO_PERIODO,COBRO_MEDIO,COBRO_NOTAS)
         VALUES (?,?,?,?,?,?)"
    );
    mysqli_stmt_bind_param($stmt, 'idssss', $uid, $monto, $fecha, $periodo, $medio, $notasVal);
    if (!mysqli_stmt_execute($stmt)) resp(false, 'Error al registrar el cobro.');

    // Avanzar próximo cobro
    $sp = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT PLAN_CICLO, PROXIMO_COBRO FROM suscripcion_plataforma WHERE USUARIOS_ID=$uid LIMIT 1"
    ));
    if ($sp) {
        $ints = ['mensual'=>'+1 month','trimestral'=>'+3 months','anual'=>'+1 year'];
        $int  = $ints[$sp['PLAN_CICLO']] ?? '+1 month';
        $base = $sp['PROXIMO_COBRO'] ?: $fecha;
        $nuevo = e(date('Y-m-d', strtotime($base . ' ' . $int)));
        $ef    = e($fecha);
        mysqli_query($link,
            "UPDATE suscripcion_plataforma
             SET ULTIMO_COBRO='$ef', PROXIMO_COBRO='$nuevo', ESTADO='activo'
             WHERE USUARIOS_ID=$uid"
        );
    }
    resp(true, 'Cobro registrado. Próximo cobro actualizado.');
}

// ── ELIMINAR COBRO ────────────────────────────────────────────────────────────
if ($action === 'eliminar_cobro') {
    $id = (int)($_POST['cobro_id'] ?? 0);
    if (!$id) resp(false, 'cobro_id requerido.');
    $stmt = mysqli_prepare($link, "DELETE FROM cobro_plataforma WHERE COBRO_ID=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (!mysqli_stmt_execute($stmt)) resp(false, 'Error al eliminar.');
    resp(true, 'Cobro eliminado.');
}

// ── GUARDAR NOTAS ─────────────────────────────────────────────────────────────
if ($action === 'guardar_notas') {
    $uid      = (int)($_POST['usuarios_id'] ?? 0);
    $notasVal = e($_POST['notas'] ?? '') ?: null;
    if (!$uid) resp(false, 'usuarios_id requerido.');
    $stmt = mysqli_prepare($link,
        "UPDATE suscripcion_plataforma SET NOTAS=? WHERE USUARIOS_ID=?"
    );
    mysqli_stmt_bind_param($stmt, 'si', $notasVal, $uid);
    mysqli_stmt_execute($stmt);
    resp(true, 'Notas guardadas.');
}

// ── TOGGLE CLIENTE ────────────────────────────────────────────────────────────
if ($action === 'toggle_cliente') {
    $uid = (int)($_POST['usuarios_id'] ?? 0);
    if (!$uid) resp(false, 'usuarios_id requerido.');
    $u = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT ACTIVO FROM usuarios WHERE USUARIOS_ID=$uid AND PERFIL_ID=2 LIMIT 1"
    ));
    if (!$u) resp(false, 'Cliente no encontrado.');
    $nuevo = $u['ACTIVO'] ? 0 : 1;
    $stmt  = mysqli_prepare($link, "UPDATE usuarios SET ACTIVO=? WHERE USUARIOS_ID=?");
    mysqli_stmt_bind_param($stmt, 'ii', $nuevo, $uid);
    mysqli_stmt_execute($stmt);
    resp(true, $nuevo ? 'Cuenta activada.' : 'Cuenta desactivada.', ['activo'=>$nuevo]);
}

// ── ENVIAR RECORDATORIO ───────────────────────────────────────────────────────
if ($action === 'recordatorio') {
    $uid = (int)($_POST['usuarios_id'] ?? 0);
    if (!$uid) resp(false, 'usuarios_id requerido.');
    $u = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO, u.USUARIOS_EMAIL,
                sp.PLAN_NOMBRE, sp.PLAN_PRECIO, sp.PLAN_CICLO, sp.PROXIMO_COBRO
         FROM usuarios u
         LEFT JOIN suscripcion_plataforma sp ON sp.USUARIOS_ID=u.USUARIOS_ID
         WHERE u.USUARIOS_ID=$uid AND u.PERFIL_ID=2 LIMIT 1"
    ));
    if (!$u) resp(false, 'Cliente no encontrado.');
    if (!$u['USUARIOS_EMAIL']) resp(false, 'El cliente no tiene email registrado.');
    require_once __DIR__ . '/../../../config/dist/script/php/mailer.php';
    $ok = enviarRecordatorioCobro([
        'nombre'        => $u['USUARIOS_NOMBRE'],
        'apellido'      => $u['USUARIOS_APELLIDO'],
        'email'         => $u['USUARIOS_EMAIL'],
        'plan_nombre'   => $u['PLAN_NOMBRE']   ?? 'Estándar',
        'plan_precio'   => $u['PLAN_PRECIO']   ?? 0,
        'proximo_cobro' => $u['PROXIMO_COBRO'] ?? '',
    ]);
    if (!$ok) resp(false, 'Error al enviar. Verificá la configuración SMTP en config/mail.php.');
    resp(true, 'Recordatorio enviado a ' . $u['USUARIOS_EMAIL']);
}

resp(false, 'Acción no reconocida.');

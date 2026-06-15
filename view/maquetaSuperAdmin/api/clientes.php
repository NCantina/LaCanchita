<?php
/**
 * API de gestión de clientes para el panel del desarrollador.
 * Solo accesible por SuperAdmin (perfil 1).
 *
 * Acciones GET:  stats | listar | historial
 * Acciones POST: upsert_plan | registrar_cobro | eliminar_cobro | toggle_cliente
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';

require_perfil(1);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function resp($ok, $msg, $data = null) {
    echo json_encode(['ok' => $ok, 'msg' => $msg, 'data' => $data]);
    exit;
}
function e($v) {
    global $link;
    return mysqli_real_escape_string($link, trim((string)($v ?? '')));
}

// ── STATS ─────────────────────────────────────────────────────────────────────
if ($action === 'stats') {
    $mes = date('Y-m');

    $r = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT
            COUNT(*) AS activos,
            COALESCE(SUM(CASE WHEN ESTADO='activo' THEN
                CASE PLAN_CICLO
                    WHEN 'mensual'    THEN PLAN_PRECIO
                    WHEN 'trimestral' THEN PLAN_PRECIO / 3
                    WHEN 'anual'      THEN PLAN_PRECIO / 12
                END ELSE 0 END), 0) AS mrr
         FROM suscripcion_plataforma WHERE ESTADO IN ('activo','prueba')"
    ));
    $c = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COALESCE(SUM(COBRO_MONTO),0) AS cobrado
         FROM cobro_plataforma WHERE DATE_FORMAT(COBRO_FECHA,'%Y-%m')='$mes'"
    ));
    $a = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) AS cnt FROM suscripcion_plataforma
         WHERE ESTADO='activo' AND PROXIMO_COBRO < CURDATE()"
    ));

    resp(true, '', [
        'activos'   => (int)$r['activos'],
        'mrr'       => (float)$r['mrr'],
        'cobrado'   => (float)$c['cobrado'],
        'atrasados' => (int)$a['cnt'],
    ]);
}

// ── LISTAR ────────────────────────────────────────────────────────────────────
if ($action === 'listar') {
    $filtro = $_GET['filtro'] ?? 'todos';

    $extra = '';
    if ($filtro === 'activos')    $extra = "AND sp.ESTADO = 'activo'";
    if ($filtro === 'prueba')     $extra = "AND sp.ESTADO = 'prueba'";
    if ($filtro === 'cancelados') $extra = "AND sp.ESTADO = 'cancelado'";
    if ($filtro === 'por_cobrar') $extra =
        "AND sp.ESTADO IN ('activo','vencido')
         AND (sp.PROXIMO_COBRO IS NULL OR sp.PROXIMO_COBRO <= DATE_ADD(CURDATE(), INTERVAL 7 DAY))";

    $q = mysqli_query($link, "
        SELECT u.USUARIOS_ID, u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO,
               u.USUARIOS_EMAIL, u.USUARIOS_TELEFONO, u.ACTIVO AS CUENTA_ACTIVA,
               COALESCE(sp.SUSCRIPCION_ID, 0)        AS SUSCRIPCION_ID,
               COALESCE(sp.PLAN_NOMBRE, '—')          AS PLAN_NOMBRE,
               COALESCE(sp.PLAN_PRECIO, 0)            AS PLAN_PRECIO,
               COALESCE(sp.PLAN_CICLO, 'mensual')     AS PLAN_CICLO,
               sp.PROXIMO_COBRO,
               sp.ULTIMO_COBRO,
               COALESCE(sp.ESTADO, 'sin_plan')        AS ESTADO,
               COALESCE(sp.MEDIO_COBRO, '—')          AS MEDIO_COBRO,
               sp.NOTAS,
               (SELECT COUNT(*) FROM complejo WHERE USUARIOS_ID = u.USUARIOS_ID)                  AS TOTAL_PREDIOS,
               (SELECT COUNT(*) FROM cobro_plataforma WHERE USUARIOS_ID = u.USUARIOS_ID)           AS TOTAL_COBROS,
               (SELECT COALESCE(SUM(COBRO_MONTO),0) FROM cobro_plataforma WHERE USUARIOS_ID = u.USUARIOS_ID) AS TOTAL_COBRADO
        FROM usuarios u
        LEFT JOIN suscripcion_plataforma sp ON sp.USUARIOS_ID = u.USUARIOS_ID
        WHERE u.PERFIL_ID = 2 $extra
        ORDER BY
            FIELD(COALESCE(sp.ESTADO,'sin_plan'),'vencido','activo','prueba','sin_plan','cancelado'),
            sp.PROXIMO_COBRO ASC, u.USUARIOS_NOMBRE ASC
    ");
    $rows = [];
    while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    resp(true, '', $rows);
}

// ── HISTORIAL ─────────────────────────────────────────────────────────────────
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

// ── UPSERT PLAN ───────────────────────────────────────────────────────────────
if ($action === 'upsert_plan') {
    $uid    = (int)($_POST['usuarios_id']   ?? 0);
    $nombre = e($_POST['plan_nombre']       ?? 'Estándar');
    $precio = (float)($_POST['plan_precio'] ?? 0);
    $ciclo  = $_POST['plan_ciclo']          ?? 'mensual';
    $prox   = e($_POST['proximo_cobro']     ?? '');
    $estado = $_POST['estado']              ?? 'activo';
    $medio  = e($_POST['medio_cobro']       ?? 'transferencia');
    $notas  = e($_POST['notas']             ?? '');

    if (!$uid) resp(false, 'usuarios_id requerido.');
    if (!in_array($ciclo, ['mensual','trimestral','anual'], true)) resp(false, 'Ciclo inválido.');
    if (!in_array($estado, ['prueba','activo','vencido','cancelado'], true)) resp(false, 'Estado inválido.');
    if ($prox && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $prox)) resp(false, 'Fecha inválida.');

    $u = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT USUARIOS_ID FROM usuarios WHERE USUARIOS_ID=$uid AND PERFIL_ID=2 LIMIT 1"
    ));
    if (!$u) resp(false, 'Cliente no encontrado.');

    $proxVal  = $prox  ?: null;
    $notasVal = $notas ?: null;

    $stmt = mysqli_prepare($link,
        "INSERT INTO suscripcion_plataforma
           (USUARIOS_ID, PLAN_NOMBRE, PLAN_PRECIO, PLAN_CICLO, PROXIMO_COBRO, ESTADO, MEDIO_COBRO, NOTAS)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           PLAN_NOMBRE=VALUES(PLAN_NOMBRE),   PLAN_PRECIO=VALUES(PLAN_PRECIO),
           PLAN_CICLO=VALUES(PLAN_CICLO),     PROXIMO_COBRO=VALUES(PROXIMO_COBRO),
           ESTADO=VALUES(ESTADO),             MEDIO_COBRO=VALUES(MEDIO_COBRO),
           NOTAS=VALUES(NOTAS)"
    );
    mysqli_stmt_bind_param($stmt, 'isdsssss',
        $uid, $nombre, $precio, $ciclo, $proxVal, $estado, $medio, $notasVal
    );
    if (!mysqli_stmt_execute($stmt)) resp(false, 'Error al guardar el plan: ' . mysqli_error($link));
    resp(true, 'Plan guardado correctamente.');
}

// ── REGISTRAR COBRO ───────────────────────────────────────────────────────────
if ($action === 'registrar_cobro') {
    $uid     = (int)($_POST['usuarios_id'] ?? 0);
    $monto   = (float)($_POST['monto']    ?? 0);
    $fecha   = e($_POST['fecha']          ?? date('Y-m-d'));
    $periodo = e($_POST['periodo']        ?? date('Y-m'));
    $medio   = e($_POST['medio']          ?? 'transferencia');
    $notas   = e($_POST['notas']          ?? '');

    if (!$uid)   resp(false, 'usuarios_id requerido.');
    if ($monto <= 0) resp(false, 'El monto debe ser mayor a 0.');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) resp(false, 'Fecha inválida.');

    $notasVal = $notas ?: null;
    $stmt = mysqli_prepare($link,
        "INSERT INTO cobro_plataforma (USUARIOS_ID, COBRO_MONTO, COBRO_FECHA, COBRO_PERIODO, COBRO_MEDIO, COBRO_NOTAS)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, 'idssss', $uid, $monto, $fecha, $periodo, $medio, $notasVal);
    if (!mysqli_stmt_execute($stmt)) resp(false, 'Error al registrar el cobro.');

    // Avanzar próximo cobro según ciclo
    $sp = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT PLAN_CICLO, PROXIMO_COBRO FROM suscripcion_plataforma WHERE USUARIOS_ID=$uid LIMIT 1"
    ));
    if ($sp) {
        $intervalos = ['mensual' => '+1 month', 'trimestral' => '+3 months', 'anual' => '+1 year'];
        $int  = $intervalos[$sp['PLAN_CICLO']] ?? '+1 month';
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
    resp(true, $nuevo ? 'Cliente activado.' : 'Cliente desactivado.', ['activo' => $nuevo]);
}

resp(false, 'Acción no reconocida.');

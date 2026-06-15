<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';

require_perfil(2);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function resp($ok, $msg, $data = null) { echo json_encode(['ok' => $ok, 'msg' => $msg, 'data' => $data]); exit; }
function e($link, $v) { return mysqli_real_escape_string($link, trim($v ?? '')); }

/**
 * Devuelve el turno fijo y verifica ownership vía can_complejo.
 * Aborta con tenancy_deny si no se encuentra o no tiene permisos.
 */
function get_turno($link, $id) {
    $id = (int)$id;
    $r = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT tf.*, ca.COMPLEJO_ID
         FROM turno_fijo tf JOIN cancha ca ON ca.CANCHA_ID = tf.CANCHA_ID
         WHERE tf.TURNO_FIJO_ID = $id
         LIMIT 1"
    ));
    if (!$r) tenancy_deny('Turno no encontrado.', 404);
    if (!can_complejo($link, $r['COMPLEJO_ID'])) tenancy_deny('Sin permisos.');
    return $r;
}

switch ($action) {

// ── SELECTS ────────────────────────────────────────────────────────────────
case 'selects':
    $ids   = tenant_complejo_ids($link);
    $scope = tenant_where($ids, 'co.COMPLEJO_ID');

    // Complejos del tenant
    $complejos = [];
    $res = mysqli_query($link,
        "SELECT co.COMPLEJO_ID, co.COMPLEJO_NOMBRE
         FROM complejo co
         WHERE $scope AND co.ACTIVO = 1
         ORDER BY co.COMPLEJO_NOMBRE ASC"
    );
    while ($row = mysqli_fetch_assoc($res)) $complejos[] = $row;

    // Canchas del tenant
    $canchas = [];
    $res = mysqli_query($link,
        "SELECT ca.CANCHA_ID, ca.CANCHA_NOMBRE, ca.COMPLEJO_ID
         FROM cancha ca
         JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
         WHERE $scope AND ca.ACTIVO = 1
         ORDER BY co.COMPLEJO_NOMBRE ASC, ca.CANCHA_NOMBRE ASC"
    );
    while ($row = mysqli_fetch_assoc($res)) $canchas[] = $row;

    // Franjas de las canchas del tenant
    $franjas = [];
    $res = mysqli_query($link,
        "SELECT fh.FRANJA_ID, fh.CANCHA_ID, fh.FRANJA_HORA_INICIO, fh.FRANJA_HORA_FIN, fh.FRANJA_PRECIO
         FROM franja_horaria fh
         JOIN cancha ca ON ca.CANCHA_ID = fh.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
         WHERE $scope AND fh.ACTIVO = 1
         ORDER BY fh.CANCHA_ID ASC, fh.FRANJA_HORA_INICIO ASC"
    );
    while ($row = mysqli_fetch_assoc($res)) $franjas[] = $row;

    // Clientes (PERFIL_ID = 5, activos) — sin filtro de tenant
    $clientes = [];
    $res = mysqli_query($link,
        "SELECT USUARIOS_ID, USUARIOS_NOMBRE, USUARIOS_APELLIDO, USUARIOS_EMAIL, USUARIOS_TELEFONO
         FROM usuarios
         WHERE PERFIL_ID = 5 AND ACTIVO = 1
         ORDER BY USUARIOS_APELLIDO ASC, USUARIOS_NOMBRE ASC"
    );
    while ($row = mysqli_fetch_assoc($res)) $clientes[] = $row;

    resp(true, 'ok', compact('complejos', 'canchas', 'franjas', 'clientes'));

// ── LISTAR ─────────────────────────────────────────────────────────────────
case 'listar':
    $ids        = tenant_complejo_ids($link);
    $scope      = tenant_where($ids, 'co.COMPLEJO_ID');
    $solo_activos = (int)($_GET['solo_activos'] ?? 1);
    $filtros    = "WHERE $scope";

    if (!empty($_GET['complejo_id'])) {
        $cid = (int)$_GET['complejo_id'];
        $filtros .= " AND co.COMPLEJO_ID = $cid";
    }
    if (!empty($_GET['cancha_id'])) {
        $cid = (int)$_GET['cancha_id'];
        $filtros .= " AND tf.CANCHA_ID = $cid";
    }
    if (!empty($_GET['dia'])) {
        $dia = (int)$_GET['dia'];
        if ($dia >= 1 && $dia <= 7) $filtros .= " AND tf.TURNO_FIJO_DIA = $dia";
    }
    if ($solo_activos) {
        $filtros .= " AND tf.ACTIVO = 1";
    }

    $dias_nombre = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    $sql = "SELECT tf.TURNO_FIJO_ID, tf.CANCHA_ID, tf.FRANJA_ID, tf.USUARIOS_ID,
                   tf.TURNO_FIJO_DIA, tf.TURNO_FIJO_HORA_INICIO, tf.TURNO_FIJO_HORA_FIN,
                   tf.TURNO_FIJO_PRECIO, tf.TURNO_FIJO_FECHA_DESDE, tf.TURNO_FIJO_FECHA_HASTA,
                   tf.ACTIVO,
                   ca.CANCHA_NOMBRE, co.COMPLEJO_ID, co.COMPLEJO_NOMBRE,
                   u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO, u.USUARIOS_TELEFONO, u.USUARIOS_EMAIL,
                   (CURDATE() >= tf.TURNO_FIJO_FECHA_DESDE
                    AND (tf.TURNO_FIJO_FECHA_HASTA IS NULL OR CURDATE() <= tf.TURNO_FIJO_FECHA_HASTA)
                    AND tf.ACTIVO = 1) AS VIGENTE
            FROM turno_fijo tf
            JOIN cancha ca ON ca.CANCHA_ID = tf.CANCHA_ID
            JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
            LEFT JOIN usuarios u ON u.USUARIOS_ID = tf.USUARIOS_ID
            $filtros
            ORDER BY tf.TURNO_FIJO_DIA ASC, tf.TURNO_FIJO_HORA_INICIO ASC";

    $lista = [];
    $res = mysqli_query($link, $sql);
    if (!$res) resp(false, 'Error al listar turnos fijos: ' . mysqli_error($link));
    while ($row = mysqli_fetch_assoc($res)) {
        $row['DIA_NOMBRE'] = $dias_nombre[(int)$row['TURNO_FIJO_DIA']] ?? '';
        $row['VIGENTE']    = (bool)$row['VIGENTE'];
        $lista[] = $row;
    }

    resp(true, 'ok', $lista);

// ── CREAR ──────────────────────────────────────────────────────────────────
case 'crear':
    $cancha_id   = (int)($_POST['cancha_id'] ?? 0);
    $franja_id   = !empty($_POST['franja_id'])  ? (int)$_POST['franja_id']  : null;
    $usuario_id  = !empty($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : null;
    $dia         = (int)($_POST['dia'] ?? 0);
    $hora_inicio = e($link, $_POST['hora_inicio'] ?? '');
    $hora_fin    = e($link, $_POST['hora_fin']    ?? '');
    $precio      = (float)($_POST['precio'] ?? 0);
    $fecha_desde = e($link, $_POST['fecha_desde'] ?? '');
    $fecha_hasta = !empty($_POST['fecha_hasta']) ? e($link, $_POST['fecha_hasta']) : null;

    // Validaciones
    if (!$cancha_id) resp(false, 'cancha_id requerido.');
    assert_cancha($link, $cancha_id);

    if ($dia < 1 || $dia > 7) resp(false, 'dia debe estar entre 1 y 7.');
    if (!$hora_inicio) resp(false, 'hora_inicio requerida.');
    if (!$hora_fin)    resp(false, 'hora_fin requerida.');
    if ($hora_fin <= $hora_inicio) resp(false, 'hora_fin debe ser mayor que hora_inicio.');
    if ($precio <= 0) resp(false, 'precio debe ser mayor que 0.');
    if (!$fecha_desde || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) resp(false, 'fecha_desde requerida y válida (YYYY-MM-DD).');
    if ($fecha_hasta !== null) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) resp(false, 'fecha_hasta inválida (YYYY-MM-DD).');
        if ($fecha_hasta < $fecha_desde) resp(false, 'fecha_hasta debe ser mayor o igual a fecha_desde.');
    }

    // Verificar que franja pertenece a la cancha
    if ($franja_id !== null) {
        $fok = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT 1 FROM franja_horaria WHERE FRANJA_ID=$franja_id AND CANCHA_ID=$cancha_id AND ACTIVO=1 LIMIT 1"
        ));
        if (!$fok) resp(false, 'La franja no pertenece a esta cancha o no está activa.');
    }

    // Verificar solapamiento
    $fh_safe = $fecha_hasta !== null ? "'$fecha_hasta'" : "'9999-12-31'";
    $overlap = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) AS cnt FROM turno_fijo
         WHERE CANCHA_ID=$cancha_id AND TURNO_FIJO_DIA=$dia AND ACTIVO=1
           AND TURNO_FIJO_HORA_INICIO < '$hora_fin'
           AND TURNO_FIJO_HORA_FIN > '$hora_inicio'
           AND TURNO_FIJO_FECHA_DESDE <= $fh_safe
           AND COALESCE(TURNO_FIJO_FECHA_HASTA, '9999-12-31') >= '$fecha_desde'"
    ));
    if ((int)$overlap['cnt'] > 0) resp(false, 'Ya existe un turno fijo que ocupa ese horario en ese día.');

    // INSERT
    $stmt = mysqli_prepare($link,
        "INSERT INTO turno_fijo
            (CANCHA_ID, FRANJA_ID, USUARIOS_ID, TURNO_FIJO_DIA,
             TURNO_FIJO_HORA_INICIO, TURNO_FIJO_HORA_FIN,
             TURNO_FIJO_PRECIO, TURNO_FIJO_FECHA_DESDE, TURNO_FIJO_FECHA_HASTA, ACTIVO)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
    );
    $precio_str   = number_format($precio, 2, '.', '');
    $franja_bind  = $franja_id;
    $usuario_bind = $usuario_id;
    $fh_bind      = $fecha_hasta;
    mysqli_stmt_bind_param($stmt, 'iiissssss',
        $cancha_id, $franja_bind, $usuario_bind, $dia,
        $hora_inicio, $hora_fin,
        $precio_str, $fecha_desde, $fh_bind
    );
    if (!mysqli_stmt_execute($stmt)) resp(false, 'Error al crear turno fijo: ' . mysqli_stmt_error($stmt));
    $new_id = mysqli_insert_id($link);
    mysqli_stmt_close($stmt);

    resp(true, 'Turno fijo creado correctamente.', ['TURNO_FIJO_ID' => $new_id]);

// ── EDITAR ─────────────────────────────────────────────────────────────────
case 'editar':
    $turno_id    = (int)($_POST['turno_id'] ?? 0);
    if (!$turno_id) resp(false, 'turno_id requerido.');
    $turno = get_turno($link, $turno_id);

    $cancha_id   = (int)($_POST['cancha_id'] ?? 0);
    $franja_id   = !empty($_POST['franja_id'])  ? (int)$_POST['franja_id']  : null;
    $usuario_id  = !empty($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : null;
    $dia         = (int)($_POST['dia'] ?? 0);
    $hora_inicio = e($link, $_POST['hora_inicio'] ?? '');
    $hora_fin    = e($link, $_POST['hora_fin']    ?? '');
    $precio      = (float)($_POST['precio'] ?? 0);
    $fecha_desde = e($link, $_POST['fecha_desde'] ?? '');
    $fecha_hasta = !empty($_POST['fecha_hasta']) ? e($link, $_POST['fecha_hasta']) : null;

    if (!$cancha_id) resp(false, 'cancha_id requerido.');
    assert_cancha($link, $cancha_id);
    if ($dia < 1 || $dia > 7) resp(false, 'dia debe estar entre 1 y 7.');
    if (!$hora_inicio) resp(false, 'hora_inicio requerida.');
    if (!$hora_fin)    resp(false, 'hora_fin requerida.');
    if ($hora_fin <= $hora_inicio) resp(false, 'hora_fin debe ser mayor que hora_inicio.');
    if ($precio <= 0) resp(false, 'precio debe ser mayor que 0.');
    if (!$fecha_desde || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) resp(false, 'fecha_desde requerida y válida (YYYY-MM-DD).');
    if ($fecha_hasta !== null) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) resp(false, 'fecha_hasta inválida (YYYY-MM-DD).');
        if ($fecha_hasta < $fecha_desde) resp(false, 'fecha_hasta debe ser mayor o igual a fecha_desde.');
    }

    // Verificar que franja pertenece a la cancha
    if ($franja_id !== null) {
        $fok = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT 1 FROM franja_horaria WHERE FRANJA_ID=$franja_id AND CANCHA_ID=$cancha_id AND ACTIVO=1 LIMIT 1"
        ));
        if (!$fok) resp(false, 'La franja no pertenece a esta cancha o no está activa.');
    }

    // Verificar solapamiento excluyendo el turno actual
    $fh_safe = $fecha_hasta !== null ? "'$fecha_hasta'" : "'9999-12-31'";
    $overlap = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) AS cnt FROM turno_fijo
         WHERE CANCHA_ID=$cancha_id AND TURNO_FIJO_DIA=$dia AND ACTIVO=1
           AND TURNO_FIJO_ID != $turno_id
           AND TURNO_FIJO_HORA_INICIO < '$hora_fin'
           AND TURNO_FIJO_HORA_FIN > '$hora_inicio'
           AND TURNO_FIJO_FECHA_DESDE <= $fh_safe
           AND COALESCE(TURNO_FIJO_FECHA_HASTA, '9999-12-31') >= '$fecha_desde'"
    ));
    if ((int)$overlap['cnt'] > 0) resp(false, 'Ya existe un turno fijo que ocupa ese horario en ese día.');

    // UPDATE
    $stmt = mysqli_prepare($link,
        "UPDATE turno_fijo SET
            CANCHA_ID = ?, FRANJA_ID = ?, USUARIOS_ID = ?,
            TURNO_FIJO_DIA = ?,
            TURNO_FIJO_HORA_INICIO = ?, TURNO_FIJO_HORA_FIN = ?,
            TURNO_FIJO_PRECIO = ?, TURNO_FIJO_FECHA_DESDE = ?, TURNO_FIJO_FECHA_HASTA = ?
         WHERE TURNO_FIJO_ID = ?"
    );
    $precio_str   = number_format($precio, 2, '.', '');
    $franja_bind  = $franja_id;
    $usuario_bind = $usuario_id;
    $fh_bind      = $fecha_hasta;
    mysqli_stmt_bind_param($stmt, 'iiissssssi',
        $cancha_id, $franja_bind, $usuario_bind, $dia,
        $hora_inicio, $hora_fin,
        $precio_str, $fecha_desde, $fh_bind,
        $turno_id
    );
    if (!mysqli_stmt_execute($stmt)) resp(false, 'Error al editar turno fijo: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);

    resp(true, 'Turno fijo actualizado correctamente.');

// ── TOGGLE ─────────────────────────────────────────────────────────────────
case 'toggle':
    $turno_id = (int)($_POST['turno_id'] ?? 0);
    if (!$turno_id) resp(false, 'turno_id requerido.');
    $turno = get_turno($link, $turno_id);

    $nuevo = $turno['ACTIVO'] ? 0 : 1;
    $stmt = mysqli_prepare($link, "UPDATE turno_fijo SET ACTIVO = ? WHERE TURNO_FIJO_ID = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $nuevo, $turno_id);
    if (!mysqli_stmt_execute($stmt)) resp(false, 'Error al cambiar estado: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);

    $msg = $nuevo ? 'Turno fijo activado.' : 'Turno fijo desactivado.';
    resp(true, $msg, ['ACTIVO' => $nuevo]);

// ── ELIMINAR ───────────────────────────────────────────────────────────────
case 'eliminar':
    $turno_id = (int)($_POST['turno_id'] ?? 0);
    if (!$turno_id) resp(false, 'turno_id requerido.');
    get_turno($link, $turno_id); // verifica ownership

    $stmt = mysqli_prepare($link, "DELETE FROM turno_fijo WHERE TURNO_FIJO_ID = ?");
    mysqli_stmt_bind_param($stmt, 'i', $turno_id);
    if (!mysqli_stmt_execute($stmt)) resp(false, 'Error al eliminar turno fijo: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);

    resp(true, 'Turno fijo eliminado correctamente.');

// ── DEFAULT ────────────────────────────────────────────────────────────────
default:
    resp(false, 'Acción no reconocida.');
}

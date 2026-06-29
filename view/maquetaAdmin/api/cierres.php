<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';

require_perfil(2);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
function resp($ok,$msg,$data=null){ echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function e($link,$v){ return mysqli_real_escape_string($link, trim($v??'')); }

// ── Helper de ownership ─────────────────────────────────────────────────────
function get_cierre($link, $id) {
    $id = (int)$id;
    $r = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT ci.*, co.USUARIOS_ID AS DUENO_ID
         FROM cierre_cancha ci
         JOIN complejo co ON co.COMPLEJO_ID = ci.COMPLEJO_ID
         WHERE ci.CIERRE_ID = $id"
    ));
    if (!$r) tenancy_deny('Cierre no encontrado.', 404);
    if (!can_complejo($link, $r['COMPLEJO_ID'])) tenancy_deny('Sin permisos sobre este cierre.');
    return $r;
}

// ── Validación de fecha ─────────────────────────────────────────────────────
function valid_date($s) {
    if (!$s) return false;
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s;
}

// Modo solo-lectura por mora: bloquear escritura del panel.
if (in_array($action, ['crear','editar','toggle','eliminar'], true)) assert_tenant_activo($link);

switch($action) {

// ── Listar cierres ──────────────────────────────────────────────────────────
case 'listar':
    $ids        = tenant_complejo_ids($link);
    $scope      = tenant_where($ids, 'ci.COMPLEJO_ID');
    $solo_activos = isset($_GET['solo_activos']) ? (int)$_GET['solo_activos'] : 1;
    $complejo_id  = (int)($_GET['complejo_id'] ?? 0);
    $desde        = e($link, $_GET['desde'] ?? '');

    $where = "WHERE $scope";
    if ($solo_activos) $where .= " AND ci.ACTIVO=1";
    if ($complejo_id)  $where .= " AND ci.COMPLEJO_ID=$complejo_id";
    if ($desde && valid_date($desde)) $where .= " AND ci.CIERRE_FECHA_HASTA >= '$desde'";

    $sql = "
        SELECT ci.CIERRE_ID, ci.COMPLEJO_ID, ci.CANCHA_ID,
               ci.CIERRE_FECHA_DESDE, ci.CIERRE_FECHA_HASTA,
               ci.CIERRE_HORA_DESDE, ci.CIERRE_HORA_HASTA,
               ci.CIERRE_MOTIVO, ci.ACTIVO,
               co.COMPLEJO_NOMBRE,
               ca.CANCHA_NOMBRE
        FROM cierre_cancha ci
        JOIN complejo co ON co.COMPLEJO_ID = ci.COMPLEJO_ID
        LEFT JOIN cancha ca ON ca.CANCHA_ID = ci.CANCHA_ID
        $where
        ORDER BY ci.CIERRE_FECHA_DESDE ASC, co.COMPLEJO_NOMBRE, ca.CANCHA_NOMBRE";

    $q = mysqli_query($link, $sql);
    $rows = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $r['ALCANCE']     = is_null($r['CANCHA_ID']) ? 'complejo' : 'cancha';
        $r['TIPO_CIERRE'] = is_null($r['CIERRE_HORA_DESDE']) ? 'total' : 'parcial';
        $rows[] = $r;
    }
    resp(true, '', $rows);

// ── Selects para modal ──────────────────────────────────────────────────────
case 'selects':
    $scope = tenant_where(tenant_complejo_ids($link), 'COMPLEJO_ID');

    $complejos = [];
    $qc = mysqli_query($link,
        "SELECT COMPLEJO_ID, COMPLEJO_NOMBRE FROM complejo
         WHERE ACTIVO=1 AND $scope ORDER BY COMPLEJO_NOMBRE");
    while ($r = mysqli_fetch_assoc($qc)) $complejos[] = $r;

    $cscope = tenant_where(tenant_complejo_ids($link), 'COMPLEJO_ID');
    $canchas = [];
    $qk = mysqli_query($link,
        "SELECT CANCHA_ID, CANCHA_NOMBRE, COMPLEJO_ID FROM cancha
         WHERE ACTIVO=1 AND $cscope ORDER BY COMPLEJO_ID, CANCHA_NOMBRE");
    while ($r = mysqli_fetch_assoc($qk)) $canchas[] = $r;

    resp(true, '', ['complejos' => $complejos, 'canchas' => $canchas]);

// ── Crear cierre ────────────────────────────────────────────────────────────
case 'crear':
    $complejo_id  = (int)($_POST['complejo_id'] ?? 0);
    $cancha_id    = (int)($_POST['cancha_id']   ?? 0);  // 0 = todo el complejo
    $fecha_desde  = e($link, $_POST['fecha_desde']  ?? '');
    $fecha_hasta  = e($link, $_POST['fecha_hasta']  ?? '');
    $hora_desde   = e($link, $_POST['hora_desde']   ?? '');
    $hora_hasta   = e($link, $_POST['hora_hasta']   ?? '');
    $motivo       = e($link, $_POST['motivo']        ?? '');

    // Validaciones
    if (!$complejo_id)            resp(false, 'El complejo es requerido.');
    if (!valid_date($fecha_desde)) resp(false, 'Fecha desde inválida.');
    if (!valid_date($fecha_hasta)) resp(false, 'Fecha hasta inválida.');
    if ($fecha_hasta < $fecha_desde) resp(false, 'La fecha hasta debe ser igual o posterior a la fecha desde.');
    if (($hora_desde && !$hora_hasta) || (!$hora_desde && $hora_hasta))
        resp(false, 'Debe ingresar hora desde y hora hasta juntas.');
    if ($hora_desde && $hora_hasta && $hora_hasta <= $hora_desde)
        resp(false, 'La hora hasta debe ser posterior a la hora desde.');

    // Ownership
    assert_complejo($link, $complejo_id);
    if ($cancha_id) {
        assert_cancha($link, $cancha_id);
        // Verificar que la cancha pertenece al complejo
        $chk = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT CANCHA_ID FROM cancha WHERE CANCHA_ID=$cancha_id AND COMPLEJO_ID=$complejo_id"
        ));
        if (!$chk) resp(false, 'La cancha no pertenece al complejo indicado.');
    }

    $cancha_val   = $cancha_id ?: 'NULL';
    $hora_d_val   = $hora_desde ? "'$hora_desde'" : 'NULL';
    $hora_h_val   = $hora_hasta ? "'$hora_hasta'" : 'NULL';
    $motivo_val   = $motivo     ? "'$motivo'"     : 'NULL';

    // Todos los valores ya están escapados/casteados; usamos query directa para manejar NULLs
    $sql_ins = "INSERT INTO cierre_cancha
        (COMPLEJO_ID, CANCHA_ID, CIERRE_FECHA_DESDE, CIERRE_FECHA_HASTA,
         CIERRE_HORA_DESDE, CIERRE_HORA_HASTA, CIERRE_MOTIVO, ACTIVO)
        VALUES ($complejo_id, $cancha_val, '$fecha_desde', '$fecha_hasta',
                $hora_d_val, $hora_h_val, $motivo_val, 1)";
    if (!mysqli_query($link, $sql_ins)) resp(false, 'Error al crear el cierre: ' . mysqli_error($link));
    $new_id = mysqli_insert_id($link);
    resp(true, 'Cierre creado correctamente.', ['id' => $new_id]);

// ── Editar cierre ───────────────────────────────────────────────────────────
case 'editar':
    $cierre_id   = (int)($_POST['cierre_id']   ?? 0);
    $complejo_id = (int)($_POST['complejo_id'] ?? 0);
    $cancha_id   = (int)($_POST['cancha_id']   ?? 0);
    $fecha_desde = e($link, $_POST['fecha_desde']  ?? '');
    $fecha_hasta = e($link, $_POST['fecha_hasta']  ?? '');
    $hora_desde  = e($link, $_POST['hora_desde']   ?? '');
    $hora_hasta  = e($link, $_POST['hora_hasta']   ?? '');
    $motivo      = e($link, $_POST['motivo']        ?? '');

    if (!$cierre_id)               resp(false, 'ID de cierre requerido.');
    if (!$complejo_id)             resp(false, 'El complejo es requerido.');
    if (!valid_date($fecha_desde)) resp(false, 'Fecha desde inválida.');
    if (!valid_date($fecha_hasta)) resp(false, 'Fecha hasta inválida.');
    if ($fecha_hasta < $fecha_desde) resp(false, 'La fecha hasta debe ser igual o posterior a la fecha desde.');
    if (($hora_desde && !$hora_hasta) || (!$hora_desde && $hora_hasta))
        resp(false, 'Debe ingresar hora desde y hora hasta juntas.');
    if ($hora_desde && $hora_hasta && $hora_hasta <= $hora_desde)
        resp(false, 'La hora hasta debe ser posterior a la hora desde.');

    // Ownership del cierre existente
    get_cierre($link, $cierre_id);

    // Ownership del nuevo complejo/cancha
    assert_complejo($link, $complejo_id);
    if ($cancha_id) {
        assert_cancha($link, $cancha_id);
        $chk = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT CANCHA_ID FROM cancha WHERE CANCHA_ID=$cancha_id AND COMPLEJO_ID=$complejo_id"
        ));
        if (!$chk) resp(false, 'La cancha no pertenece al complejo indicado.');
    }

    $cancha_val = $cancha_id ? $cancha_id : 'NULL';
    $hora_d_val = $hora_desde ? "'$hora_desde'" : 'NULL';
    $hora_h_val = $hora_hasta ? "'$hora_hasta'" : 'NULL';
    $motivo_val = $motivo     ? "'$motivo'"     : 'NULL';

    $sql_upd = "UPDATE cierre_cancha SET
        COMPLEJO_ID=$complejo_id,
        CANCHA_ID=$cancha_val,
        CIERRE_FECHA_DESDE='$fecha_desde',
        CIERRE_FECHA_HASTA='$fecha_hasta',
        CIERRE_HORA_DESDE=$hora_d_val,
        CIERRE_HORA_HASTA=$hora_h_val,
        CIERRE_MOTIVO=$motivo_val
        WHERE CIERRE_ID=$cierre_id";
    if (!mysqli_query($link, $sql_upd)) resp(false, 'Error al actualizar el cierre: ' . mysqli_error($link));
    resp(true, 'Cierre actualizado correctamente.');

// ── Toggle activo ───────────────────────────────────────────────────────────
case 'toggle':
    $cierre_id = (int)($_POST['cierre_id'] ?? 0);
    if (!$cierre_id) resp(false, 'ID de cierre requerido.');
    $cierre = get_cierre($link, $cierre_id);
    $nuevo = $cierre['ACTIVO'] ? 0 : 1;
    mysqli_query($link, "UPDATE cierre_cancha SET ACTIVO=$nuevo WHERE CIERRE_ID=$cierre_id");
    resp(true, $nuevo ? 'Cierre activado.' : 'Cierre desactivado.', ['activo' => $nuevo]);

// ── Eliminar cierre ─────────────────────────────────────────────────────────
case 'eliminar':
    $cierre_id = (int)($_POST['cierre_id'] ?? 0);
    if (!$cierre_id) resp(false, 'ID de cierre requerido.');
    get_cierre($link, $cierre_id);
    if (!mysqli_query($link, "DELETE FROM cierre_cancha WHERE CIERRE_ID=$cierre_id"))
        resp(false, 'Error al eliminar el cierre: ' . mysqli_error($link));
    resp(true, 'Cierre eliminado.');

default:
    resp(false, 'Acción no reconocida.');
}

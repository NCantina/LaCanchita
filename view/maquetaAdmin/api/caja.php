<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';   // aplica csrf_require() en POST

require_perfil(4); // dueño, encargado, empleado (y SA)

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function resp($ok, $msg, $data = null) { echo json_encode(['ok' => $ok, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE); exit; }

// Auto-crear tabla (respaldo en dev, además de sql/cierre_caja.sql)
mysqli_query($link,
    "CREATE TABLE IF NOT EXISTS cierre_caja (
        CIERRE_CAJA_ID     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        COMPLEJO_ID        INT UNSIGNED NOT NULL,
        USUARIOS_ID        INT UNSIGNED NOT NULL,
        CIERRE_FECHA       DATE NOT NULL,
        FONDO_INICIAL      DECIMAL(10,2) NOT NULL DEFAULT 0,
        EFECTIVO_SISTEMA   DECIMAL(10,2) NOT NULL DEFAULT 0,
        EFECTIVO_DECLARADO DECIMAL(10,2) NOT NULL DEFAULT 0,
        DIFERENCIA         DECIMAL(10,2) NOT NULL DEFAULT 0,
        TOTAL_GENERAL      DECIMAL(10,2) NOT NULL DEFAULT 0,
        NOTAS              TEXT,
        CREATED_AT         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UPDATED_AT         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_complejo_fecha (COMPLEJO_ID, CIERRE_FECHA),
        INDEX idx_complejo (COMPLEJO_ID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

/** Complejos accesibles por el usuario actual (null = SA sin contexto = todos). */
function caja_scope($link) {
    $ids = tenant_complejo_ids($link);
    return [$ids, tenant_where($ids, 'co.COMPLEJO_ID')];
}

/** Valida y normaliza la fecha (YYYY-MM-DD); default hoy. */
function caja_fecha() {
    $f = trim($_GET['fecha'] ?? $_POST['fecha'] ?? '');
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) ? $f : date('Y-m-d');
}

switch ($action) {

// ── COMPLEJOS DEL TENANT (para el selector) ──────────────────────────────────
case 'complejos':
    [$ids, $where] = caja_scope($link);
    if ($ids !== null && count($ids) === 0) resp(true, '', []);
    $rows = [];
    $q = mysqli_query($link,
        "SELECT co.COMPLEJO_ID, co.COMPLEJO_NOMBRE
         FROM complejo co WHERE $where AND co.ACTIVO=1 ORDER BY co.COMPLEJO_NOMBRE");
    if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    resp(true, '', $rows);

// ── ARQUEO DEL DÍA ───────────────────────────────────────────────────────────
case 'arqueo':
    $fecha      = caja_fecha();
    $eFecha     = mysqli_real_escape_string($link, $fecha);
    $complejoId = (int)($_GET['complejo_id'] ?? 0);

    [$ids, $where] = caja_scope($link);
    if ($ids !== null && count($ids) === 0) resp(true, '', ['por_medio'=>[], 'por_empleado'=>[], 'total_general'=>0, 'total_efectivo'=>0, 'cierre'=>null]);

    // Si se pidió un complejo puntual, validarlo y acotar el scope a él
    if ($complejoId) {
        if ($ids !== null && !in_array($complejoId, (array)$ids, true)) resp(false, 'Sin acceso a ese predio.');
        $where = "co.COMPLEJO_ID = $complejoId";
    }

    $joins = "JOIN reserva r ON r.RESERVA_ID = p.RESERVA_ID
              JOIN cancha c   ON c.CANCHA_ID   = r.CANCHA_ID
              JOIN complejo co ON co.COMPLEJO_ID = c.COMPLEJO_ID";
    $filtro = "p.ACTIVO=1 AND DATE(p.PAGO_FECHA)='$eFecha' AND $where";

    // Desglose por medio de pago
    $porMedio = [];
    $q = mysqli_query($link,
        "SELECT p.PAGO_MEDIO, COALESCE(SUM(p.PAGO_MONTO),0) AS total, COUNT(*) AS cnt
         FROM pago p $joins WHERE $filtro GROUP BY p.PAGO_MEDIO");
    if ($q) while ($r = mysqli_fetch_assoc($q)) $porMedio[] = $r;

    // Desglose por empleado que cobró
    $porEmpleado = [];
    $q = mysqli_query($link,
        "SELECT u.USUARIOS_ID, u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO,
                COALESCE(SUM(p.PAGO_MONTO),0) AS total, COUNT(*) AS cnt,
                COALESCE(SUM(CASE WHEN p.PAGO_MEDIO='efectivo' THEN p.PAGO_MONTO ELSE 0 END),0) AS efectivo
         FROM pago p $joins JOIN usuarios u ON u.USUARIOS_ID = p.USUARIOS_ID
         WHERE $filtro GROUP BY u.USUARIOS_ID ORDER BY total DESC");
    if ($q) while ($r = mysqli_fetch_assoc($q)) $porEmpleado[] = $r;

    // Totales
    $tot = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COALESCE(SUM(p.PAGO_MONTO),0) AS total_general,
                COALESCE(SUM(CASE WHEN p.PAGO_MEDIO='efectivo' THEN p.PAGO_MONTO ELSE 0 END),0) AS total_efectivo
         FROM pago p $joins WHERE $filtro"));

    // Cierre existente (solo si se seleccionó un predio puntual)
    $cierre = null;
    if ($complejoId) {
        $cierre = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT * FROM cierre_caja WHERE COMPLEJO_ID=$complejoId AND CIERRE_FECHA='$eFecha' LIMIT 1"));
    }

    resp(true, '', [
        'fecha'          => $fecha,
        'por_medio'      => $porMedio,
        'por_empleado'   => $porEmpleado,
        'total_general'  => (float)($tot['total_general'] ?? 0),
        'total_efectivo' => (float)($tot['total_efectivo'] ?? 0),
        'cierre'         => $cierre,
    ]);

// ── CERRAR CAJA (arqueo del día para un predio) ──────────────────────────────
case 'cerrar':
    assert_tenant_activo($link); // modo solo-lectura por mora
    $fecha      = caja_fecha();
    $eFecha     = mysqli_real_escape_string($link, $fecha);
    $complejoId = (int)($_POST['complejo_id'] ?? 0);
    $fondo      = max(0, (float)($_POST['fondo_inicial'] ?? 0));
    $declarado  = max(0, (float)($_POST['efectivo_declarado'] ?? 0));
    $notas      = mysqli_real_escape_string($link, trim($_POST['notas'] ?? ''));

    if (!$complejoId) resp(false, 'Seleccioná un predio para cerrar la caja.');
    [$ids] = caja_scope($link);
    if ($ids !== null && !in_array($complejoId, (array)$ids, true)) resp(false, 'Sin acceso a ese predio.');

    // Totales reales del día para ese predio
    $tot = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COALESCE(SUM(p.PAGO_MONTO),0) AS total_general,
                COALESCE(SUM(CASE WHEN p.PAGO_MEDIO='efectivo' THEN p.PAGO_MONTO ELSE 0 END),0) AS total_efectivo
         FROM pago p
         JOIN reserva r ON r.RESERVA_ID = p.RESERVA_ID
         JOIN cancha c  ON c.CANCHA_ID   = r.CANCHA_ID
         WHERE p.ACTIVO=1 AND DATE(p.PAGO_FECHA)='$eFecha' AND c.COMPLEJO_ID=$complejoId"));
    $efectivoSistema = (float)($tot['total_efectivo'] ?? 0);
    $totalGeneral    = (float)($tot['total_general'] ?? 0);
    $diferencia      = $declarado - ($efectivoSistema + $fondo);
    $uid             = (int)current_uid();

    $stmt = mysqli_prepare($link,
        "INSERT INTO cierre_caja
           (COMPLEJO_ID, USUARIOS_ID, CIERRE_FECHA, FONDO_INICIAL, EFECTIVO_SISTEMA,
            EFECTIVO_DECLARADO, DIFERENCIA, TOTAL_GENERAL, NOTAS)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            USUARIOS_ID=VALUES(USUARIOS_ID), FONDO_INICIAL=VALUES(FONDO_INICIAL),
            EFECTIVO_SISTEMA=VALUES(EFECTIVO_SISTEMA), EFECTIVO_DECLARADO=VALUES(EFECTIVO_DECLARADO),
            DIFERENCIA=VALUES(DIFERENCIA), TOTAL_GENERAL=VALUES(TOTAL_GENERAL), NOTAS=VALUES(NOTAS)");
    mysqli_stmt_bind_param($stmt, 'iisddddds',
        $complejoId, $uid, $fecha, $fondo, $efectivoSistema, $declarado, $diferencia, $totalGeneral, $notas);
    if (!mysqli_stmt_execute($stmt)) resp(false, 'Error al cerrar la caja.');

    resp(true, 'Caja cerrada correctamente.', [
        'efectivo_sistema'   => $efectivoSistema,
        'efectivo_declarado' => $declarado,
        'fondo_inicial'      => $fondo,
        'diferencia'         => $diferencia,
        'total_general'      => $totalGeneral,
    ]);

// ── HISTORIAL DE CIERRES ─────────────────────────────────────────────────────
case 'historial':
    [$ids, $where] = caja_scope($link);
    if ($ids !== null && count($ids) === 0) resp(true, '', []);
    $complejoId = (int)($_GET['complejo_id'] ?? 0);
    if ($complejoId) {
        if ($ids !== null && !in_array($complejoId, (array)$ids, true)) resp(false, 'Sin acceso a ese predio.');
        $where = "co.COMPLEJO_ID = $complejoId";
    }
    $rows = [];
    $q = mysqli_query($link,
        "SELECT cc.*, co.COMPLEJO_NOMBRE, u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO
         FROM cierre_caja cc
         JOIN complejo co ON co.COMPLEJO_ID = cc.COMPLEJO_ID
         LEFT JOIN usuarios u ON u.USUARIOS_ID = cc.USUARIOS_ID
         WHERE $where
         ORDER BY cc.CIERRE_FECHA DESC, co.COMPLEJO_NOMBRE ASC
         LIMIT 60");
    if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    resp(true, '', $rows);

default:
    resp(false, 'Acción no reconocida.');
}

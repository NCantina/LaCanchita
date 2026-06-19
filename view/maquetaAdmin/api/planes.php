<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';

require_perfil(2);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function resp($ok,$msg,$data=null){ echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE); exit; }
function e($link,$v){ return mysqli_real_escape_string($link,trim($v??'')); }

// Auto-crear tabla si no existe
mysqli_query($link,
    "CREATE TABLE IF NOT EXISTS plan_predio (
        PLAN_ID          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        COMPLEJO_ID      INT UNSIGNED NOT NULL,
        PLAN_NOMBRE      VARCHAR(100) NOT NULL,
        PLAN_DESCRIPCION TEXT,
        PLAN_PRECIO      DECIMAL(10,2) NOT NULL DEFAULT 0,
        PLAN_CREDITOS    SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=ilimitado',
        PLAN_DURACION    SMALLINT UNSIGNED NOT NULL DEFAULT 30 COMMENT 'días de vigencia',
        ACTIVO           TINYINT(1) NOT NULL DEFAULT 1,
        CREATED_AT       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_complejo (COMPLEJO_ID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

switch($action) {

// ── LISTAR ──────────────────────────────────────────────────────────────────
case 'listar':
    $ids   = tenant_complejo_ids($link);
    $where = tenant_where($ids, 'p.COMPLEJO_ID');
    $rows  = [];
    $q = mysqli_query($link,
        "SELECT p.PLAN_ID, p.COMPLEJO_ID, p.PLAN_NOMBRE, p.PLAN_DESCRIPCION,
                p.PLAN_PRECIO, p.PLAN_CREDITOS, p.PLAN_DURACION, p.ACTIVO,
                c.COMPLEJO_NOMBRE
         FROM plan_predio p
         JOIN complejo c ON c.COMPLEJO_ID = p.COMPLEJO_ID
         WHERE $where
         ORDER BY c.COMPLEJO_NOMBRE ASC, p.ACTIVO DESC, p.PLAN_NOMBRE ASC"
    );
    while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    resp(true, '', $rows);

// ── CREAR ────────────────────────────────────────────────────────────────────
case 'crear':
    $complejoId = (int)($_POST['complejo_id'] ?? 0);
    $nombre     = e($link, $_POST['nombre']      ?? '');
    $desc       = e($link, $_POST['descripcion'] ?? '');
    $precio     = (float)($_POST['precio']       ?? 0);
    $creditos   = max(0, (int)($_POST['creditos']  ?? 0));
    $duracion   = max(1, (int)($_POST['duracion']  ?? 30));

    if (!$complejoId) resp(false, 'Seleccioná un predio.');
    if (!$nombre)     resp(false, 'El nombre del plan es obligatorio.');
    if ($precio < 0)  resp(false, 'El precio no puede ser negativo.');

    // Verificar que el complejo pertenece al tenant
    $ids = tenant_complejo_ids($link);
    if ($ids !== null && !in_array($complejoId, (array)$ids)) resp(false, 'Sin acceso a ese predio.');

    mysqli_query($link,
        "INSERT INTO plan_predio (COMPLEJO_ID, PLAN_NOMBRE, PLAN_DESCRIPCION, PLAN_PRECIO, PLAN_CREDITOS, PLAN_DURACION)
         VALUES ($complejoId, '$nombre', '$desc', $precio, $creditos, $duracion)"
    );
    resp(true, 'Plan creado correctamente.', ['id' => mysqli_insert_id($link)]);

// ── EDITAR ───────────────────────────────────────────────────────────────────
case 'editar':
    $id       = (int)($_POST['id']           ?? 0);
    $nombre   = e($link, $_POST['nombre']      ?? '');
    $desc     = e($link, $_POST['descripcion'] ?? '');
    $precio   = (float)($_POST['precio']       ?? 0);
    $creditos = max(0, (int)($_POST['creditos']  ?? 0));
    $duracion = max(1, (int)($_POST['duracion']  ?? 30));

    if (!$id)     resp(false, 'ID inválido.');
    if (!$nombre) resp(false, 'El nombre del plan es obligatorio.');

    // Verificar tenant
    $p = mysqli_fetch_assoc(mysqli_query($link, "SELECT COMPLEJO_ID FROM plan_predio WHERE PLAN_ID=$id"));
    if (!$p) resp(false, 'Plan no encontrado.');
    $ids = tenant_complejo_ids($link);
    if ($ids !== null && !in_array((int)$p['COMPLEJO_ID'], (array)$ids)) resp(false, 'Sin acceso.');

    mysqli_query($link,
        "UPDATE plan_predio SET PLAN_NOMBRE='$nombre', PLAN_DESCRIPCION='$desc',
         PLAN_PRECIO=$precio, PLAN_CREDITOS=$creditos, PLAN_DURACION=$duracion
         WHERE PLAN_ID=$id"
    );
    resp(true, 'Plan actualizado correctamente.');

// ── TOGGLE ───────────────────────────────────────────────────────────────────
case 'toggle':
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) resp(false, 'ID inválido.');
    $p = mysqli_fetch_assoc(mysqli_query($link, "SELECT COMPLEJO_ID, ACTIVO FROM plan_predio WHERE PLAN_ID=$id"));
    if (!$p) resp(false, 'Plan no encontrado.');
    $ids = tenant_complejo_ids($link);
    if ($ids !== null && !in_array((int)$p['COMPLEJO_ID'], (array)$ids)) resp(false, 'Sin acceso.');
    $nuevo = $p['ACTIVO'] ? 0 : 1;
    mysqli_query($link, "UPDATE plan_predio SET ACTIVO=$nuevo WHERE PLAN_ID=$id");
    resp(true, $nuevo ? 'Plan activado.' : 'Plan desactivado.', ['activo' => $nuevo]);

// ── LISTAR COMPLEJOS (para el selector del modal) ────────────────────────────
case 'mis_complejos':
    $ids   = tenant_complejo_ids($link);
    $where = tenant_where($ids, 'COMPLEJO_ID');
    $rows  = [];
    $q = mysqli_query($link, "SELECT COMPLEJO_ID, COMPLEJO_NOMBRE FROM complejo WHERE $where AND ACTIVO=1 ORDER BY COMPLEJO_NOMBRE");
    while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    resp(true, '', $rows);

default:
    resp(false, 'Acción no reconocida.');
}

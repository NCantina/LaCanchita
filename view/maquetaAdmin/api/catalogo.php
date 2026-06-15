<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';
require_perfil(2);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$tabla  = $_POST['tabla']  ?? $_GET['tabla']  ?? '';

// Tablas permitidas y su configuración
$tablas = [
    'tipo_cancha'  => ['id' => 'TIPO_CANCHA_ID',   'nombre' => 'TIPO_CANCHA_NOMBRE',   'icono' => 'TIPO_CANCHA_ICONO'],
    'tipo_complejo'=> ['id' => 'TIPO_COMPLEJO_ID',  'nombre' => 'TIPO_COMPLEJO_NOMBRE', 'icono' => 'TIPO_COMPLEJO_ICONO'],
    'medio_pago'   => ['id' => 'MEDIO_PAGO_ID',     'nombre' => 'MEDIO_PAGO_NOMBRE',    'icono' => 'MEDIO_PAGO_ICONO'],
];

if (!array_key_exists($tabla, $tablas)) {
    echo json_encode(['ok' => false, 'msg' => 'Tabla no válida.']);
    exit;
}

$cfg = $tablas[$tabla];

function resp($ok, $msg, $data = null) {
    echo json_encode(['ok' => $ok, 'msg' => $msg, 'data' => $data]);
    exit;
}

function esc($link, $val) {
    return mysqli_real_escape_string($link, trim($val));
}

switch ($action) {

    // ── LISTAR ──────────────────────────────
    case 'listar':
        $q = mysqli_query($link, "SELECT * FROM `$tabla` ORDER BY ACTIVO DESC, {$cfg['nombre']} ASC");
        $rows = [];
        while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
        resp(true, '', $rows);

    // ── CREAR ────────────────────────────────
    case 'crear':
        $nombre = esc($link, $_POST['nombre'] ?? '');
        $icono  = esc($link, $_POST['icono']  ?? '');
        if ($nombre === '') resp(false, 'El nombre es obligatorio.');

        $check = mysqli_query($link, "SELECT 1 FROM `$tabla` WHERE {$cfg['nombre']} = '$nombre'");
        if (mysqli_num_rows($check) > 0) resp(false, 'Ya existe un registro con ese nombre.');

        $q = mysqli_query($link,
            "INSERT INTO `$tabla` ({$cfg['nombre']}, {$cfg['icono']}, ACTIVO)
             VALUES ('$nombre', " . ($icono ? "'$icono'" : 'NULL') . ", 1)"
        );
        if (!$q) resp(false, 'Error al crear: ' . mysqli_error($link));
        resp(true, 'Creado correctamente.', ['id' => mysqli_insert_id($link)]);

    // ── EDITAR ───────────────────────────────
    case 'editar':
        $id     = (int)($_POST['id']     ?? 0);
        $nombre = esc($link, $_POST['nombre'] ?? '');
        $icono  = esc($link, $_POST['icono']  ?? '');
        if (!$id)         resp(false, 'ID inválido.');
        if ($nombre === '') resp(false, 'El nombre es obligatorio.');

        $check = mysqli_query($link,
            "SELECT 1 FROM `$tabla` WHERE {$cfg['nombre']} = '$nombre' AND {$cfg['id']} != $id"
        );
        if (mysqli_num_rows($check) > 0) resp(false, 'Ya existe otro registro con ese nombre.');

        $q = mysqli_query($link,
            "UPDATE `$tabla` SET {$cfg['nombre']} = '$nombre',
             {$cfg['icono']} = " . ($icono ? "'$icono'" : 'NULL') . "
             WHERE {$cfg['id']} = $id"
        );
        if (!$q) resp(false, 'Error al editar: ' . mysqli_error($link));
        resp(true, 'Guardado correctamente.');

    // ── TOGGLE ACTIVO ────────────────────────
    case 'toggle':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) resp(false, 'ID inválido.');

        $cur = mysqli_fetch_assoc(mysqli_query($link, "SELECT ACTIVO FROM `$tabla` WHERE {$cfg['id']} = $id"));
        if (!$cur) resp(false, 'Registro no encontrado.');

        $nuevo = $cur['ACTIVO'] ? 0 : 1;
        mysqli_query($link, "UPDATE `$tabla` SET ACTIVO = $nuevo WHERE {$cfg['id']} = $id");
        resp(true, $nuevo ? 'Activado.' : 'Desactivado.', ['activo' => $nuevo]);

    default:
        resp(false, 'Acción no reconocida.');
}

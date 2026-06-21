<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';

require_perfil(2); // Solo dueños

function resp($ok,$msg,$data=null){ echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE); exit; }

$raw = file_get_contents('php://input');
$body = $raw ? json_decode($raw, true) : null;
if (!$body) resp(false, 'Datos inválidos.');

$predio  = $body['predio']  ?? [];
$cancha  = $body['cancha']  ?? [];
$franjas = $body['franjas'] ?? [];

// ── Validar predio ─────────────────────────────────────────────────────────────
$nombre = trim($predio['nombre']       ?? '');
$dir    = trim($predio['direccion']    ?? '');
$locId  = (int)($predio['localidad_id'] ?? 0);
$tel    = trim($predio['telefono']     ?? '');
$email  = trim($predio['email']        ?? '');
if (!$nombre) resp(false, 'El nombre del predio es obligatorio.');
if (!$dir)    resp(false, 'La dirección del predio es obligatoria.');
if (!$locId)  resp(false, 'Seleccioná una localidad.');

// ── Validar cancha ─────────────────────────────────────────────────────────────
$cNombre = trim($cancha['nombre']          ?? '');
$cTipo   = (int)($cancha['tipo_cancha_id'] ?? 0);
if (!$cNombre) resp(false, 'El nombre de la cancha es obligatorio.');
if (!$cTipo)   resp(false, 'Seleccioná el tipo de cancha.');

// ── Validar franjas ────────────────────────────────────────────────────────────
if (!is_array($franjas) || !count($franjas)) {
    resp(false, 'Debés agregar al menos una franja horaria.');
}

$uid = current_uid();
function es($link, $v) { return mysqli_real_escape_string($link, $v); }

mysqli_begin_transaction($link);
try {
    // 1. Crear complejo
    $n  = es($link, $nombre);
    $d  = es($link, $dir);
    $t  = es($link, $tel);
    $em = es($link, $email);
    mysqli_query($link,
        "INSERT INTO complejo
            (COMPLEJO_NOMBRE, COMPLEJO_DIRECCION, COMPLEJO_TELEFONO, COMPLEJO_EMAIL, LOCALIDAD_ID, USUARIOS_ID, ACTIVO)
         VALUES ('$n','$d','$t','$em',$locId,$uid,1)"
    );
    $complejoId = (int)mysqli_insert_id($link);
    if (!$complejoId) throw new Exception('Error al crear el predio.');

    // 2. Crear cancha
    $cn = es($link, $cNombre);
    mysqli_query($link,
        "INSERT INTO cancha (CANCHA_NOMBRE, TIPO_CANCHA_ID, COMPLEJO_ID, ACTIVO)
         VALUES ('$cn',$cTipo,$complejoId,1)"
    );
    $canchaId = (int)mysqli_insert_id($link);
    if (!$canchaId) throw new Exception('Error al crear la cancha.');

    // 3. Crear franjas
    $franjasOk = 0;
    foreach ($franjas as $f) {
        $ini    = trim($f['ini']    ?? '');
        $fin    = trim($f['fin']    ?? '');
        $precio = (float)($f['precio'] ?? 0);
        $dias   = array_filter(array_map('intval', (array)($f['dias'] ?? [])));

        if (!$ini || !$fin || $fin <= $ini || !$dias) continue;
        if ($precio <= 0) throw new Exception('El precio de cada franja debe ser mayor a 0.');

        $iniSql = es($link, strlen($ini) === 5 ? "$ini:00" : $ini);
        $finSql = es($link, strlen($fin) === 5 ? "$fin:00" : $fin);

        mysqli_query($link,
            "INSERT INTO franja_horaria
                (CANCHA_ID, FRANJA_HORA_INICIO, FRANJA_HORA_FIN, FRANJA_PRECIO, FRANJA_SENA, ACTIVO)
             VALUES ($canchaId,'$iniSql','$finSql',$precio,0,1)"
        );
        $franjaId = (int)mysqli_insert_id($link);
        if (!$franjaId) throw new Exception('Error al guardar la franja horaria.');

        foreach ($dias as $diaId) {
            if ($diaId < 1 || $diaId > 7) continue;
            mysqli_query($link,
                "INSERT INTO franja_dia (FRANJA_ID, DIA_ID) VALUES ($franjaId, $diaId)"
            );
        }
        $franjasOk++;
    }

    if (!$franjasOk) throw new Exception('Ninguna franja resultó válida. Revisá los horarios.');

    mysqli_commit($link);
    resp(true, '¡Predio configurado exitosamente!', ['complejo_id' => $complejoId]);

} catch (Exception $e) {
    mysqli_rollback($link);
    resp(false, $e->getMessage());
}

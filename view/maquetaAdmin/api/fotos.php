<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';   // aplica csrf_require() en POST

require_once '../../../config/dist/script/php/capabilities.php';
require_perfil(3);              // dueño, encargado (y SA) gestionan fotos del predio
require_cap('config.canchas');  // corta al empleado (4)

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function resp($ok, $msg, $data = null) { echo json_encode(['ok' => $ok, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE); exit; }

// Auto-crear tabla (respaldo en dev, además de sql/complejo_foto.sql)
mysqli_query($link,
    "CREATE TABLE IF NOT EXISTS complejo_foto (
        FOTO_ID        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        COMPLEJO_ID    INT UNSIGNED NOT NULL,
        FOTO_PATH      VARCHAR(255) NOT NULL,
        FOTO_PRINCIPAL TINYINT(1) NOT NULL DEFAULT 0,
        FOTO_ORDEN     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        CREATED_AT     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_complejo (COMPLEJO_ID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

const MAX_FOTOS      = 8;
const MAX_BYTES      = 5 * 1024 * 1024; // 5 MB
$ROOT                = dirname(__DIR__, 3);            // raíz del repo

/** Ruta de una foto del predio validada contra el tenant. Devuelve la fila o corta. */
function foto_tenant($link, $fotoId) {
    $fotoId = (int)$fotoId;
    $r = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT FOTO_ID, COMPLEJO_ID, FOTO_PATH, FOTO_PRINCIPAL FROM complejo_foto WHERE FOTO_ID=$fotoId LIMIT 1"));
    if (!$r) resp(false, 'Foto no encontrada.');
    assert_complejo($link, (int)$r['COMPLEJO_ID']);   // pertenece al tenant
    return $r;
}

switch ($action) {

// ── LISTAR ───────────────────────────────────────────────────────────────────
case 'listar':
    $cid = (int)($_GET['complejo_id'] ?? 0);
    if (!$cid) resp(false, 'Predio requerido.');
    assert_complejo($link, $cid);
    $rows = [];
    $q = mysqli_query($link,
        "SELECT FOTO_ID, COMPLEJO_ID, FOTO_PATH, FOTO_PRINCIPAL, FOTO_ORDEN
         FROM complejo_foto WHERE COMPLEJO_ID=$cid
         ORDER BY FOTO_PRINCIPAL DESC, FOTO_ORDEN ASC, FOTO_ID ASC");
    while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    resp(true, '', $rows);

// ── SUBIR ────────────────────────────────────────────────────────────────────
case 'subir':
    assert_tenant_activo($link); // modo solo-lectura por mora
    $cid = (int)($_POST['complejo_id'] ?? 0);
    if (!$cid) resp(false, 'Predio requerido.');
    assert_complejo($link, $cid);

    if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        resp(false, 'No se recibió la imagen o hubo un error en la subida.');
    }
    $file = $_FILES['foto'];
    if ($file['size'] > MAX_BYTES) resp(false, 'La imagen supera el máximo de 5 MB.');
    if (!is_uploaded_file($file['tmp_name'])) resp(false, 'Subida inválida.');

    // Validar que sea una imagen real (no confiar en la extensión del cliente)
    $info = @getimagesize($file['tmp_name']);
    $extPorTipo = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($extPorTipo[$info[2]])) {
        resp(false, 'Formato no soportado. Subí JPG, PNG o WEBP.');
    }
    $ext = $extPorTipo[$info[2]];

    // Límite de cantidad
    $cnt = (int)(mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) AS n FROM complejo_foto WHERE COMPLEJO_ID=$cid"))['n'] ?? 0);
    if ($cnt >= MAX_FOTOS) resp(false, 'Llegaste al máximo de ' . MAX_FOTOS . ' fotos por predio.');

    // Guardar el archivo con nombre seguro generado por el server
    global $ROOT;
    $dirFs  = $ROOT . '/config/dist/img/predios/' . $cid;
    if (!is_dir($dirFs) && !@mkdir($dirFs, 0775, true) && !is_dir($dirFs)) {
        resp(false, 'No se pudo preparar la carpeta de imágenes.');
    }
    $nombre = bin2hex(random_bytes(8)) . '.' . $ext;
    $destFs = $dirFs . '/' . $nombre;
    if (!@move_uploaded_file($file['tmp_name'], $destFs)) {
        resp(false, 'No se pudo guardar la imagen.');
    }
    @chmod($destFs, 0644);
    $webPath = 'config/dist/img/predios/' . $cid . '/' . $nombre;

    // Primera foto del predio → principal
    $principal = $cnt === 0 ? 1 : 0;
    $ePath = mysqli_real_escape_string($link, $webPath);
    mysqli_query($link,
        "INSERT INTO complejo_foto (COMPLEJO_ID, FOTO_PATH, FOTO_PRINCIPAL, FOTO_ORDEN)
         VALUES ($cid, '$ePath', $principal, $cnt)");
    resp(true, 'Foto subida.', ['id' => mysqli_insert_id($link), 'path' => $webPath, 'principal' => $principal]);

// ── MARCAR PRINCIPAL ─────────────────────────────────────────────────────────
case 'principal':
    assert_tenant_activo($link);
    $r   = foto_tenant($link, $_POST['foto_id'] ?? 0);
    $cid = (int)$r['COMPLEJO_ID'];
    $fid = (int)$r['FOTO_ID'];
    mysqli_query($link, "UPDATE complejo_foto SET FOTO_PRINCIPAL=0 WHERE COMPLEJO_ID=$cid");
    mysqli_query($link, "UPDATE complejo_foto SET FOTO_PRINCIPAL=1 WHERE FOTO_ID=$fid");
    resp(true, 'Foto principal actualizada.');

// ── ELIMINAR ─────────────────────────────────────────────────────────────────
case 'eliminar':
    assert_tenant_activo($link);
    $r   = foto_tenant($link, $_POST['foto_id'] ?? 0);
    $fid = (int)$r['FOTO_ID'];
    $cid = (int)$r['COMPLEJO_ID'];

    // Borrar archivo del disco (solo dentro de la carpeta de predios)
    global $ROOT;
    $path = $r['FOTO_PATH'];
    if (strpos($path, 'config/dist/img/predios/') === 0) {
        $fs = $ROOT . '/' . $path;
        if (is_file($fs)) @unlink($fs);
    }
    mysqli_query($link, "DELETE FROM complejo_foto WHERE FOTO_ID=$fid");

    // Si era la principal, promover otra
    if ((int)$r['FOTO_PRINCIPAL'] === 1) {
        $otra = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT FOTO_ID FROM complejo_foto WHERE COMPLEJO_ID=$cid ORDER BY FOTO_ORDEN ASC, FOTO_ID ASC LIMIT 1"));
        if ($otra) mysqli_query($link, "UPDATE complejo_foto SET FOTO_PRINCIPAL=1 WHERE FOTO_ID=" . (int)$otra['FOTO_ID']);
    }
    resp(true, 'Foto eliminada.');

default:
    resp(false, 'Acción no reconocida.');
}

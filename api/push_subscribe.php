<?php
/**
 * Gestiona suscripciones Web Push.
 * GET  action=vapid_public  → devuelve la clave pública VAPID (no requiere sesión)
 * POST action=subscribe     → guarda suscripción (requiere sesión)
 * POST action=unsubscribe   → elimina suscripción (requiere sesión)
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/dist/script/php/conn.php';
require_once __DIR__ . '/../config/vapid.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'vapid_public') {
    echo json_encode(['ok' => true, 'key' => VAPID_PUBLIC]);
    exit;
}

// Las siguientes acciones requieren sesión
$uid = (int)($_SESSION['usuario_id'] ?? 0);
if (!$uid) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado.']);
    exit;
}

if ($action === 'subscribe') {
    $body = json_decode(file_get_contents('php://input'), true);
    $endpoint = trim($body['endpoint']          ?? '');
    $p256dh   = trim($body['keys']['p256dh']    ?? '');
    $auth     = trim($body['keys']['auth']       ?? '');
    $ua       = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200);

    if (!$endpoint || !$p256dh || !$auth) {
        echo json_encode(['ok' => false, 'msg' => 'Datos de suscripción incompletos.']);
        exit;
    }

    $stmt = mysqli_prepare($link,
        "INSERT INTO push_subscriptions (USUARIOS_ID, ENDPOINT, P256DH, AUTH, USER_AGENT)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE USUARIOS_ID=VALUES(USUARIOS_ID), P256DH=VALUES(P256DH), AUTH=VALUES(AUTH)"
    );
    mysqli_stmt_bind_param($stmt, 'issss', $uid, $endpoint, $p256dh, $auth, $ua);
    $ok = mysqli_stmt_execute($stmt);
    echo json_encode(['ok' => $ok]);
    exit;
}

if ($action === 'unsubscribe') {
    $body     = json_decode(file_get_contents('php://input'), true);
    $endpoint = trim($body['endpoint'] ?? '');
    if (!$endpoint) { echo json_encode(['ok' => false]); exit; }
    $ep   = mysqli_real_escape_string($link, $endpoint);
    mysqli_query($link, "DELETE FROM push_subscriptions WHERE ENDPOINT='$ep' AND USUARIOS_ID=$uid");
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Acción no reconocida.']);

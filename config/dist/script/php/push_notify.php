<?php
require_once __DIR__ . '/../../../../vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Envía notificaciones Web Push a todos los dispositivos suscritos de un usuario.
 *
 * @param int    $usuarios_id  ID del destinatario
 * @param string $titulo
 * @param string $cuerpo
 * @param array  $data         Datos extra para el SW (url, tipo, etc.)
 */
function enviarPush(int $usuarios_id, string $titulo, string $cuerpo, array $data = []): void {
    if (!defined('VAPID_PUBLIC') || !defined('VAPID_PRIVATE')) return;

    global $link;

    $q = mysqli_query($link,
        "SELECT ENDPOINT, P256DH, AUTH FROM push_subscriptions WHERE USUARIOS_ID=$usuarios_id"
    );
    if (!$q || mysqli_num_rows($q) === 0) return;

    $webPush = new WebPush([
        'VAPID' => [
            'subject'    => VAPID_SUBJECT,
            'publicKey'  => VAPID_PUBLIC,
            'privateKey' => VAPID_PRIVATE,
        ],
    ]);
    $webPush->setReuseVAPIDHeaders(true);

    $payload = json_encode([
        'title' => $titulo,
        'body'  => $cuerpo,
        'icon'  => '/config/dist/img/pwa/icon-192.png',
        'badge' => '/config/dist/img/pwa/icon-192.png',
        'data'  => $data,
    ]);

    $toDelete = [];
    while ($row = mysqli_fetch_assoc($q)) {
        $sub = Subscription::create([
            'endpoint'        => $row['ENDPOINT'],
            'keys'            => [
                'p256dh' => $row['P256DH'],
                'auth'   => $row['AUTH'],
            ],
        ]);
        $webPush->queueNotification($sub, $payload);
        $toDelete[$row['ENDPOINT']] = false;
    }

    foreach ($webPush->flush() as $report) {
        if ($report->isSubscriptionExpired()) {
            // Suscripción inválida — la eliminamos
            $ep = mysqli_real_escape_string($link, $report->getRequest()->getUri()->__toString());
            mysqli_query($link,
                "DELETE FROM push_subscriptions WHERE ENDPOINT='$ep'"
            );
        }
    }
}

/**
 * Notificación específica de reserva.
 *
 * @param int    $usuarios_id
 * @param string $tipo        'pendiente' | 'confirmada' | 'cancelada'
 * @param array  $datos       cancha, complejo, fecha, hora_ini, hora_fin
 */
function enviarPushReserva(int $usuarios_id, string $tipo, array $datos): void {
    $cancha   = $datos['cancha']   ?? 'cancha';
    $complejo = $datos['complejo'] ?? '';
    $fecha    = $datos['fecha']    ?? '';
    $horaIni  = substr($datos['hora_ini'] ?? '', 0, 5);
    $horaFin  = substr($datos['hora_fin'] ?? '', 0, 5);

    $hora = $horaIni && $horaFin ? "$horaIni–$horaFin" : '';

    $titulos = [
        'pendiente'  => '⏳ Reserva recibida',
        'confirmada' => '✅ Reserva confirmada',
        'cancelada'  => '❌ Reserva cancelada',
    ];
    $cuerpos = [
        'pendiente'  => "$cancha · $hora",
        'confirmada' => "$cancha · $hora — ¡Ya podés ir!",
        'cancelada'  => "$cancha · $hora",
    ];

    enviarPush(
        $usuarios_id,
        $titulos[$tipo]  ?? '📣 La Canchita',
        $cuerpos[$tipo]  ?? "$cancha · $hora",
        ['tipo' => $tipo, 'url' => '/view/maquetaCliente/LaCanchitaCliente.php']
    );
}

<?php
/**
 * cron/recordatorios_turno.php — Recordatorios de turno (anti no-show).
 *
 * Recorre las reservas próximas y envía al cliente:
 *   • "previo"    (tipo 24h): el turno arranca entre +2h y +24h.
 *   • "inminente" (tipo 2h) : el turno arranca dentro de las próximas 2h.
 *
 * Cada envío se registra en `recordatorio_turno` (UNIQUE por reserva+tipo), así
 * que es idempotente: podés correrlo tan seguido como quieras y no duplica.
 * Estrategia "claim-then-send": primero reclama la fila con INSERT IGNORE y solo
 * envía si la reclamó (affected_rows==1), para que dos corridas solapadas no
 * manden el mismo recordatorio dos veces.
 *
 * CÓMO CORRERLO
 *   • Cron del hosting (CLI, sin clave):
 *       * * * * *  php /ruta/a/cron/recordatorios_turno.php   (o cada 15 min)
 *   • Cron externo por HTTP (cron-job.org, etc.), con clave:
 *       https://tu-dominio/cron/recordatorios_turno.php?key=EL_SECRETO
 *     El secreto sale de la env var CRON_SECRET o de config/cron.php
 *     (define('CRON_SECRET', '...')). Sin secreto configurado, el acceso HTTP
 *     se rechaza (fail-closed). Por CLI nunca pide clave.
 */

$isCli = (php_sapi_name() === 'cli');

// ── Auth para acceso HTTP ────────────────────────────────────────────────────
if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
    $__cronCfg = __DIR__ . '/../config/cron.php';   // gitignoreado
    if (is_file($__cronCfg)) require_once $__cronCfg;
    $expected = getenv('CRON_SECRET') ?: (defined('CRON_SECRET') ? CRON_SECRET : '');
    $given    = $_GET['key'] ?? ($_SERVER['HTTP_X_CRON_KEY'] ?? '');
    if ($expected === '' || !is_string($given) || !hash_equals($expected, $given)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No autorizado.']);
        exit;
    }
}

require_once __DIR__ . '/../config/dist/script/php/conn.php';
require_once __DIR__ . '/../config/dist/script/php/reserva_notify.php';

// ── Respaldo de esquema (mismo CREATE que sql/recordatorio_turno.sql) ─────────
mysqli_query($link,
    "CREATE TABLE IF NOT EXISTS recordatorio_turno (
        RT_ID       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        RESERVA_ID  INT UNSIGNED NOT NULL,
        TIPO        VARCHAR(8) NOT NULL,
        ENVIADO_EN  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_reserva_tipo (RESERVA_ID, TIPO),
        INDEX idx_reserva (RESERVA_ID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

/**
 * Procesa una ventana de recordatorio. Ventanas semiabiertas y disjuntas para
 * que un turno no dispare ambos tipos en el mismo instante.
 *
 * @param string $tipo  '24h' | '2h'
 * @param string $desde expresión SQL para el borde inferior (excluyente)
 * @param string $hasta expresión SQL para el borde superior (incluyente)
 */
function procesarVentana($link, string $tipo, string $desde, string $hasta): int {
    $q = mysqli_query($link,
        "SELECT r.RESERVA_ID
         FROM reserva r
         WHERE r.ACTIVO = 1
           AND r.RESERVA_ESTADO IN ('pendiente','confirmada')
           AND TIMESTAMP(r.RESERVA_FECHA, r.RESERVA_HORA_INICIO) >  $desde
           AND TIMESTAMP(r.RESERVA_FECHA, r.RESERVA_HORA_INICIO) <= $hasta
           AND NOT EXISTS (
               SELECT 1 FROM recordatorio_turno rt
               WHERE rt.RESERVA_ID = r.RESERVA_ID AND rt.TIPO = '$tipo'
           )
         ORDER BY r.RESERVA_FECHA, r.RESERVA_HORA_INICIO
         LIMIT 200"
    );
    if (!$q || $q === true) return 0;

    $enviados = 0;
    while ($row = mysqli_fetch_assoc($q)) {
        $rid = (int)$row['RESERVA_ID'];
        // Claim: reclamamos la fila antes de enviar. Si otra corrida ya la tomó,
        // affected_rows es 0 y la salteamos (evita doble envío en solapes).
        mysqli_query($link,
            "INSERT IGNORE INTO recordatorio_turno (RESERVA_ID, TIPO) VALUES ($rid, '$tipo')"
        );
        if (mysqli_affected_rows($link) !== 1) continue;

        enviarRecordatorioTurno($link, $rid, $tipo);   // best-effort (push + email)
        $enviados++;
    }
    return $enviados;
}

$previo    = procesarVentana($link, '24h', 'NOW() + INTERVAL 2 HOUR',  'NOW() + INTERVAL 24 HOUR');
$inminente = procesarVentana($link, '2h',  'NOW()',                    'NOW() + INTERVAL 2 HOUR');

$out = ['ok' => true, 'enviados' => ['24h' => $previo, '2h' => $inminente], 'total' => $previo + $inminente];

if ($isCli) {
    echo json_encode($out) . "\n";
} else {
    echo json_encode($out);
}

<?php
/**
 * Notificaciones al CREAR una reserva.
 *
 * Centraliza el aviso que faltaba: cuando se crea una reserva 'pendiente'
 *  1) el CLIENTE recibe su comprobante (push + email "reserva recibida"), y
 *  2) el DUEÑO y los ENCARGADOS asignados a la cancha reciben un push de
 *     "nueva reserva pendiente" en tiempo real (sin depender del polling).
 *
 * Es best-effort: cualquier fallo de mail/push se traga para no romper la
 * reserva (que ya está commiteada cuando se llama a esta función).
 */
require_once __DIR__ . '/push_notify.php';
require_once __DIR__ . '/mailer.php';

/**
 * @param string $tipoCliente  'pendiente' | 'confirmada' — qué aviso recibe el cliente.
 * @param bool   $avisarStaff   si true, también notifica al dueño + encargados de la cancha
 *                              (se desactiva cuando es el propio staff quien crea la reserva).
 */
function notificarReservaCreada($link, int $reservaId, string $tipoCliente = 'pendiente', bool $avisarStaff = true): void {
    $rid = (int)$reservaId;
    if ($rid <= 0) return;
    if (!in_array($tipoCliente, ['pendiente', 'confirmada'], true)) $tipoCliente = 'pendiente';

    $row = null;
    $res = mysqli_query($link,
        "SELECT r.RESERVA_ID, r.RESERVA_FECHA, r.RESERVA_HORA_INICIO, r.RESERVA_HORA_FIN,
                r.RESERVA_PRECIO, r.USUARIOS_ID,
                c.CANCHA_ID, c.CANCHA_NOMBRE,
                co.COMPLEJO_NOMBRE, co.USUARIOS_ID AS DUENO_ID,
                u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO, u.USUARIOS_EMAIL
         FROM reserva r
         JOIN cancha c    ON c.CANCHA_ID    = r.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID = c.COMPLEJO_ID
         JOIN usuarios u  ON u.USUARIOS_ID  = r.USUARIOS_ID
         WHERE r.RESERVA_ID = $rid LIMIT 1"
    );
    if ($res && $res !== true) $row = mysqli_fetch_assoc($res);
    if (!$row) return;

    $datos = [
        'nombre'     => $row['USUARIOS_NOMBRE']   ?? '',
        'apellido'   => $row['USUARIOS_APELLIDO'] ?? '',
        'email'      => $row['USUARIOS_EMAIL']    ?? '',
        'cancha'     => $row['CANCHA_NOMBRE']     ?? '',
        'complejo'   => $row['COMPLEJO_NOMBRE']   ?? '',
        'fecha'      => $row['RESERVA_FECHA']     ?? '',
        'hora_ini'   => $row['RESERVA_HORA_INICIO'] ?? '',
        'hora_fin'   => $row['RESERVA_HORA_FIN']    ?? '',
        'precio'     => $row['RESERVA_PRECIO']    ?? 0,
        'reserva_id' => $row['RESERVA_ID']        ?? '',
    ];

    // 1) Cliente: comprobante de la reserva (recibida o confirmada)
    try { enviarPushReserva((int)$row['USUARIOS_ID'], $tipoCliente, $datos); } catch (\Throwable $e) {}
    try { enviarEmailReserva($tipoCliente, $datos); } catch (\Throwable $e) {}

    if (!$avisarStaff) return;

    // 2) Dueño + encargados de la cancha: aviso de nueva reserva pendiente
    $destinos = [];
    if (!empty($row['DUENO_ID'])) $destinos[(int)$row['DUENO_ID']] = true;

    $canchaId = (int)$row['CANCHA_ID'];
    $qe = mysqli_query($link,
        "SELECT USUARIOS_ID FROM cancha_encargado WHERE CANCHA_ID = $canchaId AND ACTIVO = 1"
    );
    if ($qe && $qe !== true) {
        while ($e = mysqli_fetch_assoc($qe)) $destinos[(int)$e['USUARIOS_ID']] = true;
    }

    $hIni    = substr($row['RESERVA_HORA_INICIO'] ?? '', 0, 5);
    $hFin    = substr($row['RESERVA_HORA_FIN'] ?? '', 0, 5);
    $cliente = trim(($row['USUARIOS_NOMBRE'] ?? '') . ' ' . ($row['USUARIOS_APELLIDO'] ?? ''));
    $titulo  = '🆕 Nueva reserva pendiente';
    $cuerpo  = trim(($row['CANCHA_NOMBRE'] ?? 'Cancha') . " · $hIni–$hFin" . ($cliente ? " · $cliente" : ''));

    foreach (array_keys($destinos) as $destId) {
        if ($destId <= 0) continue;
        try {
            enviarPush($destId, $titulo, $cuerpo, [
                'tipo' => 'nueva_reserva',
                'url'  => '/view/maquetaAdmin/Dashboard.php',
            ]);
        } catch (\Throwable $e) {}
    }
}

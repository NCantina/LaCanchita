<?php
/**
 * API de reservas del módulo encargado.
 *
 * Solo expone las acciones que encargados y empleados (perfil 3-4) necesitan:
 *   listar          — agenda del día filtrada a sus canchas
 *   confirmar       — confirmar una reserva pendiente
 *   registrar_pago  — registrar cobro sobre una reserva confirmada
 *
 * Demás acciones (crear_admin, rechazar, agenda_grid, reportes, etc.)
 * quedan restringidas al panel admin (perfil 1-2).
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';

require_perfil(4); // bloquea sin-sesión (401) y clientes (403)

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (!in_array($action, ['listar', 'confirmar', 'registrar_pago'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Acción no disponible en el panel de encargado.']);
    exit;
}

// Delegar a la implementación compartida.
// Conn.php y tenancy.php ya están cargados (require_once es idempotente).
// El archivo compartido re-verifica require_perfil() internamente por acción.
require __DIR__ . '/../../maquetaAdmin/api/reservas.php';

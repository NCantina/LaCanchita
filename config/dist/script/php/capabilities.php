<?php
/**
 * capabilities.php — Capacidades fijas por rol (autorización por acción).
 *
 * Complementa a require_perfil() (jerárquico): las capacidades distinguen QUÉ
 * puede hacer cada rol, en particular encargado (3) vs empleado (4).
 * Fuente de verdad única: CAPS_POR_PERFIL. Sin tabla de permisos por usuario.
 *
 * Incluir DESPUÉS de conn.php (y de tenancy.php en APIs). No abre la sesión:
 * asume que el caller ya hizo session_start() (todas las APIs lo hacen).
 *
 * Spec: docs/superpowers/specs/2026-07-09-roles-y-capacidades-design.md
 */

const CAPS_POR_PERFIL = [
    1 => ['saas.gestion'],   // SA "pelado"; en modo soporte hereda las del dueño (perfil_efectivo)
    2 => ['reserva.crear','reserva.confirmar','reserva.cancelar','pago.registrar',
          'caja.cerrar','reportes.ver','config.canchas','staff.empleados',
          'staff.encargados','billing.suscripcion'],
    3 => ['reserva.crear','reserva.confirmar','reserva.cancelar','pago.registrar',
          'caja.cerrar','reportes.ver','config.canchas','staff.empleados'],
    4 => ['reserva.crear','reserva.confirmar','reserva.cancelar','pago.registrar',
          'caja.cerrar'],
    5 => [],
];

/** Perfil real de la sesión; si el SA está en modo soporte, opera como dueño (2). */
function perfil_efectivo(): int {
    $p = (int)($_SESSION['usuario_perfil'] ?? 0);
    if ($p === 1 && !empty($_SESSION['admin_as_dueno'])) return 2;
    return $p;
}

function can(string $cap): bool {
    return in_array($cap, CAPS_POR_PERFIL[perfil_efectivo()] ?? [], true);
}

/** Guard de API: 403 JSON si el perfil efectivo no tiene la capacidad. */
function require_cap(string $cap): void {
    if (can($cap)) return;
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No tenés permisos para esta acción.']);
    exit;
}

/**
 * Destino post-login según perfil. ÚNICA fuente de verdad del ruteo
 * (la usan procesar_login.php y auth_view.php). Ruta relativa a la raíz.
 */
function panel_url_para(int $perfil): string {
    if ($perfil === 5) return 'view/maquetaCliente/LaCanchitaCliente.php';
    if ($perfil === 4) return 'view/maquetaEncargado/PanelEncargado.php';
    return 'view/maquetaAdmin/Dashboard.php';   // 1, 2 y 3
}

/**
 * Auditoría best-effort de acciones sensibles: nunca rompe la acción principal.
 * Registra el usuario real; si el SA está en modo soporte, ACTUA_COMO = dueño asistido.
 */
function registrar_evento($link, string $cap, string $detalle = ''): void {
    try {
        // Respaldo de dev (la migración versionada es sql/auditoria.sql)
        @mysqli_query($link,
            "CREATE TABLE IF NOT EXISTS auditoria (
                AUD_ID       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                USUARIOS_ID  INT UNSIGNED NOT NULL,
                ACTUA_COMO   INT UNSIGNED NULL,
                CAP          VARCHAR(40) NOT NULL,
                DETALLE      VARCHAR(255) NULL,
                IP           VARCHAR(45) NULL,
                CREATED_AT   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_usuario (USUARIOS_ID),
                INDEX idx_cap (CAP),
                INDEX idx_fecha (CREATED_AT)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $uid  = (int)($_SESSION['usuario_id'] ?? 0);
        if ($uid <= 0) return;
        $como = ((int)($_SESSION['usuario_perfil'] ?? 0) === 1 && !empty($_SESSION['admin_as_dueno']))
              ? (int)$_SESSION['admin_as_dueno'] : null;
        $capE = mysqli_real_escape_string($link, $cap);
        $detE = mysqli_real_escape_string($link, mb_substr($detalle, 0, 255));
        $ipE  = mysqli_real_escape_string($link, $_SERVER['REMOTE_ADDR'] ?? '');
        $comoSql = $como === null ? 'NULL' : (string)$como;
        @mysqli_query($link,
            "INSERT INTO auditoria (USUARIOS_ID, ACTUA_COMO, CAP, DETALLE, IP)
             VALUES ($uid, $comoSql, '$capE', '$detE', '$ipE')");
    } catch (\Throwable $e) { /* best-effort */ }
}

<?php
/**
 * tenancy.php — Control de acceso multi-tenant de LaCanchita.
 *
 * Modelo SaaS:
 *   1 SuperAdmin (perfil 1)  -> ve TODO (la plataforma)
 *   2 Dueño     (perfil 2)   -> ve solo SUS predios y su staff
 *   3 Encargado (perfil 3)   -> ve solo las canchas/predios donde está asignado
 *   4 Empleado  (perfil 4)   -> idem encargado
 *   5 Cliente   (perfil 5)   -> sin acceso al panel admin
 *
 * Cadena de pertenencia:
 *   dueño(usuarios) -> complejo.USUARIOS_ID -> cancha.COMPLEJO_ID -> franja/reserva
 *   staff(usuarios.DUENO_ID) -> pertenece a un dueño
 *
 * Incluí este archivo DESPUÉS de conn.php en cada API del panel admin.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Liberar el lock de sesión inmediatamente para permitir requests concurrentes
session_write_close();

function current_perfil() { return (int)($_SESSION['usuario_perfil'] ?? 0); }
function current_uid()    { return (int)($_SESSION['usuario_id'] ?? 0); }
function is_superadmin()  { return current_perfil() === 1; }
function is_dueno()       { return current_perfil() === 2; }
function is_staff()       { return in_array(current_perfil(), [3, 4], true); }

/**
 * Devuelve respuesta JSON de error y corta la ejecución.
 */
function tenancy_deny($msg = 'Sin permisos.', $code = 403) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'msg' => $msg]);
    exit;
}

/**
 * Exige que el usuario tenga perfil <= $maxPerfil (1 = más privilegio).
 * Ej: require_perfil(2) permite SuperAdmin y Dueño; bloquea staff y clientes.
 */
function require_perfil($maxPerfil) {
    $p = current_perfil();
    if ($p === 0)            tenancy_deny('Sin sesión.', 401);
    if ($p > $maxPerfil)     tenancy_deny('No tenés permisos para esta acción.');
}

/**
 * En modo soporte, el SuperAdmin opera como si fuera este dueño.
 * Retorna el uid del dueño impersonado, o null si no hay contexto activo.
 */
function admin_as_dueno_id(): ?int {
    if (!is_superadmin()) return null;
    $id = $_SESSION['admin_as_dueno'] ?? null;
    return $id ? (int)$id : null;
}

/**
 * IDs de complejos accesibles por el usuario actual.
 * Retorna:
 *   null  -> SuperAdmin sin contexto: TODOS (sin filtro)
 *   []    -> sin acceso a ninguno
 *   [..]  -> lista de COMPLEJO_ID
 */
function tenant_complejo_ids($link) {
    $perfil = current_perfil();
    $uid    = current_uid();

    // SuperAdmin en modo soporte: actúa como el dueño elegido
    if ($perfil === 1) {
        $as = admin_as_dueno_id();
        if ($as === null) return null;              // sin contexto: todo
        $q = mysqli_query($link, "SELECT COMPLEJO_ID FROM complejo WHERE USUARIOS_ID = $as");
        $ids = [];
        if ($q) { while ($r = mysqli_fetch_assoc($q)) $ids[] = (int)$r['COMPLEJO_ID']; }
        return $ids;
    }

    $ids = [];
    if ($perfil === 2) {
        $q = mysqli_query($link, "SELECT COMPLEJO_ID FROM complejo WHERE USUARIOS_ID = $uid");
    } elseif ($perfil === 3 || $perfil === 4) {
        $q = mysqli_query($link,
            "SELECT DISTINCT ca.COMPLEJO_ID
             FROM cancha_encargado ce
             JOIN cancha ca ON ca.CANCHA_ID = ce.CANCHA_ID
             WHERE ce.USUARIOS_ID = $uid AND ce.ACTIVO = 1");
    } else {
        return [];                                  // clientes / desconocidos
    }
    if ($q) { while ($r = mysqli_fetch_assoc($q)) $ids[] = (int)$r['COMPLEJO_ID']; }
    return $ids;
}

/**
 * Fragmento SQL para filtrar por tenant en un WHERE.
 *   tenant_where($ids, 'c.COMPLEJO_ID')  ->  "c.COMPLEJO_ID IN (1,2)" | "1=1" | "1=0"
 */
function tenant_where($ids, $col) {
    if ($ids === null) return '1=1';
    if (empty($ids))   return '1=0';
    return "$col IN (" . implode(',', array_map('intval', $ids)) . ")";
}

/**
 * ¿Puede el usuario actual operar sobre este complejo?
 */
function can_complejo($link, $cid) {
    $ids = tenant_complejo_ids($link);
    if ($ids === null) return true;                 // SuperAdmin
    return in_array((int)$cid, $ids, true);
}

/**
 * ¿Puede el usuario actual operar sobre esta cancha?
 */
function can_cancha($link, $canchaId) {
    $canchaId = (int)$canchaId;
    $r = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COMPLEJO_ID FROM cancha WHERE CANCHA_ID = $canchaId"));
    if (!$r) return false;
    return can_complejo($link, (int)$r['COMPLEJO_ID']);
}

/**
 * ¿Puede el usuario actual operar sobre esta franja horaria?
 */
function can_franja($link, $franjaId) {
    $franjaId = (int)$franjaId;
    $r = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT CANCHA_ID FROM franja_horaria WHERE FRANJA_ID = $franjaId"));
    if (!$r) return false;
    return can_cancha($link, (int)$r['CANCHA_ID']);
}

/**
 * Guard de autorización: corta si no puede acceder al complejo.
 */
function assert_complejo($link, $cid) {
    if (!can_complejo($link, $cid)) tenancy_deny('Este predio no te pertenece.');
}
function assert_cancha($link, $canchaId) {
    if (!can_cancha($link, $canchaId)) tenancy_deny('Esta cancha no te pertenece.');
}
function assert_franja($link, $franjaId) {
    if (!can_franja($link, $franjaId)) tenancy_deny('Esta franja no te pertenece.');
}

/**
 * Dueño "efectivo" del usuario actual:
 *   - Dueño (perfil 2): su propio uid
 *   - Staff (perfil 3/4): el DUENO_ID al que pertenece
 *   - SuperAdmin: null (no tiene un único dueño)
 */
function current_dueno_id($link) {
    $perfil = current_perfil();
    $uid    = current_uid();
    if ($perfil === 2) return $uid;
    if ($perfil === 3 || $perfil === 4) {
        $r = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT DUENO_ID FROM usuarios WHERE USUARIOS_ID = $uid"));
        return $r ? (int)$r['DUENO_ID'] : null;
    }
    return null;
}

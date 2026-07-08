<?php
/**
 * Protección CSRF (double-submit: token en sesión + header X-CSRF-Token).
 *
 * El token se emite al renderizar cualquier página (pwa_head.php) y viaja en
 * cada POST vía un wrapper global de fetch (también en pwa_head.php) y como
 * campo oculto en los formularios clásicos. El servidor valida contra la sesión.
 *
 * Requiere una sesión activa al emitir el token. La validación tolera que la
 * sesión ya esté cerrada (lee $_SESSION en memoria).
 */

/** Devuelve el token de la sesión, creándolo si no existe. Requiere sesión abierta. */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Token recibido en el request (header, POST field o body JSON). */
function csrf_request_token(): string {
    $h = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($h !== '') return $h;
    if (isset($_POST['csrf'])) return (string)$_POST['csrf'];
    // Body JSON (para endpoints que reciben application/json)
    $ctype = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ctype, 'application/json') !== false) {
        static $jsonBody = null;
        if ($jsonBody === null) {
            $raw = file_get_contents('php://input');
            $jsonBody = $raw ? (json_decode($raw, true) ?: []) : [];
            // Cachear para que el endpoint pueda re-leer php://input igual;
            // como php://input no se puede releer, exponemos el body parseado.
            $GLOBALS['__CSRF_JSON_BODY'] = $jsonBody;
        }
        if (isset($jsonBody['csrf'])) return (string)$jsonBody['csrf'];
    }
    return '';
}

/** ¿El token del request coincide con el de la sesión? */
function csrf_valid(): bool {
    $sess = $_SESSION['csrf'] ?? '';
    $req  = csrf_request_token();
    return $sess !== '' && $req !== '' && hash_equals($sess, $req);
}

/** ¿El método actual muta estado? (todo lo que no sea lectura) */
function csrf_is_write_method(): bool {
    $m = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    return !in_array($m, ['GET', 'HEAD', 'OPTIONS'], true);
}

/**
 * Guard para APIs JSON: en métodos de escritura exige token válido.
 * Corta con 403 JSON si falla.
 */
function csrf_require($jsonResponse = true): void {
    if (!csrf_is_write_method()) return;
    if (csrf_valid()) return;
    http_response_code(403);
    if ($jsonResponse) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'msg' => 'Token de seguridad inválido. Recargá la página e intentá de nuevo.']);
    } else {
        echo 'Token de seguridad inválido. Recargá la página.';
    }
    exit;
}

/** Campo oculto para formularios HTML clásicos. Requiere sesión abierta. */
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

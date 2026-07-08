<?php
/**
 * Rate limiting simple por IP + bucket, respaldado en la tabla rate_limit.
 * Uso:
 *   require_once '.../ratelimit.php';
 *   if (!rate_limit_ok($link, 'login', 15, 300)) { ...429... }
 *
 * @param string $bucket  nombre lógico de la acción (login, registro, reset…)
 * @param int    $max     cantidad máxima de intentos por ventana
 * @param int    $window  ventana en segundos
 * @return bool  true si está permitido; false si superó el límite
 */
function rate_limit_ok($link, string $bucket, int $max, int $window): bool {
    @mysqli_query($link,
        "CREATE TABLE IF NOT EXISTS rate_limit (
            RL_KEY   VARCHAR(191) PRIMARY KEY,
            RL_COUNT INT UNSIGNED NOT NULL DEFAULT 0,
            RL_RESET DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = mysqli_real_escape_string($link, substr($bucket . '|' . $ip, 0, 191));
    $w   = max(1, $window);

    // Upsert atómico: si la ventana venció, reiniciar; si no, incrementar.
    mysqli_query($link,
        "INSERT INTO rate_limit (RL_KEY, RL_COUNT, RL_RESET)
         VALUES ('$key', 1, DATE_ADD(NOW(), INTERVAL $w SECOND))
         ON DUPLICATE KEY UPDATE
           RL_COUNT = IF(RL_RESET < NOW(), 1, RL_COUNT + 1),
           RL_RESET = IF(RL_RESET < NOW(), DATE_ADD(NOW(), INTERVAL $w SECOND), RL_RESET)"
    );

    $r = mysqli_fetch_assoc(mysqli_query($link, "SELECT RL_COUNT FROM rate_limit WHERE RL_KEY='$key'"));
    return ((int)($r['RL_COUNT'] ?? 0)) <= $max;
}

/** Corta con 429 JSON si se superó el límite. */
function rate_limit_guard_json($link, string $bucket, int $max, int $window): void {
    if (rate_limit_ok($link, $bucket, $max, $window)) return;
    http_response_code(429);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'msg' => 'Demasiados intentos. Esperá unos minutos e intentá de nuevo.']);
    exit;
}

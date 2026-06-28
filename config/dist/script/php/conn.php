<?php
ini_set('display_errors', '0');
error_reporting(0);

/*
 * Credenciales de la base de datos.
 * Prioridad: variables de entorno → config/db.php (gitignoreado) → defaults de dev.
 * Nunca hardcodear credenciales reales en este archivo (queda versionado).
 * Para producción: definir DB_HOST/DB_USER/DB_PASSWORD/DB_NAME en el entorno,
 * o copiar config/db.example.php a config/db.php con los valores reales.
 */
$__dbCfg  = [];
$__dbFile = __DIR__ . '/../../../db.php';   // → config/db.php (gitignoreado)
if (is_file($__dbFile)) {
    $__loaded = require $__dbFile;
    if (is_array($__loaded)) $__dbCfg = $__loaded;
}

$__envPass = getenv('DB_PASSWORD');

$host     = getenv('DB_HOST') ?: ($__dbCfg['host']     ?? 'localhost');
$user     = getenv('DB_USER') ?: ($__dbCfg['user']     ?? 'root');
$password = ($__envPass !== false) ? $__envPass : ($__dbCfg['password'] ?? '');
$database = getenv('DB_NAME') ?: ($__dbCfg['database'] ?? 'lacanchita');

$link = mysqli_connect($host, $user, $password, $database);

if (!$link) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']));
}

mysqli_set_charset($link, 'utf8mb4');

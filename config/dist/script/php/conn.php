<?php
ini_set('display_errors', '0');
error_reporting(0);

// Zona horaria del negocio: Argentina (UTC-3, sin horario de verano).
// Se fija explícita para que date()/time()/strtotime() no dependan de la config
// del server (XAMPP local viene en Europe/Berlin; los hostings suelen estar en UTC),
// evitando que las validaciones de "fecha/hora pasada" queden corridas.
date_default_timezone_set('America/Argentina/Buenos_Aires');

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

// Que NOW()/CURDATE() de MySQL coincidan con PHP en hora de Argentina (-03:00).
// Offset numérico (no nombre de zona) para no depender de las tablas de tz de MySQL,
// que en hostings compartidos casi nunca están cargadas.
mysqli_query($link, "SET time_zone = '-03:00'");

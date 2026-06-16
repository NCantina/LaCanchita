<?php
ini_set('display_errors', '0');
error_reporting(0);

$host = "localhost";
$user = "root";
$password = "";
$database = "lacanchita";

$link = mysqli_connect($host, $user, $password, $database);

if (!$link) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']));
}

mysqli_set_charset($link, 'utf8mb4');
?>

<?php

$host = "localhost"; // Cambia esto según tu configuración
$user = "u500921002_lacanchita"; // Usuario de la base de datos
$password = "Rom32Mb,,"; // Contraseña de la base de datos
$database = "u500921002_lacanchita"; // Cambia esto al nombre de tu base de datos

$link = mysqli_connect($host, $user, $password, $database);

if (!$link) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']));
}
?>

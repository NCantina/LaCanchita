<?php

$host = "localhost"; // Cambia esto según tu configuración
$user = "u500921002_lacanchita"; // Usuario de la base de datos
$password = "Rom32Mb,,"; // Contraseña de la base de datos
$database = "u500921002_lacanchita"; // Cambia esto al nombre de tu base de datos

// Crear conexión
// $conn = new mysqli($host, $user, $password, $database);
$link = mysqli_connect($host, $user, $password,$database);

// Verificar conexión
// if ($conn->connect_error) {
//     die("Error de conexión: " . $conn->connect_error);
// }
?>

<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'strong_shoes');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Crear la base de datos si no existe
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    $conn->select_db(DB_NAME);
} else {
    die("Error creando la base de datos: " . $conn->error);
}

// Crear la tabla de pedidos si no existe
$sql = "CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE,
    tique VARCHAR(50),
    cliente VARCHAR(100),
    ref VARCHAR(50),
    color VARCHAR(50),
    observaciones TEXT,
    tipo_calzado ENUM('caballero', 'dama', 'niño', 'juvenil'),
    marquilla VARCHAR(100),
    suela VARCHAR(100),
    ciudad VARCHAR(100),
    cantidad INT,
    tallas JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die("Error creando la tabla: " . $conn->error);
}
?> 
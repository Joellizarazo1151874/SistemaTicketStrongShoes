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

// Crear tablas para facturación si no existen
// Tabla principal de facturas
$sql = "CREATE TABLE IF NOT EXISTS facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero INT UNIQUE,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    cliente VARCHAR(150),
    nit VARCHAR(50),
    direccion VARCHAR(200),
    ciudad VARCHAR(100),
    telefono VARCHAR(50),
    observaciones TEXT,
    total DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die("Error creando la tabla facturas: " . $conn->error);
}

// Asegurar columnas nuevas en facturas (migraciones ligeras)
$col = $conn->query("SHOW COLUMNS FROM facturas LIKE 'nit'");
if ($col && $col->num_rows === 0) {
    if (!$conn->query("ALTER TABLE facturas ADD COLUMN nit VARCHAR(50) AFTER cliente")) {
        die("Error agregando columna nit: " . $conn->error);
    }
}

$col = $conn->query("SHOW COLUMNS FROM facturas LIKE 'direccion'");
if ($col && $col->num_rows === 0) {
    if (!$conn->query("ALTER TABLE facturas ADD COLUMN direccion VARCHAR(200) AFTER nit")) {
        die("Error agregando columna direccion: " . $conn->error);
    }
}

// Tabla de ítems de factura
$sql = "CREATE TABLE IF NOT EXISTS factura_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    ref VARCHAR(100),
    color VARCHAR(100),
    cantidad INT DEFAULT 0,
    valor_unitario DECIMAL(12,2) DEFAULT 0,
    subtotal DECIMAL(12,2) DEFAULT 0,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE
)";

if (!$conn->query($sql)) {
    die("Error creando la tabla factura_items: " . $conn->error);
}

// Generar un número consecutivo inicial si no existe
$result = $conn->query("SELECT MAX(numero) AS max_num FROM facturas");
if ($result) {
    $row = $result->fetch_assoc();
    if (!$row || $row['max_num'] === null) {
        // Insertar una factura ficticia para iniciar la numeración en 1000 y eliminarla
        $conn->query("INSERT INTO facturas (numero, cliente, total) VALUES (1000, 'INICIAL', 0)");
        $conn->query("DELETE FROM facturas WHERE cliente='INICIAL' AND total=0 ORDER BY id DESC LIMIT 1");
    }
}
?> 
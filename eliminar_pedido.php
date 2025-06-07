<?php
session_start();
require_once 'config/database.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $stmt = $conn->prepare("DELETE FROM pedidos WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: ver_pedidos.php?success=2");
    } else {
        header("Location: ver_pedidos.php?error=1");
    }
    exit;
} else {
    header("Location: ver_pedidos.php");
    exit;
}
?> 
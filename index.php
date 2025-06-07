<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strong Shoes - Sistema de Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #00D242;
            --hover-green: #00bf3b;
            --light-green: rgba(0, 210, 66, 0.1);
        }

        body {
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: var(--primary-green) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            color: white !important;
            font-weight: 600;
        }

        .navbar-brand img {
            height: 45px;
            margin-right: 15px;
            filter: brightness(1.1);
        }

        .navbar-brand:hover {
            color: rgba(255,255,255,0.9) !important;
        }

        .nav-link {
            color: white !important;
            margin: 0 10px;
            padding: 8px 15px !important;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }

        .navbar-toggler {
            border-color: white !important;
            background-color: rgba(255,255,255,0.1);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
        }

        .card {
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }

        .card-body {
            padding: 2rem;
        }

        .btn-primary {
            background-color: var(--primary-green) !important;
            border-color: var(--primary-green) !important;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--hover-green) !important;
            border-color: var(--hover-green) !important;
            transform: translateY(-2px);
        }

        .text-primary {
            color: var(--primary-green) !important;
        }

        .fa-4x {
            margin-bottom: 1.5rem;
            color: var(--primary-green);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .card-text {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="includes/logo.png" alt="Strong Shoes Logo"> Strong Shoes
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="nuevo_pedido.php">
                            <i class="fas fa-plus-circle"></i> Ingresar Pedido
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ver_pedidos.php">
                            <i class="fas fa-list"></i> Ver Pedidos
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <i class="fas fa-plus-circle fa-4x"></i>
                        <h5 class="card-title">Ingresar Nuevo Pedido</h5>
                        <p class="card-text">Crear un nuevo ticket de pedido para calzado</p>
                        <a href="nuevo_pedido.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Crear Pedido
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <i class="fas fa-list fa-4x"></i>
                        <h5 class="card-title">Ver Pedidos</h5>
                        <p class="card-text">Visualizar y gestionar los pedidos existentes</p>
                        <a href="ver_pedidos.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Ver Pedidos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
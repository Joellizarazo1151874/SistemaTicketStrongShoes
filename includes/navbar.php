<nav class="navbar navbar-expand-lg" style="background-color: #00D242;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php" style="color: white; font-weight: 600;">
            <img src="includes/logo.png" alt="Strong Shoes Logo" style="height: 45px; margin-right: 15px; filter: brightness(1.1);"> Strong Shoes
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="border-color: white;">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="nuevo_pedido.php" style="color: white; margin: 0 10px; padding: 8px 15px; transition: all 0.3s ease; border-radius: 5px;">
                        <i class="fas fa-plus-circle"></i> Ingresar Pedido
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="ver_pedidos.php" style="color: white; margin: 0 10px; padding: 8px 15px; transition: all 0.3s ease; border-radius: 5px;">
                        <i class="fas fa-list"></i> Ver Pedidos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="estadisticas.php" style="color: white; margin: 0 10px; padding: 8px 15px; transition: all 0.3s ease; border-radius: 5px;">
                        <i class="fas fa-chart-bar"></i> Estadísticas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="facturas.php" style="color: white; margin: 0 10px; padding: 8px 15px; transition: all 0.3s ease; border-radius: 5px;">
                        <i class="fas fa-file-invoice"></i> Remisiones
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
.navbar {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.navbar-brand:hover {
    color: rgba(255,255,255,0.9) !important;
}

.nav-link:hover {
    background-color: rgba(255,255,255,0.1) !important;
    color: white !important;
    transform: translateY(-1px);
}

.navbar-toggler {
    background-color: rgba(255,255,255,0.1);
}

.navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}
</style> 
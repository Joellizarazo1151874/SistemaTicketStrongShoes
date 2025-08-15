<?php
session_start();
require_once 'config/database.php';

// Filtros básicos (cliente, número, fecha)
$where = "1=1";
$params = [];
$types = "";

if (isset($_GET['search'])) {
    if (!empty($_GET['cliente'])) {
        $where .= " AND cliente LIKE ?";
        $params[] = "%" . $_GET['cliente'] . "%";
        $types .= "s";
    }
    if (!empty($_GET['numero'])) {
        $where .= " AND numero = ?";
        $params[] = (int) $_GET['numero'];
        $types .= "i";
    }
    if (!empty($_GET['fecha_inicio'])) {
        $where .= " AND DATE(fecha) >= ?";
        $params[] = $_GET['fecha_inicio'];
        $types .= "s";
    }
    if (!empty($_GET['fecha_fin'])) {
        $where .= " AND DATE(fecha) <= ?";
        $params[] = $_GET['fecha_fin'];
        $types .= "s";
    }
} else {
    // Por defecto, mostrar el día actual
    $where .= " AND DATE(fecha) = CURDATE()";
}

$sql = "SELECT * FROM facturas WHERE $where ORDER BY fecha DESC, id DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$facturas = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remisiones - Strong Shoes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #00D242;
            --hover-green: #00bf3b;
            --light-green: rgba(0, 210, 66, 0.1);
            --border-color: #e0e0e0;
        }

        body { background-color: #f8f9fa; }

        .card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; margin-bottom: 2rem; }
        .card-header { background-color: var(--primary-green) !important; color: white !important; border: none; padding: 1.25rem; }
        .card-body { padding: 2rem; }

        .form-label { font-weight: 600; color: #2c3e50; margin-bottom: 0.5rem; }
        .form-control, .form-select { border: 1px solid #e0e0e0; border-radius: 8px; padding: 0.75rem; transition: all 0.3s ease; }
        .form-control:focus, .form-select:focus { border-color: var(--primary-green); box-shadow: 0 0 0 0.2rem var(--light-green); }

        .btn-primary { background-color: var(--primary-green) !important; border-color: var(--primary-green) !important; padding: 10px 20px; font-weight: 500; transition: all 0.3s ease; border-radius: 8px; }
        .btn-primary:hover { background-color: var(--hover-green) !important; border-color: var(--hover-green) !important; transform: translateY(-2px); }
        .btn-outline-secondary { border-radius: 8px; }
        .btn-outline-primary { color: var(--primary-green) !important; border-color: var(--primary-green) !important; background-color: transparent; padding: 10px 20px; font-weight: 500; transition: all 0.3s ease; border-radius: 8px; }
        .btn-outline-primary:hover { color: #fff !important; background-color: var(--primary-green) !important; transform: translateY(-2px); }
        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1rem; font-weight: 500; border-radius: 8px; transition: all 0.3s ease; }
        .btn-sm { padding: 0.5rem 0.75rem; font-size: 0.875rem; }
        .btn i { font-size: 1rem; }

        .table { border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.05); margin-bottom: 0; }
        .table thead th { background-color: var(--primary-green); color: white; font-weight: 600; border: none; padding: 1rem; white-space: nowrap; }
        .table tbody td { padding: 1.2rem 1.5rem; vertical-align: middle; border-color: var(--border-color); color: #2c3e50; }
        .table tbody tr:hover { background-color: var(--light-green); }

        .table-header { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 1px solid var(--border-color); }
        .table-title { display: flex; align-items: center; gap: 0.75rem; margin: 0; color: #2c3e50; }
        .table-actions { display: flex; gap: 1rem; }
        .action-buttons { display: flex; gap: 0.5rem; justify-content: flex-end; }

        .search-form { background-color: white; padding: 2rem; border-radius: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .empty-state { text-align: center; padding: 3rem; color: #6c757d; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; color: var(--primary-green); }

        /* Toggle de filtros (igual que ver_pedidos.php) */
        .filter-toggle {
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            text-decoration: none;
            color: white !important;
        }
        .filter-toggle:hover { color: white !important; }
        .filter-toggle h4 { color: white !important; }
        .filter-toggle i { color: white !important; }
        .filter-toggle i.toggle-icon { transition: transform 0.3s ease; }
        .filter-toggle.collapsed i.toggle-icon { transform: rotate(-90deg); }
        .filter-body { transition: all 0.3s ease; }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-4">
    <div class="card mb-4">
        <div class="card-header">
            <a class="filter-toggle <?php echo !isset($_GET['search']) ? 'collapsed' : ''; ?>"
               data-bs-toggle="collapse"
               href="#filterCollapseFacturas"
               role="button"
               aria-expanded="<?php echo isset($_GET['search']) ? 'true' : 'false'; ?>"
               aria-controls="filterCollapseFacturas">
                <i class="fas fa-search"></i>
                <h4 class="mb-0">Filtrar Remisiones</h4>
                <i class="fas fa-chevron-down toggle-icon ms-auto"></i>
            </a>
        </div>
        <div class="collapse <?php echo isset($_GET['search']) ? 'show' : ''; ?>" id="filterCollapseFacturas">
            <div class="card-body">
            <form method="GET" class="search-form">
                <input type="hidden" name="search" value="1">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Cliente</label>
                        <input type="text" name="cliente" class="form-control" value="<?php echo isset($_GET['cliente']) ? htmlspecialchars($_GET['cliente']) : ''; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Número</label>
                        <input type="number" name="numero" class="form-control" value="<?php echo isset($_GET['numero']) ? (int)$_GET['numero'] : ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?php echo isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?php echo isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : ''; ?>">
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
                    <a href="facturas.php" class="btn btn-outline-secondary ms-2"><i class="fas fa-undo"></i> Limpiar</a>
                </div>
            </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-header">
            <h4 class="table-title"><i class="fas fa-file-invoice"></i> Lista de Remisiones</h4>
            <div class="table-actions">
                <a href="nueva_factura.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Remision</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Ciudad</th>
                            <th>Total</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($facturas)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-box-open"></i>
                                    <h5 class="mt-2">No se encontraron Remisiones</h5>
                                    <p class="mb-0">Ajusta los filtros o crea una nueva Remision.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($facturas as $f): ?>
                        <tr>
                            <td>#<?php echo (int)$f['numero']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($f['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars($f['cliente']); ?></td>
                            <td><?php echo htmlspecialchars($f['ciudad']); ?></td>
                            <td>$<?php echo number_format((float)$f['total'], 0, ',', '.'); ?></td>
                            <td class="text-end">
                                <div class="action-buttons">
                                    <a href="editar_factura.php?id=<?php echo $f['id']; ?>" class="btn btn-outline-primary btn-sm" title="Editar"><i class="fas fa-edit"></i></a>
                                    <a href="imprimir_factura.php?id=<?php echo $f['id']; ?>" target="_blank" class="btn btn-primary btn-sm" title="Imprimir"><i class="fas fa-print"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>




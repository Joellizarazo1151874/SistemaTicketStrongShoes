<?php
session_start();
require_once 'config/database.php';

// Función para obtener el rango de fechas
function getRangoFechas() {
    $rango = ['inicio' => date('Y-m-d', strtotime('-30 days')), 'fin' => date('Y-m-d')];
    
    if (isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio'])) {
        $rango['inicio'] = $_GET['fecha_inicio'];
    }
    if (isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])) {
        $rango['fin'] = $_GET['fecha_fin'];
    }
    
    return $rango;
}

$rango = getRangoFechas();

// Estadísticas generales
$stats = [];

// 1. Total de pedidos y pares en el período
$sql = "SELECT 
            COUNT(*) as total_pedidos,
            SUM(CAST(cantidad AS UNSIGNED)) as total_pares
        FROM pedidos 
        WHERE fecha BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $rango['inicio'], $rango['fin']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stats['general'] = $result;

// 2. Pedidos por tipo de calzado
$sql = "SELECT 
            tipo_calzado,
            COUNT(*) as cantidad_pedidos,
            SUM(CAST(cantidad AS UNSIGNED)) as total_pares
        FROM pedidos 
        WHERE fecha BETWEEN ? AND ?
        GROUP BY tipo_calzado
        ORDER BY cantidad_pedidos DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $rango['inicio'], $rango['fin']);
$stmt->execute();
$stats['por_tipo'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Top 5 clientes
$sql = "SELECT 
            cliente,
            COUNT(*) as cantidad_pedidos,
            SUM(CAST(cantidad AS UNSIGNED)) as total_pares
        FROM pedidos 
        WHERE fecha BETWEEN ? AND ?
        GROUP BY cliente
        ORDER BY cantidad_pedidos DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $rango['inicio'], $rango['fin']);
$stmt->execute();
$stats['top_clientes'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. Top 5 referencias más pedidas
$sql = "SELECT 
            ref,
            COUNT(*) as cantidad_pedidos,
            SUM(CAST(cantidad AS UNSIGNED)) as total_pares
        FROM pedidos 
        WHERE fecha BETWEEN ? AND ?
        GROUP BY ref
        ORDER BY cantidad_pedidos DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $rango['inicio'], $rango['fin']);
$stmt->execute();
$stats['top_referencias'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 5. Pedidos por ciudad
$sql = "SELECT 
            ciudad,
            COUNT(*) as cantidad_pedidos,
            SUM(CAST(cantidad AS UNSIGNED)) as total_pares
        FROM pedidos 
        WHERE fecha BETWEEN ? AND ?
        GROUP BY ciudad
        ORDER BY cantidad_pedidos DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $rango['inicio'], $rango['fin']);
$stmt->execute();
$stats['por_ciudad'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 6. Promedio diario de pedidos
$sql = "SELECT 
            DATE(fecha) as fecha,
            COUNT(*) as pedidos_dia,
            SUM(CAST(cantidad AS UNSIGNED)) as pares_dia
        FROM pedidos 
        WHERE fecha BETWEEN ? AND ?
        GROUP BY DATE(fecha)
        ORDER BY fecha DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $rango['inicio'], $rango['fin']);
$stmt->execute();
$stats['por_dia'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calcular promedios
$total_dias = count($stats['por_dia']);
if ($total_dias > 0) {
    $stats['promedios'] = [
        'pedidos_por_dia' => $stats['general']['total_pedidos'] / $total_dias,
        'pares_por_dia' => $stats['general']['total_pares'] / $total_dias
    ];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Strong Shoes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-green: #00D242;
            --hover-green: #00bf3b;
            --light-green: rgba(0, 210, 66, 0.1);
        }

        .stats-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            font-size: 2rem;
            color: var(--primary-green);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .stats-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
        }

        .table th {
            background-color: var(--light-green);
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <!-- Filtro de fechas -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" name="fecha_inicio" value="<?php echo $rango['inicio']; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" name="fecha_fin" value="<?php echo $rango['fin']; ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>
                            Filtrar
                        </button>
                        <a href="estadisticas.php" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-2"></i>
                            Resetear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resumen General -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon me-3">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div>
                                <div class="stats-number"><?php echo number_format($stats['general']['total_pedidos']); ?></div>
                                <div class="stats-label">Total Pedidos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon me-3">
                                <i class="fas fa-shoe-prints"></i>
                            </div>
                            <div>
                                <div class="stats-number"><?php echo number_format($stats['general']['total_pares']); ?></div>
                                <div class="stats-label">Total Pares</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Promedios -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon me-3">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div>
                                <div class="stats-number"><?php echo number_format($stats['promedios']['pedidos_por_dia'], 1); ?></div>
                                <div class="stats-label">Promedio Pedidos por Día</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon me-3">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div>
                                <div class="stats-number"><?php echo number_format($stats['promedios']['pares_por_dia'], 1); ?></div>
                                <div class="stats-label">Promedio Pares por Día</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Gráfico de Pedidos por Tipo -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Pedidos por Tipo de Calzado</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="tipoCalzadoChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Pedidos por Ciudad -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Pedidos por Ciudad</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="ciudadChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Top 5 Clientes -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top 5 Clientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Pedidos</th>
                                        <th>Pares</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['top_clientes'] as $cliente): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cliente['cliente']); ?></td>
                                        <td><?php echo number_format($cliente['cantidad_pedidos']); ?></td>
                                        <td><?php echo number_format($cliente['total_pares']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top 5 Referencias -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top 5 Referencias</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Referencia</th>
                                        <th>Pedidos</th>
                                        <th>Pares</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['top_referencias'] as $ref): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ref['ref']); ?></td>
                                        <td><?php echo number_format($ref['cantidad_pedidos']); ?></td>
                                        <td><?php echo number_format($ref['total_pares']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Tendencia Diaria -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Tendencia Diaria</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="tendenciaChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Datos para los gráficos
        const tipoCalzadoData = {
            labels: <?php echo json_encode(array_column($stats['por_tipo'], 'tipo_calzado')); ?>,
            datasets: [{
                label: 'Cantidad de Pedidos',
                data: <?php echo json_encode(array_column($stats['por_tipo'], 'cantidad_pedidos')); ?>,
                backgroundColor: ['#00D242', '#00A896', '#02C39A', '#028090', '#05668D'],
                borderWidth: 1
            }]
        };

        const ciudadData = {
            labels: <?php echo json_encode(array_column($stats['por_ciudad'], 'ciudad')); ?>,
            datasets: [{
                label: 'Cantidad de Pedidos',
                data: <?php echo json_encode(array_column($stats['por_ciudad'], 'cantidad_pedidos')); ?>,
                backgroundColor: ['#00D242', '#00A896', '#02C39A', '#028090', '#05668D'],
                borderWidth: 1
            }]
        };

        const tendenciaData = {
            labels: <?php echo json_encode(array_map(function($fecha) { 
                return date('d/m', strtotime($fecha['fecha'])); 
            }, $stats['por_dia'])); ?>,
            datasets: [{
                label: 'Pedidos',
                data: <?php echo json_encode(array_column($stats['por_dia'], 'pedidos_dia')); ?>,
                borderColor: '#00D242',
                tension: 0.4,
                fill: false
            }, {
                label: 'Pares',
                data: <?php echo json_encode(array_column($stats['por_dia'], 'pares_dia')); ?>,
                borderColor: '#05668D',
                tension: 0.4,
                fill: false
            }]
        };

        // Configuración común
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        };

        // Crear gráficos
        new Chart(document.getElementById('tipoCalzadoChart'), {
            type: 'pie',
            data: tipoCalzadoData,
            options: commonOptions
        });

        new Chart(document.getElementById('ciudadChart'), {
            type: 'pie',
            data: ciudadData,
            options: commonOptions
        });

        new Chart(document.getElementById('tendenciaChart'), {
            type: 'line',
            data: tendenciaData,
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html> 
<?php
session_start();
require_once 'config/database.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;

// Procesar la búsqueda
$where = "1=1";
$params = [];
$types = "";

// Si no hay búsqueda activa, mostrar solo los pedidos del día actual
if (!isset($_GET['search'])) {
    $where .= " AND DATE(fecha) = CURDATE()";
} else {
    if (!empty($_GET['cliente'])) {
        $where .= " AND cliente LIKE ?";
        $params[] = "%" . $_GET['cliente'] . "%";
        $types .= "s";
    }
    if (!empty($_GET['ref'])) {
        $where .= " AND ref LIKE ?";
        $params[] = "%" . $_GET['ref'] . "%";
        $types .= "s";
    }
    if (!empty($_GET['tique'])) {
        $where .= " AND tique LIKE ?";
        $params[] = "%" . $_GET['tique'] . "%";
        $types .= "s";
    }
    if (!empty($_GET['fecha_inicio'])) {
        $where .= " AND fecha >= ?";
        $params[] = $_GET['fecha_inicio'];
        $types .= "s";
    }
    if (!empty($_GET['fecha_fin'])) {
        $where .= " AND fecha <= ?";
        $params[] = $_GET['fecha_fin'];
        $types .= "s";
    }
}

// Preparar y ejecutar la consulta
$sql = "SELECT * FROM pedidos WHERE $where ORDER BY fecha DESC, id DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$pedidos = $result->fetch_all(MYSQLI_ASSOC);

// Agrupar pedidos por fecha
$pedidos_por_fecha = [];
foreach ($pedidos as $pedido) {
    $fecha = date('Y-m-d', strtotime($pedido['fecha']));
    if (!isset($pedidos_por_fecha[$fecha])) {
        $pedidos_por_fecha[$fecha] = [];
    }
    $pedidos_por_fecha[$fecha][] = $pedido;
}

// Generar PDF
if (isset($_GET['pdf']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();

    if ($pedido) {
        $dompdf = new Dompdf([
            'defaultFont' => 'Helvetica'
        ]);
        
        // Contenido del PDF
        $html = '
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body { 
                font-family: Helvetica, Arial, sans-serif;
                font-size: 9pt;
                line-height: 1.2;
                padding: 2mm;
            }
            .header { 
                text-align: center;
                padding: 1mm 0;
                margin-bottom: 1mm;
            }
            .header h2 {
                margin: 0;
                padding: 0;
                font-size: 11pt;
            }
            .header div {
                font-size: 9pt;
            }
            .ticket-info {
                margin-bottom: 1mm;
                font-size: 8pt;
                text-align: left;
            }
            .ticket-info table {
                width: 100%;
                border-collapse: collapse;
                margin: 0 auto;
            }
            .ticket-info td {
                border: 1px solid #000;
                padding: 0.5mm 1mm;
                text-align: left;
            }
            .obs-cell {
                min-height: 37.5mm;
                height: 37.5mm;
                vertical-align: top;
            }
            .ticket-info .label {
                background: #f0f0f0;
                font-weight: bold;
                width: 25%;
                text-align: left;
            }
            .ticket-info .value {
                width: 25%;
                text-align: left;
            }
            .tallas-section {
                margin-bottom: 1mm;
            }
            .tallas-title {
                font-weight: bold;
                text-transform: uppercase;
                font-size: 9pt;
                background: #f0f0f0;
                padding: 1mm;
                margin-bottom: 0.5mm;
            }
            .elaborado-por {
                font-weight: normal;
                color: #666;
            }
            .role-info {
                border: 1px solid #000;
                padding: 1mm;
                margin-bottom: 0.5mm;
                font-size: 7pt;
                line-height: 1.1;
            }
            .tallas-table {
                width: 100%;
                border-collapse: collapse;
            }
            .tallas-table th, .tallas-table td {
                border: 1px solid #000;
                padding: 0.5mm;
                text-align: center;
                font-size: 8pt;
            }
            .tallas-table th {
                background: #f0f0f0;
            }
            .obs-row td {
                border-top: 2px solid #000;
            }
        </style>
        <div class="header">
            <h2>STRONG SHOES</h2>
            <div>TICKET DE PEDIDO</div>
        </div>
        <div class="ticket-info">
            <table>
                <tr>
                    <td><strong>Fecha:</strong> ' . $pedido['fecha'] . '</td>
                    <td><strong>Ciudad:</strong> ' . $pedido['ciudad'] . '</td>
                </tr>
                <tr>
                    <td><strong>Ref:</strong> ' . $pedido['ref'] . '</td>
                    <td><strong>Color:</strong> ' . $pedido['color'] . '</td>
                </tr>
                <tr>
                    <td><strong>De:</strong> ' . ucfirst($pedido['tipo_calzado']) . '</td>
                    <td><strong>Marquilla:</strong> ' . $pedido['marquilla'] . '</td>
                </tr>
                <tr>
                    <td><strong>Tique:</strong> ' . $pedido['tique'] . '</td>
                    <td><strong>Cliente:</strong> ' . $pedido['cliente'] . '</td>
                </tr>
                <tr>
                    <td colspan="2" class="obs-cell"><strong>Obs:</strong> ' . $pedido['observaciones'] . '<br><br><br></td>
                </tr>
            </table>
        </div>';

        // Obtener el rango de tallas según el tipo de calzado
        $rangos = [
            'caballero' => ['min' => 37, 'max' => 45],
            'dama' => ['min' => 33, 'max' => 41],
            'niño' => ['min' => 20, 'max' => 26],
            'juvenil' => ['min' => 27, 'max' => 36]
        ];

        $rango = $rangos[$pedido['tipo_calzado']];
        $tallas = json_decode($pedido['tallas'], true);

        // Array de roles en el orden específico con su información
        $roles = [
            'LIMPIADA' => [
                'info' => [
                    'DE:' . strtoupper($pedido['tipo_calzado']),
                    'CANT:' . $pedido['cantidad'] . ' PARES',
                    'REF:' . $pedido['ref'],
                    'TIQ:' . $pedido['tique']
                ]
            ],
            'SOLETERO' => [
                'info' => [
                    'DE:' . strtoupper($pedido['tipo_calzado']),
                    'CANT:' . $pedido['cantidad'] . ' PARES',
                    'REF:' . $pedido['ref'],
                    'TIQ:' . $pedido['tique'],
                    'SUELA:' . $pedido['suela']
                ]
            ],
            'MONTADOR' => [
                'info' => [
                    'DE:' . strtoupper($pedido['tipo_calzado']),
                    'CANT:' . $pedido['cantidad'] . ' PARES',
                    'REF:' . $pedido['ref'],
                    'TIQ:' . $pedido['tique'],
                    'SUELA:' . $pedido['suela']
                ]
            ],
            'COSTURERO' => [
                'info' => [
                    'DE:' . strtoupper($pedido['tipo_calzado']),
                    'CANT:' . $pedido['cantidad'] . ' PARES',
                    'REF:' . $pedido['ref'],
                    'TIQ:' . $pedido['tique']
                ]
            ],
            'CORTADOR' => [
                'info' => [
                    'DE:' . strtoupper($pedido['tipo_calzado']),
                    'CANT:' . $pedido['cantidad'] . ' PARES',
                    'REF:' . $pedido['ref'],
                    'TIQ:' . $pedido['tique']
                ]
            ]
        ];

        foreach ($roles as $rol => $datos) {
            $html .= '
            <div class="tallas-section">
                <div class="tallas-title">' . $rol . ' <span class="elaborado-por">ELABORADO POR: _____________________</span></div>
                <div class="role-info">' . implode(' | ', $datos['info']) . '</div>
                <table class="tallas-table">
                    <tr>';
            
            // Encabezados de tallas
            for ($i = $rango['min']; $i <= $rango['max']; $i++) {
                $html .= '<th>' . $i . '</th>';
            }
            $html .= '</tr><tr>';
            
            // Valores de tallas (usando los valores reales del pedido)
            for ($i = $rango['min']; $i <= $rango['max']; $i++) {
                $valor = isset($tallas[$i]) ? $tallas[$i] : '0';
                $html .= '<td>' . $valor . '</td>';
            }
            
            $html .= '</tr></table></div>';
        }

        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 283.46, 708.66], 'portrait');
        $dompdf->render();
        $dompdf->stream("ticket-" . $pedido['id'] . ".pdf", array("Attachment" => false));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Pedidos - Strong Shoes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #00D242;
            --hover-green: #00bf3b;
            --light-green: rgba(0, 210, 66, 0.1);
            --border-color: #e0e0e0;
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

        .card {
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header {
            background-color: var(--primary-green) !important;
            color: white !important;
            border: none;
            padding: 1.25rem;
        }

        .card-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem var(--light-green);
        }

        .btn-primary {
            background-color: var(--primary-green) !important;
            border-color: var(--primary-green) !important;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background-color: var(--hover-green) !important;
            border-color: var(--hover-green) !important;
            transform: translateY(-2px);
        }

        .btn-outline-primary {
            color: var(--primary-green) !important;
            border-color: var(--primary-green) !important;
            background-color: transparent;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 8px;
        }

        .btn-outline-primary:hover {
            color: white !important;
            background-color: var(--primary-green) !important;
            transform: translateY(-2px);
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        .table thead th {
            background-color: var(--primary-green);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #f0f0f0;
        }

        .table tbody tr:hover {
            background-color: var(--light-green);
        }

        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .search-form {
            background-color: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .action-buttons .btn {
            margin: 0 0.25rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .success-message {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background-color: #e8f5e9;
            color: #2e7d32;
            border: none;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .success-message i {
            font-size: 1.25rem;
        }

        .search-form {
            background-color: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            background-color: #f8f9fa;
            border: 2px solid var(--border-color);
            padding: 0.8rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background-color: white;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem var(--light-green);
        }

        .table-container {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: var(--primary-green);
            color: white;
            font-weight: 600;
            padding: 1rem 1.5rem;
            border: none;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 1.2rem 1.5rem;
            vertical-align: middle;
            border-color: var(--border-color);
            color: #2c3e50;
        }

        .table tbody tr:hover {
            background-color: var(--light-green);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }

        .btn i {
            font-size: 1rem;
        }

        .btn-outline-danger {
            color: #dc3545;
            border-color: #dc3545;
        }

        .btn-outline-danger:hover {
            color: white;
            background-color: #dc3545;
            border-color: #dc3545;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-green);
        }

        .empty-state h4 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header i {
            font-size: 1.25rem;
        }

        .date-range {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .search-button {
            display: flex;
            justify-content: flex-end;
            padding-top: 1rem;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .table-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0;
            color: #2c3e50;
        }

        .table-actions {
            display: flex;
            gap: 1rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 50rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-active {
            background-color: var(--light-green);
            color: var(--primary-green);
        }

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

        .filter-toggle:hover {
            color: white !important;
        }

        .filter-toggle h4 {
            color: white !important;
        }

        .filter-toggle i {
            color: white !important;
        }

        .filter-toggle i.toggle-icon {
            transition: transform 0.3s ease;
        }

        .filter-toggle.collapsed i.toggle-icon {
            transform: rotate(-90deg);
        }

        .filter-body {
            transition: all 0.3s ease;
        }

        .active-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
            padding: 0.5rem 0;
        }

        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: var(--light-green);
            color: var(--primary-green);
            border-radius: 50rem;
            font-size: 0.875rem;
        }

        .filter-badge i {
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .filter-badge i:hover {
            transform: scale(1.1);
        }

        .card-header {
            border-bottom: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <span>El pedido se ha procesado exitosamente.</span>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <a class="filter-toggle <?php echo !isset($_GET['search']) ? 'collapsed' : ''; ?>" 
                   data-bs-toggle="collapse" 
                   href="#filterCollapse" 
                   role="button" 
                   aria-expanded="<?php echo isset($_GET['search']) ? 'true' : 'false'; ?>" 
                   aria-controls="filterCollapse">
                    <i class="fas fa-search"></i>
                    <h4 class="mb-0">Filtrar Pedidos</h4>
                    <i class="fas fa-chevron-down toggle-icon ms-auto"></i>
                </a>
            </div>
            
            <div class="collapse <?php echo isset($_GET['search']) ? 'show' : ''; ?>" id="filterCollapse">
                <div class="card-body">
                    <form method="GET" class="search-form">
                        <input type="hidden" name="search" value="1">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Cliente</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" name="cliente" 
                                           value="<?php echo isset($_GET['cliente']) ? htmlspecialchars($_GET['cliente']) : ''; ?>"
                                           placeholder="Buscar por nombre del cliente...">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Referencia</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-tag"></i>
                                    </span>
                                    <input type="text" class="form-control" name="ref" 
                                           value="<?php echo isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : ''; ?>"
                                           placeholder="Buscar por referencia...">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tique</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-ticket-alt"></i>
                                    </span>
                                    <input type="text" class="form-control" name="tique" 
                                           value="<?php echo isset($_GET['tique']) ? htmlspecialchars($_GET['tique']) : ''; ?>"
                                           placeholder="Buscar por número de tique...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha Inicio</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar"></i>
                                    </span>
                                    <input type="date" class="form-control" name="fecha_inicio" 
                                           value="<?php echo isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha Fin</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar"></i>
                                    </span>
                                    <input type="date" class="form-control" name="fecha_fin" 
                                           value="<?php echo isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="search-button">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Buscar Pedidos
                            </button>
                            <a href="ver_pedidos.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-undo"></i>
                                Limpiar Filtros
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (isset($_GET['search']) && 
                     (isset($_GET['cliente']) || isset($_GET['ref']) || 
                      isset($_GET['tique']) || isset($_GET['fecha_inicio']) || 
                      isset($_GET['fecha_fin']))): ?>
            <div class="card-footer">
                <div class="active-filters">
                    <?php if (!empty($_GET['cliente'])): ?>
                        <span class="filter-badge">
                            <i class="fas fa-user"></i>
                            Cliente: <?php echo htmlspecialchars($_GET['cliente']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($_GET['ref'])): ?>
                        <span class="filter-badge">
                            <i class="fas fa-tag"></i>
                            Ref: <?php echo htmlspecialchars($_GET['ref']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($_GET['tique'])): ?>
                        <span class="filter-badge">
                            <i class="fas fa-ticket-alt"></i>
                            Tique: <?php echo htmlspecialchars($_GET['tique']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($_GET['fecha_inicio'])): ?>
                        <span class="filter-badge">
                            <i class="fas fa-calendar"></i>
                            Desde: <?php echo date('d/m/Y', strtotime($_GET['fecha_inicio'])); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($_GET['fecha_fin'])): ?>
                        <span class="filter-badge">
                            <i class="fas fa-calendar"></i>
                            Hasta: <?php echo date('d/m/Y', strtotime($_GET['fecha_fin'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="table-header">
                <h4 class="table-title">
                    <i class="fas fa-list"></i>
                    Lista de Pedidos
                </h4>
                <div class="table-actions">
                    <a href="nuevo_pedido.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Nuevo Pedido
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <?php if (empty($pedidos)): ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <h4>No se encontraron pedidos</h4>
                            <p>Intenta ajustar los filtros de búsqueda o crea un nuevo pedido.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pedidos_por_fecha as $fecha => $pedidos_dia): ?>
                            <div class="fecha-grupo mb-4">
                                <div class="fecha-header bg-light p-3 mb-2 d-flex justify-content-between align-items-center">
                                    <h5 class="m-0">
                                        <i class="fas fa-calendar-day me-2"></i>
                                        <?php echo date('d/m/Y', strtotime($fecha)); ?>
                                        <span class="badge bg-primary ms-2"><?php echo count($pedidos_dia); ?> pedidos</span>
                                    </h5>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input select-day" data-date="<?php echo $fecha; ?>" id="selectDay_<?php echo $fecha; ?>">
                                        <label class="form-check-label" for="selectDay_<?php echo $fecha; ?>">Seleccionar todos del día</label>
                                    </div>
                                </div>
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">
                                                <?php if ($fecha === array_key_first($pedidos_por_fecha)): ?>
                                                <input type="checkbox" class="form-check-input" id="selectAll" title="Seleccionar todos los pedidos">
                                                <?php endif; ?>
                                            </th>
                                            <th>ID</th>
                                            <th>Hora</th>
                                            <th>Tique</th>
                                            <th>Cliente</th>
                                            <th>Referencia</th>
                                            <th>Tipo</th>
                                            <th>Cantidad</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pedidos_dia as $pedido): ?>
                                        <tr>
                                            <td><input type="checkbox" class="form-check-input pedido-checkbox" data-date="<?php echo $fecha; ?>" value="<?php echo $pedido['id']; ?>"></td>
                                            <td>
                                                <span class="fw-bold">#<?php echo $pedido['id']; ?></span>
                                            </td>
                                            <td>
                                                <i class="fas fa-clock me-2 text-muted"></i>
                                                <?php echo date('H:i', strtotime($pedido['fecha'])); ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-ticket-alt me-2 text-muted"></i>
                                                <?php echo htmlspecialchars($pedido['tique']); ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-user me-2 text-muted"></i>
                                                <?php echo htmlspecialchars($pedido['cliente']); ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-tag me-2 text-muted"></i>
                                                <?php echo htmlspecialchars($pedido['ref']); ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-active">
                                                    <?php echo ucfirst($pedido['tipo_calzado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <i class="fas fa-box me-2 text-muted"></i>
                                                <?php echo $pedido['cantidad']; ?> pares
                                            </td>
                                            <td class="action-buttons">
                                                <a href="?pdf=1&id=<?php echo $pedido['id']; ?>" 
                                                   class="btn btn-primary btn-sm" 
                                                   title="Imprimir Ticket"
                                                   target="_blank">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                                <a href="editar_pedido.php?id=<?php echo $pedido['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm"
                                                   title="Editar Pedido">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="eliminar_pedido.php?id=<?php echo $pedido['id']; ?>" 
                                                   class="btn btn-outline-danger btn-sm"
                                                   title="Eliminar Pedido"
                                                   onclick="return confirm('¿Está seguro de eliminar este pedido?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-4">
        <div class="d-flex justify-content-end">
            <button id="imprimirSeleccionados" class="btn btn-primary">
                <i class="fas fa-print me-2"></i> 
                Imprimir Tickets Seleccionados
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Seleccionar/deseleccionar todos
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.pedido-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            // También actualizar los checkboxes de días
            document.querySelectorAll('.select-day').forEach(dayCheckbox => {
                dayCheckbox.checked = this.checked;
            });
        });

        // Seleccionar/deseleccionar por día
        document.querySelectorAll('.select-day').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const fecha = this.dataset.date;
                const checkboxes = document.querySelectorAll(`.pedido-checkbox[data-date="${fecha}"]`);
                checkboxes.forEach(cb => cb.checked = this.checked);
                
                // Verificar si todos los días están seleccionados
                const todosLosDias = document.querySelectorAll('.select-day');
                const todosSeleccionados = Array.from(todosLosDias).every(cb => cb.checked);
                document.getElementById('selectAll').checked = todosSeleccionados;
            });
        });

        // Actualizar estado de checkboxes padre cuando se cambian los individuales
        document.querySelectorAll('.pedido-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const fecha = this.dataset.date;
                const checkboxesDia = document.querySelectorAll(`.pedido-checkbox[data-date="${fecha}"]`);
                const todosSeleccionadosDia = Array.from(checkboxesDia).every(cb => cb.checked);
                const selectDayCheckbox = document.querySelector(`.select-day[data-date="${fecha}"]`);
                if (selectDayCheckbox) {
                    selectDayCheckbox.checked = todosSeleccionadosDia;
                }

                // Verificar si todos los pedidos están seleccionados
                const todosPedidos = document.querySelectorAll('.pedido-checkbox');
                const todosSeleccionados = Array.from(todosPedidos).every(cb => cb.checked);
                document.getElementById('selectAll').checked = todosSeleccionados;
            });
        });

        // Imprimir seleccionados
        document.getElementById('imprimirSeleccionados').addEventListener('click', function() {
            const seleccionados = Array.from(document.querySelectorAll('.pedido-checkbox:checked')).map(cb => cb.value);
            if (seleccionados.length === 0) {
                alert('Selecciona al menos un pedido para imprimir.');
                return;
            }
            // Redirigir a imprimir_tickets.php con los IDs seleccionados
            window.open('imprimir_tickets.php?ids=' + seleccionados.join(','), '_blank');
        });
    </script>
</body>
</html>
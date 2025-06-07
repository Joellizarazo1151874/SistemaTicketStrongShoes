<?php
session_start();
require_once 'config/database.php';

// Obtener el último número de tique
$result = $conn->query("SELECT MAX(CAST(tique AS UNSIGNED)) as max_tique FROM pedidos");
$row = $result->fetch_assoc();
$next_tique = $row['max_tique'] ? $row['max_tique'] + 1 : 1;

// Obtener el último ID para autoincremento
$result = $conn->query("SELECT MAX(id) as max_id FROM pedidos");
$row = $result->fetch_assoc();
$next_id = $row['max_id'] ? $row['max_id'] + 1 : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $fecha = $_POST['fecha'];
    $tique = $next_tique; // Usar el siguiente número de tique
    $cliente = $_POST['cliente'];
    $ref = $_POST['ref'];
    $color = $_POST['color'];
    $observaciones = $_POST['observaciones'];
    $tipo_calzado = $_POST['tipo_calzado'];
    $marquilla = $_POST['marquilla'];
    $suela = $_POST['suela'];
    $ciudad = $_POST['ciudad'];
    $cantidad = $_POST['cantidad'];
    $tallas = json_encode($_POST['tallas']);

    $sql = "INSERT INTO pedidos (id, fecha, tique, cliente, ref, color, observaciones, tipo_calzado, marquilla, suela, ciudad, cantidad, tallas) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssssssss", $id, $fecha, $tique, $cliente, $ref, $color, $observaciones, $tipo_calzado, $marquilla, $suela, $ciudad, $cantidad, $tallas);
    
    if ($stmt->execute()) {
        header("Location: ver_pedidos.php?success=1");
        exit;
    } else {
        $error = "Error al guardar el pedido: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Pedido - Strong Shoes</title>
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

        .tallas-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .talla-input {
            width: 100%;
            text-align: center;
            font-weight: 500;
        }

        .btn-primary {
            background-color: var(--primary-green) !important;
            border-color: var(--primary-green) !important;
            padding: 12px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background-color: var(--hover-green) !important;
            border-color: var(--hover-green) !important;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        textarea.form-control {
            min-height: 120px;
        }

        /* Nuevos estilos para mejorar el formulario */
        .form-section {
            background-color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .form-section-title {
            color: var(--primary-green);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control, .form-select {
            background-color: #f8f9fa;
            border: 2px solid #e0e0e0;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            background-color: white;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem var(--light-green);
        }

        .form-label {
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .required-field::after {
            content: '*';
            color: #dc3545;
            margin-left: 4px;
        }

        .tallas-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            background-color: white;
            padding: 1.5rem;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
        }

        .talla-group {
            text-align: center;
        }

        .talla-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .talla-input {
            width: 100%;
            text-align: center;
            padding: 0.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .talla-input:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem var(--light-green);
        }

        .form-footer {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0 0 10px 10px;
            text-align: right;
            border-top: 1px solid #e0e0e0;
            margin-top: 2rem;
        }

        .btn-primary {
            padding: 0.8rem 2rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .input-group-text {
            background-color: var(--primary-green);
            color: white;
            border: none;
            padding: 0.8rem 1rem;
        }

        .readonly-field {
            background-color: #e9ecef !important;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-plus-circle me-2"></i>
                    Nuevo Pedido
                </h4>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="pedidoForm">
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-info-circle"></i>
                            Información Básica
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required-field">Fecha</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar"></i>
                                    </span>
                                    <input type="date" class="form-control" name="fecha" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required-field">Tique</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-ticket-alt"></i>
                                    </span>
                                    <input type="text" class="form-control" name="tique" required readonly value="<?php echo $next_tique; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required-field">Cliente</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" name="cliente" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required-field">Ciudad</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </span>
                                    <input type="text" class="form-control" name="ciudad" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-shoe-prints"></i>
                            Detalles del Calzado
                        </div>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label required-field">Referencia</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-tag"></i>
                                    </span>
                                    <input type="text" class="form-control" name="ref" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required-field">Color</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-palette"></i>
                                    </span>
                                    <input type="text" class="form-control" name="color" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required-field">Tipo de Calzado</label>
                                <select class="form-select" name="tipo_calzado" id="tipoCalzado" required>
                                    <option value="">Seleccione...</option>
                                    <option value="caballero">Caballero</option>
                                    <option value="dama">Dama</option>
                                    <option value="niño">Niño</option>
                                    <option value="juvenil">Juvenil</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required-field">Marquilla</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-tag"></i>
                                    </span>
                                    <input type="text" class="form-control" name="marquilla" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required-field">Cantidad Total</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-hashtag"></i>
                                    </span>
                                    <input type="number" class="form-control" name="cantidad" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label required-field">Suela</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-shoe-prints"></i>
                                    </span>
                                    <input type="text" class="form-control" name="suela" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-ruler"></i>
                            Tallas
                        </div>
                        <div id="tallasContainer" class="tallas-container">
                            <!-- Las tallas se generarán dinámicamente con JavaScript -->
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-comment"></i>
                            Observaciones
                        </div>
                        <textarea class="form-control" name="observaciones" rows="4" placeholder="Ingrese cualquier observación adicional aquí..."></textarea>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Guardar Pedido
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const tipoCalzado = document.getElementById('tipoCalzado');
        const tallasContainer = document.getElementById('tallasContainer');

        const rangos = {
            'caballero': {min: 37, max: 45},
            'dama': {min: 33, max: 41},
            'niño': {min: 20, max: 26},
            'juvenil': {min: 27, max: 36}
        };

        tipoCalzado.addEventListener('change', function() {
            tallasContainer.innerHTML = '';
            const tipo = this.value;
            
            if (tipo && rangos[tipo]) {
                const {min, max} = rangos[tipo];
                for (let i = min; i <= max; i++) {
                    const div = document.createElement('div');
                    div.className = 'talla-group';
                    div.innerHTML = `
                        <label class="talla-label">Talla ${i}</label>
                        <input type="number" class="talla-input" name="tallas[${i}]" min="0" value="0">
                    `;
                    tallasContainer.appendChild(div);
                }
            }
        });
    </script>
</body>
</html> 
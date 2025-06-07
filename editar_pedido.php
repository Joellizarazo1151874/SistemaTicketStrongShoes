<?php
session_start();
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: ver_pedidos.php");
    exit;
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$pedido = $result->fetch_assoc();

if (!$pedido) {
    header("Location: ver_pedidos.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'];
    $tique = $_POST['tique'];
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

    $sql = "UPDATE pedidos SET fecha=?, tique=?, cliente=?, ref=?, color=?, observaciones=?, tipo_calzado=?, 
            marquilla=?, suela=?, ciudad=?, cantidad=?, tallas=? WHERE id=?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssi", $fecha, $tique, $cliente, $ref, $color, $observaciones, $tipo_calzado, 
                      $marquilla, $suela, $ciudad, $cantidad, $tallas, $id);
    
    if ($stmt->execute()) {
        header("Location: ver_pedidos.php?success=3");
        exit;
    } else {
        $error = "Error al actualizar el pedido: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pedido - Strong Shoes</title>
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

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            padding: 12px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 8px;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        input[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-edit"></i> Editar Pedido #<?php echo $pedido['id']; ?></h4>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="pedidoForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">ID</label>
                                <input type="number" class="form-control" value="<?php echo $pedido['id']; ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Fecha</label>
                                <input type="date" class="form-control" name="fecha" value="<?php echo $pedido['fecha']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tique</label>
                                <input type="text" class="form-control" name="tique" value="<?php echo $pedido['tique']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Cliente</label>
                                <input type="text" class="form-control" name="cliente" value="<?php echo $pedido['cliente']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Referencia</label>
                                <input type="text" class="form-control" name="ref" value="<?php echo $pedido['ref']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Color</label>
                                <input type="text" class="form-control" name="color" value="<?php echo $pedido['color']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tipo de Calzado</label>
                                <select class="form-select" name="tipo_calzado" id="tipoCalzado" required>
                                    <option value="">Seleccione...</option>
                                    <option value="caballero" <?php echo $pedido['tipo_calzado'] === 'caballero' ? 'selected' : ''; ?>>Caballero</option>
                                    <option value="dama" <?php echo $pedido['tipo_calzado'] === 'dama' ? 'selected' : ''; ?>>Dama</option>
                                    <option value="niño" <?php echo $pedido['tipo_calzado'] === 'niño' ? 'selected' : ''; ?>>Niño</option>
                                    <option value="juvenil" <?php echo $pedido['tipo_calzado'] === 'juvenil' ? 'selected' : ''; ?>>Juvenil</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Cantidad</label>
                                <input type="number" class="form-control" name="cantidad" value="<?php echo $pedido['cantidad']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Marquilla</label>
                                <input type="text" class="form-control" name="marquilla" value="<?php echo $pedido['marquilla']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Suela</label>
                                <input type="text" class="form-control" name="suela" value="<?php echo $pedido['suela']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Ciudad</label>
                                <input type="text" class="form-control" name="ciudad" value="<?php echo $pedido['ciudad']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observaciones" rows="3"><?php echo $pedido['observaciones']; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tallas</label>
                        <div id="tallasContainer" class="tallas-container">
                            <!-- Las tallas se generarán dinámicamente con JavaScript -->
                        </div>
                    </div>

                    <div class="text-end">
                        <a href="ver_pedidos.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
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
        const tallasActuales = <?php echo $pedido['tallas']; ?>;

        const rangos = {
            'caballero': {min: 37, max: 45},
            'dama': {min: 33, max: 41},
            'niño': {min: 20, max: 26},
            'juvenil': {min: 27, max: 36}
        };

        function generarTallas(tipo) {
            tallasContainer.innerHTML = '';
            if (tipo && rangos[tipo]) {
                const {min, max} = rangos[tipo];
                for (let i = min; i <= max; i++) {
                    const div = document.createElement('div');
                    div.innerHTML = `
                        <label class="form-label">Talla ${i}</label>
                        <input type="number" class="form-control talla-input" name="tallas[${i}]" min="0" value="${tallasActuales[i] || 0}">
                    `;
                    tallasContainer.appendChild(div);
                }
            }
        }

        tipoCalzado.addEventListener('change', function() {
            generarTallas(this.value);
        });

        // Generar tallas al cargar la página
        generarTallas(tipoCalzado.value);
    </script>
</body>
</html> 
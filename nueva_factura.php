<?php
session_start();
require_once 'config/database.php';

// Obtener siguiente número de factura
$res = $conn->query("SELECT COALESCE(MAX(numero),999) + 1 AS next_num FROM facturas");
$row = $res->fetch_assoc();
$nextNumero = (int)$row['next_num'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = $_POST['cliente'] ?? '';
    $nit = $_POST['nit'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $ciudad = $_POST['ciudad'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    $numero = isset($_POST['numero']) ? (int)$_POST['numero'] : $nextNumero;

    $refs = $_POST['ref'] ?? [];
    $colors = $_POST['color'] ?? [];
    $cants = $_POST['cantidad'] ?? [];
    $vus = $_POST['valor_unitario'] ?? [];

    // Crear factura
    $stmt = $conn->prepare("INSERT INTO facturas (numero, cliente, nit, direccion, ciudad, telefono, observaciones, total) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param('issssss', $numero, $cliente, $nit, $direccion, $ciudad, $telefono, $observaciones);
    $stmt->execute();
    $facturaId = $stmt->insert_id;

    $total = 0;
    $itemStmt = $conn->prepare("INSERT INTO factura_items (factura_id, ref, color, cantidad, valor_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)");

    for ($i = 0; $i < count($refs); $i++) {
        $ref = trim($refs[$i] ?? '');
        $color = trim($colors[$i] ?? '');
        $cantidad = (int)($cants[$i] ?? 0);
        $vu = (float)($vus[$i] ?? 0);
        if ($ref === '' && $color === '' && $cantidad === 0 && $vu === 0) continue;
        $subtotal = $cantidad * $vu;
        $total += $subtotal;
        $itemStmt->bind_param('issidd', $facturaId, $ref, $color, $cantidad, $vu, $subtotal);
        $itemStmt->execute();
    }

    // Actualizar total
    $upd = $conn->prepare("UPDATE facturas SET total = ? WHERE id = ?");
    $upd->bind_param('di', $total, $facturaId);
    $upd->execute();

    header("Location: imprimir_factura.php?id=" . $facturaId);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Factura - Strong Shoes</title>
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

    .card-body { padding: 2rem; }

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
    .btn-outline-primary { border-radius: 8px; }
    .btn-outline-danger { border-radius: 8px; }

    .table { border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.05); margin-bottom: 0; }
    .table thead th { background-color: var(--primary-green); color: white; font-weight: 600; border: none; padding: 1rem; white-space: nowrap; }
    .table tbody td { padding: 1.2rem 1.5rem; vertical-align: middle; border-color: var(--border-color); color: #2c3e50; }
    .table tbody tr:hover { background-color: var(--light-green); }

    .subtotal, #total { font-weight: 600; }
    .card-footer { padding: 1.25rem; }

    /* Mantener vertical alignment de celdas */
    .table td, .table th { vertical-align: middle; }
    </style>
    <script>
    function addRow() {
        const tbody = document.getElementById('items-body');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input name="ref[]" class="form-control"/></td>
            <td><input name="color[]" class="form-control"/></td>
            <td><input name="cantidad[]" type="number" min="0" class="form-control" oninput="recalc(this)"/></td>
            <td><input name="valor_unitario[]" type="number" min="0" step="0.01" class="form-control" oninput="recalc(this)"/></td>
            <td class="text-end subtotal">$0</td>
            <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
        `;
        tbody.appendChild(row);
    }
    function removeRow(btn) {
        const row = btn.closest('tr');
        row.remove();
        recalcTotals();
    }
    function recalc(input) {
        const row = input.closest('tr');
        const qty = parseFloat(row.querySelector('input[name="cantidad[]"]').value || 0);
        const vu = parseFloat(row.querySelector('input[name="valor_unitario[]"]').value || 0);
        const sub = qty * vu;
        row.querySelector('.subtotal').textContent = formatMoney(sub);
        recalcTotals();
    }
    function recalcTotals() {
        let total = 0;
        document.querySelectorAll('#items-body tr').forEach(tr => {
            const qty = parseFloat(tr.querySelector('input[name="cantidad[]"]').value || 0);
            const vu = parseFloat(tr.querySelector('input[name="valor_unitario[]"]').value || 0);
            total += qty * vu;
        });
        document.getElementById('total').textContent = formatMoney(total);
    }
    function formatMoney(n){
        return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(n);
    }
    </script>
    
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container mt-4">
    <form method="POST">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Nueva Factura</h5>
                <div>
                    <label class="me-2">Número</label>
                    <input type="number" name="numero" class="form-control d-inline-block" style="width: 140px" value="<?php echo $nextNumero; ?>">
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Cliente</label>
                        <input type="text" name="cliente" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">NIT</label>
                        <input type="text" name="nit" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ciudad</label>
                        <input type="text" name="ciudad" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Items</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addRow()"><i class="fas fa-plus"></i> Agregar fila</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ref</th>
                                <th>Color</th>
                                <th style="width: 120px;">Cant</th>
                                <th style="width: 170px;">V. Unitario</th>
                                <th class="text-end" style="width: 160px;">Subtotal</th>
                                <th class="text-end" style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            <tr>
                                <td><input name="ref[]" class="form-control"/></td>
                                <td><input name="color[]" class="form-control"/></td>
                                <td><input name="cantidad[]" type="number" min="0" class="form-control" oninput="recalc(this)"/></td>
                                <td><input name="valor_unitario[]" type="number" min="0" step="0.01" class="form-control" oninput="recalc(this)"/></td>
                                <td class="text-end subtotal">$0</td>
                                <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">TOTAL</th>
                                <th class="text-end" id="total">$0</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary"><i class="fas fa-save"></i> Guardar e Imprimir</button>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



<?php
session_start();
require_once 'config/database.php';

// Obtener siguiente número de factura
$res = $conn->query("SELECT COALESCE(MAX(numero),999) + 1 AS next_num FROM facturas");
$row = $res->fetch_assoc();
$nextNumero = (int)$row['next_num'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = $_POST['cliente'] ?? '';
    $almacen = $_POST['almacen'] ?? '';
    $nit = $_POST['nit'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $ciudad = $_POST['ciudad'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    $numero = isset($_POST['numero']) ? (int)$_POST['numero'] : $nextNumero;
    $numCopias = isset($_POST['num_copias']) ? max(1, (int)$_POST['num_copias']) : 1;

    $refs = $_POST['ref'] ?? [];
    $colors = $_POST['color'] ?? [];
    $cants = $_POST['cantidad'] ?? [];
    $vus = $_POST['valor_unitario'] ?? [];

    // Crear una o varias copias de la factura
    $lastFacturaId = null;
    for ($copy = 0; $copy < $numCopias; $copy++) {
        // Calcular el siguiente número consecutivo en cada iteración
        $resNum = $conn->query("SELECT COALESCE(MAX(numero),999) + 1 AS next_num FROM facturas");
        $rowNum = $resNum->fetch_assoc();
        $numeroActual = (int)$rowNum['next_num'];

        $stmt = $conn->prepare("INSERT INTO facturas (numero, cliente, almacen, nit, direccion, ciudad, telefono, observaciones, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param('isssssss', $numeroActual, $cliente, $almacen, $nit, $direccion, $ciudad, $telefono, $observaciones);
        $stmt->execute();
        $facturaId = $stmt->insert_id;
        $lastFacturaId = $facturaId;

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

        // Actualizar total de la copia
        $upd = $conn->prepare("UPDATE facturas SET total = ? WHERE id = ?");
        $upd->bind_param('di', $total, $facturaId);
        $upd->execute();
    }

    header("Location: imprimir_factura.php?id=" . $lastFacturaId);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Remision - Strong Shoes</title>
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
    .items-header { display: flex; align-items: center; justify-content: space-between; }
    .items-header h5 { margin: 0; }
    .items-add-btn { border-radius: 999px; padding: 0.35rem 0.85rem; box-shadow: 0 2px 6px rgba(0,0,0,0.12); }

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
    .table thead th { background: linear-gradient(0deg, var(--primary-green), var(--primary-green)); color: white; font-weight: 700; border: none; padding: 0.9rem 1rem; white-space: nowrap; letter-spacing: .3px; }
    .table tbody td { padding: 0.9rem 1rem; vertical-align: middle; border-color: var(--border-color); color: #2c3e50; }
    .table tbody tr:hover { background-color: var(--light-green); }
    .table tfoot th, .table tfoot td { padding: 0.9rem 1rem; }

    .subtotal, #total { font-weight: 600; }
    .card-footer { padding: 1.25rem; }

    /* Mantener vertical alignment de celdas */
    .table td, .table th { vertical-align: middle; }
    .number-input { text-align: right; }
    .btn-icon { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; }
    .fade-in { animation: fadeIn .25s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
    </style>
    <script>
    function addRow() {
        const tbody = document.getElementById('items-body');
        const row = document.createElement('tr');
        row.className = 'fade-in';
        row.innerHTML = `
            <td><input name="ref[]" class="form-control" placeholder="Ref..."/></td>
            <td><input name="color[]" class="form-control" placeholder="Color..."/></td>
            <td><input name="cantidad[]" type="number" min="0" class="form-control number-input" placeholder="0" oninput="recalc(this)"/></td>
            <td><input name="valor_unitario[]" type="number" min="0" step="0.01" class="form-control number-input" placeholder="0" oninput="recalc(this)"/></td>
            <td class="text-end subtotal">$0</td>
            <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm btn-icon" title="Eliminar" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
        `;
        tbody.appendChild(row);
        const firstInput = row.querySelector('input[name="ref[]"]');
        if (firstInput) firstInput.focus();
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
                <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Nueva Remision</h5>
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
                        <label class="form-label">Almacén</label>
                        <input type="text" name="almacen" class="form-control" placeholder="Sucursal / Punto de venta">
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
            <div class="card-header items-header">
                <h5 class="mb-0">Items</h5>
                <button type="button" class="btn btn-outline-light btn-sm items-add-btn" onclick="addRow()"><i class="fas fa-plus me-1"></i> Agregar fila</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ref</th>
                                <th>Color</th>
                                <th class="text-end" style="width: 120px;">Cant</th>
                                <th class="text-end" style="width: 170px;">V. Unitario</th>
                                <th class="text-end" style="width: 160px;">Subtotal</th>
                                <th class="text-end" style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            <tr>
                                <td><input name="ref[]" class="form-control" placeholder="Ref..."/></td>
                                <td><input name="color[]" class="form-control" placeholder="Color..."/></td>
                                <td><input name="cantidad[]" type="number" min="0" class="form-control number-input" placeholder="0" oninput="recalc(this)"/></td>
                                <td><input name="valor_unitario[]" type="number" min="0" step="0.01" class="form-control number-input" placeholder="0" oninput="recalc(this)"/></td>
                                <td class="text-end subtotal">$0</td>
                                <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm btn-icon" title="Eliminar" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
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
            <div class="card-footer d-flex justify-content-end align-items-center gap-2 flex-wrap">
                <div class="d-flex align-items-center gap-2 me-auto">
                    <button type="button" class="btn btn-outline-secondary" id="btnCopiasFactura" title="Copias" style="border-radius: 8px; padding: 10px 16px;">
                        <i class="fas fa-copy me-2"></i> Copias
                    </button>
                    <input type="number" class="form-control" id="numCopiasFactura" name="num_copias" min="1" value="1"
                        style="width: 90px; display: none; border-radius: 8px;">
                </div>
                <button class="btn btn-primary"><i class="fas fa-save"></i> Guardar e Imprimir</button>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Copias para factura (igual patrón que pedido)
    (function(){
        const btn = document.getElementById('btnCopiasFactura');
        const input = document.getElementById('numCopiasFactura');
        if (!btn || !input) return;
        btn.addEventListener('click', function(){
            if (input.style.display === 'none') {
                input.style.display = 'block';
                btn.classList.add('active');
            } else {
                input.style.display = 'none';
                btn.classList.remove('active');
                input.value = 1;
            }
        });
    })();
</script>
</body>
</html>



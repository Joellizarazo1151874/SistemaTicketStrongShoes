<?php
session_start();
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    die('Falta id');
}
$id = (int) $_GET['id'];

// Cargar factura
$stmt = $conn->prepare("SELECT * FROM facturas WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$factura = $stmt->get_result()->fetch_assoc();
if (!$factura) die('Remision no encontrada');

// Cargar items
$itemsStmt = $conn->prepare("SELECT * FROM factura_items WHERE factura_id = ?");
$itemsStmt->bind_param('i', $id);
$itemsStmt->execute();
$items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = $_POST['cliente'] ?? '';
    $nit = $_POST['nit'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $ciudad = $_POST['ciudad'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';

    // Actualizar cabecera
    $almacen = $_POST['almacen'] ?? '';
    $upd = $conn->prepare("UPDATE facturas SET cliente=?, almacen=?, nit=?, direccion=?, ciudad=?, telefono=?, observaciones=? WHERE id=?");
    $upd->bind_param('sssssssi', $cliente, $almacen, $nit, $direccion, $ciudad, $telefono, $observaciones, $id);
    $upd->execute();

    // Reemplazar items
    $conn->query("DELETE FROM factura_items WHERE factura_id = " . (int)$id);

    $refs = $_POST['ref'] ?? [];
    $colors = $_POST['color'] ?? [];
    $cants = $_POST['cantidad'] ?? [];
    $vus = $_POST['valor_unitario'] ?? [];

    $total = 0;
    $ins = $conn->prepare("INSERT INTO factura_items (factura_id, ref, color, cantidad, valor_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    for ($i = 0; $i < count($refs); $i++) {
        $ref = trim($refs[$i] ?? '');
        $color = trim($colors[$i] ?? '');
        $cantidad = (int)($cants[$i] ?? 0);
        $vu = (float)($vus[$i] ?? 0);
        if ($ref === '' && $color === '' && $cantidad === 0 && $vu === 0) continue;
        $subtotal = $cantidad * $vu;
        $total += $subtotal;
        $ins->bind_param('issidd', $id, $ref, $color, $cantidad, $vu, $subtotal);
        $ins->execute();
    }

    // Actualizar total
    $updTotal = $conn->prepare("UPDATE facturas SET total=? WHERE id=?");
    $updTotal->bind_param('di', $total, $id);
    $updTotal->execute();

    header('Location: facturas.php?updated=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Remision - Strong Shoes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary-green: #00D242; --hover-green: #00bf3b; --light-green: rgba(0,210,66,.1); --border-color:#e0e0e0; }
        body { background-color: #f8f9fa; }
        .card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,.1); border-radius: 10px; overflow: hidden; margin-bottom: 2rem; }
        .card-header { background-color: var(--primary-green) !important; color: #fff !important; border: none; padding: 1.25rem; }
        .items-header { display: flex; align-items: center; justify-content: space-between; }
        .items-add-btn { border-radius: 999px; padding: 0.35rem 0.85rem; box-shadow: 0 2px 6px rgba(0,0,0,0.12); }
        .card-body { padding: 2rem; }
        .form-label { font-weight: 600; color: #2c3e50; margin-bottom: .5rem; }
        .form-control { border:1px solid #e0e0e0; border-radius:8px; padding:.75rem; }
        .form-control:focus { border-color: var(--primary-green); box-shadow: 0 0 0 .2rem var(--light-green); }
        .table { border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,.05); }
        .table thead th { background-color: var(--primary-green); color: #fff; border:none; padding:1rem; }
        .table tbody td { padding: .9rem 1rem; vertical-align: middle; }
        .btn { display:inline-flex; align-items:center; gap:.5rem; padding:.6rem 1rem; font-weight:500; border-radius:8px; transition: all .3s ease; }
        .btn-sm { padding: .5rem .75rem; font-size: .875rem; }
        .btn i { font-size: 1rem; }
        .btn-primary { background-color: var(--primary-green) !important; border-color: var(--primary-green) !important; border-radius: 8px; }
        .btn-primary:hover { background-color: var(--hover-green) !important; border-color: var(--hover-green) !important; transform: translateY(-2px); }
        .btn-outline-danger { border-radius: 8px; }
        .number-input { text-align: right; }
        .btn-icon { width: 34px; height: 34px; display: inline-flex; align-items:center; justify-content:center; border-radius: 50%; }
    </style>
    <script>
    function addRow() {
        const tbody = document.getElementById('items-body');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input name="ref[]" class="form-control"/></td>
            <td><input name="color[]" class="form-control"/></td>
            <td><input name="cantidad[]" type="number" min="0" class="form-control number-input" oninput="recalc(this)"/></td>
            <td><input name="valor_unitario[]" type="number" min="0" step="0.01" class="form-control number-input" oninput="recalc(this)"/></td>
            <td class="text-end subtotal">$0</td>
            <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm btn-icon" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
        `;
        tbody.appendChild(row);
    }
    function removeRow(btn) { btn.closest('tr').remove(); recalcTotals(); }
    function recalc(input){
        const row = input.closest('tr');
        const qty = parseFloat(row.querySelector('input[name="cantidad[]"]').value||0);
        const vu = parseFloat(row.querySelector('input[name="valor_unitario[]"]').value||0);
        row.querySelector('.subtotal').textContent = formatMoney(qty*vu);
        recalcTotals();
    }
    function recalcTotals(){
        let total = 0;
        document.querySelectorAll('#items-body tr').forEach(tr=>{
            const qty = parseFloat(tr.querySelector('input[name="cantidad[]"]').value||0);
            const vu = parseFloat(tr.querySelector('input[name="valor_unitario[]"]').value||0);
            total += qty*vu;
        });
        document.getElementById('total').textContent = formatMoney(total);
    }
    function formatMoney(n){ return new Intl.NumberFormat('es-CO',{style:'currency',currency:'COP',maximumFractionDigits:0}).format(n); }
    </script>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container mt-4">
    <form method="POST">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Remision #<?php echo (int)$factura['numero']; ?></h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Cliente</label>
                        <input type="text" name="cliente" class="form-control" value="<?php echo htmlspecialchars($factura['cliente']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Almacén</label>
                        <input type="text" name="almacen" class="form-control" value="<?php echo htmlspecialchars($factura['almacen']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">NIT</label>
                        <input type="text" name="nit" class="form-control" value="<?php echo htmlspecialchars($factura['nit']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control" value="<?php echo htmlspecialchars($factura['direccion']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ciudad</label>
                        <input type="text" name="ciudad" class="form-control" value="<?php echo htmlspecialchars($factura['ciudad']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($factura['telefono']); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2"><?php echo htmlspecialchars($factura['observaciones']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header items-header">
                <h6 class="mb-0">Items</h6>
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
                            <?php foreach ($items as $it): $sub = (float)$it['subtotal']; ?>
                            <tr>
                                <td><input name="ref[]" class="form-control" value="<?php echo htmlspecialchars($it['ref']); ?>"/></td>
                                <td><input name="color[]" class="form-control" value="<?php echo htmlspecialchars($it['color']); ?>"/></td>
                                <td><input name="cantidad[]" type="number" min="0" class="form-control number-input" oninput="recalc(this)" value="<?php echo (int)$it['cantidad']; ?>"/></td>
                                <td><input name="valor_unitario[]" type="number" min="0" step="0.01" class="form-control number-input" oninput="recalc(this)" value="<?php echo (float)$it['valor_unitario']; ?>"/></td>
                                <td class="text-end subtotal"><?php echo '$'.number_format($sub,0,',','.'); ?></td>
                                <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm btn-icon" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">TOTAL</th>
                                <th class="text-end" id="total"><?php echo '$'.number_format((float)$factura['total'],0,',','.'); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary"><i class="fas fa-save me-1"></i> Guardar Cambios</button>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



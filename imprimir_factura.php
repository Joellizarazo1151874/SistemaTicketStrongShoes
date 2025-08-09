<?php
require_once 'config/database.php';
require_once 'vendor/autoload.php';
require_once 'includes/company.php';

use Dompdf\Dompdf;

if (!isset($_GET['id'])) {
    die('Falta id');
}
$id = (int) $_GET['id'];

// Cargar factura
$stmt = $conn->prepare("SELECT * FROM facturas WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$factura = $stmt->get_result()->fetch_assoc();
if (!$factura) die('Factura no encontrada');

// Cargar items
$itemsStmt = $conn->prepare("SELECT * FROM factura_items WHERE factura_id = ?");
$itemsStmt->bind_param('i', $id);
$itemsStmt->execute();
$items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$dompdf = new Dompdf(['defaultFont' => 'Helvetica']);

// Estilos y estructura inspirados en la imagen del comprobante

$html = '<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10pt; padding: 12mm; color: #1f2937; }

    /* Header */
    .header { margin-bottom: 8mm; }
    .brand-line { display: flex; justify-content: space-between; align-items: baseline; gap: 6mm; }
    .brand { font-size: 26pt; font-weight: 800; color: #16a34a; letter-spacing: 0.3px; }
    .brand small { font-size: 13pt; font-weight: 700; margin-left: 6px; color: #16a34a; opacity: 0.9; }
    .inv-badge { background: #16a34a; color: #fff; padding: 3mm 5mm; border-radius: 6px; font-weight: 700; font-size: 11pt; }
    .owner { font-size: 10pt; margin-top: 2mm; color: #111827; }
    .info { font-size: 9pt; color: #4b5563; margin-top: 1mm; }

    /* Boxes */
    .box { border: 1px solid #cbd5e1; padding: 4mm; margin-top: 4mm; border-radius: 6px; background: #f8fafc; }
    .row { display: flex; gap: 4mm; }
    .row > div { flex: 1; }
    .label { font-weight: 700; color: #374151; }

    /* Items table */
    table { width: 100%; border-collapse: collapse; }
    thead th { background: #e8f5e9; color: #111827; border: 1px solid #94a3b8; padding: 3mm; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.3px; }
    tbody td { border: 1px solid #cbd5e1; padding: 3mm; font-size: 9pt; }
    tbody tr:nth-child(even) { background: #ffffff; }
    tbody tr:nth-child(odd) { background: #fdfdfd; }
    .right { text-align: right; }

    /* Totals */
    tfoot td { border: 1px solid #94a3b8; padding: 3mm; }
    .total-row td { font-weight: 800; background: #f1f5f9; }

    /* Observaciones */
    .obs { min-height: 22mm; }
  </style>';

$html .= '<div class="header">
    <div class="brand-line">
        <div class="brand">' . htmlspecialchars(COMPANY_NAME) . ' <small>' . htmlspecialchars(COMPANY_SUBTITLE) . '</small></div>
        <div class="inv-badge">Factura Nº ' . (int)$factura['numero'] . '</div>
    </div>
    <div class="owner">' . htmlspecialchars(COMPANY_OWNER) . ' · Nit: ' . htmlspecialchars(COMPANY_NIT) . '</div>
    <div class="info">' . htmlspecialchars(COMPANY_ADDRESS) . ' | ' . htmlspecialchars(COMPANY_PHONES) . '</div>
  </div>';

$html .= '<div class="box">
    <div class="row">
        <div><span class="label">Fecha:</span> ' . htmlspecialchars(date('d/m/Y H:i', strtotime($factura['fecha']))) . '</div>
        <div><span class="label">Cliente:</span> ' . htmlspecialchars($factura['cliente'] ?? '') . '</div>
        <div><span class="label">Tel:</span> ' . htmlspecialchars($factura['telefono'] ?? '') . '</div>
    </div>
    <div class="row" style="margin-top:2mm">
        <div><span class="label">Ciudad:</span> ' . htmlspecialchars($factura['ciudad'] ?? '') . '</div>
        <div><span class="label">NIT:</span> ' . htmlspecialchars($factura['nit'] ?? '') . '</div>
        <div><span class="label">Dirección:</span> ' . htmlspecialchars($factura['direccion'] ?? '') . '</div>
    </div>
</div>';

$html .= '<div class="box" style="margin-top:5mm; background:#fff;">
    <table>
        <thead>
            <tr>
                <th>REF</th>
                <th>COLOR</th>
                <th class="right">CANT</th>
                <th class="right">V. UNITARIO</th>
                <th class="right">TOTAL</th>
            </tr>
        </thead>
        <tbody>';

$total = 0;
foreach ($items as $it) {
    $subtotal = (float)$it['subtotal'];
    $total += $subtotal;
    $html .= '<tr>
        <td>' . htmlspecialchars($it['ref']) . '</td>
        <td>' . htmlspecialchars($it['color']) . '</td>
        <td class="right">' . (int)$it['cantidad'] . '</td>
        <td class="right">$' . number_format((float)$it['valor_unitario'], 0, ',', '.') . '</td>
        <td class="right">$' . number_format($subtotal, 0, ',', '.') . '</td>
    </tr>';
}

$html .= '</tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="right">TOTAL</td>
                <td class="right">$' . number_format($total, 0, ',', '.') . '</td>
            </tr>
        </tfoot>
    </table>
</div>';

$html .= '<div class="box obs" style="margin-top:5mm; background:#fff;"><span class="label">OBSERVACIONES:</span><br>' . nl2br(htmlspecialchars($factura['observaciones'] ?? '')) . '</div>';

$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();
$dompdf->stream('factura-' . (int)$factura['numero'] . '.pdf', ['Attachment' => false]);
exit;



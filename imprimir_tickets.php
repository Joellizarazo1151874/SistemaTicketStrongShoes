<?php
ob_start(); // Iniciar buffer de salida
require_once 'config/database.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;

if (!isset($_GET['ids']) || empty($_GET['ids'])) {
    die('No se recibieron IDs de pedidos.');
}

$ids = explode(',', $_GET['ids']);
$ids = array_filter(array_map('intval', $ids)); // Eliminar valores vacíos

if (empty($ids)) {
    die('No se recibieron IDs válidos.');
}

$html = '';

foreach ($ids as $id) {
    if ($id <= 0) continue; // Saltar IDs inválidos
    
    $stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    
    if (!$pedido) continue;

    // Validar el tipo de calzado
    if (!isset($pedido['tipo_calzado']) || empty($pedido['tipo_calzado'])) {
        continue;
    }

    // --- Copia la lógica de generación de HTML de ver_pedidos.php ---
    $html .= '<style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9pt; line-height: 1.2; padding: 0 2mm; }
        .header { text-align: center; padding: 1mm 0; margin-bottom: 1mm; }
        .header h2 { margin: 0; padding: 0; font-size: 11pt; }
        .header div { font-size: 9pt; }
        .ticket-info { margin-bottom: 1mm; font-size: 8pt; text-align: left; }
        .ticket-info table { width: 100%; border-collapse: collapse; margin: 0 auto; }
        .ticket-info td { border: 1px solid #000; padding: 0.5mm 1mm; text-align: left; }
        .obs-cell { min-height: 14mm; height: 14mm; vertical-align: top; }
        .ticket-info .label { background: #f0f0f0; font-weight: bold; width: 25%; text-align: left; }
        .ticket-info .value { width: 25%; text-align: left; }
        .tallas-section { margin-bottom: 1mm; }
        .tallas-title { font-weight: bold; text-transform: uppercase; font-size: 9pt; background: #f0f0f0; padding: 1mm; margin-bottom: 0.5mm; }
        .elaborado-por { font-weight: normal; color: #666; }
        .role-info { border: 1px solid #000; padding: 1mm; margin-bottom: 0.5mm; font-size: 7pt; line-height: 1.1; }
        .tallas-table { width: 100%; border-collapse: collapse; }
        .tallas-table th, .tallas-table td { border: 1px solid #000; padding: 0.5mm; text-align: center; font-size: 8pt; }
        .tallas-table th { background: #f0f0f0; }
        .obs-row td { border-top: 2px solid #000; }
    </style>';
    
    // Escapar valores HTML para prevenir XSS
    $fecha = htmlspecialchars($pedido['fecha'] ?? '');
    $ciudad = htmlspecialchars($pedido['ciudad'] ?? '');
    $ref = htmlspecialchars($pedido['ref'] ?? '');
    $color = htmlspecialchars($pedido['color'] ?? '');
    $tipo_calzado = htmlspecialchars($pedido['tipo_calzado']);
    $marquilla = htmlspecialchars($pedido['marquilla'] ?? '');
    $tique = htmlspecialchars($pedido['tique'] ?? '');
    $cliente = htmlspecialchars($pedido['cliente'] ?? '');
    $observaciones = htmlspecialchars($pedido['observaciones'] ?? '');
    $suela = htmlspecialchars($pedido['suela'] ?? '');
    $cantidad = htmlspecialchars($pedido['cantidad'] ?? '0');

    // Rango de tallas (mover antes para usarlo en cabecera del ticket)
    $rangos = [
        'caballero' => ['min' => 37, 'max' => 45],
        'dama' => ['min' => 33, 'max' => 41],
        'niño' => ['min' => 20, 'max' => 26],
        'juvenil' => ['min' => 27, 'max' => 36]
    ];

    // Verificar que el tipo de calzado existe en los rangos
    if (!isset($rangos[$tipo_calzado])) {
        continue;
    }

    $rango = $rangos[$tipo_calzado];
    $tallas = json_decode($pedido['tallas'] ?? '[]', true) ?: [];

    $html .= '<div class="header">
        <h2>STRONG SHOES</h2>
        <div>TICKET DE PEDIDO</div>
    </div>';
    $html .= '<div class="ticket-info">
        <table>
            <tr>
                <td><strong>Numeración:</strong> ' . $rango['min'] . ' - ' . $rango['max'] . ' | <strong>Cant. pares:</strong> ' . $cantidad . '</td>
                <td><strong>Suela:</strong> ' . $suela . '</td>
            </tr>
            <tr>
                <td><strong>Fecha:</strong> ' . $fecha . '</td>
                <td><strong>Ciudad:</strong> ' . $ciudad . '</td>
            </tr>
            <tr>
                <td><strong>Ref:</strong> ' . $ref . '</td>
                <td><strong>Color:</strong> ' . $color . '</td>
            </tr>
            <tr>
                <td><strong>De:</strong> ' . ucfirst($tipo_calzado) . '</td>
                <td><strong>Marquilla:</strong> ' . $marquilla . '</td>
            </tr>
            <tr>
                <td><strong>Tique:</strong> ' . $tique . '</td>
                <td><strong>Cliente:</strong> ' . $cliente . '</td>
            </tr>';
    // Agregar tabla compacta de numeración antes de Observaciones
    $html .= '<tr><td colspan="2">'
          . '<div class="tallas-section">'
          . '<div class="tallas-title">NUMERACIÓN</div>'
          . '<table class="tallas-table"><tr>';
    for ($i = $rango['min']; $i <= $rango['max']; $i++) { $html .= '<th>' . $i . '</th>'; }
    $html .= '</tr><tr>';
    for ($i = $rango['min']; $i <= $rango['max']; $i++) { $val = isset($tallas[$i]) ? $tallas[$i] : '0'; $html .= '<td>' . $val . '</td>'; }
    $html .= '</tr></table></div></td></tr>';
    $html .= '    <tr>
                <td colspan="2" class="obs-cell"><strong>Obs:</strong> ' . $observaciones . '</td>
            </tr>
        </table>
    </div>';

    

    $roles = [
        'LIMPIADA' => [
            'info' => [
                'DE:' . strtoupper($tipo_calzado),
                'CANT:' . $cantidad . ' PARES',
                'REF:' . $ref,
                'TIQ:' . $tique
            ]
        ],
        'SOLETERO' => [
            'info' => [
                'DE:' . strtoupper($tipo_calzado),
                'CANT:' . $cantidad . ' PARES',
                'REF:' . $ref,
                'TIQ:' . $tique,
                'SUELA:' . $suela
            ]
        ],
        'MONTADOR' => [
            'info' => [
                'DE:' . strtoupper($tipo_calzado),
                'CANT:' . $cantidad . ' PARES',
                'REF:' . $ref,
                'TIQ:' . $tique,
                'SUELA:' . $suela
            ]
        ],
        'COSTURERO' => [
            'info' => [
                'DE:' . strtoupper($tipo_calzado),
                'CANT:' . $cantidad . ' PARES',
                'REF:' . $ref,
                'TIQ:' . $tique
            ]
        ],
        'CORTADOR' => [
            'info' => [
                'DE:' . strtoupper($tipo_calzado),
                'CANT:' . $cantidad . ' PARES',
                'REF:' . $ref,
                'TIQ:' . $tique
            ]
        ]
    ];

    foreach ($roles as $rol => $datos) {
        $html .= '<div class="tallas-section">
            <div class="tallas-title">' . $rol . ' <span class="elaborado-por">ELABORADO POR: _____________________</span></div>';
        $html .= '<div class="role-info">' . implode(' | ', $datos['info']) . '</div>';
        $html .= '<table class="tallas-table"><tr>';
        
        for ($i = $rango['min']; $i <= $rango['max']; $i++) {
            $html .= '<th>' . $i . '</th>';
        }
        $html .= '</tr><tr>';
        
        for ($i = $rango['min']; $i <= $rango['max']; $i++) {
            $valor = isset($tallas[$i]) ? $tallas[$i] : '0';
            $html .= '<td>' . $valor . '</td>';
        }
        $html .= '</tr></table></div>';
    }

    $html .= '<div style="page-break-after: always;"></div>';
}

if (empty($html)) {
    die('No se encontraron pedidos válidos para imprimir.');
}

$dompdf = new Dompdf(['defaultFont' => 'Helvetica']);
$dompdf->loadHtml($html);
$dompdf->setPaper([0, 0, 283.46, 708.66], 'portrait');
$dompdf->render();

// Limpiar cualquier salida anterior
ob_end_clean();

// Enviar el PDF
$dompdf->stream("tickets_seleccionados.pdf", ["Attachment" => false]);
exit; 
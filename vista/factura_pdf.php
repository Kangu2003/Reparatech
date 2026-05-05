<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php?accion=login');
    exit();
}

require_once __DIR__ . '/../modelo/Pago.php';

$reservaId = (int)($_GET['reserva'] ?? 0);
$usuarioId = (int)$_SESSION['id'];

$modeloPago = new Pago();
$factura = $modeloPago->obtenerDetalleFactura($reservaId, $usuarioId);

if (!$factura) {
    die("Error: La factura no existe o no tienes permiso para verla.");
}

// Variables para cálculos
$subtotal = $factura['monto'] / 1.19; // Asumiendo IVA del 19%
$iva = $factura['monto'] - $subtotal;
$total = $factura['monto'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Factura <?= htmlspecialchars($factura['referencia']) ?></title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Syne:wght@700;800&display=swap');
    
    :root {
      --brand: #2A4747;
      --brand-light: #61D095;
      --gray: #f3f4f6;
      --text: #1f2937;
      --text-light: #6b7280;
    }
    
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DM Sans', sans-serif; color: var(--text); background: #e5e7eb; padding: 2rem; display: flex; justify-content: center; }
    
    .invoice-box {
      background: white;
      width: 100%;
      max-width: 800px;
      padding: 3rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      position: relative;
    }
    
    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid var(--gray);
      padding-bottom: 2rem;
      margin-bottom: 2rem;
    }
    
    .logo { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; color: var(--brand); }
    .logo span { color: var(--brand-light); }
    
    .invoice-info { text-align: right; }
    .invoice-info h2 { font-family: 'Syne', sans-serif; color: var(--brand); font-size: 1.8rem; margin-bottom: 0.5rem; text-transform: uppercase; }
    .invoice-info p { color: var(--text-light); font-size: 0.9rem; margin-bottom: 0.2rem; }
    .invoice-info strong { color: var(--text); }
    
    .details-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 3rem;
    }
    
    .details-col { width: 48%; }
    .details-title { font-family: 'Syne', sans-serif; font-size: 0.85rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.8rem; border-bottom: 1px solid var(--gray); padding-bottom: 0.5rem; }
    .details-col p { font-size: 0.95rem; line-height: 1.6; }
    .details-col strong { color: var(--brand); }
    
    table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
    th { text-align: left; padding: 1rem; background: var(--gray); color: var(--brand); font-family: 'Syne', sans-serif; font-size: 0.85rem; text-transform: uppercase; }
    td { padding: 1rem; border-bottom: 1px solid var(--gray); font-size: 0.95rem; }
    
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    
    .totals-box {
      width: 350px;
      margin-left: auto;
      border: 1px solid var(--gray);
      border-radius: 8px;
    }
    .totals-row { display: flex; justify-content: space-between; padding: 0.8rem 1.2rem; border-bottom: 1px solid var(--gray); font-size: 0.95rem; }
    .totals-row:last-child { border-bottom: none; background: var(--brand); color: white; border-radius: 0 0 8px 8px; font-weight: bold; font-size: 1.2rem; }
    
    .footer { margin-top: 4rem; text-align: center; color: var(--text-light); font-size: 0.85rem; border-top: 1px solid var(--gray); padding-top: 2rem; }
    .status-stamp { position: absolute; top: 15rem; right: 3rem; color: rgba(97, 208, 149, 0.15); font-size: 5rem; font-family: 'Syne', sans-serif; font-weight: 800; text-transform: uppercase; transform: rotate(-15deg); pointer-events: none; }

    /* Print settings */
    @media print {
      body { background: white; padding: 0; display: block; }
      .invoice-box { box-shadow: none; max-width: 100%; padding: 0; }
      .print-btn { display: none !important; }
    }
    
    .print-btn {
      position: fixed; bottom: 2rem; right: 2rem; background: var(--brand-light); color: var(--brand); padding: 1rem 2rem; border-radius: 100px; font-family: 'Syne', sans-serif; font-weight: bold; cursor: pointer; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-size: 1rem; transition: transform 0.2s;
    }
    .print-btn:hover { transform: translateY(-3px); }
  </style>
</head>
<body>

  <div class="invoice-box">
    <div class="status-stamp">PAGADO</div>
    
    <div class="header">
      <div>
        <div class="logo">Repara<span>Tech</span></div>
        <p style="color:var(--text-light); font-size:0.9rem; margin-top:0.5rem;">Servicios técnicos a domicilio<br>NIT: 900.123.456-7<br>Bogotá, Colombia</p>
      </div>
      <div class="invoice-info">
        <h2>FACTURA</h2>
        <p>Referencia: <strong><?= htmlspecialchars($factura['referencia']) ?></strong></p>
        <p>Fecha de Pago: <strong><?= date('d/m/Y', strtotime($factura['fecha_pago'])) ?></strong></p>
        <p>Hora de Pago: <strong><?= date('h:i A', strtotime($factura['fecha_pago'])) ?></strong></p>
      </div>
    </div>
    
    <div class="details-row">
      <div class="details-col">
        <div class="details-title">Facturar a:</div>
        <p>
          <strong><?= htmlspecialchars($factura['cliente_nombre']) ?></strong><br>
          <?= htmlspecialchars($factura['cliente_correo']) ?><br>
          Dirección del servicio: <?= htmlspecialchars($factura['direccion']) ?>
        </p>
      </div>
      <div class="details-col">
        <div class="details-title">Datos del Servicio:</div>
        <p>
          Reserva ID: <strong>#<?= str_pad($factura['reserva_id'], 5, '0', STR_PAD_LEFT) ?></strong><br>
          Atendido por: <?= htmlspecialchars($factura['tecnico_nombre']) ?><br>
          Método de Pago: <?= htmlspecialchars($factura['metodo_pago']) ?>
        </p>
      </div>
    </div>
    
    <table>
      <thead>
        <tr>
          <th>Descripción del Servicio</th>
          <th class="text-center">Fecha Ejecución</th>
          <th class="text-right">Total</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <strong><?= htmlspecialchars($factura['servicio']) ?></strong>
          </td>
          <td class="text-center"><?= date('d/m/Y', strtotime($factura['fecha_servicio'])) ?></td>
          <td class="text-right">$<?= number_format($factura['monto'], 0, ',', '.') ?></td>
        </tr>
      </tbody>
    </table>
    
    <div class="totals-box">
      <div class="totals-row">
        <span>Subtotal</span>
        <span>$<?= number_format($subtotal, 0, ',', '.') ?></span>
      </div>
      <div class="totals-row">
        <span>IVA (19%)</span>
        <span>$<?= number_format($iva, 0, ',', '.') ?></span>
      </div>
      <div class="totals-row">
        <span>Total Pagado</span>
        <span>$<?= number_format($total, 0, ',', '.') ?></span>
      </div>
    </div>
    
    <div class="footer">
      <p>Esta factura sirve como comprobante de pago electrónico.<br>Gracias por confiar en ReparaTech para tus servicios a domicilio.</p>
    </div>
  </div>

  <button class="print-btn" onclick="window.print()">🖨️ Descargar como PDF</button>

  <script>
    // Iniciar impresión automáticamente al cargar (opcional)
    // window.onload = function() { window.print(); }
  </script>
</body>
</html>

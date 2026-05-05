<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php?accion=login');
    exit();
}

if ($_SESSION['rol'] === 'tecnico') {
    header('Location: tecnico/dashboard.php');
    exit();
}

require_once __DIR__ . '/../modelo/Pago.php';

$usuarioId = (int)$_SESSION['id'];
$nombre    = htmlspecialchars($_SESSION['nombre'] ?? '');
$foto      = !empty($_SESSION['foto']) ? htmlspecialchars($_SESSION['foto']) : '';
$inicial   = strtoupper(substr($nombre, 0, 1));

$modeloPago = new Pago();
$facturas = $modeloPago->obtenerFacturasUsuario($usuarioId);

$pendientes = array_filter($facturas, fn($f) => empty($f['pago_id']));
$pagadas    = array_filter($facturas, fn($f) => !empty($f['pago_id']));

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Facturas y Pagos — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    :root { --pink:#E0BAD7; --green-light:#61D095; --green-mid:#48BF84; --green-sea:#439775; --green-dark:#2A4747; --white:#FAFAF8; --off-white:#F2F0EC; --text:#1a2a2a; --text-muted:#4a6a6a; }
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'DM Sans',sans-serif; background:var(--off-white); color:var(--text); min-height:100vh; }
    .bg-dots { position:fixed; inset:0; background-image:radial-gradient(var(--green-mid) 1px,transparent 1px); background-size:36px 36px; opacity:.06; pointer-events:none; z-index:0; }

    nav { position:fixed; top:0; left:0; right:0; z-index:100; display:flex; align-items:center; justify-content:space-between; padding:.85rem 5%; background:rgba(250,250,248,.95); backdrop-filter:blur(16px); border-bottom:1px solid rgba(72,191,132,.15); box-shadow:0 4px 30px rgba(42,71,71,.07); }
    .logo { font-family:'Syne',sans-serif; font-size:1.45rem; font-weight:800; color:var(--green-dark); text-decoration:none; }
    .logo span { color:var(--green-light); }
    .nav-right { display:flex; align-items:center; gap:.8rem; }
    .nav-avatar { width:34px; height:34px; border-radius:50%; background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:800; font-size:.85rem; display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0; text-decoration:none; }
    .nav-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
    .btn-nav { background:transparent; border:1.5px solid rgba(42,71,71,.2); color:var(--text-muted); font-size:.82rem; padding:.45rem 1rem; border-radius:100px; text-decoration:none; transition:all .2s; }
    .btn-nav:hover { border-color:var(--green-dark); color:var(--green-dark); }
    
    .page { padding:6.5rem 5% 3rem; max-width:1000px; margin:0 auto; position:relative; z-index:1; }

    .page-title { margin-bottom:2.5rem; animation:fadeUp .5s ease both; display:flex; justify-content:space-between; align-items:flex-end; }
    .page-title h1 { font-family:'Syne',sans-serif; font-size:clamp(1.6rem,3vw,2.3rem); font-weight:800; letter-spacing:-1.2px; color:var(--green-dark); }
    .page-title h1 em { font-style:normal; color:var(--green-mid); }
    .page-title p { color:var(--text-muted); font-size:.9rem; font-weight:300; margin-top:.3rem; }

    /* Tabs */
    .tabs { display:flex; gap:1rem; margin-bottom:2rem; border-bottom:2px solid rgba(42,71,71,.1); padding-bottom:1rem; animation:fadeUp .5s .1s ease both; }
    .tab { font-family:'Syne',sans-serif; font-weight:700; font-size:.95rem; color:var(--text-muted); cursor:pointer; padding:.5rem 1.2rem; border-radius:100px; transition:all .2s; }
    .tab.active { background:var(--green-light); color:var(--green-dark); }
    .tab:hover:not(.active) { background:rgba(42,71,71,.05); color:var(--green-dark); }
    .tab-content { display:none; animation:fadeUp .4s ease both; }
    .tab-content.active { display:block; }

    /* Table */
    .table-container { background:var(--white); border-radius:24px; border:1px solid rgba(72,191,132,.1); box-shadow:0 10px 40px rgba(42,71,71,.05); overflow:hidden; }
    table { width:100%; border-collapse:collapse; text-align:left; }
    th, td { padding:1.2rem 1.5rem; border-bottom:1px solid rgba(42,71,71,.05); }
    th { font-family:'Syne',sans-serif; font-weight:700; font-size:.8rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted); background:rgba(250,250,248,.5); }
    td { font-size:.9rem; color:var(--text); }
    tr:last-child td { border-bottom:none; }
    tr:hover td { background:rgba(97,208,149,.03); }

    .service-name { font-family:'Syne',sans-serif; font-weight:700; color:var(--green-dark); }
    .amount { font-family:'Syne',sans-serif; font-weight:800; color:var(--green-sea); }
    
    .tag { display:inline-block; font-size:.7rem; font-weight:700; padding:.3rem .8rem; border-radius:100px; }
    .tag-pendiente { background:rgba(251,191,36,.15); color:#b45309; }
    .tag-pagada { background:rgba(97,208,149,.15); color:var(--green-sea); }

    .btn-action { display:inline-block; font-family:'Syne',sans-serif; font-weight:700; font-size:.8rem; padding:.5rem 1.2rem; border-radius:100px; text-decoration:none; transition:all .2s; text-align:center; }
    .btn-pay { background:var(--green-light); color:var(--green-dark); }
    .btn-pay:hover { background:var(--green-mid); transform:translateY(-2px); }
    .btn-download { background:rgba(42,71,71,.08); color:var(--green-dark); }
    .btn-download:hover { background:rgba(42,71,71,.15); transform:translateY(-2px); }

    .empty-state { padding:4rem 2rem; text-align:center; color:var(--text-muted); }
    .empty-state span { font-size:3rem; display:block; margin-bottom:1rem; }

    @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
  </style>
</head>
<body>
<div class="bg-dots"></div>

<nav>
  <a href="../index.php" class="logo">Repara<span>Tech</span></a>
  <div class="nav-right">
    <a href="bienvenida.php" class="btn-nav">Inicio</a>
    <a href="mis-reservas.php" class="btn-nav">Mis Reservas</a>
    <a href="perfil.php" class="nav-avatar">
      <?php if($foto): ?><img src="<?= $foto ?>" alt="foto"><?php else: ?><?= $inicial ?><?php endif; ?>
    </a>
  </div>
</nav>

<div class="page">
  <div class="page-title">
    <div>
      <h1>Facturación y <em>Pagos</em></h1>
      <p>Administra tus facturas y mantén tus cuentas al día</p>
    </div>
  </div>

  <div class="tabs">
    <div class="tab active" data-target="tab-pendientes">Pendientes por Pagar (<?= count($pendientes) ?>)</div>
    <div class="tab" data-target="tab-pagadas">Facturas Pagadas (<?= count($pagadas) ?>)</div>
  </div>

  <!-- PENDIENTES -->
  <div id="tab-pendientes" class="tab-content active">
    <div class="table-container">
      <?php if(empty($pendientes)): ?>
        <div class="empty-state">
          <span>🎉</span>
          <p>¡Todo al día! No tienes pagos pendientes.</p>
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Reserva ID</th>
              <th>Servicio</th>
              <th>Técnico</th>
              <th>Monto</th>
              <th>Estado</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($pendientes as $p): ?>
            <tr>
              <td>#<?= str_pad($p['reserva_id'], 5, '0', STR_PAD_LEFT) ?></td>
              <td><div class="service-name"><?= htmlspecialchars($p['servicio']) ?></div><small><?= date('d/m/Y', strtotime($p['fecha_servicio'])) ?></small></td>
              <td><?= htmlspecialchars($p['tecnico_nombre']) ?></td>
              <td class="amount">$<?= number_format($p['monto'], 0, ',', '.') ?></td>
              <td><span class="tag tag-pendiente">Por Pagar</span></td>
              <td><a href="checkout.php?reserva=<?= $p['reserva_id'] ?>" class="btn-action btn-pay">Pagar Ahora</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- PAGADAS -->
  <div id="tab-pagadas" class="tab-content">
    <div class="table-container">
      <?php if(empty($pagadas)): ?>
        <div class="empty-state">
          <span>📄</span>
          <p>Aún no tienes facturas pagadas.</p>
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Factura / Ref</th>
              <th>Servicio</th>
              <th>Fecha de Pago</th>
              <th>Monto</th>
              <th>Método</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($pagadas as $p): ?>
            <tr>
              <td><strong><?= htmlspecialchars($p['referencia']) ?></strong><br><small>Reserva #<?= $p['reserva_id'] ?></small></td>
              <td><div class="service-name"><?= htmlspecialchars($p['servicio']) ?></div></td>
              <td><?= date('d/m/Y h:i A', strtotime($p['fecha_pago'])) ?></td>
              <td class="amount">$<?= number_format($p['monto'], 0, ',', '.') ?></td>
              <td><?= htmlspecialchars($p['metodo_pago']) ?></td>
              <td><a href="factura_pdf.php?reserva=<?= $p['reserva_id'] ?>" target="_blank" class="btn-action btn-download">📄 Descargar PDF</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

</div>

<script>
  // Tab Switching
  const tabs = document.querySelectorAll('.tab');
  const contents = document.querySelectorAll('.tab-content');

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      contents.forEach(c => c.classList.remove('active'));
      
      tab.classList.add('active');
      document.getElementById(tab.dataset.target).classList.add('active');
    });
  });
</script>
</body>
</html>

<?php
$nombre  = htmlspecialchars($_SESSION['nombre'] ?? 'Administrador');
$foto    = !empty($_SESSION['foto']) ? htmlspecialchars($_SESSION['foto']) : '';
$inicial = strtoupper(substr($nombre, 0, 1));

$gananciasCertificados = 0;
$totalPagado = 0;
$retirosMembresias = [];
$retirosPagos = [];

if (!empty($retiros)) {
    foreach($retiros as $r) {
        if ($r['tipo_cuenta'] === 'Membresía' && $r['estado'] === 'aprobado') {
            $gananciasCertificados += $r['monto'];
            $retirosMembresias[] = $r;
        } else {
            if ($r['estado'] === 'aprobado') {
                $totalPagado += $r['monto'];
            }
            $retirosPagos[] = $r;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Retiros — Admin ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    :root { --green-light:#61D095; --green-mid:#48BF84; --green-sea:#439775; --green-dark:#2A4747; --white:#FAFAF8; --off-white:#F2F0EC; --text:#1a2a2a; --text-muted:#4a6a6a; }
    *,*::before,*::after { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'DM Sans',sans-serif; background:var(--off-white); color:var(--text); display:flex; min-height:100vh; }

    /* SIDEBAR */
    .sidebar { width:240px; background:var(--white); border-right:1px solid rgba(72,191,132,.12); display:flex; flex-direction:column; padding:1.8rem 0; position:fixed; height:100vh; z-index:100; box-shadow:4px 0 30px rgba(42,71,71,.05); }
    .logo { font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:800; color:var(--green-dark); text-decoration:none; padding:0 1.5rem; margin-bottom:2rem; display:block; }
    .logo span { color:var(--green-light); }
    .sidebar-label { font-size:.65rem; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:var(--text-muted); padding:0 1.5rem; margin-bottom:.5rem; margin-top:1rem; }
    .nav-links { display:flex; flex-direction:column; gap:.25rem; padding:0 .8rem; flex:1; }
    .nav-link { display:flex; align-items:center; gap:.8rem; padding:.75rem 1rem; text-decoration:none; color:var(--text-muted); font-weight:500; font-size:.88rem; border-radius:12px; transition:all .2s; }
    .nav-link:hover { background:rgba(97,208,149,.1); color:var(--green-dark); }
    .nav-link.active { background:var(--green-dark); color:var(--white); font-weight:700; }

    /* MAIN */
    .main-content { flex:1; margin-left:240px; padding:2rem 2.5rem; min-height:100vh; }
    .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; }
    .top-bar h1 { font-family:'Syne',sans-serif; font-size:1.7rem; font-weight:800; color:var(--green-dark); letter-spacing:-1px; }
    .top-bar p { color:var(--text-muted); font-size:.88rem; margin-top:.2rem; }
    .admin-pill { display:flex; align-items:center; gap:.8rem; background:var(--white); padding:.5rem 1rem .5rem .5rem; border-radius:100px; border:1px solid rgba(72,191,132,.15); box-shadow:0 4px 16px rgba(42,71,71,.05); }
    .admin-avatar { width:34px; height:34px; border-radius:50%; background:var(--green-dark); color:var(--white); display:flex; align-items:center; justify-content:center; font-family:'Syne',sans-serif; font-weight:700; font-size:.82rem; overflow:hidden; }

    /* CARDS & TABLES */
    .card { background:var(--white); border-radius:20px; padding:1.5rem; border:1px solid rgba(72,191,132,.1); box-shadow:0 4px 20px rgba(42,71,71,.05); animation:fadeUp .4s ease both; overflow-x:auto; }
    table { width:100%; border-collapse:collapse; min-width:800px; }
    th, td { padding:1rem; text-align:left; border-bottom:1px solid rgba(72,191,132,.08); }
    th { font-family:'Syne',sans-serif; font-size:.78rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px; background:var(--off-white); }
    tr:last-child td { border-bottom:none; }
    td { font-size:.88rem; color:var(--text); }
    .tecnico-name { font-family:'Syne',sans-serif; font-weight:700; color:var(--green-dark); font-size:.92rem; }
    .bank-info { font-size:.82rem; color:var(--text-muted); }
    .monto { font-family:'Syne',sans-serif; font-weight:800; color:var(--green-sea); font-size:1rem; }

    .tag { font-size:.7rem; font-weight:700; padding:.25rem .7rem; border-radius:100px; text-transform:uppercase; }
    .tag-pendiente { background:rgba(251,191,36,.15); color:#b45309; }
    .tag-aprobado { background:rgba(97,208,149,.15); color:var(--green-sea); }
    .tag-rechazado { background:rgba(220,38,38,.08); color:#dc2626; }

    .btn { padding:.4rem .8rem; border-radius:100px; font-family:'Syne',sans-serif; font-weight:700; font-size:.75rem; border:none; cursor:pointer; transition:all .2s; }
    .btn-approve { background:rgba(97,208,149,.15); color:var(--green-sea); }
    .btn-approve:hover { background:rgba(97,208,149,.3); }
    .btn-reject { background:rgba(220,38,38,.1); color:#dc2626; }
    .btn-reject:hover { background:rgba(220,38,38,.2); }
    
    .actions { display:flex; gap:.4rem; }

    .btn-tab { padding:.6rem 1.2rem; border-radius:100px; font-family:'Syne',sans-serif; font-weight:700; font-size:.9rem; border:none; cursor:pointer; background:rgba(72,191,132,.1); color:var(--green-sea); transition:all .2s; }
    .btn-tab.active { background:var(--green-sea); color:#fff; box-shadow:0 4px 12px rgba(67,151,117,.2); }
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeUp .3s ease both; }

    @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
  </style>
</head>
<body>

<aside class="sidebar">
  <a href="#" class="logo">Repara<span>Tech</span></a>
  <div class="sidebar-label">Principal</div>
  <div class="nav-links">
    <a href="admin.php?accion=dashboard"   class="nav-link">📊 Dashboard</a>
    <a href="admin.php?accion=usuarios"    class="nav-link">👥 Usuarios</a>
    <a href="admin.php?accion=tecnicos"    class="nav-link">🔧 Técnicos</a>
    <a href="admin.php?accion=reservas"    class="nav-link">📅 Reservas</a>
    <a href="admin.php?accion=categorias"  class="nav-link">📁 Categorías</a>
    <a href="admin.php?accion=retiros"     class="nav-link active">💰 Pagos</a>
  </div>
</aside>

<main class="main-content">
  <div class="top-bar">
    <div>
      <h1>Gestión de Retiros</h1>
      <p>Administra y aprueba las solicitudes de retiro de los técnicos</p>
    </div>
    <div class="admin-pill">
      <div class="admin-avatar">
        <?php if($foto): ?><img src="<?= $foto ?>" style="width:100%;height:100%;object-fit:cover;"><?php else: ?><?= $inicial ?><?php endif; ?>
      </div>
      <div>
        <div style="font-weight:700; font-size:.82rem; color:var(--green-dark);"><?= $nombre ?></div>
        <div style="font-size:.68rem; color:var(--green-sea); font-weight:600; text-transform:uppercase;">Super Admin</div>
      </div>
    </div>
  </div>

  <!-- RESUMEN FINANCIERO -->
  <div style="display:flex; gap:1.5rem; margin-bottom:2rem;">
    <div class="card" style="flex:1; border-left: 5px solid #b45309;">
      <div style="color:var(--text-muted); font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Ganancias por Certificados</div>
      <div style="font-size:2rem; font-weight:800; color:#b45309; margin-top:0.5rem; font-family:'Syne',sans-serif;">
        $<?= number_format($gananciasCertificados, 0, ',', '.') ?>
      </div>
    </div>
    <div class="card" style="flex:1; border-left: 5px solid var(--green-sea);">
      <div style="color:var(--text-muted); font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Total Pagado a Técnicos</div>
      <div style="font-size:2rem; font-weight:800; color:var(--green-sea); margin-top:0.5rem; font-family:'Syne',sans-serif;">
        $<?= number_format($totalPagado, 0, ',', '.') ?>
      </div>
    </div>
  </div>

  <div style="display:flex; gap:1rem; margin-bottom: 1.5rem;">
    <button class="btn-tab active" onclick="showTab('pagos', this)">Pagos a Técnicos</button>
    <button class="btn-tab" onclick="showTab('membresias', this)">Ventas de Membresía</button>
  </div>

  <!-- PESTAÑA: PAGOS A TÉCNICOS -->
  <div class="card tab-content active" id="tab-pagos">
    <?php if(empty($retirosPagos)): ?>
      <div style="text-align:center; padding:3rem; color:var(--text-muted);">
        <span style="font-size:3rem; display:block; margin-bottom:1rem;">💸</span>
        No hay solicitudes de retiro en este momento.
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Técnico</th>
            <th>Monto</th>
            <th>Cuenta de Destino</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($retirosPagos as $r): ?>
          <tr>
            <td><?= date('d/m/Y h:i A', strtotime($r['creado_en'])) ?></td>
            <td>
              <div class="tecnico-name"><?= htmlspecialchars($r['tecnico_nombre']) ?></div>
              <div style="font-size:.75rem; color:var(--text-muted);"><?= htmlspecialchars($r['correo_electronico']) ?></div>
            </td>
            <td class="monto">$<?= number_format($r['monto'], 0, ',', '.') ?></td>
            <td>
              <div style="font-weight:600;"><?= htmlspecialchars($r['banco']) ?></div>
              <div class="bank-info"><?= htmlspecialchars($r['tipo_cuenta']) ?> - <?= htmlspecialchars($r['numero_cuenta']) ?></div>
            </td>
            <td><span class="tag tag-<?= $r['estado'] ?>"><?= $r['estado'] ?></span></td>
            <td>
              <?php if($r['estado'] === 'pendiente'): ?>
                <div class="actions">
                  <form method="POST" action="admin.php?accion=cambiar_estado_retiro" onsubmit="return confirm('¿Aprobar el retiro por $<?= number_format($r['monto'], 0, ',', '.') ?>?');">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="estado" value="aprobado">
                    <button class="btn btn-approve">✅ Aprobar</button>
                  </form>
                  <form method="POST" action="admin.php?accion=cambiar_estado_retiro" onsubmit="return confirm('¿Rechazar este retiro?');">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="estado" value="rechazado">
                    <button class="btn btn-reject">❌ Rechazar</button>
                  </form>
                </div>
              <?php else: ?>
                <span style="color:var(--text-muted); font-size:.8rem;">Procesado</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- PESTAÑA: VENTAS DE MEMBRESÍA -->
  <div class="card tab-content" id="tab-membresias">
    <?php if(empty($retirosMembresias)): ?>
      <div style="text-align:center; padding:3rem; color:var(--text-muted);">
        <span style="font-size:3rem; display:block; margin-bottom:1rem;">💎</span>
        No hay ventas de membresía premium registradas aún.
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Fecha de Compra</th>
            <th>Técnico</th>
            <th>Monto</th>
            <th>Concepto</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($retirosMembresias as $r): ?>
          <tr>
            <td><?= date('d/m/Y h:i A', strtotime($r['creado_en'])) ?></td>
            <td>
              <div class="tecnico-name"><?= htmlspecialchars($r['tecnico_nombre']) ?></div>
              <div style="font-size:.75rem; color:var(--text-muted);"><?= htmlspecialchars($r['correo_electronico']) ?></div>
            </td>
            <td class="monto" style="color:#b45309;">$<?= number_format($r['monto'], 0, ',', '.') ?></td>
            <td>
              <div style="font-weight:600; color:#b45309;">💎 Premium Activado</div>
              <div class="bank-info">Pagado de saldo disponible</div>
            </td>
            <td><span class="tag tag-aprobado">Pagado</span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <script>
    function showTab(tabId, btn) {
      document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
      document.querySelectorAll('.btn-tab').forEach(el => el.classList.remove('active'));
      document.getElementById('tab-' + tabId).classList.add('active');
      btn.classList.add('active');
    }
  </script>

</main>
</body>
</html>

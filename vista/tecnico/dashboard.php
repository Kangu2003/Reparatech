<?php
/**
 * vista/tecnico/dashboard.php — Panel del técnico
 */
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'tecnico') {
    header('Location: ../../index.php');
    exit();
}

require_once __DIR__ . '/../../modelo/Tecnico.php';

$nombre  = htmlspecialchars($_SESSION['nombre'] ?? 'Técnico');
$correo  = htmlspecialchars($_SESSION['usuario']);
$foto    = !empty($_SESSION['foto']) ? htmlspecialchars($_SESSION['foto']) : '';
$inicial = strtoupper(substr($nombre, 0, 1));
$id      = $_SESSION['id'] ?? 0;

$modeloTecnico = new Tecnico();
$servicios     = $modeloTecnico->obtenerMisServicios($id);
$solicitudes   = $modeloTecnico->obtenerSolicitudes($id);
$ganancias     = $modeloTecnico->obtenerGanancias($id);
$resenas       = $modeloTecnico->obtenerResenas($id);
$calificacion  = $modeloTecnico->obtenerCalificacionPromedio($id);
$esPremium     = $modeloTecnico->esPremium($id);
$esExperto     = $modeloTecnico->esExperto($id);

$pendientes  = array_filter($solicitudes, fn($s) => strtolower($s['estado']) === 'pendiente');
$aceptadas   = array_filter($solicitudes, fn($s) => strtolower($s['estado']) === 'aceptada');
$completadas = array_filter($solicitudes, fn($s) => strtolower($s['estado']) === 'completada');
$canceladas  = array_filter($solicitudes, fn($s) => strtolower($s['estado']) === 'cancelada' || strtolower($s['estado']) === 'rechazada');

$retiros = $modeloTecnico->obtenerRetiros($id);
$totalGanado = (float)($ganancias['total_ganado'] ?? 0);
$totalRetirado = 0;
foreach($retiros as $r) {
    if ($r['estado'] !== 'rechazado') {
        $totalRetirado += (float)$r['monto'];
    }
}
$saldoDisponible = $totalGanado - $totalRetirado;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Técnico — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500&display=swap" rel="stylesheet">
  <style>
    :root { --pink:#E0BAD7; --green-light:#61D095; --green-mid:#48BF84; --green-sea:#439775; --green-dark:#2A4747; --white:#FAFAF8; --off-white:#F2F0EC; --text:#1a2a2a; --text-muted:#4a6a6a; }
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'DM Sans',sans-serif; background:var(--off-white); color:var(--text); min-height:100vh; }
    .bg-dots { position:fixed; inset:0; background-image:radial-gradient(var(--green-mid) 1px,transparent 1px); background-size:36px 36px; opacity:.06; pointer-events:none; z-index:0; }

    /* NAV */
    nav { position:fixed; top:0; left:0; right:0; z-index:100; display:flex; align-items:center; justify-content:space-between; padding:.85rem 5%; background:rgba(250,250,248,.95); backdrop-filter:blur(16px); border-bottom:1px solid rgba(72,191,132,.15); box-shadow:0 4px 30px rgba(42,71,71,.07); }
    .logo { font-family:'Syne',sans-serif; font-size:1.45rem; font-weight:800; color:var(--green-dark); text-decoration:none; }
    .logo span { color:var(--green-light); }
    .nav-badge { background:var(--green-dark); color:var(--white); font-family:'Syne',sans-serif; font-size:.7rem; font-weight:700; padding:.25rem .7rem; border-radius:100px; letter-spacing:.5px; text-transform:uppercase; }
    .nav-right { display:flex; align-items:center; gap:.8rem; }
    .nav-avatar { width:34px; height:34px; border-radius:50%; background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:800; font-size:.85rem; display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0; text-decoration:none; }
    .nav-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
    .btn-nav { background:transparent; border:1.5px solid rgba(42,71,71,.2); color:var(--text-muted); font-size:.82rem; padding:.45rem 1rem; border-radius:100px; text-decoration:none; transition:all .2s; }
    .btn-nav:hover { border-color:var(--green-dark); color:var(--green-dark); }

    /* LAYOUT */
    .page { padding:6.5rem 5% 3rem; max-width:1200px; margin:0 auto; position:relative; z-index:1; }

    /* TABS */
    .tabs { display:flex; gap:.5rem; margin-bottom:2rem; flex-wrap:wrap; animation:fadeUp .5s ease both; }
    .tab { font-family:'Syne',sans-serif; font-size:.82rem; font-weight:700; padding:.6rem 1.2rem; border-radius:100px; border:1.5px solid rgba(72,191,132,.2); background:var(--white); color:var(--text-muted); cursor:pointer; transition:all .22s; text-decoration:none; display:inline-flex; align-items:center; gap:.4rem; }
    .tab:hover { border-color:var(--green-mid); color:var(--green-dark); }
    .tab.active { background:var(--green-dark); color:var(--white); border-color:var(--green-dark); }
    .tab .count { background:rgba(255,255,255,.2); padding:.1rem .5rem; border-radius:100px; font-size:.72rem; }
    .tab.active .count { background:rgba(255,255,255,.2); }
    .tab:not(.active) .count { background:rgba(97,208,149,.15); color:var(--green-sea); }

    /* SECTIONS */
    .section { display:none; }
    .section.active { display:block; animation:fadeUp .4s ease both; }

    /* STATS */
    .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1rem; margin-bottom:2rem; }
    .stat-card { background:var(--white); border-radius:20px; padding:1.4rem 1.6rem; border:1px solid rgba(72,191,132,.1); box-shadow:0 4px 20px rgba(42,71,71,.05); }
    .stat-icon { font-size:1.4rem; display:block; margin-bottom:.5rem; }
    .stat-value { font-family:'Syne',sans-serif; font-size:1.9rem; font-weight:800; color:var(--green-dark); letter-spacing:-1px; line-height:1; display:block; margin-bottom:.2rem; }
    .stat-label { font-size:.78rem; color:var(--text-muted); }

    /* CARDS */
    .card { background:var(--white); border-radius:24px; border:1px solid rgba(72,191,132,.1); box-shadow:0 4px 24px rgba(42,71,71,.06); overflow:hidden; margin-bottom:1.5rem; }
    .card-header { padding:1.3rem 1.6rem 1rem; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid rgba(72,191,132,.08); flex-wrap:wrap; gap:.8rem; }
    .card-title { font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; color:var(--green-dark); }
    .card-body { padding:1.4rem 1.6rem; }

    /* BTN */
    .btn-primary { background:var(--green-dark); color:var(--white); font-family:'Syne',sans-serif; font-weight:700; font-size:.85rem; padding:.7rem 1.4rem; border-radius:100px; border:none; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:.4rem; transition:all .22s; }
    .btn-primary:hover { background:#1a2a2a; transform:translateY(-1px); }
    .btn-secondary { background:transparent; color:var(--green-sea); border:1.5px solid rgba(67,151,117,.25); font-family:'Syne',sans-serif; font-weight:600; font-size:.82rem; padding:.6rem 1.2rem; border-radius:100px; cursor:pointer; text-decoration:none; transition:all .22s; }
    .btn-secondary:hover { background:rgba(97,208,149,.08); border-color:var(--green-mid); }
    .btn-danger { background:transparent; color:#dc2626; border:1.5px solid rgba(220,38,38,.25); font-family:'Syne',sans-serif; font-weight:600; font-size:.8rem; padding:.5rem 1rem; border-radius:100px; cursor:pointer; transition:all .22s; }
    .btn-danger:hover { background:#fee2e2; }
    .btn-success { background:rgba(97,208,149,.15); color:var(--green-sea); border:1.5px solid rgba(97,208,149,.3); font-family:'Syne',sans-serif; font-weight:700; font-size:.8rem; padding:.5rem 1rem; border-radius:100px; cursor:pointer; transition:all .22s; }
    .btn-success:hover { background:rgba(97,208,149,.25); }

    /* SERVICIOS GRID */
    .servicios-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1rem; }
    .servicio-card { background:var(--off-white); border:1px solid rgba(72,191,132,.12); border-radius:20px; padding:1.3rem; transition:all .22s; }
    .servicio-card:hover { border-color:var(--green-mid); box-shadow:0 6px 20px rgba(97,208,149,.12); }
    .servicio-top { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:.8rem; }
    .servicio-cat { display:inline-flex; align-items:center; gap:.3rem; background:var(--white); border-radius:100px; padding:.25rem .7rem; font-size:.72rem; font-weight:600; color:var(--green-sea); border:1px solid rgba(72,191,132,.15); }
    .servicio-badge { font-size:.68rem; font-weight:700; padding:.2rem .6rem; border-radius:100px; }
    .badge-on  { background:rgba(97,208,149,.15); color:var(--green-sea); }
    .badge-off { background:rgba(220,38,38,.08); color:#dc2626; }
    .servicio-titulo { font-family:'Syne',sans-serif; font-weight:700; font-size:.95rem; color:var(--green-dark); margin-bottom:.4rem; }
    .servicio-desc { font-size:.8rem; color:var(--text-muted); font-weight:300; line-height:1.5; margin-bottom:1rem; }
    .servicio-footer { display:flex; align-items:center; justify-content:space-between; }
    .servicio-precio { font-family:'Syne',sans-serif; font-weight:800; font-size:1.1rem; color:var(--green-dark); }
    .servicio-tipo { font-size:.72rem; color:var(--text-muted); font-weight:300; }
    .servicio-actions { display:flex; gap:.5rem; margin-top:.8rem; }

    /* SOLICITUDES */
    .solicitud-item { padding:1.1rem 0; border-bottom:1px solid rgba(72,191,132,.07); display:flex; align-items:flex-start; gap:1rem; flex-wrap:wrap; }
    .solicitud-item:last-child { border-bottom:none; }
    .sol-avatar { width:44px; height:44px; border-radius:50%; background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:800; font-size:1rem; display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0; }
    .sol-avatar img { width:100%; height:100%; object-fit:cover; }
    .sol-info { flex:1; min-width:200px; }
    .sol-cliente { font-family:'Syne',sans-serif; font-weight:700; font-size:.9rem; color:var(--green-dark); }
    .sol-servicio { font-size:.82rem; color:var(--text-muted); font-weight:300; margin-top:.15rem; }
    .sol-meta { font-size:.78rem; color:var(--text-muted); margin-top:.3rem; }
    .sol-actions { display:flex; gap:.5rem; align-items:center; flex-shrink:0; flex-wrap:wrap; }
    .tag { display:inline-block; font-size:.68rem; font-weight:700; padding:.2rem .6rem; border-radius:100px; }
    .tag-pendiente   { background:rgba(251,191,36,.15); color:#b45309; }
    .tag-aceptada    { background:rgba(97,208,149,.15); color:var(--green-sea); }
    .tag-en_progreso { background:rgba(59,130,246,.1);  color:#2563eb; }
    .tag-completada  { background:rgba(42,71,71,.1);    color:var(--green-dark); }
    .tag-cancelada   { background:rgba(220,38,38,.08);  color:#dc2626; }

    /* FORM */
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
    @media(max-width:600px){ .form-grid{ grid-template-columns:1fr; } }
    .form-group { display:flex; flex-direction:column; gap:.4rem; }
    .form-group.full { grid-column:1/-1; }
    .form-label { font-family:'Syne',sans-serif; font-size:.78rem; font-weight:700; color:var(--green-dark); text-transform:uppercase; }
    .form-input, .form-select, .form-textarea { background:var(--off-white); border:1.5px solid rgba(72,191,132,.2); border-radius:14px; padding:.8rem 1rem; font-family:'DM Sans',sans-serif; font-size:.9rem; color:var(--text); outline:none; transition:all .25s; resize:none; }
    .form-input:focus,.form-select:focus,.form-textarea:focus { border-color:var(--green-light); background:var(--white); box-shadow:0 0 0 4px rgba(97,208,149,.1); }

    /* RESEÑAS */
    .resena-item { padding:1rem 0; border-bottom:1px solid rgba(72,191,132,.07); }
    .resena-item:last-child { border-bottom:none; }
    .resena-header { display:flex; align-items:center; gap:.7rem; margin-bottom:.5rem; }
    .resena-stars { color:#f59e0b; font-size:.9rem; }
    .resena-cliente { font-family:'Syne',sans-serif; font-weight:700; font-size:.85rem; color:var(--green-dark); }
    .resena-servicio { font-size:.75rem; color:var(--text-muted); font-weight:300; }
    .resena-texto { font-size:.85rem; color:var(--text-muted); font-weight:300; line-height:1.6; }

    /* DISPONIBILIDAD */
    .dias-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:.75rem; }
    .dia-card { background:var(--off-white); border:1.5px solid rgba(72,191,132,.15); border-radius:16px; padding:1rem; }
    .dia-check { display:flex; align-items:center; gap:.5rem; margin-bottom:.6rem; cursor:pointer; }
    .dia-nombre { font-family:'Syne',sans-serif; font-weight:700; font-size:.88rem; color:var(--green-dark); }
    .dia-horas { display:flex; gap:.5rem; align-items:center; }
    .dia-horas input { flex:1; }

    /* EMPTY STATE */
    .empty { text-align:center; padding:3rem 1rem; color:var(--text-muted); }
    .empty-icon { font-size:2.5rem; margin-bottom:.8rem; }
    .empty p { font-size:.88rem; font-weight:300; }

    @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
    @media(max-width:560px){ .tabs{ gap:.35rem; } .tab{ font-size:.75rem; padding:.5rem .9rem; } }
    /* CHARTS */
    .chart-card { background:var(--white); border-radius:20px; padding:1.5rem; border:1px solid rgba(72,191,132,.1); box-shadow:0 4px 20px rgba(42,71,71,.05); margin-bottom:1.5rem; }
    .chart-header { margin-bottom:1.4rem; }
    .chart-title { font-family:'Syne',sans-serif; font-size:.95rem; font-weight:700; color:var(--green-dark); }
    .chart-subtitle { font-size:.75rem; color:var(--text-muted); font-weight:300; margin-top:.15rem; }
    .donut-wrap { display:flex; align-items:center; gap:1.5rem; }
    .donut-svg { flex-shrink:0; }
    .donut-legend { display:flex; flex-direction:column; gap:.6rem; flex:1; }
    .legend-item { display:flex; align-items:center; gap:.6rem; }
    .legend-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
    .legend-label { font-size:.78rem; color:var(--text-muted); flex:1; }
    .legend-val { font-family:'Syne',sans-serif; font-size:.82rem; font-weight:700; color:var(--text); }
    
    .hbar-item { display:flex; flex-direction:column; gap:.35rem; margin-bottom:.9rem; }
    .hbar-info { display:flex; justify-content:space-between; align-items:center; }
    .hbar-name { font-size:.82rem; font-weight:500; color:var(--text); }
    .hbar-val  { font-family:'Syne',sans-serif; font-size:.82rem; font-weight:700; color:var(--green-dark); }
    .hbar-track { height:8px; background:var(--off-white); border-radius:100px; overflow:hidden; }
    .hbar-fill  { height:100%; border-radius:100px; transition:width 1s ease; }
    
    .row { display:grid; gap:1.2rem; grid-template-columns:1fr 1fr; margin-bottom:1.5rem; }
    @media(max-width:800px) { .row { grid-template-columns:1fr; } }
  </style>
</head>
<body>

<div class="bg-dots"></div>

<!-- NAV -->
<nav>
  <div style="display:flex;align-items:center;gap:.8rem;">
    <a href="../../index.php" class="logo">Repara<span>Tech</span></a>
    <span class="nav-badge">🛠️ Técnico</span>
  </div>
  <div class="nav-right">
    <div id="navInboxContainer" style="position:relative;">
      <a href="#" class="btn-nav" style="position:relative;" id="navInboxBtn" onclick="toggleInboxDropdown(event)">
        💬 Mensajes <span id="navUnreadBadge" style="display:none; position:absolute; top:-6px; right:-6px; background:#ef4444; color:white; border-radius:50%; width:18px; height:18px; font-size:0.65rem; font-weight:700; align-items:center; justify-content:center;"></span>
      </a>
      <div id="inboxDropdown" style="display:none; position:absolute; top:calc(100% + 12px); right:0; width:340px; background:var(--white); border-radius:18px; border:1px solid rgba(72,191,132,.15); box-shadow:0 10px 40px rgba(42,71,71,.12); z-index:200; overflow:hidden;">
        <div style="padding:1rem 1.2rem; border-bottom:1px solid rgba(72,191,132,.1); font-family:'Syne',sans-serif; font-weight:700; font-size:.95rem; color:var(--green-dark); display:flex; justify-content:space-between; align-items:center;">
          <span>Mensajes</span>
        </div>
        <div id="inboxDropdownList" style="max-height:360px; overflow-y:auto; text-align:left;">
          <!-- Items injected via JS -->
        </div>
      </div>
    </div>
    <a href="../perfil.php" class="nav-avatar">
      <?php if($foto): ?><img src="<?= $foto ?>" alt="foto"><?php else: ?><?= $inicial ?><?php endif; ?>
    </a>
    <a href="../perfil.php" class="btn-nav"><?= $nombre ?></a>
    <a href="billetera.php" class="btn-nav" style="background:var(--green-light); color:var(--green-dark); border-color:var(--green-light); font-weight:700;">💰 Mi Billetera</a>
    <a href="../../index.php?accion=logout" class="btn-nav">Cerrar sesión</a>
  </div>
</nav>

<div class="page">

  <!-- TABS -->
  <div class="tabs">
    <a class="tab active" onclick="showTab('resumen')" href="#">📊 Resumen</a>
    <a class="tab" onclick="showTab('solicitudes')" href="#">📋 Solicitudes <span class="count"><?= count($pendientes) ?></span></a>
    <a class="tab" onclick="showTab('servicios')" href="#">🔧 Mis servicios <span class="count"><?= count($servicios) ?></span></a>
    <a class="tab" onclick="showTab('disponibilidad')" href="#">🗓️ Disponibilidad</a>
    <a class="tab" onclick="showTab('ganancias')" href="#">💰 Ganancias</a>
    <a class="tab" onclick="showTab('resenas')" href="#">⭐ Reseñas</a>
    <a class="tab" onclick="showTab('membresia')" href="#">💎 Membresía</a>
  </div>

  <!-- ═══ RESUMEN ═══ -->
  <div class="section active" id="tab-resumen">
    <div class="stats-row">
      <div class="stat-card">
        <span class="stat-icon">🔧</span>
        <span class="stat-value"><?= count($servicios) ?></span>
        <span class="stat-label">Servicios activos</span>
      </div>
      <div class="stat-card">
        <span class="stat-icon">📋</span>
        <span class="stat-value"><?= count($pendientes) ?></span>
        <span class="stat-label">Solicitudes pendientes</span>
      </div>
      <div class="stat-card">
        <span class="stat-icon">⭐</span>
        <span class="stat-value"><?= $calificacion ?: '—' ?></span>
        <span class="stat-label">Calificación promedio</span>
      </div>
      <div class="stat-card">
        <span class="stat-icon">💰</span>
        <span class="stat-value">$<?= number_format($ganancias['total_ganado'] ?? 0, 0, ',', '.') ?></span>
        <span class="stat-label">Total ganado</span>
      </div>
    </div>

    <!-- NUEVAS GRÁFICAS FINANCIERAS -->
    <div class="row">
      <!-- GRÁFICA DE SALDO (BARRAS HORIZONTALES) -->
      <div class="chart-card">
        <div class="chart-header">
          <div class="chart-title">💰 Balance Financiero</div>
          <div class="chart-subtitle">Tu estado de cuenta actual</div>
        </div>
        <div>
          <?php 
            $maxFinance = max($totalGanado, 1); // Evitar división por cero
            $pctGanado = 100; // Siempre 100% de la barra
            $pctDisponible = round(($saldoDisponible / $maxFinance) * 100);
            $pctRetirado = round(($totalRetirado / $maxFinance) * 100);
          ?>
          <div class="hbar-item">
            <div class="hbar-info">
              <span class="hbar-name">Ganancias Totales</span>
              <span class="hbar-val">$<?= number_format($totalGanado, 0, ',', '.') ?></span>
            </div>
            <div class="hbar-track"><div class="hbar-fill" style="width:<?= $pctGanado ?>%;background:#48BF84"></div></div>
          </div>
          <div class="hbar-item">
            <div class="hbar-info">
              <span class="hbar-name">Saldo Disponible</span>
              <span class="hbar-val">$<?= number_format($saldoDisponible, 0, ',', '.') ?></span>
            </div>
            <div class="hbar-track"><div class="hbar-fill" style="width:<?= $pctDisponible ?>%;background:#61D095"></div></div>
          </div>
          <div class="hbar-item">
            <div class="hbar-info">
              <span class="hbar-name">Saldo Retirado (o en proceso)</span>
              <span class="hbar-val">$<?= number_format($totalRetirado, 0, ',', '.') ?></span>
            </div>
            <div class="hbar-track"><div class="hbar-fill" style="width:<?= $pctRetirado ?>%;background:#fbbf24"></div></div>
          </div>
        </div>
        <div style="margin-top:1.5rem;">
          <a href="billetera.php" class="btn-primary" style="width:100%; justify-content:center;">💸 Solicitar Retiro</a>
        </div>
      </div>

      <!-- DONUT CHART (Servicios) -->
      <div class="chart-card">
        <div class="chart-header">
          <div class="chart-title">🍩 Tasa de Éxito de Servicios</div>
          <div class="chart-subtitle">Completados vs Cancelados/Rechazados</div>
        </div>
        <div class="donut-wrap">
          <svg class="donut-svg" width="110" height="110" viewBox="0 0 110 110">
            <?php
            $totalStats = count($completadas) + count($aceptadas) + count($canceladas) + count($pendientes);
            $totalStats = max(1, $totalStats); // Evitar divisón por cero
            
            $stats = [
                ['estado'=>'Aprobados / Completados', 'total'=>count($completadas) + count($aceptadas), 'color'=>'#61D095'],
                ['estado'=>'Cancelados / Rechazados', 'total'=>count($canceladas), 'color'=>'#f87171'],
                ['estado'=>'Pendientes', 'total'=>count($pendientes), 'color'=>'#fbbf24']
            ];
            
            $cx = 55; $cy = 55; $r = 42; $stroke = 18;
            $circ = 2 * M_PI * $r;
            $offset = 0;
            foreach ($stats as $e):
              $pct   = $e['total'] / $totalStats;
              $dash  = $pct * $circ;
              $gap   = $circ - $dash;
              $rot   = $offset * 360 - 90;
            ?>
            <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>"
              fill="none" stroke="<?= $e['color'] ?>" stroke-width="<?= $stroke ?>"
              stroke-dasharray="<?= round($dash,2) ?> <?= round($gap,2) ?>"
              stroke-dashoffset="0"
              transform="rotate(<?= round($rot,2) ?> <?= $cx ?> <?= $cy ?>)"
              style="transition:stroke-dasharray 1s ease"/>
            <?php $offset += $pct; endforeach; ?>
            <text x="55" y="50" text-anchor="middle" font-family="Syne,sans-serif" font-size="14" font-weight="800" fill="#2A4747"><?= $totalStats ?></text>
            <text x="55" y="63" text-anchor="middle" font-family="DM Sans,sans-serif" font-size="7" fill="#4a6a6a">solicitudes</text>
          </svg>
          <div class="donut-legend">
            <?php foreach ($stats as $e):
              $pct = round($e['total'] / $totalStats * 100);
            ?>
            <div class="legend-item">
              <div class="legend-dot" style="background:<?= $e['color'] ?>"></div>
              <span class="legend-label"><?= $e['estado'] ?></span>
              <span class="legend-val"><?= $e['total'] ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    <!-- FIN NUEVAS GRÁFICAS -->

    <!-- Solicitudes pendientes destacadas -->
    <?php if (count($pendientes) > 0): ?>
    <div class="card">
      <div class="card-header">
        <span class="card-title">⏳ Solicitudes pendientes</span>
        <a href="#" class="btn-secondary" onclick="showTab('solicitudes');return false;">Ver todas</a>
      </div>
      <div class="card-body">
        <?php foreach (array_slice($pendientes, 0, 3) as $s): ?>
        <div class="solicitud-item">
          <div class="sol-avatar">
            <?php if($s['cliente_foto']): ?><img src="<?= htmlspecialchars($s['cliente_foto']) ?>" alt=""><?php else: ?><?= strtoupper(substr($s['cliente'], 0, 1)) ?><?php endif; ?>
          </div>
          <div class="sol-info">
            <div class="sol-cliente"><?= htmlspecialchars($s['cliente']) ?></div>
            <div class="sol-servicio"><?= htmlspecialchars($s['servicio']) ?></div>
            <div class="sol-meta">📅 <?= $s['fecha'] ?> a las <?= $s['hora'] ?></div>
          </div>
          <div class="sol-actions">
            <form method="POST" action="../../controlador/ControladorTecnico.php">
              <input type="hidden" name="accion"     value="aceptar">
              <input type="hidden" name="reserva_id" value="<?= $s['id'] ?>">
              <button class="btn-success">✅ Aceptar</button>
            </form>
            <form method="POST" action="../../controlador/ControladorTecnico.php">
              <input type="hidden" name="accion"     value="cancelar">
              <input type="hidden" name="reserva_id" value="<?= $s['id'] ?>">
              <button class="btn-danger">✗ Rechazar</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- ═══ SOLICITUDES ═══ -->
  <div class="section" id="tab-solicitudes">
    <div class="card">
      <div class="card-header">
        <span class="card-title">Todas las solicitudes</span>
      </div>
      <div class="card-body">
        <?php if (empty($solicitudes)): ?>
          <div class="empty"><div class="empty-icon">📭</div><p>Aún no tienes solicitudes.</p></div>
        <?php else: ?>
          <?php foreach ($solicitudes as $s): ?>
          <div class="solicitud-item">
            <div class="sol-avatar">
              <?php if($s['cliente_foto']): ?><img src="<?= htmlspecialchars($s['cliente_foto']) ?>" alt=""><?php else: ?><?= strtoupper(substr($s['cliente'], 0, 1)) ?><?php endif; ?>
            </div>
            <div class="sol-info">
              <div class="sol-cliente"><?= htmlspecialchars($s['cliente']) ?></div>
              <div class="sol-servicio"><?= htmlspecialchars($s['servicio']) ?></div>
              <div class="sol-meta">📅 <?= $s['fecha'] ?> · 🕐 <?= $s['hora'] ?> · 📍 <?= htmlspecialchars($s['direccion']) ?></div>
              <?php if($s['notas']): ?><div class="sol-meta" style="margin-top:.3rem;font-style:italic;">"<?= htmlspecialchars($s['notas']) ?>"</div><?php endif; ?>
            </div>
            <div class="sol-actions">
              <span class="tag tag-<?= $s['estado'] ?>"><?= ucfirst($s['estado']) ?></span>
              <?php if ($s['estado'] === 'pendiente'): ?>
                <form method="POST" action="../../controlador/ControladorTecnico.php">
                  <input type="hidden" name="accion"     value="aceptar">
                  <input type="hidden" name="reserva_id" value="<?= $s['id'] ?>">
                  <button class="btn-success">✅ Aceptar</button>
                </form>
                <form method="POST" action="../../controlador/ControladorTecnico.php">
                  <input type="hidden" name="accion"     value="cancelar">
                  <input type="hidden" name="reserva_id" value="<?= $s['id'] ?>">
                  <button class="btn-danger">✗ Rechazar</button>
                </form>
              <?php elseif (in_array($s['estado'], ['aceptada', 'en_progreso'])): ?>
                <form method="POST" action="../../controlador/ControladorTecnico.php">
                  <input type="hidden" name="accion"     value="completar">
                  <input type="hidden" name="reserva_id" value="<?= $s['id'] ?>">
                  <button class="btn-primary">✔ Completar</button>
                </form>
                <a href="../chat.php?reserva=<?= $s['id'] ?>" class="btn-secondary" style="margin-left:.5rem;">💬 Chat</a>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ═══ SERVICIOS ═══ -->
  <div class="section" id="tab-servicios">
    <div class="card">
      <div class="card-header">
        <span class="card-title">Mis servicios</span>
        <button class="btn-primary" onclick="toggleForm()">➕ Nuevo servicio</button>
      </div>

      <!-- FORM NUEVO SERVICIO -->
      <div id="form-servicio" style="display:none; padding:1.4rem 1.6rem; border-bottom:1px solid rgba(72,191,132,.08);">
        <form method="POST" action="../../controlador/ControladorTecnico.php">
          <input type="hidden" name="accion" value="crear_servicio">
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Categoría</label>
              <select name="categoria_id" class="form-select" required>
                <option value="">Selecciona...</option>
                <?php
                $cats = $modeloTecnico->obtenerCategorias();
                foreach ($cats as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= $c['icono'] ?> <?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Título del servicio</label>
              <input type="text" name="titulo" class="form-input" placeholder="Ej: Instalación eléctrica residencial" required>
            </div>
            <div class="form-group">
              <label class="form-label">Precio</label>
              <input type="number" name="precio" class="form-input" placeholder="0" min="0" step="100" required>
            </div>
            <div class="form-group">
              <label class="form-label">Tipo de precio</label>
              <select name="precio_tipo" class="form-select">
                <option value="fijo">Precio fijo</option>
                <option value="por_hora">Por hora</option>
              </select>
            </div>
            <div class="form-group full">
              <label class="form-label">Descripción</label>
              <textarea name="descripcion" class="form-textarea" rows="3" placeholder="Describe qué incluye el servicio..."></textarea>
            </div>
          </div>
          <div style="display:flex;gap:.7rem;margin-top:1rem;">
            <button type="submit" class="btn-primary">💾 Guardar servicio</button>
            <button type="button" class="btn-secondary" onclick="toggleForm()">Cancelar</button>
          </div>
        </form>
      </div>

      <div class="card-body">
        <?php if (empty($servicios)): ?>
          <div class="empty"><div class="empty-icon">🔧</div><p>Aún no tienes servicios. ¡Crea el primero!</p></div>
        <?php else: ?>
          <div class="servicios-grid">
            <?php foreach ($servicios as $sv): ?>
            <div class="servicio-card">
              <div class="servicio-top">
                <span class="servicio-cat"><?= $sv['icono'] ?> <?= htmlspecialchars($sv['categoria']) ?></span>
                <span class="servicio-badge <?= $sv['disponible'] ? 'badge-on' : 'badge-off' ?>"><?= $sv['disponible'] ? 'Activo' : 'Pausado' ?></span>
              </div>
              <div class="servicio-titulo"><?= htmlspecialchars($sv['titulo']) ?></div>
              <div class="servicio-desc"><?= htmlspecialchars($sv['descripcion']) ?></div>
              <div class="servicio-footer">
                <div>
                  <div class="servicio-precio">$<?= number_format($sv['precio'], 0, ',', '.') ?></div>
                  <div class="servicio-tipo"><?= $sv['precio_tipo'] === 'por_hora' ? 'por hora' : 'precio fijo' ?></div>
                </div>
              </div>
              <div class="servicio-actions">
                <form method="POST" action="../../controlador/ControladorTecnico.php" style="display:inline;">
                  <input type="hidden" name="accion"      value="toggle_servicio">
                  <input type="hidden" name="servicio_id" value="<?= $sv['id'] ?>">
                  <input type="hidden" name="disponible"  value="<?= $sv['disponible'] ? 0 : 1 ?>">
                  <button class="btn-secondary"><?= $sv['disponible'] ? '⏸ Pausar' : '▶ Activar' ?></button>
                </form>
                <form method="POST" action="../../controlador/ControladorTecnico.php" style="display:inline;" onsubmit="return confirm('¿Eliminar este servicio?')">
                  <input type="hidden" name="accion"      value="eliminar_servicio">
                  <input type="hidden" name="servicio_id" value="<?= $sv['id'] ?>">
                  <button class="btn-danger">🗑</button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ═══ DISPONIBILIDAD ═══ -->
  <div class="section" id="tab-disponibilidad">
    <div class="card">
      <div class="card-header">
        <span class="card-title">Mi disponibilidad semanal</span>
      </div>
      <div class="card-body">
        <form method="POST" action="../../controlador/ControladorTecnico.php">
          <input type="hidden" name="accion" value="guardar_disponibilidad">
          <?php
          $diasSemana = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
          $dispActual = [];
          foreach ($modeloTecnico->obtenerDisponibilidad($id) as $d) {
              $dispActual[$d['dia_semana']] = $d;
          }
          ?>
          <div class="dias-grid">
            <?php foreach ($diasSemana as $dia): ?>
            <?php $tiene = isset($dispActual[$dia]); ?>
            <div class="dia-card">
              <label class="dia-check">
                <input type="checkbox" name="dias[<?= $dia ?>][activo]" value="1" <?= $tiene ? 'checked' : '' ?> onchange="toggleDia('<?= $dia ?>')">
                <span class="dia-nombre"><?= ucfirst($dia) ?></span>
              </label>
              <div class="dia-horas" id="horas-<?= $dia ?>" style="<?= $tiene ? '' : 'opacity:.35;pointer-events:none' ?>">
                <input type="time" name="dias[<?= $dia ?>][inicio]" class="form-input" value="<?= $dispActual[$dia]['hora_inicio'] ?? '08:00' ?>" style="padding:.5rem;">
                <span style="font-size:.8rem;color:var(--text-muted);">a</span>
                <input type="time" name="dias[<?= $dia ?>][fin]" class="form-input" value="<?= $dispActual[$dia]['hora_fin'] ?? '18:00' ?>" style="padding:.5rem;">
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div style="margin-top:1.5rem;">
            <button type="submit" class="btn-primary">💾 Guardar disponibilidad</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ═══ GANANCIAS ═══ -->
  <div class="section" id="tab-ganancias">
    <div class="stats-row">
      <div class="stat-card">
        <span class="stat-icon">💰</span>
        <span class="stat-value">$<?= number_format($ganancias['total_ganado'] ?? 0, 0, ',', '.') ?></span>
        <span class="stat-label">Total ganado</span>
      </div>
      <div class="stat-card">
        <span class="stat-icon">🔧</span>
        <span class="stat-value"><?= $ganancias['total_servicios'] ?? 0 ?></span>
        <span class="stat-label">Servicios completados</span>
      </div>
      <div class="stat-card">
        <span class="stat-icon">📊</span>
        <span class="stat-value">$<?= number_format($ganancias['promedio_servicio'] ?? 0, 0, ',', '.') ?></span>
        <span class="stat-label">Promedio por servicio</span>
      </div>
      <div class="stat-card">
        <span class="stat-icon">🏆</span>
        <span class="stat-value">$<?= number_format($ganancias['mayor_servicio'] ?? 0, 0, ',', '.') ?></span>
        <span class="stat-label">Mejor servicio</span>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <span class="card-title">Historial de pagos</span>
        <a href="billetera.php" class="btn-primary">💸 Ir a mi Billetera</a>
      </div>
      <div class="card-body">
        <?php $historial = $modeloTecnico->obtenerHistorialGanancias($id); ?>
        <?php if (empty($historial)): ?>
          <div class="empty"><div class="empty-icon">💸</div><p>Aún no tienes ganancias registradas.</p></div>
        <?php else: ?>
          <?php foreach ($historial as $g): ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:.8rem 0;border-bottom:1px solid rgba(72,191,132,.07);">
            <div>
              <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:.88rem;color:var(--green-dark);"><?= htmlspecialchars($g['servicio']) ?></div>
              <div style="font-size:.78rem;color:var(--text-muted);margin-top:.1rem;">👤 <?= htmlspecialchars($g['cliente']) ?> · <?= $g['fecha'] ?></div>
            </div>
            <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;color:var(--green-sea);">+$<?= number_format($g['monto'], 0, ',', '.') ?></div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ═══ RESEÑAS ═══ -->
  <div class="section" id="tab-resenas">
    <div class="card">
      <div class="card-header">
        <span class="card-title">Reseñas de clientes</span>
        <div style="display:flex;align-items:center;gap:.5rem;">
          <span style="font-size:1.1rem;">⭐</span>
          <span style="font-family:'Syne',sans-serif;font-weight:800;color:var(--green-dark);"><?= $calificacion ?: '—' ?></span>
          <span style="font-size:.78rem;color:var(--text-muted);">(<?= count($resenas) ?> reseñas)</span>
        </div>
      </div>
      <div class="card-body">
        <?php if (empty($resenas)): ?>
          <div class="empty"><div class="empty-icon">⭐</div><p>Aún no tienes reseñas. ¡Completa servicios para recibirlas!</p></div>
        <?php else: ?>
          <?php foreach ($resenas as $r): ?>
          <div class="resena-item">
            <div class="resena-header">
              <div style="width:32px;height:32px;border-radius:50%;background:var(--green-light);display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:.8rem;overflow:hidden;">
                <?php if($r['cliente_foto']): ?><img src="<?= htmlspecialchars($r['cliente_foto']) ?>" style="width:100%;height:100%;object-fit:cover;"><?php else: ?><?= strtoupper(substr($r['cliente'], 0, 1)) ?><?php endif; ?>
              </div>
              <div>
                <div class="resena-cliente"><?= htmlspecialchars($r['cliente']) ?></div>
                <div class="resena-servicio"><?= htmlspecialchars($r['servicio']) ?></div>
              </div>
              <div class="resena-stars"><?= str_repeat('★', $r['calificacion']) ?><?= str_repeat('☆', 5 - $r['calificacion']) ?></div>
            </div>
            <?php if($r['comentario']): ?>
              <div class="resena-texto">"<?= htmlspecialchars($r['comentario']) ?>"</div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ═══ MEMBRESÍA ═══ -->
  <div class="section" id="tab-membresia">
    <div class="card">
      <div class="card-header">
        <span class="card-title">Membresía Premium</span>
      </div>
      <div class="card-body" style="text-align:center; padding: 3rem 1rem;">
        <?php if (!empty($esPremium)): ?>
          <div style="font-size: 4rem; margin-bottom: 1rem; color: #3b82f6;">
            <svg xmlns="http://www.w3.org/2000/svg" width="120px" height="120px" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="0" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4" stroke="white" stroke-width="2"></path></svg>
            <?php if (!empty($esExperto)): ?>
              <span style="display:inline-block; vertical-align:middle; margin-left: 10px;">
                <img src="<?= BASE_URL ?>/img/experto.png" style="width:120px; height:120px; object-fit:contain;" alt="Experto">
              </span>
            <?php endif; ?>
          </div>
          <h2 style="font-family:'Syne',sans-serif; color:var(--green-dark); margin-bottom: 1rem;">
            ¡Eres Técnico Certificado<?= !empty($esExperto) ? ' y Experto' : '' ?>!
          </h2>
          <p style="color:var(--text-muted); max-width: 600px; margin: 0 auto;">
            Tu membresía premium está activa. Los clientes ven tu perfil destacado y tu insignia de confianza, lo que te ayuda a recibir más solicitudes.
            <?php if (!empty($esExperto)): ?>
              <br><br><strong style="color:#b45309;">🌟 Además, has sido certificado como Experto por nuestro equipo gracias a tu excelente servicio.</strong>
            <?php endif; ?>
          </p>
        <?php else: ?>
          <div style="font-size: 3rem; margin-bottom: 1rem;">💎</div>
          <h2 style="font-family:'Syne',sans-serif; color:var(--green-dark); margin-bottom: 1rem;">Destaca entre los demás</h2>
          <p style="color:var(--text-muted); margin-bottom: 2rem; max-width: 600px; margin-left:auto; margin-right:auto;">
            Al obtener la Membresía Premium recibirás una insignia de <strong style="color:var(--green-sea);">Técnico Certificado</strong> <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#3b82f6" stroke="#3b82f6" stroke-width="0" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4" stroke="white" stroke-width="2"></path></svg> junto a tu nombre. Esto genera mayor confianza en los clientes y te ayuda a conseguir más solicitudes de servicio.
          </p>
          <?php if (isset($_GET['error']) && $_GET['error'] === 'saldo_insuficiente'): ?>
            <div style="background: rgba(220, 38, 38, 0.1); color: #dc2626; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; max-width: 500px; margin-left: auto; margin-right: auto; font-weight: 600;">
              ⚠️ Saldo insuficiente. Necesitas al menos $49.000 de saldo disponible.
            </div>
            <script>
              document.addEventListener("DOMContentLoaded", function() {
                  showTab('membresia');
              });
            </script>
          <?php endif; ?>
          <form method="POST" action="../../controlador/ControladorTecnico.php" onsubmit="return confirm('¿Confirmas la compra de la membresía premium por $49.000? Este valor se descontará de tu saldo disponible.')">
            <input type="hidden" name="accion" value="comprar_membresia">
            <button type="submit" class="btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem; background: linear-gradient(135deg, var(--green-mid), var(--green-dark));">
              Obtener Premium por $49.000 / mes
            </button>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 1rem;">
              Se descontarán $49.000 de tu saldo disponible.
            </p>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div><!-- /page -->

<script>
  function showTab(name) {
    if (typeof event !== 'undefined' && event && event.type === 'click') {
        event.preventDefault();
    }
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    const section = document.getElementById('tab-' + name);
    if (section) section.classList.add('active');
    
    const activeTab = document.querySelector(`.tab[onclick*="showTab('${name}')"]`) || 
                      document.querySelector(`.tab[onclick*='showTab("${name}")']`);
    if (activeTab) {
        activeTab.classList.add('active');
    }
    return false;
  }

  function toggleForm() {
    const f = document.getElementById('form-servicio');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
  }

  function toggleDia(dia) {
    const cb    = document.querySelector(`input[name="dias[${dia}][activo]"]`);
    const horas = document.getElementById('horas-' + dia);
    horas.style.opacity       = cb.checked ? '1'    : '0.35';
    horas.style.pointerEvents = cb.checked ? 'auto' : 'none';
  }

  function toggleInboxDropdown(e) {
    e.preventDefault();
    const drop = document.getElementById('inboxDropdown');
    drop.style.display = drop.style.display === 'none' ? 'block' : 'none';
  }

  document.addEventListener('click', function(e) {
    const container = document.getElementById('navInboxContainer');
    if (container && !container.contains(e.target)) {
      document.getElementById('inboxDropdown').style.display = 'none';
    }
  });

  async function checkUnread() {
      try {
          const res = await fetch('../../controlador/ControladorChat.php?accion=obtener_inbox');
          const data = await res.json();
          const badge = document.getElementById('navUnreadBadge');
          const list = document.getElementById('inboxDropdownList');
          
          if (data.status === 'ok') {
              if (badge) {
                  if (data.count > 0) {
                      badge.textContent = data.count > 99 ? '99+' : data.count;
                      badge.style.display = 'flex';
                  } else {
                      badge.style.display = 'none';
                  }
              }

              if (list) {
                  if (data.data.length === 0) {
                      list.innerHTML = '<div style="padding:2rem 1rem; text-align:center; color:var(--text-muted); font-size:.85rem;">No tienes chats activos.</div>';
                  } else {
                      let html = '';
                      data.data.forEach(c => {
                          const initial = c.contraparte.charAt(0).toUpperCase();
                          const avatar = c.contraparte_foto 
                            ? `<img src="${c.contraparte_foto}" style="width:100%;height:100%;object-fit:cover;">`
                            : initial;
                          const unreadBadge = c.no_leidos > 0 
                            ? `<span style="background:#ef4444; color:white; font-size:.65rem; padding:.1rem .4rem; border-radius:100px; font-weight:700; margin-left:auto;">${c.no_leidos}</span>` 
                            : '';
                          const textStyle = c.no_leidos > 0 ? 'font-weight:700; color:var(--text);' : '';
                          
                          html += `
                            <a href="../chat.php?reserva=${c.reserva_id}" style="display:flex; align-items:center; gap:1rem; padding:1rem 1.2rem; border-bottom:1px solid rgba(72,191,132,.08); text-decoration:none; color:inherit; transition:background .2s;">
                              <div style="width:42px; height:42px; border-radius:50%; background:var(--green-light); color:var(--green-dark); display:flex; align-items:center; justify-content:center; font-family:'Syne',sans-serif; font-weight:800; font-size:1rem; overflow:hidden; flex-shrink:0;">${avatar}</div>
                              <div style="flex:1; min-width:0;">
                                <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:.1rem;">
                                  <span style="font-family:'Syne',sans-serif; font-weight:700; font-size:.9rem; color:var(--green-dark); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${c.contraparte}</span>
                                  <span style="font-size:.7rem; color:var(--text-muted);">${c.hora}</span>
                                </div>
                                <div style="font-size:.75rem; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; ${textStyle}">${c.ultimo_mensaje}</div>
                              </div>
                              ${unreadBadge}
                            </a>
                          `;
                      });
                      list.innerHTML = html;
                  }
              }
          }
      } catch(e) {}
  }
  checkUnread();
  setInterval(checkUnread, 5000); // 5 seconds
</script>
</body>
</html>

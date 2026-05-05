<?php
// Vista Dashboard Admin — ReparaTech
// Variables provistas por ControladorAdmin.php
// $totalUsuarios, $totalTecnicos, $totalReservas, $totalCategorias
// $reservasPorMes, $reservasPorEstado, $topTecnicos, $reservasPorCategoria

$nombre  = htmlspecialchars($_SESSION['nombre'] ?? 'Administrador');
$foto    = !empty($_SESSION['foto']) ? htmlspecialchars($_SESSION['foto']) : '';
$inicial = strtoupper(substr($nombre, 0, 1));

// Datos de ejemplo si no vienen del controlador (para pruebas)
$totalUsuarios   = $totalUsuarios   ?? 248;
$totalTecnicos   = $totalTecnicos   ?? 87;
$totalReservas   = $totalReservas   ?? 1342;
$totalCategorias = $totalCategorias ?? 8;

$reservasPorMes = $reservasPorMes ?? [
    ['mes'=>'Ene','total'=>82],  ['mes'=>'Feb','total'=>97],
    ['mes'=>'Mar','total'=>115], ['mes'=>'Abr','total'=>134],
    ['mes'=>'May','total'=>128], ['mes'=>'Jun','total'=>156],
    ['mes'=>'Jul','total'=>143], ['mes'=>'Ago','total'=>189],
    ['mes'=>'Sep','total'=>172], ['mes'=>'Oct','total'=>204],
    ['mes'=>'Nov','total'=>198], ['mes'=>'Dic','total'=>221],
];

$reservasPorEstado = $reservasPorEstado ?? [
    ['estado'=>'Completada', 'total'=>789,  'color'=>'#61D095'],
    ['estado'=>'Pendiente',  'total'=>234,  'color'=>'#fbbf24'],
    ['estado'=>'Aceptada',   'total'=>187,  'color'=>'#48BF84'],
    ['estado'=>'Cancelada',  'total'=>132,  'color'=>'#f87171'],
];

$topTecnicos = $topTecnicos ?? [
    ['nombre'=>'Carlos Mendoza', 'servicios'=>127, 'rating'=>4.9],
    ['nombre'=>'Ana Quintero',   'servicios'=>95,  'rating'=>4.9],
    ['nombre'=>'María Torres',   'servicios'=>112, 'rating'=>4.8],
    ['nombre'=>'Laura Pineda',   'servicios'=>89,  'rating'=>4.8],
    ['nombre'=>'Pedro Herrera',  'servicios'=>55,  'rating'=>4.7],
];

$reservasPorCategoria = $reservasPorCategoria ?? [
    ['nombre'=>'Refrigeración', 'total'=>312, 'icono'=>'❄️'],
    ['nombre'=>'Electricidad',  'total'=>287, 'icono'=>'⚡'],
    ['nombre'=>'Plomería',      'total'=>198, 'icono'=>'🪠'],
    ['nombre'=>'Pintura',       'total'=>175, 'icono'=>'🎨'],
    ['nombre'=>'Cerrajería',    'total'=>143, 'icono'=>'🔒'],
    ['nombre'=>'Tecnología',    'total'=>127, 'icono'=>'💻'],
    ['nombre'=>'Cocción',       'total'=>100, 'icono'=>'🍳'],
];

$maxMes = max(array_column($reservasPorMes, 'total'));
$totalEstados = array_sum(array_column($reservasPorEstado, 'total'));
$maxCategoria = max(array_column($reservasPorCategoria, 'total'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,700&display=swap" rel="stylesheet">
  <style>
    :root {
      --pink:#E0BAD7; --green-light:#61D095; --green-mid:#48BF84;
      --green-sea:#439775; --green-dark:#2A4747;
      --white:#FAFAF8; --off-white:#F2F0EC;
      --text:#1a2a2a; --text-muted:#4a6a6a;
    }
    *,*::before,*::after { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'DM Sans',sans-serif; background:var(--off-white); color:var(--text); display:flex; min-height:100vh; }

    /* ===== SIDEBAR ===== */
    .sidebar { width:240px; background:var(--white); border-right:1px solid rgba(72,191,132,.12); display:flex; flex-direction:column; padding:1.8rem 0; position:fixed; height:100vh; z-index:100; box-shadow:4px 0 30px rgba(42,71,71,.05); }
    .logo { font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:800; color:var(--green-dark); text-decoration:none; padding:0 1.5rem; margin-bottom:2rem; display:block; }
    .logo span { color:var(--green-light); }
    .sidebar-label { font-size:.65rem; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:var(--text-muted); padding:0 1.5rem; margin-bottom:.5rem; margin-top:1rem; }
    .nav-links { display:flex; flex-direction:column; gap:.25rem; padding:0 .8rem; flex:1; }
    .nav-link { display:flex; align-items:center; gap:.8rem; padding:.75rem 1rem; text-decoration:none; color:var(--text-muted); font-weight:500; font-size:.88rem; border-radius:12px; transition:all .2s; }
    .nav-link:hover { background:rgba(97,208,149,.1); color:var(--green-dark); }
    .nav-link.active { background:var(--green-dark); color:var(--white); font-weight:700; }
    .sidebar-footer { padding:1rem 1.5rem; border-top:1px solid rgba(72,191,132,.1); margin-top:.5rem; }
    .btn-logout { display:flex; align-items:center; gap:.7rem; color:#dc2626; text-decoration:none; font-weight:600; font-size:.88rem; padding:.6rem 0; transition:opacity .2s; }
    .btn-logout:hover { opacity:.7; }

    /* ===== MAIN ===== */
    .main-content { flex:1; margin-left:240px; padding:2rem 2.5rem; min-height:100vh; }

    /* ===== HEADER ===== */
    .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; }
    .top-bar h1 { font-family:'Syne',sans-serif; font-size:1.7rem; font-weight:800; color:var(--green-dark); letter-spacing:-1px; }
    .top-bar p { color:var(--text-muted); font-size:.88rem; margin-top:.2rem; }
    .admin-pill { display:flex; align-items:center; gap:.8rem; background:var(--white); padding:.5rem 1rem .5rem .5rem; border-radius:100px; border:1px solid rgba(72,191,132,.15); box-shadow:0 4px 16px rgba(42,71,71,.05); }
    .admin-avatar { width:34px; height:34px; border-radius:50%; background:var(--green-dark); color:var(--white); display:flex; align-items:center; justify-content:center; font-family:'Syne',sans-serif; font-weight:700; font-size:.82rem; overflow:hidden; flex-shrink:0; }
    .admin-avatar img { width:100%; height:100%; object-fit:cover; }
    .admin-name { font-weight:700; font-size:.82rem; color:var(--green-dark); }
    .admin-role { font-size:.68rem; color:var(--green-sea); font-weight:600; text-transform:uppercase; letter-spacing:.8px; }

    /* ===== STATS ===== */
    .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1.2rem; margin-bottom:1.8rem; }
    .stat-card { background:var(--white); border-radius:20px; padding:1.5rem; border:1px solid rgba(72,191,132,.1); box-shadow:0 4px 20px rgba(42,71,71,.05); position:relative; overflow:hidden; transition:transform .2s; }
    .stat-card:hover { transform:translateY(-3px); }
    .stat-card::before { content:''; position:absolute; top:-20px; right:-20px; width:90px; height:90px; border-radius:50%; opacity:.08; }
    .stat-card.blue::before   { background:#3b82f6; }
    .stat-card.green::before  { background:var(--green-light); }
    .stat-card.purple::before { background:#a855f7; }
    .stat-card.orange::before { background:#f97316; }
    .stat-icon { font-size:1.5rem; margin-bottom:.8rem; display:block; }
    .stat-val { font-family:'Syne',sans-serif; font-size:2rem; font-weight:800; color:var(--text); line-height:1; display:block; margin-bottom:.2rem; }
    .stat-lbl { font-size:.78rem; color:var(--text-muted); font-weight:400; }
    .stat-trend { font-size:.72rem; font-weight:700; margin-top:.5rem; display:inline-block; padding:.18rem .55rem; border-radius:100px; }
    .trend-up   { background:rgba(97,208,149,.15); color:var(--green-sea); }
    .trend-down { background:rgba(248,113,113,.12); color:#dc2626; }

    /* ===== GRID LAYOUTS ===== */
    .row { display:grid; gap:1.2rem; margin-bottom:1.2rem; }
    .row-2-1 { grid-template-columns:2fr 1fr; }
    .row-3   { grid-template-columns:1fr 1fr 1fr; }
    .row-2   { grid-template-columns:1fr 1fr; }

    /* ===== CHART CARD ===== */
    .chart-card { background:var(--white); border-radius:20px; padding:1.5rem; border:1px solid rgba(72,191,132,.1); box-shadow:0 4px 20px rgba(42,71,71,.05); }
    .chart-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.4rem; }
    .chart-title { font-family:'Syne',sans-serif; font-size:.95rem; font-weight:700; color:var(--green-dark); }
    .chart-subtitle { font-size:.75rem; color:var(--text-muted); font-weight:300; margin-top:.15rem; }
    .chart-badge { background:rgba(97,208,149,.1); color:var(--green-sea); font-size:.72rem; font-weight:700; padding:.25rem .7rem; border-radius:100px; }

    /* ===== BAR CHART (Reservas por mes) ===== */
    .bar-chart { display:flex; align-items:flex-end; gap:.5rem; height:160px; }
    .bar-wrap { flex:1; display:flex; flex-direction:column; align-items:center; gap:.4rem; height:100%; justify-content:flex-end; }
    .bar { width:100%; border-radius:6px 6px 0 0; transition:opacity .2s; min-height:4px; position:relative; cursor:pointer; }
    .bar:hover { opacity:.8; }
    .bar-tooltip { position:absolute; top:-28px; left:50%; transform:translateX(-50%); background:var(--green-dark); color:var(--white); font-size:.65rem; font-weight:700; padding:.2rem .5rem; border-radius:6px; white-space:nowrap; opacity:0; transition:opacity .2s; pointer-events:none; }
    .bar:hover .bar-tooltip { opacity:1; }
    .bar-lbl { font-size:.62rem; color:var(--text-muted); font-weight:500; white-space:nowrap; }

    /* ===== DONUT CHART ===== */
    .donut-wrap { display:flex; align-items:center; gap:1.5rem; }
    .donut-svg { flex-shrink:0; }
    .donut-legend { display:flex; flex-direction:column; gap:.6rem; flex:1; }
    .legend-item { display:flex; align-items:center; gap:.6rem; }
    .legend-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
    .legend-label { font-size:.78rem; color:var(--text-muted); flex:1; }
    .legend-val { font-family:'Syne',sans-serif; font-size:.82rem; font-weight:700; color:var(--text); }
    .legend-pct { font-size:.7rem; color:var(--text-muted); }

    /* ===== HORIZONTAL BAR (Categorías) ===== */
    .hbar-list { display:flex; flex-direction:column; gap:.9rem; }
    .hbar-item { display:flex; flex-direction:column; gap:.35rem; }
    .hbar-info { display:flex; justify-content:space-between; align-items:center; }
    .hbar-name { font-size:.82rem; font-weight:500; color:var(--text); display:flex; align-items:center; gap:.4rem; }
    .hbar-val  { font-family:'Syne',sans-serif; font-size:.82rem; font-weight:700; color:var(--green-dark); }
    .hbar-track { height:7px; background:var(--off-white); border-radius:100px; overflow:hidden; }
    .hbar-fill  { height:100%; border-radius:100px; transition:width 1s ease; }

    /* ===== TOP TÉCNICOS ===== */
    .tecnico-row { display:flex; align-items:center; gap:.9rem; padding:.7rem 0; border-bottom:1px solid rgba(72,191,132,.07); }
    .tecnico-row:last-child { border-bottom:none; }
    .tecnico-rank { font-family:'Syne',sans-serif; font-weight:800; font-size:.88rem; color:var(--text-muted); width:20px; text-align:center; flex-shrink:0; }
    .tecnico-rank.top1 { color:var(--green-light); }
    .tecnico-avatar { width:34px; height:34px; border-radius:50%; background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:800; font-size:.78rem; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .tecnico-info { flex:1; min-width:0; }
    .tecnico-nombre { font-weight:600; font-size:.85rem; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .tecnico-servicios { font-size:.72rem; color:var(--text-muted); font-weight:300; }
    .tecnico-rating { font-size:.8rem; font-weight:700; color:#fbbf24; white-space:nowrap; }

    /* ===== ACTIVIDAD RECIENTE ===== */
    .actividad-item { display:flex; align-items:flex-start; gap:.8rem; padding:.65rem 0; border-bottom:1px solid rgba(72,191,132,.06); }
    .actividad-item:last-child { border-bottom:none; }
    .act-dot { width:9px; height:9px; border-radius:50%; margin-top:.3rem; flex-shrink:0; }
    .act-text { font-size:.82rem; color:var(--text); font-weight:400; line-height:1.4; flex:1; }
    .act-time { font-size:.7rem; color:var(--text-muted); white-space:nowrap; }

    /* ===== ANIMATIONS ===== */
    @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
    .stat-card { animation:fadeUp .5s ease both; }
    .stat-card:nth-child(1){animation-delay:.05s}
    .stat-card:nth-child(2){animation-delay:.10s}
    .stat-card:nth-child(3){animation-delay:.15s}
    .stat-card:nth-child(4){animation-delay:.20s}

    @media(max-width:1100px) {
      .stats-grid { grid-template-columns:repeat(2,1fr); }
      .row-2-1,.row-3 { grid-template-columns:1fr; }
    }
    @media(max-width:700px) {
      .sidebar { display:none; }
      .main-content { margin-left:0; padding:1.2rem; }
      .stats-grid { grid-template-columns:1fr 1fr; }
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <a href="#" class="logo">Repara<span>Tech</span></a>
  <div class="sidebar-label">Principal</div>
  <div class="nav-links">
    <a href="admin.php?accion=dashboard"   class="nav-link active">📊 Dashboard</a>
    <a href="admin.php?accion=usuarios"    class="nav-link">👥 Usuarios</a>
    <a href="admin.php?accion=tecnicos"    class="nav-link">🔧 Técnicos</a>
    <a href="admin.php?accion=reservas"    class="nav-link">📅 Reservas</a>
    <a href="admin.php?accion=categorias"  class="nav-link">📁 Categorías</a>
    <a href="admin.php?accion=retiros"     class="nav-link">💰 Pagos</a>
    <a href="admin.php?accion=disputas"    class="nav-link">⚠️ Disputas</a>

  </div>
  <div class="sidebar-footer">
    <a href="index.php?accion=logout" class="btn-logout">🚪 Cerrar sesión</a>
  </div>
</aside>

<!-- MAIN -->
<main class="main-content">

  <!-- HEADER -->
  <div class="top-bar">
    <div>
      <h1>Dashboard</h1>
      <p>Centro de control — <?= date('d M Y') ?></p>
    </div>
    <div class="admin-pill">
      <div class="admin-avatar">
        <?php if($foto): ?><img src="<?= $foto ?>" alt=""><?php else: ?><?= $inicial ?><?php endif; ?>
      </div>
      <div>
        <div class="admin-name"><?= $nombre ?></div>
        <div class="admin-role">Super Admin</div>
      </div>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats-grid">
    <div class="stat-card blue">
      <span class="stat-icon">👥</span>
      <span class="stat-val"><?= number_format($totalUsuarios) ?></span>
      <span class="stat-lbl">Clientes registrados</span>
      <span class="stat-trend trend-up">↑ +12% este mes</span>
    </div>
    <div class="stat-card green">
      <span class="stat-icon">🔧</span>
      <span class="stat-val"><?= number_format($totalTecnicos) ?></span>
      <span class="stat-lbl">Técnicos activos</span>
      <span class="stat-trend trend-up">↑ +8% este mes</span>
    </div>
    <div class="stat-card purple">
      <span class="stat-icon">📅</span>
      <span class="stat-val"><?= number_format($totalReservas) ?></span>
      <span class="stat-lbl">Reservas totales</span>
      <span class="stat-trend trend-up">↑ +23% este mes</span>
    </div>
    <div class="stat-card orange">
      <span class="stat-icon">⭐</span>
      <span class="stat-val">4.8</span>
      <span class="stat-lbl">Calificación promedio</span>
      <span class="stat-trend trend-up">↑ Estable</span>
    </div>
  </div>

  <!-- ROW 1: Barras por mes + Donut estados -->
  <div class="row row-2-1">

    <!-- GRÁFICA DE BARRAS: Reservas por mes -->
    <div class="chart-card">
      <div class="chart-header">
        <div>
          <div class="chart-title">📈 Reservas por mes</div>
          <div class="chart-subtitle">Evolución anual de solicitudes de servicio</div>
        </div>
        <span class="chart-badge"><?= date('Y') ?></span>
      </div>
      <div class="bar-chart">
        <?php foreach ($reservasPorMes as $i => $m):
          $h = round(($m['total'] / $maxMes) * 140);
          $color = $i === count($reservasPorMes) - 1 ? 'var(--green-light)' : 'rgba(97,208,149,.4)';
        ?>
        <div class="bar-wrap">
          <div class="bar" style="height:<?= $h ?>px;background:<?= $color ?>">
            <div class="bar-tooltip"><?= $m['total'] ?></div>
          </div>
          <div class="bar-lbl"><?= $m['mes'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- DONUT: Estados de reservas -->
    <div class="chart-card">
      <div class="chart-header">
        <div>
          <div class="chart-title">🍩 Por estado</div>
          <div class="chart-subtitle">Distribución actual</div>
        </div>
      </div>
      <div class="donut-wrap">
        <svg class="donut-svg" width="110" height="110" viewBox="0 0 110 110">
          <?php
          $cx = 55; $cy = 55; $r = 42; $stroke = 18;
          $circ = 2 * M_PI * $r;
          $offset = 0;
          foreach ($reservasPorEstado as $e):
            $pct   = $e['total'] / $totalEstados;
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
          <text x="55" y="50" text-anchor="middle" font-family="Syne,sans-serif" font-size="14" font-weight="800" fill="#2A4747"><?= $totalReservas ?></text>
          <text x="55" y="63" text-anchor="middle" font-family="DM Sans,sans-serif" font-size="7" fill="#4a6a6a">total</text>
        </svg>
        <div class="donut-legend">
          <?php foreach ($reservasPorEstado as $e):
            $pct = round($e['total'] / $totalEstados * 100);
          ?>
          <div class="legend-item">
            <div class="legend-dot" style="background:<?= $e['color'] ?>"></div>
            <span class="legend-label"><?= $e['estado'] ?></span>
            <span class="legend-val"><?= $e['total'] ?></span>
            <span class="legend-pct"><?= $pct ?>%</span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- ROW 2: Categorías + Top Técnicos + Actividad -->
  <div class="row row-3">

    <!-- BARRAS HORIZONTALES: Categorías -->
    <div class="chart-card">
      <div class="chart-header">
        <div>
          <div class="chart-title">📁 Por categoría</div>
          <div class="chart-subtitle">Servicios más demandados</div>
        </div>
      </div>
      <div class="hbar-list">
        <?php foreach ($reservasPorCategoria as $i => $cat):
          $pct = round($cat['total'] / $maxCategoria * 100);
          $colors = ['#61D095','#48BF84','#439775','#fbbf24','#f87171','#a78bfa','#60a5fa'];
          $color  = $colors[$i % count($colors)];
        ?>
        <div class="hbar-item">
          <div class="hbar-info">
            <span class="hbar-name"><?= $cat['icono'] ?> <?= htmlspecialchars($cat['nombre']) ?></span>
            <span class="hbar-val"><?= $cat['total'] ?></span>
          </div>
          <div class="hbar-track">
            <div class="hbar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- TOP TÉCNICOS -->
    <div class="chart-card">
      <div class="chart-header">
        <div>
          <div class="chart-title">🏆 Top técnicos</div>
          <div class="chart-subtitle">Por servicios completados</div>
        </div>
      </div>
      <?php foreach ($topTecnicos as $i => $t): ?>
      <div class="tecnico-row">
        <div class="tecnico-rank <?= $i === 0 ? 'top1' : '' ?>"><?= $i+1 ?></div>
        <div class="tecnico-avatar"><?= strtoupper(substr($t['nombre'], 0, 1)) ?></div>
        <div class="tecnico-info">
          <div class="tecnico-nombre"><?= htmlspecialchars($t['nombre']) ?></div>
          <div class="tecnico-servicios"><?= $t['servicios'] ?> servicios</div>
        </div>
        <div class="tecnico-rating">★ <?= $t['rating'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ACTIVIDAD RECIENTE -->
    <div class="chart-card">
      <div class="chart-header">
        <div>
          <div class="chart-title">🕐 Actividad reciente</div>
          <div class="chart-subtitle">Últimas acciones en la plataforma</div>
        </div>
      </div>
      <?php
      $actividades = $actividadesFormateadas ?? [];
      if (empty($actividades)): ?>
        <div style="padding:1rem 0; color:var(--text-muted); text-align:center; font-size:0.85rem;">
          No hay actividad reciente para mostrar.
        </div>
      <?php else: ?>
        <?php foreach ($actividades as $a): ?>
        <div class="actividad-item">
          <div class="act-dot" style="background:<?= $a['color'] ?>"></div>
          <div class="act-text"><?= $a['texto'] ?></div>
          <div class="act-time"><?= $a['tiempo'] ?></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</main>
</body>
</html>

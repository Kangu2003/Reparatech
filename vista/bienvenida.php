<?php
/**
 * bienvenida.php — Panel de usuario autenticado
 */
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
    exit();
}

$correo    = htmlspecialchars($_SESSION['usuario']);
$nombre    = htmlspecialchars($_SESSION['nombre']   ?? 'Usuario');
$ciudad    = htmlspecialchars($_SESSION['ciudad']   ?? 'Santa Marta');
$foto      = !empty($_SESSION['foto']) ? htmlspecialchars($_SESSION['foto']) : '';
$inicial   = strtoupper(substr($nombre, 0, 1));
$usuarioId = (int)($_SESSION['id'] ?? 0);

// ✅ Cargar reservas reales desde la BD
require_once __DIR__ . '/../modelo/Servicio.php';
$modeloServicio  = new Servicio();
$todasReservas   = $modeloServicio->obtenerReservasUsuario($usuarioId);

// Últimas 3 para el panel
$reservasPanel   = array_slice($todasReservas, 0, 3);

// Stats reales
$totalReservas   = count($todasReservas);
$pendientes      = count(array_filter($todasReservas, fn($r) => $r['estado'] === 'pendiente'));
$completadas     = count(array_filter($todasReservas, fn($r) => $r['estado'] === 'completada'));
$totalGastado    = array_sum(array_column(
    array_filter($todasReservas, fn($r) => $r['estado'] === 'completada'),
    'precio'
));

// Próxima cita (aceptada o pendiente más cercana)
$proxima = null;
$activas = array_filter($todasReservas, fn($r) => in_array($r['estado'], ['aceptada','pendiente']));
if (!empty($activas)) {
    usort($activas, fn($a,$b) => strtotime($a['fecha'].$a['hora']) - strtotime($b['fecha'].$b['hora']));
    $proxima = reset($activas);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi Panel — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
  <style>
    :root {
      --pink: #E0BAD7;
      --green-light: #61D095;
      --green-mid: #48BF84;
      --green-sea: #439775;
      --green-dark: #2A4747;
      --white: #FAFAF8;
      --off-white: #F2F0EC;
      --text: #1a2a2a;
      --text-muted: #4a6a6a;
    }

    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--off-white);
      color: var(--text);
      min-height: 100vh;
    }

    /* ===== NAV ===== */
    nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      display: flex; align-items: center; justify-content: space-between;
      padding: .85rem 5%;
      background: rgba(250,250,248,.94);
      backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(72,191,132,.15);
      box-shadow: 0 4px 30px rgba(42,71,71,.07);
    }

    .logo {
      font-family: 'Syne', sans-serif; font-size: 1.45rem; font-weight: 800;
      color: var(--green-dark); letter-spacing: -.5px; text-decoration: none;
    }
    .logo span { color: var(--green-light); }

    .nav-right { display: flex; align-items: center; gap: .8rem; }

    /* ✅ nav-avatar: círculo limpio, sin transform */
    .nav-avatar {
      width: 34px; height: 34px; border-radius: 50%;
      background: var(--green-light); color: var(--green-dark);
      font-family: 'Syne', sans-serif; font-weight: 800; font-size: .85rem;
      display: flex; align-items: center; justify-content: center;
      overflow: hidden; flex-shrink: 0; text-decoration: none;
    }
    .nav-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

    .nav-nombre {
      font-size: .88rem; font-weight: 500; color: var(--green-dark);
      text-decoration: none;
    }

    .btn-nav {
      background: transparent;
      border: 1.5px solid rgba(42,71,71,.2);
      color: var(--text-muted);
      font-family: 'DM Sans', sans-serif; font-size: .82rem; font-weight: 500;
      padding: .45rem 1rem; border-radius: 100px;
      cursor: pointer; text-decoration: none; transition: all .2s;
    }
    .btn-nav:hover { border-color: var(--green-dark); color: var(--green-dark); }
    .btn-nav-primary {
      background: var(--green-dark); color: var(--white) !important;
      border-color: var(--green-dark);
    }
    .btn-nav-primary:hover { background: #1a2a2a; }

    /* ===== LAYOUT ===== */
    .dashboard {
      padding: 7rem 5% 3rem; max-width: 1100px;
      margin: 0 auto; position: relative; z-index: 1;
    }

    /* ===== WELCOME HEADER ===== */
    .welcome-header {
      display: flex; align-items: center; justify-content: space-between;
      gap: 1.5rem; margin-bottom: 2.5rem; flex-wrap: wrap;
      animation: fadeUp .6s ease both;
    }
    .welcome-left h1 {
      font-family: 'Syne', sans-serif;
      font-size: clamp(1.8rem, 3.5vw, 2.6rem); font-weight: 800;
      letter-spacing: -1.5px; color: var(--green-dark); line-height: 1.1; margin-bottom: .3rem;
    }
    .welcome-left h1 em { font-style: normal; color: var(--green-mid); position: relative; }
    .welcome-left h1 em::after {
      content: ''; position: absolute; bottom: 2px; left: -3px; right: -3px;
      height: 7px; background: var(--pink); opacity: .45; z-index: -1; border-radius: 3px;
    }
    .welcome-left p { font-size: .95rem; color: var(--text-muted); font-weight: 300; }

    .status-pill {
      display: inline-flex; align-items: center; gap: .5rem;
      background: rgba(97,208,149,.12); border: 1px solid rgba(97,208,149,.3);
      color: var(--green-sea); font-size: .75rem; font-weight: 700;
      letter-spacing: 1.5px; text-transform: uppercase;
      padding: .45rem 1.1rem; border-radius: 100px;
    }
    .status-dot {
      width: 7px; height: 7px; border-radius: 50%;
      background: var(--green-light); animation: pulse 2s infinite;
    }

    /* ===== STATS ===== */
    .stats-row {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 1rem; margin-bottom: 2rem;
    }
    .stat-card {
      background: var(--white); border-radius: 20px; padding: 1.4rem 1.6rem;
      border: 1px solid rgba(72,191,132,.1); box-shadow: 0 4px 20px rgba(42,71,71,.05);
    }
    .stat-icon  { font-size: 1.4rem; margin-bottom: .5rem; display: block; }
    .stat-value {
      font-family: 'Syne', sans-serif; font-size: 1.9rem; font-weight: 800;
      color: var(--green-dark); letter-spacing: -1px; line-height: 1;
      display: block; margin-bottom: .2rem;
    }
    .stat-label { font-size: .78rem; color: var(--text-muted); font-weight: 400; }

    /* ===== MAIN GRID ===== */
    .main-grid {
      display: grid; grid-template-columns: 1fr 320px;
      gap: 1.5rem; align-items: start;
    }
    @media (max-width: 820px) { .main-grid { grid-template-columns: 1fr; } }
    .left-col, .right-col { display: flex; flex-direction: column; gap: 1.5rem; }

    /* ===== CARD ===== */
    .card {
      background: var(--white); border-radius: 24px;
      border: 1px solid rgba(72,191,132,.1);
      box-shadow: 0 4px 24px rgba(42,71,71,.06); overflow: hidden;
    }
    .card-header {
      padding: 1.3rem 1.6rem 1rem; display: flex; align-items: center;
      justify-content: space-between; border-bottom: 1px solid rgba(72,191,132,.08);
    }
    .card-title { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; color: var(--green-dark); }
    .card-badge { background: var(--off-white); color: var(--text-muted); font-size: .72rem; font-weight: 600; padding: .25rem .7rem; border-radius: 100px; }
    .card-body { padding: 1.4rem 1.6rem; }

    /* ===== RESERVAS ===== */
    .reserva-item {
      display: flex; align-items: center; gap: 1rem;
      padding: .9rem 0; border-bottom: 1px solid rgba(72,191,132,.07);
    }
    .reserva-item:last-child { border-bottom: none; }
    .reserva-icon { width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
    .reserva-info { flex: 1; min-width: 0; }
    .reserva-name { font-family: 'Syne', sans-serif; font-weight: 700; font-size: .9rem; color: var(--green-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .reserva-meta { font-size: .78rem; color: var(--text-muted); font-weight: 300; margin-top: .15rem; }
    .reserva-right { text-align: right; flex-shrink: 0; }
    .reserva-price { font-family: 'Syne', sans-serif; font-weight: 700; font-size: .9rem; color: var(--green-dark); }
    .tag { display: inline-block; font-size: .68rem; font-weight: 700; padding: .2rem .6rem; border-radius: 100px; margin-top: .2rem; }
    .tag-pending { background: rgba(251,191,36,.15); color: #b45309; }
    .tag-done    { background: rgba(97,208,149,.15); color: var(--green-sea); }
    .tag-cancel  { background: rgba(220,38,38,.08);  color: #dc2626; }

    /* ===== ACCIONES RAPIDAS ===== */
    .acciones-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
    .accion-btn {
      background: var(--off-white); border: 1px solid rgba(72,191,132,.12);
      border-radius: 16px; padding: 1rem .8rem; cursor: pointer; text-align: center;
      transition: all .22s; text-decoration: none; display: flex;
      flex-direction: column; align-items: center; gap: .4rem;
    }
    .accion-btn:hover { background: rgba(97,208,149,.08); border-color: var(--green-mid); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(97,208,149,.15); }
    .accion-emoji { font-size: 1.4rem; }
    .accion-label { font-family: 'Syne', sans-serif; font-size: .78rem; font-weight: 700; color: var(--green-dark); }

    /* ===== PERFIL CARD ===== */
    .perfil-card .card-body { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1rem; }

    /* ✅ big-avatar: usa position relative para el overlay de edición */
    .big-avatar-wrap {
      position: relative; width: 80px; height: 80px;
      border-radius: 22px; flex-shrink: 0;
    }
    .big-avatar {
      width: 80px; height: 80px; border-radius: 22px;
      background: var(--green-light); color: var(--green-dark);
      font-family: 'Syne', sans-serif; font-weight: 800; font-size: 2rem;
      display: flex; align-items: center; justify-content: center;
      /* ✅ SIN transform — overflow:hidden funciona correctamente */
      box-shadow: 0 12px 28px rgba(97,208,149,.3);
      overflow: hidden;
    }
    /* ✅ La imagen ocupa exactamente el contenedor */
    .big-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

    /* Overlay editar al hacer hover sobre el avatar */
    .big-avatar-overlay {
      position: absolute; inset: 0; border-radius: 22px;
      background: rgba(42,71,71,.55);
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      gap: .2rem; opacity: 0; transition: opacity .25s;
      text-decoration: none;
    }
    .big-avatar-wrap:hover .big-avatar-overlay { opacity: 1; }
    .big-avatar-overlay span { font-size: .65rem; font-weight: 700; color: var(--white); letter-spacing: .5px; text-transform: uppercase; }

    .perfil-nombre { font-family: 'Syne', sans-serif; font-size: 1.15rem; font-weight: 800; color: var(--green-dark); letter-spacing: -.5px; }
    .perfil-correo { font-size: .8rem; color: var(--text-muted); font-weight: 300; word-break: break-all; margin-top: .1rem; }
    .perfil-divider { width: 100%; height: 1px; background: rgba(72,191,132,.1); }
    .perfil-info-row { width: 100%; display: flex; justify-content: space-between; font-size: .82rem; padding: .15rem 0; }
    .perfil-info-row span:first-child { color: var(--text-muted); font-weight: 300; }
    .perfil-info-row span:last-child  { font-weight: 600; color: var(--green-dark); }

    .btn-primary-full {
      width: 100%; background: var(--green-dark); color: var(--white);
      font-family: 'Syne', sans-serif; font-weight: 700; font-size: .9rem;
      padding: .85rem; border-radius: 100px; border: none; cursor: pointer;
      text-decoration: none; display: block; text-align: center;
      transition: all .25s; box-shadow: 0 8px 20px rgba(42,71,71,.15);
    }
    .btn-primary-full:hover { background: #1a2a2a; transform: translateY(-2px); }

    .btn-secondary-full {
      width: 100%; background: transparent; color: var(--green-sea);
      font-family: 'Syne', sans-serif; font-weight: 600; font-size: .88rem;
      padding: .8rem; border-radius: 100px;
      border: 1.5px solid rgba(67,151,117,.25); cursor: pointer;
      text-decoration: none; display: block; text-align: center; transition: all .25s;
    }
    .btn-secondary-full:hover { background: rgba(97,208,149,.08); border-color: var(--green-mid); }

    .btn-edit-perfil {
      width: 100%; background: rgba(97,208,149,.1); color: var(--green-sea);
      font-family: 'Syne', sans-serif; font-weight: 700; font-size: .85rem;
      padding: .75rem; border-radius: 100px;
      border: 1.5px solid rgba(97,208,149,.25); cursor: pointer;
      text-decoration: none; display: block; text-align: center; transition: all .25s;
    }
    .btn-edit-perfil:hover { background: rgba(97,208,149,.18); border-color: var(--green-mid); transform: translateY(-1px); }

    /* ===== PROXIMA CITA ===== */
    .cita-box { background: rgba(97,208,149,.08); border: 1px solid rgba(97,208,149,.2); border-radius: 16px; padding: 1rem; }
    .cita-box .cita-service { font-family: 'Syne', sans-serif; font-weight: 700; color: var(--green-dark); font-size: .92rem; margin: .4rem 0 .5rem; }
    .cita-box .cita-detail { font-size: .8rem; color: var(--text-muted); font-weight: 300; line-height: 1.7; }

    /* ===== BG ===== */
    .bg-dots { position: fixed; inset: 0; background-image: radial-gradient(var(--green-mid) 1px, transparent 1px); background-size: 36px 36px; opacity: .06; pointer-events: none; z-index: 0; }

    /* ===== ANIMATIONS ===== */
    @keyframes fadeUp { from { opacity: 0; transform: translateY(22px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes pulse  { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .5; transform: scale(1.5); } }
    .anim-1 { animation: fadeUp .55s .05s ease both; }
    .anim-2 { animation: fadeUp .55s .12s ease both; }
    .anim-3 { animation: fadeUp .55s .20s ease both; }
    .anim-4 { animation: fadeUp .55s .28s ease both; }

    @media (max-width: 560px) {
      .stats-row { grid-template-columns: 1fr 1fr; }
      .welcome-header { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body>

  <div class="bg-dots"></div>

  <!-- NAV -->
  <nav>
    <a href="../index.php" class="logo">Repara<span>Tech</span></a>
    <div class="nav-right">
      <div id="navInboxContainer" style="position:relative;">
        <a href="#" class="btn-nav" id="navInboxBtn" onclick="toggleInboxDropdown(event)">
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
      <!-- ✅ Avatar clickeable va a perfil -->
      <a href="perfil.php" class="nav-avatar">
        <?php if ($foto): ?>
          <img src="<?= $foto ?>" alt="foto">
        <?php else: ?>
          <?= $inicial ?>
        <?php endif; ?>
      </a>
      <a href="perfil.php" class="nav-nombre"><?= $nombre ?></a>
      <a href="perfil.php" class="btn-nav">✏️ Editar perfil</a>
      <a href="../index.php?accion=logout" class="btn-nav">Cerrar sesión</a>
    </div>
  </nav>

  <!-- DASHBOARD -->
  <div class="dashboard">

    <!-- WELCOME HEADER -->
    <div class="welcome-header">
      <div class="welcome-left">
        <h1>Hola, <em><?= $nombre ?></em> 👋</h1>
        <p>Bienvenido a tu panel de ReparaTech</p>
      </div>
      <div class="status-pill">
        <div class="status-dot"></div>
        Sesión activa
      </div>
    </div>

    <!-- STATS REALES -->
    <div class="stats-row">
      <div class="stat-card anim-1">
        <span class="stat-icon">🔧</span>
        <span class="stat-value"><?= $totalReservas ?></span>
        <span class="stat-label">Servicios contratados</span>
      </div>
      <div class="stat-card anim-2">
        <span class="stat-icon">⏳</span>
        <span class="stat-value"><?= $pendientes ?></span>
        <span class="stat-label">Reservas pendientes</span>
      </div>
      <div class="stat-card anim-3">
        <span class="stat-icon">✅</span>
        <span class="stat-value"><?= $completadas ?></span>
        <span class="stat-label">Completadas</span>
      </div>
      <div class="stat-card anim-4">
        <span class="stat-icon">💰</span>
        <span class="stat-value">$<?= number_format($totalGastado, 0, ',', '.') ?></span>
        <span class="stat-label">Total invertido</span>
      </div>
    </div>

    <!-- MAIN GRID -->
    <div class="main-grid">

      <!-- LEFT -->
      <div class="left-col">

        <!-- MIS RESERVAS REALES -->
        <div class="card anim-3">
          <div class="card-header">
            <span class="card-title">Mis reservas</span>
            <a href="mis-reservas.php" class="card-badge" style="text-decoration:none;color:var(--green-sea);">
              <?= $totalReservas ?> en total →
            </a>
          </div>
          <div class="card-body">
            <?php if (empty($reservasPanel)): ?>
              <div style="text-align:center;padding:2rem 1rem;color:var(--text-muted);">
                <div style="font-size:2rem;margin-bottom:.6rem;">📭</div>
                <p style="font-size:.85rem;font-weight:300;">Aún no tienes reservas.</p>
                <a href="buscar.php" style="display:inline-block;margin-top:.8rem;background:var(--green-light);color:var(--green-dark);font-family:'Syne',sans-serif;font-weight:700;font-size:.8rem;padding:.6rem 1.2rem;border-radius:100px;text-decoration:none;">
                  🔍 Buscar servicios
                </a>
              </div>
            <?php else: ?>
              <?php foreach ($reservasPanel as $r):
                $tagClass = match($r['estado']) {
                  'pendiente'   => 'tag-pending',
                  'completada'  => 'tag-done',
                  'cancelada'   => 'tag-cancel',
                  'aceptada'    => 'tag-done',
                  default       => 'tag-pending'
                };
                $tagLabel = match($r['estado']) {
                  'pendiente'   => 'Pendiente',
                  'completada'  => 'Completado',
                  'cancelada'   => 'Cancelado',
                  'aceptada'    => 'Aceptada',
                  'en_progreso' => 'En progreso',
                  default       => ucfirst($r['estado'])
                };
              ?>
              <div class="reserva-item">
                <div class="reserva-icon" style="background:rgba(97,208,149,.12)"><?= $r['icono'] ?></div>
                <div class="reserva-info">
                  <div class="reserva-name"><?= htmlspecialchars($r['servicio']) ?></div>
                  <div class="reserva-meta">
                    📅 <?= date('d M', strtotime($r['fecha'])) ?>, <?= $r['hora'] ?> &nbsp;·&nbsp; <?= htmlspecialchars($r['tecnico']) ?>
                  </div>
                </div>
                <div class="reserva-right">
                  <div class="reserva-price">$<?= number_format($r['precio'], 0, ',', '.') ?></div>
                  <span class="tag <?= $tagClass ?>"><?= $tagLabel ?></span>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- ACCIONES RAPIDAS -->
        <div class="card anim-4">
          <div class="card-header">
            <span class="card-title">Acciones rápidas</span>
          </div>
          <div class="card-body">
            <div class="acciones-grid">
              <a href="buscar.php" class="accion-btn">
                <span class="accion-emoji">🔍</span>
                <span class="accion-label">Buscar servicio</span>
              </a>
              <a href="mis-reservas.php" class="accion-btn">
                <span class="accion-emoji">📋</span>
                <span class="accion-label">Ver reservas</span>
              </a>
              <a href="facturas.php" class="accion-btn">
                <span class="accion-emoji">🧾</span>
                <span class="accion-label">Facturación</span>
              </a>
              <!-- ✅ Acción rápida de editar perfil -->
              <a href="perfil.php" class="accion-btn">
                <span class="accion-emoji">✏️</span>
                <span class="accion-label">Editar perfil</span>
              </a>
              <a href="favoritos.php" class="accion-btn">
                <span class="accion-emoji">❤️</span>
                <span class="accion-label">Favoritos</span>
              </a>
              <!-- ✅ Acción rápida de disputas -->
              <a href="mis-disputas.php" class="accion-btn" style="grid-column: 1 / -1; background:rgba(220,38,38,.05); border-color:rgba(220,38,38,.1);">
                <span class="accion-emoji">⚠️</span>
                <span class="accion-label" style="color:#dc2626;">Mis Disputas</span>
              </a>
            </div>
          </div>
        </div>

      </div>

      <!-- RIGHT -->
      <div class="right-col">

        <!-- PERFIL CARD -->
        <div class="card perfil-card anim-3">
          <div class="card-header">
            <span class="card-title">Mi perfil</span>
            <!-- ✅ Botón editar en el header de la card -->
            <a href="perfil.php" class="card-badge" style="text-decoration:none;color:var(--green-sea);background:rgba(97,208,149,.1);cursor:pointer;">✏️ Editar</a>
          </div>
          <div class="card-body">

            <!-- ✅ Avatar con overlay de edición al hacer hover -->
            <div class="big-avatar-wrap">
              <div class="big-avatar">
                <?php if ($foto): ?>
                  <img src="<?= $foto ?>" alt="foto de perfil">
                <?php else: ?>
                  <?= $inicial ?>
                <?php endif; ?>
              </div>
              <a href="perfil.php" class="big-avatar-overlay">
                <span>📷</span>
                <span>Editar</span>
              </a>
            </div>

            <div>
              <div class="perfil-nombre"><?= $nombre ?></div>
              <div class="perfil-correo"><?= $correo ?></div>
            </div>

            <div class="perfil-divider"></div>

            <div style="width:100%;display:flex;flex-direction:column;gap:.45rem;">
              <div class="perfil-info-row">
                <span>Tipo de cuenta</span>
                <span><?= ucfirst($_SESSION['rol'] ?? 'Usuario') ?></span>
              </div>
              <div class="perfil-info-row">
                <span>Miembro desde</span>
                <span><?= date('M Y') ?></span>
              </div>
              <div class="perfil-info-row">
                <span>Ciudad</span>
                <span><?= $ciudad ?></span>
              </div>
            </div>

            <!-- ✅ Botón principal de editar perfil -->
            <a href="perfil.php" class="btn-edit-perfil">✏️ Editar mi perfil</a>
            <a href="buscar.php" class="btn-primary-full">🔍 Explorar servicios</a>
            <a href="../index.php?accion=logout" class="btn-secondary-full">Cerrar sesión</a>
          </div>
        </div>

        <!-- PROXIMA CITA REAL -->
        <div class="card anim-4">
          <div class="card-header">
            <span class="card-title">Próxima cita</span>
            <?php if ($proxima): ?>
              <span class="card-badge" style="background:rgba(97,208,149,.12);color:var(--green-sea);">
                <?= date('d M', strtotime($proxima['fecha'])) ?>
              </span>
            <?php endif; ?>
          </div>
          <div class="card-body" style="display:flex;flex-direction:column;gap:.9rem;">
            <?php if ($proxima): ?>
              <div class="cita-box">
                <div style="font-size:1.3rem;"><?= $proxima['icono'] ?></div>
                <div class="cita-service"><?= htmlspecialchars($proxima['servicio']) ?></div>
                <div class="cita-detail">
                  📅 <?= date('d M Y', strtotime($proxima['fecha'])) ?> a las <?= $proxima['hora'] ?><br>
                  👤 <?= htmlspecialchars($proxima['tecnico']) ?><br>
                  📍 <?= htmlspecialchars($proxima['direccion']) ?>
                </div>
              </div>
            <?php else: ?>
              <div style="text-align:center;padding:1.5rem 0;color:var(--text-muted);">
                <div style="font-size:2rem;margin-bottom:.5rem;">📅</div>
                <p style="font-size:.82rem;font-weight:300;">No tienes citas próximas.</p>
              </div>
            <?php endif; ?>
            <a href="mis-reservas.php" class="btn-secondary-full" style="font-size:.82rem;padding:.65rem;">
              Ver mis reservas
            </a>
          </div>
        </div>

      </div>
    </div>
  </div>

<script>
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
          const res = await fetch('../controlador/ControladorChat.php?accion=obtener_inbox');
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
                            <a href="chat.php?reserva=${c.reserva_id}" style="display:flex; align-items:center; gap:1rem; padding:1rem 1.2rem; border-bottom:1px solid rgba(72,191,132,.08); text-decoration:none; color:inherit; transition:background .2s;">
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
<?php
/**
 * vista/mis-reservas.php — Reservas del usuario
 */
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php?accion=login');
    exit();
}

if ($_SESSION['rol'] === 'tecnico') {
    header('Location: tecnico/dashboard.php');
    exit();
}

require_once __DIR__ . '/../modelo/Servicio.php';

$nombre    = htmlspecialchars($_SESSION['nombre'] ?? '');
$foto      = !empty($_SESSION['foto']) ? htmlspecialchars($_SESSION['foto']) : '';
$inicial   = strtoupper(substr($nombre, 0, 1));
$usuarioId = (int)($_SESSION['id'] ?? 0);

$modelo   = new Servicio();
$reservas = $modelo->obtenerReservasUsuario($usuarioId);

$msg_ok       = isset($_GET['ok'])       ? '✅ ¡Reserva enviada! El técnico la revisará pronto.'       : '';
$msg_cancelada = isset($_GET['cancelada']) ? '🗑️ Reserva cancelada correctamente.'                     : '';
$msg_disputa   = isset($_GET['ok_disputa'])? '⚠️ Disputa enviada correctamente. Nuestro equipo la revisará.' : '';
$msg_err      = isset($_GET['error'])    ? htmlspecialchars($_GET['error'])                              : '';
$msg_err      = isset($_GET['error'])    ? htmlspecialchars($_GET['error'])                              : '';

// Agrupar por estado
$pendientes   = array_filter($reservas, fn($r) => $r['estado'] === 'pendiente');
$activas      = array_filter($reservas, fn($r) => in_array($r['estado'], ['aceptada','en_progreso']));
$historial    = array_filter($reservas, fn($r) => in_array($r['estado'], ['completada','cancelada']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis reservas — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500&display=swap" rel="stylesheet">
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
    .btn-nav-green { background:var(--green-light); color:var(--green-dark); border-color:var(--green-light); font-weight:700; font-family:'Syne',sans-serif; }
    .btn-nav-green:hover { background:var(--green-mid); }

    .page { padding:6.5rem 5% 3rem; max-width:900px; margin:0 auto; position:relative; z-index:1; }

    .page-title { margin-bottom:2rem; animation:fadeUp .5s ease both; }
    .page-title h1 { font-family:'Syne',sans-serif; font-size:clamp(1.6rem,3vw,2.3rem); font-weight:800; letter-spacing:-1.2px; color:var(--green-dark); }
    .page-title h1 em { font-style:normal; color:var(--green-mid); }
    .page-title p { color:var(--text-muted); font-size:.9rem; font-weight:300; margin-top:.3rem; }

    /* ALERTS */
    .alert { padding:.9rem 1.2rem; border-radius:14px; font-size:.88rem; font-weight:500; margin-bottom:1.5rem; }
    .alert-ok  { background:rgba(97,208,149,.12); color:var(--green-sea); border:1px solid rgba(97,208,149,.3); }
    .alert-err { background:#fee2e2; color:#dc2626; border:1px solid #fecaca; }

    /* STATS */
    .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:1rem; margin-bottom:2rem; }
    .stat { background:var(--white); border-radius:18px; padding:1.2rem 1.4rem; border:1px solid rgba(72,191,132,.1); box-shadow:0 4px 16px rgba(42,71,71,.05); }
    .stat-val { font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:800; color:var(--green-dark); letter-spacing:-1px; display:block; }
    .stat-lbl { font-size:.75rem; color:var(--text-muted); margin-top:.1rem; }

    /* SECTION */
    .section-title { font-family:'Syne',sans-serif; font-size:.85rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:1.5px; margin:2rem 0 1rem; display:flex; align-items:center; gap:.6rem; }
    .section-title::after { content:''; flex:1; height:1px; background:rgba(72,191,132,.15); }

    /* RESERVA CARD */
    .reserva-card { background:var(--white); border-radius:20px; border:1px solid rgba(72,191,132,.1); box-shadow:0 4px 16px rgba(42,71,71,.05); padding:1.3rem 1.5rem; margin-bottom:1rem; display:flex; align-items:flex-start; gap:1rem; flex-wrap:wrap; transition:all .22s; }
    .reserva-card:hover { border-color:rgba(97,208,149,.3); box-shadow:0 8px 24px rgba(42,71,71,.09); }

    .reserva-icon { width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg,rgba(97,208,149,.15),rgba(72,191,132,.2)); display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0; }
    .reserva-info { flex:1; min-width:200px; }
    .reserva-servicio { font-family:'Syne',sans-serif; font-weight:700; font-size:.95rem; color:var(--green-dark); margin-bottom:.25rem; }
    .reserva-meta { font-size:.8rem; color:var(--text-muted); font-weight:300; line-height:1.7; }
    .reserva-meta strong { font-weight:600; color:var(--text); }

    .reserva-right { display:flex; flex-direction:column; align-items:flex-end; gap:.5rem; flex-shrink:0; }
    .reserva-precio { font-family:'Syne',sans-serif; font-weight:800; font-size:1rem; color:var(--green-dark); }

    .tag { display:inline-block; font-size:.68rem; font-weight:700; padding:.25rem .7rem; border-radius:100px; }
    .tag-pendiente   { background:rgba(251,191,36,.15); color:#b45309; }
    .tag-aceptada    { background:rgba(97,208,149,.15); color:var(--green-sea); }
    .tag-en_progreso { background:rgba(59,130,246,.1);  color:#2563eb; }
    .tag-completada  { background:rgba(42,71,71,.1);    color:var(--green-dark); }
    .tag-cancelada   { background:rgba(220,38,38,.08);  color:#dc2626; }

    .btn-cancelar { background:transparent; color:#dc2626; border:1.5px solid rgba(220,38,38,.2); font-family:'Syne',sans-serif; font-weight:600; font-size:.75rem; padding:.4rem .9rem; border-radius:100px; cursor:pointer; transition:all .2s; }
    .btn-cancelar:hover { background:#fee2e2; border-color:#dc2626; }

    .btn-resena { background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:700; font-size:.75rem; padding:.4rem .9rem; border-radius:100px; text-decoration:none; transition:all .2s; display:inline-block; margin-top:.4rem; }
    .btn-resena:hover { background:var(--green-mid); transform:translateY(-1px); }
    .text-resenada { font-size:.75rem; color:var(--green-sea); font-weight:700; margin-top:.4rem; display:inline-block; }

    .btn-disputa { background:transparent; color:#dc2626; border:1.5px solid rgba(220,38,38,.2); font-family:'Syne',sans-serif; font-weight:600; font-size:.75rem; padding:.4rem .9rem; border-radius:100px; cursor:pointer; transition:all .2s; text-decoration:none; display:inline-block; margin-top:.4rem; }
    .btn-disputa:hover { background:#fee2e2; border-color:#dc2626; }

    /* EMPTY */
    .empty { text-align:center; padding:3rem 1rem; color:var(--text-muted); }
    .empty-icon { font-size:2.5rem; margin-bottom:.8rem; }
    .empty p { font-size:.88rem; font-weight:300; margin-bottom:1rem; }
    .btn-buscar { display:inline-block; background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:700; font-size:.85rem; padding:.75rem 1.6rem; border-radius:100px; text-decoration:none; transition:all .22s; }
    .btn-buscar:hover { background:var(--green-mid); transform:translateY(-1px); }

    @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
    .anim-1 { animation:fadeUp .5s .05s ease both; }
    .anim-2 { animation:fadeUp .5s .12s ease both; }
  </style>
</head>
<body>
<div class="bg-dots"></div>

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
        <div id="inboxDropdownList" style="max-height:360px; overflow-y:auto;">
          <!-- Items injected via JS -->
        </div>
      </div>
    </div>
    <a href="buscar.php" class="btn-nav btn-nav-green">🔍 Buscar servicios</a>
    <a href="bienvenida.php" class="btn-nav">Mi panel</a>
    <a href="perfil.php" class="nav-avatar">
      <?php if($foto): ?><img src="<?= $foto ?>" alt="foto"><?php else: ?><?= $inicial ?><?php endif; ?>
    </a>
    <a href="../index.php?accion=logout" class="btn-nav">Cerrar sesión</a>
  </div>
</nav>

<div class="page">

  <div class="page-title">
    <h1>Mis <em>reservas</em></h1>
    <p>Historial y estado de todos tus servicios</p>
  </div>

  <?php if ($msg_ok):       ?><div class="alert alert-ok"><?= $msg_ok ?></div><?php endif; ?>
  <?php if ($msg_cancelada):?><div class="alert alert-ok"><?= $msg_cancelada ?></div><?php endif; ?>
  <?php if ($msg_disputa):  ?><div class="alert alert-ok" style="background:#fee2e2; color:#dc2626; border-color:#fecaca;"><?= $msg_disputa ?></div><?php endif; ?>
  <?php if ($msg_err):      ?><div class="alert alert-err">⚠️ <?= $msg_err ?></div><?php endif; ?>

  <!-- STATS -->
  <div class="stats-row anim-1">
    <div class="stat">
      <span class="stat-val"><?= count($reservas) ?></span>
      <div class="stat-lbl">Total reservas</div>
    </div>
    <div class="stat">
      <span class="stat-val"><?= count($pendientes) ?></span>
      <div class="stat-lbl">Pendientes</div>
    </div>
    <div class="stat">
      <span class="stat-val"><?= count($activas) ?></span>
      <div class="stat-lbl">En curso</div>
    </div>
    <div class="stat">
      <span class="stat-val"><?= count(array_filter($reservas, fn($r) => $r['estado'] === 'completada')) ?></span>
      <div class="stat-lbl">Completadas</div>
    </div>
  </div>

  <?php if (empty($reservas)): ?>
    <div class="empty anim-2">
      <div class="empty-icon">📭</div>
      <p>Aún no tienes reservas.<br>¡Busca un técnico y contrata tu primer servicio!</p>
      <a href="buscar.php" class="btn-buscar">🔍 Buscar servicios</a>
    </div>

  <?php else: ?>

    <!-- PENDIENTES -->
    <?php if (!empty($pendientes)): ?>
    <div class="section-title anim-1">⏳ Esperando respuesta del técnico</div>
    <?php foreach ($pendientes as $r): ?>
    <div class="reserva-card anim-2">
      <div class="reserva-icon"><?= $r['icono'] ?></div>
      <div class="reserva-info">
        <div class="reserva-servicio"><?= htmlspecialchars($r['servicio']) ?></div>
        <div class="reserva-meta">
          👤 <strong><?= htmlspecialchars($r['tecnico']) ?></strong><br>
          📅 <?= date('d M Y', strtotime($r['fecha'])) ?> a las <?= $r['hora'] ?><br>
          📍 <?= htmlspecialchars($r['direccion']) ?>
        </div>
      </div>
      <div class="reserva-right">
        <div class="reserva-precio">$<?= number_format($r['precio'], 0, ',', '.') ?></div>
        <span class="tag tag-pendiente">⏳ Pendiente</span>
        <form method="POST" action="../controlador/ControladorReserva.php" onsubmit="return confirm('¿Cancelar esta reserva?')">
          <input type="hidden" name="accion"     value="cancelar">
          <input type="hidden" name="reserva_id" value="<?= $r['id'] ?>">
          <button class="btn-cancelar">✗ Cancelar</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- ACTIVAS -->
    <?php if (!empty($activas)): ?>
    <div class="section-title anim-1">🔧 En curso</div>
    <?php foreach ($activas as $r): ?>
    <div class="reserva-card anim-2">
      <div class="reserva-icon"><?= $r['icono'] ?></div>
      <div class="reserva-info">
        <div class="reserva-servicio"><?= htmlspecialchars($r['servicio']) ?></div>
        <div class="reserva-meta">
          👤 <strong><?= htmlspecialchars($r['tecnico']) ?></strong>
          <?php if($r['tecnico_tel']): ?> · 📞 <?= htmlspecialchars($r['tecnico_tel']) ?><?php endif; ?><br>
          📅 <?= date('d M Y', strtotime($r['fecha'])) ?> a las <?= $r['hora'] ?><br>
          📍 <?= htmlspecialchars($r['direccion']) ?>
        </div>
      </div>
      <div class="reserva-right">
        <div class="reserva-precio">$<?= number_format($r['precio'], 0, ',', '.') ?></div>
        <span class="tag tag-<?= $r['estado'] ?>"><?= $r['estado'] === 'aceptada' ? '✅ Aceptada' : '🔧 En progreso' ?></span>
        <div style="display:flex; gap:0.4rem; flex-direction:column;">
          <a href="chat.php?reserva=<?= $r['id'] ?>" class="btn-resena" style="background:var(--green-sea); color:var(--white); border:none; margin:0;">💬 Chat</a>
          <a href="crear-disputa.php?reserva=<?= $r['id'] ?>" class="btn-disputa" style="margin:0;">⚠️ Disputa</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- HISTORIAL -->
    <?php if (!empty($historial)): ?>
    <div class="section-title anim-1">📋 Historial</div>
    <?php foreach ($historial as $r): ?>
    <div class="reserva-card anim-2" style="opacity:<?= $r['estado'] === 'cancelada' ? '.6' : '1' ?>">
      <div class="reserva-icon"><?= $r['icono'] ?></div>
      <div class="reserva-info">
        <div class="reserva-servicio"><?= htmlspecialchars($r['servicio']) ?></div>
        <div class="reserva-meta">
          👤 <strong><?= htmlspecialchars($r['tecnico']) ?></strong><br>
          📅 <?= date('d M Y', strtotime($r['fecha'])) ?><br>
          📍 <?= htmlspecialchars($r['direccion']) ?>
        </div>
      </div>
      <div class="reserva-right">
        <div class="reserva-precio">$<?= number_format($r['precio'], 0, ',', '.') ?></div>
        <span class="tag tag-<?= $r['estado'] ?>"><?= $r['estado'] === 'completada' ? '✔ Completada' : '✗ Cancelada' ?></span>
        <?php if ($r['estado'] === 'completada'): ?>
          <?php if (empty($r['pagado'])): ?>
            <a href="checkout.php?reserva=<?= $r['id'] ?>" class="btn-resena" style="background:#48BF84; color:white; border:none; margin-top:5px;">💳 Pagar</a>
          <?php else: ?>
            <span class="text-resenada" style="color:#61D095;">✅ Pagado</span>
            <?php if (empty($r['tiene_resena'])): ?>
              <a href="resena.php?reserva=<?= $r['id'] ?>" class="btn-resena">⭐ Calificar</a>
            <?php elseif (!empty($r['tiene_resena'])): ?>
              <span class="text-resenada">⭐ Reseñada</span>
            <?php endif; ?>
          <?php endif; ?>
          <a href="crear-disputa.php?reserva=<?= $r['id'] ?>" class="btn-disputa">⚠️ Disputa</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

  <?php endif; ?>

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

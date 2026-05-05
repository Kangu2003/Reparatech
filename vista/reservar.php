<?php
/**
 * vista/reservar.php — Formulario de reserva
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

$nombre  = htmlspecialchars($_SESSION['nombre'] ?? '');
$foto    = !empty($_SESSION['foto']) ? htmlspecialchars($_SESSION['foto']) : '';
$inicial = strtoupper(substr($nombre, 0, 1));

$servicioId = (int)($_GET['servicio'] ?? 0);
if (!$servicioId) {
    header('Location: buscar.php');
    exit();
}

$modelo   = new Servicio();
$servicio = $modelo->obtenerServicioPorId($servicioId);

if (!$servicio) {
    header('Location: buscar.php');
    exit();
}

$msg_err = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reservar servicio — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
  <style>
    :root { --pink:#E0BAD7; --green-light:#61D095; --green-mid:#48BF84; --green-sea:#439775; --green-dark:#2A4747; --white:#FAFAF8; --off-white:#F2F0EC; --text:#1a2a2a; --text-muted:#4a6a6a; }
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'DM Sans',sans-serif; background:var(--off-white); color:var(--text); min-height:100vh; }
    .bg-dots { position:fixed; inset:0; background-image:radial-gradient(var(--green-mid) 1px,transparent 1px); background-size:36px 36px; opacity:.06; pointer-events:none; z-index:0; }

    /* NAV */
    nav { position:fixed; top:0; left:0; right:0; z-index:100; display:flex; align-items:center; justify-content:space-between; padding:.85rem 5%; background:rgba(250,250,248,.95); backdrop-filter:blur(16px); border-bottom:1px solid rgba(72,191,132,.15); box-shadow:0 4px 30px rgba(42,71,71,.07); }
    .logo { font-family:'Syne',sans-serif; font-size:1.45rem; font-weight:800; color:var(--green-dark); text-decoration:none; }
    .logo span { color:var(--green-light); }
    .nav-right { display:flex; align-items:center; gap:.8rem; }
    .nav-avatar { width:34px; height:34px; border-radius:50%; background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:800; font-size:.85rem; display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0; text-decoration:none; }
    .nav-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
    .btn-nav { background:transparent; border:1.5px solid rgba(42,71,71,.2); color:var(--text-muted); font-size:.82rem; padding:.45rem 1rem; border-radius:100px; text-decoration:none; transition:all .2s; }
    .btn-nav:hover { border-color:var(--green-dark); color:var(--green-dark); }

    /* LAYOUT */
    .page { padding:6.5rem 5% 3rem; max-width:820px; margin:0 auto; position:relative; z-index:1; }

    /* TITLE */
    .page-title { margin-bottom:2rem; animation:fadeUp .5s ease both; }
    .page-title h1 { font-family:'Syne',sans-serif; font-size:clamp(1.6rem,3vw,2.2rem); font-weight:800; letter-spacing:-1.2px; color:var(--green-dark); }
    .page-title h1 em { font-style:normal; color:var(--green-mid); }
    .page-title p { color:var(--text-muted); font-size:.9rem; font-weight:300; margin-top:.3rem; }

    /* ALERT */
    .alert-err { background:#fee2e2; color:#dc2626; border:1px solid #fecaca; padding:.9rem 1.2rem; border-radius:14px; font-size:.88rem; margin-bottom:1.5rem; }

    /* GRID */
    .layout-grid { display:grid; grid-template-columns:1fr 320px; gap:1.5rem; align-items:start; }
    @media(max-width:700px) { .layout-grid { grid-template-columns:1fr; } }

    /* CARD */
    .card { background:var(--white); border-radius:24px; border:1px solid rgba(72,191,132,.1); box-shadow:0 4px 24px rgba(42,71,71,.06); overflow:hidden; }
    .card-header { padding:1.3rem 1.6rem 1rem; border-bottom:1px solid rgba(72,191,132,.08); }
    .card-title { font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; color:var(--green-dark); }
    .card-body { padding:1.5rem 1.6rem; }

    /* FORM */
    .form-group { display:flex; flex-direction:column; gap:.4rem; margin-bottom:1.2rem; }
    .form-group:last-child { margin-bottom:0; }
    .form-label { font-family:'Syne',sans-serif; font-size:.78rem; font-weight:700; color:var(--green-dark); text-transform:uppercase; letter-spacing:.3px; }
    .form-input, .form-textarea, .form-select {
      background:var(--off-white); border:1.5px solid rgba(72,191,132,.2);
      border-radius:14px; padding:.85rem 1.1rem;
      font-family:'DM Sans',sans-serif; font-size:.92rem; color:var(--text);
      outline:none; transition:all .25s; resize:none; width:100%;
    }
    .form-input:focus, .form-textarea:focus, .form-select:focus { border-color:var(--green-light); background:var(--white); box-shadow:0 0 0 4px rgba(97,208,149,.1); }
    .form-hint { font-size:.75rem; color:var(--text-muted); font-weight:300; }

    .btn-submit { width:100%; background:var(--green-dark); color:var(--white); font-family:'Syne',sans-serif; font-weight:700; font-size:.95rem; padding:1rem; border-radius:100px; border:none; cursor:pointer; transition:all .25s; box-shadow:0 8px 20px rgba(42,71,71,.15); margin-top:.5rem; }
    .btn-submit:hover { background:#1a2a2a; transform:translateY(-2px); }

    /* RESUMEN DEL SERVICIO */
    .resumen-card .card-body { display:flex; flex-direction:column; gap:1rem; }

    .resumen-icono { width:60px; height:60px; border-radius:18px; background:linear-gradient(135deg,rgba(97,208,149,.2),rgba(72,191,132,.3)); display:flex; align-items:center; justify-content:center; font-size:1.8rem; }

    .resumen-titulo { font-family:'Syne',sans-serif; font-weight:800; font-size:1.05rem; color:var(--green-dark); }
    .resumen-cat { font-size:.78rem; color:var(--green-sea); font-weight:600; margin-top:.15rem; }

    .divider { height:1px; background:rgba(72,191,132,.1); }

    .resumen-row { display:flex; justify-content:space-between; font-size:.85rem; padding:.2rem 0; }
    .resumen-row span:first-child { color:var(--text-muted); font-weight:300; }
    .resumen-row span:last-child  { font-weight:600; color:var(--green-dark); }

    .resumen-precio { font-family:'Syne',sans-serif; font-weight:800; font-size:1.6rem; color:var(--green-dark); letter-spacing:-1px; }
    .resumen-tipo   { font-size:.78rem; color:var(--text-muted); font-weight:300; }

    .tecnico-mini { display:flex; align-items:center; gap:.7rem; padding:.9rem; background:var(--off-white); border-radius:14px; border:1px solid rgba(72,191,132,.1); }
    .tecnico-mini-avatar { width:38px; height:38px; border-radius:50%; background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:800; font-size:.9rem; display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0; }
    .tecnico-mini-avatar img { width:100%; height:100%; object-fit:cover; }
    .tecnico-mini-nombre { font-family:'Syne',sans-serif; font-weight:700; font-size:.88rem; color:var(--green-dark); }
    .tecnico-mini-ciudad { font-size:.75rem; color:var(--text-muted); font-weight:300; }

    .info-box { background:rgba(97,208,149,.07); border:1px solid rgba(97,208,149,.2); border-radius:14px; padding:.9rem 1rem; font-size:.8rem; color:var(--green-sea); line-height:1.6; font-weight:400; }

    .btn-resenas-link { background:rgba(97,208,149,.15); color:var(--green-dark); padding:.4rem .9rem; border-radius:100px; text-decoration:none; font-family:'Syne',sans-serif; font-weight:800; font-size:.9rem; transition:all .2s; border:1px solid rgba(97,208,149,.3); display:inline-flex; align-items:center; }
    .btn-resenas-link:hover { background:var(--green-light); transform:translateY(-1px); box-shadow:0 4px 12px rgba(97,208,149,.2); }

    @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
    .anim-1 { animation:fadeUp .5s .05s ease both; }
    .anim-2 { animation:fadeUp .5s .12s ease both; }
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
      <a href="buscar.php" class="btn-nav">← Volver a búsqueda</a>
      <a href="perfil.php" class="nav-avatar">
      <?php if($foto): ?><img src="<?= $foto ?>" alt="foto"><?php else: ?><?= $inicial ?><?php endif; ?>
    </a>
    <a href="../index.php?accion=logout" class="btn-nav">Cerrar sesión</a>
  </div>
</nav>

<div class="page">

  <div class="page-title">
    <h1>Solicitar <em>reserva</em></h1>
    <p>Completa los datos para enviar tu solicitud al técnico</p>
  </div>

  <?php if ($msg_err): ?>
    <div class="alert-err">⚠️ <?= $msg_err ?></div>
  <?php endif; ?>

  <div class="layout-grid">

    <!-- FORMULARIO -->
    <div class="card anim-1">
      <div class="card-header">
        <div class="card-title">📋 Datos de la reserva</div>
      </div>
      <div class="card-body">
        <form method="POST" action="../controlador/ControladorReserva.php">
          <input type="hidden" name="servicio_id"  value="<?= $servicio['id'] ?>">
          <input type="hidden" name="tecnico_id"   value="<?= $servicio['tecnico_id'] ?>">

          <div class="form-group">
            <label class="form-label">Fecha del servicio</label>
            <input type="date" name="fecha" class="form-input"
              min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
              value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Hora preferida</label>
            <input type="time" name="hora" class="form-input" value="08:00" required>
          </div>

          <div class="form-group">
            <label class="form-label">Dirección del servicio</label>
            <input type="text" name="direccion" class="form-input"
              placeholder="Ej: Calle 22 #4-15, Santa Marta" required>
            <span class="form-hint">Escribe la dirección donde necesitas el servicio</span>
          </div>

          <div class="form-group">
            <label class="form-label">Descripción del problema <span style="font-weight:300;text-transform:none;">(opcional)</span></label>
            <textarea name="notas" class="form-textarea" rows="4"
              placeholder="Describe brevemente el problema o lo que necesitas..."></textarea>
          </div>

          <button type="submit" class="btn-submit">📅 Enviar solicitud de reserva</button>
        </form>
      </div>
    </div>

    <!-- RESUMEN -->
    <div style="display:flex;flex-direction:column;gap:1.2rem;">

      <div class="card resumen-card anim-2">
        <div class="card-header">
          <div class="card-title">Resumen del servicio</div>
        </div>
        <div class="card-body">
          <div style="display:flex;align-items:center;gap:.9rem;">
            <div class="resumen-icono"><?= $servicio['icono'] ?></div>
            <div>
              <div class="resumen-titulo"><?= htmlspecialchars($servicio['titulo']) ?></div>
              <div class="resumen-cat"><?= htmlspecialchars($servicio['categoria']) ?></div>
            </div>
          </div>

          <div class="divider"></div>

          <div>
            <div class="resumen-precio">$<?= number_format($servicio['precio'], 0, ',', '.') ?></div>
            <div class="resumen-tipo"><?= $servicio['precio_tipo'] === 'por_hora' ? 'por hora' : 'precio fijo' ?></div>
          </div>

          <div class="divider"></div>

          <div class="tecnico-mini">
            <div class="tecnico-mini-avatar">
              <?php if($servicio['tecnico_foto']): ?>
                <img src="<?= htmlspecialchars($servicio['tecnico_foto']) ?>" alt="">
              <?php else: ?>
                <?= strtoupper(substr($servicio['tecnico_nombre'], 0, 1)) ?>
              <?php endif; ?>
            </div>
            <div>
              <div class="tecnico-mini-nombre"><?= htmlspecialchars($servicio['tecnico_nombre']) ?></div>
              <div class="tecnico-mini-ciudad">📍 <?= htmlspecialchars($servicio['tecnico_ciudad']) ?></div>
            </div>
          </div>

          <?php if ($servicio['calificacion']): ?>
          <div class="resumen-row" style="align-items:center;">
            <span>Calificación</span>
            <a href="leer_resenas.php?tecnico=<?= $servicio['tecnico_id'] ?>" class="btn-resenas-link" title="Ver opiniones">
               ⭐ <?= $servicio['calificacion'] ?> / 5 
               <span style="font-size:0.7rem; margin-left:6px; font-weight:700; color:var(--green-sea);">Ver reseñas ➔</span>
            </a>
          </div>
          <?php endif; ?>

        </div>
      </div>

      <?php if (!empty($servicio['latitud']) && !empty($servicio['longitud'])): ?>
      <div class="card anim-2">
        <div class="card-header" style="padding-bottom: 0.5rem;">
          <div class="card-title">📍 Ubicación del Local</div>
          <?php if (!empty($servicio['direccion_local'])): ?>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;"><?= htmlspecialchars($servicio['direccion_local']) ?></div>
          <?php endif; ?>
        </div>
        <div class="card-body" style="padding: 1rem;">
          <div id="map-tecnico" style="height: 200px; border-radius: 12px; border: 1px solid rgba(72,191,132,.2); z-index: 1;"></div>
        </div>
      </div>
      <?php endif; ?>

      <div class="info-box anim-2">
        ℹ️ Tu solicitud será enviada al técnico. Él tiene hasta <strong>24 horas</strong> para aceptarla o rechazarla. Te notificaremos en tu panel.
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

<?php if (!empty($servicio['latitud']) && !empty($servicio['longitud'])): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    const lat = <?= $servicio['latitud'] ?>;
    const lng = <?= $servicio['longitud'] ?>;
    const map = L.map('map-tecnico').setView([lat, lng], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    L.marker([lat, lng]).addTo(map)
      .bindPopup("<b><?= htmlspecialchars($servicio['tecnico_nombre']) ?></b><br>📍 <?= htmlspecialchars($servicio['direccion_local'] ?? '') ?>")
      .openPopup();
  });
</script>
<?php endif; ?>

</body>
</html>

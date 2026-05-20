<?php
/**
 * vista/buscar.php — Búsqueda de servicios y técnicos
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

if (!defined('BASE_URL')) {
    if (getenv('RENDER') !== false) {
        define('BASE_URL', '');
    } else {
        $envBase = getenv('BASE_URL');
        if ($envBase !== false && $envBase !== '') {
            define('BASE_URL', $envBase);
        } else {
            $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
            $dir = str_replace('\\', '/', dirname(__DIR__)); // sube un nivel porque estamos en vista/
            $baseUrl = str_ireplace($docRoot, '', $dir);
            define('BASE_URL', $baseUrl);
        }
    }
}

require_once __DIR__ . '/../modelo/Servicio.php';
require_once __DIR__ . '/../modelo/Usuario.php';

$nombre  = htmlspecialchars($_SESSION['nombre'] ?? '');
$foto    = !empty($_SESSION['foto']) ? htmlspecialchars($_SESSION['foto']) : '';
$inicial = strtoupper(substr($nombre, 0, 1));
$clienteId = (int)($_SESSION['id'] ?? 0);

$usuarioModelo = new Usuario();
$favoritos = $usuarioModelo->obtenerFavoritos($clienteId);
$favoritosIds = array_column($favoritos, 'id'); // ID de técnicos favoritos

$modelo      = new Servicio();
$categorias  = $modelo->obtenerCategorias();

// Filtros
$busqueda    = trim($_GET['q']          ?? '');
$categoriaId = (int)($_GET['categoria'] ?? 0);
$ciudad      = trim($_GET['ciudad']     ?? '');

$servicios = $modelo->buscarServicios($busqueda, $categoriaId, $ciudad);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buscar servicios — ReparaTech</title>
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
    .page { padding:6.5rem 5% 3rem; max-width:1100px; margin:0 auto; position:relative; z-index:1; }

    /* HERO SEARCH */
    .search-hero { text-align:center; margin-bottom:2.5rem; animation:fadeUp .5s ease both; }
    .search-hero h1 { font-family:'Syne',sans-serif; font-size:clamp(1.8rem,3.5vw,2.8rem); font-weight:800; letter-spacing:-1.5px; color:var(--green-dark); margin-bottom:.5rem; }
    .search-hero h1 em { font-style:normal; color:var(--green-mid); }
    .search-hero p { color:var(--text-muted); font-size:.95rem; font-weight:300; }

    .search-bar { display:flex; gap:.7rem; max-width:620px; margin:1.5rem auto 0; flex-wrap:wrap; }
    .search-input { flex:1; min-width:200px; background:var(--white); border:1.5px solid rgba(72,191,132,.2); border-radius:100px; padding:.85rem 1.4rem; font-family:'DM Sans',sans-serif; font-size:.92rem; outline:none; transition:all .25s; }
    .search-input:focus { border-color:var(--green-light); box-shadow:0 0 0 4px rgba(97,208,149,.1); }
    .btn-search { background:var(--green-dark); color:var(--white); font-family:'Syne',sans-serif; font-weight:700; font-size:.88rem; padding:.85rem 1.6rem; border-radius:100px; border:none; cursor:pointer; transition:all .25s; white-space:nowrap; }
    .btn-search:hover { background:#1a2a2a; transform:translateY(-1px); }

    /* FILTROS */
    .filtros { display:flex; gap:.6rem; flex-wrap:wrap; margin-bottom:2rem; animation:fadeUp .5s .1s ease both; }
    .filtro-chip { display:inline-flex; align-items:center; gap:.4rem; padding:.5rem 1rem; border-radius:100px; border:1.5px solid rgba(72,191,132,.2); background:var(--white); font-size:.8rem; font-weight:600; color:var(--text-muted); cursor:pointer; text-decoration:none; transition:all .22s; white-space:nowrap; }
    .filtro-chip:hover { border-color:var(--green-mid); color:var(--green-dark); }
    .filtro-chip.active { background:var(--green-dark); color:var(--white); border-color:var(--green-dark); }

    /* RESULTADOS */
    .resultados-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.2rem; flex-wrap:wrap; gap:.5rem; }
    .resultados-count { font-family:'Syne',sans-serif; font-size:.85rem; font-weight:700; color:var(--text-muted); }

    .servicios-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1.2rem; }

    /* SERVICIO CARD */
    .servicio-card { background:var(--white); border-radius:24px; border:1px solid rgba(72,191,132,.1); box-shadow:0 4px 20px rgba(42,71,71,.06); overflow:hidden; transition:all .25s; display:flex; flex-direction:column; }
    .servicio-card:hover { transform:translateY(-4px); box-shadow:0 12px 40px rgba(42,71,71,.12); border-color:rgba(97,208,149,.3); }

    .servicio-img { height:140px; background:linear-gradient(135deg,rgba(97,208,149,.15),rgba(72,191,132,.25)); display:flex; align-items:center; justify-content:center; font-size:3.5rem; position:relative; }
    .servicio-cat-tag { position:absolute; top:.8rem; left:.8rem; background:var(--white); border-radius:100px; padding:.25rem .8rem; font-size:.72rem; font-weight:700; color:var(--green-sea); border:1px solid rgba(72,191,132,.2); }

    .servicio-body { padding:1.2rem; flex:1; display:flex; flex-direction:column; }
    .tecnico-row { display:flex; align-items:center; gap:.6rem; margin-bottom:.8rem; }
    .tecnico-avatar { width:32px; height:32px; border-radius:50%; background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:800; font-size:.78rem; display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0; }
    .tecnico-avatar img { width:100%; height:100%; object-fit:cover; }
    .tecnico-nombre { font-size:.82rem; font-weight:600; color:var(--green-dark); }
    .tecnico-ciudad { font-size:.72rem; color:var(--text-muted); font-weight:300; }
    .servicio-titulo { font-family:'Syne',sans-serif; font-weight:700; font-size:.95rem; color:var(--green-dark); margin-bottom:.4rem; }
    .servicio-desc { font-size:.8rem; color:var(--text-muted); font-weight:300; line-height:1.5; margin-bottom:1rem; flex:1; }
    .servicio-footer { display:flex; align-items:center; justify-content:space-between; }
    .servicio-precio { font-family:'Syne',sans-serif; font-weight:800; font-size:1.1rem; color:var(--green-dark); }
    .servicio-tipo { font-size:.72rem; color:var(--text-muted); font-weight:300; margin-top:.1rem; }
    .calificacion { display:flex; align-items:center; gap:.3rem; font-size:.78rem; color:#f59e0b; font-weight:600; }

    .btn-reservar { width:100%; background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:700; font-size:.85rem; padding:.75rem; border-radius:100px; border:none; cursor:pointer; margin-top:1rem; transition:all .25s; text-decoration:none; display:block; text-align:center; }
    .btn-reservar:hover { background:var(--green-mid); transform:translateY(-1px); }

    /* FAVORITOS */
    .btn-fav { background:transparent; border:none; cursor:pointer; font-size:1.4rem; color:#ccc; transition:all .25s; margin-left:auto; display:flex; align-items:center; }
    .btn-fav.active { color:#ef4444; }
    .btn-fav:hover { transform:scale(1.1); }

    /* LEAFLET TOOLTIP CUSTOM */
    .custom-map-tooltip {
      background: var(--green-dark);
      color: var(--white);
      border: none;
      border-radius: 8px;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 0.8rem;
      padding: 0.2rem 0.5rem;
      box-shadow: 0 4px 10px rgba(42,71,71,0.3);
    }
    .leaflet-tooltip-top.custom-map-tooltip::before {
      border-top-color: var(--green-dark);
    }

    /* EMPTY */
    .empty { text-align:center; padding:4rem 1rem; color:var(--text-muted); }
    .empty-icon { font-size:3rem; margin-bottom:1rem; }
    .empty p { font-size:.92rem; font-weight:300; line-height:1.6; }

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
    <a href="bienvenida.php" class="btn-nav">👤 Mi panel</a>
    <a href="perfil.php" class="nav-avatar">
      <?php if($foto): ?><img src="<?= $foto ?>" alt="foto"><?php else: ?><?= $inicial ?><?php endif; ?>
    </a>
    <a href="../index.php?accion=logout" class="btn-nav">Cerrar sesión</a>
  </div>
</nav>

<div class="page">

  <!-- HERO -->
  <div class="search-hero">
    <h1>Encuentra tu <em>técnico</em> ideal</h1>
    <p>Servicios profesionales en tu ciudad, a un clic de distancia</p>
    <form method="GET" action="buscar.php">
      <div class="search-bar">
        <input type="text" name="q" class="search-input" placeholder="🔍 Ej: instalación eléctrica, plomería..." value="<?= htmlspecialchars($busqueda) ?>">
        <?php if ($categoriaId): ?>
          <input type="hidden" name="categoria" value="<?= $categoriaId ?>">
        <?php endif; ?>
        <button type="submit" class="btn-search">Buscar</button>
      </div>
    </form>
  </div>

  <!-- FILTROS POR CATEGORÍA -->
  <div class="filtros">
    <a href="buscar.php<?= $busqueda ? '?q='.urlencode($busqueda) : '' ?>" class="filtro-chip <?= !$categoriaId ? 'active' : '' ?>">
      🔧 Todos
    </a>
    <?php foreach ($categorias as $cat): ?>
      <a href="buscar.php?categoria=<?= $cat['id'] ?><?= $busqueda ? '&q='.urlencode($busqueda) : '' ?>"
         class="filtro-chip <?= $categoriaId === (int)$cat['id'] ? 'active' : '' ?>">
        <?= $cat['icono'] ?> <?= htmlspecialchars($cat['nombre']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- LEYENDA DE CERTIFICACIONES -->
  <div style="margin-top: 1rem; margin-bottom: 2.5rem; display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap; font-size: 0.85rem; color: var(--text-muted);">
    <div style="display:flex; align-items:center; gap:0.5rem;">
      <span style="color: #3b82f6; display:inline-flex;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="0" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4" stroke="white" stroke-width="2"></path></svg>
      </span>
      <span><strong>Certificado:</strong> Cuenta validada por la plataforma.</span>
    </div>
    <div style="display:flex; align-items:center; gap:0.5rem;">
      <img src="<?= BASE_URL ?>/img/experto.png" style="width:20px; height:20px; object-fit:contain;" alt="Experto">
      <span><strong>Experto:</strong> Alta tasa de éxito y excelentes reseñas.</span>
    </div>
  </div>

  <!-- RESULTADOS -->
  <div class="resultados-header anim-1">
    <span class="resultados-count">
      <?= count($servicios) ?> servicio<?= count($servicios) !== 1 ? 's' : '' ?> encontrado<?= count($servicios) !== 1 ? 's' : '' ?>
      <?= $busqueda ? ' para "'.htmlspecialchars($busqueda).'"' : '' ?>
      <?= $categoriaId ? ' en '.htmlspecialchars($categorias[array_search($categoriaId, array_column($categorias,'id'))]['nombre'] ?? '') : '' ?>
    </span>
  </div>

  <?php if (!empty($servicios)): ?>
  <div class="anim-1" style="margin-bottom: 2rem;">
    <div id="map-servicios" style="height: 350px; border-radius: 24px; border: 1px solid rgba(72,191,132,.2); z-index: 1;"></div>
  </div>
  <?php endif; ?>

  <?php if (empty($servicios)): ?>
    <div class="empty">
      <div class="empty-icon">🔍</div>
      <p>No encontramos servicios con esos filtros.<br>Intenta con otra búsqueda o categoría.</p>
    </div>
  <?php else: ?>
    <div class="servicios-grid anim-2">
      <?php foreach ($servicios as $s): ?>
      <div class="servicio-card">
        <div class="servicio-img">
          <span><?= $s['icono'] ?></span>
          <span class="servicio-cat-tag"><?= htmlspecialchars($s['categoria']) ?></span>
        </div>
        <div class="servicio-body">
          <div class="tecnico-row">
            <div class="tecnico-avatar">
              <?php if($s['tecnico_foto']): ?>
                <img src="<?= htmlspecialchars($s['tecnico_foto']) ?>" alt="">
              <?php else: ?>
                <?= strtoupper(substr($s['tecnico_nombre'], 0, 1)) ?>
              <?php endif; ?>
            </div>
            <div>
              <div class="tecnico-nombre" style="display:flex;align-items:center;">
                <?= htmlspecialchars($s['tecnico_nombre']) ?>
                <?php if (!empty($s['es_premium'])): ?>
                  <span title="Técnico Certificado" style="color: #3b82f6; margin-left: 4px; display:inline-flex;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="0" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4" stroke="white" stroke-width="2"></path></svg>
                  </span>
                <?php endif; ?>
                <?php if (!empty($s['es_experto'])): ?>
                  <span title="Técnico Experto" style="margin-left: 4px; display:inline-flex; width:20px; height:20px; align-items:center; justify-content:center;">
                    <img src="<?= BASE_URL ?>/img/experto.png" style="width:100%; height:100%; object-fit:contain;" alt="Experto">
                  </span>
                <?php endif; ?>
              </div>
              <div class="tecnico-ciudad">📍 <?= htmlspecialchars($s['tecnico_ciudad']) ?></div>
            </div>
            
            <button type="button" class="btn-fav <?= in_array($s['tecnico_id'], $favoritosIds) ? 'active' : '' ?>" 
                    onclick="toggleFav(this, <?= $s['tecnico_id'] ?>)" title="Guardar como favorito">
              <?= in_array($s['tecnico_id'], $favoritosIds) ? '❤️' : '🤍' ?>
            </button>
            
          </div>
          <div class="servicio-titulo" style="display:flex; justify-content:space-between; align-items:center;">
            <?= htmlspecialchars($s['titulo']) ?>
            <?php if ($s['calificacion']): ?>
              <div class="calificacion">⭐ <?= $s['calificacion'] ?></div>
            <?php endif; ?>
          </div>
          <div class="servicio-desc"><?= htmlspecialchars($s['descripcion']) ?></div>
          <div class="servicio-footer">
            <div>
              <div class="servicio-precio">$<?= number_format($s['precio'], 0, ',', '.') ?></div>
              <div class="servicio-tipo"><?= $s['precio_tipo'] === 'por_hora' ? 'por hora' : 'precio fijo' ?></div>
            </div>
          </div>
          <a href="reservar.php?servicio=<?= $s['id'] ?>" class="btn-reservar">📅 Reservar ahora</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
  function toggleFav(btn, tecnicoId) {
    const formData = new FormData();
    formData.append('accion', 'toggle');
    formData.append('tecnico_id', tecnicoId);

    fetch('../controlador/ControladorFavorito.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        if (data.action === 'added') {
          btn.classList.add('active');
          btn.innerHTML = '❤️';
        } else {
          btn.classList.remove('active');
          btn.innerHTML = '🤍';
        }
      } else {
        alert(data.message || 'Error al guardar favorito');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Error de conexión');
    });
  }

  document.addEventListener("DOMContentLoaded", function() {
    const mapContainer = document.getElementById('map-servicios');
    if (mapContainer) {
      const servicios = <?= json_encode($servicios) ?>;
      const puntos = servicios.filter(s => s.latitud && s.longitud);

      if (puntos.length > 0) {
        // Find bounds
        const latLngs = puntos.map(p => [parseFloat(p.latitud), parseFloat(p.longitud)]);
        
        const map = L.map('map-servicios');
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const bounds = L.latLngBounds(latLngs);
        map.fitBounds(bounds, { padding: [30, 30] });

        puntos.forEach(p => {
          const popupContent = `
            <div style="text-align:center;">
              <strong>${p.tecnico_nombre}</strong><br>
              <span style="font-size:0.8rem;">${p.titulo}</span><br>
              <a href="reservar.php?servicio=${p.id}" style="display:inline-block; margin-top:5px; padding:3px 8px; background:#61D095; color:#2A4747; text-decoration:none; border-radius:100px; font-weight:bold; font-size:0.8rem;">Ver Servicio</a>
            </div>
          `;
          L.marker([parseFloat(p.latitud), parseFloat(p.longitud)])
           .addTo(map)
           .bindTooltip(p.tecnico_nombre, {
             permanent: true, 
             direction: 'top', 
             offset: [0, -35], 
             className: 'custom-map-tooltip'
           })
           .bindPopup(popupContent);
        });
      } else {
        // Default map view if no services have coordinates
        const defaultLat = 11.2404; // Santa Marta
        const defaultLng = -74.1990;
        const map = L.map('map-servicios').setView([defaultLat, defaultLng], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
      }
    }
  });
</script>
</body>
</html>

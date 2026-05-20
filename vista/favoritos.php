<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'usuario') {
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../modelo/Usuario.php';

$clienteId = (int)$_SESSION['id'];
$usuarioModelo = new Usuario();
$favoritos = $usuarioModelo->obtenerFavoritos($clienteId);

$nombre  = htmlspecialchars($_SESSION['nombre'] ?? '');
$foto    = !empty($_SESSION['foto']) ? htmlspecialchars($_SESSION['foto']) : '';
$inicial = strtoupper(substr($nombre, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis Técnicos Favoritos — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --pink: #E0BAD7; --green-light: #61D095; --green-mid: #48BF84; --green-sea: #439775;
      --green-dark: #2A4747; --white: #FAFAF8; --off-white: #F2F0EC; --text: #1a2a2a; --text-muted: #4a6a6a;
    }
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DM Sans', sans-serif; background: var(--off-white); color: var(--text); min-height: 100vh; }
    
    /* NAV */
    nav { position: fixed; top: 0; left: 0; right: 0; z-index: 100; display: flex; align-items: center; justify-content: space-between; padding: .85rem 5%; background: rgba(250,250,248,.95); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(72,191,132,.15); box-shadow: 0 4px 30px rgba(42,71,71,.07); }
    .logo { font-family: 'Syne', sans-serif; font-size: 1.45rem; font-weight: 800; color: var(--green-dark); text-decoration: none; }
    .logo span { color: var(--green-light); }
    .nav-right { display: flex; align-items: center; gap: .8rem; }
    .nav-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--green-light); color: var(--green-dark); font-family: 'Syne', sans-serif; font-weight: 800; font-size: .85rem; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; text-decoration: none; }
    .nav-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .btn-nav { background: transparent; border: 1.5px solid rgba(42,71,71,.2); color: var(--text-muted); font-size: .82rem; padding: .45rem 1rem; border-radius: 100px; text-decoration: none; transition: all .2s; }
    .btn-nav:hover { border-color: var(--green-dark); color: var(--green-dark); }
    
    /* LAYOUT */
    .page { padding: 6.5rem 5% 3rem; max-width: 900px; margin: 0 auto; position: relative; z-index: 1; }
    
    /* HERO */
    .hero { margin-bottom: 2rem; animation: fadeUp .5s ease both; }
    .hero h1 { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; color: var(--green-dark); margin-bottom: .5rem; }
    .hero p { color: var(--text-muted); font-size: .95rem; }
    
    /* GRID */
    .tecnicos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; animation: fadeUp .5s .1s ease both; }
    .tecnico-card { background: var(--white); border-radius: 24px; border: 1px solid rgba(72,191,132,.1); padding: 1.5rem; display: flex; flex-direction: column; align-items: center; text-align: center; box-shadow: 0 4px 20px rgba(42,71,71,.05); transition: transform .2s, box-shadow .2s; position: relative; }
    .tecnico-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(42,71,71,.1); border-color: rgba(97,208,149,.3); }
    
    .btn-fav { position: absolute; top: 1rem; right: 1rem; background: transparent; border: none; cursor: pointer; font-size: 1.4rem; color: #ef4444; transition: transform .2s; }
    .btn-fav:hover { transform: scale(1.1); }
    
    .avatar-wrap { width: 80px; height: 80px; border-radius: 20px; background: var(--green-light); color: var(--green-dark); font-family: 'Syne', sans-serif; font-weight: 800; font-size: 2rem; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; overflow: hidden; }
    .avatar-wrap img { width: 100%; height: 100%; object-fit: cover; }
    
    .tecnico-nombre { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1.1rem; color: var(--green-dark); margin-bottom: .2rem; display:flex; align-items:center; justify-content:center; gap:.4rem; }
    .tecnico-ciudad { font-size: .8rem; color: var(--text-muted); margin-bottom: .8rem; }
    .tecnico-bio { font-size: .85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1.5rem; flex: 1; }
    
    .btn-ver { width: 100%; background: rgba(97,208,149,.1); color: var(--green-sea); border: 1.5px solid rgba(97,208,149,.3); padding: .7rem; border-radius: 100px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: .85rem; text-decoration: none; transition: all .2s; }
    .btn-ver:hover { background: var(--green-light); color: var(--green-dark); border-color: var(--green-light); }
    
    /* EMPTY */
    .empty { text-align: center; padding: 4rem 1rem; color: var(--text-muted); }
    .empty-icon { font-size: 3rem; margin-bottom: 1rem; }
    .empty p { font-size: .95rem; line-height: 1.6; }
    
    @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>
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
    <div class="hero">
      <h1>Mis Técnicos <em>Favoritos</em> ❤️</h1>
      <p>Aquí puedes ver y gestionar los técnicos que has guardado.</p>
    </div>

    <?php if (empty($favoritos)): ?>
      <div class="empty anim-1">
        <div class="empty-icon">🤍</div>
        <p>Aún no has guardado ningún técnico como favorito.<br>Explora los servicios y guarda a los mejores.</p>
        <br>
        <a href="buscar.php" class="btn-ver" style="display:inline-block; width:auto; padding: .8rem 1.5rem;">🔍 Buscar técnicos</a>
      </div>
    <?php else: ?>
      <div class="tecnicos-grid">
        <?php foreach ($favoritos as $tecnico): ?>
          <div class="tecnico-card" id="card-<?= $tecnico['id'] ?>">
            <button class="btn-fav" onclick="removeFav(<?= $tecnico['id'] ?>)" title="Eliminar de favoritos">❤️</button>
            <div class="avatar-wrap">
              <?php if($tecnico['foto']): ?>
                <img src="<?= htmlspecialchars($tecnico['foto']) ?>" alt="">
              <?php else: ?>
                <?= strtoupper(substr($tecnico['nombre_usuario'], 0, 1)) ?>
              <?php endif; ?>
            </div>
            <div class="tecnico-nombre">
              <?= htmlspecialchars($tecnico['nombre_usuario']) ?>
              <?php if (!empty($tecnico['es_premium'])): ?>
                <span title="Técnico Certificado" style="color: #3b82f6;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </span>
              <?php endif; ?>
              <?php if (!empty($tecnico['es_experto'])): ?>
                <img src="<?= BASE_URL ?>/img/experto.png" style="width:18px; height:18px;" alt="Experto" title="Técnico Experto">
              <?php endif; ?>
            </div>
            <div class="tecnico-ciudad">📍 <?= htmlspecialchars($tecnico['ciudad']) ?></div>
            <div class="tecnico-bio"><?= htmlspecialchars($tecnico['bio'] ?: 'Sin descripción') ?></div>
            <a href="buscar.php?q=<?= urlencode($tecnico['nombre_usuario']) ?>" class="btn-ver">Ver servicios</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <script>
    function removeFav(tecnicoId) {
      if(!confirm("¿Deseas eliminar este técnico de tus favoritos?")) return;

      const formData = new FormData();
      formData.append('accion', 'toggle');
      formData.append('tecnico_id', tecnicoId);

      fetch('../controlador/ControladorFavorito.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success && data.action === 'removed') {
          const card = document.getElementById('card-' + tecnicoId);
          card.style.display = 'none';
        } else {
          alert(data.message || 'Error al eliminar');
        }
      })
      .catch(err => {
        console.error(err);
        alert('Error de conexión');
      });
    }
  </script>
</body>
</html>

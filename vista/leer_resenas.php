<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php?accion=login');
    exit();
}

$tecnico_id = (int)($_GET['tecnico'] ?? 0);
if (!$tecnico_id) {
    header('Location: buscar.php');
    exit();
}

require_once __DIR__ . '/../modelo/Tecnico.php';
$modelo = new Tecnico();
$resenas = $modelo->obtenerResenas($tecnico_id);
$calificacion_promedio = $modelo->obtenerCalificacionPromedio($tecnico_id);

require_once __DIR__ . '/../modelo/Conexion.php';
$db = (new Conexion())->getConexion();
$stmt = $db->prepare("SELECT nombre_usuario, foto FROM usuarios WHERE id = ? AND rol = 'tecnico'");
$stmt->bind_param("i", $tecnico_id);
$stmt->execute();
$tecnicoInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tecnicoInfo) {
    header('Location: buscar.php');
    exit();
}

$usuario_nombre = htmlspecialchars($_SESSION['nombre'] ?? '');
$usuario_foto   = !empty($_SESSION['foto']) ? htmlspecialchars($_SESSION['foto']) : '';
$usuario_inicial = strtoupper(substr($usuario_nombre, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reseñas de <?= htmlspecialchars($tecnicoInfo['nombre_usuario']) ?> — ReparaTech</title>
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

    .page { padding:6.5rem 5% 3rem; max-width:800px; margin:0 auto; position:relative; z-index:1; }

    .header-card { background:var(--white); border-radius:24px; border:1px solid rgba(72,191,132,.1); box-shadow:0 4px 24px rgba(42,71,71,.06); padding:2rem; margin-bottom:2rem; display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap; animation:fadeUp .5s ease both; }
    .tecnico-avatar { width:80px; height:80px; border-radius:50%; background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:800; font-size:2rem; display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0; }
    .tecnico-avatar img { width:100%; height:100%; object-fit:cover; }
    .tecnico-info { flex:1; }
    .tecnico-nombre { font-family:'Syne',sans-serif; font-size:1.6rem; font-weight:800; color:var(--green-dark); letter-spacing:-1px; margin-bottom:.3rem; }
    .tecnico-stats { display:flex; align-items:center; gap:1rem; font-size:.9rem; color:var(--text-muted); }
    .stars-badge { background:rgba(97,208,149,.15); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:800; padding:.3rem .8rem; border-radius:100px; display:inline-flex; align-items:center; gap:.4rem; border:1px solid rgba(97,208,149,.3); }

    .resenas-list { display:flex; flex-direction:column; gap:1.2rem; }
    .resena-card { background:var(--white); border-radius:20px; border:1px solid rgba(72,191,132,.1); padding:1.5rem; box-shadow:0 4px 16px rgba(42,71,71,.03); animation:fadeUp .5s .1s ease both; }
    .resena-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:.8rem; flex-wrap:wrap; gap:.5rem; }
    .cliente-info { display:flex; align-items:center; gap:.8rem; }
    .cliente-avatar { width:40px; height:40px; border-radius:50%; background:var(--off-white); color:var(--text-muted); font-family:'Syne',sans-serif; font-weight:700; font-size:.9rem; display:flex; align-items:center; justify-content:center; overflow:hidden; border:1px solid rgba(42,71,71,.1); }
    .cliente-avatar img { width:100%; height:100%; object-fit:cover; }
    .cliente-nombre { font-family:'Syne',sans-serif; font-weight:700; color:var(--green-dark); font-size:.95rem; }
    .servicio-tag { font-size:.75rem; color:var(--text-muted); font-weight:300; margin-top:.1rem; }
    .resena-stars { color:#fbbf24; font-size:1.1rem; letter-spacing:2px; }
    .resena-fecha { font-size:.75rem; color:var(--text-muted); font-weight:300; }
    .resena-comentario { font-size:.9rem; color:var(--text); line-height:1.6; font-weight:300; background:rgba(97,208,149,.04); padding:1rem; border-radius:12px; border-left:3px solid var(--green-light); margin-top:.5rem; white-space:pre-line; }

    .empty { text-align:center; padding:3rem 1rem; color:var(--text-muted); animation:fadeUp .5s .1s ease both; }
    .empty-icon { font-size:2.5rem; margin-bottom:.8rem; }
    .empty p { font-size:.9rem; font-weight:300; }

    @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
  </style>
</head>
<body>
<div class="bg-dots"></div>

<nav>
  <a href="../index.php" class="logo">Repara<span>Tech</span></a>
  <div class="nav-right">
    <a href="javascript:history.back()" class="btn-nav">← Volver</a>
    <a href="perfil.php" class="nav-avatar">
      <?php if($usuario_foto): ?><img src="<?= $usuario_foto ?>" alt="foto"><?php else: ?><?= $usuario_inicial ?><?php endif; ?>
    </a>
  </div>
</nav>

<div class="page">
  <div class="header-card">
    <div class="tecnico-avatar">
      <?php if($tecnicoInfo['foto']): ?>
        <img src="<?= htmlspecialchars($tecnicoInfo['foto']) ?>" alt="Técnico">
      <?php else: ?>
        <?= strtoupper(substr($tecnicoInfo['nombre_usuario'], 0, 1)) ?>
      <?php endif; ?>
    </div>
    <div class="tecnico-info">
      <h1 class="tecnico-nombre">Reseñas de <?= htmlspecialchars($tecnicoInfo['nombre_usuario']) ?></h1>
      <div class="tecnico-stats">
        <?php if ($calificacion_promedio > 0): ?>
          <span class="stars-badge">⭐ <?= $calificacion_promedio ?> / 5</span>
          <span><?= count($resenas) ?> reseñas en total</span>
        <?php else: ?>
          <span>Aún no tiene reseñas</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="resenas-list">
    <?php if (empty($resenas)): ?>
      <div class="empty">
        <div class="empty-icon">⭐</div>
        <p>Este técnico aún no ha recibido reseñas de sus clientes.</p>
      </div>
    <?php else: ?>
      <?php foreach ($resenas as $r): ?>
        <div class="resena-card">
          <div class="resena-header">
            <div class="cliente-info">
              <div class="cliente-avatar">
                <?php if($r['cliente_foto']): ?>
                  <img src="<?= htmlspecialchars($r['cliente_foto']) ?>" alt="">
                <?php else: ?>
                  <?= strtoupper(substr($r['cliente'], 0, 1)) ?>
                <?php endif; ?>
              </div>
              <div>
                <div class="cliente-nombre"><?= htmlspecialchars($r['cliente']) ?></div>
                <div class="servicio-tag">Servicio: <?= htmlspecialchars($r['servicio']) ?></div>
              </div>
            </div>
            <div style="text-align:right;">
              <div class="resena-stars"><?= str_repeat('★', $r['calificacion']) ?><span style="color:#e5e7eb;"><?= str_repeat('★', 5 - $r['calificacion']) ?></span></div>
              <div class="resena-fecha"><?= date('d M Y', strtotime($r['creado_en'])) ?></div>
            </div>
          </div>
          <?php if (!empty($r['comentario'])): ?>
            <div class="resena-comentario"><?= htmlspecialchars($r['comentario']) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
</body>
</html>

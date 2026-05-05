<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'usuario') {
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../modelo/Disputa.php';

$usuarioId = (int)$_SESSION['id'];
$modeloDisputa = new Disputa();
$disputas = $modeloDisputa->obtenerDisputasPorUsuario($usuarioId);

$nombre  = htmlspecialchars($_SESSION['nombre'] ?? '');
$foto    = !empty($_SESSION['foto']) ? htmlspecialchars($_SESSION['foto']) : '';
$inicial = strtoupper(substr($nombre, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis Disputas — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --pink: #E0BAD7; --green-light: #61D095; --green-mid: #48BF84; --green-sea: #439775;
      --green-dark: #2A4747; --white: #FAFAF8; --off-white: #F2F0EC; --text: #1a2a2a; --text-muted: #4a6a6a;
      --danger: #dc2626; --danger-bg: #fee2e2;
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
    .page { padding: 6.5rem 5% 3rem; max-width: 800px; margin: 0 auto; position: relative; z-index: 1; }
    
    /* HERO */
    .hero { margin-bottom: 2rem; animation: fadeUp .5s ease both; }
    .hero h1 { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; color: var(--danger); margin-bottom: .5rem; }
    .hero p { color: var(--text-muted); font-size: .95rem; }
    
    /* CARDS */
    .disputas-list { display: flex; flex-direction: column; gap: 1.2rem; }
    .card { background: var(--white); border-radius: 20px; border: 1px solid rgba(220,38,38,.1); box-shadow: 0 4px 20px rgba(220,38,38,.03); padding: 1.5rem; animation: fadeUp .5s .1s ease both; display: flex; flex-direction: column; gap: 1rem; }
    
    .card-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid rgba(72,191,132,.1); padding-bottom: 1rem; }
    .card-title { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; color: var(--green-dark); }
    .card-subtitle { font-size: .85rem; color: var(--text-muted); margin-top: .3rem; }
    
    .badge { display: inline-block; padding: .3rem .8rem; border-radius: 100px; font-size: .75rem; font-weight: 700; }
    .badge-abierta { background: #fee2e2; color: #dc2626; }
    .badge-en_revision { background: #fef3c7; color: #d97706; }
    .badge-resuelta { background: #dcfce7; color: #16a34a; }
    .badge-cerrada { background: #f3f4f6; color: #4b5563; }
    
    .card-body { font-size: .9rem; color: var(--text); line-height: 1.6; }
    .card-body strong { color: var(--green-dark); }
    
    .admin-response { background: rgba(97,208,149,.1); border-left: 4px solid var(--green-mid); padding: 1rem; border-radius: 0 12px 12px 0; margin-top: 1rem; font-size: .85rem; }
    .admin-response-title { font-family: 'Syne', sans-serif; font-weight: 700; color: var(--green-dark); margin-bottom: .4rem; display: flex; align-items: center; gap: .5rem; }
    
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
      <a href="mis-reservas.php" class="btn-nav">📅 Mis Reservas</a>
      <a href="perfil.php" class="nav-avatar">
        <?php if($foto): ?><img src="<?= $foto ?>" alt="foto"><?php else: ?><?= $inicial ?><?php endif; ?>
      </a>
    </div>
  </nav>

  <div class="page">
    <div class="hero">
      <h1>Mis <em>Disputas</em> ⚠️</h1>
      <p>Revisa el estado de tus reclamos y la respuesta del equipo de soporte.</p>
    </div>

    <?php if (empty($disputas)): ?>
      <div class="empty anim-1">
        <div class="empty-icon">🛡️</div>
        <p>No tienes disputas ni reclamos abiertos.<br>¡Esperamos que todos tus servicios hayan sido de tu agrado!</p>
      </div>
    <?php else: ?>
      <div class="disputas-list">
        <?php foreach ($disputas as $d): ?>
          <div class="card">
            <div class="card-header">
              <div>
                <div class="card-title">Reserva #<?= $d['reserva_id'] ?> — <?= htmlspecialchars($d['servicio']) ?></div>
                <div class="card-subtitle">
                  Motivo: <strong><?= htmlspecialchars($d['motivo']) ?></strong><br>
                  Creada el: <?= date('d M Y', strtotime($d['creado_en'])) ?>
                </div>
              </div>
              <span class="badge badge-<?= $d['estado'] ?>"><?= ucfirst(str_replace('_', ' ', $d['estado'])) ?></span>
            </div>
            
            <div class="card-body">
              <strong>Tu reporte:</strong><br>
              <?= nl2br(htmlspecialchars($d['descripcion'])) ?>
            </div>

            <?php if (!empty($d['admin_respuesta'])): ?>
              <div class="admin-response">
                <div class="admin-response-title">🎧 Respuesta de Soporte</div>
                <?= nl2br(htmlspecialchars($d['admin_respuesta'])) ?>
              </div>
            <?php elseif (in_array($d['estado'], ['abierta', 'en_revision'])): ?>
              <div class="admin-response" style="background:#fef3c7; border-color:#f59e0b; color:#92400e;">
                <div class="admin-response-title" style="color:#b45309;">⏳ En evaluación</div>
                Nuestro equipo está revisando tu caso. Pronto recibirás una respuesta.
              </div>
            <?php endif; ?>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</body>
</html>

<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../modelo/Servicio.php';

$reservaId = (int)($_GET['reserva'] ?? 0);
if (!$reservaId) {
    header('Location: mis-reservas.php');
    exit();
}

$modeloServicio = new Servicio();
$usuarioId = (int)$_SESSION['id'];
$reservas = $modeloServicio->obtenerReservasUsuario($usuarioId);

// Buscar la reserva específica
$reservaActual = null;
foreach ($reservas as $r) {
    if ($r['id'] === $reservaId) {
        $reservaActual = $r;
        break;
    }
}

if (!$reservaActual) {
    header('Location: mis-reservas.php?error=Reserva no encontrada o no autorizada.');
    exit();
}

$msg_err = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$nombre  = htmlspecialchars($_SESSION['nombre'] ?? '');
$foto    = !empty($_SESSION['foto']) ? htmlspecialchars($_SESSION['foto']) : '';
$inicial = strtoupper(substr($nombre, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear Disputa — ReparaTech</title>
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
    .page { padding: 6.5rem 5% 3rem; max-width: 600px; margin: 0 auto; position: relative; z-index: 1; }
    
    /* HERO */
    .hero { text-align: center; margin-bottom: 2rem; animation: fadeUp .5s ease both; }
    .hero h1 { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; color: var(--danger); margin-bottom: .5rem; }
    .hero p { color: var(--text-muted); font-size: .95rem; }
    
    /* ALERTS */
    .alert { padding: .9rem 1.2rem; border-radius: 14px; font-size: .88rem; font-weight: 500; margin-bottom: 1.5rem; }
    .alert-err { background: var(--danger-bg); color: var(--danger); border: 1px solid #fecaca; }
    
    /* CARD */
    .card { background: var(--white); border-radius: 24px; border: 1px solid rgba(220,38,38,.15); box-shadow: 0 4px 20px rgba(220,38,38,.05); overflow: hidden; animation: fadeUp .5s .1s ease both; }
    .card-header { background: rgba(220,38,38,.03); padding: 1.2rem 1.6rem; border-bottom: 1px solid rgba(220,38,38,.1); }
    .card-title { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; color: var(--text); display: flex; align-items: center; justify-content: space-between; }
    .card-subtitle { font-size: .8rem; color: var(--text-muted); margin-top: .2rem; }
    .card-body { padding: 1.5rem 1.6rem; }
    
    /* FORM */
    .form-group { margin-bottom: 1.2rem; display: flex; flex-direction: column; gap: .4rem; }
    label { font-family: 'Syne', sans-serif; font-size: .8rem; font-weight: 700; color: var(--text); letter-spacing: .3px; text-transform: uppercase; }
    
    select, textarea {
      width: 100%; background: var(--off-white); border: 1.5px solid rgba(72,191,132,.2);
      border-radius: 14px; padding: .85rem 1.1rem; font-family: 'DM Sans', sans-serif;
      font-size: .92rem; color: var(--text); outline: none; transition: all .25s; resize: vertical;
    }
    select:focus, textarea:focus { border-color: var(--danger); background: var(--white); box-shadow: 0 0 0 4px rgba(220,38,38,.1); }
    
    .btn-submit { width: 100%; background: var(--danger); color: var(--white); font-family: 'Syne', sans-serif; font-weight: 700; font-size: .95rem; padding: .85rem; border-radius: 100px; border: none; cursor: pointer; transition: all .25s; box-shadow: 0 8px 20px rgba(220,38,38,.2); margin-top: 1rem; }
    .btn-submit:hover { background: #b91c1c; transform: translateY(-2px); box-shadow: 0 12px 28px rgba(220,38,38,.25); }
    
    .btn-back { display: block; text-align: center; margin-top: 1rem; font-size: .85rem; color: var(--text-muted); text-decoration: none; font-weight: 500; transition: color .2s; }
    .btn-back:hover { color: var(--text); }
    
    @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>
  <nav>
    <a href="../index.php" class="logo">Repara<span>Tech</span></a>
    <div class="nav-right">
      <a href="mis-reservas.php" class="btn-nav">← Volver a reservas</a>
      <a href="perfil.php" class="nav-avatar">
        <?php if($foto): ?><img src="<?= $foto ?>" alt="foto"><?php else: ?><?= $inicial ?><?php endif; ?>
      </a>
    </div>
  </nav>

  <div class="page">
    <div class="hero">
      <h1>Crear <em>Disputa</em> ⚠️</h1>
      <p>Reporta un problema con tu servicio</p>
    </div>

    <?php if ($msg_err): ?>
      <div class="alert alert-err"><?= $msg_err ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <div class="card-title">
          <span>Detalles del servicio</span>
          <span style="font-size:.85rem; color:var(--text-muted);">#<?= $reservaActual['id'] ?></span>
        </div>
        <div class="card-subtitle">
          <?= htmlspecialchars($reservaActual['servicio']) ?> con <?= htmlspecialchars($reservaActual['tecnico']) ?><br>
          📅 <?= date('d M Y', strtotime($reservaActual['fecha'])) ?>
        </div>
      </div>
      <div class="card-body">
        <form action="../controlador/ControladorDisputa.php" method="POST">
          <input type="hidden" name="accion" value="crear">
          <input type="hidden" name="reserva_id" value="<?= $reservaActual['id'] ?>">

          <div class="form-group">
            <label for="motivo">Motivo de la disputa</label>
            <select name="motivo" id="motivo" required>
              <option value="">Selecciona un motivo...</option>
              <option value="Trabajo incompleto o mal hecho">Trabajo incompleto o mal hecho</option>
              <option value="El técnico no se presentó">El técnico no se presentó</option>
              <option value="Cobro indebido">Cobro indebido</option>
              <option value="Daños a la propiedad">Daños a la propiedad</option>
              <option value="Comportamiento inadecuado">Comportamiento inadecuado</option>
              <option value="Otro">Otro</option>
            </select>
          </div>

          <div class="form-group">
            <label for="descripcion">Descripción detallada</label>
            <textarea name="descripcion" id="descripcion" rows="5" placeholder="Explica con detalle lo sucedido para que nuestro equipo pueda evaluar el caso..." required></textarea>
          </div>

          <button type="submit" class="btn-submit" onclick="return confirm('¿Estás seguro de enviar esta disputa? Nuestro equipo de soporte la revisará.')">Enviar Disputa</button>
        </form>
        <a href="mis-reservas.php" class="btn-back">Cancelar</a>
      </div>
    </div>
  </div>

</body>
</html>

<?php
// Vista Admin - Disputas
$nombre  = htmlspecialchars($_SESSION['nombre'] ?? 'Administrador');
$foto    = !empty($_SESSION['foto']) ? htmlspecialchars($_SESSION['foto']) : '';
$inicial = strtoupper(substr($nombre, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disputas — ReparaTech Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --green-light:#61D095; --green-sea:#439775; --green-dark:#2A4747; --white:#FAFAF8; --off-white:#F2F0EC; --text:#1a2a2a; --text-muted:#4a6a6a; }
        *,*::before,*::after { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--off-white); color:var(--text); display:flex; min-height:100vh; }
        
        .sidebar { width:240px; background:var(--white); border-right:1px solid rgba(72,191,132,.12); display:flex; flex-direction:column; padding:1.8rem 0; position:fixed; height:100vh; }
        .logo { font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:800; color:var(--green-dark); text-decoration:none; padding:0 1.5rem; margin-bottom:2rem; }
        .logo span { color:var(--green-light); }
        .nav-links { display:flex; flex-direction:column; gap:.25rem; padding:0 .8rem; flex:1; }
        .nav-link { display:flex; align-items:center; gap:.8rem; padding:.75rem 1rem; text-decoration:none; color:var(--text-muted); font-weight:500; font-size:.88rem; border-radius:12px; }
        .nav-link:hover { background:rgba(97,208,149,.1); color:var(--green-dark); }
        .nav-link.active { background:var(--green-dark); color:var(--white); font-weight:700; }
        
        .main-content { flex:1; margin-left:240px; padding:2rem 2.5rem; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; }
        .top-bar h1 { font-family:'Syne',sans-serif; font-size:1.7rem; font-weight:800; color:var(--green-dark); }
        
        .card { background:var(--white); border-radius:20px; padding:1.5rem; box-shadow:0 4px 20px rgba(42,71,71,.05); }
        
        table { width:100%; border-collapse:collapse; margin-top:1rem; font-size:.9rem; }
        th, td { padding:1rem; text-align:left; border-bottom:1px solid rgba(72,191,132,.1); }
        th { font-family:'Syne',sans-serif; font-weight:700; color:var(--green-dark); text-transform:uppercase; font-size:.75rem; letter-spacing:1px; }
        tr:hover { background:rgba(97,208,149,.03); }
        
        .badge { padding:.25rem .6rem; border-radius:100px; font-size:.75rem; font-weight:700; }
        .badge-abierta { background:#fee2e2; color:#dc2626; }
        .badge-en_revision { background:#fef3c7; color:#d97706; }
        .badge-resuelta { background:#dcfce7; color:#16a34a; }
        .badge-cerrada { background:#f3f4f6; color:#4b5563; }
        
        select { padding:.4rem; border-radius:6px; border:1px solid #ccc; font-family:inherit; }
        .btn-sm { padding:.4rem .8rem; background:var(--green-light); color:var(--green-dark); border:none; border-radius:6px; font-weight:700; cursor:pointer; }
        .btn-sm:hover { background:var(--green-mid); }
    </style>
</head>
<body>

<aside class="sidebar">
  <a href="#" class="logo">Repara<span>Tech</span></a>
  <div class="nav-links" style="margin-top:2rem;">
    <a href="admin.php?accion=dashboard" class="nav-link">📊 Dashboard</a>
    <a href="admin.php?accion=usuarios" class="nav-link">👥 Usuarios</a>
    <a href="admin.php?accion=tecnicos" class="nav-link">🔧 Técnicos</a>
    <a href="admin.php?accion=reservas" class="nav-link">📅 Reservas</a>
    <a href="admin.php?accion=categorias" class="nav-link">📁 Categorías</a>
    <a href="admin.php?accion=retiros" class="nav-link">💰 Pagos</a>
    <a href="admin.php?accion=disputas" class="nav-link active">⚠️ Disputas</a>
  </div>
</aside>

<main class="main-content">
  <div class="top-bar">
    <h1>Gestión de Disputas</h1>
  </div>

  <div class="card">
    <?php if (empty($disputas)): ?>
      <p style="text-align:center; padding:2rem; color:var(--text-muted);">No hay disputas registradas.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Reserva</th>
            <th>Cliente / Técnico</th>
            <th>Motivo</th>
            <th>Estado</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($disputas as $d): ?>
          <tr>
            <td>#<?= $d['id'] ?></td>
            <td>
              <strong>#<?= $d['reserva_id'] ?></strong><br>
              <span style="font-size:.8rem; color:var(--text-muted);"><?= htmlspecialchars($d['servicio']) ?></span>
            </td>
            <td>
              <div style="font-size:.85rem;"><strong>C:</strong> <?= htmlspecialchars($d['cliente']) ?> (<?= htmlspecialchars($d['cliente_correo']) ?>)</div>
              <div style="font-size:.85rem;"><strong>T:</strong> <?= htmlspecialchars($d['tecnico']) ?> (<?= htmlspecialchars($d['tecnico_correo']) ?>)</div>
            </td>
            <td>
              <strong><?= htmlspecialchars($d['motivo']) ?></strong><br>
              <span style="font-size:.8rem; color:var(--text-muted); display:block; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($d['descripcion']) ?>">
                <?= htmlspecialchars($d['descripcion']) ?>
              </span>
            </td>
            <td>
              <span class="badge badge-<?= $d['estado'] ?>"><?= ucfirst(str_replace('_', ' ', $d['estado'])) ?></span>
            </td>
            <td>
              <a href="admin.php?accion=ver_disputa&id=<?= $d['id'] ?>" class="btn-sm" style="text-decoration:none;">Gestionar</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</main>

</body>
</html>

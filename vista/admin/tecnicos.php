<?php
$nombre = $_SESSION['nombre'] ?? 'Administrador';
$foto = !empty($_SESSION['foto']) ? $_SESSION['foto'] : '';
$inicial = strtoupper(substr($nombre, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Técnicos — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,700&display=swap" rel="stylesheet">
  <style>
    :root { --pink:#E0BAD7; --green-light:#61D095; --green-mid:#48BF84; --green-sea:#439775; --green-dark:#2A4747; --white:#FAFAF8; --off-white:#F2F0EC; --text:#1a2a2a; --text-muted:#4a6a6a; }
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'DM Sans',sans-serif; background:var(--off-white); color:var(--text); display:flex; min-height:100vh; }
    
    /* SIDEBAR */
    .sidebar { width:260px; background:var(--white); border-right:1px solid rgba(72,191,132,.15); display:flex; flex-direction:column; padding:2rem 0; position:fixed; height:100vh; z-index:100; box-shadow:4px 0 30px rgba(42,71,71,.05); }
    .logo { font-family:'Syne',sans-serif; font-size:1.6rem; font-weight:800; color:var(--green-dark); text-decoration:none; padding:0 2rem; margin-bottom:2.5rem; display:block; }
    .logo span { color:var(--green-light); }
    
    .nav-links { display:flex; flex-direction:column; gap:.5rem; padding:0 1rem; flex:1; }
    .nav-link { display:flex; align-items:center; gap:1rem; padding:.85rem 1rem; text-decoration:none; color:var(--text-muted); font-weight:500; border-radius:12px; transition:all .2s; }
    .nav-link:hover { background:rgba(97,208,149,.1); color:var(--green-dark); }
    .nav-link.active { background:var(--green-light); color:var(--green-dark); font-weight:700; box-shadow:0 4px 12px rgba(97,208,149,.2); }
    
    .sidebar-footer { padding:0 2rem; }
    .btn-logout { display:flex; align-items:center; gap:.8rem; color:#dc2626; text-decoration:none; font-weight:600; font-size:.9rem; padding:.8rem 0; transition:opacity .2s; }
    .btn-logout:hover { opacity:.7; }

    /* MAIN CONTENT */
    .main-content { flex:1; margin-left:260px; padding:2.5rem 4rem; }
    
    .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:3rem; }
    .header-title h1 { font-family:'Syne',sans-serif; font-size:2rem; font-weight:800; color:var(--green-dark); letter-spacing:-1px; }
    .header-title p { color:var(--text-muted); font-size:.95rem; margin-top:.3rem; }
    
    .admin-profile { display:flex; align-items:center; gap:1rem; background:var(--white); padding:.6rem 1.2rem; border-radius:100px; border:1px solid rgba(72,191,132,.15); box-shadow:0 4px 16px rgba(42,71,71,.04); }
    .admin-avatar { width:38px; height:38px; border-radius:50%; background:var(--green-dark); color:var(--white); display:flex; align-items:center; justify-content:center; font-family:'Syne',sans-serif; font-weight:700; overflow:hidden; }
    .admin-avatar img { width:100%; height:100%; object-fit:cover; }
    .admin-info { display:flex; flex-direction:column; }
    .admin-name { font-weight:700; font-size:.85rem; color:var(--green-dark); }
    .admin-role { font-size:.7rem; color:var(--green-sea); font-weight:600; text-transform:uppercase; letter-spacing:1px; }

    /* TABLES */
    .table-container { background:var(--white); border-radius:24px; padding:2rem; border:1px solid rgba(72,191,132,.1); box-shadow:0 8px 30px rgba(42,71,71,.04); overflow-x:auto; }
    table { width:100%; border-collapse:collapse; }
    th { text-align:left; padding:1rem; border-bottom:2px solid rgba(72,191,132,.1); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:700; }
    td { padding:1rem; border-bottom:1px solid rgba(72,191,132,.05); color:var(--text); font-size:.95rem; }
    tr:last-child td { border-bottom:none; }
    
    .badge { padding:.3rem .8rem; border-radius:100px; font-size:.8rem; font-weight:600; display:inline-block; }
    .badge.active { background:rgba(97,208,149,.15); color:var(--green-sea); }
    .badge.inactive { background:rgba(220,38,38,.1); color:#dc2626; }
    .badge.tecnico { background:rgba(168,85,247,.1); color:#9333ea; }
    
    .btn-action { border:none; padding:.4rem .8rem; border-radius:8px; cursor:pointer; font-weight:600; font-size:.8rem; transition:all .2s; display:inline-flex; align-items:center; gap:0.3rem; }
    .btn-ban { background:rgba(220,38,38,.1); color:#dc2626; }
    .btn-ban:hover { background:#dc2626; color:#fff; }
    .btn-unban { background:rgba(97,208,149,.15); color:var(--green-sea); }
    .btn-unban:hover { background:var(--green-sea); color:#fff; }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <a href="#" class="logo">Repara<span>Tech</span></a>
    
    <div class="nav-links">
      <a href="admin.php?accion=dashboard" class="nav-link">📊 Dashboard</a>
      <a href="admin.php?accion=usuarios" class="nav-link">👥 Usuarios</a>
      <a href="admin.php?accion=tecnicos" class="nav-link active">🔧 Técnicos</a>
      <a href="admin.php?accion=categorias" class="nav-link">📁 Categorías</a>
      <a href="admin.php?accion=retiros"     class="nav-link">💰 Pagos</a>
    </div>

    <div class="sidebar-footer">
      <a href="index.php?accion=logout" class="btn-logout">🚪 Cerrar Sesión</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main-content">
    
    <header class="header">
      <div class="header-title">
        <h1>Gestión de Técnicos</h1>
        <p>Administra los profesionales que ofrecen servicios</p>
      </div>
      
      <div class="admin-profile">
        <div class="admin-info">
          <span class="admin-name"><?= htmlspecialchars($nombre) ?></span>
          <span class="admin-role">Super Admin</span>
        </div>
        <div class="admin-avatar">
          <?php if($foto): ?><img src="<?= htmlspecialchars($foto) ?>" alt="Admin"><?php else: ?><?= $inicial ?><?php endif; ?>
        </div>
      </div>
    </header>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Técnico</th>
            <th>Estadísticas</th>
            <th>Membresía</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($tecnicos)): ?>
            <?php foreach ($tecnicos as $t): ?>
            <tr>
              <td>#<?= $t['id'] ?></td>
              <td>
                <strong><?= htmlspecialchars($t['nombre_usuario']) ?></strong><br>
                <span style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($t['correo_electronico']) ?></span>
              </td>
              <td>
                <?php 
                  $tasa = $t['total_servicios'] > 0 ? round(($t['completados'] / $t['total_servicios']) * 100) : 0; 
                ?>
                <div style="font-size:0.85rem;">
                  <strong>Éxito:</strong> <?= $tasa ?>% (<?= $t['completados'] ?>/<?= $t['total_servicios'] ?>)<br>
                  <strong>Reseñas:</strong> ⭐ <?= round((float)$t['calificacion'], 1) ?> (<?= $t['total_resenas'] ?>)
                </div>
              </td>
              <td>
                <?php if ($t['es_premium']): ?>
                  <span class="badge active">💎 Premium</span>
                  <?php if ($t['es_experto']): ?>
                    <span class="badge" style="background:rgba(251,191,36,.15); color:#b45309;">🌟 Experto</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge inactive" style="background:var(--off-white); color:var(--text-muted);">Normal</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($t['activo']): ?>
                  <span class="badge active">Activo</span>
                <?php else: ?>
                  <span class="badge inactive">Bloqueado</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                  <form method="POST" action="admin.php?accion=cambiar_estado_usuario" style="margin:0;">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="tipo" value="tecnicos">
                    <?php if ($t['activo']): ?>
                      <input type="hidden" name="estado" value="0">
                      <button type="submit" class="btn-action btn-ban">🚫 Bloquear</button>
                    <?php else: ?>
                      <input type="hidden" name="estado" value="1">
                      <button type="submit" class="btn-action btn-unban">✅ Activar</button>
                    <?php endif; ?>
                  </form>
                  
                  <?php if ($t['es_premium']): ?>
                    <form method="POST" action="admin.php?accion=certificar_experto" style="margin:0;">
                      <input type="hidden" name="id" value="<?= $t['id'] ?>">
                      <?php if ($t['es_experto']): ?>
                        <input type="hidden" name="es_experto" value="0">
                        <button type="submit" class="btn-action" style="background:rgba(220,38,38,.1); color:#dc2626;">Quitar Experto</button>
                      <?php else: ?>
                        <input type="hidden" name="es_experto" value="1">
                        <button type="submit" class="btn-action" style="background:rgba(251,191,36,.15); color:#b45309;">Certificar Experto</button>
                      <?php endif; ?>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align:center; padding:2rem; color:var(--text-muted);">No hay técnicos registrados aún.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
</body>
</html>

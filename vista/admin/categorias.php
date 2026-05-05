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
  <title>Gestión de Categorías — ReparaTech</title>
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

    /* GRID & CARDS */
    .categorias-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:1.5rem; margin-bottom:2rem; }
    .categoria-card { background:var(--white); border-radius:20px; padding:1.5rem; border:1px solid rgba(72,191,132,.1); box-shadow:0 8px 30px rgba(42,71,71,.04); display:flex; align-items:center; gap:1rem; position:relative; }
    .cat-icon { font-size:2.5rem; background:rgba(97,208,149,.15); width:60px; height:60px; border-radius:16px; display:flex; align-items:center; justify-content:center; }
    .cat-info { flex:1; }
    .cat-name { font-family:'Syne',sans-serif; font-weight:700; color:var(--green-dark); font-size:1.1rem; }
    
    .cat-actions { position:absolute; top:1rem; right:1rem; display:flex; gap:0.5rem; }
    .btn-icon { background:none; border:none; cursor:pointer; color:var(--text-muted); transition:color .2s; }
    .btn-icon:hover { color:var(--green-dark); }
    .btn-icon.delete:hover { color:#dc2626; }

    /* FORM CARD */
    .form-card { background:var(--white); border-radius:24px; padding:2rem; border:1px solid rgba(72,191,132,.1); box-shadow:0 8px 30px rgba(42,71,71,.04); max-width:500px; }
    .form-card h3 { font-family:'Syne',sans-serif; color:var(--green-dark); margin-bottom:1.5rem; font-size:1.3rem; }
    .form-group { margin-bottom:1rem; }
    .form-group label { display:block; font-size:.85rem; font-weight:600; color:var(--text-muted); margin-bottom:.4rem; }
    .form-control { width:100%; padding:0.8rem 1rem; border:2px solid rgba(72,191,132,.2); border-radius:12px; font-family:'DM Sans',sans-serif; transition:border-color .2s; }
    .form-control:focus { outline:none; border-color:var(--green-light); }
    .btn-submit { background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:700; border:none; padding:0.8rem 1.5rem; border-radius:12px; cursor:pointer; transition:all .2s; }
    .btn-submit:hover { background:var(--green-mid); color:#fff; }

  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <a href="#" class="logo">Repara<span>Tech</span></a>
    
    <div class="nav-links">
      <a href="admin.php?accion=dashboard" class="nav-link">📊 Dashboard</a>
      <a href="admin.php?accion=usuarios" class="nav-link">👥 Usuarios</a>
      <a href="admin.php?accion=tecnicos" class="nav-link">🔧 Técnicos</a>
      <a href="admin.php?accion=categorias" class="nav-link active">📁 Categorías</a>
      <a href="admin.php?accion=retiros" class="nav-link">💰 Pagos</a>
    </div>

    <div class="sidebar-footer">
      <a href="index.php?accion=logout" class="btn-logout">🚪 Cerrar Sesión</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main-content">
    
    <header class="header">
      <div class="header-title">
        <h1>Gestión de Categorías</h1>
        <p>Añade o modifica los servicios disponibles</p>
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

    <div class="categorias-grid">
      <?php foreach ($categorias as $cat): ?>
      <div class="categoria-card">
        <div class="cat-icon"><?= htmlspecialchars($cat['icono']) ?></div>
        <div class="cat-info">
          <div class="cat-name"><?= htmlspecialchars($cat['nombre']) ?></div>
        </div>
        <div class="cat-actions">
          <a href="#" class="btn-icon" onclick="editarCategoria(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['nombre'])) ?>', '<?= htmlspecialchars(addslashes($cat['icono'])) ?>')">✏️</a>
          <a href="admin.php?accion=eliminar_categoria&id=<?= $cat['id'] ?>" class="btn-icon delete" onclick="return confirm('¿Seguro que deseas eliminar esta categoría?');">🗑️</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="form-card">
      <h3 id="form-title">Añadir Nueva Categoría</h3>
      <form method="POST" action="admin.php?accion=guardar_categoria">
        <input type="hidden" id="cat-id" name="id" value="">
        
        <div class="form-group">
          <label for="nombre">Nombre de la Categoría</label>
          <input type="text" id="cat-nombre" name="nombre" class="form-control" required placeholder="Ej. Electricidad">
        </div>
        
        <div class="form-group">
          <label for="icono">Icono (Emoji)</label>
          <input type="text" id="cat-icono" name="icono" class="form-control" required placeholder="Ej. 🔌">
        </div>
        
        <div style="display:flex; gap:1rem; margin-top:1.5rem;">
          <button type="submit" class="btn-submit">Guardar Categoría</button>
          <button type="button" class="btn-icon" id="btn-cancelar" style="display:none;" onclick="cancelarEdicion()">Cancelar</button>
        </div>
      </form>
    </div>

  </main>

  <script>
    function editarCategoria(id, nombre, icono) {
      document.getElementById('form-title').innerText = 'Editar Categoría';
      document.getElementById('cat-id').value = id;
      document.getElementById('cat-nombre').value = nombre;
      document.getElementById('cat-icono').value = icono;
      document.getElementById('btn-cancelar').style.display = 'block';
    }

    function cancelarEdicion() {
      document.getElementById('form-title').innerText = 'Añadir Nueva Categoría';
      document.getElementById('cat-id').value = '';
      document.getElementById('cat-nombre').value = '';
      document.getElementById('cat-icono').value = '';
      document.getElementById('btn-cancelar').style.display = 'none';
    }
  </script>
</body>
</html>

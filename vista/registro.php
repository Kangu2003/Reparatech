<?php $rolPreseleccionado = $_GET['rol'] ?? 'usuario'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500&display=swap" rel="stylesheet">
  <style>
    :root { --pink:#E0BAD7; --green-light:#61D095; --green-mid:#48BF84; --green-sea:#439775; --green-dark:#2A4747; --white:#FAFAF8; --off-white:#F2F0EC; --text:#1a2a2a; --text-muted:#4a6a6a; }
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'DM Sans',sans-serif; background:var(--off-white); display:flex; justify-content:center; align-items:center; min-height:100vh; padding:2rem 1rem; }
    .bg-pattern { position:fixed; inset:0; background-image:radial-gradient(var(--green-mid) 1px,transparent 1px); background-size:40px 40px; opacity:.07; z-index:0; }
    .card { background:var(--white); width:100%; max-width:460px; padding:2.5rem; border-radius:32px; box-shadow:0 40px 100px rgba(42,71,71,.15); position:relative; z-index:1; border:1px solid rgba(72,191,132,.1); animation:fadeUp .5s ease both; }
    .logo { font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:800; color:var(--green-dark); text-align:center; margin-bottom:.3rem; text-decoration:none; display:block; }
    .logo span { color:var(--green-light); }
    .subtitle { text-align:center; color:var(--text-muted); font-size:.9rem; margin-bottom:2rem; font-weight:300; }

    /* ROL SELECTOR */
    .rol-selector { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:1.8rem; }
    .rol-option { display:none; }
    .rol-label { display:flex; flex-direction:column; align-items:center; gap:.5rem; padding:1.1rem .8rem; border-radius:18px; cursor:pointer; border:2px solid rgba(72,191,132,.2); background:var(--off-white); transition:all .25s; text-align:center; }
    .rol-label:hover { border-color:var(--green-mid); background:rgba(97,208,149,.06); }
    .rol-option:checked + .rol-label { border-color:var(--green-light); background:rgba(97,208,149,.1); box-shadow:0 0 0 4px rgba(97,208,149,.12); }
    .rol-emoji { font-size:2rem; }
    .rol-title { font-family:'Syne',sans-serif; font-weight:700; font-size:.88rem; color:var(--green-dark); }
    .rol-desc  { font-size:.72rem; color:var(--text-muted); font-weight:300; line-height:1.4; }

    .form-group { margin-bottom:1.1rem; }
    label { display:block; font-family:'Syne',sans-serif; font-size:.78rem; font-weight:700; color:var(--green-dark); margin-bottom:.4rem; text-transform:uppercase; letter-spacing:.3px; }
    input { width:100%; border:1.5px solid rgba(72,191,132,.2); border-radius:14px; padding:.85rem 1.1rem; font-family:'DM Sans',sans-serif; font-size:.92rem; background:var(--off-white); outline:none; transition:all .25s; box-sizing:border-box; }
    input:focus { border-color:var(--green-light); background:var(--white); box-shadow:0 0 0 4px rgba(97,208,149,.1); }

    .btn { width:100%; background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:700; font-size:.95rem; padding:1rem; border-radius:100px; border:none; cursor:pointer; margin-top:1.2rem; transition:all .25s; }
    .btn:hover { background:var(--green-mid); transform:translateY(-2px); }
    .btn.tecnico-btn { background:var(--green-dark); color:var(--white); }
    .btn.tecnico-btn:hover { background:#1a2a2a; }

    .error-msg { background:#fee2e2; color:#dc2626; padding:.8rem 1rem; border-radius:12px; font-size:.85rem; margin-bottom:1.4rem; text-align:center; border:1px solid #fecaca; }
    .footer-link { text-align:center; margin-top:1.4rem; font-size:.85rem; color:var(--text-muted); }
    .footer-link a { color:var(--green-sea); text-decoration:none; font-weight:600; }

    @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
  </style>
</head>
<body>
  <div class="bg-pattern"></div>
  <div class="card">
    <a href="../index.php" class="logo">Repara<span>Tech</span></a>
    <p class="subtitle">Crea tu cuenta</p>

    <?php if (isset($error)): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="../index.php?accion=registro" method="POST">

      <!-- SELECTOR DE ROL -->
      <div class="rol-selector">
        <div>
          <input type="radio" name="rol" id="rol-usuario" value="usuario" class="rol-option" <?= $rolPreseleccionado === "usuario" ? "checked" : "" ?>>
          <label for="rol-usuario" class="rol-label">
            <span class="rol-emoji">👤</span>
            <span class="rol-title">Usuario</span>
            <span class="rol-desc">Busco y contrato servicios</span>
          </label>
        </div>
        <div>
          <input type="radio" name="rol" id="rol-tecnico" value="tecnico" class="rol-option" <?= $rolPreseleccionado === "tecnico" ? "checked" : "" ?>>
          <label for="rol-tecnico" class="rol-label">
            <span class="rol-emoji">🛠️</span>
            <span class="rol-title">Técnico</span>
            <span class="rol-desc">Ofrezco mis servicios profesionales</span>
          </label>
        </div>
      </div>

      <div class="form-group">
        <label>Nombre de usuario</label>
        <input type="text" name="nombre_usuario" placeholder="Ej: Carlos01" required>
      </div>

      <div class="form-group">
        <label>Correo electrónico</label>
        <input type="email" name="correo_electronico" placeholder="ejemplo@correo.com" required>
      </div>

      <div class="form-group">
        <label>Teléfono</label>
        <input type="tel" name="telefono" placeholder="+57 300 000 0000" value="+57 " required>
      </div>

      <div class="form-group">
        <label>Contraseña</label>
        <input type="password" name="contrasena" placeholder="Mínimo 6 caracteres" required>
      </div>

      <button type="submit" class="btn" id="btn-registro">Crear cuenta como Usuario</button>
    </form>

    <div class="footer-link">
      <p>¿Ya tienes cuenta? <a href="../index.php?accion=login">Inicia sesión aquí</a></p>
    </div>
  </div>

  <script>
    // Actualizar texto del botón al cargar si hay rol preseleccionado
    window.addEventListener('DOMContentLoaded', () => {
      const esTecnico = document.getElementById('rol-tecnico').checked;
      if (esTecnico) {
        btn.textContent = 'Crear cuenta como Técnico';
        btn.classList.add('tecnico-btn');
      }
    });
    const radios = document.querySelectorAll('.rol-option');
    const btn    = document.getElementById('btn-registro');
    radios.forEach(r => r.addEventListener('change', () => {
      const esTecnico = document.getElementById('rol-tecnico').checked;
      btn.textContent = esTecnico ? 'Crear cuenta como Técnico' : 'Crear cuenta como Usuario';
      btn.classList.toggle('tecnico-btn', esTecnico);
    }));
  </script>
</body>
</html>
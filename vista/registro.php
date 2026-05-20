<?php $rolPreseleccionado = $_GET['rol'] ?? 'usuario'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --green-light: #61D095;
      --green-mid: #48BF84;
      --green-sea: #439775;
      --green-dark: #2A4747;
      --white: #FAFAF8;
      --off-white: #F2F0EC;
      --text: #1a2a2a;
      --text-muted: #4a6a6a;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    /* FULL SCREEN BACKGROUND */
    body {
      font-family: 'DM Sans', sans-serif;
      color: var(--text);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem 1rem;
      position: relative;
      overflow-x: hidden;
    }

    body::before {
      content: '';
      position: fixed;
      inset: -5%; /* slightly larger to allow blur without edge artifacts */
      background-image: url('../img/register-hero.png');
      background-size: cover;
      background-position: center;
      filter: blur(8px) brightness(0.7);
      z-index: -2;
    }

    body::after {
      content: '';
      position: fixed;
      inset: 0;
      background: linear-gradient(135deg, rgba(42,71,71,0.8) 0%, rgba(23,42,42,0.95) 100%);
      z-index: -1;
    }

    /* FLOATING GLASS CARD */
    .glass-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(255, 255, 255, 0.4);
      border-radius: 32px;
      padding: 3rem;
      width: 100%;
      max-width: 500px;
      box-shadow: 0 40px 100px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 1);
      animation: floatUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
      position: relative;
      z-index: 10;
    }

    .logo {
      font-family: 'Syne', sans-serif;
      font-size: 2rem;
      font-weight: 800;
      color: var(--green-dark);
      text-decoration: none;
      display: block;
      text-align: center;
      margin-bottom: 0.5rem;
    }
    .logo span { color: var(--green-light); }

    .subtitle {
      text-align: center;
      color: var(--text-muted);
      font-size: 0.95rem;
      margin-bottom: 2rem;
    }

    /* TABBED ROLE SELECTOR */
    .tabs-container {
      background: rgba(72,191,132,0.1);
      border-radius: 100px;
      padding: 0.3rem;
      display: flex;
      margin-bottom: 2rem;
      position: relative;
    }

    .tab-input { display: none; }

    .tab-label {
      flex: 1;
      text-align: center;
      padding: 0.8rem 1rem;
      font-family: 'Syne', sans-serif;
      font-size: 0.9rem;
      font-weight: 700;
      color: var(--text-muted);
      cursor: pointer;
      border-radius: 100px;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      position: relative;
      z-index: 2;
    }

    .tab-label:hover {
      color: var(--green-dark);
    }

    .tab-input:checked + .tab-label {
      color: var(--white);
    }

    /* Magic slider background for tabs */
    .tab-slider {
      position: absolute;
      top: 0.3rem;
      bottom: 0.3rem;
      width: calc(50% - 0.3rem);
      background: var(--green-dark);
      border-radius: 100px;
      transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 1;
      box-shadow: 0 4px 15px rgba(42,71,71,0.2);
    }

    #rol-usuario:checked ~ .tab-slider {
      transform: translateX(0);
      background: var(--green-light);
    }

    #rol-tecnico:checked ~ .tab-slider {
      transform: translateX(100%);
      background: var(--green-dark);
    }

    #rol-usuario:checked + .tab-label {
      color: var(--green-dark);
    }

    /* FORM GROUPS */
    .form-group {
      margin-bottom: 1.2rem;
    }

    label {
      display: block;
      font-size: 0.78rem;
      font-weight: 700;
      margin-bottom: 0.4rem;
      font-family: 'Syne', sans-serif;
      color: var(--green-dark);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    input {
      width: 100%;
      border: 1.5px solid rgba(72,191,132,0.25);
      border-radius: 16px;
      padding: 1rem 1.2rem;
      font-size: 0.95rem;
      font-family: 'DM Sans', sans-serif;
      background: var(--off-white);
      color: var(--text);
      outline: none;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    input:focus {
      border-color: var(--green-mid);
      background: var(--white);
      box-shadow: 0 0 0 4px rgba(97,208,149,0.15);
      transform: translateY(-2px);
    }

    /* BUTTONS */
    .btn {
      width: 100%;
      background: linear-gradient(135deg, var(--green-light), var(--green-mid));
      color: var(--green-dark);
      font-family: 'Syne', sans-serif;
      font-size: 1.05rem;
      font-weight: 800;
      padding: 1.2rem;
      border-radius: 100px;
      border: none;
      cursor: pointer;
      margin-top: 1rem;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 10px 20px rgba(97,208,149,0.25);
    }

    .btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 30px rgba(97,208,149,0.35);
      background: linear-gradient(135deg, var(--green-mid), var(--green-sea));
      color: var(--white);
    }

    .btn.tecnico-btn {
      background: linear-gradient(135deg, var(--green-dark), #172a2a);
      color: var(--white);
      box-shadow: 0 10px 20px rgba(42, 71, 71, 0.3);
    }
    .btn.tecnico-btn:hover {
      box-shadow: 0 15px 30px rgba(42, 71, 71, 0.45);
    }

    .alert {
      padding: 1rem;
      border-radius: 14px;
      font-size: 0.85rem;
      margin-bottom: 1.5rem;
      font-weight: 500;
      background: #fef2f2;
      color: #dc2626;
      border: 1px solid #fecaca;
    }

    .form-footer {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      align-items: center;
      margin-top: 1.8rem;
      font-size: 0.95rem;
    }

    .form-footer a {
      color: var(--green-sea);
      text-decoration: none;
      font-weight: 700;
      transition: color 0.2s;
    }

    .form-footer a:hover {
      color: var(--green-dark);
      text-decoration: underline;
    }

    /* ANIMATIONS */
    @keyframes floatUp {
      from { opacity: 0; transform: translateY(40px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* RESPONSIVE */
    @media (max-width: 600px) {
      .glass-card {
        padding: 2rem 1.5rem;
        border-radius: 24px;
      }
      .logo {
        font-size: 1.8rem;
      }
      body {
        padding: 1rem;
      }
    }
  </style>
</head>
<body>

  <div class="glass-card">
    <a href="../index.php" class="logo">Repara<span>Tech</span></a>
    <p class="subtitle">Únete a la comunidad de servicios más grande.</p>

    <?php if (isset($error)): ?>
      <div class="alert">
        ⚠️ <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form action="../index.php?accion=registro" method="POST">

      <!-- SELECTOR DE ROL ESTILO TABS -->
      <div class="tabs-container">
        <input type="radio" name="rol" id="rol-usuario" value="usuario" class="tab-input" <?= $rolPreseleccionado === "usuario" ? "checked" : "" ?>>
        <label for="rol-usuario" class="tab-label">
          <span>👤 Cliente</span>
        </label>

        <input type="radio" name="rol" id="rol-tecnico" value="tecnico" class="tab-input" <?= $rolPreseleccionado === "tecnico" ? "checked" : "" ?>>
        <label for="rol-tecnico" class="tab-label">
          <span>🛠️ Técnico</span>
        </label>
        
        <!-- Animated slider -->
        <div class="tab-slider"></div>
      </div>

      <div class="form-group">
        <label>Nombre completo</label>
        <input type="text" name="nombre_usuario" placeholder="Ej: Carlos Mendoza" required>
      </div>

      <div class="form-group">
        <label>Correo electrónico</label>
        <input type="email" name="correo_electronico" placeholder="ejemplo@correo.com" required>
      </div>

      <div class="form-group">
        <label>Teléfono móvil</label>
        <input type="tel" name="telefono" placeholder="+57 300 000 0000" value="+57 " required>
      </div>

      <div class="form-group">
        <label>Contraseña</label>
        <input type="password" name="contrasena" placeholder="Crea una contraseña segura" required>
      </div>

      <button type="submit" class="btn" id="btn-registro">Crear cuenta</button>
    </form>

    <div class="form-footer">
      <span style="color: var(--text-muted);">¿Ya tienes una cuenta?</span>
      <a href="../index.php?accion=login">Inicia sesión</a>
    </div>

  </div>

  <script>
    const btn = document.getElementById('btn-registro');
    const rolUsuario = document.getElementById('rol-usuario');
    const rolTecnico = document.getElementById('rol-tecnico');

    function actualizarBoton() {
      if (rolTecnico.checked) {
        btn.textContent = 'Crear cuenta como Técnico';
        btn.classList.add('tecnico-btn');
      } else {
        btn.textContent = 'Crear cuenta como Cliente';
        btn.classList.remove('tecnico-btn');
      }
    }

    // Inicializar al cargar
    window.addEventListener('DOMContentLoaded', actualizarBoton);

    // Actualizar al cambiar
    rolUsuario.addEventListener('change', actualizarBoton);
    rolTecnico.addEventListener('change', actualizarBoton);
  </script>
</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — ReparaTech</title>
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

    body {
      font-family: 'DM Sans', sans-serif;
      background-color: var(--white);
      color: var(--text);
      min-height: 100vh;
      display: flex;
    }

    /* SPLIT LAYOUT */
    .split-layout {
      display: flex;
      width: 100%;
      min-height: 100vh;
    }

    /* LEFT PANEL (IMAGE) */
    .left-panel {
      flex: 1;
      background: linear-gradient(135deg, var(--green-dark) 0%, #172a2a 100%);
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 3rem;
      overflow: hidden;
    }

    /* Background image with overlay */
    .left-panel::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: url('../img/login-hero.png');
      background-size: cover;
      background-position: center;
      opacity: 0.6; /* Slight transparency to let the gradient show */
      mix-blend-mode: screen; /* Integrates the image with the dark green background beautifully */
    }

    /* Add a subtle pattern over the image for texture */
    .left-panel::after {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(var(--green-light) 1px, transparent 1px);
      background-size: 30px 30px;
      opacity: 0.05;
    }

    .brand-glass {
      position: relative;
      z-index: 10;
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      padding: 2.2rem;
      border-radius: 24px;
      max-width: 480px;
      animation: fadeRight 0.8s ease forwards;
    }

    .brand-glass h1 {
      font-family: 'Syne', sans-serif;
      font-size: 2.5rem;
      font-weight: 800;
      color: var(--white);
      margin-bottom: 0.5rem;
      line-height: 1.1;
    }

    .brand-glass h1 span {
      color: var(--green-light);
    }

    .brand-glass p {
      color: rgba(255, 255, 255, 0.8);
      font-size: 1rem;
      line-height: 1.5;
    }

    /* RIGHT PANEL (FORM) */
    .right-panel {
      flex: 1;
      max-width: 600px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 2rem;
      background: var(--white);
      position: relative;
    }

    .form-container {
      width: 100%;
      max-width: 380px;
      animation: fadeUp 0.6s ease forwards;
    }

    /* Mobile Logo (hidden on desktop) */
    .mobile-logo {
      display: none;
      font-family: 'Syne', sans-serif;
      font-size: 2rem;
      font-weight: 800;
      color: var(--green-dark);
      text-decoration: none;
      margin-bottom: 2rem;
      text-align: center;
    }
    .mobile-logo span { color: var(--green-light); }

    .form-header {
      margin-bottom: 2rem;
    }

    .form-header h2 {
      font-family: 'Syne', sans-serif;
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--green-dark);
      margin-bottom: 0.4rem;
    }

    .form-header p {
      color: var(--text-muted);
      font-size: 0.95rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
      position: relative;
    }

    label {
      display: block;
      font-size: 0.8rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
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

    input::placeholder {
      color: #9ca3af;
    }

    .btn-login {
      width: 100%;
      background: linear-gradient(135deg, var(--green-light), var(--green-mid));
      color: var(--green-dark);
      font-family: 'Syne', sans-serif;
      font-size: 1rem;
      font-weight: 800;
      padding: 1.1rem;
      border-radius: 100px;
      border: none;
      cursor: pointer;
      margin-top: 1rem;
      transition: all 0.3s ease;
      box-shadow: 0 10px 20px rgba(97,208,149,0.2);
    }

    .btn-login:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 25px rgba(97,208,149,0.3);
      background: linear-gradient(135deg, var(--green-mid), var(--green-sea));
      color: var(--white);
    }

    .btn-login:active {
      transform: translateY(0);
    }

    /* GOOGLE BUTTON */
    .divider {
      display: flex;
      align-items: center;
      text-align: center;
      margin: 1.5rem 0;
      color: var(--text-muted);
      font-size: 0.85rem;
    }
    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      border-bottom: 1px solid rgba(42,71,71,0.1);
    }
    .divider:not(:empty)::before { margin-right: .5em; }
    .divider:not(:empty)::after { margin-left: .5em; }

    .btn-google {
      width: 100%;
      background: var(--white);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.95rem;
      font-weight: 600;
      padding: 1rem;
      border-radius: 100px;
      border: 1px solid rgba(42,71,71,0.15);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.8rem;
      transition: all 0.3s ease;
      text-decoration: none;
      box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    }
    .btn-google:hover {
      background: var(--off-white);
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(0,0,0,0.06);
    }
    .btn-google svg {
      width: 20px;
      height: 20px;
    }

    .alert {
      padding: 1rem;
      border-radius: 12px;
      font-size: 0.85rem;
      margin-bottom: 1.5rem;
      font-weight: 500;
    }

    .alert-error {
      background: #fef2f2;
      color: #dc2626;
      border: 1px solid #fecaca;
    }

    .alert-success {
      background: rgba(97,208,149,0.12);
      color: var(--green-sea);
      border: 1px solid rgba(97,208,149,0.25);
    }

    .form-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 2rem;
      font-size: 0.85rem;
    }

    .form-footer a {
      color: var(--green-sea);
      text-decoration: none;
      font-weight: 700;
      transition: color 0.2s;
    }

    .form-footer a:hover {
      color: var(--green-dark);
    }

    .forgot-link {
      display: block;
      text-align: right;
      font-size: 0.8rem;
      color: var(--text-muted);
      text-decoration: none;
      margin-top: 0.5rem;
      font-weight: 500;
    }
    
    .forgot-link:hover {
      color: var(--green-sea);
      text-decoration: underline;
    }

    /* ANIMATIONS */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeRight {
      from { opacity: 0; transform: translateX(-20px); }
      to { opacity: 1; transform: translateX(0); }
    }

    /* RESPONSIVE DESIGN */
    @media (max-width: 900px) {
      .split-layout {
        flex-direction: column;
      }
      .left-panel {
        display: none; /* Hide image on mobile for cleaner look */
      }
      .right-panel {
        max-width: 100%;
        padding: 3rem 2rem;
      }
      .mobile-logo {
        display: block;
      }
    }
  </style>
</head>
<body>
  
  <div class="split-layout">
    
    <!-- LEFT PANEL: Image & Branding -->
    <div class="left-panel">
      <!-- Decorative top-left element if needed -->
      <div></div> 
      
      <div class="brand-glass">
        <h1>Repara<span>Tech</span></h1>
        <p>Conectando expertos con hogares inteligentes. Inicia sesión para gestionar tus servicios y clientes en un solo lugar.</p>
      </div>
    </div>

    <!-- RIGHT PANEL: Login Form -->
    <div class="right-panel">
      <div class="form-container">
        
        <a href="../index.php" class="mobile-logo">Repara<span>Tech</span></a>

        <div class="form-header">
          <h2>¡Bienvenido de nuevo!</h2>
          <p>Ingresa tus credenciales para continuar.</p>
        </div>

        <?php if (isset($error)): ?>
          <div class="alert alert-error">
            ⚠️ <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['recuperacion']) && $_GET['recuperacion'] === 'exito'): ?>
          <div class="alert alert-success">
            ✅ Contraseña actualizada con éxito. Ya puedes iniciar sesión.
          </div>
        <?php endif; ?>

        <form action="../index.php?accion=login" method="POST">
          <div class="form-group">
            <label>Correo Electrónico</label>
            <input type="email" name="correo_electronico" placeholder="ejemplo@correo.com" required>
          </div>
          
          <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="contrasena" placeholder="••••••••" required>
            <a href="recuperar_password.php" class="forgot-link">¿Olvidaste tu contraseña?</a>
          </div>
          
          <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>

        <div class="divider">o</div>

        <a href="../index.php?accion=login_google" class="btn-google">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
          </svg>
          Continuar con Google
        </a>

        <div class="form-footer">
          <span style="color: var(--text-muted);">¿No tienes cuenta?</span>
          <a href="registro.php">Regístrate gratis</a>
        </div>

      </div>
    </div>

  </div>

</body>
</html>
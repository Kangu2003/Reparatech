<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root { --green-light: #61D095; --green-mid: #48BF84; --green-dark: #2A4747; --white: #FAFAF8; --off-white: #F2F0EC; --text: #1a2a2a; }
    body { margin: 0; font-family: 'DM Sans', sans-serif; background-color: var(--off-white); display: flex; justify-content: center; align-items: center; min-height: 100vh; }
    .bg-pattern { position: absolute; inset: 0; background-image: radial-gradient(var(--green-mid) 1px, transparent 1px); background-size: 40px 40px; opacity: 0.1; }
    .login-container { background: var(--white); width: 100%; max-width: 400px; padding: 3rem; border-radius: 32px; box-shadow: 0 40px 100px rgba(42, 71, 71, 0.1); position: relative; z-index: 1; text-align: center; }
    .logo { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; color: var(--green-dark); text-decoration: none; display: block; margin-bottom: 2rem; }
    .logo span { color: var(--green-light); }
    .form-group { text-align: left; margin-bottom: 1.2rem; }
    label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem; font-family: 'Syne', sans-serif; }
    input { width: 100%; border: 1.5px solid rgba(72,191,132,0.2); border-radius: 14px; padding: 0.9rem; box-sizing: border-box; background: var(--off-white); outline: none; }
    .btn-login { width: 100%; background: var(--green-light); color: var(--green-dark); font-family: 'Syne', sans-serif; font-weight: 700; padding: 1rem; border-radius: 100px; border: none; cursor: pointer; margin-top: 1rem; }
    .error { color: #dc2626; font-size: 0.85rem; margin-bottom: 1rem; }
  </style>
</head>
<body>
  <div class="bg-pattern"></div>
  <div class="login-container">
    <a href="#" class="logo">Repara<span>Tech</span></a>

    <!-- ✅ $error sanitizado con htmlspecialchars() -->
    <?php if (isset($error)): ?>
      <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if (isset($_GET['recuperacion']) && $_GET['recuperacion'] === 'exito'): ?>
      <p style="color: #439775; font-size: 0.85rem; margin-bottom: 1rem; background: rgba(97,208,149,0.12); padding: 0.8rem; border-radius: 12px; border: 1px solid rgba(97,208,149,0.25);">
        ✅ Contraseña actualizada con éxito. Ya puedes iniciar sesión.
      </p>
    <?php endif; ?>

    <form action="../index.php?accion=login" method="POST">
      <div class="form-group">
        <label>Correo Electrónico</label>
        <input type="email" name="correo_electronico" required>
      </div>
      <div class="form-group">
        <label>Contraseña</label>
        <input type="password" name="contrasena" required>
      </div>
      <button type="submit" class="btn-login">Entrar</button>
    </form>

    <div style="margin-top: 1rem; font-size: 0.8rem;">
      <a href="recuperar_password.php" style="color: var(--text-muted); text-decoration: none;">¿Olvidaste tu contraseña?</a>
    </div>

    <p style="font-size: 0.8rem; margin-top: 1rem;">
      ¿No tienes cuenta? <a href="registro.php" style="color: var(--green-dark); font-weight: bold;">Regístrate</a>
    </p>
  </div>
</body>
</html>
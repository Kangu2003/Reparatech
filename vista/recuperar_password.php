<?php
/**
 * vista/recuperar_password.php
 * Vista para recuperar la contraseña mediante un código enviado por WhatsApp.
 */
session_start();
if (isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar Contraseña — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    :root { 
      --pink: #E0BAD7; 
      --green-light: #61D095; 
      --green-mid: #48BF84; 
      --green-sea: #439775; 
      --green-dark: #2A4747; 
      --white: #FAFAF8; 
      --off-white: #F2F0EC; 
      --text: #1a2a2a; 
      --text-muted: #4a6a6a; 
      --danger: #dc2626;
      --danger-bg: #fee2e2;
    }
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'DM Sans',sans-serif; background:var(--off-white); display:flex; justify-content:center; align-items:center; min-height:100vh; padding:2rem 1rem; }
    .bg-pattern { position:fixed; inset:0; background-image:radial-gradient(var(--green-mid) 1px,transparent 1px); background-size:40px 40px; opacity:.07; z-index:0; }
    
    .card { 
      background:var(--white); width:100%; max-width:440px; padding:2.5rem; 
      border-radius:32px; box-shadow:0 40px 100px rgba(42,71,71,.15); 
      position:relative; z-index:1; border:1px solid rgba(72,191,132,.1); 
      animation:fadeUp .5s ease both; 
    }
    
    .logo { font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:800; color:var(--green-dark); text-align:center; margin-bottom:.3rem; text-decoration:none; display:block; }
    .logo span { color:var(--green-light); }
    .subtitle { text-align:center; color:var(--text-muted); font-size:.9rem; margin-bottom:2rem; font-weight:300; line-height:1.5; }
    
    .form-group { margin-bottom:1.1rem; }
    label { display:block; font-family:'Syne',sans-serif; font-size:.78rem; font-weight:700; color:var(--green-dark); margin-bottom:.4rem; text-transform:uppercase; letter-spacing:.3px; }
    input { width:100%; border:1.5px solid rgba(72,191,132,.2); border-radius:14px; padding:.85rem 1.1rem; font-family:'DM Sans',sans-serif; font-size:.92rem; background:var(--off-white); outline:none; transition:all .25s; box-sizing:border-box; }
    input:focus { border-color:var(--green-light); background:var(--white); box-shadow:0 0 0 4px rgba(97,208,149,.1); }
    
    .btn { width:100%; background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:700; font-size:.95rem; padding:1rem; border-radius:100px; border:none; cursor:pointer; margin-top:1.2rem; transition:all .25s; display:flex; justify-content:center; align-items:center; gap:0.5rem; }
    .btn:hover { background:var(--green-mid); transform:translateY(-2px); }
    .btn:disabled { opacity:0.7; cursor:not-allowed; transform:none; }
    
    .spinner { display:none; width:18px; height:18px; border:2px solid rgba(42,71,71,.3); border-top-color:var(--green-dark); border-radius:50%; animation:spin .8s linear infinite; }
    .btn.loading .spinner { display:block; }
    .btn.loading .btn-text { display:none; }
    
    .alert { display:none; padding:.8rem 1rem; border-radius:12px; font-size:.85rem; margin-bottom:1.4rem; text-align:center; }
    .alert.error { display:block; background:var(--danger-bg); color:var(--danger); border:1px solid #fecaca; }
    .alert.success { display:block; background:rgba(97,208,149,.12); color:var(--green-sea); border:1px solid rgba(97,208,149,.25); }
    
    .footer-link { text-align:center; margin-top:1.4rem; font-size:.85rem; color:var(--text-muted); }
    .footer-link a { color:var(--green-sea); text-decoration:none; font-weight:600; }
    
    /* Code input styles */
    .code-inputs { display:flex; gap:0.5rem; justify-content:center; margin-bottom:1.5rem; }
    .code-inputs input { width:45px; height:55px; text-align:center; font-size:1.5rem; font-weight:700; padding:0; border-radius:12px; }
    
    @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Paneles */
    .panel { display: none; }
    .panel.active { display: block; animation: fadeUp 0.4s ease both; }
  </style>
</head>
<body>
  <div class="bg-pattern"></div>
  <div class="card">
    <a href="../index.php" class="logo">Repara<span>Tech</span></a>
    
    <div id="alert-box" class="alert"></div>

    <!-- PASO 1: SOLICITAR CÓDIGO -->
    <div id="panel-request" class="panel active">
      <p class="subtitle">Ingresa tu correo electrónico registrado para enviarte un código de recuperación por WhatsApp.</p>
      
      <form id="form-request">
        <div class="form-group">
          <label>Correo electrónico</label>
          <input type="email" id="correo_request" placeholder="ejemplo@correo.com" required>
        </div>
        <button type="submit" class="btn" id="btn-request">
          <span class="btn-text">Enviar código</span>
          <span class="spinner"></span>
        </button>
      </form>
    </div>

    <!-- PASO 2: VERIFICAR Y CAMBIAR CONTRASEÑA -->
    <div id="panel-verify" class="panel">
      <p class="subtitle">Hemos enviado un código de 6 dígitos a tu WhatsApp. Ingresa el código y tu nueva contraseña.</p>
      
      <form id="form-verify">
        <div class="form-group">
          <label style="text-align:center;">Código de Seguridad</label>
          <div class="code-inputs" id="code-inputs">
            <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
            <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
            <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
            <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
            <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
            <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
          </div>
          <input type="hidden" id="codigo_completo" name="codigo">
        </div>

        <div class="form-group">
          <label>Nueva contraseña</label>
          <input type="password" id="nueva_contrasena" placeholder="Mínimo 6 caracteres" required>
        </div>
        
        <div class="form-group">
          <label>Confirmar contraseña</label>
          <input type="password" id="confirmar_contrasena" placeholder="Repite tu contraseña" required>
        </div>

        <button type="submit" class="btn" id="btn-verify">
          <span class="btn-text">Cambiar contraseña</span>
          <span class="spinner"></span>
        </button>
      </form>
      <div class="footer-link" style="margin-top: 1rem;">
        <p><a href="#" id="btn-resend">Reenviar código</a></p>
      </div>
    </div>

    <div class="footer-link">
      <p>¿Recordaste tu contraseña? <a href="../index.php?accion=login">Inicia sesión</a></p>
    </div>
  </div>

  <script>
    const panelRequest = document.getElementById('panel-request');
    const panelVerify = document.getElementById('panel-verify');
    const alertBox = document.getElementById('alert-box');
    let correoUsuario = '';

    function showAlert(msg, isError = true) {
      alertBox.textContent = msg;
      alertBox.className = 'alert ' + (isError ? 'error' : 'success');
    }

    function hideAlert() {
      alertBox.className = 'alert';
      alertBox.textContent = '';
    }

    // Lógica para los inputs del código
    const codeInputs = document.querySelectorAll('#code-inputs input');
    codeInputs.forEach((input, index) => {
      input.addEventListener('input', (e) => {
        if (e.target.value.length > 0 && index < codeInputs.length - 1) {
          codeInputs[index + 1].focus();
        }
        updateCodeValue();
      });
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
          codeInputs[index - 1].focus();
        }
      });
      // Permite pegar el código completo
      input.addEventListener('paste', (e) => {
        e.preventDefault();
        const pasted = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
        if (pasted) {
          for (let i = 0; i < pasted.length; i++) {
            codeInputs[i].value = pasted[i];
          }
          if (pasted.length < 6) codeInputs[pasted.length].focus();
          else codeInputs[5].focus();
          updateCodeValue();
        }
      });
    });

    function updateCodeValue() {
      const val = Array.from(codeInputs).map(inp => inp.value).join('');
      document.getElementById('codigo_completo').value = val;
    }

    // PASO 1: Enviar correo
    document.getElementById('form-request').addEventListener('submit', async (e) => {
      e.preventDefault();
      hideAlert();
      const correo = document.getElementById('correo_request').value.trim();
      if (!correo) return;

      const btn = document.getElementById('btn-request');
      btn.classList.add('loading');
      btn.disabled = true;

      try {
        const formData = new FormData();
        formData.append('accion', 'solicitar_codigo');
        formData.append('correo', correo);

        const res = await fetch('../controlador/recuperarPassword.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();

        if (data.success) {
          correoUsuario = correo; // Guardar para el paso 2
          showAlert('Código enviado a tu WhatsApp.', false);
          panelRequest.classList.remove('active');
          panelVerify.classList.add('active');
          codeInputs[0].focus();
        } else {
          showAlert(data.message || 'Error al enviar el código.');
        }
      } catch (err) {
        showAlert('Error de conexión. Inténtalo de nuevo.');
      } finally {
        btn.classList.remove('loading');
        btn.disabled = false;
      }
    });

    // PASO 2: Verificar código y cambiar contraseña
    document.getElementById('form-verify').addEventListener('submit', async (e) => {
      e.preventDefault();
      hideAlert();
      
      const codigo = document.getElementById('codigo_completo').value;
      const pass1 = document.getElementById('nueva_contrasena').value;
      const pass2 = document.getElementById('confirmar_contrasena').value;

      if (codigo.length !== 6) return showAlert('El código debe tener 6 dígitos.');
      if (pass1 !== pass2) return showAlert('Las contraseñas no coinciden.');
      if (pass1.length < 6) return showAlert('La contraseña debe tener al menos 6 caracteres.');

      const btn = document.getElementById('btn-verify');
      btn.classList.add('loading');
      btn.disabled = true;

      try {
        const formData = new FormData();
        formData.append('accion', 'verificar_cambiar');
        formData.append('correo', correoUsuario);
        formData.append('codigo', codigo);
        formData.append('nueva_contrasena', pass1);

        const res = await fetch('../controlador/recuperarPassword.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();

        if (data.success) {
          // Cambió con éxito, redirigir al login
          window.location.href = '../index.php?accion=login&recuperacion=exito';
        } else {
          showAlert(data.message || 'Código incorrecto o expirado.');
        }
      } catch (err) {
        showAlert('Error de conexión. Inténtalo de nuevo.');
      } finally {
        btn.classList.remove('loading');
        btn.disabled = false;
      }
    });

    // Reenviar código
    document.getElementById('btn-resend').addEventListener('click', async (e) => {
      e.preventDefault();
      if (!correoUsuario) return;
      hideAlert();
      showAlert('Reenviando código...', false);
      
      const formData = new FormData();
      formData.append('accion', 'solicitar_codigo');
      formData.append('correo', correoUsuario);

      try {
        const res = await fetch('../controlador/recuperarPassword.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();
        if (data.success) {
          showAlert('Nuevo código enviado.', false);
          // Limpiar inputs
          codeInputs.forEach(i => i.value = '');
          updateCodeValue();
          codeInputs[0].focus();
        } else {
          showAlert(data.message);
        }
      } catch (err) {
        showAlert('Error al reenviar.');
      }
    });

  </script>
</body>
</html>

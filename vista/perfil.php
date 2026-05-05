<?php
/**
 * perfil.php — Edición de perfil de usuario
 * ReparaTech · Vista de configuración de cuenta
 */

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
    exit();
}

$correo  = htmlspecialchars($_SESSION['usuario']);
$nombre  = htmlspecialchars($_SESSION['nombre'] ?? 'Estudiante');
$inicial = strtoupper(substr($nombre, 0, 1));

// Datos extra de sesión (se guardan al actualizar)
$telefono  = htmlspecialchars($_SESSION['telefono']  ?? '');
$ciudad    = htmlspecialchars($_SESSION['ciudad']    ?? 'Santa Marta');
$bio       = htmlspecialchars($_SESSION['bio']       ?? '');
$foto      = $_SESSION['foto'] ?? ''; // ruta relativa a la foto subida

// Mensaje de feedback
$msg_ok  = isset($_GET['ok'])    ? true  : false;
$msg_err = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi Perfil — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
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

    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    html { scroll-behavior: smooth; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--off-white);
      color: var(--text);
      min-height: 100vh;
    }

    /* ===== BG ===== */
    .bg-dots {
      position: fixed; inset: 0;
      background-image: radial-gradient(var(--green-mid) 1px, transparent 1px);
      background-size: 36px 36px;
      opacity: .06;
      pointer-events: none;
      z-index: 0;
    }

    /* ===== NAV ===== */
    nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      display: flex; align-items: center; justify-content: space-between;
      padding: .85rem 5%;
      background: rgba(250,250,248,.95);
      backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(72,191,132,.15);
      box-shadow: 0 4px 30px rgba(42,71,71,.07);
    }

    .logo {
      font-family: 'Syne', sans-serif;
      font-size: 1.45rem; font-weight: 800;
      color: var(--green-dark); letter-spacing: -.5px;
      text-decoration: none;
    }
    .logo span { color: var(--green-light); }

    .nav-right { display: flex; align-items: center; gap: .8rem; }

    .nav-back {
      display: inline-flex; align-items: center; gap: .4rem;
      font-size: .85rem; font-weight: 500; color: var(--text-muted);
      text-decoration: none; padding: .45rem 1rem;
      border: 1.5px solid rgba(42,71,71,.15);
      border-radius: 100px; transition: all .2s;
    }
    .nav-back:hover { color: var(--green-dark); border-color: var(--green-dark); }

    .nav-avatar {
      width: 34px; height: 34px; border-radius: 50%;
      background: var(--green-light); color: var(--green-dark);
      font-family: 'Syne', sans-serif; font-weight: 800; font-size: .85rem;
      display: flex; align-items: center; justify-content: center;
      overflow: hidden;
    }
    .nav-avatar img { width: 100%; height: 100%; object-fit: cover; }

    /* ===== LAYOUT ===== */
    .page-wrap {
      position: relative; z-index: 1;
      padding: 6.5rem 5% 3rem;
      max-width: 900px; margin: 0 auto;
    }

    /* ===== PAGE TITLE ===== */
    .page-title-area {
      margin-bottom: 2rem;
      animation: fadeUp .5s ease both;
    }

    .page-title-area h1 {
      font-family: 'Syne', sans-serif;
      font-size: clamp(1.7rem, 3vw, 2.3rem);
      font-weight: 800; letter-spacing: -1.2px;
      color: var(--green-dark); line-height: 1.1;
    }

    .page-title-area h1 em {
      font-style: normal; color: var(--green-mid);
      position: relative;
    }
    .page-title-area h1 em::after {
      content: '';
      position: absolute; bottom: 2px; left: -2px; right: -2px; height: 7px;
      background: var(--pink); opacity: .4; z-index: -1; border-radius: 3px;
    }

    .page-title-area p { font-size: .9rem; color: var(--text-muted); margin-top: .35rem; font-weight: 300; }

    /* ===== ALERTS ===== */
    .alert {
      padding: .9rem 1.2rem; border-radius: 14px;
      font-size: .88rem; font-weight: 500;
      margin-bottom: 1.5rem;
      display: flex; align-items: center; gap: .6rem;
      animation: fadeUp .4s ease both;
    }
    .alert-ok  { background: rgba(97,208,149,.12); color: var(--green-sea); border: 1px solid rgba(97,208,149,.25); }
    .alert-err { background: var(--danger-bg);     color: var(--danger);    border: 1px solid #fecaca; }

    /* ===== GRID ===== */
    .profile-grid {
      display: grid;
      grid-template-columns: 260px 1fr;
      gap: 1.5rem;
      align-items: start;
    }

    @media (max-width: 700px) { .profile-grid { grid-template-columns: 1fr; } }

    /* ===== CARD ===== */
    .card {
      background: var(--white);
      border-radius: 24px;
      border: 1px solid rgba(72,191,132,.1);
      box-shadow: 0 4px 24px rgba(42,71,71,.06);
      overflow: hidden;
    }

    .card-header {
      padding: 1.2rem 1.6rem .9rem;
      border-bottom: 1px solid rgba(72,191,132,.08);
    }
    .card-title {
      font-family: 'Syne', sans-serif;
      font-size: .95rem; font-weight: 700; color: var(--green-dark);
    }
    .card-subtitle {
      font-size: .78rem; color: var(--text-muted); font-weight: 300; margin-top: .15rem;
    }
    .card-body { padding: 1.5rem 1.6rem; }

    /* ===== FOTO UPLOAD ===== */
    .foto-card .card-body {
      display: flex; flex-direction: column; align-items: center;
      gap: 1.2rem; text-align: center;
    }

    /* ✅ El wrapper rota y recorta — el circle ya no rota */
    .foto-wrapper {
      position: relative; width: 110px; height: 110px;
      cursor: pointer;
      transform: rotate(-4deg);           /* rotación aquí */
      border-radius: 32px;
      overflow: hidden;                   /* recorte aquí */
      transition: transform .25s, box-shadow .25s;
      box-shadow: 0 14px 32px rgba(97,208,149,.3);
    }

    .foto-wrapper:hover {
      transform: rotate(-4deg) scale(1.04);
      box-shadow: 0 18px 40px rgba(97,208,149,.4);
    }

    .foto-circle {
      width: 110px; height: 110px;
      border-radius: 32px;
      background: var(--green-light);
      color: var(--green-dark);
      font-family: 'Syne', sans-serif;
      font-weight: 800; font-size: 2.6rem;
      display: flex; align-items: center; justify-content: center;
      /* ✅ SIN transform — overflow funciona correctamente */
    }

    .foto-circle img {
      width: 100%; height: 100%;
      object-fit: cover; display: block;
    }

    .foto-overlay {
      position: absolute; inset: 0;
      background: rgba(42,71,71,.55);
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      gap: .3rem;
      opacity: 0; transition: opacity .25s;
      color: var(--white);
      font-size: .72rem; font-weight: 600;
      /* ✅ SIN transform propio — hereda la del wrapper */
    }
    .foto-overlay .camera-icon { font-size: 1.4rem; }
    .foto-wrapper:hover .foto-overlay { opacity: 1; }

    #foto-input { display: none; }

    .foto-nombre {
      font-family: 'Syne', sans-serif;
      font-size: 1.1rem; font-weight: 800;
      color: var(--green-dark); letter-spacing: -.4px;
    }

    .foto-correo {
      font-size: .78rem; color: var(--text-muted);
      font-weight: 300; word-break: break-all;
    }

    .foto-hint {
      font-size: .72rem; color: var(--text-muted);
      font-weight: 300; line-height: 1.5;
    }

    .btn-upload {
      width: 100%;
      background: rgba(97,208,149,.1);
      color: var(--green-sea);
      border: 1.5px solid rgba(97,208,149,.25);
      font-family: 'Syne', sans-serif;
      font-weight: 700; font-size: .82rem;
      padding: .7rem 1rem; border-radius: 100px;
      cursor: pointer; transition: all .22s;
    }
    .btn-upload:hover { background: rgba(97,208,149,.18); border-color: var(--green-mid); }

    /* ===== FORM ===== */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.1rem;
    }

    @media (max-width: 500px) { .form-grid { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: .4rem; }
    .form-group.full { grid-column: 1 / -1; }

    label {
      font-family: 'Syne', sans-serif;
      font-size: .78rem; font-weight: 700;
      color: var(--green-dark); letter-spacing: .3px;
      text-transform: uppercase;
    }

    input[type="text"],
    input[type="email"],
    input[type="tel"],
    select,
    textarea {
      width: 100%;
      background: var(--off-white);
      border: 1.5px solid rgba(72,191,132,.2);
      border-radius: 14px;
      padding: .85rem 1.1rem;
      font-family: 'DM Sans', sans-serif;
      font-size: .92rem;
      color: var(--text);
      outline: none;
      transition: all .25s;
      resize: none;
    }

    input:focus, select:focus, textarea:focus {
      border-color: var(--green-light);
      background: var(--white);
      box-shadow: 0 0 0 4px rgba(97,208,149,.1);
    }

    input[readonly] {
      opacity: .55; cursor: not-allowed;
    }

    .input-hint {
      font-size: .73rem; color: var(--text-muted);
      font-weight: 300; margin-top: .1rem;
    }

    /* ===== DIVIDER ===== */
    .section-divider {
      display: flex; align-items: center; gap: .8rem;
      margin: 1.4rem 0 1rem;
    }
    .section-divider span {
      font-family: 'Syne', sans-serif;
      font-size: .72rem; font-weight: 700;
      color: var(--green-sea); text-transform: uppercase;
      letter-spacing: 1.5px; white-space: nowrap;
    }
    .section-divider::before,
    .section-divider::after {
      content: ''; flex: 1; height: 1px;
      background: rgba(72,191,132,.15);
    }

    /* ===== PASS STRENGTH ===== */
    .pass-strength-bar {
      height: 4px; border-radius: 4px;
      background: rgba(72,191,132,.1);
      margin-top: .4rem; overflow: hidden;
    }
    .pass-fill {
      height: 100%; border-radius: 4px;
      width: 0%; transition: width .3s, background .3s;
    }
    .pass-label {
      font-size: .72rem; color: var(--text-muted);
      margin-top: .3rem; font-weight: 300;
    }

    /* ===== FORM FOOTER ===== */
    .form-footer {
      display: flex; align-items: center;
      justify-content: space-between;
      gap: 1rem; flex-wrap: wrap;
      margin-top: 1.8rem;
      padding-top: 1.4rem;
      border-top: 1px solid rgba(72,191,132,.1);
    }

    .form-footer p {
      font-size: .78rem; color: var(--text-muted); font-weight: 300;
    }

    .btn-save {
      background: var(--green-dark);
      color: var(--white);
      font-family: 'Syne', sans-serif;
      font-weight: 700; font-size: .92rem;
      padding: .85rem 2.2rem;
      border-radius: 100px; border: none;
      cursor: pointer; transition: all .25s;
      box-shadow: 0 8px 20px rgba(42,71,71,.18);
      display: inline-flex; align-items: center; gap: .5rem;
    }
    .btn-save:hover { background: #1a2a2a; transform: translateY(-2px); box-shadow: 0 12px 28px rgba(42,71,71,.25); }
    .btn-save:active { transform: translateY(0); }
    .btn-save .spinner { display: none; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,.3); border-top-color: white; border-radius: 50%; animation: spin .7s linear infinite; }
    .btn-save.loading .btn-label { display: none; }
    .btn-save.loading .spinner { display: block; }

    /* ===== DANGER ZONE ===== */
    .danger-zone {
      margin-top: 1.5rem;
    }
    .danger-card {
      background: var(--white);
      border-radius: 24px;
      border: 1.5px solid rgba(220,38,38,.12);
      box-shadow: 0 4px 24px rgba(220,38,38,.04);
      overflow: hidden;
    }
    .danger-card .card-header { border-bottom-color: rgba(220,38,38,.08); }
    .danger-card .card-title { color: var(--danger); }
    .danger-card .card-body {
      display: flex; align-items: center;
      justify-content: space-between; gap: 1rem; flex-wrap: wrap;
    }
    .danger-card .card-body p {
      font-size: .85rem; color: var(--text-muted);
      font-weight: 300; max-width: 400px; line-height: 1.6;
    }
    .btn-danger {
      background: transparent;
      color: var(--danger);
      border: 1.5px solid rgba(220,38,38,.3);
      font-family: 'Syne', sans-serif;
      font-weight: 700; font-size: .82rem;
      padding: .7rem 1.4rem; border-radius: 100px;
      cursor: pointer; transition: all .22s; white-space: nowrap;
    }
    .btn-danger:hover { background: var(--danger-bg); border-color: var(--danger); }

    /* ===== ANIMATIONS ===== */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .anim-1 { animation: fadeUp .5s .04s ease both; }
    .anim-2 { animation: fadeUp .5s .10s ease both; }
    .anim-3 { animation: fadeUp .5s .16s ease both; }
    .anim-4 { animation: fadeUp .5s .22s ease both; }
  </style>
</head>
<body>

  <div class="bg-dots"></div>

  <!-- NAV -->
  <nav>
    <a href="../index.php" class="logo">Repara<span>Tech</span></a>
    <div class="nav-right">
      <?php
        $urlPanel = 'bienvenida.php'; // Por defecto, panel de cliente
        if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'tecnico') {
            $urlPanel = 'tecnico/dashboard.php'; // Si es técnico, va a su panel
        }
      ?>
      <a href="<?= $urlPanel ?>" class="nav-back">← Mi panel</a>
      <div class="nav-avatar">
        <?php if ($foto): ?>
          <img src="<?= htmlspecialchars($foto) ?>" alt="foto">
        <?php else: ?>
          <?= $inicial ?>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <div class="page-wrap">

    <!-- TITLE -->
    <div class="page-title-area">
      <h1>Editar <em>perfil</em></h1>
      <p>Actualiza tu información personal y foto de cuenta</p>
    </div>

    <!-- ALERTS -->
    <?php if ($msg_ok): ?>
      <div class="alert alert-ok">✅ Perfil actualizado correctamente.</div>
    <?php endif; ?>
    <?php if ($msg_err): ?>
      <div class="alert alert-err">⚠️ <?= $msg_err ?></div>
    <?php endif; ?>

    <!-- MAIN FORM -->
    <form action="../controlador/actualizarPerfil.php" method="POST" enctype="multipart/form-data" id="perfil-form">

      <div class="profile-grid">

        <!-- COLUMNA IZQUIERDA: FOTO -->
        <div style="display:flex;flex-direction:column;gap:1.2rem;">

          <div class="card foto-card anim-2">
            <div class="card-header">
              <div class="card-title">Foto de perfil</div>
              <div class="card-subtitle">JPG, PNG o WEBP · Máx 2MB</div>
            </div>
            <div class="card-body">

              <!-- Avatar clickeable -->
              <div class="foto-wrapper" onclick="document.getElementById('foto-input').click()">
                <div class="foto-circle" id="foto-preview-circle">
                  <?php if ($foto): ?>
                    <img src="<?= htmlspecialchars($foto) ?>" alt="foto" id="foto-preview-img">
                  <?php else: ?>
                    <span id="foto-inicial"><?= $inicial ?></span>
                  <?php endif; ?>
                </div>
                <div class="foto-overlay">
                  <span class="camera-icon">📷</span>
                  <span>Cambiar foto</span>
                </div>
              </div>

              <div>
                <div class="foto-nombre"><?= $nombre ?></div>
                <div class="foto-correo"><?= $correo ?></div>
              </div>

              <p class="foto-hint">Haz clic en la foto para<br>seleccionar una imagen</p>

              <input type="file" id="foto-input" name="foto" accept="image/jpeg,image/png,image/webp">
              <button type="button" class="btn-upload" onclick="document.getElementById('foto-input').click()">
                📁 Elegir archivo
              </button>

              <p class="foto-hint" id="foto-filename" style="color:var(--green-sea);font-weight:500;display:none;"></p>

            </div>
          </div>

        </div>

        <!-- COLUMNA DERECHA: DATOS -->
        <div style="display:flex;flex-direction:column;gap:1.2rem;">

          <!-- DATOS PERSONALES -->
          <div class="card anim-3">
            <div class="card-header">
              <div class="card-title">Datos personales</div>
              <div class="card-subtitle">Tu información visible en ReparaTech</div>
            </div>
            <div class="card-body">
              <div class="form-grid">

                <div class="form-group">
                  <label for="nombre_usuario">Nombre de usuario</label>
                  <input type="text" id="nombre_usuario" name="nombre_usuario"
                    value="<?= $nombre ?>" placeholder="Tu nombre" required>
                </div>

                <div class="form-group">
                  <label for="telefono">Teléfono WhatsApp</label>
                  <input type="tel" id="telefono" name="telefono"
                    value="<?= !empty($telefono) ? (str_starts_with(trim($telefono), '+57') ? trim($telefono) : '+57 ' . ltrim(trim($telefono), '+')) : '+57 ' ?>" 
                    placeholder="+57 300 000 0000" required>
                  <p class="input-hint">El código +57 de Colombia se agrega por defecto.</p>
                </div>

                <div class="form-group">
                  <label for="correo_electronico">Correo electrónico</label>
                  <input type="email" id="correo_electronico"
                    value="<?= $correo ?>" readonly>
                  <p class="input-hint">El correo no se puede cambiar</p>
                </div>

                <div class="form-group">
                  <label for="ciudad">Ciudad</label>
                  <select id="ciudad" name="ciudad">
                    <?php
                    $ciudades = ['Santa Marta','Bogotá','Medellín','Cali','Barranquilla','Cartagena','Bucaramanga','Otra'];
                    foreach ($ciudades as $c):
                      $sel = ($ciudad === $c) ? 'selected' : '';
                    ?>
                      <option value="<?= $c ?>" <?= $sel ?>><?= $c ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group full">
                  <label for="bio">Sobre ti</label>
                  <textarea id="bio" name="bio" rows="3"
                    placeholder="Cuéntanos brevemente sobre ti..."><?= $bio ?></textarea>
                </div>

                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'tecnico'): ?>
                <div class="form-group full" style="margin-top: .5rem;">
                  <label for="direccion_local">Dirección de tu Local (Opcional)</label>
                  <input type="text" id="direccion_local" name="direccion_local"
                    value="<?= htmlspecialchars($_SESSION['direccion_local'] ?? '') ?>" placeholder="Ej: Calle 123 #45-67, Centro">
                  <p class="input-hint">Ubica el punto en el mapa para que los clientes puedan encontrarte fácilmente.</p>
                </div>
                <div class="form-group full">
                  <div id="map-picker" style="height: 250px; border-radius: 14px; border: 1.5px solid rgba(72,191,132,.2); z-index: 1;"></div>
                  <input type="hidden" id="latitud" name="latitud" value="<?= htmlspecialchars($_SESSION['latitud'] ?? '') ?>">
                  <input type="hidden" id="longitud" name="longitud" value="<?= htmlspecialchars($_SESSION['longitud'] ?? '') ?>">
                </div>
                <?php endif; ?>

              </div>

              <!-- CONTRASEÑA -->
              <div class="section-divider">
                <span>Cambiar contraseña</span>
              </div>

              <div class="form-grid">

                <div class="form-group full">
                  <label for="pass_actual">Contraseña actual</label>
                  <input type="password" id="pass_actual" name="pass_actual"
                    placeholder="Escribe tu contraseña actual">
                  <p class="input-hint">Déjalo vacío si no quieres cambiar la contraseña</p>
                </div>

                <div class="form-group">
                  <label for="pass_nueva">Nueva contraseña</label>
                  <input type="password" id="pass_nueva" name="pass_nueva"
                    placeholder="Mínimo 6 caracteres" oninput="checkStrength(this.value)">
                  <div class="pass-strength-bar"><div class="pass-fill" id="pass-fill"></div></div>
                  <p class="pass-label" id="pass-label"></p>
                </div>

                <div class="form-group">
                  <label for="pass_confirm">Confirmar nueva contraseña</label>
                  <input type="password" id="pass_confirm" name="pass_confirm"
                    placeholder="Repite la nueva contraseña">
                </div>

              </div>

              <!-- FOOTER -->
              <div class="form-footer">
                <p>Los cambios se guardan de forma segura.</p>
                <button type="submit" class="btn-save" id="btn-save">
                  <span class="btn-label">💾 Guardar cambios</span>
                  <span class="spinner"></span>
                </button>
              </div>

            </div>
          </div>

        </div>
      </div>

    </form>

    <!-- DANGER ZONE -->
    <div class="danger-zone anim-4">
      <div class="danger-card">
        <div class="card-header">
          <div class="card-title">⚠️ Zona de peligro</div>
        </div>
        <div class="card-body">
          <p>Al eliminar tu cuenta se borrarán todos tus datos, reservas e historial de forma permanente. Esta acción no se puede deshacer.</p>
          <button class="btn-danger" onclick="confirmarEliminar()">Eliminar mi cuenta</button>
        </div>
      </div>
    </div>

  </div><!-- /page-wrap -->

  <script>
    // ===== PREVIEW DE FOTO =====
    document.getElementById('foto-input').addEventListener('change', function () {
      const file = this.files[0];
      if (!file) return;

      // Validación de tamaño (2MB)
      if (file.size > 2 * 1024 * 1024) {
        alert('La imagen no puede superar los 2MB.');
        this.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = function (e) {
        const circle = document.getElementById('foto-preview-circle');
        // Reemplazar contenido con img
        circle.innerHTML = `<img src="${e.target.result}" alt="preview" style="width:100%;height:100%;object-fit:cover;">`;
        // Actualizar nav avatar
        const navAv = document.querySelector('.nav-avatar');
        if (navAv) navAv.innerHTML = `<img src="${e.target.result}" alt="preview" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
      };
      reader.readAsDataURL(file);

      // Mostrar nombre de archivo
      const fn = document.getElementById('foto-filename');
      fn.textContent = '📎 ' + file.name;
      fn.style.display = 'block';
    });

    // ===== STRENGTH DE CONTRASEÑA =====
    function checkStrength(val) {
      const fill  = document.getElementById('pass-fill');
      const label = document.getElementById('pass-label');
      if (!val) { fill.style.width = '0'; label.textContent = ''; return; }

      let score = 0;
      if (val.length >= 6)  score++;
      if (val.length >= 10) score++;
      if (/[A-Z]/.test(val)) score++;
      if (/[0-9]/.test(val)) score++;
      if (/[^A-Za-z0-9]/.test(val)) score++;

      const levels = [
        { w: '20%',  bg: '#ef4444', txt: 'Muy débil'  },
        { w: '40%',  bg: '#f97316', txt: 'Débil'      },
        { w: '60%',  bg: '#eab308', txt: 'Regular'    },
        { w: '80%',  bg: '#22c55e', txt: 'Fuerte'     },
        { w: '100%', bg: '#16a34a', txt: 'Muy fuerte' },
      ];
      const l = levels[Math.min(score - 1, 4)];
      fill.style.width      = l.w;
      fill.style.background = l.bg;
      label.textContent     = l.txt;
      label.style.color     = l.bg;
    }

    // ===== VALIDACIÓN ANTES DE ENVIAR =====
    document.getElementById('perfil-form').addEventListener('submit', function (e) {
      const nueva   = document.getElementById('pass_nueva').value;
      const confirm = document.getElementById('pass_confirm').value;
      const actual  = document.getElementById('pass_actual').value;

      if (nueva && !actual) {
        e.preventDefault();
        alert('Debes ingresar tu contraseña actual para cambiarla.');
        return;
      }
      if (nueva && nueva !== confirm) {
        e.preventDefault();
        alert('Las contraseñas nuevas no coinciden.');
        return;
      }
      if (nueva && nueva.length < 6) {
        e.preventDefault();
        alert('La nueva contraseña debe tener al menos 6 caracteres.');
        return;
      }

      // Loading state
      const btn = document.getElementById('btn-save');
      btn.classList.add('loading');
    });

    // ===== ELIMINAR CUENTA =====
    function confirmarEliminar() {
      if (confirm('¿Estás SEGURO de que quieres eliminar tu cuenta? Esta acción no se puede deshacer.')) {
        window.location.href = '../controlador/eliminarCuenta.php';
      }
    }
  </script>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const mapContainer = document.getElementById('map-picker');
      if (mapContainer) {
        let lat = document.getElementById('latitud').value;
        let lng = document.getElementById('longitud').value;
        let map;
        let marker;

        const defaultLat = 11.2404; // Santa Marta
        const defaultLng = -74.1990;

        if (lat && lng) {
          map = L.map('map-picker').setView([lat, lng], 15);
          marker = L.marker([lat, lng], {draggable: true}).addTo(map);
        } else {
          map = L.map('map-picker').setView([defaultLat, defaultLng], 12);
          marker = L.marker([defaultLat, defaultLng], {draggable: true}).addTo(map);
        }

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        marker.on('dragend', function (e) {
          document.getElementById('latitud').value = marker.getLatLng().lat;
          document.getElementById('longitud').value = marker.getLatLng().lng;
        });

        map.on('click', function(e) {
          marker.setLatLng(e.latlng);
          document.getElementById('latitud').value = e.latlng.lat;
          document.getElementById('longitud').value = e.latlng.lng;
        });
        
        // In case it's hidden initially (due to tabs, etc.), force invalidate size
        setTimeout(() => map.invalidateSize(), 500);
      }
    });
  </script>

</body>
</html>
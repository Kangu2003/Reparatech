<?php
/**
 * resena.php — Formulario para dejar reseña de un servicio completado
 * Ubicación: vista/resena.php
 */
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
    exit();
}

$usuarioId = (int)($_SESSION['id'] ?? 0);
$nombre    = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario');
$foto      = !empty($_SESSION['foto']) ? htmlspecialchars($_SESSION['foto']) : '';
$inicial   = strtoupper(substr($nombre, 0, 1));

$reservaId = (int)($_GET['reserva'] ?? 0);
if (!$reservaId) {
    header('Location: mis-reservas.php');
    exit();
}

require_once __DIR__ . '/../modelo/Conexion.php';
$db = (new Conexion())->getConexion();

// Obtener la reserva verificando que pertenece al usuario y está completada
$stmt = $db->prepare("
    SELECT r.id, r.fecha, r.hora, r.tecnico_id, r.precio_final,
           s.titulo AS servicio,
           c.icono,
           u.nombre_usuario AS tecnico
    FROM reservas r
    JOIN servicios   s ON s.id = r.servicio_id
    JOIN categorias  c ON c.id = s.categoria_id
    JOIN usuarios    u ON u.id = r.tecnico_id
    WHERE r.id = ? AND r.usuario_id = ? AND r.estado = 'completada'
    LIMIT 1
");
$stmt->bind_param("ii", $reservaId, $usuarioId);
$stmt->execute();
$reserva = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reserva) {
    header('Location: mis-reservas.php?error=no_disponible');
    exit();
}

// Verificar que no tenga ya una reseña
$stmtCheck = $db->prepare("SELECT COUNT(*) as total FROM resenas WHERE reserva_id = ?");
$stmtCheck->bind_param("i", $reservaId);
$stmtCheck->execute();
$rowCheck = $stmtCheck->get_result()->fetch_assoc();
if ((int)$rowCheck['total'] > 0) {
    header('Location: mis-reservas.php?error=ya_resenado');
    exit();
}
$stmtCheck->close();

$exito = isset($_GET['ok']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dejar reseña — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
  <style>
    :root {
      --pink:#E0BAD7; --green-light:#61D095; --green-mid:#48BF84;
      --green-sea:#439775; --green-dark:#2A4747;
      --white:#FAFAF8; --off-white:#F2F0EC; --text:#1a2a2a; --text-muted:#4a6a6a;
    }
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'DM Sans',sans-serif;background:var(--off-white);color:var(--text);min-height:100vh}

    nav{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:.85rem 5%;background:rgba(250,250,248,.94);backdrop-filter:blur(16px);border-bottom:1px solid rgba(72,191,132,.15);box-shadow:0 4px 30px rgba(42,71,71,.07)}
    .logo{font-family:'Syne',sans-serif;font-size:1.45rem;font-weight:800;color:var(--green-dark);letter-spacing:-.5px;text-decoration:none}
    .logo span{color:var(--green-light)}
    .nav-right{display:flex;align-items:center;gap:.8rem}
    .nav-avatar{width:34px;height:34px;border-radius:50%;background:var(--green-light);color:var(--green-dark);font-family:'Syne',sans-serif;font-weight:800;font-size:.85rem;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;text-decoration:none}
    .nav-avatar img{width:100%;height:100%;object-fit:cover;display:block}
    .nav-nombre{font-size:.88rem;font-weight:500;color:var(--green-dark);text-decoration:none}
    .btn-nav{background:transparent;border:1.5px solid rgba(42,71,71,.2);color:var(--text-muted);font-family:'DM Sans',sans-serif;font-size:.82rem;font-weight:500;padding:.45rem 1rem;border-radius:100px;cursor:pointer;text-decoration:none;transition:all .2s}
    .btn-nav:hover{border-color:var(--green-dark);color:var(--green-dark)}

    .bg-dots{position:fixed;inset:0;background-image:radial-gradient(var(--green-mid) 1px,transparent 1px);background-size:36px 36px;opacity:.06;pointer-events:none;z-index:0}

    .page-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:6rem 1.5rem 3rem;position:relative;z-index:1}

    .resena-card{background:var(--white);border-radius:28px;border:1px solid rgba(72,191,132,.12);box-shadow:0 8px 40px rgba(42,71,71,.1);width:100%;max-width:520px;animation:fadeUp .55s ease both}

    .resena-header{padding:2rem 2rem 1.5rem;border-bottom:1px solid rgba(72,191,132,.08);text-align:center}
    .resena-header .emoji{font-size:2.8rem;display:block;margin-bottom:.8rem}
    .resena-header h1{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--green-dark);letter-spacing:-1px;margin-bottom:.3rem}
    .resena-header p{font-size:.88rem;color:var(--text-muted);font-weight:300}

    .servicio-info{margin:1.5rem 2rem;background:rgba(97,208,149,.07);border:1px solid rgba(97,208,149,.18);border-radius:18px;padding:1rem 1.2rem;display:flex;align-items:center;gap:1rem}
    .servicio-icono{width:48px;height:48px;border-radius:14px;background:rgba(97,208,149,.15);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
    .servicio-nombre{font-family:'Syne',sans-serif;font-weight:700;font-size:.95rem;color:var(--green-dark)}
    .servicio-meta{font-size:.78rem;color:var(--text-muted);font-weight:300;margin-top:.15rem}

    .resena-form{padding:0 2rem 2rem;display:flex;flex-direction:column;gap:1.4rem}
    .field-label{font-family:'Syne',sans-serif;font-size:.8rem;font-weight:700;color:var(--green-dark);letter-spacing:.5px;text-transform:uppercase;display:block;margin-bottom:.6rem}
    .field-label .opt{font-weight:300;text-transform:none;letter-spacing:0}

    .stars-group{display:flex;gap:.4rem;justify-content:center}
    .star-btn{font-size:2.2rem;background:none;border:none;cursor:pointer;transition:transform .15s;line-height:1;color:#d1d5db;padding:0 .1rem}
    .star-btn:hover,.star-btn.active{color:#fbbf24;transform:scale(1.15)}
    .star-label{text-align:center;font-size:.78rem;color:var(--text-muted);font-weight:300;margin-top:.4rem;min-height:1.2em}

    .aspectos-grid{display:flex;flex-wrap:wrap;gap:.5rem}
    .aspecto-chip{background:var(--off-white);border:1.5px solid rgba(72,191,132,.15);color:var(--text-muted);font-size:.8rem;font-weight:500;padding:.4rem .9rem;border-radius:100px;cursor:pointer;transition:all .18s;user-select:none}
    .aspecto-chip.selected{background:rgba(97,208,149,.12);border-color:var(--green-mid);color:var(--green-sea);font-weight:600}

    textarea{width:100%;background:var(--off-white);border:1.5px solid rgba(72,191,132,.15);border-radius:14px;font-family:'DM Sans',sans-serif;font-size:.9rem;font-weight:300;color:var(--text);padding:.9rem 1rem;resize:vertical;min-height:110px;transition:border-color .2s;outline:none}
    textarea:focus{border-color:var(--green-mid);box-shadow:0 0 0 3px rgba(97,208,149,.1)}
    textarea::placeholder{color:#9ab8b8}
    .char-count{font-size:.72rem;color:var(--text-muted);text-align:right;margin-top:.3rem}

    .btn-submit{width:100%;background:var(--green-dark);color:var(--white);font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;padding:1rem;border-radius:100px;border:none;cursor:pointer;transition:all .25s;box-shadow:0 8px 24px rgba(42,71,71,.2);display:flex;align-items:center;justify-content:center;gap:.5rem}
    .btn-submit:hover{background:#1a2a2a;transform:translateY(-2px)}
    .btn-submit:disabled{opacity:.5;cursor:not-allowed;transform:none}
    .btn-volver{display:block;text-align:center;margin-top:.5rem;font-size:.82rem;color:var(--text-muted);text-decoration:none;transition:color .2s}
    .btn-volver:hover{color:var(--green-sea)}

    .exito-box{text-align:center;padding:3rem 2rem;display:flex;flex-direction:column;align-items:center;gap:1rem}
    .exito-emoji{font-size:4rem;animation:bounceIn .5s ease both}
    .exito-box h2{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--green-dark);letter-spacing:-1px}
    .exito-box p{font-size:.9rem;color:var(--text-muted);font-weight:300;max-width:280px}
    .btn-exito{background:var(--green-light);color:var(--green-dark);font-family:'Syne',sans-serif;font-weight:800;font-size:.9rem;padding:.85rem 2rem;border-radius:100px;text-decoration:none;transition:all .2s;box-shadow:0 6px 20px rgba(97,208,149,.35)}
    .btn-exito:hover{background:var(--green-mid);transform:translateY(-2px)}

    .alert-error{background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);color:#dc2626;border-radius:12px;padding:.75rem 1rem;font-size:.85rem;font-weight:500;text-align:center}

    @keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
    @keyframes bounceIn{0%{transform:scale(.5);opacity:0}70%{transform:scale(1.15)}100%{transform:scale(1);opacity:1}}
  </style>
</head>
<body>

<div class="bg-dots"></div>

<nav>
  <a href="../index.php" class="logo">Repara<span>Tech</span></a>
  <div class="nav-right">
    <a href="perfil.php" class="nav-avatar">
      <?php if ($foto): ?><img src="<?= $foto ?>" alt="foto"><?php else: ?><?= $inicial ?><?php endif; ?>
    </a>
    <a href="perfil.php" class="nav-nombre"><?= $nombre ?></a>
    <a href="mis-reservas.php" class="btn-nav">← Mis reservas</a>
  </div>
</nav>

<div class="page-wrap">
  <div class="resena-card">

    <?php if ($exito): ?>
      <div class="exito-box">
        <span class="exito-emoji">🎉</span>
        <h2>¡Reseña publicada!</h2>
        <p>Gracias por compartir tu experiencia. Tu opinión ayuda a otros usuarios a elegir mejor.</p>
        <a href="mis-reservas.php" class="btn-exito">Ver mis reservas</a>
        <a href="bienvenida.php" class="btn-volver">Ir al panel</a>
      </div>

    <?php else: ?>

      <div class="resena-header">
        <span class="emoji">⭐</span>
        <h1>Califica tu servicio</h1>
        <p>Tu opinión ayuda a mejorar la comunidad de ReparaTech</p>
      </div>

      <?php if (isset($_GET['error'])): ?>
        <div style="padding:0 2rem;margin-top:1.2rem">
          <div class="alert-error">Ocurrió un error al guardar tu reseña. Intenta de nuevo.</div>
        </div>
      <?php endif; ?>

      <div class="servicio-info">
        <div class="servicio-icono"><?= htmlspecialchars($reserva['icono'] ?? '🔧') ?></div>
        <div>
          <div class="servicio-nombre"><?= htmlspecialchars($reserva['servicio']) ?></div>
          <div class="servicio-meta">
            👤 <?= htmlspecialchars($reserva['tecnico']) ?> &nbsp;·&nbsp;
            📅 <?= date('d M Y', strtotime($reserva['fecha'])) ?>
          </div>
        </div>
      </div>

      <form class="resena-form" action="../controlador/guardar_resena.php" method="POST">
        <input type="hidden" name="reserva_id"   value="<?= $reservaId ?>">
        <input type="hidden" name="tecnico_id"   value="<?= (int)$reserva['tecnico_id'] ?>">
        <input type="hidden" name="calificacion" id="input-calificacion" value="0">
        <input type="hidden" name="aspectos"     id="input-aspectos"     value="">

        <div>
          <span class="field-label">¿Cómo calificas el servicio?</span>
          <div class="stars-group">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <button type="button" class="star-btn" data-val="<?= $i ?>">★</button>
            <?php endfor; ?>
          </div>
          <div class="star-label" id="star-label">Toca una estrella para calificar</div>
        </div>

        <div>
          <span class="field-label">¿Qué destacas? <span class="opt">(opcional)</span></span>
          <div class="aspectos-grid">
            <?php foreach (['Puntual','Profesional','Buen precio','Trabajo limpio','Comunicación clara','Rápido','Amable','Garantía del trabajo'] as $a): ?>
              <span class="aspecto-chip" data-aspecto="<?= $a ?>"><?= $a ?></span>
            <?php endforeach; ?>
          </div>
        </div>

        <div>
          <span class="field-label">Comentario <span class="opt">(opcional)</span></span>
          <textarea name="comentario" id="comentario" maxlength="500"
            placeholder="Cuéntanos cómo fue tu experiencia con el técnico..."></textarea>
          <div class="char-count"><span id="char-count">0</span> / 500</div>
        </div>

        <div>
          <button type="submit" class="btn-submit" id="btn-submit" disabled>⭐ Publicar reseña</button>
          <a href="mis-reservas.php" class="btn-volver">Cancelar</a>
        </div>
      </form>

    <?php endif; ?>
  </div>
</div>

<script>
  const labels   = ['','Muy malo 😞','Regular 😐','Bueno 🙂','Muy bueno 😊','Excelente 🤩'];
  const stars    = document.querySelectorAll('.star-btn');
  const inputCal = document.getElementById('input-calificacion');
  const inputAsp = document.getElementById('input-aspectos');
  const btnSub   = document.getElementById('btn-submit');
  const starLbl  = document.getElementById('star-label');
  let cal = 0, asp = [];

  function pintar(n){ stars.forEach((s,i) => s.classList.toggle('active', i < n)); }

  stars.forEach((btn, i) => {
    btn.addEventListener('click', () => {
      cal = i + 1;
      inputCal.value = cal;
      starLbl.textContent = labels[cal];
      pintar(cal);
      btnSub.disabled = false;
    });
    btn.addEventListener('mouseenter', () => { pintar(i+1); starLbl.textContent = labels[i+1]; });
    btn.addEventListener('mouseleave', () => { pintar(cal); starLbl.textContent = cal ? labels[cal] : 'Toca una estrella para calificar'; });
  });

  document.querySelectorAll('.aspecto-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      chip.classList.toggle('selected');
      const v = chip.dataset.aspecto;
      asp = chip.classList.contains('selected') ? [...asp, v] : asp.filter(a => a !== v);
      inputAsp.value = asp.join(',');
    });
  });

  document.getElementById('comentario').addEventListener('input', function(){
    document.getElementById('char-count').textContent = this.value.length;
  });
</script>

</body>
</html>

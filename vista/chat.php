<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
    exit();
}

$reservaId = (int)($_GET['reserva'] ?? 0);
if (!$reservaId) {
    header('Location: inbox.php');
    exit();
}

$usuarioId = (int)$_SESSION['id'];
$rol = $_SESSION['rol'] ?? 'usuario';

// Obtener datos de la contraparte y servicio
require_once __DIR__ . '/../modelo/Conexion.php';
$db = (new Conexion())->getConexion();

if ($rol === 'tecnico') {
    $stmt = $db->prepare(
        "SELECT r.estado, s.titulo AS servicio, u.nombre_usuario AS contraparte, u.foto AS contraparte_foto
         FROM reservas r
         JOIN servicios s ON r.servicio_id = s.id
         JOIN usuarios u ON r.usuario_id = u.id
         WHERE r.id = ? AND r.tecnico_id = ?"
    );
} else {
    $stmt = $db->prepare(
        "SELECT r.estado, s.titulo AS servicio, u.nombre_usuario AS contraparte, u.foto AS contraparte_foto
         FROM reservas r
         JOIN servicios s ON r.servicio_id = s.id
         JOIN usuarios u ON r.tecnico_id = u.id
         WHERE r.id = ? AND r.usuario_id = ?"
    );
}
$stmt->bind_param("ii", $reservaId, $usuarioId);
$stmt->execute();
$reservaInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reservaInfo) {
    // No tiene permiso o no existe
    header('Location: inbox.php');
    exit();
}

$puedeChatear = in_array($reservaInfo['estado'], ['aceptada', 'en_progreso']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat - <?= htmlspecialchars($reservaInfo['servicio']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
  <style>
    :root { --pink:#E0BAD7; --green-light:#61D095; --green-mid:#48BF84; --green-sea:#439775; --green-dark:#2A4747; --white:#FAFAF8; --off-white:#F2F0EC; --text:#1a2a2a; --text-muted:#4a6a6a; }
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'DM Sans',sans-serif; background:var(--off-white); color:var(--text); height:100vh; display:flex; flex-direction:column; overflow:hidden; }
    
    /* CHAT HEADER */
    .chat-header { background:var(--white); border-bottom:1px solid rgba(72,191,132,.15); padding:1rem 5%; display:flex; align-items:center; gap:1rem; box-shadow:0 4px 15px rgba(42,71,71,.03); z-index:10; }
    .btn-back { background:transparent; border:1px solid rgba(42,71,71,.2); width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; text-decoration:none; color:var(--text); transition:all .2s; }
    .btn-back:hover { background:var(--green-light); border-color:var(--green-light); color:var(--green-dark); }
    
    .chat-avatar { width:46px; height:46px; border-radius:50%; background:var(--green-light); display:flex; align-items:center; justify-content:center; font-family:'Syne',sans-serif; font-weight:800; font-size:1.2rem; color:var(--green-dark); overflow:hidden; border:1px solid rgba(72,191,132,.3); }
    .chat-avatar img { width:100%; height:100%; object-fit:cover; }
    
    .chat-info { flex:1; }
    .chat-name { font-family:'Syne',sans-serif; font-weight:700; font-size:1.1rem; color:var(--green-dark); }
    .chat-service { font-size:.8rem; color:var(--text-muted); display:flex; align-items:center; gap:.4rem; }
    .tag-estado { font-size:.65rem; padding:.1rem .5rem; border-radius:100px; font-weight:700; background:rgba(97,208,149,.15); color:var(--green-sea); }
    
    /* CHAT BODY */
    .chat-body { flex:1; overflow-y:auto; padding:1.5rem 5%; background-image:radial-gradient(var(--green-mid) 1px,transparent 1px); background-size:36px 36px; display:flex; flex-direction:column; gap:.8rem; }
    
    .msg-wrap { display:flex; flex-direction:column; max-width:85%; }
    .msg-wrap.mio { align-self:flex-end; align-items:flex-end; }
    .msg-wrap.otro { align-self:flex-start; align-items:flex-start; }
    
    .msg-bubble { padding:.8rem 1rem; border-radius:18px; font-size:.9rem; line-height:1.5; position:relative; box-shadow:0 2px 8px rgba(42,71,71,.05); white-space:pre-wrap; word-wrap:break-word; }
    .msg-wrap.mio .msg-bubble { background:var(--green-dark); color:var(--white); border-bottom-right-radius:4px; }
    .msg-wrap.otro .msg-bubble { background:var(--white); color:var(--text); border-bottom-left-radius:4px; border:1px solid rgba(72,191,132,.1); }
    
    .msg-time { font-size:.7rem; color:var(--text-muted); margin-top:.3rem; padding:0 .5rem; }
    
    /* CHAT FOOTER */
    .chat-footer { background:var(--white); border-top:1px solid rgba(72,191,132,.15); padding:1rem 5%; }
    .chat-form { display:flex; gap:.8rem; align-items:center; max-width:800px; margin:0 auto; }
    .chat-input { flex:1; background:var(--off-white); border:1.5px solid rgba(72,191,132,.2); border-radius:100px; padding:.9rem 1.2rem; font-family:'DM Sans',sans-serif; font-size:.95rem; outline:none; transition:all .2s; }
    .chat-input:focus { border-color:var(--green-mid); background:var(--white); box-shadow:0 0 0 4px rgba(97,208,149,.1); }
    .btn-send { background:var(--green-light); color:var(--green-dark); width:48px; height:48px; border-radius:50%; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:1.2rem; transition:all .2s; }
    .btn-send:hover { background:var(--green-mid); transform:scale(1.05); }
    .btn-send:disabled { background:#ccc; cursor:not-allowed; transform:none; }
    
    .chat-closed { text-align:center; padding:1rem; font-size:.85rem; color:var(--text-muted); background:rgba(0,0,0,.03); border-radius:12px; margin:0 auto; width:100%; max-width:600px; }
  </style>
</head>
<body>

<div class="chat-header">
  <a href="<?= $rol === 'tecnico' ? 'tecnico/dashboard.php' : 'mis-reservas.php' ?>" class="btn-back">←</a>
  <div class="chat-avatar">
    <?php if($reservaInfo['contraparte_foto']): ?>
      <img src="<?= htmlspecialchars($reservaInfo['contraparte_foto']) ?>" alt="">
    <?php else: ?>
      <?= strtoupper(substr($reservaInfo['contraparte'], 0, 1)) ?>
    <?php endif; ?>
  </div>
  <div class="chat-info">
    <div class="chat-name"><?= htmlspecialchars($reservaInfo['contraparte']) ?></div>
    <div class="chat-service">
      <?= htmlspecialchars($reservaInfo['servicio']) ?>
      <span class="tag-estado"><?= ucfirst($reservaInfo['estado']) ?></span>
    </div>
  </div>
</div>

<div class="chat-body" id="chatBody">
  <!-- Messages will be injected here via JS -->
</div>

<div class="chat-footer">
  <?php if ($puedeChatear): ?>
    <form class="chat-form" id="chatForm">
      <input type="text" id="mensajeInput" class="chat-input" placeholder="Escribe un mensaje..." autocomplete="off" required>
      <button type="submit" class="btn-send" id="btnSend">➤</button>
    </form>
  <?php else: ?>
    <div class="chat-closed">El chat está deshabilitado porque el servicio se encuentra en estado <strong><?= $reservaInfo['estado'] ?></strong>.</div>
  <?php endif; ?>
</div>

<script>
const reservaId = <?= $reservaId ?>;
const chatBody = document.getElementById('chatBody');
const chatForm = document.getElementById('chatForm');
const mensajeInput = document.getElementById('mensajeInput');
const btnSend = document.getElementById('btnSend');

let lastMessageId = 0;
let isScrolledToBottom = true;

// Detect if user scrolled up
chatBody.addEventListener('scroll', () => {
    isScrolledToBottom = chatBody.scrollHeight - chatBody.clientHeight <= chatBody.scrollTop + 20;
});

function scrollToBottom() {
    chatBody.scrollTop = chatBody.scrollHeight;
}

function renderMensajes(mensajes) {
    let html = '';
    mensajes.forEach(m => {
        // Keep track of the highest ID so we only process new ones if we had a more complex logic,
        // but for now we re-render or append. Since we get ALL messages, let's just re-render fully to keep it simple,
        // OR better, we can check if length changed to only re-render if there are new messages.
        
        const typeClass = m.mio ? 'mio' : 'otro';
        html += `
          <div class="msg-wrap ${typeClass}">
            <div class="msg-bubble">${m.mensaje}</div>
            <div class="msg-time">${m.hora}</div>
          </div>
        `;
    });
    
    // Only update DOM if there are messages
    if (chatBody.innerHTML !== html) {
        chatBody.innerHTML = html;
        if (isScrolledToBottom) {
            scrollToBottom();
        }
    }
}

async function fetchMensajes() {
    try {
        const res = await fetch(`../controlador/ControladorChat.php?accion=obtener&reserva_id=${reservaId}`);
        const data = await res.json();
        if (data.status === 'ok') {
            renderMensajes(data.data);
        }
    } catch (err) {
        console.error('Error fetching messages:', err);
    }
}

if (chatForm) {
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const texto = mensajeInput.value.trim();
        if (!texto) return;
        
        btnSend.disabled = true;
        
        const formData = new FormData();
        formData.append('accion', 'enviar');
        formData.append('reserva_id', reservaId);
        formData.append('mensaje', texto);
        
        try {
            const res = await fetch('../controlador/ControladorChat.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.status === 'ok') {
                mensajeInput.value = '';
                // Fetch immediately to show the sent message
                await fetchMensajes();
                scrollToBottom();
            } else {
                alert(data.message || 'Error al enviar');
            }
        } catch (err) {
            alert('Error de conexión');
        } finally {
            btnSend.disabled = false;
            mensajeInput.focus();
        }
    });
}

// Initial fetch and start polling every 3 seconds
fetchMensajes().then(() => scrollToBottom());
setInterval(fetchMensajes, 3000);

</script>
</body>
</html>

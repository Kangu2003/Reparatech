<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php?accion=login');
    exit();
}

require_once __DIR__ . '/../modelo/Servicio.php';
$reservaId = (int)($_GET['reserva'] ?? 0);
$usuarioId = (int)$_SESSION['id'];

$modelo = new Servicio();
$reservas = $modelo->obtenerReservasUsuario($usuarioId);

$reserva = null;
foreach ($reservas as $r) {
    if ($r['id'] === $reservaId) {
        $reserva = $r;
        break;
    }
}

if (!$reserva || $reserva['estado'] !== 'completada' || !empty($reserva['pagado'])) {
    header('Location: mis-reservas.php?error=La reserva no es válida o ya ha sido pagada.');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pago Seguro — ReparaTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    :root { 
      --pink:#E0BAD7; 
      --green-light:#61D095; 
      --green-mid:#48BF84; 
      --green-sea:#439775; 
      --green-dark:#2A4747; 
      --white:#FAFAF8; 
      --off-white:#F2F0EC; 
      --text:#1a2a2a; 
      --text-muted:#4a6a6a; 
      --danger:#dc2626;
      --bg-gradient: linear-gradient(135deg, var(--off-white) 0%, #e6e3dd 100%);
    }
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'DM Sans',sans-serif; background:var(--bg-gradient); color:var(--text); display:flex; justify-content:center; align-items:center; min-height:100vh; padding:2rem 1rem; }
    
    .checkout-container {
      display:flex; flex-direction:row; background:var(--white); border-radius:24px; box-shadow:0 30px 80px rgba(42,71,71,.12); overflow:hidden; max-width:900px; width:100%; border:1px solid rgba(72,191,132,.1); animation:fadeUp .6s ease both;
    }
    
    .summary-pane {
      background:var(--green-dark); color:var(--white); padding:3rem; width:40%; display:flex; flex-direction:column; position:relative; overflow:hidden;
    }
    .summary-pane::before {
      content:''; position:absolute; top:-50%; left:-50%; width:200%; height:200%; background:radial-gradient(circle, rgba(97,208,149,0.1) 0%, transparent 70%); pointer-events:none;
    }
    
    .logo { font-family:'Syne',sans-serif; font-size:1.6rem; font-weight:800; color:var(--white); text-decoration:none; margin-bottom:3rem; position:relative; z-index:1; }
    .logo span { color:var(--green-light); }
    
    .amount-label { font-size:.9rem; color:rgba(250,250,248,.7); text-transform:uppercase; letter-spacing:1px; margin-bottom:.5rem; font-family:'Syne',sans-serif; font-weight:700; position:relative; z-index:1; }
    .amount-value { font-size:3rem; font-weight:700; font-family:'Syne',sans-serif; letter-spacing:-1px; margin-bottom:2rem; position:relative; z-index:1; }
    
    .item-row { display:flex; justify-content:space-between; margin-bottom:1rem; font-size:.95rem; font-weight:300; position:relative; z-index:1; border-bottom:1px solid rgba(250,250,248,.1); padding-bottom:.5rem; }
    .item-row strong { font-weight:500; }
    
    .payment-pane { padding:3rem; width:60%; background:var(--white); }
    
    /* Tabs */
    .tabs { display:flex; gap:1rem; margin-bottom:2rem; border-bottom:2px solid rgba(42,71,71,.1); padding-bottom:1rem; }
    .tab { font-family:'Syne',sans-serif; font-weight:700; font-size:.95rem; color:var(--text-muted); cursor:pointer; padding:.5rem 1rem; border-radius:100px; transition:all .2s; }
    .tab.active { background:rgba(97,208,149,.15); color:var(--green-sea); }
    .tab:hover:not(.active) { color:var(--green-dark); background:rgba(42,71,71,.05); }

    .tab-content { display:none; animation:fadeUp .3s ease both; }
    .tab-content.active { display:block; }
    
    .form-group { margin-bottom:1.2rem; }
    label { display:block; font-family:'Syne',sans-serif; font-size:.8rem; font-weight:700; color:var(--text-muted); margin-bottom:.4rem; text-transform:uppercase; letter-spacing:.5px; }
    input, select { width:100%; border:1.5px solid rgba(42,71,71,.15); border-radius:12px; padding:1rem 1.2rem; font-family:'DM Sans',sans-serif; font-size:.95rem; background:var(--white); outline:none; transition:all .2s; }
    input:focus, select:focus { border-color:var(--green-light); box-shadow:0 0 0 4px rgba(97,208,149,.1); }
    
    .row { display:flex; gap:1rem; }
    .row .form-group { flex:1; }
    
    .btn-pay { width:100%; background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:800; font-size:1rem; padding:1.2rem; border-radius:100px; border:none; cursor:pointer; margin-top:2rem; transition:all .25s; position:relative; overflow:hidden; }
    .btn-pay:hover { background:var(--green-mid); transform:translateY(-2px); box-shadow:0 10px 25px rgba(97,208,149,.3); }
    .btn-pay:disabled { opacity:0.7; cursor:not-allowed; transform:none; box-shadow:none; }
    
    .btn-cancel { display:block; text-align:center; color:var(--text-muted); font-size:.9rem; margin-top:1.5rem; text-decoration:none; transition:color .2s; }
    .btn-cancel:hover { color:var(--danger); }
    
    /* Overlay and Spinner for Loading */
    .loading-overlay { position:fixed; inset:0; background:rgba(250,250,248,.9); z-index:100; display:flex; flex-direction:column; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition:opacity .3s; }
    .loading-overlay.active { opacity:1; pointer-events:all; }
    
    .spinner { width:50px; height:50px; border:4px solid rgba(72,191,132,.2); border-top-color:var(--green-mid); border-radius:50%; animation:spin 1s linear infinite; margin-bottom:1rem; }
    .loading-text { font-family:'Syne',sans-serif; font-size:1.2rem; font-weight:700; color:var(--green-dark); }

    .success-icon { width:80px; height:80px; background:var(--green-light); border-radius:50%; display:flex; justify-content:center; align-items:center; color:var(--white); font-size:2.5rem; margin-bottom:1.5rem; animation:scaleUp .5s cubic-bezier(0.175, 0.885, 0.32, 1.275) both; }

    /* Estilos banco (pse) */
    .pse-logo { background:#FAFAF8; border:1.5px solid rgba(42,71,71,.1); padding:1rem; border-radius:12px; display:flex; align-items:center; gap:1rem; margin-bottom:1rem; }
    .pse-circle { width:40px; height:40px; background:#f97316; border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; font-size:0.8rem; }

    @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
    @keyframes spin { to { transform: rotate(360deg); } }
    @keyframes scaleUp { from{transform:scale(0)} to{transform:scale(1)} }

    @media(max-width:768px) {
      .checkout-container { flex-direction:column; }
      .summary-pane, .payment-pane { width:100%; padding:2rem; }
    }
  </style>
</head>
<body>

  <div class="checkout-container">
    
    <!-- RESUMEN -->
    <div class="summary-pane">
      <a href="../index.php" class="logo">Repara<span>Tech</span></a>
      
      <div class="amount-label">Total a Pagar</div>
      <div class="amount-value">$<?= number_format($reserva['precio'], 0, ',', '.') ?></div>
      
      <div class="item-row">
        <span>Servicio</span>
        <strong><?= htmlspecialchars($reserva['servicio']) ?></strong>
      </div>
      <div class="item-row">
        <span>Técnico</span>
        <strong><?= htmlspecialchars($reserva['tecnico']) ?></strong>
      </div>
      <div class="item-row">
        <span>Reserva ID</span>
        <strong>#<?= str_pad($reserva['id'], 5, '0', STR_PAD_LEFT) ?></strong>
      </div>
      <div class="item-row" style="border:none; margin-top:auto; font-size:0.8rem; opacity:0.7;">
        🔒 Pagos protegidos y encriptados de extremo a extremo.
      </div>
    </div>
    
    <!-- PAGO -->
    <div class="payment-pane">
      <div class="tabs">
        <div class="tab active" data-target="tab-tarjeta">💳 Tarjeta</div>
        <div class="tab" data-target="tab-transferencia">🏦 Transferencia (PSE)</div>
      </div>
      
      <form id="payment-form">
        <input type="hidden" name="reserva_id" value="<?= $reserva['id'] ?>">
        <input type="hidden" name="monto" value="<?= $reserva['precio'] ?>">
        <input type="hidden" name="accion" value="procesar_pago">
        <input type="hidden" name="tipo_pago" id="tipo_pago" value="Tarjeta">

        <!-- TAB TARJETA -->
        <div id="tab-tarjeta" class="tab-content active">
          <div class="form-group">
            <label>Nombre en la tarjeta</label>
            <input type="text" id="cc-name" placeholder="Ej. Juan Pérez" required>
          </div>
          <div class="form-group">
            <label>Número de Tarjeta</label>
            <input type="text" id="card-number" placeholder="0000 0000 0000 0000" maxlength="19" required>
          </div>
          <div class="row">
            <div class="form-group">
              <label>Vencimiento</label>
              <input type="text" placeholder="MM/YY" maxlength="5" id="card-expiry" required>
            </div>
            <div class="form-group">
              <label>CVC</label>
              <input type="password" id="cc-cvc" placeholder="123" maxlength="4" required>
            </div>
          </div>
          <div class="form-group" style="margin-top:1rem;">
             <label>Cuotas</label>
             <select id="cc-cuotas" required>
               <option value="1">1 cuota sin interés</option>
               <option value="3">3 cuotas</option>
               <option value="6">6 cuotas</option>
             </select>
          </div>
        </div>

        <!-- TAB TRANSFERENCIA (PSE) -->
        <div id="tab-transferencia" class="tab-content">
          <div class="pse-logo">
            <div class="pse-circle">PSE</div>
            <div>
              <div style="font-weight:bold; color:var(--green-dark);">Pagos Seguros en Línea</div>
              <div style="font-size:0.8rem; color:var(--text-muted);">Paga con Nequi, Daviplata o tu banco.</div>
            </div>
          </div>
          
          <div class="form-group">
            <label>Selecciona tu banco</label>
            <select name="banco_pse" id="banco_pse">
              <option value="">Selecciona un banco...</option>
              <option value="Nequi">Nequi</option>
              <option value="Bancolombia">Bancolombia</option>
              <option value="Daviplata">Daviplata</option>
              <option value="Dale!">Dale!</option>
              <option value="Banco de Bogotá">Banco de Bogotá</option>
              <option value="Davivienda">Davivienda</option>
              <option value="Lulo Bank">Lulo Bank</option>
            </select>
          </div>

          <div class="form-group">
            <label>Tipo de documento</label>
            <select id="doc_tipo">
              <option value="CC">Cédula de Ciudadanía</option>
              <option value="CE">Cédula de Extranjería</option>
            </select>
          </div>

          <div class="form-group">
            <label>Número de documento</label>
            <input type="text" id="doc_numero" placeholder="Ej. 1234567890">
          </div>
        </div>
        
        <button type="submit" class="btn-pay" id="btn-submit">Pagar $<?= number_format($reserva['precio'], 0, ',', '.') ?></button>
        <a href="mis-reservas.php" class="btn-cancel">Cancelar y volver</a>
      </form>
    </div>
    
  </div>

  <div class="loading-overlay" id="loading">
    <div class="spinner" id="spinner"></div>
    <div class="success-icon" id="success-icon" style="display:none;">✓</div>
    <div class="loading-text" id="loading-text">Procesando pago...</div>
  </div>

  <script>
    // Tab Switching Logic
    const tabs = document.querySelectorAll('.tab');
    const contents = document.querySelectorAll('.tab-content');
    const inputTipoPago = document.getElementById('tipo_pago');

    // Inputs requeridos por tab
    const ccInputs = [document.getElementById('cc-name'), document.getElementById('card-number'), document.getElementById('card-expiry'), document.getElementById('cc-cvc'), document.getElementById('cc-cuotas')];
    const pseInputs = [document.getElementById('banco_pse'), document.getElementById('doc_numero')];

    function toggleRequired(isCard) {
        ccInputs.forEach(el => el.required = isCard);
        pseInputs.forEach(el => el.required = !isCard);
    }
    // Setup initial required
    toggleRequired(true);

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        contents.forEach(c => c.classList.remove('active'));
        
        tab.classList.add('active');
        document.getElementById(tab.dataset.target).classList.add('active');
        
        if(tab.dataset.target === 'tab-tarjeta') {
            inputTipoPago.value = 'Tarjeta';
            toggleRequired(true);
        } else {
            inputTipoPago.value = 'Transferencia';
            toggleRequired(false);
        }
      });
    });

    // Formatear input de tarjeta
    document.getElementById('card-number').addEventListener('input', function(e) {
      this.value = this.value.replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim();
    });

    document.getElementById('card-expiry').addEventListener('input', function(e) {
      this.value = this.value.replace(/\D/g, '');
      if (this.value.length > 2) {
        this.value = this.value.substring(0,2) + '/' + this.value.substring(2,4);
      }
    });

    // Enviar formulario (Simulado)
    document.getElementById('payment-form').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const btn = document.getElementById('btn-submit');
      const overlay = document.getElementById('loading');
      
      btn.disabled = true;
      overlay.classList.add('active');
      
      // Ajustar texto de carga según el método
      const loadingText = document.getElementById('loading-text');
      if(inputTipoPago.value === 'Transferencia') {
         const banco = document.getElementById('banco_pse').value;
         loadingText.textContent = 'Conectando con ' + banco + '...';
      } else {
         loadingText.textContent = 'Procesando pago seguro...';
      }
      
      const formData = new FormData(this);

      try {
        const res = await fetch('../controlador/ControladorPago.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();
        
        if (data.success) {
          setTimeout(() => {
            document.getElementById('spinner').style.display = 'none';
            document.getElementById('success-icon').style.display = 'flex';
            document.getElementById('loading-text').textContent = '¡Pago Aprobado!';
            document.getElementById('loading-text').style.color = 'var(--green-mid)';
            
            setTimeout(() => {
              window.location.href = 'mis-reservas.php?ok=1';
            }, 2000);
          }, 2000); // 2 segundos de simulación
        } else {
          alert('Error: ' + data.message);
          overlay.classList.remove('active');
          btn.disabled = false;
        }
      } catch (err) {
        alert('Error de conexión.');
        overlay.classList.remove('active');
        btn.disabled = false;
      }
    });
  </script>
</body>
</html>

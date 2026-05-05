<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'tecnico') {
    header('Location: ../../index.php');
    exit();
}

require_once __DIR__ . '/../../modelo/Tecnico.php';

$tecnicoId = (int)$_SESSION['id'];
$modelo = new Tecnico();

// Calcular saldos
$ganancias = $modelo->obtenerGanancias($tecnicoId);
$totalGanado = (float)($ganancias['total_ganado'] ?? 0);

$retiros = $modelo->obtenerRetiros($tecnicoId);
$totalRetirado = 0;
foreach ($retiros as $r) {
    if ($r['estado'] !== 'rechazado') {
        $totalRetirado += (float)$r['monto'];
    }
}
$saldoDisponible = $totalGanado - $totalRetirado;

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mi Billetera — Técnico</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    :root { --green-light:#61D095; --green-mid:#48BF84; --green-sea:#439775; --green-dark:#2A4747; --white:#FAFAF8; --off-white:#F2F0EC; --text:#1a2a2a; --text-muted:#4a6a6a; }
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'DM Sans',sans-serif; background:var(--off-white); color:var(--text); min-height:100vh; display:flex; }
    
    /* SIDEBAR (simplificado) */
    .sidebar { width:260px; background:var(--white); border-right:1px solid rgba(72,191,132,.15); display:flex; flex-direction:column; position:fixed; height:100vh; left:0; top:0; z-index:100; }
    .brand { font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:800; color:var(--green-dark); padding:1.5rem; display:flex; align-items:center; gap:.5rem; text-decoration:none; border-bottom:1px solid rgba(72,191,132,.1); }
    .brand span { color:var(--green-light); }
    .nav-menu { padding:1.5rem 1rem; display:flex; flex-direction:column; gap:.5rem; flex:1; }
    .nav-link { display:flex; align-items:center; gap:1rem; padding:.8rem 1rem; color:var(--text-muted); text-decoration:none; border-radius:12px; font-weight:500; transition:all .2s; }
    .nav-link:hover { background:rgba(97,208,149,.1); color:var(--green-dark); }
    .nav-link.active { background:var(--green-light); color:var(--green-dark); font-weight:700; }
    
    /* MAIN AREA */
    .main-content { margin-left:260px; padding:2.5rem 4%; flex:1; width:calc(100% - 260px); }
    .header { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:2.5rem; animation:fadeUp .5s ease both; }
    .title h1 { font-family:'Syne',sans-serif; font-size:2.2rem; color:var(--green-dark); font-weight:800; letter-spacing:-1px; }
    .title p { color:var(--text-muted); margin-top:.3rem; }

    /* BALANCES */
    .balances-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:1.5rem; margin-bottom:2.5rem; animation:fadeUp .6s .1s ease both; }
    .balance-card { background:var(--white); border-radius:24px; padding:2rem; border:1px solid rgba(72,191,132,.1); box-shadow:0 10px 40px rgba(42,71,71,.05); position:relative; overflow:hidden; }
    .balance-card.highlight { background:var(--green-dark); color:var(--white); }
    .balance-label { font-size:.85rem; font-family:'Syne',sans-serif; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:1rem; color:var(--text-muted); }
    .highlight .balance-label { color:rgba(250,250,248,.7); }
    .balance-amount { font-family:'Syne',sans-serif; font-size:2.8rem; font-weight:800; letter-spacing:-1px; color:var(--green-sea); line-height:1; }
    .highlight .balance-amount { color:var(--green-light); }

    /* CONTENT GRID */
    .content-grid { display:grid; grid-template-columns:1fr 1fr; gap:2rem; animation:fadeUp .6s .2s ease both; }
    .card { background:var(--white); border-radius:24px; padding:2rem; border:1px solid rgba(72,191,132,.1); box-shadow:0 10px 40px rgba(42,71,71,.05); }
    .card-title { font-family:'Syne',sans-serif; font-size:1.2rem; font-weight:700; color:var(--green-dark); margin-bottom:1.5rem; display:flex; align-items:center; gap:.5rem; }

    /* FORM */
    .form-group { margin-bottom:1.2rem; }
    label { display:block; font-size:.85rem; font-weight:600; color:var(--text-muted); margin-bottom:.5rem; }
    input, select { width:100%; border:1.5px solid rgba(42,71,71,.15); border-radius:12px; padding:.9rem 1rem; font-family:'DM Sans',sans-serif; font-size:.95rem; outline:none; transition:all .2s; }
    input:focus, select:focus { border-color:var(--green-light); box-shadow:0 0 0 4px rgba(97,208,149,.1); }
    .btn-submit { width:100%; background:var(--green-light); color:var(--green-dark); font-family:'Syne',sans-serif; font-weight:800; font-size:1rem; padding:1.2rem; border-radius:100px; border:none; cursor:pointer; margin-top:1rem; transition:all .2s; }
    .btn-submit:hover:not(:disabled) { background:var(--green-mid); transform:translateY(-2px); box-shadow:0 8px 20px rgba(97,208,149,.2); }
    .btn-submit:disabled { opacity:0.5; cursor:not-allowed; }

    /* HISTORY */
    .history-list { display:flex; flex-direction:column; gap:1rem; }
    .history-item { display:flex; justify-content:space-between; align-items:center; padding:1rem; border-radius:16px; background:var(--off-white); border:1px solid rgba(42,71,71,.05); }
    .history-info { display:flex; flex-direction:column; gap:.2rem; }
    .history-bank { font-family:'Syne',sans-serif; font-weight:700; color:var(--green-dark); }
    .history-date { font-size:.8rem; color:var(--text-muted); }
    .history-amount { font-family:'Syne',sans-serif; font-weight:800; color:var(--green-sea); }
    
    .tag { font-size:.7rem; font-weight:700; padding:.2rem .6rem; border-radius:100px; text-transform:uppercase; }
    .tag-pendiente { background:rgba(251,191,36,.15); color:#b45309; }
    .tag-aprobado { background:rgba(97,208,149,.15); color:var(--green-sea); }
    .tag-rechazado { background:rgba(220,38,38,.08); color:#dc2626; }

    @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <a href="../../index.php" class="brand">Repara<span>Tech</span></a>
    <nav class="nav-menu">
      <a href="dashboard.php" class="nav-link">🏠 Panel Principal</a>
      <a href="servicios.php" class="nav-link">🔧 Mis Servicios</a>
      <a href="billetera.php" class="nav-link active">💰 Mi Billetera</a>
      <a href="../../index.php?accion=logout" class="nav-link" style="margin-top:auto; color:#dc2626;">🚪 Salir</a>
    </nav>
  </aside>

  <main class="main-content">
    <div class="header">
      <div class="title">
        <h1>Mi <em>Billetera</em></h1>
        <p>Gestiona tus ganancias y retira tu dinero al instante.</p>
      </div>
    </div>

    <div class="balances-grid">
      <div class="balance-card highlight">
        <div class="balance-label">Saldo Disponible</div>
        <div class="balance-amount">$<?= number_format($saldoDisponible, 0, ',', '.') ?></div>
      </div>
      <div class="balance-card">
        <div class="balance-label">Total Retirado</div>
        <div class="balance-amount" style="color:var(--text);">$<?= number_format($totalRetirado, 0, ',', '.') ?></div>
      </div>
      <div class="balance-card">
        <div class="balance-label">Total Generado Histórico</div>
        <div class="balance-amount" style="color:var(--text-muted);">$<?= number_format($totalGanado, 0, ',', '.') ?></div>
      </div>
    </div>

    <div class="content-grid">
      <!-- FORMULARIO RETIRO -->
      <div class="card">
        <div class="card-title">💸 Solicitar Retiro</div>
        <?php if($saldoDisponible < 10000): ?>
          <div style="padding:1rem; background:rgba(251,191,36,.1); color:#b45309; border-radius:12px; font-size:.9rem; font-weight:500; margin-bottom:1.5rem;">
            Debes tener un saldo mínimo de $10.000 COP para poder retirar.
          </div>
        <?php endif; ?>
        
        <form id="formRetiro">
          <input type="hidden" name="accion" value="solicitar">
          
          <div class="form-group">
            <label>Monto a retirar (COP)</label>
            <input type="number" name="monto" id="monto" min="10000" max="<?= $saldoDisponible ?>" value="<?= $saldoDisponible ?>" step="1000" required <?= $saldoDisponible < 10000 ? 'disabled' : '' ?>>
          </div>
          
          <div class="form-group">
            <label>Banco de destino</label>
            <select name="banco" required <?= $saldoDisponible < 10000 ? 'disabled' : '' ?>>
              <option value="">Selecciona tu banco...</option>
              <option value="Nequi">Nequi</option>
              <option value="Bancolombia">Bancolombia</option>
              <option value="Daviplata">Daviplata</option>
              <option value="Davivienda">Davivienda</option>
              <option value="Banco de Bogotá">Banco de Bogotá</option>
            </select>
          </div>
          
          <div style="display:flex; gap:1rem;">
            <div class="form-group" style="flex:1;">
              <label>Tipo de Cuenta</label>
              <select name="tipo_cuenta" required <?= $saldoDisponible < 10000 ? 'disabled' : '' ?>>
                <option value="Ahorros">Ahorros</option>
                <option value="Corriente">Corriente</option>
              </select>
            </div>
            <div class="form-group" style="flex:2;">
              <label>Número de Cuenta</label>
              <input type="text" name="numero_cuenta" required placeholder="Ej. 3001234567" <?= $saldoDisponible < 10000 ? 'disabled' : '' ?>>
            </div>
          </div>
          
          <button type="submit" class="btn-submit" <?= $saldoDisponible < 10000 ? 'disabled' : '' ?> id="btnRetirar">Transferir Dinero</button>
        </form>
      </div>

      <!-- HISTORIAL RETIROS -->
      <div class="card">
        <div class="card-title">📜 Historial de Retiros</div>
        <div class="history-list">
          <?php if(empty($retiros)): ?>
            <div style="text-align:center; color:var(--text-muted); padding:2rem 0;">No tienes retiros registrados.</div>
          <?php else: ?>
            <?php foreach($retiros as $r): ?>
              <div class="history-item">
                <div class="history-info">
                  <span class="history-bank"><?= htmlspecialchars($r['banco']) ?> (<?= htmlspecialchars($r['tipo_cuenta']) ?>)</span>
                  <span class="history-date"><?= date('d M Y, h:i A', strtotime($r['creado_en'])) ?></span>
                  <span class="tag tag-<?= $r['estado'] ?>"><?= $r['estado'] ?></span>
                </div>
                <div class="history-amount">
                  $<?= number_format($r['monto'], 0, ',', '.') ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <script>
    document.getElementById('formRetiro').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const btn = document.getElementById('btnRetirar');
      const monto = document.getElementById('monto').value;
      
      if(!confirm(`¿Estás seguro de solicitar el retiro de $${parseInt(monto).toLocaleString('es-CO')}?`)) return;
      
      btn.disabled = true;
      btn.textContent = 'Procesando...';
      
      const formData = new FormData(this);
      
      try {
        const res = await fetch('../../controlador/ControladorRetiro.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();
        
        if(data.success) {
          alert('✅ ' + data.message);
          window.location.reload();
        } else {
          alert('❌ Error: ' + data.message);
          btn.disabled = false;
          btn.textContent = 'Transferir Dinero';
        }
      } catch(err) {
        alert('Error de conexión.');
        btn.disabled = false;
        btn.textContent = 'Transferir Dinero';
      }
    });
  </script>
</body>
</html>

<?php
$nombre  = htmlspecialchars($_SESSION['nombre'] ?? 'Administrador');
$foto    = !empty($_SESSION['foto']) ? htmlspecialchars($_SESSION['foto']) : '';
$inicial = strtoupper(substr($nombre, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Disputa — ReparaTech Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --green-light:#61D095; --green-sea:#439775; --green-dark:#2A4747; --white:#FAFAF8; --off-white:#F2F0EC; --text:#1a2a2a; --text-muted:#4a6a6a; }
        *,*::before,*::after { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--off-white); color:var(--text); display:flex; min-height:100vh; }
        
        .sidebar { width:240px; background:var(--white); border-right:1px solid rgba(72,191,132,.12); display:flex; flex-direction:column; padding:1.8rem 0; position:fixed; height:100vh; }
        .logo { font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:800; color:var(--green-dark); text-decoration:none; padding:0 1.5rem; margin-bottom:2rem; }
        .logo span { color:var(--green-light); }
        .nav-links { display:flex; flex-direction:column; gap:.25rem; padding:0 .8rem; flex:1; }
        .nav-link { display:flex; align-items:center; gap:.8rem; padding:.75rem 1rem; text-decoration:none; color:var(--text-muted); font-weight:500; font-size:.88rem; border-radius:12px; }
        .nav-link:hover { background:rgba(97,208,149,.1); color:var(--green-dark); }
        .nav-link.active { background:var(--green-dark); color:var(--white); font-weight:700; }
        
        .main-content { flex:1; margin-left:240px; padding:2rem 2.5rem; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; }
        .top-bar h1 { font-family:'Syne',sans-serif; font-size:1.7rem; font-weight:800; color:var(--green-dark); }
        
        .card { background:var(--white); border-radius:20px; padding:2rem; box-shadow:0 4px 20px rgba(42,71,71,.05); max-width:800px; margin:0 auto; }
        
        .d-group { margin-bottom: 1.5rem; }
        .d-label { font-family: 'Syne', sans-serif; font-size: .8rem; font-weight: 700; color: var(--green-dark); text-transform: uppercase; letter-spacing: 1px; margin-bottom: .3rem; display: block; }
        .d-val { font-size: 1rem; color: var(--text); background: rgba(72,191,132,.05); padding: 1rem; border-radius: 12px; border: 1px solid rgba(72,191,132,.1); }
        
        textarea, select { width: 100%; padding: .8rem 1rem; border-radius: 12px; border: 1px solid #ccc; font-family: 'DM Sans', sans-serif; font-size: .95rem; margin-top: .5rem; }
        textarea { resize: vertical; min-height: 120px; }
        
        .btn-submit { background: var(--green-dark); color: var(--white); font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1rem; padding: .8rem 1.5rem; border-radius: 100px; border: none; cursor: pointer; transition: all .2s; margin-top: 1rem; display: inline-block; }
        .btn-submit:hover { background: var(--green-sea); transform: translateY(-2px); }
        .btn-back { display: inline-block; margin-right: 1rem; color: var(--text-muted); text-decoration: none; font-weight: 500; }
        
        .badge { display: inline-block; padding: .3rem .8rem; border-radius: 100px; font-size: .85rem; font-weight: 700; margin-bottom: 1.5rem; }
        .badge-abierta { background: #fee2e2; color: #dc2626; }
        .badge-en_revision { background: #fef3c7; color: #d97706; }
        .badge-resuelta { background: #dcfce7; color: #16a34a; }
        .badge-cerrada { background: #f3f4f6; color: #4b5563; }
    </style>
</head>
<body>

<aside class="sidebar">
  <a href="#" class="logo">Repara<span>Tech</span></a>
  <div class="nav-links" style="margin-top:2rem;">
    <a href="admin.php?accion=dashboard" class="nav-link">📊 Dashboard</a>
    <a href="admin.php?accion=disputas" class="nav-link active">⚠️ Disputas</a>
  </div>
</aside>

<main class="main-content">
  <div class="top-bar">
    <h1>Detalle de la Disputa #<?= $disputa['id'] ?></h1>
  </div>

  <div class="card">
    <span class="badge badge-<?= $disputa['estado'] ?>"><?= ucfirst(str_replace('_', ' ', $disputa['estado'])) ?></span>
    
    <div class="d-group">
        <span class="d-label">Reserva Asociada</span>
        <div class="d-val"><strong>#<?= $disputa['reserva_id'] ?></strong> - <?= htmlspecialchars($disputa['servicio']) ?></div>
    </div>
    <div class="d-group">
        <span class="d-label">Involucrados</span>
        <div class="d-val">
            <strong>Cliente:</strong> <?= htmlspecialchars($disputa['cliente']) ?> (<?= htmlspecialchars($disputa['cliente_correo']) ?>)<br>
            <strong>Técnico:</strong> <?= htmlspecialchars($disputa['tecnico']) ?> (<?= htmlspecialchars($disputa['tecnico_correo']) ?>)
        </div>
    </div>
    <div class="d-group">
        <span class="d-label">Motivo del reclamo</span>
        <div class="d-val"><strong><?= htmlspecialchars($disputa['motivo']) ?></strong></div>
    </div>
    <div class="d-group">
        <span class="d-label">Descripción Detallada</span>
        <div class="d-val"><?= nl2br(htmlspecialchars($disputa['descripcion'])) ?></div>
    </div>

    <hr style="border:0; border-top:1px solid rgba(72,191,132,.2); margin:2rem 0;">

    <form action="admin.php?accion=cambiar_estado_disputa" method="POST">
        <input type="hidden" name="id" value="<?= $disputa['id'] ?>">
        
        <div class="d-group">
            <span class="d-label">Responder / Solución Oficial (Se enviará por correo al cliente)</span>
            <textarea name="admin_respuesta" placeholder="Escribe aquí la resolución o actualización del caso..."><?= htmlspecialchars($disputa['admin_respuesta'] ?? '') ?></textarea>
        </div>

        <div class="d-group">
            <span class="d-label">Actualizar Estado</span>
            <select name="estado">
                <option value="abierta" <?= $disputa['estado'] === 'abierta' ? 'selected' : '' ?>>Abierta</option>
                <option value="en_revision" <?= $disputa['estado'] === 'en_revision' ? 'selected' : '' ?>>En revisión</option>
                <option value="resuelta" <?= $disputa['estado'] === 'resuelta' ? 'selected' : '' ?>>Resuelta</option>
                <option value="cerrada" <?= $disputa['estado'] === 'cerrada' ? 'selected' : '' ?>>Cerrada</option>
            </select>
        </div>

        <div style="margin-top: 2rem;">
            <a href="admin.php?accion=disputas" class="btn-back">← Volver</a>
            <button type="submit" class="btn-submit">Guardar y Enviar Correo</button>
        </div>
    </form>
  </div>
</main>

</body>
</html>

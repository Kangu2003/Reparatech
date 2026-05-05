<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit();
}

require_once __DIR__ . '/../modelo/Mensaje.php';
require_once __DIR__ . '/../modelo/Conexion.php';

$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');
$usuarioId = (int)$_SESSION['id'];
$rol = $_SESSION['rol'] ?? 'usuario';

$modelo = new Mensaje();

// ─── VERIFICAR PERMISO SOBRE LA RESERVA ───
function verificarPermisoReserva($reservaId, $usuarioId, $rol) {
    $db = (new Conexion())->getConexion();
    if ($rol === 'tecnico') {
        $stmt = $db->prepare("SELECT id FROM reservas WHERE id = ? AND tecnico_id = ?");
    } else {
        $stmt = $db->prepare("SELECT id FROM reservas WHERE id = ? AND usuario_id = ?");
    }
    $stmt->bind_param("ii", $reservaId, $usuarioId);
    $stmt->execute();
    $res = $stmt->get_result();
    $tienePermiso = $res->num_rows > 0;
    $stmt->close();
    return $tienePermiso;
}

if ($accion === 'enviar') {
    $reservaId = (int)($_POST['reserva_id'] ?? 0);
    $mensaje = trim($_POST['mensaje'] ?? '');

    if ($reservaId === 0 || empty($mensaje)) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
        exit();
    }

    if (!verificarPermisoReserva($reservaId, $usuarioId, $rol)) {
        echo json_encode(['status' => 'error', 'message' => 'Permiso denegado']);
        exit();
    }

    if ($modelo->enviarMensaje($reservaId, $usuarioId, $mensaje)) {
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo enviar']);
    }
    exit();

} elseif ($accion === 'obtener') {
    $reservaId = (int)($_GET['reserva_id'] ?? 0);

    if ($reservaId === 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        exit();
    }

    if (!verificarPermisoReserva($reservaId, $usuarioId, $rol)) {
        echo json_encode(['status' => 'error', 'message' => 'Permiso denegado']);
        exit();
    }

    // Marcar como leídos los mensajes entrantes al obtenerlos
    $modelo->marcarComoLeidos($reservaId, $usuarioId);

    $mensajes = $modelo->obtenerMensajesPorReserva($reservaId);
    
    // Formatear para el cliente
    $data = array_map(function($m) use ($usuarioId) {
        return [
            'id' => $m['id'],
            'mio' => ((int)$m['remitente_id'] === $usuarioId),
            'mensaje' => htmlspecialchars($m['mensaje']),
            'hora' => date('H:i', strtotime($m['creado_en'])),
            'nombre' => htmlspecialchars($m['nombre_usuario']),
            'foto' => $m['foto'] ? htmlspecialchars($m['foto']) : null
        ];
    }, $mensajes);

    echo json_encode(['status' => 'ok', 'data' => $data]);
    exit();
    
} elseif ($accion === 'no_leidos') {
    $count = $modelo->contarNoLeidosGlobal($usuarioId, $rol);
    echo json_encode(['status' => 'ok', 'count' => $count]);
    exit();
    
} elseif ($accion === 'obtener_inbox') {
    $chats = $modelo->obtenerChatsActivos($usuarioId, $rol);
    
    $totalNoLeidos = 0;
    $data = array_map(function($c) use (&$totalNoLeidos) {
        $totalNoLeidos += (int)$c['no_leidos'];
        return [
            'reserva_id' => $c['reserva_id'],
            'contraparte' => htmlspecialchars($c['contraparte']),
            'contraparte_foto' => $c['contraparte_foto'] ? htmlspecialchars($c['contraparte_foto']) : null,
            'servicio' => htmlspecialchars($c['servicio']),
            'ultimo_mensaje' => $c['ultimo_mensaje'] ? htmlspecialchars($c['ultimo_mensaje']) : 'Envía el primer mensaje...',
            'hora' => $c['fecha_ultimo_mensaje'] ? date('H:i', strtotime($c['fecha_ultimo_mensaje'])) : '',
            'no_leidos' => (int)$c['no_leidos']
        ];
    }, $chats);

    echo json_encode(['status' => 'ok', 'count' => $totalNoLeidos, 'data' => $data]);
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);

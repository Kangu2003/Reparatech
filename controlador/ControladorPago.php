<?php
/**
 * controlador/ControladorPago.php
 * Procesa el pago simulado
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit;
}

require_once __DIR__ . '/../modelo/Conexion.php';

$accion = $_POST['accion'] ?? '';

if ($accion === 'procesar_pago') {
    $reservaId = (int)($_POST['reserva_id'] ?? 0);
    $monto     = (float)($_POST['monto'] ?? 0);
    $usuarioId = (int)$_SESSION['id'];
    
    if ($reservaId <= 0 || $monto <= 0) {
        echo json_encode(['success' => false, 'message' => 'Datos de pago inválidos.']);
        exit;
    }
    
    $conexion = (new Conexion())->getConexion();
    
    // Verificar que la reserva pertenezca al usuario y esté completada
    $stmt = $conexion->prepare("SELECT estado, tecnico_id FROM reservas WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $reservaId, $usuarioId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$res || $res['estado'] !== 'completada') {
        echo json_encode(['success' => false, 'message' => 'La reserva no está lista para ser pagada.']);
        exit;
    }
    
    // Verificar si ya existe un pago
    $stmt = $conexion->prepare("SELECT id FROM pagos WHERE reserva_id = ? AND estado = 'completado'");
    $stmt->bind_param("i", $reservaId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Esta reserva ya ha sido pagada.']);
        exit;
    }
    $stmt->close();
    
    // Simular procesamiento y guardar pago
    $referencia = 'SIM-' . strtoupper(uniqid());
    
    $tipoPago = $_POST['tipo_pago'] ?? 'Tarjeta';
    if ($tipoPago === 'Transferencia') {
        $banco = $_POST['banco_pse'] ?? 'Banco Desconocido';
        $metodo_pago = 'PSE - ' . $banco;
    } else {
        $metodo_pago = 'Tarjeta (Simulado)';
    }
    
    $estado = 'completado';
    
    $stmt = $conexion->prepare(
        "INSERT INTO pagos (reserva_id, usuario_id, monto, metodo_pago, estado, referencia)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
        exit;
    }
    
    $stmt->bind_param("iidsss", $reservaId, $usuarioId, $monto, $metodo_pago, $estado, $referencia);
    if ($stmt->execute()) {
        // Registrar la ganancia del técnico ahora que se ha pagado
        require_once __DIR__ . '/../modelo/Tecnico.php';
        $tecnicoModelo = new Tecnico();
        $tecnicoModelo->registrarGanancia($reservaId, $res['tecnico_id']);
        
        echo json_encode(['success' => true, 'referencia' => $referencia]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar el pago.']);
    }
    $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
exit;

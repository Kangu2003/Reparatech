<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'tecnico') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit();
}

require_once __DIR__ . '/../modelo/Tecnico.php';

$accion = $_POST['accion'] ?? '';
$tecnicoId = (int)$_SESSION['id'];

if ($accion === 'solicitar') {
    $monto = (float)($_POST['monto'] ?? 0);
    $banco = trim($_POST['banco'] ?? '');
    $tipoCuenta = trim($_POST['tipo_cuenta'] ?? '');
    $numeroCuenta = trim($_POST['numero_cuenta'] ?? '');

    if ($monto < 10000) {
        echo json_encode(['success' => false, 'message' => 'El monto mínimo de retiro es $10.000 COP.']);
        exit();
    }
    if (empty($banco) || empty($tipoCuenta) || empty($numeroCuenta)) {
        echo json_encode(['success' => false, 'message' => 'Todos los datos bancarios son obligatorios.']);
        exit();
    }

    $modelo = new Tecnico();
    $resultado = $modelo->solicitarRetiro($tecnicoId, $monto, $banco, $tipoCuenta, $numeroCuenta);

    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Solicitud de retiro enviada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fondos insuficientes o error en el sistema.']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Acción inválida.']);

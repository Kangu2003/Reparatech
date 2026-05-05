<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'usuario') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

require_once __DIR__ . '/../modelo/Usuario.php';

$accion = $_POST['accion'] ?? '';
$cliente_id = (int)($_SESSION['id'] ?? 0);

if ($accion === 'toggle') {
    $tecnico_id = (int)($_POST['tecnico_id'] ?? 0);
    if ($tecnico_id > 0) {
        $usuarioModel = new Usuario();
        $resultado = $usuarioModel->toggleFavorito($cliente_id, $tecnico_id);
        echo json_encode($resultado);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID de técnico inválido']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);

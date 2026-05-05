<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
    exit();
}

$usuarioId = (int)($_SESSION['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../vista/mis-reservas.php');
    exit();
}

$reservaId = (int)($_POST['reserva_id'] ?? 0);
$tecnicoId = (int)($_POST['tecnico_id'] ?? 0);
$calificacion = (int)($_POST['calificacion'] ?? 0);
$aspectos = trim($_POST['aspectos'] ?? '');
$comentarioBase = trim($_POST['comentario'] ?? '');

if ($reservaId === 0 || $tecnicoId === 0 || $calificacion < 1 || $calificacion > 5) {
    header("Location: ../vista/resena.php?reserva={$reservaId}&error=1");
    exit();
}

// Combinar los aspectos elegidos con el comentario
$comentario = $comentarioBase;
if (!empty($aspectos)) {
    $comentario = "Aspectos a destacar: " . $aspectos . "\n\n" . $comentarioBase;
}

require_once __DIR__ . '/../modelo/Conexion.php';
$db = (new Conexion())->getConexion();

// Verificar que la reserva pertenezca al usuario y esté completada
$stmtCheck = $db->prepare("SELECT id FROM reservas WHERE id = ? AND usuario_id = ? AND estado = 'completada'");
$stmtCheck->bind_param("ii", $reservaId, $usuarioId);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();
if ($resCheck->num_rows === 0) {
    $stmtCheck->close();
    header('Location: ../vista/mis-reservas.php?error=no_valido');
    exit();
}
$stmtCheck->close();

// Insertar la reseña en la base de datos
$stmt = $db->prepare("INSERT INTO resenas (reserva_id, usuario_id, tecnico_id, calificacion, comentario) VALUES (?, ?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("iiiis", $reservaId, $usuarioId, $tecnicoId, $calificacion, $comentario);
    if ($stmt->execute()) {
        header("Location: ../vista/resena.php?reserva={$reservaId}&ok=1");
    } else {
        header("Location: ../vista/resena.php?reserva={$reservaId}&error=1");
    }
    $stmt->close();
} else {
    header("Location: ../vista/resena.php?reserva={$reservaId}&error=1");
}

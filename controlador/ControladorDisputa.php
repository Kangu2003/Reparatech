<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../modelo/Disputa.php';

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$usuarioId = (int)$_SESSION['id'];

if ($accion === 'crear') {
    $reservaId = (int)$_POST['reserva_id'];
    $motivo = trim($_POST['motivo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (!$reservaId || !$motivo || !$descripcion) {
        header('Location: ../vista/crear-disputa.php?reserva=' . $reservaId . '&error=' . urlencode('Todos los campos son obligatorios.'));
        exit();
    }

    $modelo = new Disputa();
    $disputaId = $modelo->crearDisputa($reservaId, $usuarioId, $motivo, $descripcion);
    if ($disputaId) {
        require_once __DIR__ . '/../utils/CorreoHelper.php';
        $correo = $_SESSION['correo'] ?? ''; // Asumiendo que está en sesión, si no, se saca del modelo.
        if (empty($correo)) {
            require_once __DIR__ . '/../modelo/Usuario.php';
            $u = (new Usuario())->obtenerPorId($usuarioId);
            $correo = $u['correo_electronico'] ?? '';
        }
        $nombre = $_SESSION['nombre'] ?? 'Cliente';
        CorreoHelper::enviarCorreoDisputaCreada($correo, $nombre, $disputaId);

        header('Location: ../vista/mis-reservas.php?ok_disputa=1');
    } else {
        header('Location: ../vista/crear-disputa.php?reserva=' . $reservaId . '&error=' . urlencode('Ya existe una disputa activa para esta reserva o hubo un error.'));
    }
    exit();
}

header('Location: ../vista/mis-reservas.php');

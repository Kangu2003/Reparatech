<?php
/**
 * controlador/ControladorReserva.php
 * Procesa la creación y cancelación de reservas
 */
session_start();
define('BASE_URL', '/inicio_sesion_mvc');

if (!isset($_SESSION['usuario'])) {
    header('Location: ' . BASE_URL . '/index.php?accion=login');
    exit();
}

if ($_SESSION['rol'] === 'tecnico') {
    header('Location: ' . BASE_URL . '/vista/tecnico/dashboard.php');
    exit();
}

require_once __DIR__ . '/../modelo/Servicio.php';

$modelo   = new Servicio();
$usuarioId = (int)($_SESSION['id'] ?? 0);
$accion    = $_POST['accion'] ?? 'crear';

// ─── Helper redirecciones ──────────────────────────────────
function error(string $msg, int $servicioId = 0): void {
    if ($servicioId) {
        header('Location: ' . BASE_URL . '/vista/reservar.php?servicio=' . $servicioId . '&error=' . urlencode($msg));
    } else {
        header('Location: ' . BASE_URL . '/vista/mis-reservas.php?error=' . urlencode($msg));
    }
    exit();
}

// ─── CREAR RESERVA ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion !== 'cancelar') {

    $servicioId = (int)($_POST['servicio_id'] ?? 0);
    $tecnicoId  = (int)($_POST['tecnico_id']  ?? 0);
    $fecha      = trim($_POST['fecha']         ?? '');
    $hora       = trim($_POST['hora']          ?? '');
    $direccion  = trim($_POST['direccion']     ?? '');
    $notas      = trim($_POST['notas']         ?? '');

    // Validaciones
    if (!$servicioId || !$tecnicoId) {
        error('Servicio no válido.', $servicioId);
    }

    if (empty($fecha) || empty($hora)) {
        error('La fecha y hora son obligatorias.', $servicioId);
    }

    if (strtotime($fecha) < strtotime('today')) {
        error('La fecha no puede ser en el pasado.', $servicioId);
    }

    if (empty($direccion)) {
        error('La dirección es obligatoria.', $servicioId);
    }

    // No puede reservar su propio servicio
    if ($tecnicoId === $usuarioId) {
        error('No puedes reservar tu propio servicio.', $servicioId);
    }

    if ($modelo->crearReserva($usuarioId, $servicioId, $tecnicoId, $fecha, $hora, $direccion, $notas)) {
        
        // --- NOTIFICACIÓN DE WHATSAPP AL TÉCNICO ---
        require_once __DIR__ . '/../modelo/Conexion.php';
        $db = (new Conexion())->getConexion();
        $stmt = $db->prepare("SELECT telefono, nombre_usuario FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $tecnicoId);
        $stmt->execute();
        $resTec = $stmt->get_result()->fetch_assoc();

        if ($resTec && !empty($resTec['telefono'])) {
            $nombreCliente = $_SESSION['nombre'] ?? 'Un cliente';
            $mensaje = "🔔 *Nueva Solicitud de Servicio*\n\nHola *{$resTec['nombre_usuario']}*, tienes una nueva reserva de *{$nombreCliente}* para el *{$fecha}* a las *{$hora}*.\n\n📍 Dirección: {$direccion}";
            
            // Integración con API de WhatsApp (Ej: UltraMsg, Twilio, Meta API)
            // Configura tus credenciales aquí:
            $instanceId = "instance172553"; 
            $token = "svhp7dq1l6cjvtoc"; 
            
            // Limpia el teléfono (solo números, con prefijo internacional)
            $telefono = preg_replace('/[^0-9]/', '', $resTec['telefono']); 
            
            $url = "https://api.ultramsg.com/$instanceId/messages/chat";
            $data = [
                'token' => $token,
                'to' => $telefono,
                'body' => $mensaje
            ];
            
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => $url,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_SSL_VERIFYPEER => false, // Evita bloqueos de SSL en XAMPP (Windows)
              CURLOPT_SSL_VERIFYHOST => false,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS => http_build_query($data),
              CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded"
              ),
            ));
            
            $respuesta = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);
            
            // Si quieres ver si UltraMsg devuelve un error, puedes guardar el log:
            // file_put_contents(__DIR__ . '/../wa_log.txt', "Tel: $telefono | Resp: $respuesta | Err: $error\n", FILE_APPEND);
        }
        // -------------------------------------------

        header('Location: ' . BASE_URL . '/vista/mis-reservas.php?ok=1');
        exit();
    } else {
        error('Error al crear la reserva. Intenta de nuevo.', $servicioId);
    }
}

// ─── CANCELAR RESERVA ──────────────────────────────────────
if ($accion === 'cancelar') {
    $reservaId = (int)($_POST['reserva_id'] ?? 0);

    if (!$reservaId) {
        error('Reserva no válida.');
    }

    if ($modelo->cancelarReserva($reservaId, $usuarioId)) {
        header('Location: ' . BASE_URL . '/vista/mis-reservas.php?cancelada=1');
    } else {
        error('No se pudo cancelar. Solo puedes cancelar reservas pendientes.');
    }
    exit();
}

header('Location: ' . BASE_URL . '/vista/buscar.php');
exit();

<?php
/**
 * controlador/ControladorTecnico.php
 * Maneja acciones POST del panel técnico
 */
session_start();

// ✅ BASE_URL centralizado dinámico
if (!defined('BASE_URL')) {
    $envBase = getenv('BASE_URL');
    if ($envBase !== false && $envBase !== '') {
        define('BASE_URL', $envBase);
    } else {
        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
        $dir = str_replace('\\', '/', dirname(__DIR__));
        $baseUrl = str_ireplace($docRoot, '', $dir);
        define('BASE_URL', $baseUrl);
    }
}

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'tecnico') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

require_once __DIR__ . '/../modelo/Tecnico.php';

$modelo    = new Tecnico();
$tecnicoId = (int)$_SESSION['id'];
$accion    = $_POST['accion'] ?? '';

switch ($accion) {

    case 'crear_servicio':
        $modelo->crearServicio(
            $tecnicoId,
            (int)  ($_POST['categoria_id'] ?? 0),
            trim(   $_POST['titulo']        ?? ''),
            trim(   $_POST['descripcion']   ?? ''),
            (float)($_POST['precio']        ?? 0),
            in_array($_POST['precio_tipo'] ?? '', ['fijo','por_hora'])
                ? $_POST['precio_tipo'] : 'fijo'
        );
        break;

    case 'toggle_servicio':
        // ✅ Método dedicado solo para cambiar disponible
        $modelo->toggleDisponible(
            (int)($_POST['servicio_id'] ?? 0),
            $tecnicoId,
            (int)($_POST['disponible']  ?? 0)
        );
        break;

    case 'eliminar_servicio':
        $modelo->eliminarServicio(
            (int)($_POST['servicio_id'] ?? 0),
            $tecnicoId
        );
        break;

    case 'aceptar':
        $reservaId = (int)($_POST['reserva_id'] ?? 0);
        if ($modelo->actualizarEstadoReserva($reservaId, $tecnicoId, 'aceptada')) {
            // NOTIFICACIÓN WHATSAPP AL CLIENTE
            require_once __DIR__ . '/../modelo/Conexion.php';
            $db = (new Conexion())->getConexion();
            
            $stmt = $db->prepare("
                SELECT u.telefono, u.nombre_usuario, s.titulo, r.fecha, r.hora 
                FROM reservas r
                JOIN usuarios u ON r.usuario_id = u.id
                JOIN servicios s ON r.servicio_id = s.id
                WHERE r.id = ?
            ");
            $stmt->bind_param("i", $reservaId);
            $stmt->execute();
            $resCliente = $stmt->get_result()->fetch_assoc();

            if ($resCliente && !empty($resCliente['telefono'])) {
                $nombreTecnico = $_SESSION['nombre'] ?? 'Un técnico';
                $horaAmPm = date('h:i A', strtotime($resCliente['hora']));
                $fechaFormateada = date('d/m/Y', strtotime($resCliente['fecha']));
                
                $mensaje = "🎉 ¡Excelente noticia, *{$resCliente['nombre_usuario']}*! 🎉\n\n"
                         . "👷‍♂️ El técnico *{$nombreTecnico}* ha *APROBADO* tu solicitud de servicio.\n\n"
                         . "🛠️ *Servicio:* {$resCliente['titulo']}\n"
                         . "📅 *Fecha:* {$fechaFormateada}\n"
                         . "⏰ *Hora:* {$horaAmPm}\n\n"
                         . "¡Prepárate para recibir el mejor servicio! ✨ Nos vemos pronto.";
                
                $instanceId = "instance172553"; 
                $token = "svhp7dq1l6cjvtoc"; 
                
                $telefono = preg_replace('/[^0-9]/', '', $resCliente['telefono']); 
                
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
                  CURLOPT_SSL_VERIFYPEER => false,
                  CURLOPT_SSL_VERIFYHOST => false,
                  CURLOPT_CUSTOMREQUEST => "POST",
                  CURLOPT_POSTFIELDS => http_build_query($data),
                  CURLOPT_HTTPHEADER => array(
                    "content-type: application/x-www-form-urlencoded"
                  ),
                ));
                
                curl_exec($curl);
                curl_close($curl);
            }
        }
        break;

    case 'completar':
        $modelo->actualizarEstadoReserva(
            (int)($_POST['reserva_id'] ?? 0),
            $tecnicoId,
            'completada'
        );
        break;

    case 'cancelar':
        $reservaId = (int)($_POST['reserva_id'] ?? 0);
        if ($modelo->actualizarEstadoReserva($reservaId, $tecnicoId, 'cancelada')) {
            // NOTIFICACIÓN WHATSAPP AL CLIENTE (RECHAZO)
            require_once __DIR__ . '/../modelo/Conexion.php';
            $db = (new Conexion())->getConexion();
            
            $stmt = $db->prepare("
                SELECT u.telefono, u.nombre_usuario, s.titulo, r.fecha, r.hora 
                FROM reservas r
                JOIN usuarios u ON r.usuario_id = u.id
                JOIN servicios s ON r.servicio_id = s.id
                WHERE r.id = ?
            ");
            $stmt->bind_param("i", $reservaId);
            $stmt->execute();
            $resCliente = $stmt->get_result()->fetch_assoc();

            if ($resCliente && !empty($resCliente['telefono'])) {
                $nombreTecnico = $_SESSION['nombre'] ?? 'Un técnico';
                $horaAmPm = date('h:i A', strtotime($resCliente['hora']));
                $fechaFormateada = date('d/m/Y', strtotime($resCliente['fecha']));
                
                $mensaje = "😔 Hola *{$resCliente['nombre_usuario']}*,\n\n"
                         . "Lamentablemente el técnico *{$nombreTecnico}* no podrá atender tu solicitud de servicio en este momento debido a que se encuentra con la agenda llena.\n\n"
                         . "🛠️ *Servicio:* {$resCliente['titulo']}\n"
                         . "📅 *Fecha solicitada:* {$fechaFormateada}\n"
                         . "⏰ *Hora:* {$horaAmPm}\n\n"
                         . "Te invitamos a buscar otro técnico disponible en nuestra plataforma. ¡Gracias por tu comprensión! 🙏";
                
                $instanceId = "instance172553"; 
                $token = "svhp7dq1l6cjvtoc"; 
                
                $telefono = preg_replace('/[^0-9]/', '', $resCliente['telefono']); 
                
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
                  CURLOPT_SSL_VERIFYPEER => false,
                  CURLOPT_SSL_VERIFYHOST => false,
                  CURLOPT_CUSTOMREQUEST => "POST",
                  CURLOPT_POSTFIELDS => http_build_query($data),
                  CURLOPT_HTTPHEADER => array(
                    "content-type: application/x-www-form-urlencoded"
                  ),
                ));
                
                curl_exec($curl);
                curl_close($curl);
            }
        }
        break;

    case 'comprar_membresia':
        $resultado = $modelo->comprarMembresia($tecnicoId);
        if (!$resultado['success']) {
            header('Location: ' . BASE_URL . '/vista/tecnico/dashboard.php?error=saldo_insuficiente');
            exit();
        }
        break;

    case 'guardar_disponibilidad':
        $dias = [];
        foreach ($_POST['dias'] ?? [] as $dia => $valores) {
            if (!empty($valores['activo'])) {
                $dias[] = [
                    'dia'    => $dia,
                    'inicio' => $valores['inicio'] ?? '08:00',
                    'fin'    => $valores['fin']    ?? '18:00',
                ];
            }
        }
        $modelo->guardarDisponibilidad($tecnicoId, $dias);
        break;
}

header('Location: ' . BASE_URL . '/vista/tecnico/dashboard.php');
exit();

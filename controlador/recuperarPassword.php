<?php
/**
 * controlador/recuperarPassword.php
 * Controlador AJAX para gestionar la recuperación de contraseña vía WhatsApp
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../modelo/Usuario.php';
$modelo = new Usuario();

$accion = $_POST['accion'] ?? '';

if ($accion === 'solicitar_codigo') {
    $correo = trim($_POST['correo'] ?? '');
    
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Correo inválido.']);
        exit;
    }
    
    $telefono = $modelo->obtenerTelefonoPorCorreo($correo);
    if (!$telefono) {
        // No revelar si existe o no por seguridad, pero mostramos mensaje genérico.
        // Opcional: mostrar un mensaje específico
        echo json_encode(['success' => false, 'message' => 'No se encontró un usuario con ese correo electrónico.']);
        exit;
    }
    
    // Generar código de 6 dígitos
    $codigo = sprintf("%06d", mt_rand(1, 999999));
    
    if ($modelo->guardarCodigoRecuperacion($correo, $codigo)) {
        // Enviar por WhatsApp
        $instanceId = "instance172553"; 
        $token = "svhp7dq1l6cjvtoc"; 
        
        $telefonoStr = preg_replace('/[^0-9]/', '', $telefono); 
        
        $mensaje = "🔐 *Recuperación de Contraseña - ReparaTech*\n\n"
                 . "Tu código de verificación es: *{$codigo}*\n\n"
                 . "⚠️ Este código expirará en 15 minutos.\n"
                 . "Si no solicitaste este cambio, ignora este mensaje.";
        
        $url = "https://api.ultramsg.com/$instanceId/messages/chat";
        $data = [
            'token' => $token,
            'to' => $telefonoStr,
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
        
        $respuesta = curl_exec($curl);
        curl_close($curl);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al generar el código. Intenta nuevamente.']);
    }
    exit;
}

if ($accion === 'verificar_cambiar') {
    $correo = trim($_POST['correo'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');
    $nueva = $_POST['nueva_contrasena'] ?? '';
    
    if (empty($correo) || strlen($codigo) !== 6 || empty($nueva)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
        exit;
    }
    
    if (strlen($nueva) < 6) {
        echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.']);
        exit;
    }
    
    if ($modelo->restablecerContrasena($correo, $codigo, $nueva)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'El código es incorrecto o ha expirado.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
exit;

<?php
class CorreoHelper {
    public static function enviarCorreoDisputaCreada($destinatario, $clienteNombre, $disputaId) {
        $asunto = "ReparaTech - Nueva disputa generada (#$disputaId)";
        $mensaje = "Hola $clienteNombre,\n\nHemos recibido tu disputa (#$disputaId). Nuestro equipo de soporte la revisará a la brevedad posible.\n\nAtentamente,\nEquipo ReparaTech.";
        $headers = "From: soporte@reparatech.com\r\n" .
                   "Reply-To: soporte@reparatech.com\r\n" .
                   "X-Mailer: PHP/" . phpversion();
        @mail($destinatario, $asunto, $mensaje, $headers);
    }

    public static function enviarCorreoDisputaActualizada($destinatario, $clienteNombre, $disputaId, $estado) {
        $asunto = "ReparaTech - Actualización de tu disputa (#$disputaId)";
        $mensaje = "Hola $clienteNombre,\n\nTu disputa (#$disputaId) ha cambiado su estado a: $estado.\n\nPuedes revisar los detalles y la respuesta de nuestro equipo de soporte en tu panel de usuario.\n\nAtentamente,\nEquipo ReparaTech.";
        $headers = "From: soporte@reparatech.com\r\n" .
                   "Reply-To: soporte@reparatech.com\r\n" .
                   "X-Mailer: PHP/" . phpversion();
        @mail($destinatario, $asunto, $mensaje, $headers);
    }
}

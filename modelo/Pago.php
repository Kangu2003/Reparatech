<?php
require_once __DIR__ . '/Conexion.php';

class Pago {
    private $conexion;

    public function __construct() {
        $this->conexion = (new Conexion())->getConexion();
    }

    public function obtenerFacturasUsuario(int $usuarioId): array {
        // Obtenemos todas las reservas completadas para el usuario.
        // Si tienen un registro en pagos, están "pagadas". Si no, están "pendientes".
        $sql = "SELECT r.id AS reserva_id, r.fecha AS fecha_servicio, s.titulo AS servicio, s.precio AS monto,
                       u.nombre_usuario AS tecnico_nombre,
                       p.id AS pago_id, p.referencia, p.metodo_pago, p.creado_en AS fecha_pago
                FROM reservas r
                JOIN servicios s ON r.servicio_id = s.id
                JOIN usuarios u ON r.tecnico_id = u.id
                LEFT JOIN pagos p ON p.reserva_id = r.id AND p.estado = 'completado'
                WHERE r.usuario_id = ? AND r.estado = 'completada'
                ORDER BY r.creado_en DESC";
                
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $resultado;
    }

    public function obtenerDetalleFactura(int $reservaId, int $usuarioId): ?array {
        $sql = "SELECT r.id AS reserva_id, r.fecha AS fecha_servicio, r.hora, r.direccion,
                       s.titulo AS servicio, s.precio AS monto,
                       t.nombre_usuario AS tecnico_nombre, t.telefono AS tecnico_tel,
                       c.nombre_usuario AS cliente_nombre, c.correo_electronico AS cliente_correo,
                       p.referencia, p.metodo_pago, p.creado_en AS fecha_pago
                FROM reservas r
                JOIN servicios s ON r.servicio_id = s.id
                JOIN usuarios t ON r.tecnico_id = t.id
                JOIN usuarios c ON r.usuario_id = c.id
                JOIN pagos p ON p.reserva_id = r.id AND p.estado = 'completado'
                WHERE r.id = ? AND r.usuario_id = ?";
                
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("ii", $reservaId, $usuarioId);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $resultado ?: null;
    }
}

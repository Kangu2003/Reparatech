<?php
require_once __DIR__ . '/Conexion.php';

class Disputa {
    private $conexion;

    public function __construct() {
        $this->conexion = (new Conexion())->getConexion();
    }

    public function crearDisputa(int $reservaId, int $creadorId, string $motivo, string $descripcion): bool {
        // Verificar que no exista ya una disputa abierta para esta reserva por este usuario
        $stmtCheck = $this->conexion->prepare("SELECT id FROM disputas WHERE reserva_id = ? AND creador_id = ? AND estado NOT IN ('cerrada', 'resuelta')");
        if (!$stmtCheck) return false;
        $stmtCheck->bind_param("ii", $reservaId, $creadorId);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        if ($stmtCheck->num_rows > 0) {
            $stmtCheck->close();
            return false; // Ya existe disputa
        }
        $stmtCheck->close();

        $stmt = $this->conexion->prepare(
            "INSERT INTO disputas (reserva_id, creador_id, motivo, descripcion) VALUES (?, ?, ?, ?)"
        );
        if (!$stmt) return false;
        
        $stmt->bind_param("iiss", $reservaId, $creadorId, $motivo, $descripcion);
        if ($stmt->execute()) {
            $id = $this->conexion->insert_id;
            $stmt->close();
            return $id;
        }
        $stmt->close();
        return false;
    }

    public function obtenerDisputasPorUsuario(int $usuarioId): array {
        $stmt = $this->conexion->prepare(
            "SELECT d.*, s.titulo AS servicio, u.nombre_usuario AS tecnico
             FROM disputas d
             JOIN reservas r ON d.reserva_id = r.id
             JOIN servicios s ON r.servicio_id = s.id
             JOIN usuarios u ON r.tecnico_id = u.id
             WHERE d.creador_id = ?
             ORDER BY d.creado_en DESC"
        );
        if (!$stmt) return [];
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $resultado;
    }

    // ─── ADMIN METODOS ─────────────────────────────────────
    public function obtenerTodasLasDisputas(): array {
        $stmt = $this->conexion->prepare(
            "SELECT d.*, 
                    s.titulo AS servicio, 
                    u_cliente.nombre_usuario AS cliente, 
                    u_cliente.correo_electronico AS cliente_correo,
                    u_tecnico.nombre_usuario AS tecnico,
                    u_tecnico.correo_electronico AS tecnico_correo
             FROM disputas d
             JOIN reservas r ON d.reserva_id = r.id
             JOIN servicios s ON r.servicio_id = s.id
             JOIN usuarios u_cliente ON d.creador_id = u_cliente.id
             JOIN usuarios u_tecnico ON r.tecnico_id = u_tecnico.id
             ORDER BY d.creado_en DESC"
        );
        if (!$stmt) return [];
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $resultado;
    }

    public function actualizarDisputaAdmin(int $disputaId, string $estado, string $respuesta): bool {
        $estadosValidos = ['abierta', 'en_revision', 'resuelta', 'cerrada'];
        if (!in_array($estado, $estadosValidos)) return false;

        $stmt = $this->conexion->prepare("UPDATE disputas SET estado = ?, admin_respuesta = ? WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("ssi", $estado, $respuesta, $disputaId);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }
    
    public function obtenerDisputaPorId(int $disputaId) {
        $stmt = $this->conexion->prepare(
            "SELECT d.*, 
                    s.titulo AS servicio, 
                    u_cliente.nombre_usuario AS cliente, 
                    u_cliente.correo_electronico AS cliente_correo,
                    u_tecnico.nombre_usuario AS tecnico,
                    u_tecnico.correo_electronico AS tecnico_correo
             FROM disputas d
             JOIN reservas r ON d.reserva_id = r.id
             JOIN servicios s ON r.servicio_id = s.id
             JOIN usuarios u_cliente ON d.creador_id = u_cliente.id
             JOIN usuarios u_tecnico ON r.tecnico_id = u_tecnico.id
             WHERE d.id = ?"
        );
        if (!$stmt) return false;
        $stmt->bind_param("i", $disputaId);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $resultado;
    }
}

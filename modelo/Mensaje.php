<?php
require_once __DIR__ . '/Conexion.php';

class Mensaje {
    private $conexion;

    public function __construct() {
        $this->conexion = (new Conexion())->getConexion();
    }

    // ─── OBTENER MENSAJES DE UNA RESERVA ──────────────────
    public function obtenerMensajesPorReserva(int $reservaId): array {
        $stmt = $this->conexion->prepare(
            "SELECT m.id, m.remitente_id, m.mensaje, m.leido, m.creado_en, 
                    u.nombre_usuario, u.foto
             FROM mensajes m
             JOIN usuarios u ON m.remitente_id = u.id
             WHERE m.reserva_id = ?
             ORDER BY m.creado_en ASC"
        );
        if (!$stmt) return [];

        $stmt->bind_param("i", $reservaId);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $resultado;
    }

    // ─── ENVIAR MENSAJE ───────────────────────────────────
    public function enviarMensaje(int $reservaId, int $remitenteId, string $mensaje): bool {
        $stmt = $this->conexion->prepare(
            "INSERT INTO mensajes (reserva_id, remitente_id, mensaje) VALUES (?, ?, ?)"
        );
        if (!$stmt) return false;

        $stmt->bind_param("iis", $reservaId, $remitenteId, $mensaje);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    // ─── MARCAR COMO LEÍDOS ───────────────────────────────
    // Marca como leídos los mensajes de la reserva donde el remitente NO es el usuario actual.
    public function marcarComoLeidos(int $reservaId, int $miUsuarioId): bool {
        $stmt = $this->conexion->prepare(
            "UPDATE mensajes SET leido = 1 WHERE reserva_id = ? AND remitente_id != ?"
        );
        if (!$stmt) return false;

        $stmt->bind_param("ii", $reservaId, $miUsuarioId);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    // ─── OBTENER CHATS ACTIVOS (INBOX) ────────────────────
    public function obtenerChatsActivos(int $usuarioId, string $rol): array {
        // Obtenemos las reservas que tienen mensajes, o que están en progreso/aceptadas.
        if ($rol === 'tecnico') {
            $condicion = "r.tecnico_id = ?";
            $joinUser = "JOIN usuarios u ON r.usuario_id = u.id";
            $nombreAlias = "u.nombre_usuario AS contraparte";
            $fotoAlias = "u.foto AS contraparte_foto";
        } else {
            $condicion = "r.usuario_id = ?";
            $joinUser = "JOIN usuarios u ON r.tecnico_id = u.id";
            $nombreAlias = "u.nombre_usuario AS contraparte";
            $fotoAlias = "u.foto AS contraparte_foto";
        }

        $sql = "
            SELECT r.id AS reserva_id, r.estado, s.titulo AS servicio,
                   $nombreAlias, $fotoAlias,
                   (SELECT mensaje FROM mensajes WHERE reserva_id = r.id ORDER BY creado_en DESC LIMIT 1) AS ultimo_mensaje,
                   (SELECT creado_en FROM mensajes WHERE reserva_id = r.id ORDER BY creado_en DESC LIMIT 1) AS fecha_ultimo_mensaje,
                   (SELECT COUNT(*) FROM mensajes WHERE reserva_id = r.id AND remitente_id != ? AND leido = 0) AS no_leidos
            FROM reservas r
            JOIN servicios s ON r.servicio_id = s.id
            $joinUser
            WHERE $condicion AND r.estado IN ('aceptada', 'en_progreso', 'completada')
            ORDER BY fecha_ultimo_mensaje DESC, r.creado_en DESC
        ";

        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param("ii", $usuarioId, $usuarioId);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $resultado;
    }

    // ─── CONTADOR GLOBAL DE MENSAJES NO LEÍDOS ────────────
    public function contarNoLeidosGlobal(int $usuarioId, string $rol): int {
        if ($rol === 'tecnico') {
            $condicion = "r.tecnico_id = ?";
        } else {
            $condicion = "r.usuario_id = ?";
        }

        $stmt = $this->conexion->prepare(
            "SELECT COUNT(*) 
             FROM mensajes m
             JOIN reservas r ON m.reserva_id = r.id
             WHERE $condicion AND m.remitente_id != ? AND m.leido = 0"
        );
        if (!$stmt) return 0;

        $stmt->bind_param("ii", $usuarioId, $usuarioId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        
        return $count ?? 0;
    }
}

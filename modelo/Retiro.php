<?php
require_once __DIR__ . '/Conexion.php';

class Retiro {
    private $conexion;

    public function __construct() {
        $this->conexion = (new Conexion())->getConexion();
    }

    public function obtenerTodosLosRetiros(): array {
        $query = "
            SELECT r.id, r.monto, r.banco, r.tipo_cuenta, r.numero_cuenta, r.estado, r.creado_en,
                   u.nombre_usuario AS tecnico_nombre, u.correo_electronico
            FROM retiros r
            JOIN usuarios u ON r.tecnico_id = u.id
            ORDER BY r.creado_en DESC
        ";
        $resultado = $this->conexion->query($query);
        if (!$resultado) return [];
        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    public function cambiarEstado(int $retiro_id, string $estado): bool {
        if (!in_array($estado, ['pendiente', 'aprobado', 'rechazado'])) return false;
        
        $stmt = $this->conexion->prepare("UPDATE retiros SET estado = ? WHERE id = ?");
        if (!$stmt) return false;
        
        $stmt->bind_param("si", $estado, $retiro_id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }
}

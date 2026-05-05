<?php
require_once __DIR__ . '/Conexion.php';

class Tecnico {
    private $conexion;

    public function __construct() {
        $this->conexion = (new Conexion())->getConexion();
    }

    // ─── SERVICIOS ─────────────────────────────────────────

    public function crearServicio(int $tecnico_id, int $categoria_id, string $titulo, string $descripcion, float $precio, string $precio_tipo): bool {
        $stmt = $this->conexion->prepare(
            "INSERT INTO servicios (tecnico_id, categoria_id, titulo, descripcion, precio, precio_tipo)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) return false;
        $stmt->bind_param("iissds", $tecnico_id, $categoria_id, $titulo, $descripcion, $precio, $precio_tipo);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    public function editarServicio(int $servicio_id, int $tecnico_id, int $categoria_id, string $titulo, string $descripcion, float $precio, string $precio_tipo, int $disponible): bool {
        $stmt = $this->conexion->prepare(
            "UPDATE servicios SET categoria_id=?, titulo=?, descripcion=?, precio=?, precio_tipo=?, disponible=?
             WHERE id=? AND tecnico_id=?"
        );
        if (!$stmt) return false;
        $stmt->bind_param("issdssii", $categoria_id, $titulo, $descripcion, $precio, $precio_tipo, $disponible, $servicio_id, $tecnico_id);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    public function eliminarServicio(int $servicio_id, int $tecnico_id): bool {
        $stmt = $this->conexion->prepare(
            "DELETE FROM servicios WHERE id=? AND tecnico_id=?"
        );
        if (!$stmt) return false;
        $stmt->bind_param("ii", $servicio_id, $tecnico_id);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    // ✅ Solo cambia el campo disponible — sin tocar el resto
    public function toggleDisponible(int $servicio_id, int $tecnico_id, int $disponible): bool {
        $stmt = $this->conexion->prepare(
            "UPDATE servicios SET disponible = ? WHERE id = ? AND tecnico_id = ?"
        );
        if (!$stmt) return false;
        $stmt->bind_param("iii", $disponible, $servicio_id, $tecnico_id);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    public function obtenerMisServicios(int $tecnico_id): array {
        $stmt = $this->conexion->prepare(
            "SELECT s.id, s.titulo, s.descripcion, s.precio, s.precio_tipo, s.disponible,
                    c.nombre AS categoria, c.icono
             FROM servicios s
             JOIN categorias c ON s.categoria_id = c.id
             WHERE s.tecnico_id = ?
             ORDER BY s.creado_en DESC"
        );
        if (!$stmt) return [];
        $stmt->bind_param("i", $tecnico_id);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $resultado;
    }

    // ─── CATEGORÍAS ────────────────────────────────────────

    public function obtenerCategorias(): array {
        $result = $this->conexion->query("SELECT id, nombre, icono FROM categorias ORDER BY nombre");
        if (!$result) return [];
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // ─── DISPONIBILIDAD ────────────────────────────────────

    public function guardarDisponibilidad(int $tecnico_id, array $dias): bool {
        // Borrar disponibilidad anterior
        $stmt = $this->conexion->prepare("DELETE FROM disponibilidad WHERE tecnico_id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $tecnico_id);
        $stmt->execute();
        $stmt->close();

        // Insertar nueva
        $stmt = $this->conexion->prepare(
            "INSERT INTO disponibilidad (tecnico_id, dia_semana, hora_inicio, hora_fin)
             VALUES (?, ?, ?, ?)"
        );
        if (!$stmt) return false;

        foreach ($dias as $dia) {
            $stmt->bind_param("isss", $tecnico_id, $dia['dia'], $dia['inicio'], $dia['fin']);
            $stmt->execute();
        }
        $stmt->close();
        return true;
    }

    public function obtenerDisponibilidad(int $tecnico_id): array {
        $stmt = $this->conexion->prepare(
            "SELECT dia_semana, hora_inicio, hora_fin FROM disponibilidad WHERE tecnico_id = ? ORDER BY FIELD(dia_semana,'lunes','martes','miercoles','jueves','viernes','sabado','domingo')"
        );
        if (!$stmt) return [];
        $stmt->bind_param("i", $tecnico_id);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $resultado;
    }

    // ─── RESERVAS ──────────────────────────────────────────

    public function obtenerSolicitudes(int $tecnico_id): array {
        $stmt = $this->conexion->prepare(
            "SELECT r.id, r.fecha, r.hora, r.direccion, r.notas, r.estado, r.precio_final,
                    s.titulo AS servicio, s.precio,
                    u.nombre_usuario AS cliente, u.telefono AS cliente_tel, u.foto AS cliente_foto
             FROM reservas r
             JOIN servicios s ON r.servicio_id = s.id
             JOIN usuarios  u ON r.usuario_id  = u.id
             WHERE r.tecnico_id = ?
             ORDER BY r.creado_en DESC"
        );
        if (!$stmt) return [];
        $stmt->bind_param("i", $tecnico_id);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $resultado;
    }

    public function actualizarEstadoReserva(int $reserva_id, int $tecnico_id, string $estado): bool {
        $estadosValidos = ['aceptada', 'en_progreso', 'completada', 'cancelada'];
        if (!in_array($estado, $estadosValidos)) return false;

        $stmt = $this->conexion->prepare(
            "UPDATE reservas SET estado=? WHERE id=? AND tecnico_id=?"
        );
        if (!$stmt) return false;
        $stmt->bind_param("sii", $estado, $reserva_id, $tecnico_id);
        $resultado = $stmt->execute();
        $stmt->close();

        return $resultado;
    }

    public function registrarGanancia(int $reserva_id, int $tecnico_id): void {
        $stmt = $this->conexion->prepare(
            "INSERT IGNORE INTO ganancias (tecnico_id, reserva_id, monto, fecha)
             SELECT ?, r.id, s.precio, r.fecha
             FROM reservas r JOIN servicios s ON r.servicio_id = s.id
             WHERE r.id = ?"
        );
        if (!$stmt) return;
        $stmt->bind_param("ii", $tecnico_id, $reserva_id);
        $stmt->execute();
        $stmt->close();
    }

    // ─── GANANCIAS ─────────────────────────────────────────

    public function obtenerGanancias(int $tecnico_id): array {
        $stmt = $this->conexion->prepare(
            "SELECT
               COUNT(*)        AS total_servicios,
               SUM(monto)      AS total_ganado,
               AVG(monto)      AS promedio_servicio,
               MAX(monto)      AS mayor_servicio
             FROM ganancias WHERE tecnico_id = ?"
        );
        if (!$stmt) return [];
        $stmt->bind_param("i", $tecnico_id);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $resultado ?? [];
    }

    public function obtenerHistorialGanancias(int $tecnico_id, int $limite = 10): array {
        $stmt = $this->conexion->prepare(
            "SELECT g.monto, g.fecha, s.titulo AS servicio, u.nombre_usuario AS cliente
             FROM ganancias g
             JOIN reservas  r ON g.reserva_id  = r.id
             JOIN servicios s ON r.servicio_id  = s.id
             JOIN usuarios  u ON r.usuario_id   = u.id
             WHERE g.tecnico_id = ?
             ORDER BY g.fecha DESC LIMIT ?"
        );
        if (!$stmt) return [];
        $stmt->bind_param("ii", $tecnico_id, $limite);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $resultado;
    }

    // ─── RETIROS ───────────────────────────────────────────

    public function obtenerRetiros(int $tecnico_id): array {
        $stmt = $this->conexion->prepare(
            "SELECT id, monto, banco, tipo_cuenta, numero_cuenta, estado, creado_en
             FROM retiros
             WHERE tecnico_id = ?
             ORDER BY creado_en DESC"
        );
        if (!$stmt) return [];
        $stmt->bind_param("i", $tecnico_id);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $resultado;
    }

    public function solicitarRetiro(int $tecnico_id, float $monto, string $banco, string $tipo_cuenta, string $numero_cuenta): bool {
        $ganancias = $this->obtenerGanancias($tecnico_id);
        $totalGanado = (float)($ganancias['total_ganado'] ?? 0);
        
        $stmt = $this->conexion->prepare("SELECT SUM(monto) AS total_retirado FROM retiros WHERE tecnico_id = ? AND estado != 'rechazado'");
        $stmt->bind_param("i", $tecnico_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $totalRetirado = (float)($res['total_retirado'] ?? 0);
        $saldoDisponible = $totalGanado - $totalRetirado;

        if ($monto <= 0 || $monto > $saldoDisponible) {
            return false;
        }

        $estado = ($monto < 200000) ? 'aprobado' : 'pendiente';

        $stmt = $this->conexion->prepare(
            "INSERT INTO retiros (tecnico_id, monto, banco, tipo_cuenta, numero_cuenta, estado)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) return false;
        
        $stmt->bind_param("idssss", $tecnico_id, $monto, $banco, $tipo_cuenta, $numero_cuenta, $estado);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    // ─── RESEÑAS ───────────────────────────────────────────

    public function obtenerResenas(int $tecnico_id): array {
        $stmt = $this->conexion->prepare(
            "SELECT r.calificacion, r.comentario, r.creado_en,
                    u.nombre_usuario AS cliente, u.foto AS cliente_foto,
                    s.titulo AS servicio
             FROM resenas r
             JOIN usuarios  u ON r.usuario_id  = u.id
             JOIN reservas  rv ON r.reserva_id = rv.id
             JOIN servicios s  ON rv.servicio_id = s.id
             WHERE r.tecnico_id = ?
             ORDER BY r.creado_en DESC"
        );
        if (!$stmt) return [];
        $stmt->bind_param("i", $tecnico_id);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $resultado;
    }

    public function esPremium(int $tecnico_id): bool {
        $stmt = $this->conexion->prepare("SELECT es_premium FROM usuarios WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $tecnico_id);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return !empty($resultado['es_premium']);
    }

    public function esExperto(int $tecnico_id): bool {
        $stmt = $this->conexion->prepare("SELECT es_experto FROM usuarios WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $tecnico_id);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return !empty($resultado['es_experto']);
    }

    public function activarPremium(int $tecnico_id): bool {
        $stmt = $this->conexion->prepare("UPDATE usuarios SET es_premium = 1 WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $tecnico_id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    public function comprarMembresia(int $tecnico_id): array {
        $ganancias = $this->obtenerGanancias($tecnico_id);
        $totalGanado = (float)($ganancias['total_ganado'] ?? 0);
        
        $stmt = $this->conexion->prepare("SELECT SUM(monto) AS total_retirado FROM retiros WHERE tecnico_id = ? AND estado != 'rechazado'");
        if (!$stmt) return ['success' => false, 'message' => 'Error al calcular saldo'];
        $stmt->bind_param("i", $tecnico_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $totalRetirado = (float)($res['total_retirado'] ?? 0);
        $saldoDisponible = $totalGanado - $totalRetirado;

        if ($saldoDisponible < 49000) {
            return ['success' => false, 'message' => 'Saldo insuficiente para adquirir la membresía.'];
        }

        $stmt = $this->conexion->prepare(
            "INSERT INTO retiros (tecnico_id, monto, banco, tipo_cuenta, numero_cuenta, estado)
             VALUES (?, 49000, 'ReparaTech', 'Membresía', 'Pago Premium', 'aprobado')"
        );
        if (!$stmt) return ['success' => false, 'message' => 'Error al procesar pago'];
        $stmt->bind_param("i", $tecnico_id);
        $stmt->execute();
        $stmt->close();

        $this->activarPremium($tecnico_id);

        return ['success' => true, 'message' => 'Membresía premium adquirida con éxito.'];
    }

    public function obtenerCalificacionPromedio(int $tecnico_id): float {
        $stmt = $this->conexion->prepare(
            "SELECT AVG(calificacion) FROM resenas WHERE tecnico_id = ?"
        );
        if (!$stmt) return 0.0;
        $stmt->bind_param("i", $tecnico_id);
        $stmt->execute();
        $db_avg = null;
        $stmt->bind_result($db_avg);
        $stmt->fetch();
        $stmt->close();
        return round((float)($db_avg ?? 0), 1);
    }
}
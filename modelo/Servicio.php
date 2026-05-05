<?php
require_once __DIR__ . '/Conexion.php';

class Servicio {
    private $conexion;

    public function __construct() {
        $this->conexion = (new Conexion())->getConexion();
    }

    // ─── Buscar servicios con filtros ──────────────────────
    public function buscarServicios(string $busqueda = '', int $categoriaId = 0, string $ciudad = ''): array {
        $sql = "SELECT s.id, s.titulo, s.descripcion, s.precio, s.precio_tipo,
                       s.tecnico_id,
                       c.nombre AS categoria, c.icono,
                       u.nombre_usuario AS tecnico_nombre,
                       u.ciudad        AS tecnico_ciudad,
                       u.foto          AS tecnico_foto,
                       u.es_premium,
                       u.es_experto,
                       u.direccion_local,
                       u.latitud,
                       u.longitud,
                       ROUND(AVG(r.calificacion), 1) AS calificacion
                FROM servicios s
                JOIN categorias c ON s.categoria_id = c.id
                JOIN usuarios   u ON s.tecnico_id   = u.id
                LEFT JOIN resenas r ON r.tecnico_id  = u.id
                WHERE s.disponible = 1 AND u.activo = 1";

        $params = [];
        $types  = '';

        if ($busqueda) {
            $sql    .= " AND (s.titulo LIKE ? OR s.descripcion LIKE ? OR c.nombre LIKE ? OR u.nombre_usuario LIKE ?)";
            $like    = '%' . $busqueda . '%';
            $params  = array_merge($params, [$like, $like, $like, $like]);
            $types  .= 'ssss';
        }

        if ($categoriaId) {
            $sql    .= " AND s.categoria_id = ?";
            $params[] = $categoriaId;
            $types   .= 'i';
        }

        if ($ciudad) {
            $sql    .= " AND u.ciudad = ?";
            $params[] = $ciudad;
            $types   .= 's';
        }

        $sql .= " GROUP BY s.id ORDER BY calificacion DESC, s.creado_en DESC";

        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return [];

        if ($params) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $resultado;
    }

    // ─── Obtener servicio por ID ───────────────────────────
    public function obtenerServicioPorId(int $id): array|false {
        $stmt = $this->conexion->prepare(
            "SELECT s.id, s.titulo, s.descripcion, s.precio, s.precio_tipo,
                    s.tecnico_id,
                    c.nombre AS categoria, c.icono,
                    u.nombre_usuario AS tecnico_nombre,
                    u.ciudad         AS tecnico_ciudad,
                    u.foto           AS tecnico_foto,
                    u.es_premium,
                    u.es_experto,
                    u.direccion_local,
                    u.latitud,
                    u.longitud,
                    ROUND(AVG(r.calificacion), 1) AS calificacion
             FROM servicios s
             JOIN categorias c ON s.categoria_id = c.id
             JOIN usuarios   u ON s.tecnico_id   = u.id
             LEFT JOIN resenas r ON r.tecnico_id  = u.id
             WHERE s.id = ? AND s.disponible = 1
             GROUP BY s.id"
        );
        if (!$stmt) return false;

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $resultado ?: false;
    }

    // ─── Obtener categorías ────────────────────────────────
    public function obtenerCategorias(): array {
        $result = $this->conexion->query(
            "SELECT id, nombre, icono FROM categorias ORDER BY nombre"
        );
        if (!$result) return [];
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // ─── Crear reserva ─────────────────────────────────────
    public function crearReserva(int $usuarioId, int $servicioId, int $tecnicoId, string $fecha, string $hora, string $direccion, string $notas): bool {
        $stmt = $this->conexion->prepare(
            "INSERT INTO reservas (usuario_id, servicio_id, tecnico_id, fecha, hora, direccion, notas)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) return false;

        $stmt->bind_param("iiissss", $usuarioId, $servicioId, $tecnicoId, $fecha, $hora, $direccion, $notas);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    // ─── Reservas del usuario ──────────────────────────────
    public function obtenerReservasUsuario(int $usuarioId): array {
        $stmt = $this->conexion->prepare(
            "SELECT r.id, r.fecha, r.hora, r.direccion, r.estado, r.creado_en,
                    s.titulo AS servicio, s.precio, c.icono,
                    u.nombre_usuario AS tecnico, u.telefono AS tecnico_tel,
                    (SELECT COUNT(*) FROM resenas re WHERE re.reserva_id = r.id) as tiene_resena,
                    (SELECT COUNT(*) FROM pagos p WHERE p.reserva_id = r.id AND p.estado = 'completado') as pagado
             FROM reservas r
             JOIN servicios s  ON r.servicio_id  = s.id
             JOIN categorias c ON s.categoria_id = c.id
             JOIN usuarios   u ON r.tecnico_id   = u.id
             WHERE r.usuario_id = ?
             ORDER BY r.creado_en DESC"
        );
        if (!$stmt) return [];

        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $resultado;
    }

    // ─── Cancelar reserva (solo usuario, solo si está pendiente) ──
    public function cancelarReserva(int $reservaId, int $usuarioId): bool {
        $stmt = $this->conexion->prepare(
            "UPDATE reservas SET estado = 'cancelada'
             WHERE id = ? AND usuario_id = ? AND estado = 'pendiente'"
        );
        if (!$stmt) return false;

        $stmt->bind_param("ii", $reservaId, $usuarioId);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }
}

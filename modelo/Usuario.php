<?php
require_once __DIR__ . '/Conexion.php';

class Usuario {
    private $conexion;

    public function __construct() {
        $this->conexion = (new Conexion())->getConexion();
    }

    // ─── Registrar (usuario o técnico) ────────────────────
    public function registrar(string $nombre, string $correo, string $pass, string $rol = 'usuario', string $telefono = ''): bool {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $rol  = in_array($rol, ['usuario', 'tecnico']) ? $rol : 'usuario';

        $stmt = $this->conexion->prepare(
            "INSERT INTO usuarios (nombre_usuario, correo_electronico, contrasena, rol, telefono)
             VALUES (?, ?, ?, ?, ?)"
        );
        if (!$stmt) return false;

        $stmt->bind_param("sssss", $nombre, $correo, $hash, $rol, $telefono);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    // ─── Verificar credenciales ────────────────────────────
    public function verificarCredenciales(string $correo, string $pass): array|false {
        $stmt = $this->conexion->prepare(
            "SELECT nombre_usuario, contrasena, rol, foto, ciudad, telefono, bio, direccion_local, latitud, longitud
             FROM usuarios WHERE correo_electronico = ? AND activo = 1"
        );
        if (!$stmt) return false;

        $stmt->bind_param("s", $correo);
        $stmt->execute();

        $db_nombre   = null; $db_hash  = null; $db_rol    = null;
        $db_foto     = null; $db_ciudad = null; $db_tel   = null;
        $db_bio      = null; $db_direccion = null; $db_lat = null; $db_lng = null;

        $stmt->bind_result($db_nombre, $db_hash, $db_rol, $db_foto, $db_ciudad, $db_tel, $db_bio, $db_direccion, $db_lat, $db_lng);
        $encontrado = $stmt->fetch();
        $stmt->close();

        if ($encontrado && password_verify($pass, $db_hash)) {
            // Corrige la ruta de la foto dinámicamente según el entorno
            if ($db_foto) {
                $db_foto = ltrim(str_replace('/inicio_sesion_mvc/', '', $db_foto), '/');
                $db_foto = rtrim(BASE_URL, '/') . '/' . $db_foto;
            }

            return [
                'nombre'   => $db_nombre,
                'rol'      => $db_rol,
                'foto'     => $db_foto,
                'ciudad'   => $db_ciudad,
                'telefono' => $db_tel,
                'bio'      => $db_bio,
                'direccion_local' => $db_direccion,
                'latitud'  => $db_lat,
                'longitud' => $db_lng,
            ];
        }
        return false;
    }

    // ─── Actualizar perfil ─────────────────────────────────
    public function actualizarPerfil(
        string $correo,
        string $nombre,
        string $telefono  = '',
        string $ciudad    = '',
        string $bio       = '',
        string $foto      = '',
        string $passNueva = '',
        string $direccion_local = '',
        ?float $latitud = null,
        ?float $longitud = null
    ): bool {
        if (!empty($passNueva)) {
            $hash = password_hash($passNueva, PASSWORD_DEFAULT);
            $stmt = $this->conexion->prepare(
                "UPDATE usuarios SET nombre_usuario=?, telefono=?, ciudad=?,
                 bio=?, foto=?, contrasena=?, direccion_local=?, latitud=?, longitud=? WHERE correo_electronico=?"
            );
            if (!$stmt) return false;
            $stmt->bind_param("ssssssddds", $nombre, $telefono, $ciudad, $bio, $foto, $hash, $direccion_local, $latitud, $longitud, $correo);
        } else {
            $stmt = $this->conexion->prepare(
                "UPDATE usuarios SET nombre_usuario=?, telefono=?, ciudad=?,
                 bio=?, foto=?, direccion_local=?, latitud=?, longitud=? WHERE correo_electronico=?"
            );
            if (!$stmt) return false;
            $stmt->bind_param("ssssssdds", $nombre, $telefono, $ciudad, $bio, $foto, $direccion_local, $latitud, $longitud, $correo);
        }
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    // ─── Obtener ID por correo ─────────────────────────────
    public function obtenerIdPorCorreo(string $correo): int|false {
        $stmt = $this->conexion->prepare(
            "SELECT id FROM usuarios WHERE correo_electronico = ?"
        );
        if (!$stmt) return false;
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $db_id = null;
        $stmt->bind_result($db_id);
        $stmt->fetch();
        $stmt->close();
        return $db_id ?? false;
    }

    // ─── Métodos para Administrador ──────────────────────────
    public function obtenerTodos(string $rol = null): array {
        if ($rol) {
            $query = "SELECT id, nombre_usuario, correo_electronico, rol, telefono, ciudad, activo, creado_en FROM usuarios WHERE rol = ? ORDER BY creado_en DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->bind_param("s", $rol);
            $stmt->execute();
            $resultado = $stmt->get_result();
        } else {
            $query = "SELECT id, nombre_usuario, correo_electronico, rol, telefono, ciudad, activo, creado_en FROM usuarios ORDER BY creado_en DESC";
            $resultado = $this->conexion->query($query);
        }
        
        $usuarios = [];
        if ($resultado && $resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                $usuarios[] = $fila;
            }
        }
        return $usuarios;
    }

    public function cambiarEstadoActivo(int $id, int $estado): bool {
        $stmt = $this->conexion->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("ii", $estado, $id);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    // ─── RECUPERACIÓN DE CONTRASEÑA ────────────────────────
    
    public function obtenerTelefonoPorCorreo(string $correo): ?string {
        $stmt = $this->conexion->prepare("SELECT telefono FROM usuarios WHERE correo_electronico = ? AND activo = 1");
        if (!$stmt) return null;
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $stmt->bind_result($telefono);
        $stmt->fetch();
        $stmt->close();
        return $telefono;
    }

    public function guardarCodigoRecuperacion(string $correo, string $codigo): bool {
        // Expiración en 15 minutos
        $expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $stmt = $this->conexion->prepare("UPDATE usuarios SET codigo_recuperacion = ?, expiracion_codigo = ? WHERE correo_electronico = ?");
        if (!$stmt) return false;
        $stmt->bind_param("sss", $codigo, $expiracion, $correo);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    public function verificarCodigoRecuperacion(string $correo, string $codigo): bool {
        $stmt = $this->conexion->prepare("SELECT id FROM usuarios WHERE correo_electronico = ? AND codigo_recuperacion = ? AND expiracion_codigo >= NOW() AND activo = 1");
        if (!$stmt) return false;
        $stmt->bind_param("ss", $correo, $codigo);
        $stmt->execute();
        $stmt->store_result();
        $valido = $stmt->num_rows > 0;
        $stmt->close();
        return $valido;
    }

    public function restablecerContrasena(string $correo, string $codigo, string $nuevaPass): bool {
        if (!$this->verificarCodigoRecuperacion($correo, $codigo)) {
            return false;
        }
        $hash = password_hash($nuevaPass, PASSWORD_DEFAULT);
        $stmt = $this->conexion->prepare("UPDATE usuarios SET contrasena = ?, codigo_recuperacion = NULL, expiracion_codigo = NULL WHERE correo_electronico = ?");
        if (!$stmt) return false;
        $stmt->bind_param("ss", $hash, $correo);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    // ─── FAVORITOS ─────────────────────────────────────────

    public function toggleFavorito(int $cliente_id, int $tecnico_id): array {
        if ($this->esFavorito($cliente_id, $tecnico_id)) {
            $stmt = $this->conexion->prepare("DELETE FROM favoritos WHERE cliente_id = ? AND tecnico_id = ?");
            $stmt->bind_param("ii", $cliente_id, $tecnico_id);
            $stmt->execute();
            $stmt->close();
            return ['success' => true, 'action' => 'removed'];
        } else {
            $stmt = $this->conexion->prepare("INSERT INTO favoritos (cliente_id, tecnico_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $cliente_id, $tecnico_id);
            $stmt->execute();
            $stmt->close();
            return ['success' => true, 'action' => 'added'];
        }
    }

    public function esFavorito(int $cliente_id, int $tecnico_id): bool {
        $stmt = $this->conexion->prepare("SELECT id FROM favoritos WHERE cliente_id = ? AND tecnico_id = ?");
        $stmt->bind_param("ii", $cliente_id, $tecnico_id);
        $stmt->execute();
        $stmt->store_result();
        $existe = $stmt->num_rows > 0;
        $stmt->close();
        return $existe;
    }

    public function obtenerFavoritos(int $cliente_id): array {
        $stmt = $this->conexion->prepare("
            SELECT u.id, u.nombre_usuario, u.foto, u.ciudad, u.bio, u.es_premium, u.es_experto 
            FROM favoritos f
            JOIN usuarios u ON f.tecnico_id = u.id
            WHERE f.cliente_id = ?
            ORDER BY f.creado_en DESC
        ");
        $stmt->bind_param("i", $cliente_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $favoritos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $favoritos[] = $fila;
        }
        $stmt->close();
        return $favoritos;
    }
}

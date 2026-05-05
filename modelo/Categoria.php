<?php
require_once __DIR__ . '/Conexion.php';

class Categoria {
    private $db;

    public function __construct() {
        $conexion = new Conexion();
        $this->db = $conexion->getConexion();
    }

    // Obtener todas las categorías
    public function obtenerTodas() {
        $query = "SELECT * FROM categorias ORDER BY nombre ASC";
        $resultado = $this->db->query($query);
        $categorias = [];
        
        if ($resultado && $resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                $categorias[] = $fila;
            }
        }
        return $categorias;
    }

    // Obtener una categoría por ID
    public function obtenerPorId($id) {
        $query = "SELECT * FROM categorias WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    // Crear nueva categoría
    public function crear($nombre, $icono) {
        $query = "INSERT INTO categorias (nombre, icono) VALUES (?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $nombre, $icono);
        return $stmt->execute();
    }

    // Actualizar categoría
    public function actualizar($id, $nombre, $icono) {
        $query = "UPDATE categorias SET nombre = ?, icono = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssi", $nombre, $icono, $id);
        return $stmt->execute();
    }

    // Eliminar categoría
    public function eliminar($id) {
        // La restricción de llave foránea ON DELETE RESTRICT evitará borrar
        // categorías que tengan servicios asociados.
        $query = "DELETE FROM categorias WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>

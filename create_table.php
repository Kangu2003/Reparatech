<?php
require_once __DIR__ . '/modelo/Conexion.php';
$conexion = (new Conexion())->getConexion();
$query = "CREATE TABLE IF NOT EXISTS disputas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT NOT NULL,
    creador_id INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    estado ENUM('abierta', 'en_revision', 'resuelta', 'cerrada') DEFAULT 'abierta',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
    FOREIGN KEY (creador_id) REFERENCES usuarios(id) ON DELETE CASCADE
)";
if ($conexion->query($query)) {
    echo "Table 'disputas' created successfully.";
} else {
    echo "Error: " . $conexion->error;
}

<?php
class Conexion {
    private $conexion;
    public function __construct() {
        $config = require __DIR__ . '/../config/config.php';
        $this->conexion = new mysqli($config['host'], $config['usuario'], $config['contrasena'], $config['base_de_datos'], $config['puerto']);
        if ($this->conexion->connect_error) { die("Error: " . $this->conexion->connect_error); }
        $this->conexion->set_charset("utf8mb4");
    }
    public function getConexion(): mysqli { return $this->conexion; }
}
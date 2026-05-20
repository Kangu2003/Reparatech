<?php
require 'config/config.php';
require 'modelo/Conexion.php';
$db = (new Conexion())->getConexion();
$db->query("UPDATE usuarios SET foto = REPLACE(foto, '/inicio_sesion_mvc/', '/') WHERE foto LIKE '/inicio_sesion_mvc/%'");
echo 'Base de datos actualizada.';

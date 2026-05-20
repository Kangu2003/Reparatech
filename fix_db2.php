<?php
require 'config/config.php';
require 'modelo/Conexion.php';
$db = (new Conexion())->getConexion();

// Strip /inicio_sesion_mvc/
$db->query("UPDATE usuarios SET foto = REPLACE(foto, '/inicio_sesion_mvc/', '') WHERE foto LIKE '%/inicio_sesion_mvc/%'");

// Strip leading slash if any
$db->query("UPDATE usuarios SET foto = SUBSTRING(foto, 2) WHERE foto LIKE '/%'");

// Also fix resenas and anywhere else if necessary, but foto is only in usuarios.
echo 'Base de datos limpia. Rutas ahora son relativas.';

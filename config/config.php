<?php
// config/config.php
// Lee variables de entorno en producción (Render) o usa valores locales (XAMPP)
return [
    'host'          => getenv('DB_HOST')     ?: '127.0.0.1',
    'usuario'       => getenv('DB_USER')     ?: 'root',
    'contrasena'    => getenv('DB_PASSWORD') ?: '',
    'base_de_datos' => getenv('DB_NAME')     ?: 'reparatech',
    'puerto'        => (int)(getenv('DB_PORT') ?: 3306),
];
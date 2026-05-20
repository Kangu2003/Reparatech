<?php
// config/config.php
// Lee variables de entorno en producción (Render) o usa valores locales (XAMPP)
return [
    'host'          => getenv('DB_HOST')     ?: '127.0.0.1',
    'usuario'       => getenv('DB_USER')     ?: 'root',
    'contrasena'    => getenv('DB_PASSWORD') ?: '',
    'base_de_datos' => getenv('DB_NAME')     ?: 'reparatech',
    'puerto'        => (int)(getenv('DB_PORT') ?: 3306),
    
    // Google OAuth 2.0
    // ATENCIÓN: NUNCA subas tus credenciales reales a GitHub. Usa las variables de entorno en Render.
    'google_client_id'     => getenv('GOOGLE_CLIENT_ID')     ?: '',
    'google_client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
    // La URI de redirección debe coincidir exactamente con la configurada en Google Cloud Console
    'google_redirect_uri'  => getenv('GOOGLE_REDIRECT_URI')  ?: 'http://localhost/inicio_sesion_mvc/index.php?accion=google_callback'
];
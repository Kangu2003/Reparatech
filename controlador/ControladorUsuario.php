<?php
require_once __DIR__ . '/../modelo/Usuario.php';

class ControladorUsuario {
    private $modelo;

    public function __construct() {
        $this->modelo = new Usuario();
    }

    // ─── LOGIN ─────────────────────────────────────────────
    public function iniciarSesion(string $correo, string $pass): void {
        $correo = trim($correo);
        $pass   = trim($pass);

        if (empty($correo) || empty($pass)) {
            $error = "Por favor completa todos los campos.";
            require __DIR__ . '/../vista/login.php';
            return;
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = "El formato del correo no es válido.";
            require __DIR__ . '/../vista/login.php';
            return;
        }

        $datos = $this->modelo->verificarCredenciales($correo, $pass);

        if ($datos) {
            $_SESSION['usuario']  = htmlspecialchars($correo);
            $_SESSION['nombre']   = htmlspecialchars($datos['nombre']);
            $_SESSION['rol']      = $datos['rol'];
            $_SESSION['foto']     = $datos['foto']     ?? '';
            $_SESSION['ciudad']   = $datos['ciudad']   ?? '';
            $_SESSION['telefono'] = $datos['telefono'] ?? '';
            $_SESSION['bio']      = $datos['bio']      ?? '';
            $_SESSION['direccion_local'] = $datos['direccion_local'] ?? '';
            $_SESSION['latitud']  = $datos['latitud']  ?? null;
            $_SESSION['longitud'] = $datos['longitud'] ?? null;
            $_SESSION['id']       = $this->modelo->obtenerIdPorCorreo($correo);

            // ✅ Redirigir según rol
            if ($datos['rol'] === 'tecnico') {
                header('Location: ' . BASE_URL . '/vista/tecnico/dashboard.php');
            } elseif ($datos['rol'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin.php?accion=dashboard');
            } else {
                header('Location: ' . BASE_URL . '/vista/bienvenida.php');
            }
            exit();
        } else {
            $error = "Credenciales incorrectas.";
            require __DIR__ . '/../vista/login.php';
        }
    }

    // ─── REGISTRO ──────────────────────────────────────────
    public function registrar(string $nombre, string $correo, string $pass, string $rol, string $telefono = ''): void {
        $nombre = trim($nombre);
        $correo = trim($correo);
        $pass   = trim($pass);
        $telefono = preg_replace('/[^\+0-9]/', '', trim($telefono));
        if (!empty($telefono) && !str_starts_with($telefono, '+57')) {
            $telefono = '+57' . ltrim($telefono, '+');
        }
        $rol    = in_array($rol, ['usuario', 'tecnico']) ? $rol : 'usuario';

        if (empty($nombre) || empty($correo) || empty($pass) || empty($telefono)) {
            $error = "Por favor completa todos los campos.";
            require __DIR__ . '/../vista/registro.php';
            return;
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = "El formato del correo no es válido.";
            require __DIR__ . '/../vista/registro.php';
            return;
        }

        if (strlen($pass) < 6) {
            $error = "La contraseña debe tener mínimo 6 caracteres.";
            require __DIR__ . '/../vista/registro.php';
            return;
        }

        if ($this->modelo->registrar($nombre, $correo, $pass, $rol, $telefono)) {
            header('Location: ' . BASE_URL . '/index.php?accion=login&registro=exito');
            exit();
        } else {
            $error = "Error al registrar. El correo podría ya existir.";
            require __DIR__ . '/../vista/registro.php';
        }
    }

    // ─── LOGIN CON GOOGLE ──────────────────────────────────
    public function loginConGoogle(string $code, string $rolEsperado): void {
        $config = require __DIR__ . '/../config/config.php';
        $clientId = $config['google_client_id'] ?? '';
        $clientSecret = $config['google_client_secret'] ?? '';
        
        $protocol = (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $redirectUri = $protocol . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/index.php?accion=google_callback';

        // 1. Intercambiar code por Access Token
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
            'code' => $code
        ]));
        $response = curl_exec($ch);
        curl_close($ch);

        $tokenData = json_decode($response, true);
        if (!isset($tokenData['access_token'])) {
            header('Location: ' . BASE_URL . '/vista/login.php?error=' . urlencode('Error al autenticar con Google. (Falta Token)'));
            exit();
        }

        // 2. Obtener datos del usuario
        $chInfo = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($chInfo, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chInfo, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
        $infoResponse = curl_exec($chInfo);
        curl_close($chInfo);

        $googleUser = json_decode($infoResponse, true);
        if (!isset($googleUser['email'])) {
            header('Location: ' . BASE_URL . '/vista/login.php?error=' . urlencode('Error al obtener datos de Google.'));
            exit();
        }

        $correo = $googleUser['email'];
        $nombre = $googleUser['name'] ?? 'Usuario Google';
        $foto   = $googleUser['picture'] ?? '';

        // 3. Verificar si el usuario ya existe en BD (Login directo o Registro)
        $this->modelo->registrarDesdeGoogle($nombre, $correo, $foto, $rolEsperado);

        // 4. Iniciar sesión forzosamente obteniendo los datos actuales de la BD
        $datos = $this->modelo->obtenerDatosCompletosPorCorreo($correo);
        if ($datos && $datos['activo'] == 1) {
            $_SESSION['usuario']  = htmlspecialchars($correo);
            $_SESSION['nombre']   = htmlspecialchars($datos['nombre_usuario']);
            $_SESSION['rol']      = $datos['rol'];
            
            // Tratamiento dinámico de la foto (por si acaso es local o es la URL de Google directa)
            $db_foto = $datos['foto'] ?? '';
            if ($db_foto && !str_starts_with($db_foto, 'http')) {
                $db_foto = ltrim(str_replace('/inicio_sesion_mvc/', '', $db_foto), '/');
                $db_foto = rtrim(BASE_URL, '/') . '/' . $db_foto;
            }
            $_SESSION['foto']     = $db_foto;
            
            $_SESSION['ciudad']   = $datos['ciudad']   ?? '';
            $_SESSION['telefono'] = $datos['telefono'] ?? '';
            $_SESSION['bio']      = $datos['bio']      ?? '';
            $_SESSION['direccion_local'] = $datos['direccion_local'] ?? '';
            $_SESSION['latitud']  = $datos['latitud']  ?? null;
            $_SESSION['longitud'] = $datos['longitud'] ?? null;
            $_SESSION['id']       = $datos['id'];

            if ($datos['rol'] === 'tecnico') {
                header('Location: ' . BASE_URL . '/vista/tecnico/dashboard.php');
            } elseif ($datos['rol'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin.php?accion=dashboard');
            } else {
                header('Location: ' . BASE_URL . '/vista/bienvenida.php');
            }
            exit();
        } else {
            header('Location: ' . BASE_URL . '/vista/login.php?error=' . urlencode('Cuenta deshabilitada.'));
            exit();
        }
    }
}

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
}

<?php
/**
 * actualizarPerfil.php — Controlador de actualización de perfil
 * Procesa: nombre, teléfono, ciudad, bio, foto y cambio de contraseña
 */

session_start();

// Si no hay sesión, pa' fuera
if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../modelo/Usuario.php';

// ─── Helpers ───────────────────────────────────────────────
function redirigirError(string $msg): void {
    header('Location: ../vista/perfil.php?error=' . urlencode($msg));
    exit();
}

function redirigirOk(): void {
    header('Location: ../vista/perfil.php?ok=1');
    exit();
}

// ─── Solo acepta POST ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../vista/perfil.php');
    exit();
}

// ─── Recoger y limpiar campos ──────────────────────────────
$nombre   = trim($_POST['nombre_usuario'] ?? '');
$telefono = trim($_POST['telefono']       ?? '');
// Limpiar teléfono: mantener solo el '+' inicial y los números
$telefono = preg_replace('/[^\+0-9]/', '', $telefono);
if (!empty($telefono) && !str_starts_with($telefono, '+57')) {
    $telefono = '+57' . ltrim($telefono, '+');
}
$ciudad   = trim($_POST['ciudad']         ?? '');
$bio      = trim($_POST['bio']            ?? '');

$pass_actual  = $_POST['pass_actual']  ?? '';
$pass_nueva   = $_POST['pass_nueva']   ?? '';
$pass_confirm = $_POST['pass_confirm'] ?? '';

// ─── Validaciones básicas ──────────────────────────────────
if (empty($nombre)) {
    redirigirError('El nombre de usuario no puede estar vacío.');
}

if (strlen($nombre) > 50) {
    redirigirError('El nombre no puede superar los 50 caracteres.');
}

// ─── Manejo de foto ────────────────────────────────────────
$rutaFoto = $_SESSION['foto'] ?? ''; // conservar la anterior si no se sube nada

if (!empty($_FILES['foto']['name'])) {
    $file     = $_FILES['foto'];
    $maxSize  = 2 * 1024 * 1024; // 2MB
    $tiposOk  = ['image/jpeg', 'image/png', 'image/webp'];

    // Validar tipo MIME real (no solo extensión)
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeReal = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($file['error'] !== UPLOAD_ERR_OK) {
        redirigirError('Error al subir la imagen. Intenta de nuevo.');
    }

    if ($file['size'] > $maxSize) {
        redirigirError('La imagen supera el límite de 2MB.');
    }

    if (!in_array($mimeReal, $tiposOk)) {
        redirigirError('Solo se aceptan imágenes JPG, PNG o WEBP.');
    }

    // Crear carpeta de uploads si no existe
    $uploadDir = __DIR__ . '/../uploads/fotos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Nombre único para evitar colisiones
    $extension = match($mimeReal) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg',
    };

    $nombreArchivo = 'user_' . md5($_SESSION['usuario'] . time()) . '.' . $extension;
    $destino       = $uploadDir . $nombreArchivo;

    // Borrar foto anterior si existe
    if ($rutaFoto && file_exists(__DIR__ . '/../' . $rutaFoto)) {
        unlink(__DIR__ . '/../' . $rutaFoto);
    }

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        redirigirError('No se pudo guardar la imagen. Verifica los permisos.');
    }

    // Definir BASE_URL correctamente
    if (!defined('BASE_URL')) {
        if (getenv('RENDER') !== false) {
            define('BASE_URL', '');
        } else {
            $envBase = getenv('BASE_URL');
            if ($envBase !== false && $envBase !== '') {
                define('BASE_URL', $envBase);
            } else {
                $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
                $dir = str_replace('\\', '/', dirname(__DIR__));
                $baseUrl = str_ireplace($docRoot, '', $dir);
                define('BASE_URL', $baseUrl);
            }
        }
    }
    
    $rutaFoto = BASE_URL . '/uploads/fotos/' . $nombreArchivo;
}

// ─── Cambio de contraseña (opcional) ──────────────────────
$cambiarPass = false;

if (!empty($pass_nueva)) {
    if (empty($pass_actual)) {
        redirigirError('Debes ingresar tu contraseña actual para cambiarla.');
    }
    if ($pass_nueva !== $pass_confirm) {
        redirigirError('Las contraseñas nuevas no coinciden.');
    }
    if (strlen($pass_nueva) < 6) {
        redirigirError('La nueva contraseña debe tener al menos 6 caracteres.');
    }
    $cambiarPass = true;
}

$direccion_local = trim($_POST['direccion_local'] ?? '');
$latitud = !empty($_POST['latitud']) ? (float)$_POST['latitud'] : null;
$longitud = !empty($_POST['longitud']) ? (float)$_POST['longitud'] : null;

// ─── Actualizar en BD ─────────────────────────────────────
try {
    $modelo = new Usuario();

    if ($cambiarPass) {
        // Verificar contraseña actual antes de cambiar
        $resultado = $modelo->verificarCredenciales($_SESSION['usuario'], $pass_actual);
        if (!$resultado) {
            redirigirError('La contraseña actual es incorrecta.');
        }
        $modelo->actualizarPerfil(
            correo:    $_SESSION['usuario'],
            nombre:    $nombre,
            telefono:  $telefono,
            ciudad:    $ciudad,
            bio:       $bio,
            foto:      $rutaFoto,
            passNueva: $pass_nueva,
            direccion_local: $direccion_local,
            latitud:   $latitud,
            longitud:  $longitud
        );
    } else {
        $modelo->actualizarPerfil(
            correo:   $_SESSION['usuario'],
            nombre:   $nombre,
            telefono: $telefono,
            ciudad:   $ciudad,
            bio:      $bio,
            foto:     $rutaFoto,
            passNueva: '',
            direccion_local: $direccion_local,
            latitud:   $latitud,
            longitud:  $longitud
        );
    }

    // ─── Actualizar sesión ─────────────────────────────────
    $_SESSION['nombre']   = $nombre;
    $_SESSION['telefono'] = $telefono;
    $_SESSION['ciudad']   = $ciudad;
    $_SESSION['bio']      = $bio;
    $_SESSION['foto']     = $rutaFoto;
    $_SESSION['direccion_local'] = $direccion_local;
    $_SESSION['latitud']  = $latitud;
    $_SESSION['longitud'] = $longitud;

    redirigirOk();

} catch (Exception $e) {
    redirigirError('Error al guardar los cambios. Intenta de nuevo.');
}
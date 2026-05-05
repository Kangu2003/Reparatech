<?php
// admin.php - ENRUTADOR DEL PANEL DE ADMINISTRACIÓN
session_start();

// Validar acceso de administrador
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header('Location: index.php');
    exit();
}

require_once __DIR__ . '/controlador/ControladorAdmin.php';

$controlador = new ControladorAdmin();
$accion = $_GET['accion'] ?? 'dashboard';

switch ($accion) {
    case 'dashboard':
        $controlador->dashboard();
        break;
    
    // Usuarios y Técnicos
    case 'usuarios':
        $controlador->listarUsuarios();
        break;
    case 'tecnicos':
        $controlador->listarTecnicos();
        break;
    case 'cambiar_estado_usuario':
        $controlador->cambiarEstadoUsuario();
        break;
    case 'certificar_experto':
        $controlador->certificarExperto();
        break;
        
    // Categorías
    case 'categorias':
        $controlador->listarCategorias();
        break;
    case 'guardar_categoria':
        $controlador->guardarCategoria();
        break;
    case 'eliminar_categoria':
        $controlador->eliminarCategoria();
        break;
        
        
    // Retiros / Pagos
    case 'retiros':
        $controlador->listarRetiros();
        break;
    case 'cambiar_estado_retiro':
        $controlador->cambiarEstadoRetiro();
        break;

    // Disputas
    case 'disputas':
        $controlador->listarDisputas();
        break;
    case 'ver_disputa':
        $controlador->verDisputa();
        break;
    case 'cambiar_estado_disputa':
        $controlador->cambiarEstadoDisputa();
        break;

    default:
        $controlador->dashboard();
        break;
}
?>

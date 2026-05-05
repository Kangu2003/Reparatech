<?php
// controlador/ControladorAdmin.php
require_once __DIR__ . '/../modelo/Conexion.php';
require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Categoria.php';

class ControladorAdmin {

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar que sea admin
        if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
            header('Location: index.php');
            exit();
        }
    }

    public function index() {
        // Redirigir por defecto al dashboard
        $this->dashboard();
    }

    public function dashboard() {
        $db = (new Conexion())->getConexion();
        
        // Obtener estadísticas
        $res = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'usuario'");
        $totalUsuarios = $res->fetch_assoc()['total'];
        
        $res = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'tecnico'");
        $totalTecnicos = $res->fetch_assoc()['total'];
        
        $res = $db->query("SELECT COUNT(*) as total FROM reservas");
        $totalReservas = $res->fetch_assoc()['total'];
        
        $res = $db->query("SELECT COUNT(*) as total FROM categorias");
        $totalCategorias = $res->fetch_assoc()['total'];

        // 1. Reservas por Mes (del año actual)
        $queryMes = "SELECT MONTH(creado_en) as mes, COUNT(*) as total FROM reservas WHERE YEAR(creado_en) = YEAR(CURRENT_DATE) GROUP BY MONTH(creado_en) ORDER BY MONTH(creado_en)";
        $resMes = $db->query($queryMes);
        $mesesNombres = [1=>'Ene', 2=>'Feb', 3=>'Mar', 4=>'Abr', 5=>'May', 6=>'Jun', 7=>'Jul', 8=>'Ago', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dic'];
        $reservasPorMes = [];
        if ($resMes && $resMes->num_rows > 0) {
            while($row = $resMes->fetch_assoc()) {
                $reservasPorMes[] = ['mes' => $mesesNombres[$row['mes']], 'total' => (int)$row['total']];
            }
        }

        // 2. Reservas por Estado
        $coloresEstado = [
            'completada' => '#61D095',
            'pendiente'  => '#fbbf24',
            'aceptada'   => '#48BF84',
            'cancelada'  => '#f87171',
            'en_progreso'=> '#60a5fa'
        ];
        $queryEstado = "SELECT estado, COUNT(*) as total FROM reservas GROUP BY estado";
        $resEstado = $db->query($queryEstado);
        $reservasPorEstado = [];
        if ($resEstado && $resEstado->num_rows > 0) {
            while($row = $resEstado->fetch_assoc()) {
                $estado = strtolower($row['estado']);
                $reservasPorEstado[] = [
                    'estado' => ucfirst($estado),
                    'total' => (int)$row['total'],
                    'color' => $coloresEstado[$estado] ?? '#a78bfa'
                ];
            }
        }

        // 3. Top Técnicos
        $queryTop = "
            SELECT 
                u.nombre_usuario as nombre,
                (SELECT COUNT(*) FROM reservas r WHERE r.tecnico_id = u.id AND r.estado = 'completada') as servicios,
                COALESCE((SELECT AVG(calificacion) FROM resenas re WHERE re.tecnico_id = u.id), 5.0) as rating
            FROM usuarios u
            WHERE u.rol = 'tecnico'
            ORDER BY servicios DESC, rating DESC
            LIMIT 5
        ";
        $resTop = $db->query($queryTop);
        $topTecnicos = [];
        if ($resTop && $resTop->num_rows > 0) {
            while($row = $resTop->fetch_assoc()) {
                $topTecnicos[] = [
                    'nombre' => $row['nombre'],
                    'servicios' => (int)$row['servicios'],
                    'rating' => round((float)$row['rating'], 1)
                ];
            }
        }

        // 4. Reservas por Categoria
        $queryCat = "
            SELECT c.nombre, c.icono, COUNT(r.id) as total
            FROM categorias c
            JOIN servicios s ON c.id = s.categoria_id
            JOIN reservas r ON s.id = r.servicio_id
            GROUP BY c.id
            ORDER BY total DESC
            LIMIT 7
        ";
        $resCat = $db->query($queryCat);
        $reservasPorCategoria = [];
        if ($resCat && $resCat->num_rows > 0) {
            while($row = $resCat->fetch_assoc()) {
                $reservasPorCategoria[] = [
                    'nombre' => $row['nombre'],
                    'icono' => $row['icono'],
                    'total' => (int)$row['total']
                ];
            }
        }

        // 5. Actividad Reciente
        $actividadReciente = [];
        // Usuarios recientes
        $resU = $db->query("SELECT 'usuario' as tipo, nombre_usuario as nombre, rol, creado_en FROM usuarios ORDER BY creado_en DESC LIMIT 5");
        if ($resU) { while($r = $resU->fetch_assoc()) $actividadReciente[] = $r; }
        
        // Reservas recientes
        $resR = $db->query("SELECT 'reserva' as tipo, u.nombre_usuario as nombre, r.estado, c.nombre as categoria, r.creado_en FROM reservas r JOIN usuarios u ON r.usuario_id = u.id JOIN servicios s ON r.servicio_id = s.id JOIN categorias c ON s.categoria_id = c.id ORDER BY r.creado_en DESC LIMIT 5");
        if ($resR) { while($r = $resR->fetch_assoc()) $actividadReciente[] = $r; }
        
        // Ordenar por fecha desc
        usort($actividadReciente, function($a, $b) {
            return strtotime($b['creado_en']) - strtotime($a['creado_en']);
        });
        $actividadReciente = array_slice($actividadReciente, 0, 7);

        $actividadesFormateadas = [];
        foreach($actividadReciente as $act) {
            $tiempo = self::tiempoTranscurrido($act['creado_en']);
            if ($act['tipo'] === 'usuario') {
                $rol = $act['rol'] === 'tecnico' ? 'técnico' : 'usuario';
                $color = $act['rol'] === 'tecnico' ? '#a78bfa' : '#60a5fa';
                $texto = "Nuevo $rol registrado: <strong>" . htmlspecialchars($act['nombre']) . "</strong>";
            } else {
                $color = '#fbbf24';
                $estado = strtolower($act['estado']);
                if ($estado === 'completada') $color = '#61D095';
                if ($estado === 'cancelada') $color = '#f87171';
                if ($estado === 'pendiente') $color = '#fbbf24';
                if ($estado === 'aceptada') $color = '#48BF84';
                if ($estado === 'en_progreso') $color = '#60a5fa';
                $texto = "Reserva <strong>$estado</strong> de <strong>" . htmlspecialchars($act['nombre']) . "</strong> para " . htmlspecialchars($act['categoria']);
            }
            $actividadesFormateadas[] = [
                'color' => $color,
                'texto' => $texto,
                'tiempo' => $tiempo
            ];
        }

        // Fallback a datos de prueba visual si la BD está vacía
        if (empty($reservasPorMes)) $reservasPorMes = null;
        if (empty($reservasPorEstado)) $reservasPorEstado = null;
        if (empty($topTecnicos)) $topTecnicos = null;
        if (empty($reservasPorCategoria)) $reservasPorCategoria = null;
        if (empty($actividadesFormateadas)) $actividadesFormateadas = null;

        require_once __DIR__ . '/../vista/admin/dashboard.php';
    }

    private static function tiempoTranscurrido($fecha) {
        $timestamp = strtotime($fecha);
        $diferencia = time() - $timestamp;
        if ($diferencia < 60) return "hace unos segundos";
        if ($diferencia < 3600) return "hace " . floor($diferencia / 60) . " min";
        if ($diferencia < 86400) return "hace " . floor($diferencia / 3600) . " h";
        if ($diferencia < 2592000) return "hace " . floor($diferencia / 86400) . " días";
        return "hace " . floor($diferencia / 2592000) . " meses";
    }

    public function listarUsuarios() {
        $usuarioModel = new Usuario();
        $usuarios = $usuarioModel->obtenerTodos('usuario');
        require_once __DIR__ . '/../vista/admin/usuarios.php';
    }

    public function listarTecnicos() {
        $db = (new Conexion())->getConexion();
        $query = "
            SELECT u.id, u.nombre_usuario, u.correo_electronico, u.telefono, u.activo, u.es_premium, u.es_experto,
                   (SELECT COUNT(*) FROM reservas r WHERE r.tecnico_id = u.id) as total_servicios,
                   (SELECT COUNT(*) FROM reservas r WHERE r.tecnico_id = u.id AND r.estado = 'completada') as completados,
                   (SELECT AVG(calificacion) FROM resenas re WHERE re.tecnico_id = u.id) as calificacion,
                   (SELECT COUNT(*) FROM resenas re WHERE re.tecnico_id = u.id) as total_resenas
            FROM usuarios u
            WHERE u.rol = 'tecnico'
            ORDER BY u.creado_en DESC
        ";
        $res = $db->query($query);
        $tecnicos = [];
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $tecnicos[] = $row;
            }
        }
        require_once __DIR__ . '/../vista/admin/tecnicos.php';
    }

    public function certificarExperto() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $es_experto = $_POST['es_experto'] ?? 0;
            if ($id !== null) {
                $db = (new Conexion())->getConexion();
                $stmt = $db->prepare("UPDATE usuarios SET es_experto = ? WHERE id = ?");
                $stmt->bind_param("ii", $es_experto, $id);
                $stmt->execute();
            }
        }
        header('Location: admin.php?accion=tecnicos');
        exit();
    }

    public function cambiarEstadoUsuario() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $estado = $_POST['estado'] ?? null;
            $tipo = $_POST['tipo'] ?? 'usuarios'; // Para saber adonde redirigir
            
            if ($id !== null && $estado !== null) {
                $usuarioModel = new Usuario();
                $usuarioModel->cambiarEstadoActivo((int)$id, (int)$estado);
            }
            header('Location: admin.php?accion=' . $tipo);
            exit();
        }
        header('Location: admin.php?accion=dashboard');
        exit();
    }

    public function listarCategorias() {
        $categoriaModel = new Categoria();
        $categorias = $categoriaModel->obtenerTodas();
        require_once __DIR__ . '/../vista/admin/categorias.php';
    }

    public function guardarCategoria() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = $_POST['nombre'] ?? '';
            $icono = $_POST['icono'] ?? '🔧';
            $id = $_POST['id'] ?? null;

            if (!empty($nombre)) {
                $categoriaModel = new Categoria();
                if ($id) {
                    $categoriaModel->actualizar($id, $nombre, $icono);
                } else {
                    $categoriaModel->crear($nombre, $icono);
                }
            }
        }
        header('Location: admin.php?accion=categorias');
        exit();
    }

    public function eliminarCategoria() {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $categoriaModel = new Categoria();
            $categoriaModel->eliminar($id);
        }
        header('Location: admin.php?accion=categorias');
        exit();
    }

    public function listarRetiros() {
        require_once __DIR__ . '/../modelo/Retiro.php';
        $retiroModel = new Retiro();
        $retiros = $retiroModel->obtenerTodosLosRetiros();
        require_once __DIR__ . '/../vista/admin/retiros.php';
    }

    public function cambiarEstadoRetiro() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $estado = $_POST['estado'] ?? null;
            
            if ($id !== null && $estado !== null) {
                require_once __DIR__ . '/../modelo/Retiro.php';
                $retiroModel = new Retiro();
                $retiroModel->cambiarEstado((int)$id, $estado);
            }
        }
        header('Location: admin.php?accion=retiros');
        exit();
    }

    public function listarDisputas() {
        require_once __DIR__ . '/../modelo/Disputa.php';
        $disputaModel = new Disputa();
        $disputas = $disputaModel->obtenerTodasLasDisputas();
        require_once __DIR__ . '/../vista/admin/disputas.php';
    }

    public function verDisputa() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            require_once __DIR__ . '/../modelo/Disputa.php';
            $disputaModel = new Disputa();
            $disputa = $disputaModel->obtenerDisputaPorId($id);
            if ($disputa) {
                require_once __DIR__ . '/../vista/admin/detalle-disputa.php';
                return;
            }
        }
        header('Location: admin.php?accion=disputas');
    }

    public function cambiarEstadoDisputa() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $estado = $_POST['estado'] ?? null;
            $respuesta = trim($_POST['admin_respuesta'] ?? '');
            
            if ($id !== null && $estado !== null) {
                require_once __DIR__ . '/../modelo/Disputa.php';
                require_once __DIR__ . '/../utils/CorreoHelper.php';
                $disputaModel = new Disputa();
                $disputaActual = $disputaModel->obtenerDisputaPorId((int)$id);

                if ($disputaModel->actualizarDisputaAdmin((int)$id, $estado, $respuesta)) {
                    // Si cambió el estado o hay respuesta, enviar correo
                    if ($disputaActual && $disputaActual['cliente_correo']) {
                        CorreoHelper::enviarCorreoDisputaActualizada($disputaActual['cliente_correo'], $disputaActual['cliente'], $id, ucfirst(str_replace('_', ' ', $estado)));
                    }
                }
            }
        }
        header('Location: admin.php?accion=disputas');
        exit();
    }
}
?>

<?php
// index.php - RAÍZ DEL PROYECTO
session_start();

// ✅ BASE_URL centralizado
define('BASE_URL', '/inicio_sesion_mvc');

// ✅ Nuevo controlador con soporte de roles
require_once __DIR__ . '/controlador/ControladorUsuario.php';

$accion = $_GET['accion'] ?? null;

// ✅ Login
if ($accion === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $controlador = new ControladorUsuario();
    $controlador->iniciarSesion($_POST['correo_electronico'], $_POST['contrasena']);
    exit();
}

// ✅ Registro — ahora recibe el rol elegido en el formulario
if ($accion === 'registro' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $controlador = new ControladorUsuario();
    $controlador->registrar(
        $_POST['nombre_usuario'],
        $_POST['correo_electronico'],
        $_POST['contrasena'],
        $_POST['rol'] ?? 'usuario',   // ✅ campo rol del nuevo registro.php
        $_POST['telefono'] ?? ''
    );
    exit();
}

// ✅ Logout completo
if ($accion === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

// Datos de sesion para la vista
$sesionActiva  = isset($_SESSION['usuario']);
$nombreUsuario = $sesionActiva ? htmlspecialchars($_SESSION['nombre']   ?? 'Usuario') : '';
$correoUsuario = $sesionActiva ? htmlspecialchars($_SESSION['usuario'])               : '';
$rolUsuario    = $sesionActiva ? ($_SESSION['rol'] ?? 'usuario')                      : '';
$fotoUsuario   = $sesionActiva ? ($_SESSION['foto'] ?? '')                            : '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ReparaTech — Servicios del Hogar en Santa Marta</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

  <style>
    :root {
      --pink: #E0BAD7;
      --green-light: #61D095;
      --green-mid: #48BF84;
      --green-sea: #439775;
      --green-dark: #2A4747;
      --white: #FAFAF8;
      --off-white: #F2F0EC;
      --text: #1a2a2a;
      --text-muted: #4a6a6a
    }

    *,
    *::before,
    *::after {
      margin: 0;
      padding: 0;
      box-sizing: border-box
    }

    html {
      scroll-behavior: smooth
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--white);
      color: var(--text);
      overflow-x: hidden
    }

    .page {
      display: none
    }

    .page.active {
      display: block
    }


    /* SESSION NAV STYLES */
    .nav-user-badge {
      display: flex;
      align-items: center;
      gap: .5rem;
      font-size: .85rem;
      font-weight: 500;
      color: var(--green-dark);
    }
    .nav-avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: var(--green-light);
  color: var(--green-dark);
  font-family: 'Syne', sans-serif;
  font-weight: 800;
  font-size: .8rem;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
}
.nav-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
    .mobile-user-info {
      font-size: .85rem;
      color: var(--text-muted);
      padding: .3rem 0;
    }
    /* Registro exito banner */
    .registro-banner {
      position: fixed;
      top: 80px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--green-light);
      color: var(--green-dark);
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      padding: .75rem 2rem;
      border-radius: 100px;
      z-index: 200;
      box-shadow: 0 8px 24px rgba(97,208,149,.4);
      animation: fadeUp .5s ease both;
    }

    /* NAV */
    nav {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.2rem 5%;
      transition: all .3s
    }

    nav.scrolled {
      background: rgba(250, 250, 248, .94);
      backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(72, 191, 132, .15);
      padding: .85rem 5%;
      box-shadow: 0 4px 30px rgba(42, 71, 71, .07)
    }

    .logo {
      font-family: 'Syne', sans-serif;
      font-size: 1.45rem;
      font-weight: 800;
      color: var(--green-dark);
      letter-spacing: -.5px;
      text-decoration: none;
      cursor: pointer
    }

    .logo span {
      color: var(--green-light)
    }

    .nav-links {
      display: flex;
      gap: 1.8rem;
      list-style: none;
      align-items: center
    }

    .nav-links a {
      font-size: .85rem;
      font-weight: 500;
      color: var(--green-dark);
      text-decoration: none;
      transition: color .2s;
      white-space: nowrap;
      cursor: pointer
    }

    .nav-links a:hover {
      color: var(--green-mid)
    }

    .nav-cta {
      background: var(--green-dark) !important;
      color: var(--white) !important;
      padding: .55rem 1.2rem;
      border-radius: 100px;
      font-size: .8rem !important
    }

    .nav-cta:hover {
      background: var(--green-sea) !important
    }

    .hamburger {
      display: none;
      flex-direction: column;
      gap: 5px;
      background: none;
      border: none;
      cursor: pointer;
      padding: 4px
    }

    .hamburger span {
      display: block;
      width: 24px;
      height: 2px;
      background: var(--green-dark);
      border-radius: 2px;
      transition: all .25s
    }

    .hamburger.active span:nth-child(1) {
      transform: translateY(7px) rotate(45deg)
    }

    .hamburger.active span:nth-child(2) {
      opacity: 0
    }

    .hamburger.active span:nth-child(3) {
      transform: translateY(-7px) rotate(-45deg)
    }

    .mobile-menu {
      position: fixed;
      top: 60px;
      left: 0;
      right: 0;
      background: var(--white);
      border-bottom: 1px solid rgba(72, 191, 132, .15);
      padding: 1.5rem 6%;
      display: flex;
      flex-direction: column;
      gap: 1.2rem;
      z-index: 99;
      transform: translateY(-110%);
      transition: transform .3s;
      box-shadow: 0 10px 30px rgba(0, 0, 0, .08)
    }

    .mobile-menu.open {
      transform: translateY(0)
    }

    .mobile-menu a {
      font-family: 'Syne', sans-serif;
      font-weight: 600;
      font-size: 1rem;
      color: var(--green-dark);
      text-decoration: none;
      cursor: pointer
    }

    .mobile-cta-btn {
      background: var(--green-light);
      color: var(--green-dark) !important;
      padding: .7rem 1.5rem;
      border-radius: 100px;
      text-align: center;
      width: fit-content
    }

    /* SHARED */
    .section-label {
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--green-mid);
      margin-bottom: .8rem;
      display: block
    }

    .section-title {
      font-family: 'Syne', sans-serif;
      font-size: clamp(1.9rem, 3.5vw, 2.8rem);
      font-weight: 800;
      letter-spacing: -1.5px;
      color: var(--green-dark);
      line-height: 1.08;
      margin-bottom: 1rem
    }

    .section-desc {
      font-size: 1rem;
      color: var(--text-muted);
      line-height: 1.75;
      font-weight: 300;
      margin-bottom: 3.5rem
    }

    .btn-primary {
      background: var(--green-light);
      color: var(--green-dark);
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .95rem;
      padding: .9rem 2rem;
      border-radius: 100px;
      border: none;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      transition: transform .2s, box-shadow .2s;
      box-shadow: 0 4px 20px rgba(97, 208, 149, .4)
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 30px rgba(97, 208, 149, .55)
    }

    .btn-secondary {
      background: transparent;
      color: var(--green-dark);
      font-family: 'Syne', sans-serif;
      font-weight: 600;
      font-size: .95rem;
      padding: .88rem 2rem;
      border-radius: 100px;
      border: 2px solid var(--green-dark);
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      transition: all .22s
    }

    .btn-secondary:hover {
      background: var(--green-dark);
      color: var(--white)
    }

    .reveal {
      opacity: 0;
      transform: translateY(32px);
      transition: opacity .7s ease, transform .7s ease
    }

    .reveal.visible {
      opacity: 1;
      transform: translateY(0)
    }

    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(30px)
      }

      to {
        opacity: 1;
        transform: translateY(0)
      }
    }

    @keyframes fadeRight {
      from {
        opacity: 0;
        transform: translateX(40px)
      }

      to {
        opacity: 1;
        transform: translateX(0)
      }
    }

    @keyframes pulse {

      0%,
      100% {
        opacity: 1;
        transform: scale(1)
      }

      50% {
        opacity: .5;
        transform: scale(1.5)
      }
    }

    @keyframes float {

      0%,
      100% {
        transform: translateY(0)
      }

      50% {
        transform: translateY(-12px)
      }
    }

    @keyframes slideUp {
      from {
        transform: translateY(30px);
        opacity: 0
      }

      to {
        transform: translateY(0);
        opacity: 1
      }
    }

    /* HERO */
    .hero {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1fr 1fr;
      align-items: center;
      padding: 9rem 6% 5rem;
      position: relative;
      overflow: hidden;
      background: var(--off-white)
    }

    #hero-map {
      position: absolute;
      inset: 0;
      z-index: 0;
      opacity: .35
    }

    .map-fade {
      position: absolute;
      inset: 0;
      z-index: 1;
      background: linear-gradient(to right, var(--off-white) 0%, var(--off-white) 45%, rgba(242, 240, 236, .85) 60%, rgba(242, 240, 236, .4) 75%, transparent 100%), linear-gradient(to bottom, var(--off-white) 0%, transparent 15%, transparent 80%, var(--off-white) 100%)
    }

    .hero-bg-orb {
      position: absolute;
      border-radius: 50%;
      pointer-events: none;
      z-index: 2
    }

    .orb1 {
      width: 500px;
      height: 500px;
      top: -150px;
      right: -100px;
      background: radial-gradient(circle, rgba(97, 208, 149, .15) 0%, transparent 65%)
    }

    .orb2 {
      width: 400px;
      height: 400px;
      bottom: -100px;
      left: 10%;
      background: radial-gradient(circle, rgba(224, 186, 215, .18) 0%, transparent 65%)
    }

    .hero-content {
      position: relative;
      z-index: 3
    }

    .hero-tag {
      display: inline-flex;
      align-items: center;
      gap: .6rem;
      background: rgba(97, 208, 149, .12);
      border: 1px solid rgba(97, 208, 149, .35);
      color: var(--green-sea);
      font-size: .74rem;
      font-weight: 600;
      letter-spacing: 1.2px;
      text-transform: uppercase;
      padding: .4rem 1rem;
      border-radius: 100px;
      margin-bottom: 1.6rem;
      animation: fadeUp .8s ease both
    }

    .hero-tag .dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--green-light);
      animation: pulse 2s infinite
    }

    .hero h1 {
      font-family: 'Syne', sans-serif;
      font-size: clamp(2.6rem, 4.5vw, 4rem);
      font-weight: 800;
      line-height: 1.04;
      letter-spacing: -2px;
      color: var(--green-dark);
      margin-bottom: 1.2rem;
      animation: fadeUp .8s .1s ease both
    }

    .hero h1 em {
      font-style: normal;
      color: var(--green-mid);
      position: relative
    }

    .hero h1 em::after {
      content: '';
      position: absolute;
      bottom: 3px;
      left: -3px;
      right: -3px;
      height: 8px;
      background: var(--pink);
      opacity: .5;
      z-index: -1;
      border-radius: 3px
    }

    .hero p {
      font-size: 1rem;
      line-height: 1.75;
      color: #3a5252;
      max-width: 460px;
      margin-bottom: 1.8rem;
      font-weight: 300;
      animation: fadeUp .8s .2s ease both
    }

    .hero-btns {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      margin-bottom: 2rem;
      animation: fadeUp .8s .4s ease both
    }

    .hero-trust {
      display: flex;
      align-items: center;
      gap: .9rem;
      animation: fadeUp .8s .5s ease both
    }

    .hero-trust span {
      font-size: .82rem;
      color: var(--text-muted)
    }

    .trust-avatars {
      display: flex
    }

    .trust-avatars .av {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      border: 2px solid var(--white);
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .75rem;
      color: var(--green-dark);
      margin-right: -8px
    }

    /* SEARCH BAR */
    .search-wrapper {
      position: relative;
      margin-bottom: 2rem;
      animation: fadeUp .8s .35s ease both;
      z-index: 10
    }

    .search-box {
      display: flex;
      align-items: center;
      background: var(--white);
      border-radius: 16px;
      border: 2px solid rgba(72, 191, 132, .2);
      box-shadow: 0 8px 30px rgba(42, 71, 71, .1);
      overflow: hidden;
      transition: border-color .2s
    }

    .search-box:focus-within {
      border-color: var(--green-light);
      box-shadow: 0 12px 40px rgba(97, 208, 149, .2)
    }

    .search-icon {
      padding: 0 .8rem 0 1.2rem;
      font-size: 1.1rem;
      flex-shrink: 0
    }

    .search-box input {
      flex: 1;
      border: none;
      outline: none;
      font-family: 'DM Sans', sans-serif;
      font-size: .9rem;
      color: var(--text);
      padding: .95rem .5rem;
      background: transparent;
      min-width: 0
    }

    .search-box input::placeholder {
      color: #9ab8b8;
      font-weight: 300
    }

    .search-cat {
      border-left: 1px solid rgba(72, 191, 132, .2)
    }

    .search-cat select {
      border: none;
      outline: none;
      font-family: 'DM Sans', sans-serif;
      font-size: .82rem;
      color: var(--text-muted);
      padding: .95rem 1rem;
      background: transparent;
      cursor: pointer
    }

    .search-btn-el {
      background: var(--green-light);
      color: var(--green-dark);
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .88rem;
      padding: .95rem 1.5rem;
      border: none;
      cursor: pointer;
      transition: background .2s
    }

    .search-btn-el:hover {
      background: var(--green-mid)
    }

    .search-dropdown {
      position: absolute;
      top: calc(100% + 8px);
      left: 0;
      right: 0;
      background: var(--white);
      border-radius: 16px;
      border: 1px solid rgba(72, 191, 132, .2);
      box-shadow: 0 20px 60px rgba(42, 71, 71, .14);
      overflow: hidden;
      z-index: 50;
      display: none
    }

    .search-dropdown.show {
      display: block;
      animation: fadeUp .2s ease both
    }

    .dropdown-section {
      padding: 1rem
    }

    .dropdown-label {
      font-size: .7rem;
      font-weight: 700;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--green-sea);
      margin-bottom: .8rem
    }

    .popular-tags {
      display: flex;
      flex-wrap: wrap;
      gap: .5rem
    }

    .pop-tag {
      background: rgba(97, 208, 149, .1);
      color: var(--green-dark);
      border: 1px solid rgba(97, 208, 149, .25);
      font-family: 'DM Sans', sans-serif;
      font-size: .82rem;
      font-weight: 500;
      padding: .35rem .9rem;
      border-radius: 100px;
      cursor: pointer;
      transition: all .2s
    }

    .pop-tag:hover {
      background: rgba(97, 208, 149, .2);
      border-color: var(--green-light)
    }

    /* HERO VISUAL */
    .hero-visual {
      position: relative;
      z-index: 3;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 460px;
      animation: fadeRight 1s .2s ease both
    }

    .floating-card {
      background: var(--white);
      border-radius: 22px;
      box-shadow: 0 20px 60px rgba(42, 71, 71, .13), 0 2px 8px rgba(0, 0, 0, .04);
      position: absolute
    }

    .card-main {
      width: 290px;
      padding: 1.5rem;
      animation: float 5s ease-in-out infinite;
      z-index: 3
    }

    .card-header {
      display: flex;
      align-items: center;
      gap: .8rem;
      margin-bottom: .9rem
    }

    .card-icon {
      width: 44px;
      height: 44px;
      background: rgba(97, 208, 149, .15);
      border-radius: 13px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      flex-shrink: 0
    }

    .card-name {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .9rem;
      color: var(--green-dark)
    }

    .card-role {
      font-size: .73rem;
      color: #7a9a9a;
      font-weight: 300
    }

    .card-badge {
      margin-left: auto;
      font-size: .65rem;
      font-weight: 700;
      color: var(--green-sea);
      background: rgba(67, 151, 117, .1);
      border: 1px solid rgba(67, 151, 117, .25);
      padding: .2rem .55rem;
      border-radius: 100px;
      white-space: nowrap
    }

    .card-stars {
      color: var(--green-light);
      font-size: .88rem;
      margin-bottom: .8rem
    }

    .card-stars span {
      font-size: .73rem;
      color: #7a9a9a;
      font-weight: 300;
      margin-left: 4px
    }

    .card-chips {
      display: flex;
      gap: .4rem;
      flex-wrap: wrap;
      margin-bottom: 1.1rem
    }

    .chip {
      background: rgba(97, 208, 149, .12);
      color: var(--green-sea);
      font-size: .68rem;
      font-weight: 600;
      padding: .2rem .6rem;
      border-radius: 100px
    }

    .card-footer-el {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 1px solid rgba(72, 191, 132, .15);
      padding-top: .9rem
    }

    .card-price-el {
      font-family: 'Syne', sans-serif;
      font-size: 1.2rem;
      font-weight: 800;
      color: var(--green-mid)
    }

    .card-price-el span {
      font-size: .75rem;
      font-weight: 400;
      color: #7a9a9a
    }

    .card-btn-el {
      background: var(--green-light);
      color: var(--green-dark);
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .78rem;
      padding: .48rem 1rem;
      border-radius: 100px;
      border: none;
      cursor: pointer;
      transition: all .2s
    }

    .card-btn-el:hover {
      background: var(--green-mid)
    }

    .card-pill {
      display: flex;
      align-items: center;
      gap: .5rem;
      padding: .55rem 1rem;
      font-size: .8rem;
      font-weight: 600;
      font-family: 'Syne', sans-serif;
      color: var(--green-dark);
      white-space: nowrap
    }

    .pill1 {
      top: 14%;
      left: -8%;
      animation: float 4.5s .5s ease-in-out infinite
    }

    .pill2 {
      bottom: 24%;
      right: -10%;
      animation: float 5.5s 1s ease-in-out infinite
    }

    .pill3 {
      bottom: 9%;
      left: -2%;
      animation: float 4s 1.5s ease-in-out infinite
    }

    /* STATS */
    .stats-strip {
      background: var(--green-dark);
      padding: 2.2rem 6%;
      display: flex;
      justify-content: space-around;
      align-items: center;
      gap: 2rem;
      flex-wrap: wrap;
      position: relative
    }

    .stats-strip::before {
      content: '';
      position: absolute;
      inset: 0;
      background: repeating-linear-gradient(90deg, rgba(97, 208, 149, .03) 0px, rgba(97, 208, 149, .03) 1px, transparent 1px, transparent 80px);
      pointer-events: none
    }

    .stat-item {
      text-align: center;
      position: relative;
      z-index: 1
    }

    .stat-num {
      font-family: 'Syne', sans-serif;
      font-size: 2.2rem;
      font-weight: 800;
      color: var(--green-light);
      line-height: 1
    }

    .stat-label {
      font-size: .8rem;
      color: rgba(255, 255, 255, .55);
      margin-top: .3rem;
      font-weight: 300;
      letter-spacing: .3px
    }

    /* HOW IT WORKS */
    .how-section {
      padding: 6rem 6%;
      background: var(--white)
    }

    .steps-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.8rem
    }

    .step-card {
      background: var(--off-white);
      border-radius: 26px;
      padding: 2.2rem 2rem;
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(72, 191, 132, .1);
      transition: transform .3s, box-shadow .3s
    }

    .step-card:hover {
      transform: translateY(-7px);
      box-shadow: 0 24px 60px rgba(42, 71, 71, .1)
    }

    .step-num {
      font-family: 'Syne', sans-serif;
      font-size: 4.5rem;
      font-weight: 800;
      color: rgba(97, 208, 149, .18);
      line-height: 1;
      margin-bottom: .5rem
    }

    .step-icon {
      font-size: 2.2rem;
      margin-bottom: 1rem;
      display: block
    }

    .step-card h3 {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 1.12rem;
      color: var(--green-dark);
      margin-bottom: .7rem
    }

    .step-card p {
      font-size: .9rem;
      color: #5a7a7a;
      line-height: 1.68;
      font-weight: 300
    }

    .step-glow {
      position: absolute;
      bottom: -30px;
      right: -30px;
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(97, 208, 149, .14), transparent)
    }

    /* SERVICES */
    .services-section {
      padding: 6rem 6%;
      background: var(--green-dark);
      position: relative;
      overflow: hidden
    }

    .bg-pattern {
      position: absolute;
      inset: 0;
      background-image: radial-gradient(rgba(97, 208, 149, .06) 1px, transparent 1px);
      background-size: 40px 40px;
      pointer-events: none
    }

    .services-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.4rem;
      position: relative;
      z-index: 1
    }
    /* REVIEWS */
    .reviews-section {
      padding: 6rem 6%;
      background: var(--off-white)
    }

    .reviews-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.5rem
    }

    .review-card {
      background: var(--white);
      border-radius: 22px;
      padding: 2rem;
      border: 1px solid rgba(72, 191, 132, .1);
      transition: transform .3s, box-shadow .3s
    }

    .review-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 50px rgba(42, 71, 71, .09)
    }

    .review-stars {
      color: var(--green-light);
      font-size: 1rem;
      letter-spacing: 2px;
      margin-bottom: 1.1rem
    }

    .review-text {
      font-size: .92rem;
      color: #3a5252;
      line-height: 1.75;
      font-weight: 300;
      font-style: italic;
      margin-bottom: 1.5rem
    }

    .review-author {
      display: flex;
      align-items: center;
      gap: .85rem
    }

    .review-avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .88rem;
      flex-shrink: 0
    }

    .author-name {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .88rem;
      color: var(--green-dark)
    }

    .author-role {
      font-size: .76rem;
      color: #7a9a9a;
      font-weight: 300;
      margin-top: 1px
    }

    /* CTA */
    .cta-wrapper {
      padding: 0 6% 6rem
    }

    .cta-section {
      background: linear-gradient(135deg, var(--green-mid) 0%, var(--green-sea) 45%, var(--green-dark) 100%);
      border-radius: 32px;
      padding: 4.5rem;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 4rem;
      align-items: center;
      position: relative;
      overflow: hidden
    }

    .cta-orb {
      position: absolute;
      top: -120px;
      left: 50%;
      width: 500px;
      height: 500px;
      background: radial-gradient(circle, rgba(224, 186, 215, .18), transparent);
      transform: translateX(-50%);
      pointer-events: none
    }

    .cta-content {
      position: relative;
      z-index: 1
    }

    .cta-section h2 {
      font-family: 'Syne', sans-serif;
      font-size: clamp(1.9rem, 3vw, 2.6rem);
      font-weight: 800;
      color: var(--white);
      letter-spacing: -1.5px;
      margin-bottom: 1rem;
      line-height: 1.1
    }

    .cta-section p {
      font-size: .98rem;
      color: rgba(255, 255, 255, .72);
      margin-bottom: 2.2rem;
      font-weight: 300;
      line-height: 1.7
    }

    .cta-btns {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap
    }

    .btn-white {
      background: var(--white);
      color: var(--green-dark);
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .9rem;
      padding: .85rem 1.8rem;
      border-radius: 100px;
      border: none;
      cursor: pointer;
      text-decoration: none;
      transition: all .22s;
      box-shadow: 0 4px 20px rgba(0, 0, 0, .18)
    }

    .btn-white:hover {
      transform: translateY(-2px)
    }

    .btn-outline-white {
      background: transparent;
      color: var(--white);
      font-family: 'Syne', sans-serif;
      font-weight: 600;
      font-size: .9rem;
      padding: .85rem 1.8rem;
      border-radius: 100px;
      border: 2px solid rgba(255, 255, 255, .45);
      text-decoration: none;
      transition: all .22s
    }

    .btn-outline-white:hover {
      border-color: var(--white);
      background: rgba(255, 255, 255, .1)
    }

    .cta-perks {
      display: flex;
      flex-direction: column;
      gap: 1.6rem;
      position: relative;
      z-index: 1
    }

    .perk {
      display: flex;
      gap: 1rem;
      align-items: flex-start
    }

    .perk-icon {
      font-size: 1.6rem;
      flex-shrink: 0;
      width: 48px;
      height: 48px;
      background: rgba(255, 255, 255, .1);
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center
    }

    .perk-title {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .92rem;
      color: var(--white);
      margin-bottom: .25rem
    }

    .perk-desc {
      font-size: .82rem;
      color: rgba(255, 255, 255, .6);
      font-weight: 300
    }

    /* FOOTER */
    footer {
      background: var(--green-dark);
      padding: 4.5rem 6% 2rem;
      color: rgba(255, 255, 255, .55)
    }

    .footer-grid {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1fr;
      gap: 3rem;
      margin-bottom: 3rem;
      padding-bottom: 3rem;
      border-bottom: 1px solid rgba(255, 255, 255, .07)
    }

    .footer-logo {
      font-family: 'Syne', sans-serif;
      font-size: 1.4rem;
      font-weight: 800;
      color: var(--white);
      letter-spacing: -.5px;
      margin-bottom: 1rem
    }

    .footer-logo span {
      color: var(--green-light)
    }

    .footer-brand-p {
      font-size: .85rem;
      line-height: 1.72;
      font-weight: 300;
      max-width: 280px;
      margin-bottom: 1.2rem
    }

    .social-links {
      display: flex;
      gap: .7rem
    }

    .social-links a {
      width: 36px;
      height: 36px;
      background: rgba(255, 255, 255, .07);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      text-decoration: none;
      transition: background .2s
    }

    .social-links a:hover {
      background: rgba(97, 208, 149, .2)
    }

    .footer-col h4 {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .88rem;
      color: var(--white);
      margin-bottom: 1.2rem
    }

    .footer-col ul {
      list-style: none
    }

    .footer-col ul li {
      margin-bottom: .6rem
    }

    .footer-col ul a {
      font-size: .84rem;
      color: rgba(255, 255, 255, .45);
      text-decoration: none;
      transition: color .2s;
      font-weight: 300
    }

    .footer-col ul a:hover {
      color: var(--green-light)
    }

    .footer-bottom {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: .79rem;
      flex-wrap: wrap;
      gap: 1rem
    }

    .heart {
      color: var(--green-light)
    }

    /* MODAL */
    label {
      display: block;
      font-size: .8rem;
      font-weight: 600;
      color: var(--green-dark);
      margin-bottom: .4rem;
      letter-spacing: .3px
    }

    input,
    select,
    textarea {
      width: 100%;
      border: 1.5px solid rgba(72, 191, 132, .2);
      border-radius: 12px;
      padding: .75rem 1rem;
      font-family: 'DM Sans', sans-serif;
      font-size: .88rem;
      color: var(--text);
      background: var(--off-white);
      outline: none;
      transition: border-color .2s
    }

    input:focus,
    select:focus,
    textarea:focus {
      border-color: var(--green-light);
      background: var(--white)
    }

    textarea {
      resize: none
    }
    /* FILTERS */
    .filter-chip:hover,
    .filter-chip.active {
      background: var(--green-light);
      border-color: var(--green-light);
      color: var(--green-dark);
      font-weight: 700
    }

    .filter-sort select {
      border: 1px solid rgba(72, 191, 132, .25);
      border-radius: 100px;
      padding: .42rem 1rem;
      font-family: 'DM Sans', sans-serif;
      font-size: .82rem;
      color: var(--text-muted);
      background: transparent;
      outline: none;
      cursor: pointer
    }

    .chat-message.bot .msg-bubble,
    .chat-message.human .msg-bubble {
      background: var(--white);
      border-radius: 18px 18px 18px 4px;
      padding: .7rem 1rem;
      box-shadow: 0 2px 8px rgba(42, 71, 71, .08)
    }

    .chat-message.bot .msg-text,
    .chat-message.human .msg-text {
      color: var(--text)
    }

    .chat-message.bot .msg-time,
    .chat-message.human .msg-time {
      color: #aac4c4
    }

    @keyframes bounce {

      0%,
      60%,
      100% {
        transform: translateY(0)
      }

      30% {
        transform: translateY(-6px)
      }
    }

    /* TABS */
    .tab.active {
      background: var(--green-dark);
      color: var(--white)
    }

    .star-selector span.active {
      color: var(--green-light)
    }

    .fstep.active {
      color: var(--green-dark)
    }

    .fstep.active span {
      background: var(--green-light);
      color: var(--green-dark)
    }
    /* RESPONSIVE */
    @media(max-width:900px) {
      .hero {
        grid-template-columns: 1fr;
        text-align: center;
        padding-top: 7rem
      }

      .hero p {
        margin: 0 auto 1.8rem
      }

      .hero-btns,
      .hero-trust {
        justify-content: center
      }

      .hero-visual {
        display: none
      }

      .steps-grid,
      .reviews-grid,

      .services-grid {
        grid-template-columns: repeat(2, 1fr)
      }

      .cta-section {
        grid-template-columns: 1fr;
        padding: 3rem 2rem;
        gap: 2.5rem
      }



      .footer-grid {
        grid-template-columns: 1fr 1fr
      }

    }

    @media(max-width:600px) {
      .services-grid {
        grid-template-columns: 1fr
      }

      .footer-grid {
        grid-template-columns: 1fr
      }

      .nav-links {
        display: none
      }

      .hamburger {
        display: flex
      }

      .search-cat {
        display: none
      }


    }
    /* SERVICES GRID */
    .services-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.4rem;
      position: relative;
      z-index: 1
    }

    .service-card {
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(97,208,149,.12);
      border-radius: 22px;
      padding: 1.8rem 1.5rem;
      transition: all .3s;
      position: relative;
      cursor: pointer;
      display: block;
      text-decoration: none
    }

    .service-card:hover {
      background: rgba(97,208,149,.09);
      border-color: rgba(97,208,149,.3);
      transform: translateY(-5px)
    }

    .service-card:hover .card-arrow {
      opacity: 1;
      transform: translateX(0)
    }

    .card-arrow {
      position: absolute;
      top: 1.5rem;
      right: 1.5rem;
      font-size: 1rem;
      color: var(--green-light);
      opacity: 0;
      transform: translateX(-6px);
      transition: all .3s
    }

    .svc-icon {
      width: 52px;
      height: 52px;
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 1.2rem
    }

    .service-card h3 {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 1rem;
      color: var(--white);
      margin-bottom: .5rem
    }

    .service-card p {
      font-size: .84rem;
      color: rgba(255,255,255,.5);
      line-height: 1.62;
      font-weight: 300;
      margin-bottom: 1rem
    }

    .svc-tags {
      display: flex;
      gap: .35rem;
      flex-wrap: wrap
    }

    .svc-tag {
      font-size: .66rem;
      font-weight: 600;
      color: rgba(255,255,255,.4);
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.08);
      padding: .18rem .6rem;
      border-radius: 100px
    }

    @media(max-width:900px) {
      .services-grid { grid-template-columns: repeat(2,1fr) }
    }
    @media(max-width:600px) {
      .services-grid { grid-template-columns: 1fr }
    }
  </style>
</head>

<body>

  <!-- NAV -->
  <nav id="navbar">
    <div class="logo" onclick="goTo('home')">Repara<span>Tech</span></div>
    <ul class="nav-links">
      <li><a href="#como-funciona" onclick="document.getElementById('como-funciona').scrollIntoView({behavior:'smooth'});return false;">Cómo funciona</a></li>
      <li><a href="vista/buscar.php">Servicios</a></li>
      <li><a href="#opiniones" onclick="document.getElementById('opiniones').scrollIntoView({behavior:'smooth'});return false;">Opiniones</a></li>
      <li><a href="vista/mis-reservas.php">Mis reservas</a></li>
      <li><a href="vista/registro.php?rol=tecnico">¿Trabajar con nosotros?</a></li>
      <?php if ($sesionActiva): ?>
        <li>
          <a href="vista/perfil.php" class="nav-user-badge" style="text-decoration:none">
            <div class="nav-avatar">
              <?php if (!empty($fotoUsuario)): ?>
                <img src="<?php echo htmlspecialchars($fotoUsuario); ?>" alt="foto">
              <?php else: ?>
                <?php echo strtoupper(substr($nombreUsuario, 0, 1)); ?>
              <?php endif; ?>
            </div>
            <span><?php echo $nombreUsuario; ?></span>
          </a>
        </li>
        <!-- ✅ Link al panel según rol -->
        <?php if ($rolUsuario === 'tecnico'): ?>
          <li><a href="vista/tecnico/dashboard.php" style="font-weight:600">🛠️ Mi panel</a></li>
        <?php else: ?>
          <li><a href="vista/bienvenida.php" style="font-weight:600">👤 Mi panel</a></li>
        <?php endif; ?>
        <li><a class="nav-cta" href="index.php?accion=logout">Cerrar sesión</a></li>
      <?php else: ?>
        <li><a href="vista/login.php" style="font-weight:600">Iniciar sesión</a></li>
        <li><a class="nav-cta" href="vista/registro.php">Registrarse</a></li>
      <?php endif; ?>
    </ul>
    <button class="hamburger" id="hamburger" onclick="toggleMenu()">
      <span></span><span></span><span></span>
    </button>
  </nav>
  <div class="mobile-menu" id="mobile-menu">
    <a href="#como-funciona" onclick="document.getElementById('como-funciona').scrollIntoView({behavior:'smooth'});toggleMenu();return false;">Cómo funciona</a>
    <a href="vista/buscar.php">Servicios</a>
    <a href="#opiniones" onclick="document.getElementById('opiniones').scrollIntoView({behavior:'smooth'});toggleMenu();return false;">Opiniones</a>
    <a href="vista/mis-reservas.php">Mis reservas</a>
    <a href="vista/registro.php?rol=tecnico">¿Trabajar con nosotros?</a>
  <?php if ($sesionActiva): ?>
    <div class="mobile-user-info">👤 <?php echo $nombreUsuario; ?> &mdash; <?php echo $correoUsuario; ?></div>
    <a href="vista/perfil.php">✏️ Editar perfil</a>
    <?php if ($rolUsuario === 'tecnico'): ?>
      <a href="vista/tecnico/dashboard.php">🛠️ Mi panel técnico</a>
    <?php else: ?>
      <a href="vista/bienvenida.php">👤 Mi panel</a>
    <?php endif; ?>
    <a href="index.php?accion=logout" class="mobile-cta-btn" style="background:var(--green-dark);color:var(--white)!important">Cerrar sesión</a>
  <?php else: ?>
    <a href="vista/login.php">Iniciar sesión</a>
    <a href="vista/registro.php" class="mobile-cta-btn">Registrarse</a>
  <?php endif; ?>
  </div>


  <?php if (isset($_GET['registro']) && $_GET['registro'] === 'exito'): ?>
    <div class="registro-banner" id="reg-banner">
      ✅ ¡Registro exitoso! Ya puedes iniciar sesión.
    </div>
    <script>setTimeout(() => { const b = document.getElementById('reg-banner'); if(b) b.remove(); }, 4000);</script>
  <?php endif; ?>

  <!-- ===== HOME PAGE ===== -->
  <div id="page-home" class="page active">

    <!-- HERO -->
    <section class="hero">
      <div class="hero-bg-orb orb1"></div>
      <div class="hero-bg-orb orb2"></div>
      <div class="hero-content">
        <div class="hero-tag"><span class="dot"></span> Plataforma verificada y confiable</div>
        <h1>Todos los servicios<br>para tu <em>hogar</em><br>en un solo lugar</h1>
        <p>Conectamos usuarios con técnicos y profesionales certificados en tu ciudad. Compara precios, lee reseñas
          reales y agenda el servicio que mejor se adapte a ti.</p>
        <div class="search-wrapper">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" id="hero-search" placeholder="¿Qué servicio necesitas? Ej: nevera, plomería, pintura..."
              onkeydown="if(event.key==='Enter'){window.location='vista/buscar.php?q='+encodeURIComponent(this.value)}">
            <div class="search-cat"><select id="search-cat-sel">
                <option value="">Todas las categorías</option>
                <option>Electrodomésticos</option>
                <option>Plomería</option>
                <option>Electricidad</option>
                <option>Pintura</option>
                <option>Cerrajería</option>
                <option>Electrónica</option>
                <option>Cocción</option>
              </select></div>
            <button class="search-btn-el" onclick="window.location='vista/buscar.php?q='+encodeURIComponent(document.getElementById('hero-search').value)">Buscar</button>
          </div>

        </div>
        <div class="hero-btns">
          <a href="vista/buscar.php" class="btn-primary">Explorar servicios →</a>
          <a href="#como-funciona" class="btn-secondary" onclick="document.getElementById('como-funciona').scrollIntoView({behavior:'smooth'});return false;">¿Cómo funciona?</a>
        </div>
        <div class="hero-trust">
          <div class="trust-avatars">
            <div class="av" style="background:#61D095">C</div>
            <div class="av" style="background:#E0BAD7">M</div>
            <div class="av" style="background:#439775;color:#fff">J</div>
            <div class="av" style="background:#48BF84">L</div>
          </div>
          <span>+18.000 servicios completados con ⭐ 4.8</span>
        </div>
      </div>
      <div class="hero-visual">
        <div class="floating-card card-main">
          <div class="card-header">
            <div class="card-icon">🔧</div>
            <div>
              <div class="card-name">Carlos Mendoza</div>
              <div class="card-role">Técnico · Electrodomésticos</div>
            </div>
            <div class="card-badge">✓ Verificado</div>
          </div>
          <div class="card-stars">★★★★★ <span>4.9 (127 reseñas)</span></div>
          <div class="card-chips"><span class="chip">Neveras</span><span class="chip">Lavadoras</span><span
              class="chip">A/C</span></div>
          <div class="card-footer-el">
            <div class="card-price-el">$45.000 <span>/ visita</span></div><button class="card-btn-el"
              onclick="openBookingModal('Carlos Mendoza','Técnico Electrodomésticos','$45.000','rgba(97,208,149,0.25)','CM')">Contactar</button>
          </div>
        </div>
        <div class="floating-card card-pill pill1"><span>🪠</span> Plomería disponible</div>
        <div class="floating-card card-pill pill2"><span>⚡</span> +1.200 técnicos</div>
        <div class="floating-card card-pill pill3"><span>🎨</span> Pintura y drywall</div>
      </div>
    </section>

    <!-- STATS -->
    <div class="stats-strip">
      <div class="stat-item">
        <div class="stat-num">+1.200</div>
        <div class="stat-label">Técnicos certificados</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">+18.000</div>
        <div class="stat-label">Servicios realizados</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">4.8 ★</div>
        <div class="stat-label">Calificación promedio</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">+45</div>
        <div class="stat-label">Ciudades disponibles</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">12+</div>
        <div class="stat-label">Tipos de servicio</div>
      </div>
    </div>

    <!-- HOW IT WORKS -->
    <section class="how-section" id="como-funciona">
      <div style="max-width:560px" class="reveal">
        <span class="section-label">El proceso</span>
        <div class="section-title">Tres pasos para un servicio exitoso</div>
        <p class="section-desc">Encontrar un profesional confiable nunca fue tan sencillo. Nuestra plataforma te guía en
          cada etapa.</p>
      </div>
      <div class="steps-grid">
        <div class="step-card reveal">
          <div class="step-num">01</div><span class="step-icon">🔍</span>
          <h3>Describe lo que necesitas</h3>
          <p>Cuéntanos qué servicio requieres: reparación, instalación, construcción o mantenimiento. En segundos verás
            profesionales disponibles cerca de ti.</p>
          <div class="step-glow"></div>
        </div>
        <div class="step-card reveal" style="transition-delay:.1s">
          <div class="step-num">02</div><span class="step-icon">⚖️</span>
          <h3>Compara y elige</h3>
          <p>Revisa perfiles, precios, calificaciones y reseñas verificadas de otros clientes. Elige la opción que mejor
            se adapte a tu presupuesto.</p>
          <div class="step-glow"></div>
        </div>
        <div class="step-card reveal" style="transition-delay:.2s">
          <div class="step-num">03</div><span class="step-icon">📅</span>
          <h3>Agenda y confía</h3>
          <p>Reserva la visita directamente desde la plataforma. El profesional llega, realiza el trabajo, y tú lo
            calificas para ayudar a la comunidad.</p>
          <div class="step-glow"></div>
        </div>
      </div>
    </section>

    <!-- SERVICES -->
    <section class="services-section" id="servicios">
      <div class="bg-pattern"></div>
      <div style="max-width:560px;margin-bottom:3.5rem;position:relative;z-index:1" class="reveal">
        <span class="section-label">Lo que ofrecemos</span>
        <div class="section-title" style="color:var(--white)">Profesionales para<br>cada necesidad del hogar</div>
        <p class="section-desc" style="color:rgba(255,255,255,.6);margin-bottom:0">Desde reparación de electrodomésticos
          hasta remodelaciones completas.</p>
      </div>
      <div class="services-grid" id="services-grid">
        <a href="vista/buscar.php?categoria=electrodomesticos" class="service-card" style="text-decoration:none">
          <div class="card-arrow">→</div>
          <div class="svc-icon" style="background:rgba(97,208,149,.12)">🔧</div>
          <h3>Electrodomésticos</h3>
          <p>Neveras, lavadoras, secadoras, aires acondicionados y más.</p>
          <div class="svc-tags"><span class="svc-tag">Neveras</span><span class="svc-tag">Lavadoras</span><span class="svc-tag">A/C</span></div>
        </a>
        <a href="vista/buscar.php?categoria=plomeria" class="service-card" style="text-decoration:none">
          <div class="card-arrow">→</div>
          <div class="svc-icon" style="background:rgba(97,208,149,.12)">🪠</div>
          <h3>Plomería</h3>
          <p>Tuberías, filtraciones, instalación de sanitarios y grifería.</p>
          <div class="svc-tags"><span class="svc-tag">Tuberías</span><span class="svc-tag">Grifería</span><span class="svc-tag">Sanitarios</span></div>
        </a>
        <a href="vista/buscar.php?categoria=electricidad" class="service-card" style="text-decoration:none">
          <div class="card-arrow">→</div>
          <div class="svc-icon" style="background:rgba(224,186,215,.12)">⚡</div>
          <h3>Electricidad</h3>
          <p>Instalaciones eléctricas, tomacorrientes, tableros y más.</p>
          <div class="svc-tags"><span class="svc-tag">Instalaciones</span><span class="svc-tag">Tableros</span><span class="svc-tag">Urgencias</span></div>
        </a>
        <a href="vista/buscar.php?categoria=pintura" class="service-card" style="text-decoration:none">
          <div class="card-arrow">→</div>
          <div class="svc-icon" style="background:rgba(97,208,149,.12)">🎨</div>
          <h3>Pintura</h3>
          <p>Pintura interior y exterior, drywall, estuco y acabados.</p>
          <div class="svc-tags"><span class="svc-tag">Interior</span><span class="svc-tag">Exterior</span><span class="svc-tag">Drywall</span></div>
        </a>
        <a href="vista/buscar.php?categoria=cerrajeria" class="service-card" style="text-decoration:none">
          <div class="card-arrow">→</div>
          <div class="svc-icon" style="background:rgba(67,151,117,.12)">🔑</div>
          <h3>Cerrajería</h3>
          <p>Apertura de puertas, cambio de chapas y cerraduras de seguridad.</p>
          <div class="svc-tags"><span class="svc-tag">Chapas</span><span class="svc-tag">Emergencias</span><span class="svc-tag">Seguridad</span></div>
        </a>
        <a href="vista/buscar.php?categoria=electronica" class="service-card" style="text-decoration:none">
          <div class="card-arrow">→</div>
          <div class="svc-icon" style="background:rgba(224,186,215,.12)">📺</div>
          <h3>Electrónica</h3>
          <p>Reparación de televisores, computadores y dispositivos del hogar.</p>
          <div class="svc-tags"><span class="svc-tag">TV</span><span class="svc-tag">Computadores</span><span class="svc-tag">Sonido</span></div>
        </a>
        <a href="vista/buscar.php?categoria=coccion" class="service-card" style="text-decoration:none">
          <div class="card-arrow">→</div>
          <div class="svc-icon" style="background:rgba(97,208,149,.12)">🍳</div>
          <h3>Cocción</h3>
          <p>Estufas, hornos, microondas y campanas extractoras.</p>
          <div class="svc-tags"><span class="svc-tag">Estufas</span><span class="svc-tag">Hornos</span><span class="svc-tag">Campanas</span></div>
        </a>
        <a href="vista/buscar.php?categoria=remodelacion" class="service-card" style="text-decoration:none">
          <div class="card-arrow">→</div>
          <div class="svc-icon" style="background:rgba(67,151,117,.12)">🏠</div>
          <h3>Remodelación</h3>
          <p>Enchapes, pisos, cielos rasos y reformas integrales del hogar.</p>
          <div class="svc-tags"><span class="svc-tag">Pisos</span><span class="svc-tag">Enchapes</span><span class="svc-tag">Cielos rasos</span></div>
        </a>
      </div>
    </section>

    <!-- REVIEWS -->
    <section class="reviews-section" id="opiniones">
      <div style="max-width:560px;margin-bottom:3rem" class="reveal">
        <span class="section-label">Testimonios</span>
        <div class="section-title">Miles de hogares ya confían en ReparaTech</div>
      </div>
      <div class="reviews-grid">
        <div class="review-card reveal">
          <div class="review-stars">★★★★★</div>
          <p class="review-text">"Encontré un técnico en minutos. El precio fue justo y el trabajo quedó perfecto. Nunca
            había tenido una experiencia tan sencilla buscando servicio técnico."</p>
          <div class="review-author">
            <div class="review-avatar" style="background:rgba(224,186,215,.3);color:var(--green-dark)">AM</div>
            <div>
              <div class="author-name">Andrea Moreno</div>
              <div class="author-role">Medellín · Reparación de lavadora</div>
            </div>
          </div>
        </div>
        <div class="review-card reveal" style="transition-delay:.1s">
          <div class="review-stars">★★★★★</div>
          <p class="review-text">"Me encantó poder comparar varios técnicos y ver las reseñas antes de elegir. Mucho
            mejor que buscar en internet sin ninguna garantía."</p>
          <div class="review-author">
            <div class="review-avatar" style="background:rgba(97,208,149,.25);color:var(--green-sea)">JR</div>
            <div>
              <div class="author-name">Jorge Ramírez</div>
              <div class="author-role">Bogotá · Reparación de nevera</div>
            </div>
          </div>
        </div>
        <div class="review-card reveal" style="transition-delay:.2s">
          <div class="review-stars">★★★★★</div>
          <p class="review-text">"Excelente plataforma. El técnico llegó puntual, explicó todo claramente y dejó la
            estufa como nueva. Ya la recomendé a toda mi familia."</p>
          <div class="review-author">
            <div class="review-avatar" style="background:rgba(67,151,117,.2);color:var(--green-sea)">LP</div>
            <div>
              <div class="author-name">Laura Pineda</div>
              <div class="author-role">Cali · Reparación de estufa</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <div class="cta-wrapper" id="tecnico">
      <section class="cta-section">
        <div class="cta-orb"></div>
        <div class="cta-content">
          <span class="section-label" style="color:var(--green-light)">Para profesionales</span>
          <h2>¿Quieres trabajar<br>con nosotros?</h2>
          <p>Únete a nuestra red y accede a cientos de clientes en tu ciudad. Mayor visibilidad, más ingresos y
            reputación digital verificada.</p>
          <div class="cta-btns">
            <button class="btn-white" href="vista/registro.php?rol=tecnico">Registrarme como profesional</button>
            <button class="btn-outline-white" href="vista/registro.php?rol=tecnico">Conocer los beneficios →</button>
          </div>
        </div>
        <div class="cta-perks">
          <div class="perk"><span class="perk-icon">📱</span>
            <div>
              <div class="perk-title">Perfil digital</div>
              <div class="perk-desc">Muestra tu trabajo y acumula reseñas verificadas</div>
            </div>
          </div>
          <div class="perk"><span class="perk-icon">💰</span>
            <div>
              <div class="perk-title">Más ingresos</div>
              <div class="perk-desc">Accede a clientes activos sin costo por registro</div>
            </div>
          </div>
          <div class="perk"><span class="perk-icon">🛡️</span>
            <div>
              <div class="perk-title">Red confiable</div>
              <div class="perk-desc">Sello de técnico verificado para ganar credibilidad</div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- FOOTER -->
    <footer>
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="footer-logo">Repara<span>Tech</span></div>
          <p class="footer-brand-p">La plataforma especializada que conecta hogares con técnicos y profesionales
            certificados en toda Colombia.</p>
          <div class="social-links"><a href="#">📘</a><a href="#">📷</a><a href="#">💼</a></div>
        </div>
        <div class="footer-col">
          <h4>Para usuarios</h4>
          <ul>
            <li><a href="#" href="vista/buscar.php">Buscar profesional</a></li>
            <li><a href="#">Cómo funciona</a></li>
            <li><a href="#">Precios</a></li>
            <li><a href="#">Garantías</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Para técnicos</h4>
          <ul>
            <li><a href="#" href="vista/registro.php?rol=tecnico">Registrarse</a></li>
            <li><a href="#">Beneficios</a></li>
            <li><a href="#">Certificaciones</a></li>
            <li><a href="#">Soporte</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Empresa</h4>
          <ul>
            <li><a href="#">Acerca de</a></li>
            <li><a href="#">Blog</a></li>
            <li><a href="#">Términos</a></li>
            <li><a href="#">Privacidad</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom"><span>© 2025 ReparaTech — Todos los derechos reservados</span><span>Hecho con <span
            class="heart">♥</span> para Colombia</span></div>
    </footer>
  </div><!-- end page-home -->


  <script>
    // Reveal on scroll
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); observer.unobserve(e.target); } });
    }, { threshold: 0.12 });
    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

    // Navbar scroll
    window.addEventListener('scroll', () => {
      document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 40);
    });

    // Hamburger menu
    function toggleMenu() {
      document.getElementById('hamburger').classList.toggle('active');
      document.getElementById('mobile-menu').classList.toggle('open');
    }

    // goTo (logo click)
    function goTo(page) {
      if (page === 'home') window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  </script>
</body>
</html>
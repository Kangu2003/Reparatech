# ReparaTech - Plataforma de Servicios Técnicos

ReparaTech es una plataforma web completa desarrollada con **PHP, MySQL y el patrón de diseño MVC**, que conecta a usuarios (clientes) con técnicos especializados en diversas áreas (reparación, mantenimiento, instalación, etc.).

## 🚀 Características Principales

### Roles de Usuario
- **Clientes (Usuarios):** Pueden buscar técnicos por categoría o ubicación, ver perfiles y valoraciones, realizar reservas de servicios, chatear en tiempo real con los técnicos, guardar favoritos, pagar servicios y abrir disputas si es necesario.
- **Técnicos:** Pueden gestionar su perfil profesional, indicar su ubicación en un mapa, establecer su disponibilidad y tarifas, recibir solicitudes de reserva, enviar mensajes a clientes, ver un panel financiero con sus ganancias y solicitar retiros de fondos. Además, cuentan con un sistema de membresía "Premium/Experto".
- **Administradores:** Tienen acceso a un panel de control avanzado para gestionar usuarios, técnicos, aprobar solicitudes de retiro, resolver disputas, moderar la plataforma y visualizar estadísticas de rendimiento.

### Funcionalidades Clave
- **Búsqueda y Filtros:** Búsqueda avanzada de técnicos con mapas interactivos (Leaflet).
- **Sistema de Reservas:** Gestión completa del ciclo de vida de un servicio (solicitud, aprobación, completado).
- **Chat en Tiempo Real:** Sistema de mensajería instantánea entre clientes y técnicos (AJAX/Polling).
- **Valoraciones y Reseñas:** Los clientes pueden calificar el trabajo de los técnicos.
- **Gestión Financiera:** Integración de pagos, facturación en PDF (FPDF), panel de ganancias y retiros.
- **Sistema de Disputas:** Resolución de conflictos entre clientes y técnicos mediado por administradores.
- **Notificaciones:** Integración con WhatsApp (UltraMsg) para alertas importantes.

---

## 🛠️ Tecnologías Utilizadas

- **Backend:** PHP 7.4+ u 8.x
- **Base de Datos:** MySQL
- **Arquitectura:** MVC (Modelo-Vista-Controlador) sin frameworks pesados
- **Frontend:** HTML5, CSS3, JavaScript Vanilla
- **Librerías Adicionales:** 
  - Chart.js (Gráficos estadísticos)
  - Leaflet.js (Mapas y geolocalización)
  - FPDF (Generación de facturas PDF)
  - FontAwesome (Iconos)

---

## 📁 Estructura del Proyecto

```
inicio_sesion_mvc/
├── index.php                        ← Punto de entrada (Front Controller) y enrutador principal
├── database.sql                     ← Script SQL con la estructura y datos de prueba
├── README.md                        ← Documentación del proyecto
│
├── config/
│   └── config.php                   ← Credenciales de la base de datos
│
├── modelo/                          ← Lógica de acceso a datos e interacción con la BD
│   ├── Conexion.php                 ← Gestión de la conexión PDO/MySQLi
│   ├── Usuario.php, Tecnico.php     ← Modelos de entidades principales
│   ├── Servicio.php, Reserva.php    ← Modelos de servicios y reservas
│   ├── Mensaje.php, Disputa.php     ← Modelos para chat y resolución de conflictos
│   └── Pago.php, Retiro.php         ← Modelos financieros
│
├── controlador/                     ← Lógica de la aplicación e intermediario
│   ├── ControladorUsuario.php       ← Gestión de clientes
│   ├── ControladorTecnico.php       ← Gestión de técnicos
│   ├── ControladorAdmin.php         ← Panel de administración
│   ├── ControladorReserva.php       ← Flujo de reservas
│   └── ControladorChat.php          ← Lógica de mensajería
│
├── vista/                           ← Interfaz de usuario (HTML/PHP)
│   ├── admin/                       ← Vistas del panel de administrador
│   ├── tecnico/                     ← Vistas exclusivas de técnicos (dashboard financiero, retiros)
│   ├── buscar.php, reservar.php     ← Vistas públicas o de cliente
│   ├── chat.php, mis-reservas.php   ← Herramientas interactivas
│   └── login.php, registro.php      ← Vistas de autenticación
│
└── utils/                           ← Funciones auxiliares y utilidades
    └── fpdf/                        ← Librería para PDF
```

---

## ⚙️ Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache recomendado, e.g., XAMPP, WAMP, MAMP)
- Navegador web moderno

---

## 🚀 Pasos de Instalación

### 1. Configurar el entorno local
Instala **XAMPP** o similar. Asegúrate de iniciar los servicios de **Apache** y **MySQL**.

### 2. Copiar el proyecto
Coloca la carpeta del proyecto (`inicio_sesion_mvc`) en el directorio público de tu servidor:
- **Windows (XAMPP):** `C:\xampp\htdocs\inicio_sesion_mvc`
- **Linux (XAMPP):** `/opt/lampp/htdocs/inicio_sesion_mvc`

### 3. Base de Datos
1. Accede a `http://localhost/phpmyadmin`.
2. Crea una base de datos nueva (ej. `reparatech`).
3. Importa el archivo `database.sql` incluido en la raíz del proyecto para crear todas las tablas (usuarios, tecnicos, reservas, mensajes, disputas, etc.).

### 4. Configuración
Edita el archivo `config/config.php` con tus credenciales de base de datos:

```php
return [
    'host'          => 'localhost',
    'usuario'       => 'root',
    'contrasena'    => '', // Vacío por defecto en XAMPP
    'base_de_datos' => 'reparatech' // El nombre que hayas elegido
];
```

### 5. Ejecución
Abre tu navegador y accede a:
```
http://localhost/inicio_sesion_mvc/
```

---

## 🔒 Seguridad Implementada

- **Contraseñas seguras:** Encriptadas mediante `password_hash()` (bcrypt).
- **Protección SQLi:** Uso de sentencias preparadas en todas las consultas a la base de datos.
- **Protección XSS:** Sanitización de salidas con `htmlspecialchars()`.
- **Control de Acceso:** Verificación rigurosa de sesiones para las distintas áreas (cliente, técnico, administrador).

---

## 📝 Notas Adicionales
- La plataforma sigue estrictamente el patrón **MVC**, separando la lógica de negocio de la presentación visual.
- El archivo `index.php` actúa como un **Front Controller**, manejando todo el enrutamiento y despacho de acciones hacia los controladores correspondientes a través del parámetro GET `?action=...`.

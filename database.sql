-- ============================================================
-- ReparaTech — Base de datos completa con roles y mejoras (Chat, Pagos, Recuperación)
-- Ejecuta este archivo en phpMyAdmin o MySQL Workbench
-- ============================================================

CREATE DATABASE IF NOT EXISTS reparatech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reparatech;

-- ============================================================
-- TABLA: usuarios (unifica usuarios, administradores y técnicos)
-- ============================================================
CREATE TABLE usuarios (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario   VARCHAR(50)  NOT NULL,
    correo_electronico VARCHAR(100) NOT NULL UNIQUE,
    contrasena       VARCHAR(255) NOT NULL,
    rol              ENUM('usuario', 'tecnico', 'admin') NOT NULL DEFAULT 'usuario',
    telefono         VARCHAR(20)  DEFAULT '',
    ciudad           VARCHAR(60)  DEFAULT 'Santa Marta',
    bio              TEXT         DEFAULT '',
    foto             VARCHAR(255) DEFAULT '',
    activo           TINYINT(1)   NOT NULL DEFAULT 1,
    codigo_recuperacion VARCHAR(10) DEFAULT NULL,
    expiracion_codigo TIMESTAMP   NULL DEFAULT NULL,
    creado_en        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insertar Administrador por defecto (admin@reparatech.com / admin123)
INSERT INTO usuarios (nombre_usuario, correo_electronico, contrasena, rol, foto) 
VALUES ('Super Administrador', 'admin@reparatech.com', '$2y$10$wN1Q.P/kYI0xJ9xO6XvD.eN1v1sH2zGkYI4xJ9xO6XvD.eN1v1sH2', 'admin', 'https://api.dicebear.com/7.x/avataaars/svg?seed=Admin')
ON DUPLICATE KEY UPDATE rol = 'admin';

-- ============================================================
-- TABLA: categorias de servicios
-- ============================================================
CREATE TABLE categorias (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    icono  VARCHAR(10) DEFAULT '🔧'
) ENGINE=InnoDB;

INSERT INTO categorias (nombre, icono) VALUES
('Electricidad',   '🔌'),
('Plomería',       '🚿'),
('Refrigeración',  '🧊'),
('Carpintería',    '🪚'),
('Pintura',        '🖌️'),
('Tecnología',     '💻'),
('Cerrajería',     '🔑'),
('Jardinería',     '🌿');

-- ============================================================
-- TABLA: servicios (creados por técnicos)
-- ============================================================
CREATE TABLE servicios (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    tecnico_id   INT          NOT NULL,
    categoria_id INT          NOT NULL,
    titulo       VARCHAR(100) NOT NULL,
    descripcion  TEXT,
    precio       DECIMAL(10,2) NOT NULL,
    precio_tipo  ENUM('fijo', 'por_hora') NOT NULL DEFAULT 'fijo',
    disponible   TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tecnico_id)   REFERENCES usuarios(id)    ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)  ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: disponibilidad del técnico
-- ============================================================
CREATE TABLE disponibilidad (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    tecnico_id INT  NOT NULL,
    dia_semana ENUM('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin    TIME NOT NULL,
    FOREIGN KEY (tecnico_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: reservas / solicitudes de servicio
-- ============================================================
CREATE TABLE reservas (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id   INT  NOT NULL,
    servicio_id  INT  NOT NULL,
    tecnico_id   INT  NOT NULL,
    fecha        DATE NOT NULL,
    hora         TIME NOT NULL,
    direccion    VARCHAR(200) DEFAULT '',
    notas        TEXT,
    estado       ENUM('pendiente','aceptada','en_progreso','completada','cancelada')
                 NOT NULL DEFAULT 'pendiente',
    precio_final DECIMAL(10,2),
    creado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)  ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE RESTRICT,
    FOREIGN KEY (tecnico_id)  REFERENCES usuarios(id)  ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: reseñas
-- ============================================================
CREATE TABLE resenas (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT  NOT NULL UNIQUE,
    usuario_id INT  NOT NULL,
    tecnico_id INT  NOT NULL,
    calificacion TINYINT NOT NULL CHECK (calificacion BETWEEN 1 AND 5),
    calificacion_puntualidad   TINYINT DEFAULT NULL COMMENT '1-5: puntualidad',
    calificacion_comunicacion  TINYINT DEFAULT NULL COMMENT '1-5: comunicación',
    calificacion_calidad       TINYINT DEFAULT NULL COMMENT '1-5: calidad del trabajo',
    calificacion_precio        TINYINT DEFAULT NULL COMMENT '1-5: relación precio-valor',
    aspectos                   VARCHAR(500) DEFAULT '' COMMENT 'Etiquetas positivas separadas por coma',
    comentario TEXT,
    respuesta_tecnico          TEXT DEFAULT NULL COMMENT 'Respuesta pública del técnico',
    fecha_respuesta            TIMESTAMP NULL DEFAULT NULL,
    util_count                 INT DEFAULT 0 COMMENT 'Cuántos usuarios marcaron como útil',
    creado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (tecnico_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: pagos (registro de transacciones y facturación)
-- ============================================================
CREATE TABLE pagos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id  INT NOT NULL UNIQUE,
    usuario_id  INT NOT NULL,
    monto       DECIMAL(10,2) NOT NULL,
    metodo_pago VARCHAR(50) NOT NULL,
    estado      VARCHAR(20) NOT NULL DEFAULT 'completado',
    referencia  VARCHAR(50) NOT NULL UNIQUE,
    creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: ganancias del técnico (se registra al completar el pago)
-- ============================================================
CREATE TABLE ganancias (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    tecnico_id INT            NOT NULL,
    reserva_id INT            NOT NULL UNIQUE,
    monto      DECIMAL(10,2)  NOT NULL,
    fecha      DATE           NOT NULL,
    FOREIGN KEY (tecnico_id) REFERENCES usuarios(id)  ON DELETE CASCADE,
    FOREIGN KEY (reserva_id) REFERENCES reservas(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: mensajes (Chat en tiempo real)
-- ============================================================
CREATE TABLE mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT NOT NULL,
    remitente_id INT NOT NULL,
    mensaje TEXT NOT NULL,
    leido TINYINT(1) DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
    FOREIGN KEY (remitente_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: retiros (solicitudes de retiro de ganancias de técnicos)
-- ============================================================
CREATE TABLE retiros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tecnico_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    banco VARCHAR(100) NOT NULL,
    tipo_cuenta VARCHAR(50) NOT NULL,
    numero_cuenta VARCHAR(100) NOT NULL,
    estado ENUM('pendiente', 'aprobado', 'rechazado') NOT NULL DEFAULT 'pendiente',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tecnico_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: calificaciones de USUARIOS (por técnicos)
-- ============================================================
CREATE TABLE calificaciones_usuario (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id      INT NOT NULL UNIQUE,
    tecnico_id      INT NOT NULL,
    usuario_id      INT NOT NULL,
    calificacion    TINYINT NOT NULL CHECK (calificacion BETWEEN 1 AND 5),
    puntualidad     TINYINT DEFAULT NULL,
    comunicacion    TINYINT DEFAULT NULL,
    respeto         TINYINT DEFAULT NULL,
    pago_puntual    TINYINT DEFAULT NULL,
    comentario      TEXT,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reserva_id)  REFERENCES reservas(id)  ON DELETE CASCADE,
    FOREIGN KEY (tecnico_id)  REFERENCES usuarios(id)  ON DELETE CASCADE,
    FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: PORTAFOLIO del técnico
-- ============================================================
CREATE TABLE portafolio (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    tecnico_id      INT NOT NULL,
    titulo          VARCHAR(150) NOT NULL,
    descripcion     TEXT,
    categoria_id    INT DEFAULT NULL,
    imagen_url      VARCHAR(500) DEFAULT '',
    imagen_2_url    VARCHAR(500) DEFAULT '',
    imagen_3_url    VARCHAR(500) DEFAULT '',
    precio_cobrado  DECIMAL(10,2) DEFAULT NULL,
    duracion_horas  DECIMAL(5,1) DEFAULT NULL,
    fecha_trabajo   DATE DEFAULT NULL,
    destacado       TINYINT(1) DEFAULT 0,
    likes           INT DEFAULT 0,
    vistas          INT DEFAULT 0,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tecnico_id)   REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: Likes del portafolio
-- ============================================================
CREATE TABLE portafolio_likes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    portafolio_id   INT NOT NULL,
    usuario_id      INT NOT NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (portafolio_id, usuario_id),
    FOREIGN KEY (portafolio_id) REFERENCES portafolio(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id)    REFERENCES usuarios(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: Historial del chatbot (para contexto)
-- ============================================================
CREATE TABLE chatbot_historial (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT NOT NULL,
    rol             ENUM('user','assistant') NOT NULL,
    mensaje         TEXT NOT NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;
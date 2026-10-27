-- Esquema completo para una instalación nueva de AgendaWeb en MySQL.
-- En bases existentes, aplica las migraciones P5 y P8 en lugar de volver a crear tablas.

CREATE DATABASE IF NOT EXISTS agendaweb CHARACTER SET utf8mb4;
USE agendaweb;

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT IGNORE INTO categorias (nombre) VALUES
    ('Trabajo'),
    ('Personal'),
    ('Estudio'),
    ('Ocio / Deporte');

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    correo VARCHAR(120) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(120) NOT NULL,
    ubicacion VARCHAR(255) NULL,
    fecha DATE NOT NULL,
    hora TIME NULL,
    categoria_id INT NOT NULL,
    descripcion VARCHAR(500) NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_eventos_usuario_id (usuario_id),
    INDEX idx_eventos_categoria_id (categoria_id),
    CONSTRAINT fk_eventos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_eventos_categorias
        FOREIGN KEY (categoria_id) REFERENCES categorias (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

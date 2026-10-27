-- Migración P8 para una base existente de AgendaWeb.
-- Ejecuta la Fase 1 una sola vez. Registra la primera cuenta en registro.php.
-- Después reemplaza 1 en la Fase 2 por el id de esa cuenta y ejecuta la fase.
-- Haz respaldo de la base antes de comenzar.

USE agendaweb;

-- Fase 1: conservar datos y preparar cuentas y dueño nullable.
CREATE TABLE eventos_respaldo_p8 AS
SELECT * FROM eventos;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    correo VARCHAR(120) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE eventos
    ENGINE=InnoDB,
    ADD COLUMN usuario_id INT NULL AFTER id,
    ADD INDEX idx_eventos_usuario_id (usuario_id);

-- Fase 2: después de crear la primera cuenta, reemplaza 1 por su id.
-- Ejecuta las sentencias siguientes después de la Fase 1 y del registro.
UPDATE eventos
SET usuario_id = 1
WHERE usuario_id IS NULL;

ALTER TABLE eventos
    MODIFY usuario_id INT NOT NULL,
    ADD CONSTRAINT fk_eventos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE;

-- Verifica la migración:
-- DESCRIBE eventos;
-- SELECT id, nombre, correo FROM usuarios;

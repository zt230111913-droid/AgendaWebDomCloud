-- Migración P5: normaliza las categorías y conserva los eventos existentes.
-- Haz una copia de seguridad antes de ejecutar. Ejecutar una sola vez por base.
-- Selecciona la base correcta en phpMyAdmin o ajusta este nombre para DOM Cloud.

USE agendaweb;

CREATE TABLE eventos_respaldo LIKE eventos;
INSERT INTO eventos_respaldo SELECT * FROM eventos;

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT IGNORE INTO categorias (nombre) VALUES
    ('Trabajo'),
    ('Personal'),
    ('Estudio'),
    ('Ocio / Deporte');

-- También conserva nombres de categorías personalizadas que ya existan.
INSERT IGNORE INTO categorias (nombre)
SELECT DISTINCT CASE LOWER(TRIM(COALESCE(categoria, '')))
    WHEN 'trabajo' THEN 'Trabajo'
    WHEN 'personal' THEN 'Personal'
    WHEN 'estudio' THEN 'Estudio'
    WHEN 'ocio' THEN 'Ocio / Deporte'
    WHEN 'ocio / deporte' THEN 'Ocio / Deporte'
    ELSE TRIM(categoria)
END
FROM eventos
WHERE TRIM(COALESCE(categoria, '')) <> '';

ALTER TABLE eventos
    ADD COLUMN categoria_id INT NULL AFTER categoria;

UPDATE eventos AS e
JOIN categorias AS c
    ON c.nombre COLLATE utf8mb4_unicode_ci = (
        CASE LOWER(TRIM(COALESCE(e.categoria, '')))
        WHEN '' THEN 'Personal'
        WHEN 'trabajo' THEN 'Trabajo'
        WHEN 'personal' THEN 'Personal'
        WHEN 'estudio' THEN 'Estudio'
        WHEN 'ocio' THEN 'Ocio / Deporte'
        WHEN 'ocio / deporte' THEN 'Ocio / Deporte'
        ELSE TRIM(e.categoria)
        END
    ) COLLATE utf8mb4_unicode_ci
SET e.categoria_id = c.id;

ALTER TABLE eventos
    ENGINE=InnoDB,
    MODIFY hora TIME NULL,
    MODIFY categoria_id INT NOT NULL,
    ADD INDEX idx_eventos_categoria_id (categoria_id),
    ADD CONSTRAINT fk_eventos_categorias
        FOREIGN KEY (categoria_id) REFERENCES categorias (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    DROP COLUMN categoria;

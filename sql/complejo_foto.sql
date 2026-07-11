-- ─────────────────────────────────────────────────────────────────────────────
-- complejo_foto.sql
-- Galería de fotos de cada predio (Ola 2). Una marcada como principal se usa
-- como imagen de portada en la landing y en las tarjetas del buscador/home.
-- Los archivos viven en config/dist/img/predios/<COMPLEJO_ID>/ ; acá se guarda
-- la ruta relativa a la raíz del repo.
-- Ejecutar una sola vez sobre la base `lacanchita`.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS complejo_foto (
    FOTO_ID        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    COMPLEJO_ID    INT UNSIGNED NOT NULL,
    FOTO_PATH      VARCHAR(255) NOT NULL,             -- ruta relativa a la raíz del repo
    FOTO_PRINCIPAL TINYINT(1) NOT NULL DEFAULT 0,
    FOTO_ORDEN     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    CREATED_AT     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_complejo (COMPLEJO_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- cierre_caja.sql
-- Arqueo / cierre de caja diario por predio (Ola 2).
-- Registra el corte de jornada: efectivo esperado por el sistema vs. contado
-- físicamente, con su diferencia. Un cierre por predio por día (re-cerrar pisa).
-- Ejecutar una sola vez sobre la base `lacanchita`.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS cierre_caja (
    CIERRE_CAJA_ID     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    COMPLEJO_ID        INT UNSIGNED NOT NULL,               -- predio de la caja
    USUARIOS_ID        INT UNSIGNED NOT NULL,               -- quién cerró
    CIERRE_FECHA       DATE NOT NULL,                       -- jornada arqueada
    FONDO_INICIAL      DECIMAL(10,2) NOT NULL DEFAULT 0,    -- caja de arranque
    EFECTIVO_SISTEMA   DECIMAL(10,2) NOT NULL DEFAULT 0,    -- efectivo cobrado (esperado)
    EFECTIVO_DECLARADO DECIMAL(10,2) NOT NULL DEFAULT 0,    -- efectivo contado físico
    DIFERENCIA         DECIMAL(10,2) NOT NULL DEFAULT 0,    -- declarado - (sistema + fondo)
    TOTAL_GENERAL      DECIMAL(10,2) NOT NULL DEFAULT 0,    -- cobrado en todos los medios
    NOTAS              TEXT,
    CREATED_AT         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_complejo_fecha (COMPLEJO_ID, CIERRE_FECHA),
    INDEX idx_complejo (COMPLEJO_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

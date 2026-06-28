-- ─────────────────────────────────────────────────────────────────────────────
-- suscripcion_plataforma.sql
-- Billing del desarrollador hacia sus clientes (dueños de predios).
-- Ejecutar una sola vez sobre la base de datos `lacanchita`.
-- ─────────────────────────────────────────────────────────────────────────────

-- Plan de cada cliente
CREATE TABLE IF NOT EXISTS suscripcion_plataforma (
    SUSCRIPCION_ID  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    USUARIOS_ID     INT UNSIGNED NOT NULL,               -- FK → usuarios (PERFIL_ID=2)
    PLAN_NOMBRE     VARCHAR(80)  NOT NULL DEFAULT 'Estándar',
    PLAN_PRECIO     DECIMAL(10,2) NOT NULL DEFAULT 0,
    PLAN_CICLO      ENUM('mensual','trimestral','anual') NOT NULL DEFAULT 'mensual',
    PROXIMO_COBRO   DATE,
    ULTIMO_COBRO    DATE,
    ESTADO          ENUM('prueba','activo','vencido','cancelado') NOT NULL DEFAULT 'prueba',
    MEDIO_COBRO     VARCHAR(50)  DEFAULT 'transferencia',
    NOTAS           TEXT,
    CREATED_AT      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuario (USUARIOS_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial de cobros de la plataforma
CREATE TABLE IF NOT EXISTS cobro_plataforma (
    COBRO_ID        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    USUARIOS_ID     INT UNSIGNED NOT NULL,               -- FK → usuarios
    COBRO_MONTO     DECIMAL(10,2) NOT NULL,
    COBRO_FECHA     DATE NOT NULL,
    COBRO_PERIODO   VARCHAR(7),                          -- YYYY-MM del período cubierto
    COBRO_MEDIO     VARCHAR(50),
    COBRO_NOTAS     TEXT,
    CREATED_AT      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

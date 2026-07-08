-- ─────────────────────────────────────────────────────────────────────────────
-- 2026-06-28_plan_predio.sql
-- Tipos de plan / suscripciones que cada dueño configura POR PREDIO.
-- El "tipo de plan" ES la suscripción: nombre + precio + período de cobro.
-- Cimiento de la futura "gestión de socios".
-- Idempotente: planes.php también crea/altera estas estructuras inline.
-- Ejecutar sobre la base `lacanchita`.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS plan_predio (
    PLAN_ID          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    COMPLEJO_ID      INT UNSIGNED NOT NULL,                 -- FK → complejo
    PLAN_NOMBRE      VARCHAR(100) NOT NULL,
    PLAN_DESCRIPCION TEXT,
    PLAN_PRECIO      DECIMAL(10,2) NOT NULL DEFAULT 0,
    PLAN_PERIODO     VARCHAR(20)  NOT NULL DEFAULT 'mensual', -- mensual|bimestral|trimestral|semestral|anual|unico
    PLAN_CREDITOS    SMALLINT UNSIGNED NOT NULL DEFAULT 0   COMMENT '0=ilimitado',
    PLAN_DURACION    SMALLINT UNSIGNED NOT NULL DEFAULT 30  COMMENT 'días de vigencia (derivado del período)',
    ACTIVO           TINYINT(1) NOT NULL DEFAULT 1,
    CREATED_AT       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_complejo (COMPLEJO_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Período de cobro (para bases donde plan_predio ya existía sin la columna)
ALTER TABLE plan_predio
    ADD COLUMN IF NOT EXISTS PLAN_PERIODO VARCHAR(20) NOT NULL DEFAULT 'mensual'
    AFTER PLAN_PRECIO;

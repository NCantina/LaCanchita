-- ─────────────────────────────────────────────────────────────────────────────
-- auditoria.sql
-- Log de acciones sensibles del panel: quién hizo qué y cuándo (cancelaciones
-- de reservas confirmadas, cierres de caja, gestión de staff, config de canchas).
-- Si el SuperAdmin actúa en modo soporte, ACTUA_COMO guarda el dueño asistido.
-- capabilities.php tiene el mismo CREATE embebido como respaldo de dev.
-- Ejecutar una sola vez sobre la base `lacanchita`.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS auditoria (
    AUD_ID       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    USUARIOS_ID  INT UNSIGNED NOT NULL,      -- quién ejecutó (usuario real)
    ACTUA_COMO   INT UNSIGNED NULL,          -- SA en modo soporte: dueño asistido
    CAP          VARCHAR(40) NOT NULL,       -- slug de capacidad (ej: reserva.cancelar)
    DETALLE      VARCHAR(255) NULL,          -- ej: "reserva #123 cancelada"
    IP           VARCHAR(45) NULL,
    CREATED_AT   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (USUARIOS_ID),
    INDEX idx_cap (CAP),
    INDEX idx_fecha (CREATED_AT)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

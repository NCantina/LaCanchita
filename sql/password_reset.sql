-- ─────────────────────────────────────────────────────────────────────────────
-- password_reset.sql
-- Tokens de recuperación de contraseña (flujo "¿Olvidaste tu contraseña?").
-- Se guarda el SHA-256 del token (nunca el token en claro); el token real viaja
-- solo en el link del email y caduca en 1 hora.
-- Ejecutar una sola vez sobre la base `lacanchita`.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS password_reset (
    RESET_ID    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    USUARIOS_ID INT UNSIGNED NOT NULL,               -- FK → usuarios
    TOKEN_HASH  CHAR(64) NOT NULL,                    -- sha256(token) en hex
    EXPIRA      DATETIME NOT NULL,
    USADO       TINYINT(1) NOT NULL DEFAULT 0,
    CREATED_AT  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_token (TOKEN_HASH),
    INDEX idx_usuario (USUARIOS_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

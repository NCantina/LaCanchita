-- ─────────────────────────────────────────────────────────────────────────────
-- recordatorio_plataforma.sql
-- Recordatorios de cobro de la plataforma hacia sus clientes (dueños de predios).
-- El SuperAdmin agenda un recordatorio (estilo Google Calendar) y un job lo envía
-- por email en la fecha/hora indicada.
-- Hasta ahora esta tabla nacía de un CREATE IF NOT EXISTS embebido en
-- view/maquetaSuperAdmin/api/clientes.php; se versiona acá para que el esquema
-- sea reproducible entre entornos.
-- Ejecutar una sola vez sobre la base `lacanchita`.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS recordatorio_plataforma (
    REC_ID      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    USUARIOS_ID INT UNSIGNED NOT NULL,               -- FK → usuarios (PERFIL_ID=2)
    ENVIAR_EN   DATETIME NOT NULL,
    DESCRIPCION VARCHAR(200),
    ENVIADO     TINYINT(1) NOT NULL DEFAULT 0,
    ENVIADO_EN  DATETIME,
    CREATED_AT  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pendientes (ENVIADO, ENVIAR_EN),
    INDEX idx_usuario (USUARIOS_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

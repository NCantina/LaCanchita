-- ─────────────────────────────────────────────────────────────────────────────
-- recordatorio_turno.sql
-- Log de recordatorios de turno enviados a los clientes (anti no-show).
-- El cron (cron/recordatorios_turno.php) recorre las reservas próximas y manda
-- un recordatorio "previo" (turno entre +2h y +24h) y otro "inminente"
-- (turno dentro de las próximas 2h). Cada envío se registra acá para no repetir.
--
-- La UNIQUE(RESERVA_ID, TIPO) es la garantía real de idempotencia: aunque dos
-- corridas del cron se solapen, el segundo INSERT del mismo (reserva, tipo) falla.
--
-- El cron también crea esta tabla con CREATE IF NOT EXISTS embebido como respaldo
-- de dev; se versiona acá para que el esquema sea reproducible entre entornos.
-- Ejecutar una sola vez sobre la base `lacanchita`.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS recordatorio_turno (
    RT_ID       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    RESERVA_ID  INT UNSIGNED NOT NULL,                 -- FK → reserva
    TIPO        VARCHAR(8) NOT NULL,                   -- '24h' (previo) | '2h' (inminente)
    ENVIADO_EN  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reserva_tipo (RESERVA_ID, TIPO),
    INDEX idx_reserva (RESERVA_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

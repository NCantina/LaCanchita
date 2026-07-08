-- ─────────────────────────────────────────────────────────────────────────────
-- rate_limit.sql
-- Contadores de rate limiting por IP+acción (anti fuerza-bruta en login,
-- registro y recuperación de contraseña). La app la autocrea con
-- CREATE IF NOT EXISTS; se versiona acá para el esquema reproducible.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS rate_limit (
    RL_KEY   VARCHAR(191) PRIMARY KEY,     -- "<bucket>|<ip>"
    RL_COUNT INT UNSIGNED NOT NULL DEFAULT 0,
    RL_RESET DATETIME NOT NULL             -- fin de la ventana actual
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- 2026-06-28_complejo_instagram.sql
-- Agrega el Instagram del predio (se muestra como botón en la landing predio.php).
-- Idempotente: la API complejos.php también lo aplica inline con ADD COLUMN IF NOT EXISTS.
-- Ejecutar sobre la base `lacanchita`.
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE complejo
    ADD COLUMN IF NOT EXISTS COMPLEJO_INSTAGRAM VARCHAR(150) NULL DEFAULT NULL
    AFTER COMPLEJO_EMAIL;

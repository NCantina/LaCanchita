# HISTORIAL.md — Log de decisiones y cambios importantes

> Formato: fecha — [COWORK|CODE] — resumen corto. Lo más nuevo arriba.
> Solo cosas importantes: decisiones de arquitectura/producto, features terminadas, bugs graves resueltos, cambios de convenciones.

---

## 2026-07-10 — [CODE] — Modelo de roles y capacidades implementado
- `capabilities.php`: can()/require_cap() por rol; encargado ≠ empleado en backend, UI y ruteo.
- Encargado → Dashboard (reportes/config/staff-empleados); Empleado → PanelEncargado (solo operación).
- Auditoría de acciones sensibles en tabla `auditoria`. Tests deny-by-default en la suite.
- Fix de suite en Git Bash/Windows: LC_ALL=C.UTF-8 (grep -P fallaba y los tokens CSRF salían vacíos).

## 2026-07-09 — [CODE] — Diseño del modelo de roles y capacidades
- Se define el mapa completo de trabajo por perfil con **capacidades fijas por rol** (helper `capabilities.php`: `can()`/`require_cap()`), enforcement en backend + auditoría + tests deny-by-default. Spec: `docs/superpowers/specs/2026-07-09-roles-y-capacidades-design.md`.
- Decisiones: empleado (4) puede caja y cancelar confirmadas, pero NO reportes/config; encargado (3) suma reportes/config y gestiona empleados; permisos fijos (no configurables por usuario).
- Superficie por perfil: **empleado → PanelEncargado**, **encargado → Dashboard** (gated), dueño/SA → Dashboard. Se detecta y se planifica cerrar el hueco del Dashboard (dejaba entrar 1–4).

## 2026-07-09 — [CODE] — Recordatorios de turno (anti no-show)
- Cron `cron/recordatorios_turno.php` + tabla `recordatorio_turno` (idempotente): recordatorio previo (2–24h) e inminente (<2h) por push/email. CLI o HTTP con clave. Pendiente correr la suite completa en un entorno con `composer install`.

## 2026-07-05 — [COWORK] — Se define el flujo de documentación
- Se acuerda dividir el trabajo: Claude Code para desarrollo, Cowork para decisiones/documentos/análisis.
- Se crea este archivo (`HISTORIAL.md`) como log compartido de decisiones.
- Regla agregada al `CLAUDE.md`: toda decisión o cambio importante se registra acá, tanto desde Code como desde Cowork.

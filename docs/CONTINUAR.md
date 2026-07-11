# CONTINUAR — Handoff para seguir LaCanchita en otra conversación

> Pegá en el chat nuevo: **"Leé `docs/CONTINUAR.md` y `docs/AUDITORIA-2026-06-28.md`. Estás en la rama `claude/ola-2-monetizacion`. Seguí paso a paso con el próximo feature de la Ola 2."**

---

## 1. Proyecto
- **LaCanchita**: SaaS multi-tenant de reservas de canchas. PHP puro + mysqli, sin frameworks/ORM, sesiones nativas, PWA.
- **Perfiles**: 1=SuperAdmin, 2=Dueño, 3=Encargado, 4=Empleado, 5=Cliente.
- Reglas base del código: ver `CLAUDE.md` en la raíz (columnas SQL en MAYÚSCULAS, escapar inputs, APIs devuelven JSON `{ok,msg,data}`, no usar `strftime`, ojo con `tenancy.php` que hace `session_write_close()` al incluirse).

## 2. Ramas y PRs (estructura apilada)
```
master (viejo)
 └─ claude/session-context-u2ymzs   (rama de desarrollo, base)
     └─ claude/roadmap-mejoras       → PR #4 (Ola 1)  [abierto]
         └─ claude/ola-2-monetizacion → PR #5 (Ola 2) [abierto, rama ACTUAL]
```
- Orden de merge: **PR #4 primero, después PR #5**.
- Todo está commiteado y pusheado. Seguí trabajando en `claude/ola-2-monetizacion`.

## 3. Reglas de trabajo (seguí estas)
- **Commits atómicos**, un feature/fix por commit, mensaje claro en español. Footer obligatorio:
  ```
  Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>
  Claude-Session: https://claude.ai/code/session_01Re5JReb2mRfGkCcaa8Uyji
  ```
- **Migraciones**: cada cambio de esquema = un `.sql` versionado en `/sql` **Y** `CREATE/ALTER ... IF NOT EXISTS` inline en la API como respaldo de dev.
- **PR por ola** (apiladas). Al agregar features a la Ola 2, actualizar título/cuerpo del PR #5.
- **Testear siempre** antes de commitear: `php -l` en cada archivo PHP tocado + prueba funcional real (ver sección 6). Para JS grande, `node --check` sobre el bloque.
- **Nunca commitear credenciales**: `config/vapid.php`, `config/mail.php`, `config/db.php` (gitignoreados). `config/dist/img/predios/` también gitignoreado (fotos subidas).
- No pushear a `master` ni a otra rama sin permiso.

## 4. Infra transversal ya implementada (usala, no la re-inventes)
- **CSRF**: `tenancy.php` hace `csrf_require()` al incluirse → cualquier API que incluya tenancy valida CSRF en POST automáticamente. El front (`pwa_head.php`) tiene un wrapper global de `fetch` que inyecta el header `X-CSRF-Token`. En formularios clásicos usar `<?= csrf_field() ?>`. Helpers en `config/dist/script/php/csrf.php`.
- **Rate limiting**: `config/dist/script/php/ratelimit.php` → `rate_limit_ok($link,'clave',max,segundos)`. Ya aplicado en login/registro/reset.
- **Multi-tenant** (`tenancy.php`): `require_perfil(N)`, `tenant_complejo_ids()`, `tenant_where($ids,$col)`, `assert_complejo/assert_cancha/assert_franja`, `current_dueno_id()`, `admin_as_dueno_id()`.
- **Mora / solo-lectura**: `assert_tenant_activo($link)` corta con 402 si el dueño tiene suscripción vencida/cancelada. Ponelo en toda acción de ESCRITURA nueva. `complejo_recibe_reservas($link,$cid)` para el alta de reservas.
- **Notificaciones**: `reserva_notify.php` → `notificarReservaCreada($link,$rid,$tipo,$avisarStaff)`. Push: `push_notify.php` (`enviarPush`, `enviarPushReserva`). Email: `mailer.php` (`enviarEmailReserva`, `enviarEmailReset`). Ambos toleran config ausente (no rompen).

## 5. Decisiones de producto ya tomadas (respetalas)
- **Monetización**: suscripción mensual de los **dueños** (SaaS puro). NO comisión por reserva.
- **Pago del jugador**: **MercadoPago vía OAuth/marketplace** (cada dueño conecta SU cuenta; la plata de la seña va al dueño). **Seña opcional, configurable por predio.**
- **Morosidad**: modo **solo lectura** (puede ver, no escribir; sus predios no reciben reservas).
- **Mercado**: Argentina, arranca en La Plata y alrededores. **Multideporte.** Gestión de **socios** a futuro (los "tipos de plan" por predio son el cimiento).

## 6. Cómo correr y testear
MariaDB NO viene instalado en el entorno remoto → `apt-get update && apt-get install -y mariadb-server` (usar `dangerouslyDisableSandbox` en Bash). Luego `service mariadb start`.
```bash
mysql -uroot -e "DROP DATABASE IF EXISTS lacanchita_test; CREATE DATABASE lacanchita_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -uroot --default-character-set=utf8mb4 lacanchita_test < tests/schema_test.sql
for f in sql/*.sql; do mysql -uroot --default-character-set=utf8mb4 lacanchita_test < "$f"; done
mysql -uroot --default-character-set=utf8mb4 lacanchita_test < tests/seed_test.sql
DB_NAME=lacanchita_test php -S 127.0.0.1:8088 -t . &   # conn.php lee DB_NAME del entorno
bash tests/suite.sh                                     # esperar TOTAL: PASS=62 FAIL=0
```
- Usuarios seed (password `test1234`): `sa@test.com`, `dueno@test.com`, `enc@test.com`, `emp@test.com`, `cliente@test.com`, `dueno2@test.com` (moroso).
- Para endpoints autenticados por HTTP: logueá con `api/login_ajax.php`, sacá el token con `grep -oP 'CSRF_TOKEN\s*=\s*"\K[a-f0-9]{64}'` de `index.php`, y mandalo en el header `X-CSRF-Token`.
- ⚠️ `conn.php` tiene `error_reporting(0)`: un fatal en runtime **trunca la página en silencio** (sin mensaje). Si un render sale cortado, mirá el log del `php -S` para ver el stack trace.

## 7. Estado
- **Ola 1 (PR #4)** — hecho: fixes críticos, seguridad (CSRF, rate-limit, credenciales fuera del código, sesión), enforcement de mora, recuperación de contraseña, notificaciones al crear reserva, disponibilidad real del buscador. Suite e2e **62/62**. Detalle: `docs/AUDITORIA-2026-06-28.md` (roadmap en 3 olas).
- **Ola 2 (PR #5)** — hecho:
  - **Cierre de caja / arqueo** (`api/caja.php`, `sql/cierre_caja.sql`, UI en vista Cobros del Dashboard).
  - **Fotos de predio** (`api/fotos.php`, `sql/complejo_foto.sql`, modal en Dashboard, galería en `predio.php`, portada en el buscador de `index.php`).

## 8. Próximos pasos (Ola 2) — pendientes
Recomendado seguir por el **más autónomo**:
1. 🟢 **Recordatorios de turno** (anti no-show) — cron + push/email T-24h y T-2h. Sin decisiones pendientes; solo necesitás poder configurar un cron en el hosting. Tabla sugerida `recordatorio_turno` + script `cron/recordatorios.php` + función `enviarRecordatorioTurno`. Encararlo primero.
2. 🟠 **Permisos granulares empleado vs encargado** — NECESITA decisión del dueño: qué puede hacer un empleado (4) que un encargado (3) no (ej: cobrar/confirmar sí, cancelar/reportes no). Preguntar antes de implementar.
3. 🟠 **MercadoPago (OAuth)** — el flagship (XL). Se puede construir el flujo (conectar cuenta del dueño, generar preferencia de pago de la seña, webhook, persistir en `pago`), pero para **probarlo** el dueño debe registrar la app en Mercado Pago y dar client_id/secret. Dejar armado + documentado si no hay credenciales.
4. Otros (auditoría): CRM + lista negra de clientes, pricing dinámico (pico/finde/feriado), reembolsos/reverso de pago, reseñas, check-in / no-show, recordatorios de cobro con cron real.

## 9. Notas de entorno
- El contenedor es efímero y **se reinicia** (perdés MariaDB y paquetes apt); reinstalá/relevantá si hace falta.
- Hay un `docs/AUDITORIA-2026-06-28.md` con el análisis por perfil y el roadmap completo — leelo para prioridades.

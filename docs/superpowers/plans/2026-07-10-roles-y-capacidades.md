# Modelo de Roles y Capacidades — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar capacidades fijas por rol (`can()`/`require_cap()`), separar encargado (3) de empleado (4) en backend + UI + ruteo, y auditar acciones sensibles.

**Architecture:** Un helper central `capabilities.php` define la matriz perfil→capacidades y las guardas. Las APIs del panel agregan `require_cap()` después de `require_perfil()` (defensa en profundidad). El ruteo post-login se unifica en `panel_url_para()`: empleado→PanelEncargado, encargado→Dashboard (que hoy bloqueaba a 3 vía `require_perfil(2)` en sus APIs y dejaba entrar a 4 en la vista — ambas cosas se corrigen). Tabla `auditoria` registra quién hizo qué.

**Tech Stack:** PHP puro + mysqli (sin frameworks), MariaDB, suite e2e bash (`tests/suite.sh`).

**Spec:** `docs/superpowers/specs/2026-07-09-roles-y-capacidades-design.md`

---

## Contexto imprescindible para el ejecutor

- **Convenciones del repo:** ver `CLAUDE.md`. APIs devuelven JSON `{ok,msg,data}`. Columnas SQL en MAYÚSCULAS. Escapar inputs. `tenancy.php` hace `session_write_close()` al incluirse (leer `$_SESSION` sigue OK; escribir requiere `session_start()` de nuevo).
- **Guardas existentes** (en `config/dist/script/php/tenancy.php`): `require_perfil($max)` permite perfil ≤ `$max` (1=más privilegio). `tenancy_deny($msg,$code)` responde JSON y corta. `assert_tenant_activo($link)` corta 402 por mora. `admin_as_dueno_id()` = modo soporte SA. `current_dueno_id($link)` = dueño del staff.
- **Estado actual de guardas por API** (verificado):
  - `reportes.php`, `export_reportes.php`, `cierres.php`, `canchas.php`, `horarios.php`, `fotos.php`, `turnos_fijos.php`, `complejos.php` → `require_perfil(2)` (encargado hoy NO accede).
  - `planes.php` → `require_perfil(3)`. `caja.php` → `require_perfil(4)`. `reservas.php` → `require_perfil(4)` por acción.
  - `usuarios.php` → `require_perfil(4)` solo para `crear_cliente_rapido`/`buscar_clientes`; el resto `require_perfil(2)`.
- **Ruteo actual:** `procesar_login.php:60-66` manda 3 y 4 al PanelEncargado; `auth_view.php:28-30` ídem; `Dashboard.php:6-12` deja entrar a cualquier 1–4.
- **Commits:** atómicos, mensaje en español, footer `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`.

### Cómo correr los tests (setup una vez por sesión)

```bash
cd /c/xampp/htdocs/LaCanchita
export PATH="$PATH:/c/xampp/php:/c/xampp/mysql/bin"
mysql -uroot -e "DROP DATABASE IF EXISTS lacanchita_test; CREATE DATABASE lacanchita_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -uroot --default-character-set=utf8mb4 lacanchita_test < tests/schema_test.sql
for f in sql/*.sql; do mysql -uroot --default-character-set=utf8mb4 lacanchita_test < "$f"; done
mysql -uroot --default-character-set=utf8mb4 lacanchita_test < tests/seed_test.sql
DB_NAME=lacanchita_test php -S 127.0.0.1:8088 -t . >/tmp/php8088.log 2>&1 &
DB_NAME=lacanchita_test bash tests/suite.sh 2>&1 | sed -n '/ROLES/,$p'   # ver solo la sección nueva
```

⚠️ **En esta máquina Windows falta `vendor/minishlink/web-push`** (`composer install` lo instala en deploy). Consecuencia: cualquier endpoint que incluya `reserva_notify.php` (`reservas.php` del admin, `reservar_publico.php`) da fatal silencioso local. Los tests nuevos sobre `reportes/canchas/usuarios/caja/etc.` **sí corren local**; los de `reservas.php` quedan escritos y se verifican en el entorno completo. El criterio de "verde" local es: **todos los checks de la sección `ROLES / CAPACIDADES` pasan salvo los marcados `[FULL-ENV]`**.

---

## File Structure

| Archivo | Acción | Responsabilidad |
|---|---|---|
| `config/dist/script/php/capabilities.php` | **Crear** | Matriz de capacidades, `can()`, `require_cap()`, `perfil_efectivo()`, `panel_url_para()`, `registrar_evento()` |
| `sql/auditoria.sql` | **Crear** | Migración de la tabla `auditoria` |
| `view/maquetaAdmin/api/reportes.php`, `export_reportes.php`, `cierres.php` | Modificar | `require_perfil(3)` + `require_cap('reportes.ver')` |
| `view/maquetaAdmin/api/canchas.php`, `horarios.php`, `planes.php`, `fotos.php`, `turnos_fijos.php`, `complejos.php` | Modificar | `require_perfil(3)` + `require_cap('config.canchas')` en escrituras |
| `view/maquetaAdmin/api/reservas.php`, `caja.php` | Modificar | caps operativas + auditoría |
| `view/maquetaAdmin/api/usuarios.php` | Modificar | staff por capacidad según perfil objetivo + auditoría |
| `procesar_login.php`, `config/dist/script/php/auth_view.php`, `view/maquetaAdmin/Dashboard.php` | Modificar | ruteo por superficie + gate + sidebar `can()` |
| `tests/suite.sh` | Modificar | sección `ROLES / CAPACIDADES` |
| `CLAUDE.md`, `HISTORIAL.md` | Modificar | doc de la convención + registro |

---

### Task 1: Helper central `capabilities.php` + migración de auditoría

**Files:**
- Create: `config/dist/script/php/capabilities.php`
- Create: `sql/auditoria.sql`

- [ ] **Step 1: Crear `config/dist/script/php/capabilities.php`** con este contenido exacto:

```php
<?php
/**
 * capabilities.php — Capacidades fijas por rol (autorización por acción).
 *
 * Complementa a require_perfil() (jerárquico): las capacidades distinguen QUÉ
 * puede hacer cada rol, en particular encargado (3) vs empleado (4).
 * Fuente de verdad única: CAPS_POR_PERFIL. Sin tabla de permisos por usuario.
 *
 * Incluir DESPUÉS de conn.php (y de tenancy.php en APIs). No abre la sesión:
 * asume que el caller ya hizo session_start() (todas las APIs lo hacen).
 *
 * Spec: docs/superpowers/specs/2026-07-09-roles-y-capacidades-design.md
 */

const CAPS_POR_PERFIL = [
    1 => ['saas.gestion'],   // SA "pelado"; en modo soporte hereda las del dueño (perfil_efectivo)
    2 => ['reserva.crear','reserva.confirmar','reserva.cancelar','pago.registrar',
          'caja.cerrar','reportes.ver','config.canchas','staff.empleados',
          'staff.encargados','billing.suscripcion'],
    3 => ['reserva.crear','reserva.confirmar','reserva.cancelar','pago.registrar',
          'caja.cerrar','reportes.ver','config.canchas','staff.empleados'],
    4 => ['reserva.crear','reserva.confirmar','reserva.cancelar','pago.registrar',
          'caja.cerrar'],
    5 => [],
];

/** Perfil real de la sesión; si el SA está en modo soporte, opera como dueño (2). */
function perfil_efectivo(): int {
    $p = (int)($_SESSION['usuario_perfil'] ?? 0);
    if ($p === 1 && !empty($_SESSION['admin_as_dueno'])) return 2;
    return $p;
}

function can(string $cap): bool {
    return in_array($cap, CAPS_POR_PERFIL[perfil_efectivo()] ?? [], true);
}

/** Guard de API: 403 JSON si el perfil efectivo no tiene la capacidad. */
function require_cap(string $cap): void {
    if (can($cap)) return;
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No tenés permisos para esta acción.']);
    exit;
}

/**
 * Destino post-login según perfil. ÚNICA fuente de verdad del ruteo
 * (la usan procesar_login.php y auth_view.php). Ruta relativa a la raíz.
 */
function panel_url_para(int $perfil): string {
    if ($perfil === 5) return 'view/maquetaCliente/LaCanchitaCliente.php';
    if ($perfil === 4) return 'view/maquetaEncargado/PanelEncargado.php';
    return 'view/maquetaAdmin/Dashboard.php';   // 1, 2 y 3
}

/**
 * Auditoría best-effort de acciones sensibles: nunca rompe la acción principal.
 * Registra el usuario real; si el SA está en modo soporte, ACTUA_COMO = dueño asistido.
 */
function registrar_evento($link, string $cap, string $detalle = ''): void {
    try {
        // Respaldo de dev (la migración versionada es sql/auditoria.sql)
        @mysqli_query($link,
            "CREATE TABLE IF NOT EXISTS auditoria (
                AUD_ID       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                USUARIOS_ID  INT UNSIGNED NOT NULL,
                ACTUA_COMO   INT UNSIGNED NULL,
                CAP          VARCHAR(40) NOT NULL,
                DETALLE      VARCHAR(255) NULL,
                IP           VARCHAR(45) NULL,
                CREATED_AT   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_usuario (USUARIOS_ID),
                INDEX idx_cap (CAP),
                INDEX idx_fecha (CREATED_AT)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $uid  = (int)($_SESSION['usuario_id'] ?? 0);
        if ($uid <= 0) return;
        $como = ((int)($_SESSION['usuario_perfil'] ?? 0) === 1 && !empty($_SESSION['admin_as_dueno']))
              ? (int)$_SESSION['admin_as_dueno'] : null;
        $capE = mysqli_real_escape_string($link, $cap);
        $detE = mysqli_real_escape_string($link, mb_substr($detalle, 0, 255));
        $ipE  = mysqli_real_escape_string($link, $_SERVER['REMOTE_ADDR'] ?? '');
        $comoSql = $como === null ? 'NULL' : (string)$como;
        @mysqli_query($link,
            "INSERT INTO auditoria (USUARIOS_ID, ACTUA_COMO, CAP, DETALLE, IP)
             VALUES ($uid, $comoSql, '$capE', '$detE', '$ipE')");
    } catch (\Throwable $e) { /* best-effort */ }
}
```

- [ ] **Step 2: Crear `sql/auditoria.sql`:**

```sql
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
```

- [ ] **Step 3: Verificar sintaxis y carga de la migración**

```bash
php -l config/dist/script/php/capabilities.php
mysql -uroot --default-character-set=utf8mb4 lacanchita_test < sql/auditoria.sql
mysql -uroot lacanchita_test -e "SHOW TABLES LIKE 'auditoria'"
```
Expected: `No syntax errors` y la tabla listada.

- [ ] **Step 4: Smoke test del helper por CLI** (simula sesiones; verifica matriz y ruteo):

```bash
php -r '
$_SESSION = ["usuario_perfil" => 4];
require "config/dist/script/php/capabilities.php";
assert(can("caja.cerrar") === true);
assert(can("reportes.ver") === false);
$_SESSION["usuario_perfil"] = 3;
assert(can("reportes.ver") === true);
assert(can("staff.encargados") === false);
$_SESSION = ["usuario_perfil" => 1, "admin_as_dueno" => 2];
assert(perfil_efectivo() === 2 && can("billing.suscripcion") === true);
assert(panel_url_para(4) === "view/maquetaEncargado/PanelEncargado.php");
assert(panel_url_para(3) === "view/maquetaAdmin/Dashboard.php");
echo "CAPS OK\n";'
```
Expected: `CAPS OK`

- [ ] **Step 5: Commit**

```bash
git add config/dist/script/php/capabilities.php sql/auditoria.sql
git commit -m "feat(roles): helper central de capacidades por rol + tabla de auditoria" \
  -m "can()/require_cap() con matriz fija por perfil, perfil_efectivo() resuelve el modo soporte del SA, panel_url_para() unifica el ruteo post-login, registrar_evento() audita best-effort." \
  -m "Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: Enforcement de reportes (`reportes.ver`)

**Files:**
- Modify: `view/maquetaAdmin/api/reportes.php:5-6`, `export_reportes.php:4-5`, `cierres.php:5-7`
- Test: `tests/suite.sh`

- [ ] **Step 1: Escribir los tests que fallan.** En `tests/suite.sh`, ANTES de la línea final `echo ""; echo "════..."; echo "TOTAL: ..."`, insertar:

```bash
echo "══ ROLES / CAPACIDADES ══"
# Empleado (perfil 4) NO ve reportes; encargado (3) SÍ
ck "empleado reportes 403" "$(curl -s -b $J/emp.jar "$B/view/maquetaAdmin/api/reportes.php?action=resumen")" 'permisos'
ck "empleado export_reportes 403" "$(curl -s -b $J/emp.jar "$B/view/maquetaAdmin/api/export_reportes.php")" 'permisos'
ck "empleado cierres 403" "$(curl -s -b $J/emp.jar "$B/view/maquetaAdmin/api/cierres.php?action=listar")" 'permisos'
ck "encargado reportes ok" "$(curl -s -b $J/enc.jar "$B/view/maquetaAdmin/api/reportes.php?action=resumen")" '"ok":true'
```
> Nota: si `action=resumen` no existe en `reportes.php`, abrir el archivo, tomar la primera acción GET real del `switch` y usarla en ambos checks (el objetivo es el guard, no la acción).

- [ ] **Step 2: Correr y verificar que fallan** (hoy `require_perfil(2)` da "permisos" para AMBOS: el check del encargado debe FALLAR):

```bash
DB_NAME=lacanchita_test bash tests/suite.sh 2>&1 | sed -n '/ROLES/,$p'
```
Expected: `PASS` los 3 de empleado, `FAIL: encargado reportes ok` (encargado aún bloqueado).

- [ ] **Step 3: Implementar.** En `reportes.php`, `export_reportes.php` y `cierres.php`, reemplazar la línea `require_perfil(2);` por:

```php
require_once '../../../config/dist/script/php/capabilities.php';
require_perfil(3);              // dueño, encargado (y SA)
require_cap('reportes.ver');    // corta al empleado (4)
```

- [ ] **Step 4: Verificar sintaxis y que los 4 checks pasan**

```bash
php -l view/maquetaAdmin/api/reportes.php && php -l view/maquetaAdmin/api/export_reportes.php && php -l view/maquetaAdmin/api/cierres.php
DB_NAME=lacanchita_test bash tests/suite.sh 2>&1 | sed -n '/ROLES/,$p'
```
Expected: los 4 checks `PASS`.

- [ ] **Step 5: Commit**

```bash
git add view/maquetaAdmin/api/reportes.php view/maquetaAdmin/api/export_reportes.php view/maquetaAdmin/api/cierres.php tests/suite.sh
git commit -m "feat(roles): reportes visibles para encargado, vedados al empleado (reportes.ver)" \
  -m "Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: Enforcement de configuración (`config.canchas`)

**Files:**
- Modify: `view/maquetaAdmin/api/canchas.php:8`, `horarios.php:7`, `planes.php:7`, `fotos.php:7`, `turnos_fijos.php:7`, `complejos.php:8`
- Test: `tests/suite.sh`

- [ ] **Step 1: Tests que fallan.** Agregar a la sección `ROLES / CAPACIDADES` de `tests/suite.sh`:

```bash
# Config de canchas/horarios/planes/fotos/turnos/complejos: encargado SÍ, empleado NO
ck "empleado crear cancha 403" "$(fpost $J/emp.jar $TM view/maquetaAdmin/api/canchas.php 'action=crear&complejo_id=1&nombre=X&tipo_cancha_id=1')" 'permisos'
ck "empleado crear horario 403" "$(fpost $J/emp.jar $TM view/maquetaAdmin/api/horarios.php 'action=crear&cancha_id=1&hora_inicio=20:00&hora_fin=21:00&precio=1')" 'permisos'
ck "empleado crear plan 403" "$(fpost $J/emp.jar $TM view/maquetaAdmin/api/planes.php 'action=crear&complejo_id=1&nombre=X&precio=1&periodo=mensual')" 'permisos'
ck "empleado tocar fotos 403" "$(fpost $J/emp.jar $TM view/maquetaAdmin/api/fotos.php 'action=eliminar&foto_id=1')" 'permisos'
ck "empleado turno fijo 403" "$(fpost $J/emp.jar $TM view/maquetaAdmin/api/turnos_fijos.php 'action=crear&cancha_id=1')" 'permisos'
ck "empleado editar complejo 403" "$(fpost $J/emp.jar $TM view/maquetaAdmin/api/complejos.php 'action=editar&id=1&nombre=X')" 'permisos'
R=$(fpost $J/enc.jar $TE view/maquetaAdmin/api/canchas.php 'action=crear&complejo_id=1&nombre=Cancha Enc&tipo_cancha_id=1')
ck "encargado crear cancha ok" "$R" '"ok":true'
ck "encargado listar canchas ok" "$(curl -s -b $J/enc.jar "$B/view/maquetaAdmin/api/canchas.php?action=listar")" '"ok":true'
```

- [ ] **Step 2: Correr; deben fallar los 2 del encargado** (hoy bloqueado por `require_perfil(2)`; `planes.php` ya permite 3, ese check del empleado también debería fallar hoy porque `require_perfil(3)` corta al 4 con el mismo mensaje — verificar el output real y anotar).

```bash
DB_NAME=lacanchita_test bash tests/suite.sh 2>&1 | sed -n '/ROLES/,$p'
```

- [ ] **Step 3: Implementar.** En `canchas.php`, `horarios.php`, `fotos.php`, `turnos_fijos.php`, `complejos.php`: reemplazar `require_perfil(2);` por:

```php
require_once '../../../config/dist/script/php/capabilities.php';
require_perfil(3);              // dueño, encargado (y SA)
require_cap('config.canchas');  // corta al empleado (4)
```

En `planes.php`: mantener `require_perfil(3);` y agregar debajo:

```php
require_once '../../../config/dist/script/php/capabilities.php';
require_cap('config.canchas');  // corta al empleado (4)
```

**No tocar** las líneas `assert_tenant_activo(...)` existentes (mora): el orden queda perfil → cap → mora, como define el spec.

Además, auditoría de config (spec §8): en `canchas.php`, dentro de los cases `crear`, `editar` y `toggle`, tras la query exitosa, agregar:

```php
registrar_evento($link, 'config.canchas', "cancha: $action");
```
(una línea por case; usar la variable `$action` que ya existe). En `horarios.php`, ídem en sus cases de escritura con detalle `"horario: $action"`.

> **Cubiertos sin cambios** (dejar como están): `admin_context.php` ya tiene `require_perfil(1)`, que equivale exactamente a `saas.gestion`; `onboarding_completo.php` mantiene `require_perfil(2)` (flujo exclusivo del dueño nuevo). El spec los inventaría como cubiertos por esas guardas.

- [ ] **Step 4: Sintaxis + tests en verde**

```bash
for f in canchas horarios planes fotos turnos_fijos complejos; do php -l view/maquetaAdmin/api/$f.php; done
DB_NAME=lacanchita_test bash tests/suite.sh 2>&1 | sed -n '/ROLES/,$p'
```
Expected: los 8 checks nuevos `PASS`.

- [ ] **Step 5: Commit**

```bash
git add view/maquetaAdmin/api/canchas.php view/maquetaAdmin/api/horarios.php view/maquetaAdmin/api/planes.php view/maquetaAdmin/api/fotos.php view/maquetaAdmin/api/turnos_fijos.php view/maquetaAdmin/api/complejos.php tests/suite.sh
git commit -m "feat(roles): configuracion de canchas/horarios/planes para encargado, vedada al empleado (config.canchas)" \
  -m "Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 4: Caps operativas + auditoría en `reservas.php` y `caja.php`

**Files:**
- Modify: `view/maquetaAdmin/api/reservas.php` (líneas 5-7 y cases `crear_admin:361`, `confirmar:627`, `rechazar:686`, `cancelar:486`, `registrar_pago:735`)
- Modify: `view/maquetaAdmin/api/caja.php:5-7` (y su acción de escritura)
- Test: `tests/suite.sh`

- [ ] **Step 1: Tests.** Agregar a la sección (los de `reservas.php` marcarlos con comentario `[FULL-ENV]` — local dan fatal por vendor):

```bash
# [FULL-ENV] Operativas: el empleado SÍ puede (confirmar, cobrar, cancelar)
PAS=$(date -d "+2 day" +%Y-%m-%d)
R=$(fpost $J/emp.jar $TM view/maquetaAdmin/api/reservas.php "action=crear_admin&cancha_id=1&fecha=$PAS&franja_id=1&cliente_id=5")
ck "[FULL-ENV] empleado crea reserva ok" "$R" '"ok":true'
RIDE=$(echo "$R" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo $d["data"]["RESERVA_ID"]??0;')
ck "[FULL-ENV] empleado confirma ok" "$(fpost $J/emp.jar $TM view/maquetaAdmin/api/reservas.php "action=confirmar&reserva_id=$RIDE")" '"ok":true'
ck "[FULL-ENV] empleado cancela confirmada ok" "$(fpost $J/emp.jar $TM view/maquetaAdmin/api/reservas.php "action=cancelar&reserva_id=$RIDE")" '"ok":true'
AUDC=$(mysql -uroot "$DB" -N -e "SELECT COUNT(*) FROM auditoria WHERE CAP='reserva.cancelar' AND USUARIOS_ID=4")
if [ "${AUDC:-0}" -ge 1 ]; then PASS=$((PASS+1)); echo "PASS: auditoria de cancelacion registrada"; else FAIL=$((FAIL+1)); echo "FAIL: sin fila de auditoria reserva.cancelar"; fi
# Caja: empleado puede cerrar; queda auditado (corre local)
ck "empleado caja listar ok" "$(curl -s -b $J/emp.jar "$B/view/maquetaAdmin/api/caja.php?action=resumen&complejo_id=1&fecha=$(date +%Y-%m-%d)")" '"ok"'
```
> Si `action=resumen` no existe en `caja.php`, usar la primera acción GET real de su `switch`.

- [ ] **Step 2: Implementar en `reservas.php`.** (a) Tras la línea 5 (`require_once .../tenancy.php`) agregar:

```php
require_once '../../../config/dist/script/php/capabilities.php';
```

(b) En cada case, DESPUÉS del `require_perfil(4);` existente, agregar el `require_cap`:
- `case 'crear_admin':` → `require_cap('reserva.crear');`
- `case 'confirmar':` → `require_cap('reserva.confirmar');`
- `case 'rechazar':` y `case 'cancelar':` → `require_cap('reserva.cancelar');` (`cancelar` en línea 486 no tiene `require_perfil` propio — dejarlo así: es multi-perfil, cliente cancela lo suyo; agregar el cap SOLO si la sesión es de staff/dueño/SA, envuelto así:)

```php
if ((int)($_SESSION['usuario_perfil'] ?? 5) <= 4) require_cap('reserva.cancelar');
```
- `case 'registrar_pago':` → `require_cap('pago.registrar');`

(c) Auditoría: dentro del case `cancelar`, justo después del UPDATE exitoso que marca la reserva cancelada (buscar el `mysqli_query` con `RESERVA_ESTADO='cancelada'`), agregar:

```php
registrar_evento($link, 'reserva.cancelar', 'reserva #' . (int)$reserva_id . ' cancelada desde panel');
```
(usar la variable de id que ese case ya usa; si se llama distinto a `$reserva_id`, adaptar).

- [ ] **Step 3: Implementar en `caja.php`.** Tras `require_perfil(4);` agregar:

```php
require_once '../../../config/dist/script/php/capabilities.php';
require_cap('caja.cerrar');
```
Y en la acción de escritura (la que hace `INSERT INTO cierre_caja` — localizarla con `grep -n 'INSERT INTO cierre_caja' view/maquetaAdmin/api/caja.php`), tras el insert exitoso:

```php
registrar_evento($link, 'caja.cerrar', 'cierre de caja complejo #' . (int)$complejo_id);
```

- [ ] **Step 4: Sintaxis + tests**

```bash
php -l view/maquetaAdmin/api/reservas.php && php -l view/maquetaAdmin/api/caja.php
DB_NAME=lacanchita_test bash tests/suite.sh 2>&1 | sed -n '/ROLES/,$p'
```
Expected local: `empleado caja listar ok` PASS; los `[FULL-ENV]` pueden fallar local (anotar y verificar en deploy).

- [ ] **Step 5: Commit**

```bash
git add view/maquetaAdmin/api/reservas.php view/maquetaAdmin/api/caja.php tests/suite.sh
git commit -m "feat(roles): caps operativas en reservas y caja + auditoria de cancelaciones y cierres" \
  -m "Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 5: Gestión de staff por capacidad (`usuarios.php`)

**Files:**
- Modify: `view/maquetaAdmin/api/usuarios.php` (líneas 11-12, y cases `crear_staff:76`, `editar:154`, `toggle:188`, `asignar_canchas:203`, `listar_staff:23`, `canchas_asignables:227`)
- Test: `tests/suite.sh`

- [ ] **Step 1: Tests.** Agregar a la sección:

```bash
# Staff: encargado gestiona EMPLEADOS pero no encargados; empleado no gestiona nada
ck "empleado crear staff 403" "$(fpost $J/emp.jar $TM view/maquetaAdmin/api/usuarios.php 'action=crear_staff&nombre=X&apellido=Y&dni=30000001&email=x1@test.com&telefono=1&perfil_id=4&password=test1234')" 'permisos'
ck "encargado crea EMPLEADO ok" "$(fpost $J/enc.jar $TE view/maquetaAdmin/api/usuarios.php 'action=crear_staff&nombre=Emple&apellido=Nuevo&dni=30000002&email=empnuevo@test.com&telefono=1&perfil_id=4&password=test1234')" '"ok":true'
ck "encargado crea ENCARGADO 403" "$(fpost $J/enc.jar $TE view/maquetaAdmin/api/usuarios.php 'action=crear_staff&nombre=Enc&apellido=Nuevo&dni=30000003&email=encnuevo@test.com&telefono=1&perfil_id=3&password=test1234')" 'permisos'
ck "encargado lista staff ok" "$(curl -s -b $J/enc.jar "$B/view/maquetaAdmin/api/usuarios.php?action=listar_staff")" '"ok":true'
ck "dueno crea ENCARGADO ok" "$(fpost $J/due.jar $TD view/maquetaAdmin/api/usuarios.php 'action=crear_staff&nombre=Enc2&apellido=Due&dni=30000004&email=enc2@test.com&telefono=1&perfil_id=3&password=test1234')" '"ok":true'
AUDS=$(mysql -uroot "$DB" -N -e "SELECT COUNT(*) FROM auditoria WHERE CAP LIKE 'staff.%'")
if [ "${AUDS:-0}" -ge 2 ]; then PASS=$((PASS+1)); echo "PASS: auditoria de staff registrada"; else FAIL=$((FAIL+1)); echo "FAIL: auditoria staff insuficiente ($AUDS)"; fi
```

- [ ] **Step 2: Correr; los del encargado deben FALLAR hoy** (todo staff requiere perfil ≤2).

- [ ] **Step 3: Implementar.** (a) Reemplazar las líneas 11-12 de `usuarios.php`:

```php
$STAFF_ACTIONS = ['crear_cliente_rapido', 'buscar_clientes'];
require_perfil(in_array($action, $STAFF_ACTIONS, true) ? 4 : 2);
```
por:

```php
require_once '../../../config/dist/script/php/capabilities.php';
$STAFF_ACTIONS = ['crear_cliente_rapido', 'buscar_clientes'];                    // operativas: todo el staff
$ENC_ACTIONS   = ['listar_staff','crear_staff','editar','toggle',
                  'asignar_canchas','canchas_asignables'];                        // gestión de equipo: encargado+
require_perfil(in_array($action, $STAFF_ACTIONS, true) ? 4
             : (in_array($action, $ENC_ACTIONS, true) ? 3 : 2));
if (in_array($action, $ENC_ACTIONS, true)) require_cap('staff.empleados');       // corta al empleado (4)

/** Cap según el perfil objetivo: gestionar un encargado exige staff.encargados. */
function require_cap_para_perfil(int $perfilObjetivo): void {
    if ($perfilObjetivo === 3)      require_cap('staff.encargados');
    elseif ($perfilObjetivo === 4)  require_cap('staff.empleados');
    // perfil 5 (cliente rápido) no requiere cap de staff
}
```

(b) En `case 'crear_staff':` después de que `$perfilId` queda validado (tras la línea 90, el check de `$perfilesPermitidos`), agregar:

```php
require_cap_para_perfil($perfilId);
```
Y en la resolución de `$duenoId` (línea 95), contemplar al encargado (su dueño es `current_dueno_id`):

```php
$duenoId = is_dueno() ? current_uid()
         : (is_staff() ? current_dueno_id($link)
         : ((int)($_POST['dueno_id'] ?? 0) ?: null));
```
Tras el INSERT exitoso del staff nuevo, agregar auditoría:

```php
registrar_evento($link, $perfilId === 3 ? 'staff.encargados' : 'staff.empleados',
    "alta staff #" . mysqli_insert_id($link) . " (perfil $perfilId)");
```

(c) En `case 'editar':` y `case 'toggle':` ya se fetchea `$target` con `PERFIL_ID` y `DUENO_ID`. Después de ese fetch (y de su check de existencia), agregar:

```php
require_cap_para_perfil((int)$target['PERFIL_ID']);
```
Y **reemplazar** el check de pertenencia `if (!is_superadmin() && (int)$target['DUENO_ID']!==current_uid()) resp(false,'Sin permisos.');` por uno que contemple al encargado (compara contra el dueño efectivo):

```php
$duenoEfectivo = is_dueno() ? current_uid() : current_dueno_id($link);
if (!is_superadmin() && (int)$target['DUENO_ID'] !== (int)$duenoEfectivo) resp(false,'Sin permisos.');
```
En `toggle`, tras el UPDATE exitoso: `registrar_evento($link, (int)$target['PERFIL_ID']===3?'staff.encargados':'staff.empleados', "toggle staff #$id");`

(d) En `case 'asignar_canchas':` aplicar el mismo patrón que (c): fetch del perfil del objetivo si no está, `require_cap_para_perfil`, y pertenencia por dueño efectivo. `canchas_asignables` y `listar_staff` quedan cubiertos por el `require_cap('staff.empleados')` global de (a); en `listar_staff` verificar que el scope ya filtra por `DUENO_ID` del dueño efectivo (si filtra por `current_uid()`, cambiar a `$duenoEfectivo` como en (c)).

- [ ] **Step 4: Sintaxis + tests en verde**

```bash
php -l view/maquetaAdmin/api/usuarios.php
DB_NAME=lacanchita_test bash tests/suite.sh 2>&1 | sed -n '/ROLES/,$p'
```
Expected: los 6 checks de staff `PASS`.

- [ ] **Step 5: Commit**

```bash
git add view/maquetaAdmin/api/usuarios.php tests/suite.sh
git commit -m "feat(roles): encargado gestiona empleados (no encargados) con auditoria (staff.*)" \
  -m "Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 6: Ruteo por superficie (empleado→PanelEncargado, encargado→Dashboard)

**Files:**
- Modify: `procesar_login.php:60-66`
- Modify: `config/dist/script/php/auth_view.php:27-31`
- Modify: `view/maquetaAdmin/Dashboard.php:6-12`
- Test: `tests/suite.sh`

- [ ] **Step 1: Tests.** Agregar a la sección:

```bash
# Ruteo por superficie: empleado NO entra al Dashboard (302), encargado SÍ (200)
ck "empleado Dashboard redirigido" "$(curl -s -o /dev/null -w '%{http_code}' -b $J/emp.jar "$B/view/maquetaAdmin/Dashboard.php")" '302'
ck "encargado Dashboard entra" "$(curl -s -o /dev/null -w '%{http_code}' -b $J/enc.jar "$B/view/maquetaAdmin/Dashboard.php")" '200'
ck "empleado PanelEncargado entra" "$(curl -s -o /dev/null -w '%{http_code}' -b $J/emp.jar "$B/view/maquetaEncargado/PanelEncargado.php")" '200'
```

- [ ] **Step 2: Correr; `empleado Dashboard redirigido` debe FAIL** (hoy devuelve 200).

- [ ] **Step 3: Implementar.**

(a) `procesar_login.php`: reemplazar las líneas 60-66 (el bloque `// Redirigir según perfil` con sus `if/elseif`) por:

```php
// Redirigir según perfil (fuente de verdad: panel_url_para en capabilities.php)
require_once __DIR__ . '/config/dist/script/php/capabilities.php';
header('Location: ' . panel_url_para((int)$user['PERFIL_ID']));
```

(b) `auth_view.php`: reemplazar las líneas 27-31 (los tres redirects del final de `require_view`) por:

```php
    // Redirigir al panel que le corresponde según su perfil
    require_once __DIR__ . '/capabilities.php';
    header('Location: ../../' . panel_url_para($p));
```
> Ojo con la ruta: las vistas viven 2 niveles bajo la raíz, y `panel_url_para` devuelve ruta desde la raíz — por eso el prefijo `../../`.

(c) `Dashboard.php`: después del bloque de líneas 10-12 (redirect de clientes), agregar:

```php
if ((int)$_SESSION['usuario_perfil'] === 4) {
    // El empleado opera en su propio panel, no en el Dashboard
    header('Location: ../maquetaEncargado/PanelEncargado.php'); exit;
}
```

- [ ] **Step 4: Sintaxis + tests + smoke manual del form login**

```bash
php -l procesar_login.php && php -l config/dist/script/php/auth_view.php && php -l view/maquetaAdmin/Dashboard.php
DB_NAME=lacanchita_test bash tests/suite.sh 2>&1 | sed -n '/ROLES/,$p'
```
Expected: los 3 checks de ruteo `PASS`. Además verificar que el resto de la suite no se rompió (el login del form usa `procesar_login.php`).

- [ ] **Step 5: Commit**

```bash
git add procesar_login.php config/dist/script/php/auth_view.php view/maquetaAdmin/Dashboard.php tests/suite.sh
git commit -m "feat(roles): ruteo por superficie -- encargado al Dashboard, empleado al PanelEncargado" \
  -m "panel_url_para() unifica el destino post-login; el Dashboard ya no acepta perfil 4 por URL." \
  -m "Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 7: Sidebar del Dashboard por capacidades + objeto CAPS en JS

**Files:**
- Modify: `view/maquetaAdmin/Dashboard.php` (include en :3-4, sidebar `$perfil === 3 || $perfil === 4` en :1538-1561, select `mStaffPerfil` ~:6328)

- [ ] **Step 1: Incluir capabilities.** Tras la línea 4 (`require_once .../tenancy.php`) agregar:

```php
require_once '../../config/dist/script/php/capabilities.php';
```

- [ ] **Step 2: Sidebar del encargado.** Reemplazar TODO el bloque `<?php if($perfil === 3 || $perfil === 4): ?> ... <?php endif; ?>` (líneas 1538-1561) por (el empleado ya no entra; el encargado recibe el menú gated por `can()`):

```php
        <?php if($perfil === 3): ?>
        <!-- ── Encargado: opera y configura, sin billing ni encargados ── -->
        <?php if (can('config.canchas')): ?>
        <div class="sb-section">Mi negocio</div>
        <div class="sb-item" data-view="canchas" onclick="showView(this)">
            <i class="fas fa-futbol"></i> Canchas
        </div>
        <div class="sb-item" data-view="horarios" onclick="showView(this)">
            <i class="fas fa-clock"></i> Horarios y precios
        </div>
        <div class="sb-item" data-view="cierres" onclick="showView(this)">
            <i class="fas fa-ban"></i> Cierres
        </div>
        <div class="sb-item" data-view="turnos" onclick="showView(this)">
            <i class="fas fa-redo-alt"></i> Turnos fijos
        </div>
        <?php endif; ?>
        <div class="sb-section">Operaciones</div>
        <?php if (can('reportes.ver')): ?>
        <div class="sb-item" data-view="reportes" onclick="showView(this)">
            <i class="fas fa-chart-bar"></i> Reportes
        </div>
        <?php endif; ?>
        <div class="sb-item" data-view="agenda" onclick="showView(this)">
            <i class="fas fa-calendar-alt"></i> Agenda
        </div>
        <div class="sb-item" data-view="reservas" onclick="showView(this)">
            <i class="fas fa-calendar-check"></i> Reservas
        </div>
        <div class="sb-item" data-view="pagos" onclick="showView(this)">
            <i class="fas fa-dollar-sign"></i> Cobros
        </div>
        <?php if (can('config.canchas')): ?>
        <div class="sb-section">Plataforma</div>
        <div class="sb-item" data-view="planes" onclick="showView(this)">
            <i class="fas fa-tags"></i> Tipos de plan
        </div>
        <?php endif; ?>
        <?php if (can('staff.empleados')): ?>
        <div class="sb-section">Personas</div>
        <div class="sb-item" data-view="staff" onclick="showView(this)">
            <i class="fas fa-id-badge"></i> Mi Staff
        </div>
        <?php endif; ?>
        <div class="sb-section">Mi cuenta</div>
        <div class="sb-item" data-view="perfil" onclick="showView(this)">
            <i class="fas fa-user-circle"></i> Mi perfil
        </div>
        <?php endif; ?>
```

- [ ] **Step 3: Objeto CAPS para el JS.** Localizar el primer `<script>` del cuerpo principal (después del HTML del sidebar) y agregar al inicio:

```php
<script>
window.CAPS = <?= json_encode([
    'reportes.ver'      => can('reportes.ver'),
    'config.canchas'    => can('config.canchas'),
    'staff.empleados'   => can('staff.empleados'),
    'staff.encargados'  => can('staff.encargados'),
    'billing.suscripcion' => can('billing.suscripcion'),
]) ?>;
</script>
```

- [ ] **Step 4: Select de perfil en el modal de staff.** Buscar el `<select id="mStaffPerfil">` (cerca de la línea 6328 hay JS que lo setea; el markup está en el HTML del modal). Envolver la opción "Encargado" para que solo aparezca con la capacidad:

```php
<?php if (can('staff.encargados')): ?><option value="3">Encargado</option><?php endif; ?>
<option value="4">Empleado</option>
```
(adaptar al markup real de las options; el objetivo: el encargado no ve la opción de crear otros encargados).

- [ ] **Step 5: Sintaxis + smoke por HTTP**

```bash
php -l view/maquetaAdmin/Dashboard.php
DB_NAME=lacanchita_test bash tests/suite.sh 2>&1 | sed -n '/ROLES/,$p'
curl -s -b /tmp/enc_test.jar "$B/view/maquetaAdmin/Dashboard.php" | grep -c 'data-view="reportes"'
```
Expected: `php -l` limpio; la sección ROLES sigue verde; el Dashboard del encargado contiene el ítem reportes (y NO el de billing).
> Nota: `error_reporting(0)` en conn.php trunca la página en silencio ante un fatal — si el HTML sale cortado, mirar `/tmp/php8088.log`.

- [ ] **Step 6: Commit**

```bash
git add view/maquetaAdmin/Dashboard.php
git commit -m "feat(roles): sidebar del Dashboard gated por can() + objeto CAPS para el front" \
  -m "Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 8: Documentación, historial y push

**Files:**
- Modify: `CLAUDE.md` (sección "Archivos críticos" y "Git")
- Modify: `HISTORIAL.md`

- [ ] **Step 1: `CLAUDE.md`.** (a) En "Archivos críticos", bajo la línea de `tenancy.php`, agregar:

```
  capabilities.php  → capacidades por rol: can(), require_cap(), panel_url_para(), registrar_evento()
```
(b) En la sección "Git", corregir `Rama de desarrollo: claude/session-context-u2ymzs` → `Rama de desarrollo: claude/ola-2-monetizacion`.
(c) En "Roles y perfiles", actualizar la tabla de paneles: perfil 3 → `view/maquetaAdmin/Dashboard.php`, perfil 4 → `view/maquetaEncargado/PanelEncargado.php`.

- [ ] **Step 2: `HISTORIAL.md`.** Agregar arriba de todo:

```markdown
## 2026-07-10 — [CODE] — Modelo de roles y capacidades implementado
- `capabilities.php`: can()/require_cap() por rol; encargado ≠ empleado en backend, UI y ruteo.
- Encargado → Dashboard (reportes/config/staff-empleados); Empleado → PanelEncargado (solo operación).
- Auditoría de acciones sensibles en tabla `auditoria`. Tests deny-by-default en la suite.
```

- [ ] **Step 3: Correr la suite completa una última vez y anotar el resultado local** (sección ROLES verde salvo `[FULL-ENV]`).

- [ ] **Step 4: Commit + push**

```bash
git add CLAUDE.md HISTORIAL.md
git commit -m "docs: registra el modelo de roles en CLAUDE.md e HISTORIAL.md" \
  -m "Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
git push origin claude/ola-2-monetizacion
```

---

## Verificación final (criterio de done)

1. Sección `ROLES / CAPACIDADES` de la suite: **todos los checks PASS** localmente, excepto los marcados `[FULL-ENV]` (verificar en entorno con `composer install`).
2. El resto de la suite no empeoró respecto del baseline local (los fallos pre-existentes por vendor no cuentan).
3. Smoke manual (guía `docs/TESTING-POR-PERFIL.md`, secciones 2 y 3): login como `emp@test.com` → PanelEncargado; login como `enc@test.com` → Dashboard con reportes/config/staff visibles y sin billing.
4. `SELECT * FROM auditoria` muestra filas tras cancelar/cerrar caja/crear staff.

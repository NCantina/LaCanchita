# Modelo de roles y capacidades — Diseño

> Fecha: 2026-07-09 · Rama: `claude/ola-2-monetizacion` · Origen: [CODE]
> Estado: aprobado para pasar a plan de implementación.

## 1. Objetivo

Definir el **mapa completo de trabajo según perfil**: a dónde entra cada rol, qué ve
y qué puede tocar. Hoy los perfiles **encargado (3) y empleado (4) están tratados
idénticos** (`if ($perfil === 3 || $perfil === 4)` esparcido por el código), y no hay
una fuente única que diga qué puede hacer cada uno. Este diseño centraliza eso en un
modelo de **capacidades fijas por rol**, lo hace cumplir en el backend (seguridad real),
lo refleja en la UI, y agrega **auditoría** de las acciones sensibles.

## 2. Estado actual

- **Dos caminos de login:**
  - `procesar_login.php` (form, flujo real de usuario): guarda `usuario_perfil` y **redirige
    por perfil** — 5 → panel cliente; **3 y 4 → `PanelEncargado.php`**; 1 y 2 → Dashboard.
  - `api/login_ajax.php` (lo usan `index.php` y la suite): solo devuelve el perfil en JSON y
    delega el redirect al front. **Los dos criterios pueden divergir** → conviene unificar.
- **Ruteo real hoy:**
  - Cliente (5) → `view/maquetaCliente/LaCanchitaCliente.php`
  - **Empleado (4) y Encargado (3) → `view/maquetaEncargado/PanelEncargado.php`** (mobile-first:
    agenda del día + confirmar + cobrar; su API solo expone `listar`/`confirmar`/`registrar_pago`)
  - Dueño (2), SA (1) → `view/maquetaAdmin/Dashboard.php`
- **Huecos detectados:**
  - Encargado (3) y Empleado (4) hoy son **idénticos** (mismo panel, mismas 3 acciones). El
    encargado no tiene reportes/config/staff en ninguna superficie.
  - `Dashboard.php` (líneas 6–12) deja entrar a **cualquier perfil 1–4**: un empleado que
    escriba la URL a mano entra igual (over-exposición; hay bloques `$perfil === 3 || 4` vivos).
    El `Dashboard` **no** usa `require_view`; hace su propio chequeo inline laxo.
- **Guardas existentes** en `config/dist/script/php/tenancy.php`: `require_perfil($min)`
  (jerárquico por número de perfil) + filtrado multi-tenant (`tenant_where`, etc.).
- **Mora:** `assert_tenant_activo($link)` corta con 402 en escrituras si el dueño está en
  mora (modo solo-lectura).
- **Modo soporte SA:** `admin_as_dueno` en sesión hace que el SA opere como un dueño.
- **Hueco:** `require_perfil` es jerárquico (perfil ≤ N), no sabe distinguir *qué acción*
  puede hacer un rol. Encargado y empleado quedan mezclados.

## 3. Mapa de capacidades (fijo por rol)

| Capacidad (slug)            | Cliente (5) | Empleado (4) | Encargado (3) | Dueño (2) | SA (1) |
|-----------------------------|:----:|:----:|:----:|:----:|:----:|
| Reservar como cliente       | ✅ | — | — | — | — |
| `reserva.crear`             | — | ✅ | ✅ | ✅ | ✅* |
| `reserva.confirmar`         | — | ✅ | ✅ | ✅ | ✅* |
| `reserva.cancelar`          | — | ✅ | ✅ | ✅ | ✅* |
| `pago.registrar`            | — | ✅ | ✅ | ✅ | ✅* |
| `caja.cerrar`               | — | ✅ | ✅ | ✅ | ✅* |
| `reportes.ver`              | — | ❌ | ✅ | ✅ | ✅* |
| `config.canchas`            | — | ❌ | ✅ | ✅ | ✅* |
| `staff.empleados`           | — | ❌ | ✅ | ✅ | ✅* |
| `staff.encargados`          | — | ❌ | ❌ | ✅ | ✅* |
| `billing.suscripcion`       | — | ❌ | ❌ | ✅ | — |
| `saas.gestion`              | — | — | — | — | ✅ |

\* El SuperAdmin obtiene las capacidades operativas **solo en modo soporte**
(`admin_as_dueno` seteado): en ese caso su perfil efectivo es 2 (dueño). Fuera del modo
soporte, el SA solo tiene `saas.gestion`.

Decisiones de producto tomadas en el brainstorming:
- Empleado (4) **puede** cancelar reservas confirmadas y hacer cierre de caja; **no puede**
  ver reportes ni configurar canchas/horarios/precios.
- Encargado (3) suma reportes, configuración y gestión de **empleados** (no de encargados).
- Modelo **fijo por rol** (no configurable por usuario). Sin tabla de permisos por persona.

## 4. Componente central: `capabilities.php`

Nuevo archivo `config/dist/script/php/capabilities.php`, incluido después de `tenancy.php`
en las APIs. Contiene:

```php
const CAPS_POR_PERFIL = [
    1 => ['saas.gestion'],                       // + caps de dueño vía modo soporte
    2 => ['reserva.crear','reserva.confirmar','reserva.cancelar','pago.registrar',
          'caja.cerrar','reportes.ver','config.canchas','staff.empleados',
          'staff.encargados','billing.suscripcion'],
    3 => ['reserva.crear','reserva.confirmar','reserva.cancelar','pago.registrar',
          'caja.cerrar','reportes.ver','config.canchas','staff.empleados'],
    4 => ['reserva.crear','reserva.confirmar','reserva.cancelar','pago.registrar',
          'caja.cerrar'],
    5 => [],
];

function perfil_efectivo(): int      // usuario_perfil; si SA con admin_as_dueno → 2
function can(string $cap): bool       // ¿el perfil efectivo tiene la capacidad?
function require_cap(string $cap)     // si no → 403 {ok:false,msg:'No tenés permisos...'}
function panel_url_para(int $perfil)  // destino post-login: 5→cliente, 4→encargado, 3/2/1→dashboard
```

- `perfil_efectivo()` resuelve el modo soporte del SA en un solo lugar: si hay
  `admin_as_dueno`, devuelve 2, y así `can()`/`require_cap()` habilitan las capacidades
  operativas del dueño asistido.
- `require_cap()` responde con el mismo formato JSON y código 403 que el resto de las
  guardas, con la palabra **"permisos"** en el mensaje (la suite ya la usa como marcador).

## 5. Backend — enforcement (la seguridad real)

En cada API sensible, **después** de `require_perfil()`, se agrega `require_cap('<slug>')`
en la acción correspondiente. La UI que oculta botones **no es seguridad**; el 403 del
backend sí.

**Orden de chequeos** (convención en toda API que escribe):
`auth (sesión) → require_perfil → require_cap → assert_tenant_activo (mora)`.
Así un empleado de un dueño moroso recibe primero el 403 de permisos si no tiene la
capacidad, o el 402 de mora si la tiene pero el dueño está vencido.

### Inventario de endpoints (panel admin) y capacidad requerida

| Endpoint (`view/maquetaAdmin/api/`) | Acciones a cerrar | Capacidad |
|---|---|---|
| `reservas.php`      | crear/confirmar/cancelar        | `reserva.*` (empleado ✅) |
| `caja.php`          | arqueo/cierre                    | `caja.cerrar` (empleado ✅) |
| `reportes.php`      | todo                             | `reportes.ver` (empleado ❌) |
| `export_reportes.php` | export                         | `reportes.ver` (empleado ❌) |
| `cierres.php`       | listar cierres históricos        | `reportes.ver` (empleado ❌) |
| `canchas.php`       | crear/editar/toggle              | `config.canchas` (empleado ❌) |
| `horarios.php`      | crear/editar/eliminar            | `config.canchas` (empleado ❌) |
| `planes.php`        | crear/editar/toggle              | `config.canchas` (empleado ❌) |
| `fotos.php`         | subir/borrar                     | `config.canchas` (empleado ❌) |
| `turnos_fijos.php`  | crear/editar turnos recurrentes  | `config.canchas` (empleado ❌) |
| `complejos.php`     | crear/editar complejo            | `config.canchas` (empleado ❌) |
| `usuarios.php`      | alta/baja/toggle **empleado**    | `staff.empleados` |
| `usuarios.php`      | alta/baja/toggle **encargado**   | `staff.encargados` |
| `onboarding_completo.php` | onboarding del dueño        | `config.canchas` (dueño) |
| `admin_context.php` | set/clear modo soporte           | `saas.gestion` (SA) |
| `perfil.php`        | ver/editar **propio** perfil     | sin cap (self) |
| `geo.php`, `catalogo.php` | GET catálogos              | sin cap (lectura pública autenticada) |

Panel encargado (`view/maquetaEncargado/api/`):

| Endpoint | Acciones | Capacidad |
|---|---|---|
| `reservas.php` (wrapper) | `listar`/`confirmar`/`registrar_pago` (delega al admin) | `reserva.*` / `pago.registrar` (empleado ✅) |

**Defensa en profundidad:** aunque el empleado no *vea* el Dashboard, sus APIs
(`reportes.php`, `canchas.php`, etc.) deben devolver **403** si el empleado las llama directo
por HTTP. El enforcement va en el backend, no en qué panel se renderiza. La sección 10 lo testea.

> A confirmar en el plan de implementación: si `complejos.php` (editar el complejo en sí)
> y `turnos_fijos.php` deben quedar en `config.canchas` o subir a una capacidad dueño-only.
> Default propuesto: `config.canchas` (encargado ✅). `cierres.php` como lectura de reportes.

## 6. Frontend

### `Dashboard.php` (dueño, SA y ahora **encargado**)

- Reemplazar los `$perfil === 3 || 4` (y checks sueltos) por `can('<cap>')` para la
  visibilidad de secciones/tabs. El `|| 4` sale (el empleado ya no entra al Dashboard).
- Inyectar en el render un objeto JS `CAPS = { 'reportes.ver': true, ... }` con el
  resultado de `can()` por capacidad, para ocultar/deshabilitar botones del lado cliente
  (gestionar encargados, billing/suscripción → ocultos para encargado).
- Resultado por rol dentro del Dashboard:
  - **Encargado (3):** turnos, cobros, caja, **reportes, configuración, alta de empleados**.
    Sin gestionar encargados, sin billing.
  - **Dueño (2):** todo lo del complejo + encargados + suscripción.
  - **SA (1):** + gestión SaaS; en modo soporte, capacidades del dueño asistido.

### `PanelEncargado.php` (empleado)

- Superficie operativa del empleado (4). No requiere cambios de features (ya hace
  agenda/confirmar/cobrar); su seguridad se refuerza en backend (sección 5). El caja/cierre,
  si hoy no está en el panel, se suma como acción operativa gated por `caja.cerrar`.

## 7. Ruteo / superficie por perfil

Decisión: **cada perfil trabaja en su superficie**, y el corte 3-vs-4 se materializa como
paneles distintos (que es la distinción de producto que se busca).

| Perfil | Superficie | Notas |
|---|---|---|
| Cliente (5)  | `LaCanchitaCliente.php` | sin cambios |
| Empleado (4) | `PanelEncargado.php`    | solo operación (agenda, confirmar, cobrar, caja) |
| Encargado (3)| **`Dashboard.php`**     | Dashboard completo, con capacidades que ocultan lo dueño-only (gestionar encargados, billing) |
| Dueño (2)    | `Dashboard.php`         | todo su complejo |
| SA (1)       | `Dashboard.php`         | + gestión SaaS; modo soporte para operar como dueño |

**Cambios de ruteo necesarios** (unificar en un solo criterio):

1. Helper único `panel_url_para(int $perfil): string` (en `capabilities.php`), fuente de
   verdad del destino post-login. Lo usan tanto `procesar_login.php` como el front que
   consume `login_ajax.php` (hoy divergen).
2. `procesar_login.php` y `auth_view.php`: **encargado (3) → Dashboard**, empleado (4) →
   PanelEncargado (hoy ambos van al PanelEncargado).
3. **Cerrar el hueco del Dashboard:** su chequeo inline (deja entrar 1–4) pasa a permitir
   solo **1–3**; el empleado (4) que llegue por URL se redirige a PanelEncargado. Los bloques
   `$perfil === 3 || 4` se reescriben con `can()` (el `|| 4` desaparece porque el 4 ya no entra).
4. `PanelEncargado.php` mantiene `require_view(3,4)` (el encargado puede seguir usándolo para
   operación rápida), pero su **home** es el Dashboard.

**Landing por rol** (toque de "flujo guiado", reusando secciones): empleado aterriza en la
operación del día (agenda/cobros); encargado y dueño en el overview con KPIs. Se elige el tab
default según capacidad, sin pantallas nuevas.

## 8. Auditoría de acciones sensibles

Nueva tabla `auditoria` + helper `registrar_evento()` en `capabilities.php` (o archivo
propio `auditoria.php`), llamado en las mismas acciones donde va `require_cap` para las
operaciones sensibles: `reserva.cancelar`, `caja.cerrar`, `staff.empleados`,
`staff.encargados`, `config.canchas`.

```sql
CREATE TABLE IF NOT EXISTS auditoria (
    AUD_ID       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    USUARIOS_ID  INT UNSIGNED NOT NULL,      -- quién ejecutó (perfil real)
    ACTUA_COMO   INT UNSIGNED NULL,          -- si SA en modo soporte: dueño asistido
    CAP          VARCHAR(40) NOT NULL,       -- slug de capacidad
    DETALLE      VARCHAR(255) NULL,          -- ej: "reserva #123 cancelada"
    IP           VARCHAR(45) NULL,
    CREATED_AT   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (USUARIOS_ID),
    INDEX idx_cap (CAP),
    INDEX idx_fecha (CREATED_AT)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- `registrar_evento(string $cap, string $detalle)` toma `usuario_id` real de sesión y,
  si hay `admin_as_dueno`, lo guarda en `ACTUA_COMO` — así el modo soporte del SA queda
  atribuido ("SA actuando como dueño X").
- Best-effort: un fallo al auditar no rompe la acción principal.
- Migración versionada en `sql/auditoria.sql` **y** `CREATE IF NOT EXISTS` inline como respaldo.

## 9. Modelo de datos

- **Roles/capacidades: cero tablas.** Todo vive en código (`CAPS_POR_PERFIL`);
  `PERFIL_ID` ya existe en `usuarios`.
- **Auditoría: una tabla nueva** (`auditoria`, sección 8).

## 10. Testing — deny-by-default

Extender `tests/suite.sh` usando el seed existente (`emp@test.com`=4, `enc@test.com`=3,
`dueno@test.com`=2, `sa@test.com`=1):

- **Empleado (4):**
  - `reportes.php`, `export_reportes.php`, `cierres.php` → **403 / permisos**
  - `canchas.php`/`horarios.php`/`planes.php`/`fotos.php`/`turnos_fijos.php`/`complejos.php`
    (crear/editar) → **403**
  - `usuarios.php` alta de empleado o encargado → **403**
  - `reservas.php` crear/confirmar/cancelar, `caja.php` cierre, `pago` → **ok**
- **Encargado (3):** reportes/config/alta de empleado → **ok**; alta de encargado → **403**
- **Dueño (2):** todo lo del complejo → **ok**
- **Auditoría:** tras cancelar una confirmada / cerrar caja / alta de staff, existe la fila
  en `auditoria` con el `CAP` y el `USUARIOS_ID` correctos; en modo soporte SA, `ACTUA_COMO`
  apunta al dueño.
- **Cobertura demostrable:** recorrer el inventario de la sección 5 y afirmar el 403 del
  empleado en **cada** endpoint restringido (no "creo que están todos").
- **Ruteo por superficie:** el empleado (4) que pide `Dashboard.php` es redirigido (302) al
  PanelEncargado; el encargado (3) que pide `Dashboard.php` **entra** (200). `panel_url_para()`
  devuelve el destino correcto por perfil.

## 11. Fuera de scope (YAGNI)

- Sin permisos configurables por usuario (roles fijos).
- Sin router/front controller nuevo (PHP puro, cada API es un archivo suelto).
- Sin cambios a las capacidades del panel cliente.
- Los archivos locales `gastos.php`/`productos.php`/`ventas.php` (sin trackear) no forman
  parte de este inventario.
- La feature de recordatorios de turno sigue en su propia rama de trabajo, aparte.

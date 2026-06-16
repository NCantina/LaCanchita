# CLAUDE.md — Reglas de trabajo para LaCanchita

## ¿Qué es este proyecto?

SaaS multi-tenant en PHP puro para reservas de canchas deportivas.
Sin frameworks, sin ORM. MySQL + mysqli, sesiones PHP nativas.

---

## Roles y perfiles

| PERFIL_ID | Nombre      | Panel                                      |
|-----------|-------------|--------------------------------------------|
| 1         | SuperAdmin  | `view/maquetaAdmin/Dashboard.php`          |
| 2         | Dueño       | `view/maquetaAdmin/Dashboard.php`          |
| 3         | Encargado   | `view/maquetaAdmin/Dashboard.php`          |
| 4         | Empleado    | `view/maquetaAdmin/Dashboard.php`          |
| 5         | Cliente     | `view/maquetaCliente/LaCanchitaCliente.php`|

---

## Archivos críticos

```
config/dist/script/php/
  conn.php          → conexión MySQL ($link global)
  tenancy.php       → multi-tenant: tenant_complejo_ids(), tenant_where(), require_perfil()
  auth_view.php     → require_view($perfil, $minPerfil) para vistas PHP
  pwa_head.php      → <head> PWA: manifest, SW, push, install prompt
  push_notify.php   → enviarPush() y enviarPushReserva()
  mailer.php        → enviarConfirmacion(), enviarRecordatorioCobro(), etc.

view/maquetaAdmin/
  Dashboard.php     → panel admin/dueño/encargado/SA (un solo archivo, ~9000 líneas)
  Onboarding.php    → wizard 3 pasos para dueños nuevos (solo perfil=2 sin complejos)
  api/              → APIs JSON del panel admin

view/maquetaCliente/
  LaCanchitaCliente.php → panel cliente: 3 tabs (Predios, Mis Reservas, Mi Perfil)
  api/predios.php       → listar predios, canchas y disponibilidad
  api/reservas.php      → mis_reservas, crear, cancelar
  api/perfil.php        → get, update

view/maquetaSuperAdmin/
  PanelDesarrollador.php → gestión SaaS: clientes/dueños, cobros, MRR
  api/clientes.php       → stats, listar, cobros, planes

config/vapid.php    → VAPID keys (GITIGNOREADO — no commitear)
config/mail.php     → credenciales SMTP (GITIGNOREADO — no commitear)
```

---

## Convenciones de código

### PHP
- Siempre `session_start()` al inicio de cada archivo que use sesiones
- **NUNCA** escribir a `$_SESSION` después de que `tenancy.php` fue incluido sin antes llamar `session_start()` nuevamente — tenancy.php llama `session_write_close()` inmediatamente
- Toda API devuelve JSON: `['ok'=>bool, 'msg'=>string, 'data'=>mixed]`
- SQL: nombres de columnas en MAYÚSCULAS (`USUARIOS_ID`, `COMPLEJO_NOMBRE`, etc.)
- Escapar siempre inputs: `(int)$_POST['id']`, `mysqli_real_escape_string($link, $str)`

### JavaScript
- `escHtml(str)` para escapar antes de insertar en el DOM
- `toast(msg, 'ok'|'err')` para feedback al usuario
- `confirmar(title, msg, onYes)` reemplaza `window.confirm()`
- Fetch siempre a rutas relativas (`api/reservas.php`, no absolutas)
- Estado global en objeto `S = {}` o `const STATE = {}`

### CSS
- Variables: `--bg:#09090f`, `--s1:#101018`, `--s2:#16161f`, `--s3:#1d1d28`, `--green:#4cd964`
- Mobile-first: breakpoint principal `@media(max-width:767px)`
- Bottom nav mobile: 64px fijo abajo (`--bn-h:64px`)
- Sidebar desktop: 240px fijo izquierda (`--sb-w:240px`)

---

## Multi-tenancy

`tenancy.php` es el guardián central. Siempre incluirlo en APIs del panel admin.

```php
// Al inicio de cada API:
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';
require_perfil(2); // mínimo: 1=SA, 2=dueño, 3=encargado

// Filtrar por tenant:
$ids = tenant_complejo_ids($link);   // null=todo (SA), []=sin acceso, [1,2]=sus complejos
$where = tenant_where($ids, 'co.COMPLEJO_ID');  // "co.COMPLEJO_ID IN (1,2)" | "1=1" | "1=0"
```

### Modo soporte SuperAdmin
- SuperAdmin puede "entrar" al panel de un dueño via `api/admin_context.php`
- Guarda `$_SESSION['admin_as_dueno']` = USUARIOS_ID del dueño
- `tenancy.php` lo detecta y aplica el tenant del dueño al SuperAdmin
- **BUG CONOCIDO Y RESUELTO**: `admin_context.php` debe llamar `session_start()` antes de escribir al `$_SESSION` porque `tenancy.php` ya cerró la sesión

---

## PWA

- `manifest.webmanifest` en raíz
- `sw.js` en raíz — strategies: APIs nunca cacheadas, estáticos cache-first, navegación network-first
- Íconos en `config/dist/img/pwa/` (192, 512, maskable-512, apple-touch-icon 180px)
- `pwa_head.php` se incluye en el `<head>` de cada vista: `$PWA_BASE = '../../'; require_once '...'`
- Push notifications: VAPID en `config/vapid.php`, suscripciones en tabla `push_subscriptions`

---

## Web Push

```php
// En cualquier API, para notificar al cliente:
require_once '../../../config/dist/script/php/push_notify.php';
enviarPushReserva($usuarios_id, 'confirmada'|'cancelada', $datos_reserva);

// O genérico:
enviarPush($usuarios_id, 'Título', 'Cuerpo del mensaje', ['url'=>'/...']);
```

---

## Onboarding

- Solo para `$perfil === 2` (dueños) sin complejos activos
- `Dashboard.php` redirige a `Onboarding.php` automáticamente
- Skip: `?skip_onboarding=1` → guarda `$_SESSION['onboarding_skip'] = true`
- Flujo: Predio (complejo) → Cancha → Horarios → Listo

---

## APIs del panel cliente

Base: `view/maquetaCliente/api/`

| Archivo        | Acciones                                    |
|---------------|---------------------------------------------|
| predios.php    | `listar`, `canchas`, `disponibilidad`       |
| reservas.php   | `mis_reservas`, `crear`, `cancelar`         |
| perfil.php     | `get`, `update`                             |

---

## APIs del panel admin

Base: `view/maquetaAdmin/api/`

| Archivo            | Acciones principales                                      |
|-------------------|-----------------------------------------------------------|
| complejos.php      | `listar`, `crear`, `editar`, `toggle`                    |
| canchas.php        | `listar`, `crear`, `editar`, `toggle`                    |
| horarios.php       | `listar`, `crear`, `editar`, `eliminar`                  |
| reservas.php       | `listar`, `crear`, `confirmar`, `rechazar`, `cancelar`   |
| usuarios.php       | `listar_duenos`, `crear`, `toggle`                       |
| admin_context.php  | `set`, `clear`, `current` (modo soporte SA)              |
| geo.php            | provincias, partidos, localidades (cascade)              |
| catalogo.php       | tipos de cancha, tipos de complejo, medios de pago       |
| perfil.php         | `get`, `update`                                          |

---

## Variables de sesión

| Variable                 | Descripción                                      |
|--------------------------|--------------------------------------------------|
| `usuario_id`             | ID del usuario logueado                         |
| `usuario_nombre`         | Nombre                                           |
| `usuario_perfil`         | PERFIL_ID (1-5)                                  |
| `admin_as_dueno`         | (Solo SA) ID del dueño que se está gestionando  |
| `admin_as_dueno_nombre`  | (Solo SA) Nombre del dueño en modo soporte      |
| `onboarding_skip`        | true si el dueño saltó el onboarding            |

---

## Git

- Rama de desarrollo: `claude/session-context-u2ymzs`
- Gitignoreados: `config/vapid.php`, `config/mail.php`, `vendor/`, `*.log`
- SQL migrations pendientes: `sql/push_subscriptions.sql`, `sql/suscripcion_plataforma.sql`

---

## Lo que NO hacer

- No usar `strftime()` — deprecado en PHP 8.1+, removido en 8.4. Usar array de meses manual
- No poner `use Namespace\Class` dentro de funciones — va al inicio del archivo
- No cachear en el SW rutas que terminen en `.php` o contengan `?action=`
- No commitear `config/vapid.php` ni `config/mail.php`
- No escribir a `$_SESSION` sin reabrir la sesión si ya se incluyó `tenancy.php`

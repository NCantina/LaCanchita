# La Canchita

Plataforma SaaS para gestión de reservas de canchas deportivas. Permite a dueños de predios administrar sus complejos, canchas y turnos desde un panel web; a encargados gestionar el día a día operativo; y a clientes reservar en línea desde la página pública del predio.

---

## Características principales

- **Multi-tenant**: cada dueño administra sus propios complejos y canchas de forma aislada
- **Roles granulares**: 5 niveles de acceso con paneles separados por rol
- **Reservas públicas**: página pública del predio sin login, con calendario visual
- **Notificaciones por email**: confirmación, cancelación y recordatorios vía PHPMailer
- **Billing integrado**: el SuperAdmin gestiona suscripciones y cobra a los dueños desde el Panel Desarrollador
- **Sin dependencias de Node**: stack PHP puro + jQuery/Bootstrap del lado del cliente

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Lenguaje backend | PHP 8+ |
| Base de datos | MySQL 5.7+ / MariaDB |
| Email | PHPMailer 7.1 (vía Composer) |
| Frontend | Bootstrap 4, jQuery 3, Font Awesome 5 |
| Autenticación | Sesiones PHP nativas |
| Control de acceso | Tenancy personalizado (roles por perfil) |

---

## Estructura de roles

| Perfil ID | Nombre | Acceso |
|---|---|---|
| 1 | SuperAdmin / Desarrollador | Panel Desarrollador — gestión global de clientes SaaS, billing, MRR |
| 2 | Dueño | Dashboard Admin — complejos, canchas, reservas, reportes, usuarios |
| 3 | Encargado | Panel Encargado — reservas del día, confirmaciones, cobros |
| 4 | Empleado | Panel Encargado (vista reducida) — mismas vistas que encargado |
| 5 | Cliente | Panel Cliente — mis reservas, historial |

---

## Estructura del proyecto

```
LaCanchita/
├─ api/                          # Endpoints públicos (sin autenticación requerida)
│  ├─ buscar_canchas.php         # Búsqueda de canchas por localidad/deporte
│  ├─ login_ajax.php             # Login por AJAX
│  ├─ predio_publico.php         # Info pública del predio + disponibilidad
│  ├─ register_ajax.php          # Registro de nuevos clientes
│  └─ reservar_publico.php       # Alta de reserva pública
│
├─ view/
│  ├─ maquetaAdmin/              # Dashboard del Dueño (perfil 2) y SuperAdmin (perfil 1)
│  │  ├─ Dashboard.php
│  │  └─ api/                   # APIs privadas del admin (autenticadas con tenancy)
│  │     ├─ canchas.php
│  │     ├─ complejos.php
│  │     ├─ reservas.php
│  │     ├─ horarios.php
│  │     ├─ turnos_fijos.php
│  │     ├─ cierres.php
│  │     ├─ catalogo.php
│  │     ├─ usuarios.php
│  │     ├─ perfil.php
│  │     ├─ reportes.php
│  │     ├─ export_reportes.php
│  │     ├─ geo.php
│  │     └─ admin_context.php
│  │
│  ├─ maquetaEncargado/          # Panel del Encargado/Empleado (perfiles 3–4)
│  │  ├─ PanelEncargado.php
│  │  └─ api/
│  │     └─ reservas.php         # Proxy con whitelist → delega a maquetaAdmin/api/reservas.php
│  │
│  ├─ maquetaCliente/            # Panel del Cliente (perfil 5)
│  │  ├─ LaCanchitaCliente.php
│  │  ├─ HomeCliente.php
│  │  └─ api/
│  │     ├─ reservas.php
│  │     ├─ predios.php
│  │     └─ perfil.php
│  │
│  └─ maquetaSuperAdmin/         # Panel Desarrollador (perfil 1)
│     ├─ PanelDesarrollador.php  # Gestión de clientes SaaS, billing, MRR
│     └─ api/
│        └─ clientes.php         # API de billing y suscripciones
│
├─ config/
│  ├─ mail.php                   # Credenciales SMTP (no commitear con datos reales)
│  └─ dist/script/php/
│     ├─ conn.php                # Conexión MySQL
│     ├─ tenancy.php             # Control de acceso multi-tenant para APIs
│     ├─ auth_view.php           # Guard de redirección para vistas HTML
│     ├─ auth_check.php          # Guard básico (legacy)
│     └─ mailer.php              # Funciones de envío de email
│
├─ sql/
│  └─ suscripcion_plataforma.sql # DDL: tablas suscripcion_plataforma + cobro_plataforma
│
├─ vendor/                       # Composer (PHPMailer)
├─ composer.json
├─ index.php                     # Landing / home pública
├─ login.php                     # Formulario de login
├─ procesar_login.php            # Procesador de login → redirige según perfil
├─ logout.php
├─ register.php                  # Formulario de registro de clientes
└─ predio.php                    # Página pública del predio (calendario de reservas)
```

---

## Instalación

### Requisitos previos

- PHP 8.0 o superior
- MySQL 5.7+ / MariaDB 10.3+
- Apache con `mod_rewrite` habilitado (o Nginx equivalente)
- Composer

### 1. Clonar el repositorio

```bash
git clone https://github.com/ncantina/lacanchita.git
cd lacanchita
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Configurar la base de datos

Crear la base de datos e importar el schema:

```bash
mysql -u root -p -e "CREATE DATABASE lacanchita CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p lacanchita < sql/suscripcion_plataforma.sql
```

### 4. Configurar la conexión a la base de datos

Editar `config/dist/script/php/conn.php`:

```php
$host     = "localhost";
$user     = "tu_usuario";
$password = "tu_contraseña";
$database = "lacanchita";
```

### 5. Configurar el email (opcional)

Editar `config/mail.php`:

```php
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USER',     'tu-cuenta@gmail.com');
define('MAIL_PASS',     'xxxx xxxx xxxx xxxx');   // Contraseña de aplicación de Google
define('MAIL_FROM',     'tu-cuenta@gmail.com');
define('MAIL_FROM_NAME','La Canchita');
define('MAIL_ENABLED',  true);
```

Para Gmail: activar verificación en dos pasos y generar una **Contraseña de aplicación** en myaccount.google.com/apppasswords.

Mientras `MAIL_ENABLED` sea `false`, las funciones de envío no realizan ninguna petición SMTP — seguro para desarrollo.

---

## Flujo de autenticación

```
POST /procesar_login.php
        │
        ├─ perfil 5 (Cliente)         →  view/maquetaCliente/LaCanchitaCliente.php
        ├─ perfil 3 o 4 (Staff)       →  view/maquetaEncargado/PanelEncargado.php
        └─ perfil 1 o 2 (Admin/Dev)   →  view/maquetaAdmin/Dashboard.php
```

Las **vistas** protegidas usan `require_view($min, $max)` de `auth_view.php` — redirige al panel correcto si el rol no coincide.  
Las **APIs** protegidas usan `require_perfil($max)` de `tenancy.php` — responde `403 JSON` si el rol no coincide.

---

## APIs — referencia rápida

### Públicas (`/api/`)

| Endpoint | Método | Descripción |
|---|---|---|
| `buscar_canchas.php` | GET | Busca predios/canchas por localidad y deporte |
| `predio_publico.php` | GET | Datos del predio + disponibilidad de canchas |
| `reservar_publico.php` | POST | Crea una reserva sin login (estado pendiente) |
| `login_ajax.php` | POST | Login vía AJAX |
| `register_ajax.php` | POST | Registro de nuevo cliente |

### Admin (`/view/maquetaAdmin/api/`) — perfil ≤ 2

| Endpoint | Acciones principales |
|---|---|
| `reservas.php` | listar, confirmar, cancelar, registrar_pago, agenda |
| `canchas.php` | listar, alta, editar, baja |
| `complejos.php` | listar, alta, editar |
| `horarios.php` | listar, guardar |
| `turnos_fijos.php` | listar, guardar, eliminar |
| `cierres.php` | listar, guardar, eliminar |
| `usuarios.php` | listar, alta, editar, baja |
| `reportes.php` | estadísticas por período |
| `catalogo.php` | deportes y servicios disponibles |

### Encargado (`/view/maquetaEncargado/api/`) — perfil ≤ 4

Proxy con whitelist hacia el admin; expone únicamente: `listar`, `confirmar`, `registrar_pago`.

### SuperAdmin (`/view/maquetaSuperAdmin/api/`) — perfil = 1

| Acción | Descripción |
|---|---|
| `stats` | Totales: clientes, MRR, cobros del mes, alertas |
| `listar` | Lista clientes con filtros (todos / activos / vencidos / por_cobrar) |
| `historial` | Historial de cobros de un cliente |
| `mrr_historico` | MRR mensual — últimos 6 meses sin huecos |
| `cobros_todos` | Todos los cobros filtrados por mes y cliente |
| `upsert_plan` | Crear o actualizar suscripción de un cliente |
| `registrar_cobro` | Registrar pago + avanzar PROXIMO_COBRO automáticamente |
| `eliminar_cobro` | Eliminar registro de cobro |
| `guardar_notas` | Autosave de notas internas del cliente (debounce 900ms) |
| `toggle_cliente` | Bloquear / desbloquear cuenta |
| `recordatorio` | Enviar email de recordatorio de pago |
| `marcar_vencidos` | Actualiza a "vencido" los planes con PROXIMO_COBRO pasado |

---

## Modelo de datos — billing

```sql
-- Plan de suscripción por cliente (dueño de predio)
suscripcion_plataforma (
  SUSCRIPCION_ID, USUARIOS_ID, PLAN_NOMBRE, PLAN_PRECIO,
  PLAN_CICLO ENUM('mensual','trimestral','anual'),
  PROXIMO_COBRO DATE, ULTIMO_COBRO DATE,
  ESTADO ENUM('prueba','activo','vencido','cancelado'),
  MEDIO_COBRO, NOTAS
)

-- Historial de pagos recibidos
cobro_plataforma (
  COBRO_ID, USUARIOS_ID, COBRO_MONTO, COBRO_FECHA DATE,
  COBRO_PERIODO VARCHAR(7),  -- formato YYYY-MM
  COBRO_MEDIO, COBRO_NOTAS
)
```

---

## Emails de notificación

`enviarEmailReserva(string $tipo, array $datos)` — notifica al cliente cambios en su reserva:

| `$tipo` | Asunto | Acento |
|---|---|---|
| `pendiente` | ✅ Reserva recibida | Naranja `#ff9500` |
| `confirmada` | 🎉 Reserva confirmada | Verde `#4cd964` |
| `cancelada` | Reserva cancelada | Rojo `#e74c3c` |

`enviarRecordatorioCobro(array $datos)` — avisa al dueño del predio sobre un pago de suscripción pendiente.

Ambas retornan `false` silenciosamente si `MAIL_ENABLED` es `false` o si ocurre un error SMTP (el error va a `error_log()`).

---

## Panel Desarrollador

Accesible desde el Dashboard Admin cuando `perfil = 1` → enlace "Panel desarrollador" en el pie del sidebar.

**Tres pestañas:**

- **Resumen** — tarjetas de MRR, clientes activos/vencidos, cobros del mes. Lista de alertas (cobros próximos o vencidos). Gráfico de barras MRR últimos 6 meses (CSS puro, sin librerías).
- **Clientes** — tabla filtrable y ordenable por nombre, precio o próximo cobro. Panel de detalle lateral con plan, historial de cobros, notas (autosave 900ms) y acciones rápidas (cobrar, recordatorio, bloquear).
- **Cobros** — historial global filtrado por mes y cliente.

Atajos de teclado: `ESC` cierra paneles y diálogos; `Ctrl+K` enfoca el buscador.

---

## Convenciones de código

- Las APIs siempre comienzan con `session_start()` + `header('Content-Type: application/json')` y responden `{ ok: bool, ... }`.
- Toda query con input de usuario usa `mysqli_prepare` + bind (sin interpolación de strings).
- Las columnas de base de datos usan `MAYUSCULAS_CON_GUION_BAJO`.
- Las APIs admin incluyen `tenancy.php` y llaman `require_perfil(N)` antes de cualquier lógica de negocio.
- Las vistas HTML incluyen `auth_view.php` y llaman `require_view($min, $max)`.

---

## Variables de sesión

| Variable | Contenido |
|---|---|
| `$_SESSION['usuario_id']` | ID numérico del usuario autenticado |
| `$_SESSION['usuario_nombre']` | Nombre |
| `$_SESSION['usuario_apellido']` | Apellido |
| `$_SESSION['usuario_email']` | Email |
| `$_SESSION['usuario_perfil']` | Perfil ID (1–5) |

---

## Seguridad — consideraciones

- Las credenciales de base de datos están en `conn.php`. En producción, moverlas a variables de entorno del servidor web.
- `config/mail.php` contiene credenciales SMTP — agregar al `.gitignore` antes de usar datos reales.
- Todas las APIs validan rol antes de ejecutar cualquier lógica.
- Los inputs de usuario en queries SQL siempre van por `mysqli_prepare`.

---

## Licencia

Proyecto privado — todos los derechos reservados.

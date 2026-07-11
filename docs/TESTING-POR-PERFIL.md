# Cómo probar el sistema según cada perfil

Guía manual de QA para verificar los flujos de los 5 perfiles de LaCanchita.
Usa la **base de prueba descartable** (`lacanchita_test`) con usuarios seed conocidos —
nunca la base de desarrollo con datos reales (la suite y estas pruebas ESCRIBEN datos).

> Fecha: 2026-07-09 · Rama: `claude/ola-2-monetizacion`

---

## 0. Preparar el entorno (una vez)

Desde la raíz del repo (`C:\xampp\htdocs\LaCanchita`), en Git Bash. XAMPP ya trae PHP y
MariaDB; solo hay que ponerlos en el PATH y levantar la base de prueba.

```bash
export PATH="$PATH:/c/xampp/php:/c/xampp/mysql/bin"

# 1) Base de prueba limpia
mysql -uroot -e "DROP DATABASE IF EXISTS lacanchita_test; CREATE DATABASE lacanchita_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -uroot --default-character-set=utf8mb4 lacanchita_test < tests/schema_test.sql
for f in sql/*.sql; do mysql -uroot --default-character-set=utf8mb4 lacanchita_test < "$f"; done
mysql -uroot --default-character-set=utf8mb4 lacanchita_test < tests/seed_test.sql

# 2) Levantar la app apuntando a la base de prueba
DB_NAME=lacanchita_test php -S 127.0.0.1:8088 -t . &
```

Abrí el navegador en **http://127.0.0.1:8088/login.php**

> Alternativa (base de desarrollo real vía Apache): arrancá Apache+MySQL desde el panel de
> XAMPP y entrá a `http://localhost/LaCanchita/login.php`. Usa tus propias cuentas; el resto
> de esta guía asume los usuarios seed de la base de prueba.

### Usuarios seed (contraseña `test1234` para todos)

| Perfil | Email | Nombre | Notas |
|---|---|---|---|
| 1 SuperAdmin | `sa@test.com`     | Súper Admin   | gestión de la plataforma |
| 2 Dueño      | `dueno@test.com`  | Diego Dueño   | dueño de **Predio Test** (Cancha 1) |
| 3 Encargado  | `enc@test.com`    | Elena Encargada | asignada a Cancha 1 |
| 4 Empleado   | `emp@test.com`    | Emi Empleado  | del equipo de Diego |
| 5 Cliente    | `cliente@test.com`| Carla Cliente | juega, reserva |
| 2 Dueño moroso | `dueno2@test.com` | Damián DueñoDos | **suscripción vencida** → modo solo-lectura; dueño de Predio Ajeno |

Datos del Predio Test: Cancha 1 (Fútbol 5), franjas 10–11, 11–12 y 12–13 hs
($10.000, seña $2.000), todos los días.

---

## 1. Cliente (`cliente@test.com`)

1. Login → **debe aterrizar en el panel cliente** (`LaCanchitaCliente.php`).
2. Tab **Predios**: buscá por localidad "La Plata" → aparece **Predio Test**. Entrá al predio.
3. Elegí Cancha 1, fecha de mañana, horario 10:00 → **Reservar**. Debe confirmar "reserva recibida".
4. Tab **Mis Reservas**: la reserva figura como **pendiente**.
5. Cancelá esa reserva → pasa a **cancelada** y el horario vuelve a quedar libre.
6. Tab **Mi Perfil**: editá tu teléfono y guardá → persiste al recargar.
7. **Negativo:** intentá entrar a mano a `http://127.0.0.1:8088/view/maquetaAdmin/Dashboard.php`
   → debe **echarte** al panel cliente (no podés ver el panel admin).

---

## 2. Empleado (`emp@test.com`)

1. Login → **debe aterrizar en el Panel Encargado** (`PanelEncargado.php`, vista mobile).
2. Ves la **agenda del día** de las canchas del complejo.
3. **Confirmar** una reserva pendiente → pasa a confirmada.
4. **Registrar cobro** sobre una reserva confirmada → queda registrado.
5. **Lo que NO debe poder (según el modelo de roles a implementar):**
   - No ve reportes ni estadísticas.
   - No configura canchas/horarios/precios.
   - No gestiona personal.
   - **Negativo (backend):** llamar directo a una API restringida debe dar **403**. Ej. en otra
     pestaña logueado como empleado:
     `http://127.0.0.1:8088/view/maquetaAdmin/api/reportes.php?action=...` → `{"ok":false, ... permisos}`.

> Estado actual: hoy empleado y encargado comparten este panel y son idénticos. El modelo de
> roles (spec `2026-07-09-roles-y-capacidades`) separa: empleado se queda acá; encargado pasa
> al Dashboard. Al testear *después* de esa entrega, verificá el punto 5 estricto.

---

## 3. Encargado (`enc@test.com`)

**Hoy:** login → Panel Encargado (igual que el empleado).

**Después del modelo de roles** (criterio de aceptación):

1. Login → **debe aterrizar en el Dashboard** (`Dashboard.php`).
2. **Puede:** ver turnos, cobrar, cierre de caja, **ver reportes**, **configurar canchas/horarios/precios**,
   y **dar de alta/baja empleados**.
3. **No debe poder:**
   - Gestionar **encargados** (solo el dueño) → la sección/acción está oculta y la API da **403**.
   - Ver/tocar **suscripción / billing**.
4. **Negativo (backend):** logueado como encargado, `POST` a `usuarios.php` creando un **encargado**
   → **403**; creando un **empleado** → **ok**.

---

## 4. Dueño (`dueno@test.com`)

1. Login → **Dashboard**. Si el dueño no tuviera complejos, iría al **Onboarding** (wizard).
2. **Overview:** ve KPIs de **su** complejo (Predio Test) — no de otros dueños.
3. **Reservas:** crear (walk-in), confirmar, rechazar, cancelar.
4. **Configuración:** crear/editar Cancha, horarios y precios; subir fotos del predio; planes.
5. **Reportes:** ingresos, ocupación por cancha, cierres de caja.
6. **Personal:** dar de alta/baja **encargados y empleados**, asignarlos a canchas.
7. **Suscripción:** ve el estado de su plan / próximos cobros.
8. **Aislamiento (importante):** en ninguna vista debe ver datos de **Predio Ajeno** (dueño2).
   Probá manipular IDs en las APIs (`complejo_id=2`, `cancha_id=2`) → debe devolver vacío o
   **error de acceso**, nunca datos ajenos.

---

## 5. SuperAdmin (`sa@test.com`)

1. Login → **Dashboard** en modo plataforma (o `PanelDesarrollador.php`).
2. Ve **todos** los complejos y dueños del sistema, MRR, cobros de la plataforma.
3. Puede **registrar un cobro** de la suscripción de un dueño → el `PROXIMO_COBRO` se corre a futuro.
4. **Modo soporte:** "entrar" como un dueño (`admin_context` / `admin_as_dueno`) → a partir de ahí
   opera **como ese dueño** (ve su complejo, sus reservas). Al salir del modo soporte, vuelve a
   la vista global.
5. **Aislamiento inverso:** un cliente/empleado NO debe poder pegarle a las APIs de SuperAdmin
   (`view/maquetaSuperAdmin/api/clientes.php`) → **403 / permisos**.

---

## 6. Dueño moroso (`dueno2@test.com`) — modo solo-lectura

1. Login → Dashboard, pero con **suscripción vencida**.
2. **Puede ver** sus datos (lectura).
3. **No puede escribir:** crear/confirmar reservas, cobrar, configurar → la acción corta con
   **402** (mensaje de mora / "suscripción vencida").
4. **Sus predios no reciben reservas:** desde el buscador público, Predio Ajeno no debe permitir
   reservar (o avisa que no está disponible).

---

## 7. Volver a un estado limpio

Cada corrida escribe datos. Para reiniciar, repetí el bloque del punto 0 (recrea la base de
prueba desde cero). Para bajar el server: `kill %1` (o cerrar la terminal donde corre `php -S`).

## 8. Suite automatizada (complemento)

Además de esta prueba manual, la suite e2e cubre los mismos flujos:
```bash
DB_NAME=lacanchita_test bash tests/suite.sh    # espera TOTAL: PASS=… FAIL=0
```
> Nota: en esta máquina falta `vendor/minishlink/web-push` (se instala con `composer install`);
> sin esa librería, las rutas que incluyen notificaciones fallan y la suite no da verde local.
> En el entorno de deploy (con `composer install`) corre completa.

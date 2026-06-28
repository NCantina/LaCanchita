# Auditoría LaCanchita — Experiencia por perfil y roadmap

> Fecha: 2026-06-28 · Rama: `claude/roadmap-mejoras`
> Método: auditoría multi-agente sobre el código real (9 áreas leídas en paralelo + síntesis por perfil).
> Modelo de negocio asumido (definido por el dueño del proyecto): **SaaS que se monetiza con la suscripción de los dueños de predios**; pago del jugador vía **MercadoPago/transferencias** como *feature del producto* (no como fuente de ingresos); mercado **Argentina (La Plata y alrededores)**, **multideporte**; **gestión de socios** como evolución futura.

---

## 1. Estado general

LaCanchita es un **MVP funcional y sorprendentemente completo en gestión interna de turnos**, con dos cimientos muy sólidos: el **aislamiento multi-tenant** (centralizado en `tenancy.php`) y la **PWA + Web Push** (instalable, service worker bien resuelto, push end-to-end con VAPID). El setup del negocio (predios, canchas, franjas, cierres, onboarding atómico) y la operación diaria (agenda, alta manual, confirmar/cobrar) están bien armados.

Pero **todo el modelo de plata es 100% presencial y decorativo**: la seña se muestra pero nunca se cobra ni registra, los abonos son vidriera, y no hay pasarela. Falta la capa de **confianza y conveniencia** que un producto moderno exige: el cliente reserva y **no recibe ninguna confirmación**, no puede recuperar su contraseña (404), y el dueño **no se entera de una reserva nueva** salvo por polling. Y hay **deuda de seguridad de manual** (sin CSRF, credenciales hardcodeadas, sin rate limiting) y varios **bugs que rompen tareas reales**.

En síntesis: base técnica seria, pero hay que (a) tapar los agujeros que rompen la experiencia y la seguridad, y (b) construir la capa de cobro/confianza/escala.

---

## 2. Por perfil

### 🟢 Cliente (consumidor)
**Sólido:** buscador geográfico del home con reserva inline, anti-doble-reserva transaccional (FOR UPDATE), login/registro con hashing, "Mis Reservas" con saldo real, WhatsApp directo, PWA.
**Gaps top:**
| Gap | Sev | Esf |
|---|---|---|
| Recuperar contraseña inexistente (404 en login y home) | 🔴 crítico | M |
| Cero notificación al crear la reserva (ni push ni email) | 🔴 crítico | S |
| No puede pagar la seña online (decorativa) | 🔴 crítico | XL |
| Bug: no puede cancelar reserva **confirmada** desde la UI; cancelación no setea `ACTIVO=0` | 🟠 alto | S |
| Buscador muestra como libres slots que son turno fijo / cerrados | 🟠 alto | M |
| Sin recordatorio de turno (T-24h/T-2h) → no-show | 🟠 alto | M |
| Sin reseñas/ratings; elige a ciegas | 🟠 alto | L |
| Landing del predio (SEO/redes) no permite reservar inline | 🟠 alto | L |
| Login social decorativo; registro sin verificación de email | 🟡 medio | L |

### 🟢 Empleado / Encargado (operación)
**Sólido:** scoping por `cancha_encargado`, confirmar notifica al cliente, cobro valida saldo y auto-confirma, agenda en grilla.
**Gaps top:**
| Gap | Sev | Esf |
|---|---|---|
| **Walk-in y buscar clientes ROTOS** para staff: 403 por `require_perfil(2)` al tope de `usuarios.php` | 🔴 crítico | S |
| Sin **cierre de caja / arqueo** por empleado | 🔴 crítico | L |
| Encargado no puede cerrar cancha ad-hoc (lluvia) desde su panel | 🟠 alto | M |
| Sin aviso en tiempo real de reservas nuevas (solo polling) | 🟠 alto | M |
| Sin **check-in / no-show** (estados incompletos) | 🟠 alto | M |
| Empleado (4) = Encargado (3): sin permisos granulares → riesgo de fraude | 🟠 alto | L |
| `pendientes_count` cuenta canchas no asignadas (fuga de scope) | 🟡 medio | S |
| `registrar_pago` sin transacción → sobre-cobro concurrente | 🟡 medio | S |

### 🟢 Dueño (negocio)
**Sólido:** setup transaccional completo, anti-solapamiento, cobro manual con reportes y export Excel/PDF, gestión de staff por `DUENO_ID`.
**Gaps top:**
| Gap | Sev | Esf |
|---|---|---|
| Cobro online de seña/turno (MercadoPago) — pieza monetizadora ausente | 🔴 crítico | XL |
| No se entera de reserva nueva (cero notificación al crear) | 🔴 crítico | S |
| Sin cierre de caja + permisos binarios → no puede confiar plata al staff | 🔴 crítico | L |
| Reembolsos inexistentes: cancelar reserva con pago descuadra reportes | 🟠 alto | M |
| **Sin fotos** de predio/cancha (ni columna ni upload) → no comercializable | 🟠 alto | L |
| Sin CRM ni lista negra de clientes → no-show sin defensa | 🟠 alto | L |
| Dueño moroso sigue operando; fuga de PII de clientes entre tenants | 🟠 alto | L |
| Pricing rígido: un precio por franja, sin pico/finde/feriado | 🟠 alto | M |
| Onboarding fuerza `SENA=0` y ocupación de reportes es `dias*9` (mentira) | 🟡 medio | M |

### 🟢 SuperAdmin / Plataforma (operador SaaS)
**Sólido:** CRM/cobranza manual completo (suscripciones, MRR, historial, gráfico), modo soporte funcional, `clientes.php` con prepared statements.
**Gaps top:**
| Gap | Sev | Esf |
|---|---|---|
| El estado de la suscripción **no bloquea** el acceso del dueño (sin enforcement) | 🔴 crítico | M |
| Sin límites por plan ni tiers reales (planes = texto libre) | 🔴 crítico | L |
| Impersonación **sin auditoría** (riesgo legal/compliance) | 🔴 crítico | M |
| Sin cron real: vencimientos/recordatorios dependen de abrir el panel | 🔴 crítico | M |
| SQLi latente en `usuarios.php(SA)` + credenciales DB hardcodeadas | 🟠 alto | M |
| Sin pasarela ni cobro recurrente automático de la plataforma | 🟠 alto | XL |
| Sin self-service de alta de dueños ni landing de pricing | 🟠 alto | L |
| Inconsistencias contables en el billing (PROXIMO_COBRO, eliminar_cobro) | 🟠 alto | M |

---

## 3. Riesgos técnicos / seguridad transversales (orden de urgencia)

1. **Credenciales de DB hardcodeadas** (`conn.php`: root / password vacío) → mover a entorno/gitignore. **(S)**
2. **Cero CSRF** en todos los POST (reservas, cobros, perfil, `admin_as_dueno`). **(L)**
3. **Notificación al crear reserva ausente** (cliente y dueño) — las plantillas ya existen, solo falta cablear. **(S)**
4. **Sesión insegura:** sin `session_regenerate_id`, cookies sin HttpOnly/Secure/SameSite. **(S)**
5. **Sin rate limiting** en login/registro/reservas (fuerza bruta + spam de pendientes que bloquean slots). **(M)**
6. **Reservas pendientes sin TTL** → acaparan horarios indefinidamente. **(M)**
7. **Bug de esquema en `api/predio_publico.php`** (columnas inexistentes, oculto por `error_reporting(0)`). **(S)**
8. **Fuga de PII de clientes entre tenants** (`buscar_clientes` sin scope de tenant). **(L)**
9. **SQL por interpolación** en casi todas las rutas (mitigado pero frágil) → migrar a prepared statements como estándar. **(XL)**
10. **APIs filtran `mysqli_error()` crudo** al cliente. **(S)**
11. **Migraciones de billing no versionadas** (nacen de `CREATE IF NOT EXISTS` en runtime). **(S)**

---

## 4. Roadmap priorizado (3 olas)

### 🌊 Ola 1 — Fundaciones: que no se rompa y que dé confianza
*Objetivo: cerrar bugs, seguridad base y el loop de comunicación. Casi todo S/M, alto retorno.*
- Notificar al **crear** reserva: push + email al cliente ("recibida") y al dueño/encargado ("nueva reserva"). *(S, plantillas ya existen)* — Cliente/Empleado/Dueño
- **Recuperar contraseña** (token por email + página de reset). *(M)* — Cliente
- Fix **cancelar reserva confirmada** + `ACTIVO=0` en cancelación del cliente. *(S)* — Cliente
- Fix **walk-in / buscar clientes 403** para staff. *(S)* — Empleado
- Disponibilidad correcta: descontar `turno_fijo` y `cierre_cancha` en `buscar_canchas`. *(S/M)* — Cliente
- Validación server-side de **hora pasada** y cancha/complejo activo al crear. *(S)* — Cliente
- Seguridad base: **credenciales fuera de `conn.php`**, `session_regenerate_id` + flags de cookie, sanitizar errores JSON, borrar cookie en logout. *(S)* — Transversal
- **CSRF** en POST + **rate limiting** en login/registro/reservas. *(L)* — Transversal
- **TTL de reservas pendientes** (expiración + job que libera el slot). *(M)* — Transversal
- Fix **ocupación** de reportes (franjas reales, no `dias*9`). *(S)* — Dueño
- Enforcement de suscripción: dueño vencido **deja de recibir reservas** y se bloquea su acceso. *(M)* — Plataforma
- **Tenantizar `buscar_clientes`** (cortar fuga de PII). *(L)* — Dueño/Seguridad
- Versionar migraciones de billing en `/sql`. *(S)* — Plataforma

### 🌊 Ola 2 — Monetización + diferenciadores
*Objetivo: cobrar, proteger del no-show, y dar herramientas reales de mostrador.*
- **Pago online con MercadoPago** (seña/turno): Checkout, webhooks, persistir en `pago`, estado de pago en la reserva. *(XL)* — Cliente/Dueño
- **Cierre de caja / arqueo** por empleado (totales por medio de pago, jornada). *(L)* — Empleado/Dueño
- **Permisos granulares** encargado vs empleado (capabilities: cobrar/cancelar/reportes). *(L)* — Empleado/Dueño
- **Fotos** de predio/canchas (columna + upload + galería en landing). *(L)* — Dueño/Cliente
- **Recordatorios de turno** T-24h/T-2h (cron + plantilla). *(M)* — Cliente
- **Check-in / no-show** (nuevos estados + acción del staff). *(M)* — Empleado
- **CRM de clientes + lista negra** (historial, frecuentes, morosos). *(L)* — Dueño
- **Pricing dinámico** (pico/finde/feriado). *(M)* — Dueño
- **Reembolsos / reverso de pago** al cancelar. *(M)* — Dueño
- **Reseñas/ratings** de predios (solo quienes jugaron). *(L)* — Cliente

### 🌊 Ola 3 — Escala, plataforma y socios
*Objetivo: que el SaaS crezca solo y soporte el modelo de membresías.*
- **Self-service de alta de dueños** + trial + landing de pricing. *(L)* — Plataforma
- **Tiers de plan con límites y enforcement** (predios/canchas/staff/reservas). *(L)* — Plataforma
- **Cron real / dunning** automático + cobro recurrente de plataforma (MercadoPago suscripciones). *(XL)* — Plataforma
- **Auditoría de impersonación** del SuperAdmin. *(M)* — Plataforma/Seguridad
- **Gestión de socios** (sobre los tipos de plan por predio: socio con plan activo → vencimientos → cobro recurrente → carnet/acceso). *(XL)* — Dueño/Cliente
- **Facturación AFIP / comprobantes**. *(L)* — Dueño/Plataforma
- **Marketplace**: descubrimiento por cercanía (lat/lng) + reserva inline en landing. *(L)* — Cliente
- Centro de notificaciones in-app + log de push. *(M)* — Transversal
- Migración progresiva a **prepared statements**. *(XL)* — Seguridad

---

## 5. Preguntas abiertas para definir el plan

Estas son las decisiones de producto/negocio que necesito para fijar prioridades (las técnicas las decido yo):

1. **Arranque:** ¿empezamos por la **Ola 1 (fundaciones: bugs + seguridad + notificaciones)** antes de features grandes, o querés priorizar ya el **pago online**?
2. **Seña:** ¿el pago online de la seña será **obligatorio** para garantizar el turno, **opcional**, o seguimos **presencial** por ahora con el pago online como agregado?
3. **Política de cancelación / no-show:** ¿ventana mínima de cancelación (ej. 24h/12h)? ¿la seña se retiene si cancela tarde o no se presenta?
4. **Marketplace vs herramienta de gestión:** ¿el foco es que el cliente **descubra predios por zona/reseñas** (prioriza fotos + reseñas + geo), o es una **herramienta para predios con clientela propia** (prioriza caja + permisos + CRM)?
5. **Permisos del empleado raso (4):** ¿puede **cancelar reservas y mover plata**, o solo **confirmar y cobrar**?
6. **Cierre de caja:** ¿necesita **fondo inicial, vueltos y egresos** (kiosco/insumos), o alcanza con **totalizar lo cobrado por empleado por día**?
7. **Alta de dueños:** ¿**self-service** con trial y elección de plan, o **alta manual** por el SuperAdmin (comercial)?
8. **Tiers de plan de plataforma:** ¿qué **límites** diferencian los planes (cantidad de predios / canchas / staff / reservas-mes / features premium)?
9. **Facturación fiscal (AFIP):** ¿hace falta desde el MVP comercial o se maneja por fuera por ahora?
10. **WhatsApp:** ¿querés integrar **WhatsApp Business API** (hoy es link manual) dado que el rubro lo usa intensivamente?
11. **`api/predio_publico.php`:** ¿es código vivo (lo usás) o lo borramos? Tiene un bug de esquema oculto.

#!/bin/bash
# Suite funcional end-to-end de LaCanchita (token-aware: CSRF activo)
B=http://127.0.0.1:8088
DB="${DB_NAME:-lacanchita_test}"   # misma base que usa la app (DB_NAME); NUNCA la de dev
J=$(mktemp -d)
mkdir -p $J; rm -f $J/*.jar
PASS=0; FAIL=0
MANANA=$(date -d "+1 day" +%Y-%m-%d)

ck() { if echo "$2" | grep -q "$3"; then PASS=$((PASS+1)); echo "PASS: $1";
  else FAIL=$((FAIL+1)); echo "FAIL: $1"; echo "   esperaba: $3"; echo "   obtuve:   $(echo "$2" | head -c 300)"; fi; }

# login <jar> <email>  → deja sesión + siembra token; devuelve el token CSRF
login() {
  curl -s -c $1 -X POST $B/api/login_ajax.php -H 'Content-Type: application/json' \
    -d "{\"username\":\"$2\",\"password\":\"test1234\"}" >/dev/null
  curl -s -b $1 -c $1 "$B/index.php" | grep -oP 'CSRF_TOKEN\s*=\s*"\K[a-f0-9]{64}' | head -1
}
# fpost <jar> <tok> <url> <formdata>   (form-encoded, con header CSRF)
fpost() { curl -s -b $1 -H "X-CSRF-Token: $2" -X POST "$B/$3" -d "$4"; }
# jpost <jar> <tok> <url> <json>       (json body, con header CSRF)
jpost() { curl -s -b $1 -H "X-CSRF-Token: $2" -H 'Content-Type: application/json' -X POST "$B/$3" -d "$4"; }

echo "══ AUTH ══"
ck "login clave incorrecta rechazado" "$(curl -s -X POST $B/api/login_ajax.php -H 'Content-Type: application/json' -d '{"username":"cliente@test.com","password":"MAL"}')" '"ok":false'
ck "registro nuevo cliente" "$(curl -s -X POST $B/api/register_ajax.php -H 'Content-Type: application/json' -d '{"nombre":"Nuevo","apellido":"Usuario","dni":"20000001","email":"nuevo@test.com","telefono":"2210000099","password":"test1234","password2":"test1234"}')" '"ok":true'
ck "registro email duplicado rechazado" "$(curl -s -X POST $B/api/register_ajax.php -H 'Content-Type: application/json' -d '{"nombre":"Dup","apellido":"L","dni":"20000002","email":"cliente@test.com","telefono":"2210000098","password":"test1234","password2":"test1234"}')" '"ok":false'
TC=$(login $J/cli.jar cliente@test.com); ck "login cliente + token" "$TC" '^[a-f0-9]\{64\}$'
TD=$(login $J/due.jar dueno@test.com);   ck "login dueño + token" "$TD" '^[a-f0-9]\{64\}$'
TE=$(login $J/enc.jar enc@test.com)
TM=$(login $J/emp.jar emp@test.com)
TS=$(login $J/sa.jar sa@test.com)

echo "══ CSRF ══"
ck "POST autenticado SIN token → 403" "$(curl -s -b $J/due.jar -X POST $B/view/maquetaAdmin/api/planes.php -d 'action=crear&complejo_id=1&nombre=X&precio=1&periodo=mensual')" 'seguridad'
ck "POST con token INVÁLIDO → 403" "$(fpost $J/due.jar deadbeef $B'nope' 'x=1'; fpost $J/due.jar deadbeef view/maquetaAdmin/api/planes.php 'action=crear&complejo_id=1&nombre=X&precio=1&periodo=mensual')" 'seguridad'
ck "GET no requiere token" "$(curl -s -b $J/due.jar "$B/view/maquetaAdmin/api/canchas.php?action=listar")" '"ok":true'

echo "══ HOME / BÚSQUEDA PÚBLICA ══"
R=$(curl -s "$B/index.php"); ck "home renderiza" "$R" "La Canchita"; ck "home sección beneficios" "$R" "Por qué La Canchita"; ck "home inyecta CSRF token" "$R" "CSRF_TOKEN"
R=$(curl -s "$B/api/buscar_canchas.php?localidad=1&fecha=$MANANA")
ck "buscador ok" "$R" '"ok":true'; ck "buscador Cancha 1" "$R" 'Cancha 1'; ck "slot 10:00 libre" "$R" '"hora":"10:00","libre":true'
ck "landing predio" "$(curl -s "$B/predio.php?id=1")" "Predio Test"

echo "══ RESERVA PÚBLICA (cliente) ══"
R=$(jpost $J/cli.jar $TC api/reservar_publico.php "{\"cancha_id\":1,\"fecha\":\"$MANANA\",\"hora\":\"10:00\"}")
ck "reservar 10:00" "$R" '"ok":true'
RID=$(echo "$R" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo $d["data"]["RESERVA_ID"]??0;')
ck "doble reserva rechazada" "$(jpost $J/cli.jar $TC api/reservar_publico.php "{\"cancha_id\":1,\"fecha\":\"$MANANA\",\"hora\":\"10:00\"}")" '"ok":false'
ck "fecha pasada rechazada" "$(jpost $J/cli.jar $TC api/reservar_publico.php '{"cancha_id":1,"fecha":"2020-01-01","hora":"10:00"}')" 'pasad'
ck "sin sesión NO reserva" "$(curl -s -X POST $B/api/reservar_publico.php -H 'Content-Type: application/json' -d "{\"cancha_id\":1,\"fecha\":\"$MANANA\",\"hora\":\"11:00\"}")" '"ok":false'
ck "slot 10:00 ahora ocupado" "$(curl -s "$B/api/buscar_canchas.php?localidad=1&fecha=$MANANA")" '"hora":"10:00","libre":false'

echo "══ PANEL CLIENTE ══"
ck "mis_reservas" "$(curl -s -b $J/cli.jar "$B/view/maquetaCliente/api/reservas.php?action=mis_reservas")" 'Cancha 1'
ck "disponibilidad 10:00 no disp" "$(curl -s -b $J/cli.jar "$B/view/maquetaCliente/api/reservas.php?action=disponibilidad&cancha_id=1&fecha=$MANANA")" '"disponible":false'
R=$(fpost $J/cli.jar $TC view/maquetaCliente/api/reservas.php "action=crear&cancha_id=1&franja_id=2&fecha=$MANANA")
ck "cliente crea 2da reserva" "$R" '"ok":true'
RID2=$(echo "$R" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo $d["data"]["RESERVA_ID"]??0;')
ck "cliente cancela" "$(fpost $J/cli.jar $TC view/maquetaCliente/api/reservas.php "action=cancelar&reserva_id=$RID2")" '"ok":true'
ck "slot 11:00 liberado" "$(curl -s -b $J/cli.jar "$B/view/maquetaCliente/api/reservas.php?action=disponibilidad&cancha_id=1&fecha=$MANANA")" '"FRANJA_HORA_INICIO":"11:00:00".*"disponible":true'
ck "perfil update" "$(fpost $J/cli.jar $TC view/maquetaAdmin/api/perfil.php "action=update&nombre=Carla&apellido=Clienta&email=cliente@test.com&telefono=2210000005")" '"ok":true'

echo "══ PANEL ADMIN (dueño) ══"
ck "dueño lista reservas" "$(curl -s -b $J/due.jar "$B/view/maquetaAdmin/api/reservas.php?action=listar&fecha=$MANANA")" '"ok":true'
ck "dueño confirma" "$(fpost $J/due.jar $TD view/maquetaAdmin/api/reservas.php "action=confirmar&reserva_id=$RID")" '"ok":true'
ck "cobra seña 2000" "$(fpost $J/due.jar $TD view/maquetaAdmin/api/reservas.php "action=registrar_pago&reserva_id=$RID&monto=2000&tipo=sena&medio=efectivo")" '"ok":true'
ck "sobre-cobro rechazado" "$(fpost $J/due.jar $TD view/maquetaAdmin/api/reservas.php "action=registrar_pago&reserva_id=$RID&monto=99999&tipo=parcial&medio=efectivo")" 'excede'
ck "saldo 8000 cobrado" "$(fpost $J/due.jar $TD view/maquetaAdmin/api/reservas.php "action=registrar_pago&reserva_id=$RID&monto=8000&tipo=total&medio=transferencia")" '"ok":true'

echo "══ TENANT ISOLATION ══"
R=$(curl -s -b $J/due.jar "$B/view/maquetaAdmin/api/canchas.php?action=listar")
if echo "$R" | grep -q 'Cancha Ajena'; then FAIL=$((FAIL+1)); echo "FAIL: aislamiento (ve ajena)"; else PASS=$((PASS+1)); echo "PASS: aislamiento canchas"; fi
ck "dueño NO reserva en cancha ajena" "$(fpost $J/due.jar $TD view/maquetaAdmin/api/reservas.php "action=crear_admin&cancha_id=2&franja_id=4&fecha=$MANANA&usuario_id=5&estado=confirmada")" '"ok":false'

echo "══ STAFF (encargado) ══"
ck "encargado lista agenda" "$(curl -s -b $J/enc.jar "$B/view/maquetaAdmin/api/reservas.php?action=listar&fecha=$MANANA")" '"ok":true'
ck "encargado busca clientes (fix 403)" "$(curl -s -b $J/enc.jar "$B/view/maquetaAdmin/api/usuarios.php?action=buscar_clientes&q=Carla")" '"ok":true'
ck "encargado walk-in (fix 403)" "$(fpost $J/enc.jar $TE view/maquetaAdmin/api/usuarios.php "action=crear_cliente_rapido&nombre=Walk&apellido=In&telefono=2219999999")" '"ok":true'
ck "pendientes_count staff" "$(curl -s -b $J/enc.jar "$B/view/maquetaAdmin/api/reservas.php?action=pendientes_count")" '"ok":true'
ck "empleado NO lista staff" "$(curl -s -b $J/emp.jar "$B/view/maquetaAdmin/api/usuarios.php?action=listar_staff")" 'permisos'

echo "══ PLANES ══"
ck "dueño crea tipo de plan" "$(fpost $J/due.jar $TD view/maquetaAdmin/api/planes.php "action=crear&complejo_id=1&nombre=Abono Mensual&precio=50000&periodo=mensual&creditos=0")" '"ok":true'
ck "encargado ve planes" "$(curl -s -b $J/enc.jar "$B/view/maquetaAdmin/api/planes.php?action=listar")" 'Abono Mensual'
ck "dueño NO crea plan ajeno" "$(fpost $J/due.jar $TD view/maquetaAdmin/api/planes.php "action=crear&complejo_id=2&nombre=Intruso&precio=1&periodo=mensual")" 'Sin acceso'

echo "══ ONBOARDING ATÓMICO ══"
TD2=$(login $J/due2.jar dueno2@test.com)
ck "onboarding completo OK" "$(jpost $J/due2.jar $TD2 view/maquetaAdmin/api/onboarding_completo.php "{\"predio\":{\"nombre\":\"Predio Onb\",\"direccion\":\"Calle 3\",\"localidad_id\":1},\"cancha\":{\"nombre\":\"Cancha Onb\",\"tipo_cancha_id\":1},\"franjas\":[{\"ini\":\"09:00\",\"fin\":\"10:00\",\"dias\":[1,2,3],\"precio\":5000}]}")" '"ok":true'
ck "onboarding precio 0 falla" "$(jpost $J/due2.jar $TD2 view/maquetaAdmin/api/onboarding_completo.php "{\"predio\":{\"nombre\":\"Predio Roto\",\"direccion\":\"C4\",\"localidad_id\":1},\"cancha\":{\"nombre\":\"C\",\"tipo_cancha_id\":1},\"franjas\":[{\"ini\":\"09:00\",\"fin\":\"10:00\",\"dias\":[1],\"precio\":0}]}")" '"ok":false'
N=$(mysql -uroot "$DB" -N -e "SELECT COUNT(*) FROM complejo WHERE COMPLEJO_NOMBRE='Predio Roto'")
if [ "$N" = "0" ]; then PASS=$((PASS+1)); echo "PASS: rollback atómico"; else FAIL=$((FAIL+1)); echo "FAIL: predio huérfano"; fi

echo "══ ENFORCEMENT DE MORA ══"
mysql -uroot "$DB" -e "INSERT INTO suscripcion_plataforma (USUARIOS_ID,PLAN_NOMBRE,PLAN_PRECIO,ESTADO) VALUES (2,'Estándar',30000,'vencido') ON DUPLICATE KEY UPDATE ESTADO='vencido'"
ck "predio moroso no recibe reservas" "$(jpost $J/cli.jar $TC api/reservar_publico.php "{\"cancha_id\":1,\"fecha\":\"$MANANA\",\"hora\":\"12:00\"}")" 'no está recibiendo'
ck "dueño moroso no escribe (402)" "$(fpost $J/due.jar $TD view/maquetaAdmin/api/canchas.php "action=crear&nombre=Nueva&tipo_cancha_id=1&complejo_id=1")" 'solo lectura'
ck "dueño moroso SÍ lee" "$(curl -s -b $J/due.jar "$B/view/maquetaAdmin/api/canchas.php?action=listar")" '"ok":true'
mysql -uroot "$DB" -e "UPDATE suscripcion_plataforma SET ESTADO='activo' WHERE USUARIOS_ID=2"
ck "regularizado vuelve a recibir" "$(jpost $J/cli.jar $TC api/reservar_publico.php "{\"cancha_id\":1,\"fecha\":\"$MANANA\",\"hora\":\"12:00\"}")" '"ok":true'

echo "══ RESET DE CONTRASEÑA ══"
# el formulario clásico necesita cookie de sesión + token del propio form
RCJ=$J/reset.jar
CTOK=$(curl -s -c $RCJ "$B/recuperar_contrasena.php" | grep -oP 'name="csrf" value="\K[a-f0-9]{64}')
ck "form reset trae token" "$CTOK" '^[a-f0-9]\{64\}$'
ck "pedido reset responde" "$(curl -s -b $RCJ -X POST $B/recuperar_contrasena.php -d "csrf=$CTOK&email=cliente@test.com")" 'Revisá tu correo'
ck "pedido reset SIN token rechazado" "$(curl -s -b $RCJ -X POST $B/recuperar_contrasena.php -d "email=cliente@test.com")" 'Sesión expirada'
TOKEN=deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef
TH=$(php -r "echo hash('sha256','$TOKEN');")
mysql -uroot "$DB" -e "DELETE FROM password_reset WHERE USUARIOS_ID=5; INSERT INTO password_reset (USUARIOS_ID,TOKEN_HASH,EXPIRA) VALUES (5,'$TH',DATE_ADD(NOW(),INTERVAL 1 HOUR))"
RSJ=$J/setpass.jar
STOK=$(curl -s -c $RSJ "$B/restablecer_contrasena.php?token=$TOKEN" | grep -oP 'name="csrf" value="\K[a-f0-9]{64}')
ck "página reset token válido" "$(curl -s "$B/restablecer_contrasena.php?token=$TOKEN")" 'Creá tu nueva'
ck "reset ejecuta (302)" "$(curl -s -b $RSJ -o /dev/null -w '%{http_code}' -X POST $B/restablecer_contrasena.php -d "csrf=$STOK&token=$TOKEN&password=nueva1234&password2=nueva1234")" '302'
ck "login con clave nueva" "$(curl -s -X POST $B/api/login_ajax.php -H 'Content-Type: application/json' -d '{"username":"cliente@test.com","password":"nueva1234"}')" '"ok":true'
ck "token ya usado invalida" "$(curl -s "$B/restablecer_contrasena.php?token=$TOKEN")" 'inválido'

echo "══ SUPERADMIN / PLATAFORMA ══"
ck "SA stats" "$(curl -s -b $J/sa.jar "$B/view/maquetaSuperAdmin/api/clientes.php?action=stats")" '"ok":true'
ck "SA registra cobro" "$(fpost $J/sa.jar $TS view/maquetaSuperAdmin/api/clientes.php "action=registrar_cobro&usuarios_id=2&monto=30000")" '"ok":true'
PROX=$(mysql -uroot "$DB" -N -e "SELECT PROXIMO_COBRO >= CURDATE() FROM suscripcion_plataforma WHERE USUARIOS_ID=2")
if [ "$PROX" = "1" ]; then PASS=$((PASS+1)); echo "PASS: PROXIMO_COBRO futuro"; else FAIL=$((FAIL+1)); echo "FAIL: PROXIMO_COBRO pasado"; fi
ck "cliente NO accede API SA" "$(curl -s -b $J/cli.jar "$B/view/maquetaSuperAdmin/api/clientes.php?action=stats")" 'permisos'

echo "══ GEO / CATÁLOGO ══"
ck "geo provincias" "$(curl -s -b $J/due.jar "$B/view/maquetaAdmin/api/geo.php?action=provincias")" 'Buenos Aires'
ck "catálogo tipos cancha" "$(curl -s -b $J/due.jar "$B/view/maquetaAdmin/api/catalogo.php?action=listar&tabla=tipo_cancha")" "tbol 5"
ck "catálogo tabla no permitida" "$(curl -s -b $J/due.jar "$B/view/maquetaAdmin/api/catalogo.php?action=listar&tabla=usuarios")" "no v"

echo "══ ROLES / CAPACIDADES ══"
# Reportes: encargado (3) SÍ, empleado (4) NO
ck "empleado reportes 403" "$(curl -s -b $J/emp.jar "$B/view/maquetaAdmin/api/reportes.php?action=resumen")" 'permisos'
ck "empleado export_reportes 403" "$(curl -s -b $J/emp.jar "$B/view/maquetaAdmin/api/export_reportes.php")" 'permisos'
ck "empleado cierres 403" "$(curl -s -b $J/emp.jar "$B/view/maquetaAdmin/api/cierres.php?action=listar")" 'permisos'
ck "encargado reportes ok" "$(curl -s -b $J/enc.jar "$B/view/maquetaAdmin/api/reportes.php?action=resumen")" '"ok":true'

echo "══ RECORDATORIOS DE TURNO ══"
# Reserva "inminente" (~90 min → ventana 2h) y "previa" (~5 h → ventana 24h) para el cliente
F1=$(date -d "+90 min" +%Y-%m-%d);  H1=$(date -d "+90 min" +%H:%M:%S);  H1F=$(date -d "+150 min" +%H:%M:%S)
F2=$(date -d "+5 hours" +%Y-%m-%d); H2=$(date -d "+5 hours" +%H:%M:%S);  H2F=$(date -d "+6 hours" +%H:%M:%S)
mysql -uroot "$DB" -e "INSERT INTO reserva (CANCHA_ID,FRANJA_ID,USUARIOS_ID,RESERVA_FECHA,RESERVA_HORA_INICIO,RESERVA_HORA_FIN,RESERVA_PRECIO,RESERVA_ESTADO) SELECT 1,1,USUARIOS_ID,'$F1','$H1','$H1F',1000,'confirmada' FROM usuarios WHERE USUARIOS_EMAIL='cliente@test.com'"
mysql -uroot "$DB" -e "INSERT INTO reserva (CANCHA_ID,FRANJA_ID,USUARIOS_ID,RESERVA_FECHA,RESERVA_HORA_INICIO,RESERVA_HORA_FIN,RESERVA_PRECIO,RESERVA_ESTADO) SELECT 1,1,USUARIOS_ID,'$F2','$H2','$H2F',1000,'confirmada' FROM usuarios WHERE USUARIOS_EMAIL='cliente@test.com'"
CRON1=$(DB_NAME=$DB php cron/recordatorios_turno.php)
ck "cron responde ok" "$CRON1" '"ok":true'
N1=$(mysql -uroot "$DB" -N -e "SELECT COUNT(*) FROM recordatorio_turno")
if [ "${N1:-0}" -ge 2 ]; then PASS=$((PASS+1)); echo "PASS: recordatorios registrados ($N1)"; else FAIL=$((FAIL+1)); echo "FAIL: esperaba >=2 recordatorios, obtuve ${N1:-0}"; fi
ck "recordatorio inminente (2h) registrado" "$(mysql -uroot "$DB" -N -e "SELECT TIPO FROM recordatorio_turno WHERE TIPO='2h' LIMIT 1")" '2h'
ck "recordatorio previo (24h) registrado" "$(mysql -uroot "$DB" -N -e "SELECT TIPO FROM recordatorio_turno WHERE TIPO='24h' LIMIT 1")" '24h'
CRON2=$(DB_NAME=$DB php cron/recordatorios_turno.php)
ck "cron 2da corrida no reenvía (total 0)" "$CRON2" '"total":0'
N2=$(mysql -uroot "$DB" -N -e "SELECT COUNT(*) FROM recordatorio_turno")
if [ "$N2" = "$N1" ]; then PASS=$((PASS+1)); echo "PASS: idempotente (sin filas nuevas)"; else FAIL=$((FAIL+1)); echo "FAIL: idempotencia rota $N1 -> $N2"; fi

echo ""; echo "════════════════════════════"; echo "TOTAL: PASS=$PASS FAIL=$FAIL"

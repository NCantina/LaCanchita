#!/bin/bash
# Suite funcional end-to-end de LaCanchita
B=http://127.0.0.1:8088
J=$(mktemp -d)
mkdir -p $J; rm -f $J/*.jar
PASS=0; FAIL=0
MANANA=$(date -d "+1 day" +%Y-%m-%d)

ck() { # ck "nombre" "resultado" "esperado_substring"
  if echo "$2" | grep -q "$3"; then PASS=$((PASS+1)); echo "PASS: $1";
  else FAIL=$((FAIL+1)); echo "FAIL: $1"; echo "   esperaba: $3"; echo "   obtuve:   $(echo "$2" | head -c 300)"; fi
}

echo "══ AUTH ══"
R=$(curl -s -c $J/cli.jar -X POST $B/api/login_ajax.php -H 'Content-Type: application/json' -d '{"username":"cliente@test.com","password":"test1234"}')
ck "login cliente" "$R" '"ok":true'
R=$(curl -s -c $J/due.jar -X POST $B/api/login_ajax.php -H 'Content-Type: application/json' -d '{"username":"dueno@test.com","password":"test1234"}')
ck "login dueño" "$R" '"ok":true'
R=$(curl -s -c $J/enc.jar -X POST $B/api/login_ajax.php -H 'Content-Type: application/json' -d '{"username":"enc@test.com","password":"test1234"}')
ck "login encargado" "$R" '"ok":true'
R=$(curl -s -c $J/emp.jar -X POST $B/api/login_ajax.php -H 'Content-Type: application/json' -d '{"username":"emp@test.com","password":"test1234"}')
ck "login empleado" "$R" '"ok":true'
R=$(curl -s -c $J/sa.jar -X POST $B/api/login_ajax.php -H 'Content-Type: application/json' -d '{"username":"sa@test.com","password":"test1234"}')
ck "login SA" "$R" '"ok":true'
R=$(curl -s -X POST $B/api/login_ajax.php -H 'Content-Type: application/json' -d '{"username":"cliente@test.com","password":"MAL"}')
ck "login clave incorrecta rechazado" "$R" '"ok":false'
R=$(curl -s -X POST $B/api/register_ajax.php -H 'Content-Type: application/json' -d '{"nombre":"Nuevo","apellido":"Usuario","dni":"20000001","email":"nuevo@test.com","telefono":"2210000099","password":"test1234","password2":"test1234"}')
ck "registro nuevo cliente" "$R" '"ok":true'
R=$(curl -s -X POST $B/api/register_ajax.php -H 'Content-Type: application/json' -d '{"nombre":"Dup","apellido":"Licado","dni":"20000002","email":"cliente@test.com","telefono":"2210000098","password":"test1234","password2":"test1234"}')
ck "registro email duplicado rechazado" "$R" '"ok":false'

echo "══ HOME / BÚSQUEDA PÚBLICA ══"
R=$(curl -s "$B/index.php")
ck "home renderiza" "$R" "La Canchita"
ck "home tiene sección beneficios" "$R" "Por qué La Canchita"
R=$(curl -s "$B/api/buscar_canchas.php?localidad=1&fecha=$MANANA")
ck "buscador devuelve canchas" "$R" '"ok":true'
ck "buscador incluye Cancha 1" "$R" 'Cancha 1'
ck "buscador slot 10:00 libre" "$R" '"hora":"10:00","libre":true'
R=$(curl -s "$B/predio.php?id=1")
ck "landing predio renderiza" "$R" "Predio Test"

echo "══ RESERVA PÚBLICA (cliente) ══"
R=$(curl -s -b $J/cli.jar -X POST $B/api/reservar_publico.php -H 'Content-Type: application/json' -d "{\"cancha_id\":1,\"fecha\":\"$MANANA\",\"hora\":\"10:00\"}")
ck "reservar 10:00 mañana" "$R" '"ok":true'
RID=$(echo "$R" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo $d["data"]["RESERVA_ID"]??0;')
R=$(curl -s -b $J/cli.jar -X POST $B/api/reservar_publico.php -H 'Content-Type: application/json' -d "{\"cancha_id\":1,\"fecha\":\"$MANANA\",\"hora\":\"10:00\"}")
ck "doble reserva mismo slot rechazada" "$R" '"ok":false'
R=$(curl -s -b $J/cli.jar -X POST $B/api/reservar_publico.php -H 'Content-Type: application/json' -d '{"cancha_id":1,"fecha":"2020-01-01","hora":"10:00"}')
ck "reserva fecha pasada rechazada" "$R" 'pasad'
R=$(curl -s -X POST $B/api/reservar_publico.php -H 'Content-Type: application/json' -d "{\"cancha_id\":1,\"fecha\":\"$MANANA\",\"hora\":\"11:00\"}")
ck "reserva sin sesión rechazada" "$R" 'sesión'
R=$(curl -s "$B/api/buscar_canchas.php?localidad=1&fecha=$MANANA")
ck "slot 10:00 ahora ocupado" "$R" '"hora":"10:00","libre":false'

echo "══ PANEL CLIENTE ══"
R=$(curl -s -b $J/cli.jar "$B/view/maquetaCliente/api/reservas.php?action=mis_reservas")
ck "mis_reservas lista la reserva" "$R" 'Cancha 1'
R=$(curl -s -b $J/cli.jar "$B/view/maquetaCliente/api/reservas.php?action=disponibilidad&cancha_id=1&fecha=$MANANA")
ck "disponibilidad marca 10:00 no disponible" "$R" '"disponible":false'
R=$(curl -s -b $J/cli.jar -X POST $B/view/maquetaCliente/api/reservas.php -d "action=crear&cancha_id=1&franja_id=2&fecha=$MANANA")
ck "cliente crea 2da reserva (11:00)" "$R" '"ok":true'
RID2=$(echo "$R" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo $d["data"]["RESERVA_ID"]??0;')
R=$(curl -s -b $J/cli.jar -X POST $B/view/maquetaCliente/api/reservas.php -d "action=cancelar&reserva_id=$RID2")
ck "cliente cancela su reserva" "$R" '"ok":true'
R=$(curl -s -b $J/cli.jar "$B/view/maquetaCliente/api/reservas.php?action=disponibilidad&cancha_id=1&fecha=$MANANA")
ck "slot 11:00 liberado tras cancelar" "$R" '"FRANJA_ID":"2","FRANJA_HORA_INICIO":"11:00:00","FRANJA_HORA_FIN":"12:00:00","FRANJA_PRECIO":"10000.00","FRANJA_SENA":"2000.00","disponible":true'
R=$(curl -s -b $J/cli.jar -X POST $B/view/maquetaAdmin/api/perfil.php -d "action=update&nombre=Carla&apellido=Clienta&email=cliente@test.com&telefono=2210000005")
ck "perfil update" "$R" '"ok":true'

echo "══ PANEL ADMIN (dueño) ══"
R=$(curl -s -b $J/due.jar "$B/view/maquetaAdmin/api/reservas.php?action=listar&fecha=$MANANA")
ck "dueño lista reservas del día" "$R" '"ok":true'
R=$(curl -s -b $J/due.jar -X POST $B/view/maquetaAdmin/api/reservas.php -d "action=confirmar&reserva_id=$RID")
ck "dueño confirma reserva" "$R" '"ok":true'
R=$(curl -s -b $J/due.jar -X POST $B/view/maquetaAdmin/api/reservas.php -d "action=registrar_pago&reserva_id=$RID&monto=2000&tipo=sena&medio=efectivo")
ck "dueño cobra seña 2000" "$R" '"ok":true'
R=$(curl -s -b $J/due.jar -X POST $B/view/maquetaAdmin/api/reservas.php -d "action=registrar_pago&reserva_id=$RID&monto=99999&tipo=parcial&medio=efectivo")
ck "sobre-cobro rechazado" "$R" 'excede'
R=$(curl -s -b $J/due.jar -X POST $B/view/maquetaAdmin/api/reservas.php -d "action=registrar_pago&reserva_id=$RID&monto=8000&tipo=total&medio=transferencia")
ck "saldo restante 8000 cobrado" "$R" '"ok":true'

echo "══ TENANT ISOLATION ══"
R=$(curl -s -b $J/due.jar "$B/view/maquetaAdmin/api/canchas.php?action=listar")
if echo "$R" | grep -q 'Cancha Ajena'; then FAIL=$((FAIL+1)); echo "FAIL: aislamiento canchas (ve la ajena)"; else PASS=$((PASS+1)); echo "PASS: aislamiento canchas"; fi
R=$(curl -s -b $J/due.jar -X POST $B/view/maquetaAdmin/api/reservas.php -d "action=crear_admin&cancha_id=2&franja_id=4&fecha=$MANANA&usuario_id=5&estado=confirmada")
ck "dueño NO puede reservar en cancha ajena" "$R" '"ok":false'

echo "══ STAFF (encargado) ══"
R=$(curl -s -b $J/enc.jar "$B/view/maquetaAdmin/api/reservas.php?action=listar&fecha=$MANANA")
ck "encargado lista su agenda" "$R" '"ok":true'
R=$(curl -s -b $J/enc.jar "$B/view/maquetaAdmin/api/usuarios.php?action=buscar_clientes&q=Carla")
ck "encargado busca clientes (fix 403)" "$R" '"ok":true'
R=$(curl -s -b $J/enc.jar -X POST $B/view/maquetaAdmin/api/usuarios.php -d "action=crear_cliente_rapido&nombre=Walk&apellido=In&telefono=2219999999")
ck "encargado crea walk-in (fix 403)" "$R" '"ok":true'
R=$(curl -s -b $J/enc.jar "$B/view/maquetaAdmin/api/reservas.php?action=pendientes_count")
ck "pendientes_count staff responde" "$R" '"ok":true'
R=$(curl -s -b $J/emp.jar "$B/view/maquetaAdmin/api/usuarios.php?action=listar_staff")
ck "empleado NO puede listar staff" "$R" 'permisos'

echo "══ PLANES ══"
R=$(curl -s -b $J/due.jar -X POST $B/view/maquetaAdmin/api/planes.php -d "action=crear&complejo_id=1&nombre=Abono Mensual&precio=50000&periodo=mensual&creditos=0")
ck "dueño crea tipo de plan" "$R" '"ok":true'
R=$(curl -s -b $J/enc.jar "$B/view/maquetaAdmin/api/planes.php?action=listar")
ck "encargado ve planes del dueño" "$R" 'Abono Mensual'
R=$(curl -s -b $J/due.jar -X POST $B/view/maquetaAdmin/api/planes.php -d "action=crear&complejo_id=2&nombre=Plan Intruso&precio=1&periodo=mensual")
ck "dueño NO crea plan en predio ajeno" "$R" 'Sin acceso'

echo "══ ONBOARDING ATÓMICO (dueño2 sin predios... usa dueño2) ══"
R=$(curl -s -c $J/due2.jar -X POST $B/api/login_ajax.php -H 'Content-Type: application/json' -d '{"username":"dueno2@test.com","password":"test1234"}')
N_ANTES=$(mysql -uroot lacanchita -N -e "SELECT COUNT(*) FROM complejo")
R=$(curl -s -b $J/due2.jar -X POST $B/view/maquetaAdmin/api/onboarding_completo.php -H 'Content-Type: application/json' -d "{\"predio\":{\"nombre\":\"Predio Onb\",\"direccion\":\"Calle 3\",\"localidad_id\":1},\"cancha\":{\"nombre\":\"Cancha Onb\",\"tipo_cancha_id\":1},\"franjas\":[{\"ini\":\"09:00\",\"fin\":\"10:00\",\"dias\":[1,2,3],\"precio\":5000}]}")
ck "onboarding completo OK" "$R" '"ok":true'
R=$(curl -s -b $J/due2.jar -X POST $B/view/maquetaAdmin/api/onboarding_completo.php -H 'Content-Type: application/json' -d "{\"predio\":{\"nombre\":\"Predio Roto\",\"direccion\":\"Calle 4\",\"localidad_id\":1},\"cancha\":{\"nombre\":\"C\",\"tipo_cancha_id\":1},\"franjas\":[{\"ini\":\"09:00\",\"fin\":\"10:00\",\"dias\":[1],\"precio\":0}]}")
ck "onboarding con precio 0 falla" "$R" '"ok":false'
N_DESP=$(mysql -uroot lacanchita -N -e "SELECT COUNT(*) FROM complejo WHERE COMPLEJO_NOMBRE='Predio Roto'")
if [ "$N_DESP" = "0" ]; then PASS=$((PASS+1)); echo "PASS: rollback atómico (sin predio huérfano)"; else FAIL=$((FAIL+1)); echo "FAIL: quedó predio huérfano"; fi

echo "══ ENFORCEMENT DE MORA ══"
mysql -uroot lacanchita -e "INSERT INTO suscripcion_plataforma (USUARIOS_ID, PLAN_NOMBRE, PLAN_PRECIO, ESTADO) VALUES (2,'Estándar',30000,'vencido') ON DUPLICATE KEY UPDATE ESTADO='vencido'"
R=$(curl -s -b $J/cli.jar -X POST $B/api/reservar_publico.php -H 'Content-Type: application/json' -d "{\"cancha_id\":1,\"fecha\":\"$MANANA\",\"hora\":\"12:00\"}")
ck "predio de moroso no recibe reservas" "$R" 'no está recibiendo'
R=$(curl -s -b $J/due.jar -X POST $B/view/maquetaAdmin/api/canchas.php -d "action=crear&nombre=NuevaCancha&tipo_cancha_id=1&complejo_id=1")
ck "dueño moroso no puede escribir (402)" "$R" 'solo lectura'
R=$(curl -s -b $J/due.jar "$B/view/maquetaAdmin/api/canchas.php?action=listar")
ck "dueño moroso SÍ puede leer" "$R" '"ok":true'
mysql -uroot lacanchita -e "UPDATE suscripcion_plataforma SET ESTADO='activo' WHERE USUARIOS_ID=2"
R=$(curl -s -b $J/cli.jar -X POST $B/api/reservar_publico.php -H 'Content-Type: application/json' -d "{\"cancha_id\":1,\"fecha\":\"$MANANA\",\"hora\":\"12:00\"}")
ck "al regularizar vuelve a recibir reservas" "$R" '"ok":true'

echo "══ RESET DE CONTRASEÑA ══"
R=$(curl -s -X POST $B/recuperar_contrasena.php -d "email=cliente@test.com")
ck "pedido de reset responde genérico" "$R" 'Revisá tu correo'
TOKEN=deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef
TH=$(php -r "echo hash('sha256','$TOKEN');")
mysql -uroot lacanchita -e "INSERT INTO password_reset (USUARIOS_ID, TOKEN_HASH, EXPIRA) VALUES (5,'$TH', DATE_ADD(NOW(), INTERVAL 1 HOUR))"
R=$(curl -s "$B/restablecer_contrasena.php?token=$TOKEN")
ck "página de reset con token válido" "$R" 'Creá tu nueva contraseña'
R=$(curl -s -X POST $B/restablecer_contrasena.php -d "token=$TOKEN&password=nueva1234&password2=nueva1234" -o /dev/null -w '%{http_code}')
ck "reset ejecuta y redirige (302)" "$R" '302'
R=$(curl -s -X POST $B/api/login_ajax.php -H 'Content-Type: application/json' -d '{"username":"cliente@test.com","password":"nueva1234"}')
ck "login con contraseña nueva" "$R" '"ok":true'
R=$(curl -s "$B/restablecer_contrasena.php?token=$TOKEN")
ck "token ya usado invalida" "$R" 'inválido'

echo "══ SUPERADMIN / PLATAFORMA ══"
R=$(curl -s -b $J/sa.jar "$B/view/maquetaSuperAdmin/api/clientes.php?action=stats")
ck "SA stats" "$R" '"ok":true'
R=$(curl -s -b $J/sa.jar -X POST $B/view/maquetaSuperAdmin/api/clientes.php -d "action=registrar_cobro&usuarios_id=2&monto=30000")
ck "SA registra cobro" "$R" '"ok":true'
PROX=$(mysql -uroot lacanchita -N -e "SELECT PROXIMO_COBRO >= CURDATE() FROM suscripcion_plataforma WHERE USUARIOS_ID=2")
if [ "$PROX" = "1" ]; then PASS=$((PASS+1)); echo "PASS: PROXIMO_COBRO en el futuro (fix max)"; else FAIL=$((FAIL+1)); echo "FAIL: PROXIMO_COBRO quedó en el pasado"; fi
R=$(curl -s -b $J/cli.jar "$B/view/maquetaSuperAdmin/api/clientes.php?action=stats")
ck "cliente NO accede a API de SA" "$R" 'permisos'

echo "══ GEO / CATÁLOGO ══"
R=$(curl -s -b $J/due.jar "$B/view/maquetaAdmin/api/geo.php?action=provincias")
ck "geo provincias" "$R" 'Buenos Aires'
R=$(curl -s -b $J/due.jar "$B/view/maquetaAdmin/api/catalogo.php?action=listar&tabla=tipo_cancha")
ck "catálogo tipos de cancha" "$R" "tbol 5"
R=$(curl -s -b $J/due.jar "$B/view/maquetaAdmin/api/catalogo.php?action=listar&tabla=usuarios")
ck "catálogo tabla no permitida rechazada" "$R" "no v"

echo ""
echo "════════════════════════════"
echo "TOTAL: PASS=$PASS FAIL=$FAIL"

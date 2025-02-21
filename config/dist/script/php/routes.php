<?php
require_once 'conn.php'; // Conexión a la base de datos

$action = $_REQUEST['action'] ?? null;

switch ($action) {
    case 'insertDatosPredio':
        $nombre = mysqli_real_escape_string($link, $_REQUEST['nombrePredio']);
        $direccion = mysqli_real_escape_string($link, $_REQUEST['direccionPredio']);
        $telefono = mysqli_real_escape_string($link, $_REQUEST['telefonoPredio']);
        $wsp = mysqli_real_escape_string($link, $_REQUEST['wspPredio']);
        $localidad = mysqli_real_escape_string($link, $_REQUEST['localidadPredio']);

        if (!$nombre || !$direccion || !$telefono || !$localidad || !$wsp) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        if (!preg_match('/^\d+$/', $wsp)) {
            echo json_encode(['success' => false, 'message' => 'El número de WhatsApp debe contener solo números.']);
            exit;
        }

        $sql = "CALL sp_insertar_predio('$nombre', '$direccion', '$telefono', '$wsp', '$localidad')";
        $rs = mysqli_query($link, $sql);

        echo json_encode($rs ? ['success' => true] : ['success' => false, 'message' => mysqli_error($link)]);
        break;

    case 'insertCancha':
        $descripcion = mysqli_real_escape_string($link, $_REQUEST['descripcion']);

        if (!$descripcion) {
            echo json_encode(['success' => false, 'message' => 'El nombre de la cancha es obligatorio.']);
            exit;
        }

        $sql = "CALL sp_insertar_cancha('$descripcion', 1, 1)";
        $rs = mysqli_query($link, $sql);

        echo json_encode($rs ? ['success' => true] : ['success' => false, 'message' => mysqli_error($link)]);
        break;

    case 'getCanchas':
        $sql = "SELECT CANCHAS_ID, CANCHAS_DESCRIPCION, CANCHAS_ACTIVO FROM canchas";
        $rs = mysqli_query($link, $sql);
        $data = [];

        while ($row = mysqli_fetch_assoc($rs)) {
            $data[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'updateCancha':
        $id = mysqli_real_escape_string($link, $_REQUEST['id']);
        $descripcion = mysqli_real_escape_string($link, $_REQUEST['descripcion']);
        $activo = mysqli_real_escape_string($link, $_REQUEST['activo']);

        if (!$id || !$descripcion) {
            echo json_encode(['success' => false, 'message' => 'El ID y la descripción son obligatorios.']);
            exit;
        }

        $sql = "CALL sp_actualizar_cancha('$id', '$descripcion', '$activo')";
        $rs = mysqli_query($link, $sql);

        echo json_encode($rs ? ['success' => true] : ['success' => false, 'message' => mysqli_error($link)]);
        break;

    case 'deleteCancha':
        $id = mysqli_real_escape_string($link, $_REQUEST['id']);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'El ID de la cancha es obligatorio.']);
            exit;
        }

        $sql = "CALL sp_auditar_y_eliminar_cancha('$id')";
        $rs = mysqli_query($link, $sql);

        echo json_encode($rs ? ['success' => true] : ['success' => false, 'message' => mysqli_error($link)]);
        break;

    case 'insertHorario':
        $canchaId = mysqli_real_escape_string($link, $_REQUEST['canchaId']);
        $horaInicio = mysqli_real_escape_string($link, $_REQUEST['horaInicio']);
        $horaFin = mysqli_real_escape_string($link, $_REQUEST['horaFin']);
        $usuarioId = 1; // Usuario por defecto
        $activo = 1; // Activo por defecto
        $fechaUltMod = date('Y-m-d H:i:s'); // Timestamp actual

        // Validación de campos
        if (!$canchaId || !$horaInicio || !$horaFin) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        // Validar formato de hora
        if (
            !preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $horaInicio) ||
            !preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $horaFin)
        ) {
            echo json_encode(['success' => false, 'message' => 'El formato de las horas debe ser HH:mm.']);
            exit;
        }

        // Llamada al procedimiento almacenado
        $sql = "CALL sp_insertar_horario('$canchaId', '$horaInicio', '$horaFin', '$activo', '$usuarioId', '$fechaUltMod')";
        $rs = mysqli_query($link, $sql);

        echo json_encode($rs ? ['success' => true] : ['success' => false, 'message' => 'Error: ' . mysqli_error($link)]);
        break;


    case 'getHorarios':
        $sql = "SELECT h.HORARIOS_ID, h.CANCHA_ID, c.CANCHAS_DESCRIPCION, h.HORA_INICIO, h.HORA_FIN, h.HORARIOS_ACTIVO 
                        FROM horarios h
                        INNER JOIN canchas c ON h.CANCHA_ID = c.CANCHAS_ID";
        $rs = mysqli_query($link, $sql);
        $data = [];

        while ($row = mysqli_fetch_assoc($rs)) {
            $data[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $data]);
        break;


    case 'updateHorario':
        $id = mysqli_real_escape_string($link, $_REQUEST['id']);
        $canchaId = mysqli_real_escape_string($link, $_REQUEST['canchaId']);
        $horaInicio = mysqli_real_escape_string($link, $_REQUEST['horaInicio']);
        $horaFin = mysqli_real_escape_string($link, $_REQUEST['horaFin']);
        $activo = mysqli_real_escape_string($link, $_REQUEST['activo']);
        $usuarioId = 1; // Usar el ID del usuario autenticado dinámicamente
        $fechaUltMod = date('Y-m-d H:i:s');

        if (!$id || !$horaInicio || !$horaFin) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        // Llamada al procedimiento almacenado para actualizar y auditar
        $sql = "CALL sp_actualizar_y_auditar_horario('$id', '$canchaId', '$horaInicio', '$horaFin', '$activo', '$usuarioId', '$fechaUltMod')";
        $rs = mysqli_query($link, $sql);

        echo json_encode($rs ? ['success' => true] : ['success' => false, 'message' => mysqli_error($link)]);
        break;

    case 'deleteHorario':
        $id = mysqli_real_escape_string($link, $_REQUEST['id']);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'El ID del horario es obligatorio.']);
            exit;
        }

        $sql = "CALL sp_auditar_y_eliminar_horario('$id')";
        $rs = mysqli_query($link, $sql);

        if ($rs) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($link)]);
        }
        break;
    case 'getCanchas2':
        $sql = "SELECT CANCHAS_ID, CANCHAS_DESCRIPCION FROM canchas WHERE CANCHAS_ACTIVO = 1";
        $rs = mysqli_query($link, $sql);
        $data = [];

        while ($row = mysqli_fetch_assoc($rs)) {
            $data[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $data]);
        break;
    case 'insertUsuario':
        $nombre = mysqli_real_escape_string($link, $_REQUEST['nombre']);
        $apellido = mysqli_real_escape_string($link, $_REQUEST['apellido']);
        $dni = mysqli_real_escape_string($link, $_REQUEST['dni']);
        $email = mysqli_real_escape_string($link, $_REQUEST['email']);
        $telefono = mysqli_real_escape_string($link, $_REQUEST['telefono']);
        $password = mysqli_real_escape_string($link, $_REQUEST['password']);
        $perfil = mysqli_real_escape_string($link, $_REQUEST['perfil']);

        // Validación de campos obligatorios
        if (!$nombre || !$apellido || !$dni || !$email || !$telefono || !$password || !$perfil) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        // Encriptar la contraseña
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Llamada al procedimiento almacenado
        $sql = "CALL sp_insertar_usuario('$nombre', '$apellido', '$dni', '$email', '$telefono', '$passwordHash', '$perfil')";
        $rs = mysqli_query($link, $sql);

        // Depuración: Verifica si hubo un error en la consulta
        if (!$rs) {
            error_log("Error MySQL: " . mysqli_error($link)); // Escribe el error en los logs del servidor
            echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . mysqli_error($link)]);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Usuario insertado correctamente.']);
        break;

    case 'getUsuarios':
        $sql = "SELECT 
                        u.USUARIOS_ID, 
                        u.USUARIOS_NOMBRE, 
                        u.USUARIOS_APELLIDO, 
                        u.USUARIOS_DNI, 
                        u.USUARIOS_EMAIL, 
                        u.USUARIOS_TELEFONO, 
                        p.PERFIL_DESCRIPCION AS PERFIL
                    FROM usuarios u
                    INNER JOIN perfil_x_usuario pxu ON u.USUARIOS_ID = pxu.USUARIOS_ID
                    INNER JOIN perfil p ON pxu.PERFIL_ID = p.PERFIL_ID
                    WHERE pxu.PERFIL_X_USUARIO_ACTIVO = 1";
        $rs = mysqli_query($link, $sql);

        if ($rs) {
            $data = [];
            while ($row = mysqli_fetch_assoc($rs)) {
                $data[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $data]); // Respuesta en formato JSON válido
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al cargar los usuarios: ' . mysqli_error($link)]);
        }
        break;



    case 'deleteUsuario':
        $id = mysqli_real_escape_string($link, $_REQUEST['id']);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'El ID del usuario es obligatorio.']);
            exit;
        }

        // Llamar al procedimiento almacenado para eliminar el usuario
        $sql = "CALL sp_eliminar_usuario('$id')";
        $rs = mysqli_query($link, $sql);

        if ($rs) {
            echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el usuario: ' . mysqli_error($link)]);
        }
        break;
}

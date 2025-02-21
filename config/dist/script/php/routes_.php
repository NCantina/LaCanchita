<?php
require_once 'conn.php'; // Conexión a la base de datos

$action = $_REQUEST['action'] ?? null;

switch ($action) {
    case 'insertDatosPredio':
        // Validar y sanitizar entrada
        $nombre = mysqli_real_escape_string($link, $_REQUEST['nombrePredio']);
        $direccion = mysqli_real_escape_string($link, $_REQUEST['direccionPredio']);
        $telefono = mysqli_real_escape_string($link, $_REQUEST['telefonoPredio']);
        $wsp = mysqli_real_escape_string($link, $_REQUEST['wspPredio']);
        $localidad = mysqli_real_escape_string($link, $_REQUEST['localidadPredio']);

        // Validaciones de campos obligatorios
        if (!$nombre || !$direccion || !$telefono || !$localidad || !$wsp) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios, incluyendo el número de WhatsApp.']);
            exit;
        }

        // Validar que el número de WhatsApp solo contenga números
        if (!preg_match('/^\d+$/', $wsp)) {
            echo json_encode(['success' => false, 'message' => 'El número de WhatsApp debe contener solo números.']);
            exit;
        }

        // Llamar al stored procedure
        $sql = "CALL sp_insertar_predio('$nombre', '$direccion', '$telefono', '$wsp', '$localidad')";
        $rs = mysqli_query($link, $sql);

        if ($rs) {
            echo json_encode(['success' => true]);
        } else {
            $error = mysqli_error($link);
            echo json_encode(['success' => false, 'message' => "Error al ejecutar la consulta: $error"]);
        }
        break;



    case 'insertCancha':
        $descripcion = mysqli_real_escape_string($link, $_REQUEST['descripcion']);

        if (!$descripcion) {
            echo json_encode(['success' => false, 'message' => 'El nombre de la cancha es obligatorio.']);
            exit;
        }

        $sql = "CALL sp_insertar_cancha('$descripcion', 1, 1)"; // CANCHAS_ACTIVO y USUARIOS_ID por defecto
        $rs = mysqli_query($link, $sql);

        if ($rs) {
            echo json_encode(['success' => true]);
        } else {
            $error = mysqli_error($link);
            echo json_encode(['success' => false, 'message' => "Error en la consulta: $error"]);
        }
        break;

        case 'getCanchas':
            $sql = "SELECT CANCHAS_ID, CANCHAS_DESCRIPCION FROM canchas WHERE CANCHAS_ACTIVO = 1";
            $rs = mysqli_query($link, $sql);
        
            if (!$rs) {
                echo json_encode(['success' => false, 'message' => 'Error al consultar las canchas: ' . mysqli_error($link)]);
                exit;
            }
        
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
        
                // Llamar al stored procedure para actualizar la cancha
                $sql = "CALL sp_actualizar_cancha('$id', '$descripcion', '$activo')";
                $rs = mysqli_query($link, $sql);
        
                if ($rs) {
                    echo json_encode(['success' => true]);
                } else {
                    $error = mysqli_error($link);
                    echo json_encode(['success' => false, 'message' => "Error al actualizar la cancha: $error"]);
                }
                break;
    case 'deleteCancha':
        $id = mysqli_real_escape_string($link, $_REQUEST['id']);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'El ID de la cancha es obligatorio.']);
            exit;
        }

        // Llamar al stored procedure para auditar y eliminar la cancha
        $sql = "CALL sp_auditar_y_eliminar_cancha('$id')";
        $rs = mysqli_query($link, $sql);

        if ($rs) {
            echo json_encode(['success' => true, 'message' => 'La cancha ha sido eliminada correctamente y registrada en la auditoría.']);
        } else {
            $error = mysqli_error($link);
            echo json_encode(['success' => false, 'message' => "Error al eliminar la cancha: $error"]);
        }
        break;

    case 'insertHorario':
        $canchaId = mysqli_real_escape_string($link, $_REQUEST['canchaId']);
        $horaInicio = mysqli_real_escape_string($link, $_REQUEST['horaInicio']);
        $horaFin = mysqli_real_escape_string($link, $_REQUEST['horaFin']);

        if (!$canchaId || !$horaInicio || !$horaFin) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        // Llamar al stored procedure para insertar el horario
        $sql = "CALL sp_insertar_horario('$canchaId', '$horaInicio', '$horaFin', 1, 1)";
        $rs = mysqli_query($link, $sql);

        if ($rs) {
            echo json_encode(['success' => true, 'message' => 'Horario insertado correctamente.']);
        } else {
            $error = mysqli_error($link);
            echo json_encode(['success' => false, 'message' => "Error al insertar el horario: $error"]);
        }
        break;

        // Obtener Horarios
    case 'getHorarios':
        $sql = "SELECT 
                            h.HORARIOS_ID, 
                            c.CANCHAS_DESCRIPCION, 
                            h.HORA_INICIO, 
                            h.HORA_FIN, 
                            h.HORARIOS_ACTIVO 
                        FROM horarios h
                        INNER JOIN canchas c ON h.CANCHA_ID = c.CANCHAS_ID";
        $rs = mysqli_query($link, $sql);
        $data = [];

        while ($row = mysqli_fetch_assoc($rs)) {
            $data[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $data]);
        break;

        // Actualizar Horario
    case 'updateHorario':
        $id = mysqli_real_escape_string($link, $_REQUEST['id']);
        $canchaId = mysqli_real_escape_string($link, $_REQUEST['canchaId']);
        $horaInicio = mysqli_real_escape_string($link, $_REQUEST['horaInicio']);
        $horaFin = mysqli_real_escape_string($link, $_REQUEST['horaFin']);
        $activo = mysqli_real_escape_string($link, $_REQUEST['activo']);

        if (!$id || !$canchaId || !$horaInicio || !$horaFin) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        // Llamar al stored procedure para actualizar el horario
        $sql = "CALL sp_actualizar_horario('$id', '$canchaId', '$horaInicio', '$horaFin', '$activo')";
        $rs = mysqli_query($link, $sql);

        if ($rs) {
            echo json_encode(['success' => true, 'message' => 'Horario actualizado correctamente.']);
        } else {
            $error = mysqli_error($link);
            echo json_encode(['success' => false, 'message' => "Error al actualizar el horario: $error"]);
        }
        break;

        // Eliminar Horario
    case 'deleteHorario':
        $id = mysqli_real_escape_string($link, $_REQUEST['id']);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'El ID del horario es obligatorio.']);
            exit;
        }

        // Llamar al stored procedure para eliminar el horario
        $sql = "CALL sp_eliminar_horario('$id')";
        $rs = mysqli_query($link, $sql);

        if ($rs) {
            echo json_encode(['success' => true, 'message' => 'Horario eliminado correctamente.']);
        } else {
            $error = mysqli_error($link);
            echo json_encode(['success' => false, 'message' => "Error al eliminar el horario: $error"]);
        }
        break;
}

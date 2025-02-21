$(document).ready(function () {
    $('#predioFormData').on('submit', function (e) {
        e.preventDefault(); // Prevenir envío tradicional del formulario

        var formData = {
            'action': 'insertDatosPredio', // Definir la acción
            'nombrePredio': $('#nombrePredio').val(),
            'direccionPredio': $('#direccionPredio').val(),
            'telefonoPredio': $('#telefonoPredio').val(),
            'wspPredio': $('#wspPredio').val(),
            'localidadPredio': $('#localidadPredio').val()
        };

        // Validación de campos
        if (!formData.nombrePredio || !formData.direccionPredio || !formData.telefonoPredio || !formData.localidadPredio || !formData.wspPredio) {
            Swal.fire({
                title: "Campos incompletos",
                text: "Por favor, complete todos los campos, incluyendo el número de WhatsApp.",
                icon: "warning"
            });
            return;
        }

        if (!/^\d+$/.test(formData.wspPredio)) { // Validar que PREDIO_WSP solo contenga números
            Swal.fire({
                title: "Número inválido",
                text: "El número de WhatsApp debe contener solo números.",
                icon: "warning"
            });
            return;
        }

        $.ajax({
            type: 'POST',
            url: '../../config/dist/script/php/routes.php', // Ruta hacia el archivo de PHP
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        title: "Registro guardado correctamente",
                        text: "Los datos del predio han sido registrados.",
                        icon: "success"
                    });
                    $('#predioFormData')[0].reset(); // Resetear el formulario
                } else {
                    Swal.fire({
                        title: "Error al guardar",
                        text: response.message || "Ocurrió un error inesperado.",
                        icon: "error"
                    });
                }
            },
            error: function () {
                Swal.fire({
                    title: "Error del servidor",
                    text: "No se pudo procesar la solicitud. Intente nuevamente más tarde.",
                    icon: "error"
                });
            }
        });
    });
});


$(document).ready(function () {
    // Cargar datos en el DataTable
    function cargarCanchas() {
        $.ajax({
            type: 'GET',
            url: '../../config/dist/script/php/routes.php',
            data: { action: 'getCanchas' },
            dataType: 'json',
            success: function (response) {
                const tableBody = $('#canchasTable tbody');
                tableBody.empty(); // Limpiar datos anteriores
                response.data.forEach((cancha, index) => {
                    const estado = cancha.CANCHAS_ACTIVO == 1 ? 
                        '<span class="badge badge-success">Activo</span>' : 
                        '<span class="badge badge-danger">Inactivo</span>';

                    tableBody.append(`
                        <tr class="text-center">
                            <td>${index + 1}</td>
                            <td>${cancha.CANCHAS_DESCRIPCION}</td>
                            <td>${estado}</td>
                            <td>
                                <button class="btn btn-warning btn-sm editarCancha" data-id="${cancha.CANCHAS_ID}" data-descripcion="${cancha.CANCHAS_DESCRIPCION}" data-activo="${cancha.CANCHAS_ACTIVO}">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                            <td>
                                <button class="btn btn-danger btn-sm borrarCancha" data-id="${cancha.CANCHAS_ID}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    `);
                });
            },
            error: function () {
                Swal.fire({
                    title: "Error al cargar canchas",
                    text: "No se pudo cargar la lista de canchas.",
                    icon: "error"
                });
            }
        });
    }

    // Enviar formulario
    $('#canchasForm form').on('submit', function (e) {
        e.preventDefault();
        const formData = {
            action: 'insertCancha',
            descripcion: $('#nombreCancha').val(),
        };

        if (!formData.descripcion) {
            Swal.fire({
                title: "Campo requerido",
                text: "El nombre de la cancha es obligatorio.",
                icon: "warning"
            });
            return;
        }

        $.ajax({
            type: 'POST',
            url: '../../config/dist/script/php/routes.php',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        title: "Registro guardado",
                        text: "La cancha ha sido registrada correctamente.",
                        icon: "success"
                    });
                    $('#canchasForm form')[0].reset();
                    cargarCanchas();
                } else {
                    Swal.fire({
                        title: "Error",
                        text: response.message || "No se pudo guardar la cancha.",
                        icon: "error"
                    });
                }
            },
            error: function () {
                Swal.fire({
                    title: "Error del servidor",
                    text: "No se pudo procesar la solicitud.",
                    icon: "error"
                });
            }
        });
    });

    // Editar Cancha - Levantar Modal
    $(document).on('click', '.editarCancha', function () {
        const canchaId = $(this).data('id');
        const descripcion = $(this).data('descripcion');
        const activo = $(this).data('activo');

        $('#modalCanchaId').val(canchaId);
        $('#modalCanchaDescripcion').val(descripcion);
        $('#modalCanchaActivo').val(activo);

        $('#editarCanchaModal').modal('show');
    });

    // Guardar cambios en el modal
    $('#guardarCanchaBtn').on('click', function () {
        const formData = {
            action: 'updateCancha',
            id: $('#modalCanchaId').val(),
            descripcion: $('#modalCanchaDescripcion').val(),
            activo: $('#modalCanchaActivo').val()
        };
    
        if (!formData.descripcion) {
            Swal.fire({
                title: "Campo requerido",
                text: "El nombre de la cancha es obligatorio.",
                icon: "warning"
            });
            return;
        }
    
        $.ajax({
            type: 'POST',
            url: '../../config/dist/script/php/routes.php',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        title: "Actualización exitosa",
                        text: "La cancha ha sido actualizada correctamente.",
                        icon: "success"
                    });
                    $('#editarCanchaModal').modal('hide');
                    cargarCanchas(); // Recargar el DataTable
                } else {
                    Swal.fire({
                        title: "Error",
                        text: response.message || "No se pudo actualizar la cancha.",
                        icon: "error"
                    });
                }
            },
            error: function () {
                Swal.fire({
                    title: "Error del servidor",
                    text: "No se pudo procesar la solicitud.",
                    icon: "error"
                });
            }
        });
    });
    

    // Borrar Cancha - Confirmación
    $(document).on('click', '.borrarCancha', function () {
        const canchaId = $(this).data('id');

        Swal.fire({
            title: "¿Está seguro que desea eliminar esta cancha?",
            text: "Puede que la misma tenga horarios y reservas asignadas.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'POST',
                    url: '../../config/dist/script/php/routes.php',
                    data: { action: 'deleteCancha', id: canchaId },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                title: "Cancha eliminada",
                                text: "La cancha ha sido eliminada correctamente.",
                                icon: "success"
                            });
                            cargarCanchas();
                        } else {
                            Swal.fire({
                                title: "Error",
                                text: response.message || "No se pudo eliminar la cancha.",
                                icon: "error"
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            title: "Error del servidor",
                            text: "No se pudo procesar la solicitud.",
                            icon: "error"
                        });
                    }
                });
            }
        });
    });

    // Cargar datos al inicio
    cargarCanchas();
});

// Modal HTML agregado
$(document.body).append(`
    <div class="modal fade" id="editarCanchaModal" tabindex="-1" role="dialog" aria-labelledby="editarCanchaLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarCanchaLabel">Editar Cancha</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modalCanchaId">
                    <div class="form-group">
                        <label for="modalCanchaDescripcion">Descripción</label>
                        <input type="text" class="form-control" id="modalCanchaDescripcion">
                    </div>
                    <div class="form-group">
                        <label for="modalCanchaActivo">Estado</label>
                        <select class="form-control" id="modalCanchaActivo">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer d-flex">
                    <button type="button" class="btn btn-secondary w-50" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary w-50" id="guardarCanchaBtn">Guardar</button>
                </div>
            </div>
        </div>
    </div>
    `);
    

$(document).ready(function () {
    // Cargar datos en el select de canchas
    function cargarSelectCanchas() {
        $.ajax({
            type: 'GET',
            url: '../../config/dist/script/php/routes.php',
            data: { action: 'getCanchas' },
            dataType: 'json',
            success: function (response) {
                const selectCancha = $('#selectCancha');
                selectCancha.empty(); // Limpiar opciones anteriores
                selectCancha.append('<option value="">Seleccione una cancha</option>');
                response.data.forEach((cancha) => {
                    selectCancha.append(`<option value="${cancha.CANCHAS_ID}">${cancha.CANCHAS_DESCRIPCION}</option>`);
                });
            },
            error: function () {
                Swal.fire({
                    title: "Error al cargar canchas",
                    text: "No se pudo cargar la lista de canchas.",
                    icon: "error"
                });
            }
        });
    }

    // Cargar datos en el DataTable de horarios
    function cargarHorarios() {
        $.ajax({
            type: 'GET',
            url: '../../config/dist/script/php/routes.php',
            data: { action: 'getHorarios' },
            dataType: 'json',
            success: function (response) {
                const tableBody = $('#horariosTable tbody');
                tableBody.empty(); // Limpiar datos anteriores
                response.data.forEach((horario, index) => {
                    const estado = horario.HORARIOS_ACTIVO == 1 ? 
                        '<span class="badge badge-success">Activo</span>' : 
                        '<span class="badge badge-danger">Inactivo</span>';

                    tableBody.append(`
                        <tr class="text-center">
                            <td>${index + 1}</td>
                            <td>${horario.CANCHAS_DESCRIPCION}</td>
                            <td>${horario.HORA_INICIO}</td>
                            <td>${horario.HORA_FIN}</td>
                            <td>${estado}</td>
                            <td>
                                <button class="btn btn-warning btn-sm editarHorario" data-id="${horario.HORARIOS_ID}" data-cancha="${horario.CANCHA_ID}" data-horainicio="${horario.HORA_INICIO}" data-horafin="${horario.HORA_FIN}" data-activo="${horario.HORARIOS_ACTIVO}">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                            <td>
                                <button class="btn btn-danger btn-sm borrarHorario" data-id="${horario.HORARIOS_ID}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    `);
                });
            },
            error: function () {
                Swal.fire({
                    title: "Error al cargar horarios",
                    text: "No se pudo cargar la lista de horarios.",
                    icon: "error"
                });
            }
        });
    }

    // Enviar formulario de horarios
    $('#horariosForm form').on('submit', function (e) {
        e.preventDefault();
        const formData = {
            action: 'insertHorario',
            canchaId: $('#selectCancha').val(),
            horaInicio: $('#horaInicio').val(),
            horaFin: $('#horaFin').val()
        };

        if (!formData.canchaId || !formData.horaInicio || !formData.horaFin) {
            Swal.fire({
                title: "Campos incompletos",
                text: "Por favor, complete todos los campos.",
                icon: "warning"
            });
            return;
        }

        $.ajax({
            type: 'POST',
            url: '../../config/dist/script/php/routes.php',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        title: "Horario guardado",
                        text: "El horario ha sido registrado correctamente.",
                        icon: "success"
                    });
                    $('#horariosForm form')[0].reset();
                    cargarHorarios();
                } else {
                    Swal.fire({
                        title: "Error",
                        text: response.message || "No se pudo guardar el horario.",
                        icon: "error"
                    });
                }
            },
            error: function () {
                Swal.fire({
                    title: "Error del servidor",
                    text: "No se pudo procesar la solicitud.",
                    icon: "error"
                });
            }
        });
    });

    // Cargar datos iniciales
    cargarSelectCanchas();
    cargarHorarios();
});

// Modal HTML agregado para editar horarios
$(document.body).append(`
<div class="modal fade" id="editarHorarioModal" tabindex="-1" role="dialog" aria-labelledby="editarHorarioLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarHorarioLabel">Editar Horario</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalHorarioId">
                <div class="form-group">
                    <label for="modalSelectCancha">Cancha</label>
                    <select class="form-control" id="modalSelectCancha"></select>
                </div>
                <div class="form-group">
                    <label for="modalHoraInicio">Hora Inicio</label>
                    <input type="time" class="form-control" id="modalHoraInicio">
                </div>
                <div class="form-group">
                    <label for="modalHoraFin">Hora Fin</label>
                    <input type="time" class="form-control" id="modalHoraFin">
                </div>
                <div class="form-group">
                    <label for="modalHorarioActivo">Estado</label>
                    <select class="form-control" id="modalHorarioActivo">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer d-flex">
                <button type="button" class="btn btn-secondary w-50" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary w-50" id="guardarHorarioBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>
`);

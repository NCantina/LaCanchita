<?php
require_once "../../view/layoutsCliente/InicioHastaElContenido.php";
require_once "../../view/layoutsCliente/InicioSuperior.php";
?>
<style>
  .custom-slider-height {
      height: 300px; /* Reduce el tamaño del contenedor */
  }

  .rev-slider .tp-caption {
      font-size: 30px; /* Ajusta el tamaño del texto si es necesario */
      line-height: 35px; /* Ajusta el interlineado si es necesario */
  }

  .rev-slidebg {
      margin-bottom: 20px; /* Agrega un margen inferior para separar el logo del texto */
  }
</style>

<div class="slider-container rev_slider_wrapper container-sm custom-slider-height">
    <div id="revolutionSlider" class="slider rev_slider" data-version="5.4.8" data-plugin-revolution-slider
        data-plugin-options="{
            'addOnTypewriter': { 'enable': true },
            'sliderLayout': 'auto',
            'delay': 6000,
            'gridwidth': [1410,1110,930,690],
            'gridheight': [300,250,200,180],
            'disableProgressBar': 'on',
            'responsiveLevels': [4096,1422,1182,974],
            'navigation' : {
                'arrows': { 'enable': true, 'style': 'arrows-style-1 arrows-primary' },
                'bullets': {
                    'enable': true,
                    'style': 'bullets-style-1',
                    'h_align': 'center',
                    'v_align': 'bottom',
                    'space': 6,
                    'v_offset': 60,
                    'h_offset': 0
                }
            }
        }">
        <ul>
            <!-- Diapositiva 1 -->
            <li class="slide-overlay slide-overlay-level-8 custom-slider-height" data-transition="fade">
                <img src="../../config/dist/img/ESTADIO.webp" alt="" data-bgposition="center center" data-bgfit="cover"
                    data-bgrepeat="no-repeat" class="rev-slidebg custom-slider-height">

                <!-- Título de la diapositiva con ajuste de margen superior -->
                <h1 class="tp-caption font-weight-extra-bold text-color-light custom-slider-text"
                    data-frames='[{"delay":1000,"speed":500,"frame":"0","from":"opacity:0;x:50%;","to":"o:1;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;fb:0;","ease":"Power3.easeInOut"}]'
                    data-x="['left','left','left','center']" data-y="center" data-voffset="['-30','-30','-30','-30']"
                    data-fontsize="['36','36','36','28']" data-lineheight="['40','40','40','35']"
                    data-letterspacing="-1">Administración del Sitio<strong> ESTADIO22</strong></h1>
            </li>
        </ul>
    </div>
</div>


<!-- Sección de botones -->
<style>
  .box-content {
      padding: 10px; /* Reduce el espacio interno del contenedor */
  }

  .box-content .text-primary {
      font-size: 24px; /* Reduce el tamaño del ícono */
  }

  .box-content .font-weight-bold {
      font-size: 14px; /* Reduce el tamaño del texto */
      margin-top: 5px; /* Ajusta el margen superior del texto */
  }
</style>
<div class="container mt-5">
    <div class="row">
        <!-- Botón Predio -->
        <div class="col-6 col-md-4 col-lg-2 mb-4">
            <div class="featured-box featured-box-no-borders featured-box-box-shadow text-center" style="height: 120px;">
                <a href="#" class="text-decoration-none" onclick="showForm('predioForm')">
                    <span class="box-content px-1 py-3 d-block">
                        <span class="text-primary text-8 position-relative mt-2">
                            <i class="fas fa-building"></i>
                        </span>
                        <span class="font-weight-bold text-uppercase d-block text-dark pt-1">Predio</span>
                    </span>
                </a>
            </div>
        </div>

        <!-- Botón Canchas -->
        <div class="col-6 col-md-4 col-lg-2 mb-4">
            <div class="featured-box featured-box-no-borders featured-box-box-shadow text-center" style="height: 120px;">
                <a href="#" class="text-decoration-none" onclick="showForm('canchasForm')">
                    <span class="box-content px-1 py-3 d-block">
                        <span class="text-primary text-8 position-relative mt-2">
                            <i class="fas fa-futbol"></i>
                        </span>
                        <span class="font-weight-bold text-uppercase d-block text-dark pt-1">Canchas</span>
                    </span>
                </a>
            </div>
        </div>

        <!-- Botón Horario de Turnos -->
        <div class="col-6 col-md-4 col-lg-2 mb-4">
            <div class="featured-box featured-box-no-borders featured-box-box-shadow text-center" style="height: 120px;">
                <a href="#" class="text-decoration-none" onclick="showForm('horariosForm')">
                    <span class="box-content px-1 py-3 d-block">
                        <span class="text-primary text-8 position-relative mt-2">
                            <i class="fas fa-clock"></i>
                        </span>
                        <span class="font-weight-bold text-uppercase d-block text-dark pt-1">Horario de Turnos</span>
                    </span>
                </a>
            </div>
        </div>

        <!-- Botón Reservas -->
        <div class="col-6 col-md-4 col-lg-2 mb-4">
            <div class="featured-box featured-box-no-borders featured-box-box-shadow text-center" style="height: 120px;">
                <a href="#" class="text-decoration-none" onclick="showForm('reservasForm')">
                    <span class="box-content px-1 py-3 d-block">
                        <span class="text-primary text-8 position-relative mt-2">
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                        <span class="font-weight-bold text-uppercase d-block text-dark pt-1">Reservas</span>
                    </span>
                </a>
            </div>
        </div>

        <!-- Botón Usuarios -->
        <div class="col-6 col-md-4 col-lg-2 mb-4">
            <div class="featured-box featured-box-no-borders featured-box-box-shadow text-center" style="height: 120px;">
                <a href="#" class="text-decoration-none" onclick="showForm('usuariosForm')">
                    <span class="box-content px-1 py-3 d-block">
                        <span class="text-primary text-8 position-relative mt-2">
                            <i class="fas fa-users"></i>
                        </span>
                        <span class="font-weight-bold text-uppercase d-block text-dark pt-1">Usuarios</span>
                    </span>
                </a>
            </div>
        </div>

        <!-- Botón Facturación -->
        <div class="col-6 col-md-4 col-lg-2 mb-4">
            <div class="featured-box featured-box-no-borders featured-box-box-shadow text-center" style="height: 120px;">
                <a href="#" class="text-decoration-none" onclick="showForm('facturacionForm')">
                    <span class="box-content px-1 py-3 d-block">
                        <span class="text-primary text-8 position-relative mt-2">
                            <i class="fas fa-dollar-sign"></i>
                        </span>
                        <span class="font-weight-bold text-uppercase d-block text-dark pt-1">Facturación</span>
                    </span>
                </a>
            </div>
        </div>


<!-- Sección de Estilos deformularios dinámicos -->
<style>
  @media (min-width: 992px) {
    /* Estilo para pantallas grandes */
    .form-row .col-xl-6 {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
  }

  /* Botones de guardar a pantalla completa */
  .btn-block {
      display: block;
      width: 100%;
  }

  /* Reducir la distancia entre botones y formularios */
  .form-section {
      margin-top: 5px;
  }
</style>

<!-- Sección de Estilos deformularios dinámicos -->
<style>
  @media (min-width: 992px) {
    /* Estilo para pantallas grandes */
    .form-row .col-xl-6 {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
  }

  /* Botones de guardar a pantalla completa */
  .btn-block {
      display: block;
      width: 100%;
  }

  /* Reducir la distancia entre botones y formularios */
  .form-section {
      margin-top: 10px;
  }
</style>

<!-- Sección de formularios dinámicos -->
<div class="container">
    <div id="dynamicSection">
        <!-- Formulario de Empresa -->
        <div id="predioForm" class="form-section">
            <h2>Datos del Predio</h2>
            <form id="predioFormData">
                <div class="form-row">
                    <div class="form-group col-md-6 col-xl-6">
                        <label for="nombrePredio">Nombre del Predio</label>
                        <input type="text" class="form-control" id="nombrePredio" required>
                    </div>
                    <div class="form-group col-md-6 col-xl-6">
                        <label for="direccionPredio">Dirección</label>
                        <input type="text" class="form-control" id="direccionPredio" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6 col-xl-6">
                        <label for="telefonoPredio">Teléfono</label>
                        <input type="tel" class="form-control" id="telefonoPredio" required>
                    </div>
                    <div class="form-group col-md-6 col-xl-6">
                        <label for="wspPredio">Número de WhatsApp</label>
                        <input type="tel" class="form-control" id="wspPredio">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6 col-xl-6">
                        <label for="localidadPredio">Localidad</label>
                        <input type="text" class="form-control" id="localidadPredio" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Guardar</button>
            </form>
        </div>

        <!-- Formulario de Canchas -->
        <div id="canchasForm" class="form-section" style="display:none;">
            <h2>Agregar Canchas</h2>
            <form>
                <div class="form-row">
                    <div class="form-group col-md-6 col-xl-6">
                        <label for="nombreCancha">Nombre de la Cancha</label>
                        <input type="text" class="form-control" id="nombreCancha">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Guardar</button>
            </form>
            
            <!-- DataTable para mostrar las canchas -->
            <div class="mt-4">
                <table class="table table-striped table-bordered" id="canchasTable">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Cancha</th>
                            <th>Estado</th>
                            <th>Editar</th>
                            <th>Borrar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Datos dinámicos se cargarán aquí -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Formulario de Horario de Turnos -->
        <div id="horariosForm" class="form-section" style="display:none;">
            <h2>Horario de Turnos</h2>
            <form>
                <div class="form-row">
                <div class="form-group col-md-4 col-xl-4">
    <label for="canchaSelect">Cancha</label>
    <select class="form-control" id="canchaSelect">
        <!-- Opciones dinámicas se cargarán aquí -->
    </select>
</div>

                    <div class="form-group col-md-4 col-xl-4">
                        <label for="horaInicio">Hora de Inicio</label>
                        <input type="time" class="form-control" id="horaInicio">
                    </div>
                    <div class="form-group col-md-4 col-xl-4">
                        <label for="horaFin">Hora de Fin</label>
                        <input type="time" class="form-control" id="horaFin">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Guardar</button>
            </form>

            <!-- DataTable para mostrar horarios de turnos -->
            <div class="mt-4">
                <table class="table table-striped table-bordered" id="horariosTable">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Cancha</th>
                            <th>Hs Inicio</th>
                            <th>Hs Fin</th>
                            <th>Estado</th>
                            <th>Editar</th>
                            <th>Borrar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Datos dinámicos se cargarán aquí -->
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Formulario de Reservas -->
        <div id="reservasForm" class="form-section" style="display:none;">
            <h2>Reservas</h2>
            <form>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cancha</th>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Reservado Por</th>
                            <th>Seña</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Datos dinámicos aquí -->
                    </tbody>
                </table>
            </form>
        </div>

        <!-- Formulario de Usuarios -->
        <div id="usuariosForm" class="form-section" style="display:none;">
            <h2>Gestión de Usuarios</h2>
            <form>
                <div class="form-row">
                    <div class="form-group col-md-4 col-xl-4">
                        <label for="nombreUsuario">Nombre</label>
                        <input type="text" class="form-control" id="nombreUsuario">
                    </div>
                    <div class="form-group col-md-4 col-xl-4">
                        <label for="apellidoUsuario">Apellido</label>
                        <input type="text" class="form-control" id="apellidoUsuario">
                    </div>
                    <div class="form-group col-md-4 col-xl-4">
                        <label for="dniUsuario">DNI</label>
                        <input type="text" class="form-control" id="dniUsuario">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6 col-xl-6">
                        <label for="emailUsuario">Correo</label>
                        <input type="email" class="form-control" id="emailUsuario">
                    </div>
                    <div class="form-group col-md-6 col-xl-6">
                        <label for="telefonoUsuario">Teléfono</label>
                        <input type="tel" class="form-control" id="telefonoUsuario">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6 col-xl-6">
                        <label for="password">Contraseña</label>
                        <input type="password" class="form-control" id="password">
                    </div>
                    <div class="form-group col-md-6 col-xl-6">
                        <label for="confirmPassword">Confirmar Contraseña</label>
                        <input type="password" class="form-control" id="confirmPassword">
                    </div>
                </div>
                <div class="form-group">
                    <label for="rolUsuario">Rol</label>
                    <select class="form-control" id="rolUsuario">
                        <option>Administrador</option>
                        <option>Usuario</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Guardar</button>
            </form>

            <!-- DataTable para mostrar usuarios -->
            <div class="mt-4">
            <table id="usuariosTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>DNI</th>
            <th>Correo</th>
            <th>Teléfono</th>
            <th>Rol</th>
            <th>Editar</th>
            <th>Borrar</th>
        </tr>
    </thead>
    <tbody>
        <!-- Las filas se cargarán dinámicamente aquí -->
    </tbody>
</table>


            </div>
        </div>
        <!-- Formulario de Facturación -->
        <div id="facturacionForm" class="form-section" style="display:none;">
            <h2>Facturación</h2>

            <!-- Boxes de resumen -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="featured-box featured-box-no-borders featured-box-box-shadow text-center" style="background-color: #d9f2ff;">
                        <div class="box-content px-3 py-4">
                            <h4 class="font-weight-bold text-uppercase">Ingresos</h4>
                            <p class="font-weight-bold" id="ingresosTotal">$0.00</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="featured-box featured-box-no-borders featured-box-box-shadow text-center" style="background-color: #ffcccc;">
                        <div class="box-content px-3 py-4">
                            <h4 class="font-weight-bold text-uppercase">Egresos</h4>
                            <p class="font-weight-bold" id="egresosTotal">$0.00</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="featured-box featured-box-no-borders featured-box-box-shadow text-center" style="background-color: #d9ffd9;">
                        <div class="box-content px-3 py-4">
                            <h4 class="font-weight-bold text-uppercase">Caja</h4>
                            <p class="font-weight-bold" id="cajaTotal">$0.00</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selectores de fecha -->
            <form>
                <div class="form-row mb-4">
                    <div class="form-group col-md-6">
                        <label for="fechaUnica">Seleccionar un día</label>
                        <input type="date" class="form-control" id="fechaUnica" onchange="toggleFechaUnica(true)">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="periodoDesde">Seleccionar un período</label>
                        <div class="d-flex">
                            <input type="date" class="form-control mr-2" id="periodoDesde" onchange="toggleFechaUnica(false)">
                            <input type="date" class="form-control" id="periodoHasta" onchange="toggleFechaUnica(false)">
                        </div>
                    </div>
                </div>
            </form>

            <!-- DataTable para mostrar facturación -->
            <div class="mt-4">
                <table class="table table-striped table-bordered" id="facturacionTable">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Fecha</th>
                            <th>Día</th>
                            <th>Cancha</th>
                            <th>Reservado por</th>
                            <th>Estado</th>
                            <th>Cobro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Datos dinámicos se cargarán aquí -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6" class="text-right">Total:</th>
                            <th id="totalCobro">$0.00</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <script>
        function toggleFechaUnica(isSingleDate) {
            const fechaUnica = document.getElementById('fechaUnica');
            const periodoDesde = document.getElementById('periodoDesde');
            const periodoHasta = document.getElementById('periodoHasta');

            if (isSingleDate) {
                periodoDesde.value = '';
                periodoHasta.value = '';
                periodoDesde.disabled = true;
                periodoHasta.disabled = true;
                fechaUnica.disabled = false;
            } else {
                fechaUnica.value = '';
                fechaUnica.disabled = true;
                periodoDesde.disabled = false;
                periodoHasta.disabled = false;
            }
        }

        // Script para calcular el total del cobro y actualizar los boxes de resumen
        function calcularTotalCobro() {
            const tabla = document.getElementById('facturacionTable');
            const filas = tabla.querySelectorAll('tbody tr');
            let total = 0;

            filas.forEach(fila => {
                const cobro = parseFloat(fila.querySelector('td:last-child').textContent.replace('$', '')) || 0;
                total += cobro;
            });

            document.getElementById('totalCobro').textContent = `$${total.toFixed(2)}`;
            document.getElementById('ingresosTotal').textContent = `$${total.toFixed(2)}`; // Ejemplo de actualización de ingresos
            document.getElementById('cajaTotal').textContent = `$${total.toFixed(2)}`; // Ejemplo de actualización de caja
        }

        // Evento para actualizar el total cuando se carguen datos dinámicos
        document.addEventListener('DOMContentLoaded', () => {
            calcularTotalCobro();
        });
        </script>
    </div>
</div>


<!-- Modal para agregar Cancha -->
<div class="modal fade" id="agregarCanchaModal" tabindex="-1" role="dialog" aria-labelledby="agregarCanchaLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agregarCanchaLabel">Agregar Cancha</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <label for="nombreCancha">Nombre de la Cancha</label>
                        <input type="text" class="form-control" id="nombreCancha">
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>

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

<div class="modal fade" id="editarUsuarioModal" tabindex="-1" aria-labelledby="editarUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarUsuarioLabel">Editar Usuario</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                
            </div>
            <div class="modal-body">
                <form id="editarUsuarioForm">
                    <input type="hidden" id="editarUsuarioId">
                    <div class="mb-3">
                        <label for="editarNombreUsuario" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="editarNombreUsuario">
                    </div>
                    <div class="mb-3">
                        <label for="editarApellidoUsuario" class="form-label">Apellido</label>
                        <input type="text" class="form-control" id="editarApellidoUsuario">
                    </div>
                    <div class="mb-3">
                        <label for="editarDniUsuario" class="form-label">DNI</label>
                        <input type="text" class="form-control" id="editarDniUsuario">
                    </div>
                    <div class="mb-3">
                        <label for="editarEmailUsuario" class="form-label">Correo</label>
                        <input type="email" class="form-control" id="editarEmailUsuario">
                    </div>
                    <div class="mb-3">
                        <label for="editarTelefonoUsuario" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="editarTelefonoUsuario">
                    </div>
                    <div class="mb-3">
                        <label for="editarRolUsuario" class="form-label">Rol</label>
                        <select class="form-control" id="editarRolUsuario">
                            <option value="1">Administrador</option>
                            <option value="2">Usuario</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Script para mostrar los formularios dinámicamente -->
<script>
function showForm(formId) {
    // Oculta todas las secciones de formularios
    var forms = document.getElementsByClassName('form-section');
    for (var i = 0; i < forms.length; i++) {
        forms[i].style.display = 'none';
    }
    // Muestra solo el formulario que se ha clickeado
    document.getElementById(formId).style.display = 'block';
};

</script>





<!-- Scripts de terceros -->
<script src="../../config/pluggins/vendor/jquery/jquery.min.js"></script>
<script src="../../config/pluggins/vendor/jquery.appear/jquery.appear.min.js"></script>
<script src="../../config/pluggins/vendor/jquery.easing/jquery.easing.min.js"></script>
<script src="../../config/pluggins/vendor/jquery.cookie/jquery.cookie.min.js"></script>
<script src="../../config/pluggins/vendor/popper/umd/popper.min.js"></script>
<script src="../../config/pluggins/vendor/bootstrap/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="../../config/pluggins/vendor/common/common.min.js"></script>
<script src="../../config/pluggins/vendor/jquery.validation/jquery.validate.min.js"></script>
<script src="../../config/pluggins/vendor/jquery.easy-pie-chart/jquery.easypiechart.min.js"></script>
<script src="../../config/pluggins/vendor/jquery.gmap/jquery.gmap.min.js"></script>
<script src="../../config/pluggins/vendor/jquery.lazyload/jquery.lazyload.min.js"></script>
<script src="../../config/pluggins/vendor/isotope/jquery.isotope.min.js"></script>
<script src="../../config/pluggins/vendor/owl.carousel/owl.carousel.min.js"></script>
<script src="../../config/pluggins/vendor/magnific-popup/jquery.magnific-popup.min.js"></script>
<script src="../../config/pluggins/vendor/vide/jquery.vide.min.js"></script>
<script src="../../config/pluggins/vendor/vivus/vivus.min.js"></script>

<!-- Scripts del tema -->
<script src="../../config/dist/js/theme.js"></script>

<!-- Scripts específicos de la página -->
<script src="../../config/pluggins/vendor/rs-plugin/js/jquery.themepunch.tools.min.js"></script>
<script src="../../config/pluggins/vendor/rs-plugin/js/jquery.themepunch.revolution.min.js"></script>

<!-- Complemento del Slider Revolution para efectos de máquina de escribir -->
<script src="../../config/pluggins/vendor/rs-plugin/revolution-addons/typewriter/js/revolution.addon.typewriter.min.js">
</script>

<!-- Scripts personalizados -->
<script src="../../config/dist/js/custom.js"></script>

<!-- Inicialización del tema -->
<script src="../../config/dist/js/theme.init.js"></script>

<script src="../../config/dist/js/soporte/soporte.js"></script>


</body>

</html>
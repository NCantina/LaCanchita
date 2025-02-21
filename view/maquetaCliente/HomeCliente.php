<?php
require_once "../../view/layoutsCliente/InicioHastaElContenido.php";
require_once "../../view/layoutsCliente/InicioSuperior.php";
?>

<!-- Contenido principal -->
<div role="main" class="main">
    <!-- Contenedor del slider -->
    <div class="slider-container rev_slider_wrapper">
        <div id="revolutionSlider" class="slider rev_slider" data-version="5.4.8" data-plugin-revolution-slider
            data-plugin-options="{
                        'addOnTypewriter': { 'enable': true },
                        'sliderLayout': 'auto',
                        'delay': 9000,
                        'gridwidth': [1410,1110,930,690],
                        'gridheight': [500,400,350,300],
                        'disableProgressBar': 'on',
                        'responsiveLevels': [4096,1422,1182,974],
                        'navigation' : {
                            'arrows': { 'enable': true, 'style': 'arrows-style-1 arrows-primary' },
                            'bullets': {
                                'enable': true,
                                'style': 'bullets-style-1',
                                'h_align': 'center',
                                'v_align': 'bottom',
                                'space': 7,
                                'v_offset': 70,
                                'h_offset': 0
                            }
                        }
                    }">
            <ul>
                <!-- Diapositiva 1 -->
                <li class="slide-overlay slide-overlay-level-8" data-transition="fade">

                    <img src="../../config/dist/img/ESTADIO.webp" alt="" data-bgposition="center center"
                        data-bgfit="cover" data-bgrepeat="no-repeat" class="rev-slidebg">

                    <!-- Título de la diapositiva -->
                    <h1 class="tp-caption font-weight-extra-bold text-color-light"
                        data-frames='[{"delay":1000,"speed":500,"frame":"0","from":"opacity:0;x:50%;","to":"o:1;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;fb:0;","ease":"Power3.easeInOut"}]'
                        data-x="['left','left','left','center']" data-y="center"
                        data-voffset="['-50','-50','-50','-50']" data-fontsize="['48','48','48','36']"
                        data-lineheight="['55','55','55','40']" data-letterspacing="-1">Todo sobre<strong>
                            ESTADIO22</strong></h1>


                    <!-- Botón de acción en la diapositiva -->
                    <a class="tp-caption btn btn-primary font-weight-bold rounded" href="#"
                        data-frames='[{"delay":3000,"speed":2000,"frame":"0","from":"y:50%;opacity:0;","to":"y:0;o:1;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;fb:0;","ease":"Power3.easeInOut"}]'
                        data-x="['left','left','left','center']" data-y="center" data-voffset="['90','90','90','110']"
                        data-paddingtop="['16','16','16','12']" data-paddingbottom="['16','16','16','12']"
                        data-paddingleft="['40','40','40','30']" data-paddingright="['40','40','40','30']"
                        data-fontsize="['14','14','14','12']" data-lineheight="['20','20','20','18']">Video si es que
                        tienen<i class="fas fa-arrow-right ml-1"></i></a>

                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Sección de Tabs Ocupación de Canchas y Contabilidad -->
<section class="section mt-1">
    <div class="container">
        <!-- Encabezado del Tab -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="ocupacion-tab" data-toggle="tab" href="#ocupacion" role="tab"
                    aria-controls="ocupacion" aria-selected="true">
                    Ocupación de Canchas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="contabilidad-tab" data-toggle="tab" href="#contabilidad" role="tab"
                    aria-controls="contabilidad" aria-selected="false">
                    Contabilidad
                </a>
            </li>
        </ul>

        <!-- Contenido del Tab -->
        <div class="tab-content mt-3" id="myTabContent">
            <!-- Tab de Ocupación de Canchas -->
            <div class="tab-pane fade show active" id="ocupacion" role="tabpanel" aria-labelledby="ocupacion-tab">
                <!-- Calendario para seleccionar la fecha -->
                <div class="row mb-4">
                    <div class="col-md-6 offset-md-3">
                        <input type="date" class="form-control text-center" id="fechaOcupacion" name="fechaOcupacion">
                    </div>
                </div>


                <!-- Ocupación de Canchas -->
                <div class="row text-center">
                    <!-- Cancha 1 -->
                    <div class="col-md-4 mb-4">
                        <img src="/dist/img/cancha.PNG" alt="Cancha 1" class="img-fluid"
                            style="max-width: 50%; height: auto;">
                        <h4>Cancha 12</h4>
                        <!-- Horarios para Cancha 1 en tabla -->
                        <div class="mt-3">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Nro</th>
                                        <th>Horario</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>08:00 a 09:00</td>
                                        <td class="text-center" style="cursor: pointer;"
                                            onclick="levantarModal('Cancha 1', '08:00 a 09:00', 'completado')">
                                            <span class="badge badge-outline-success">Turno Completado</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>09:00 a 10:00</td>
                                        <td class="text-center" style="cursor: pointer;"
                                            onclick="levantarModal('Cancha 1', '09:00 a 10:00', 'reservado')">
                                            <span class="badge badge-outline-warning">Turno Reservado</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>10:00 a 11:00</td>
                                        <td class="text-center" style="cursor: pointer;"
                                            onclick="levantarModal('Cancha 1', '10:00 a 11:00', 'libre')">
                                            <span class="badge badge-outline-danger">Turno Libre</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Cancha 2 -->
                    <div class="col-md-4 mb-4">
                        <img src="config/dist/img/cancha.PNG" alt="Cancha 2" class="img-fluid"
                            style="max-width: 50%; height: auto;">
                        <h4>Cancha 22</h4>
                        <!-- Horarios para Cancha 2 en tabla -->
                        <div class="mt-3">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Nro</th>
                                        <th>Horario</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>08:00 a 09:00</td>
                                        <td class="text-center" style="cursor: pointer;"
                                            onclick="levantarModal('Cancha 2', '08:00 a 09:00', 'completado')">
                                            <span class="badge badge-outline-success">Turno Completado</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>09:00 a 10:00</td>
                                        <td class="text-center" style="cursor: pointer;"
                                            onclick="levantarModal('Cancha 2', '09:00 a 10:00', 'reservado')">
                                            <span class="badge badge-outline-warning">Turno Reservado</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>10:00 a 11:00</td>
                                        <td class="text-center" style="cursor: pointer;"
                                            onclick="levantarModal('Cancha 2', '10:00 a 11:00', 'libre')">
                                            <span class="badge badge-outline-danger">Turno Libre</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Cancha 3 -->
                    <div class="col-md-4 mb-4">
                        <img src="../../config/dist/img/cancha.png" alt="Cancha 3" class="img-fluid"
                            style="max-width: 50%; height: auto;">
                        <h4>Cancha 3</h4>
                        <!-- Horarios para Cancha 3 en tabla -->
                        <div class="mt-3">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Nro</th>
                                        <th>Horario</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>08:00 a 09:00</td>
                                        <td class="text-center" style="cursor: pointer;"
                                            onclick="levantarModal('Cancha 3', '08:00 a 09:00', 'completado')">
                                            <span class="badge badge-outline-success">Turno Completado</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>09:00 a 10:00</td>
                                        <td class="text-center" style="cursor: pointer;"
                                            onclick="levantarModal('Cancha 3', '09:00 a 10:00', 'reservado')">
                                            <span class="badge badge-outline-warning">Turno Reservado</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>10:00 a 11:00</td>
                                        <td class="text-center" style="cursor: pointer;"
                                            onclick="levantarModal('Cancha 3', '10:00 a 11:00', 'libre')">
                                            <span class="badge badge-outline-danger">Turno Libre</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido del Tab de Contabilidad -->
        <div class="tab-pane fade" id="contabilidad" role="tabpanel" aria-labelledby="contabilidad-tab">
            <!-- Calendario para seleccionar la fecha -->
            <div class="row mb-4">
                <div class="col-md-6 offset-md-3">
                    <input type="date" class="form-control text-center" id="fechaOcupacion" name="fechaOcupacion">
                </div>
            </div>

            <!-- Tarjetas de Dinero en Caja y Cobros del Día -->
            <div class="row mb-4">
                <!-- Dinero en Caja -->
                <div class="col-md-6">
                    <div class="card bg-light mb-3 text-center">
                        <div class="card-body">
                            <h5 class="card-title">Dinero en Caja</h5>
                            <p class="card-text">$20,000</p>
                        </div>
                    </div>
                </div>
                <!-- Cobros del Día -->
                <div class="col-md-6">
                    <div class="card bg-light mb-3 text-center">
                        <div class="card-body">
                            <h5 class="card-title">Cobros del Día</h5>
                            <p class="card-text">$7,500</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Contabilidad -->
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Cancha</th>
                                <th>Turno</th>
                                <th>Reserva Nombre</th> <!-- Nueva columna para el nombre del que reservó -->
                                <th>Estado</th>
                                <th>Seña</th>
                                <th>A Cobrar</th>
                                <th>Tipo de Cobro</th> <!-- Nueva columna para el tipo de cobro -->
                                <th>Cobrado</th> <!-- Nueva columna para checkbox -->
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Cancha 1</td>
                                <td>08:00 a 09:00</td>
                                <td>Juan Pérez</td> <!-- Ejemplo de nombre -->
                                <td>Turno Completado</td>
                                <td>$1000</td>
                                <td>$5000</td>
                                <td>
                                    <select class="form-control">
                                        <option>Efectivo</option>
                                        <option>Tarjeta</option>
                                        <option>Mercado Pago</option>
                                        <option>Cuenta DNI</option>
                                    </select>
                                </td> <!-- Select para tipo de cobro -->
                                <td><input type="checkbox" class="form-check-input" checked></td>
                                <!-- Checkbox cobrado -->
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Cancha 2</td>
                                <td>09:00 a 10:00</td>
                                <td>María López</td> <!-- Ejemplo de nombre -->
                                <td>Turno Reservado</td>
                                <td>$500</td>
                                <td>$3000</td>
                                <td>
                                    <select class="form-control">
                                        <option>Efectivo</option>
                                        <option>Tarjeta</option>
                                        <option>Mercado Pago</option>
                                        <option>Cuenta DNI</option>
                                    </select>
                                </td> <!-- Select para tipo de cobro -->
                                <td><input type="checkbox" class="form-check-input"></td> <!-- Checkbox no cobrado -->
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Cancha 3</td>
                                <td>10:00 a 11:00</td>
                                <td></td> <!-- Sin nombre porque es Turno Libre -->
                                <td>Turno Libre</td>
                                <td>$0</td>
                                <td>$0</td>
                                <td>
                                    <select class="form-control">
                                        <option>Efectivo</option>
                                        <option>Tarjeta</option>
                                        <option>Mercado Pago</option>
                                        <option>Cuenta DNI</option>
                                    </select>
                                </td> <!-- Select para tipo de cobro -->
                                <td><input type="checkbox" class="form-check-input"></td> <!-- Checkbox no cobrado -->
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal para tomar reserva -->
        <div class="modal fade" id="reservaModal" tabindex="-1" role="dialog" aria-labelledby="reservaModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reservaModalLabel">Reserva</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <label for="cancha">Cancha</label>
                                <input type="text" class="form-control" id="cancha" disabled>
                            </div>
                            <div class="form-group">
                                <label for="horario">Horario</label>
                                <input type="text" class="form-control" id="horario" disabled>
                            </div>
                            <div class="form-group">
                                <label for="nombreReserva">Nombre de la Reserva</label>
                                <input type="text" class="form-control" id="nombreReserva"
                                    placeholder="Ingresa el nombre de quien reserva">
                            </div>
                            <div class="form-group">
                                <label for="senia">Seña</label>
                                <input type="number" class="form-control" id="senia" placeholder="Ingresa la seña">
                            </div>
                            <div id="botonExtra"></div>
                            <button type="submit" class="btn btn-primary">Confirmar Reserva</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

</section>

<style>
.badge-outline-success {
    color: #28a745;
    border: 1px solid #28a745;
    background-color: transparent;
}

.badge-outline-warning {
    color: #ffc107;
    border: 1px solid #ffc107;
    background-color: transparent;
}

.badge-outline-danger {
    color: #dc3545;
    border: 1px solid #dc3545;
    background-color: transparent;
}

.nav-tabs .nav-link.active {
    background-color: #007bff;
    color: #fff;
    border-color: #007bff;
}

.nav-tabs .nav-link {
    background-color: #f8f9fa;
    color: #007bff;
}
</style>



<!-- Script para abrir el modal y completar datos -->
<script>
function levantarModal(cancha, horario, estado) {
    document.getElementById('cancha').value = cancha;
    document.getElementById('horario').value = horario;
    document.getElementById('nombreReserva').disabled = estado === 'completado';
    document.getElementById('senia').disabled = estado === 'completado';
    document.getElementById('nombreReserva').value = estado === 'completado' ? 'Reservado por Juan Pérez' : '';
    document.getElementById('senia').value = estado === 'completado' ? '1000' : '';

    let botonExtra = document.getElementById('botonExtra');
    botonExtra.innerHTML = '';

    if (estado === 'reservado') {
        let botonQuitar = document.createElement('button');
        botonQuitar.className = 'btn btn-danger';
        botonQuitar.innerText = 'Quitar Reserva';
        botonQuitar.type = 'button';
        botonExtra.appendChild(botonQuitar);
    }

    $('#reservaModal').modal('show');
}
</script>


</div>

<!-- Pie de página -->
<footer id="footer" class="mt-0">
    <div class="container container-lg my-4">
        <div class="row py-5">
            <!-- Columna de descripción del pie de página -->
            <div class="col-lg-4 mb-5 mb-lg-0 text-center text-lg-left pt-3">
                <h5 class="text-5 text-transform-none font-weight-semibold text-color-light mb-4">EFEGENE |
                    DesarrollosWeb</h5>
                <p class="text-4 mb-3">Lorem ipsum dolor sit amet, consectetur adipiscing elit.<br>Cras volutpat id
                    sapien ac varius.</p>
                <a href="http://themeforest.net/item/porto-responsive-html5-template/4106987" target="_blank"
                    class="d-inline-flex align-items-center btn btn-primary font-weight-semibold px-5 btn-py-3 text-3 rounded mt-2">PURCHASE
                    PORTO</a>
            </div>
            <!-- Columna de enlaces a páginas -->
            <div class="col-lg-2 mb-4 mb-md-0 text-center text-lg-left pt-3">
                <h5 class="text-5 text-transform-none font-weight-semibold text-color-light mb-4">Pages</h5>
                <ul class="list list-icons list-icons-sm d-inline-flex flex-column">
                    <li class="text-4 mb-2"><i class="fas fa-angle-right"></i><a href="page-services.html"
                            class="link-hover-style-1 ml-1"> Our Services</a></li>
                    <li class="text-4 mb-2"><i class="fas fa-angle-right"></i><a href="about-us.html"
                            class="link-hover-style-1 ml-1"> About Us</a></li>
                    <li class="text-4 mb-2"><i class="fas fa-angle-right"></i><a href="contact-us.html"
                            class="link-hover-style-1 ml-1"> Contact Us</a></li>
                </ul>
            </div>
            <!-- Columna de información de contacto -->
            <div class="col-lg-3 mb-4 mb-lg-0 text-center text-lg-left pt-3">
                <h5 class="text-5 text-transform-none font-weight-semibold text-color-light mb-4">Contact Us</h5>
                <p class="text-4 mb-2"><span class="text-color-light">Address:</span> 1234 Street Name, City Name, USA
                </p>
                <p class="text-4 mb-2"><span class="text-color-light">Phone:</span> (123) 456-7890</p>
                <p class="text-4 mb-2"><span class="text-color-light">Email:</span> <a
                        href="mailto:mail@example.com">mail@example.com</a></p>
            </div>
            <!-- Columna de enlaces a redes sociales -->
            <div class="col-lg-3 text-center text-lg-left pt-3">
                <h5 class="text-5 text-transform-none font-weight-semibold text-color-light mb-4">Follow Us</h5>
                <ul
                    class="footer-social-icons social-icons social-icons-clean social-icons-big social-icons-opacity-light social-icons-icon-light mt-0 mt-lg-3">
                    <li class="social-icons-facebook"><a href="http://www.facebook.com/" target="_blank"
                            title="Facebook"><i class="fab fa-facebook-f"></i></a></li>
                    <li class="social-icons-twitter"><a href="http://www.twitter.com/" target="_blank"
                            title="Twitter"><i class="fab fa-twitter"></i></a></li>
                    <li class="social-icons-linkedin"><a href="http://www.linkedin.com/" target="_blank"
                            title="Linkedin"><i class="fab fa-linkedin-in"></i></a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="container container-lg">
        <div class="footer-copyright footer-copyright-style-2">
            <div class="py-2">
                <div class="row py-4">
                    <div class="col d-flex align-items-center justify-content-center mb-4 mb-lg-0">
                        <p>© Copyright 2019. All Rights Reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
</div>




<!-- Scripts de terceros -->
<script src="../../config/pluggins/vendor/jquery/jquery.min.js"></script>
<script src="../../config/pluggins/vendor/jquery.appear/jquery.appear.min.js"></script>
<script src="../../config/pluggins/vendor/jquery.easing/jquery.easing.min.js"></script>
<script src="../../config/pluggins/vendor/jquery.cookie/jquery.cookie.min.js"></script>
<script src="../../config/pluggins/vendor/popper/umd/popper.min.js"></script>
<script src="../../config/pluggins/vendor/bootstrap/js/bootstrap.min.js"></script>
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


</body>

</html>
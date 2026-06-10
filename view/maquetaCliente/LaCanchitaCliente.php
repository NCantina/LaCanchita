<?php
require_once "../../config/dist/script/php/auth_check.php";
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
                        data-lineheight="['55','55','55','40']" data-letterspacing="-1">Veni a Jugar a<strong>
                            ESTADIO22</strong></h1>

                    <!-- Texto con animación de máquina de escribir -->
                    <div class="tp-caption font-weight-extra-bold text-color-light"
                        data-frames='[{"delay":500,"speed":2500,"from":"y:50px;sX:1;sY:1;opacity:0;","to":"o:1;","ease":"Power4.easeOut"},{"delay":"wait","speed":300,"to":"opacity:0;","ease":"nothing"}]'
                        data-type="text" data-typewriter='{
                                    "lines":"El%20mejor%20sistema%20web%20de%20administración",
                                    "enabled":"on",
                                    "speed":"60",
                                    "start_delay":"1500",
                                    "looped":"off"
                                }' data-x="['left','left','left','center']" data-y="center"
                        data-voffset="['-10','-10','-10','-10']" data-responsive_offset="on"
                        data-width="['750','750','750','500']" data-fontsize="['48','48','48','28']"
                        data-lineheight="['55','55','55','35']" data-textAlign="['left','left','left','center']">Dicho o
                        slogan de la cancha</div>

                    <!-- Subtítulo de la diapositiva -->
                    <div class="tp-caption font-weight-light text-color-light ws-normal"
                        data-frames='[{"from":"opacity:0;","speed":300,"to":"o:1;","delay":2300,"split":"chars","splitdelay":0.05,"ease":"Power2.easeInOut"},{"delay":"wait","speed":1000,"to":"y:[100%];","mask":"x:inherit;y:inherit;s:inherit;e:inherit;","ease":"Power2.easeInOut"}]'
                        data-x="['left','left','left','center']" data-y="center" data-voffset="['40','40','40','40']"
                        data-width="['900','900','900','600']" data-fontsize="['18','18','18','16']"
                        data-lineheight="['26','26','26','22']" data-textAlign="['left','left','left','center']">10 e/
                        44 y 45</div>

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

<!-- Sección de encontrar canchas -->
<section class="section section-parallax bg-color-grey-scale-1 border-0 m-0 appear-animation"
    data-appear-animation="fadeIn" data-plugin-parallax
    data-plugin-options="{'speed': 1.5, 'parallaxHeight': '90%', 'offset': 70}"
    data-image-src="../config/dist/img/parallax/parallax-corporate-14-3.jpg"
    style="padding-top: 15px; padding-bottom: 15px;">
    <div class="container container-lg">
        <!-- Título siempre arriba -->
        <div class="row justify-content-center text-center mt-4">
            <!-- Cancha 1 -->
            <div class="col-sm-6 col-md-4 mb-3">
                <div class="d-flex justify-content-center align-items-center flex-column">
                    <img src="config/dist/img/loguito_lacanchita.WEBP" alt="Cancha 1" class="img-fluid cancha-img"
                        style="max-width: 33%;" onclick="openModal('Cancha 1')">
                    <strong class="mt-2">
                        <h4>Cancha 1</h4>
                    </strong>
                </div>
            </div>
            <!-- Cancha 2 -->
            <div class="col-sm-6 col-md-4 mb-3">
                <div class="d-flex justify-content-center align-items-center flex-column">
                    <img src="dist/img/cancha.png" alt="Cancha 2" class="img-fluid cancha-img"
                        style="max-width: 33%;" onclick="openModal('Cancha 2')">
                    <strong class="mt-2">
                        <h4>Cancha 22</h4>
                    </strong>
                </div>
            </div>
            <!-- Cancha 3 -->
            <div class="col-sm-6 col-md-4 mb-3">
                <div class="d-flex justify-content-center align-items-center flex-column position-relative">
                    <img src="img/cancha.PNG" alt="Cancha 3" class="img-fluid cancha-img"
                        style="max-width: 33%;" onclick="openModal('Cancha 3')">
                    <div class="ribbon"><span>No disponible</span></div>
                    <strong class="mt-2">
                        <h4>Cancha 3 Techada</h4>
                    </strong>
                </div>
            </div>
        </div>

        <!-- Filtros de búsqueda -->
        <div class="row justify-content-center">
            <div class="col-md-10">
                <!-- Formulario con los filtros -->
                <form>
                    <div class="form-row">
                        <!-- Campo de fecha -->
                        <div class="form-group col-md-4">
                            <label for="fecha">Fecha</label>
                            <input type="date" class="form-control" id="fecha" name="fecha">
                        </div>
                        <!-- Campo de Horarios Disponibles -->
                        <div class="form-group col-md-4">
                            <label for="localidad">Horarios Disponibles</label>
                            <select class="form-control" id="localidad" name="localidad">
                                <option value="">Seleccione un Horario</option>
                                <option value="08:00">08:00 a 09:00</option>
                                <option value="09:00">09:00 a 10:00</option>
                                <!-- Añade más opciones según sea necesario -->
                            </select>
                        </div>
                        <!-- Campo de Cancha -->
                        <div class="form-group col-md-4">
                            <label for="horario">Cancha</label>
                            <select class="form-control" id="horario" name="horario">
                                <option value="">Seleccione una Cancha</option>
                                <option value="1">Cancha 1</option>
                                <option value="2">Cancha 2</option>
                                <option value="3">Cancha 3 Techada</option>
                            </select>
                        </div>
                    </div>
                    <!-- Botón de búsqueda -->
                    <div class="form-row">
                        <div class="col text-center">
                            <button type="submit" class="btn btn-primary">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>


<style>
.position-relative {
    position: relative;
}

.ribbon {
    position: absolute;
    top: 0;
    right: 0;
    overflow: hidden;
    width: 75px;
    height: 75px;
}

.ribbon span {
    position: absolute;
    display: block;
    width: 100px;
    background: red;
    color: white;
    text-transform: uppercase;
    text-align: center;
    font-weight: bold;
    line-height: 25px;
    transform: rotate(45deg);
    -webkit-transform: rotate(45deg);
    top: 19px;
    right: -21px;
}

/* Agregar cursor de puntero en las imágenes */
.cancha-img {
    cursor: pointer;
}
</style>






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

<!-- Modal para el formulario de "Ingresar" -->
<div class="modal fade" id="modalIngresar" tabindex="-1" role="dialog" aria-labelledby="modalIngresarLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <!-- Encabezado del modal -->
            <div class="modal-header">
                <h5 class="modal-title color-primary font-weight-semibold text-4 text-uppercase mb-3"
                    id="modalIngresarLabel">Ingreso al Sistema</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <!-- Cuerpo del modal -->
            <div class="modal-body">
                <!-- Formulario de inicio de sesión -->
                <form action="../../procesar_login.php" id="frmSignIn" method="post">
                    <div class="form-row">
                        <div class="form-group col">
                            <label class="font-weight-bold text-dark text-2">Nombre de Usuario o E-mail</label>
                            <input type="text" class="form-control form-control-lg" name="username"
                                placeholder="Ingresa tu usuario o email" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col">
                            <label class="font-weight-bold text-dark text-2">Contraseña</label>
                            <input type="password" class="form-control form-control-lg" name="password"
                                placeholder="Ingresa tu contraseña" required>
                            <a class="float-right mt-2" href="#">¿Olvidaste tu contraseña?</a>
                        </div>
                    </div>
                    <div class="form-row align-items-center">
                        <div class="form-group col-6">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="rememberme">
                                <label class="custom-control-label text-2" for="rememberme">Recordarme</label>
                            </div>
                        </div>
                        <div class="form-group col-6 text-right">
                            <input type="submit" value="Ingresar" class="btn btn-primary"
                                data-loading-text="Loading...">
                        </div>
                    </div>
                </form>
                <hr>
                <!-- Botones de inicio de sesión con redes sociales -->
                <div class="text-center mt-3">
                    <p>O ingresar con</p>
                    <button type="button" class="btn btn-danger btn-block mb-2" id="btnGoogle">
                        <i class="fab fa-google mr-2"></i> Google
                    </button>
                    <button type="button" class="btn btn-primary btn-block" id="btnFacebook"
                        style="background-color: #3b5998; border-color: #3b5998;">
                        <i class="fab fa-facebook-f mr-2"></i> Facebook
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Reserva -->
<div class="modal fade" id="reservaModal" tabindex="-1" role="dialog" aria-labelledby="reservaModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reservaModalLabel">Reservar Cancha</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="fecha">Fecha</label>
                    <input type="date" class="form-control" id="modalFecha" name="fecha">
                </div>
                <div class="form-group">
                    <label>Horarios Disponibles</label>
                    <div id="horariosGrid" class="d-flex flex-wrap justify-content-around">
                        <!-- Horarios dinámicos se cargarán aquí -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary">Reservar</button>
            </div>
        </div>
    </div>
</div>


<!-- Scripts de terceros -->
<script src="../..//config/pluggins/vendor/jquery/jquery.min.js"></script>
<script src="../..//config/pluggins/vendor/jquery.appear/jquery.appear.min.js"></script>
<script src="../..//config/pluggins/vendor/jquery.easing/jquery.easing.min.js"></script>
<script src="../..//config/pluggins/vendor/jquery.cookie/jquery.cookie.min.js"></script>
<script src="../..//config/pluggins/vendor/popper/umd/popper.min.js"></script>
<script src="../..//config/pluggins/vendor/bootstrap/js/bootstrap.min.js"></script>
<script src="../..//config/pluggins/vendor/common/common.min.js"></script>
<script src="../..//config/pluggins/vendor/jquery.validation/jquery.validate.min.js"></script>
<script src="../..//config/pluggins/vendor/jquery.easy-pie-chart/jquery.easypiechart.min.js"></script>
<script src="../..//config/pluggins/vendor/jquery.gmap/jquery.gmap.min.js"></script>
<script src="../..//config/pluggins/vendor/jquery.lazyload/jquery.lazyload.min.js"></script>
<script src="../..//config/pluggins/vendor/isotope/jquery.isotope.min.js"></script>
<script src="../..//config/pluggins/vendor/owl.carousel/owl.carousel.min.js"></script>
<script src="../..//config/pluggins/vendor/magnific-popup/jquery.magnific-popup.min.js"></script>
<script src="../..//config/pluggins/vendor/vide/jquery.vide.min.js"></script>
<script src="../..//config/pluggins/vendor/vivus/vivus.min.js"></script>

<!-- Scripts del tema -->
<script src="../../config/dist/js/theme.js"></script>

<!-- Scripts específicos de la página -->
<script src="../../config/pluggins/vendor/rs-plugin/js/jquery.themepunch.tools.min.js"></script>
<script src="../../config/pluggins/vendor/rs-plugin/js/jquery.themepunch.revolution.min.js"></script>

<!-- Complemento del Slider Revolution para efectos de máquina de escribir -->
<!-- Asegúrate de que este archivo existe en tu proyecto -->
<script src="../../config/pluggins/vendor/rs-plugin/revolution-addons/typewriter/js/revolution.addon.typewriter.min.js">
</script>

<!-- Scripts personalizados -->
<script src="../../config/dist/js/custom.js"></script>

<!-- Inicialización del tema -->
<script src="../../config/dist/js/theme.init.js"></script>

<!-- Google Analytics: cambia UA-XXXXX-X por el ID de tu sitio -->
<script>
(function(i, s, o, g, r, a, m) {
    i['GoogleAnalyticsObject'] = r;
    i[r] = i[r] || function() {
        (i[r].q = i[r].q || []).push(arguments)
    }, i[r].l = 1 * new Date();
    a = s.createElement(o),
        m = s.getElementsByTagName(o)[0];
    a.async = 1;
    a.src = g;
    m.parentNode.insertBefore(a, m)
})(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

ga('create', 'UA-12345678-1', 'auto');
ga('send', 'pageview');
</script>


<!-- Scripts adicionales -->
<script>
// Validación de formularios al abrir el modal
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();

// Abrir modales con eventos
$('#loginModal').on('shown.bs.modal', function() {
    $('#username').trigger('focus');
});
</script>
<script>
function openModal(cancha) {
    document.getElementById('reservaModalLabel').innerText = `Reservar ${cancha}`;
    const horarios = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00'];
    const horariosGrid = document.getElementById('horariosGrid');
    horariosGrid.innerHTML = ''; // Limpiar horarios previos
    horarios.forEach(horario => {
        const div = document.createElement('div');
        div.className = 'p-2 border text-center';
        div.style.width = '60px';
        div.style.cursor = 'pointer';
        div.innerText = horario;
        horariosGrid.appendChild(div);
    });
    $('#reservaModal').modal('show');
}
</script>
</body>

</html>
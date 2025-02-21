<!DOCTYPE html>
<html lang="es">

<head>
    <!-- Básico -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>La Canchita - Sistema Web para la Gestión de Canchas Deportivas</title>
    <meta name="keywords" content="alquiler de canchas, gestión deportiva, reservas online, sistema web, La Canchita">
    <meta name="description"
        content="Potencia el alquiler y la gestión de tus canchas deportivas con nuestro sistema web fácil de usar y totalmente personalizable.">
    <meta name="author" content="okler.net">
    <!-- Ruta actualizada del favicon -->
    <link rel="shortcut icon" href="config/dist/img/loguito_lacanchita.WEBP" type="image/webp">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, shrink-to-fit=no">

    <!-- Fuentes Web -->
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800%7CShadows+Into+Light%7CPlayfair+Display:400"
        rel="stylesheet" type="text/css">

    <!-- CSS de proveedores (bibliotecas de terceros) -->
    <link rel="stylesheet" href="config/pluggins/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="config/pluggins/vendor/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="config/pluggins/vendor/animate/animate.min.css">
    <link rel="stylesheet" href="config/pluggins/vendor/simple-line-icons/css/simple-line-icons.min.css">
    <link rel="stylesheet" href="config/pluggins/vendor/owl.carousel/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="config/pluggins/vendor/owl.carousel/assets/owl.theme.default.min.css">
    <link rel="stylesheet" href="config/pluggins/vendor/magnific-popup/magnific-popup.min.css">

    <!-- CSS del tema -->
    <link rel="stylesheet" href="config/css/theme.css">
    <link rel="stylesheet" href="config/css/theme-elements.css">
    <link rel="stylesheet" href="config/css/theme-blog.css">
    <link rel="stylesheet" href="config/css/theme-shop.css">

    <!-- CSS específico de la página actual -->
    <link rel="stylesheet" href="config/pluggins/vendor/rs-plugin/css/settings.css">
    <link rel="stylesheet" href="config/pluggins/vendor/rs-plugin/css/layers.css">
    <link rel="stylesheet" href="config/pluggins/vendor/rs-plugin/css/navigation.css">

    <!-- Skin CSS (aplicación de estilos específicos) -->
    <link rel="stylesheet" href="config/css/skins/skin-corporate-14.css">

    <!-- CSS personalizado -->
    <link rel="stylesheet" href="config/css/custom.css">

    <!-- Librerías del Head -->
    <script src="config/pluggins/vendor/modernizr/modernizr.min.js"></script>
</head>

<body class="loading-overlay-showing" data-plugin-page-transition data-loading-overlay
    data-plugin-options="{'hideDelay': 500}">
    <!-- Capa de carga -->
    <div class="loading-overlay">
        <div class="bounce-loader">
            <div class="bounce1"></div>
            <div class="bounce2"></div>
            <div class="bounce3"></div>
        </div>
    </div>
	    <!-- ID en la parte superior de la página -->
		<div id="inicio"></div>

    <!-- Contenido principal del sitio -->
    <div class="body">
        <!-- Cabecera (header) del sitio -->
        <header id="header" class="header-transparent header-semi-transparent header-semi-transparent-dark"
            data-plugin-options="{'stickyEnabled': true, 'stickyEnableOnBoxed': true, 'stickyEnableOnMobile': true, 'stickyChangeLogo': false, 'stickyStartAt': 53, 'stickySetTop': '-53px'}">
            <div class="header-body border-top-0 bg-dark box-shadow-none">
                <!-- Barra superior de la cabecera -->
                <div class="header-top header-top-borders header-top-light-2-borders">
                    <div class="container container-lg h-100">
                        <div class="header-row h-100">
                            <!-- Columna izquierda del header -->
                            <div class="header-column justify-content-start">
                                <div class="header-row">
                                    <nav class="header-nav-top">
                                        <ul class="nav nav-pills">
                                            <!-- Dirección -->
                                            <li class="nav-item nav-item-borders py-2 d-none d-sm-inline-flex">
                                                <span class="pl-0"><i
                                                        class="far fa-dot-circle text-4 text-color-primary"
                                                        style="top: 1px;"></i> Calle 54 nro. 630, La Plata</span>
                                            </li>
                                            <!-- Teléfono -->
                                            <li class="nav-item nav-item-borders py-2">
                                                <a href="tel:123-456-7890"><i
                                                        class="fab fa-whatsapp text-4 text-color-primary"
                                                        style="top: 0;"></i> 221-000-0000</a>
                                            </li>
                                            <!-- Correo electrónico -->
                                            <li class="nav-item nav-item-borders py-2 d-none d-md-inline-flex">
                                                <a href="mailto:mail@domain.com"><i
                                                        class="far fa-envelope text-4 text-color-primary"
                                                        style="top: 1px;"></i> efegene@domain.com</a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                            <!-- Columna derecha del header -->
                            <div class="header-column justify-content-end">
                                <div class="header-row">
                                    <nav class="header-nav-top">
                                        <ul class="nav nav-pills">
                                            <!-- Enlace a DesarrollosWeb -->
                                            <li class="nav-item nav-item-borders py-2 d-none d-lg-inline-flex">
                                                <a href="#">DesarrollosWeb</a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Contenedor principal de la cabecera -->
                <div class="header-container header-container-height-sm container container-lg">
                    <div class="header-row">
                        <!-- Columna izquierda del header (logo) -->
                        <div class="header-column">
                            <div class="header-row">
                                <div class="header-logo">
                                    <a href="index.html">
                                        <img alt="Lacanchita" width="110" height="70"
                                            src="config/dist/img/loguito_lacanchita.webp">
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- Columna derecha del header (navegación) -->
                        <div class="header-column justify-content-end" >
                            <div class="header-row">
                                <!-- Menú de navegación principal -->
                                <div
                                    class="header-nav header-nav-links header-nav-dropdowns-dark header-nav-light-text order-2 order-lg-1">
                                    <div
                                        class="header-nav-main header-nav-main-mobile-dark header-nav-main-square header-nav-main-dropdown-no-borders header-nav-main-effect-2 header-nav-main-sub-effect-1">
                                        <nav class="collapse">
                                            <ul class="nav nav-pills" id="mainNav">
                                                <!-- Menú "Home" -->
                                                <li class="dropdown">
                                                    <a class="dropdown-item dropdown-toggle" href="#inicio">Inicio</a>
                                                </li>
                                                <!-- Menú "Elements" con mega menú -->
                                                <li class="dropdown dropdown-mega">
                                                    <a class="dropdown-item dropdown-toggle" href="elements.html">
                                                        Preguntas Frecuentes
                                                    </a>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <div class="dropdown-mega-content">
                                                                <div class="row">
                                                                    <div class="col-lg-3">
                                                                        <span
                                                                            class="dropdown-mega-sub-title">Secciones</span>
                                                                        <ul class="dropdown-mega-sub-nav">
                                                                            <li><a class="dropdown-item"
                                                                                    href="elements-accordions.html">Como
                                                                                    Registrarse</a></li>
                                                                            <li><a class="dropdown-item"
                                                                                    href="elements-toggles.html">Ingreso
                                                                                    al Sistemas</a></li>
                                                                            <li><a class="dropdown-item"
                                                                                    href="elements-icon-boxes.html">Beneficios</a>
                                                                            </li>
                                                                            <li><a class="dropdown-item"
                                                                                    href="elements-tabs.html">Como
                                                                                    contratarnos?</a></li>
                                                                            <li><a class="dropdown-item"
                                                                                    href="elements-icons.html">Atencion
                                                                                    de Usuarios</a></li>
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                </li>
                                                <!-- Menú "Ingresar" -->
                                                <li class="dropdown">
                                                    <a class="dropdown-item dropdown-toggle" href="login.php">
                                                        Ingresar
                                                    </a>
                                                </li>


                                                <!-- Menú "Registrarse" -->
                                                <li class="dropdown">
                                                    <a class="dropdown-item dropdown-toggle" href="register.php">
                                                        Registrarse
                                                    </a>
                                                </li>

                                            </ul>
                                        </nav>
                                    </div>
                                    <!-- Botón de colapso para navegación móvil -->
                                    <button class="btn header-btn-collapse-nav" data-toggle="collapse"
                                        data-target=".header-nav-main nav">
                                        <i class="fas fa-bars"></i>
                                    </button>
                                </div>
                                <!-- Funciones adicionales del header (buscar, carrito) -->
                                <div
                                    class="header-nav-features header-nav-features-light header-nav-features-no-border header-nav-features-lg-show-border order-1 order-lg-2">
                                    <!-- Función de búsqueda -->
                                    <div class="header-nav-feature header-nav-features-search d-inline-flex">
                                        <a href="#" class="header-nav-features-toggle" data-focus="headerSearch"><i
                                                class="fas fa-search header-nav-top-icon"></i></a>
                                        <div class="header-nav-features-dropdown header-nav-features-dropdown-mobile-fixed"
                                            id="headerTopSearchDropdown">
                                            <form role="search" action="page-search-results.html" method="get">
                                                <div class="simple-search input-group">
                                                    <input class="form-control text-1" id="headerSearch" name="q"
                                                        type="search" value="" placeholder="Buscar...">
                                                    <span class="input-group-append">
                                                        <button class="btn" type="submit">
                                                            <i class="fa fa-search header-nav-top-icon"></i>
                                                        </button>
                                                    </span>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <!-- Función de carrito -->
                                    <div class="header-nav-feature header-nav-features-cart d-inline-flex ml-2">
                                        <a href="#" class="header-nav-features-toggle">
                                            <img src="config/dist/img/icons/icon-cart-light.svg" width="14" alt=""
                                                class="header-nav-top-icon-img">
                                            <span class="cart-info">
                                                <span class="cart-qty">1</span>
                                            </span>
                                        </a>
                                        <div class="header-nav-features-dropdown" id="headerTopCartDropdown">
                                            <ol class="mini-products-list">
                                                <li class="item">
                                                    <a href="#" title="Camera X1000" class="product-image"></a>
                                                    <div class="product-details">
                                                        <p class="product-name">
                                                            <a href="#">
                                                                <h3>Turno Reservado</h3>
                                                            </a>
                                                        </p>
                                                        <p class="qty-price">
                                                        <h2>Cancha 1 -<span class="price"> 19:30hs</span></h2>
                                                        </p>
                                                        <a href="#" title="Remove This Item" class="btn-remove"><i
                                                                class="fas fa-times"></i></a>
                                                    </div>
                                                </li>
                                            </ol>
                                            <div class="totals">
                                                <span class="label">Total:</span>
                                                <span class="price-total"><span class="price">$15000</span></span><br>
                                                <span class="label">Seña:</span>
                                                <span class="price-total"><span class="price">$5000</span></span><br>
                                                <span class="label">A Cobrar:</span>
                                                <span class="price-total"><span class="price">$10000</span></span>
                                            </div>
                                            <div class="actions">
                                                <a class="btn btn-dark" href="#">Ver Reserva</a>
                                                <a class="btn btn-primary" href="#">Siguiente <i
                                                        class="fas fa-arrow-right ml-1"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

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

                            <img src="config/dist/img/ESTADIO.webp" alt="" data-bgposition="center center"
                                data-bgfit="cover" data-bgrepeat="no-repeat" class="rev-slidebg">

                            <!-- Título de la diapositiva -->
                            <h1 class="tp-caption font-weight-extra-bold text-color-light"
                                data-frames='[{"delay":1000,"speed":500,"frame":"0","from":"opacity:0;x:50%;","to":"o:1;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;fb:0;","ease":"Power3.easeInOut"}]'
                                data-x="['left','left','left','center']" data-y="center"
                                data-voffset="['-50','-50','-50','-50']" data-fontsize="['48','48','48','36']"
                                data-lineheight="['55','55','55','40']" data-letterspacing="-1">Potencia el alquiler de
                                tus canchas.</h1>

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
                                data-lineheight="['55','55','55','35']"
                                data-textAlign="['left','left','left','center']">El mejor sistema web de administración
                            </div>

                            <!-- Subtítulo de la diapositiva -->
                            <div class="tp-caption font-weight-light text-color-light ws-normal"
                                data-frames='[{"from":"opacity:0;","speed":300,"to":"o:1;","delay":2300,"split":"chars","splitdelay":0.05,"ease":"Power2.easeInOut"},{"delay":"wait","speed":1000,"to":"y:[100%];","mask":"x:inherit;y:inherit;s:inherit;e:inherit;","ease":"Power2.easeInOut"}]'
                                data-x="['left','left','left','center']" data-y="center"
                                data-voffset="['40','40','40','40']" data-width="['900','900','900','600']"
                                data-fontsize="['18','18','18','16']" data-lineheight="['26','26','26','22']"
                                data-textAlign="['left','left','left','center']">100% adaptable a tus necesidades</div>

                            <!-- Botón de acción en la diapositiva -->
                            <a class="tp-caption btn btn-primary font-weight-bold rounded" href="#"
                                data-frames='[{"delay":3000,"speed":2000,"frame":"0","from":"y:50%;opacity:0;","to":"y:0;o:1;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;fb:0;","ease":"Power3.easeInOut"}]'
                                data-x="['left','left','left','center']" data-y="center"
                                data-voffset="['90','90','90','110']" data-paddingtop="['16','16','16','12']"
                                data-paddingbottom="['16','16','16','12']" data-paddingleft="['40','40','40','30']"
                                data-paddingright="['40','40','40','30']" data-fontsize="['14','14','14','12']"
                                data-lineheight="['20','20','20','18']">Así funciona<i
                                    class="fas fa-arrow-right ml-1"></i></a>

                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sección de encontrar canchas -->
        <section class="section section-height-3 section-parallax bg-color-grey-scale-1 border-0 m-0 appear-animation"
            data-appear-animation="fadeIn" data-plugin-parallax
            data-plugin-options="{'speed': 1.5, 'parallaxHeight': '100%', 'offset': 70}"
            data-image-src="config/dist/img/parallax/parallax-corporate-14-3.jpg">
            <div class="container container-lg">
                <!-- Título siempre arriba -->
                <div class="row">
                    <div class="col-12 text-center">
                        <h2 class="font-weight-bold text-9 mb-4">¡Encontrá cancha para jugar de manera fácil!</h2>
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
                                <!-- Campo de localidad -->
                                <div class="form-group col-md-4">
                                    <label for="localidad">Localidad</label>
                                    <select class="form-control" id="localidad" name="localidad">
                                        <option value="">Seleccione una localidad</option>
                                        <option value="localidad1">Localidad 1</option>
                                        <option value="localidad2">Localidad 2</option>
                                        <!-- Añade más opciones según sea necesario -->
                                    </select>
                                </div>
                                <!-- Campo de horario -->
                                <div class="form-group col-md-4">
                                    <label for="horario">Horario</label>
                                    <select class="form-control" id="horario" name="horario">
                                        <option value="">Seleccione un horario</option>
                                        <option value="08:00">08:00</option>
                                        <option value="09:00">09:00</option>
                                        <option value="10:00">10:00</option>
                                        <!-- Añade más opciones según sea necesario -->
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
                <!-- Botones de canchas -->
                <div class="row mt-4">
                    <!-- Botón 1 -->
                    <div class="col-sm-6 col-md-3 mb-3 appear-animation" data-appear-animation="fadeInUpShorter"
                        data-appear-animation-delay="0">
                        <button type="button" class="btn btn-outline btn-gradient w-100 h-100">
                            <strong>Segurola y Habana</strong><br>
                            66 e/ 16 y 17 LP
                        </button>
                    </div>
                    <!-- Botón 2 -->
                    <div class="col-sm-6 col-md-3 mb-3 appear-animation" data-appear-animation="fadeInUpShorter"
                        data-appear-animation-delay="200">
                        <button type="button" class="btn btn-outline btn-gradient w-100 h-100">
                            <strong>Cancha 2</strong><br>
                            Dirección de la cancha 2
                        </button>
                    </div>
                    <!-- Botón 3 -->
                    <div class="col-sm-6 col-md-3 mb-3 appear-animation" data-appear-animation="fadeInUpShorter"
                        data-appear-animation-delay="400">
                        <button type="button" class="btn btn-outline btn-gradient w-100 h-100">
                            <strong>Cancha 3</strong><br>
                            Dirección de la cancha 3
                        </button>
                    </div>
                    <!-- Botón 4 -->
                    <div class="col-sm-6 col-md-3 mb-3 appear-animation" data-appear-animation="fadeInUpShorter"
                        data-appear-animation-delay="600">
                        <button type="button" class="btn btn-outline btn-gradient w-100 h-100">
                            <strong>Cancha 4</strong><br>
                            Dirección de la cancha 4
                        </button>
                    </div>
                    <!-- Añade más botones según sea necesario -->
                </div>
            </div>
        </section>



        <!-- Sección de contenido con animaciones -->
        <div class="container container-lg py-5 my-5">
            <div class="row justify-content-center">
                <div class="col-xl-9 text-center">
                    <h2 class="font-weight-bold text-11 appear-animation" data-appear-animation="fadeInUpShorter">
                        EFEGENE <BR>desarrollos webs</h2>
                    <p class="line-height-9 text-4 opacity-9 appear-animation" data-appear-animation="fadeInUpShorter"
                        data-appear-animation-delay="200">Este sistema está diseñado específicamente para facilitar y
                        optimizar la gestión diaria de sus canchas, ya sea que prefieran una solución online, accesible
                        desde cualquier dispositivo con conexión a internet, o una solución offline, que funcione
                        perfectamente sin necesidad de estar conectado.</p>
                </div>
            </div>
            <div class="row featured-boxes featured-boxes-style-4">
                <div class="col-sm-6 col-lg-3 appear-animation" data-appear-animation="fadeInLeftShorter"
                    data-appear-animation-delay="400">
                    <div class="featured-box mb-lg-0">
                        <div class="box-content px-lg-1 px-xl-5">
                            <i class="icon-featured icons icon-bubbles text-color-primary text-11"></i>
                            <h4 class="font-weight-bold text-5 mb-3">Mensajería</h4>
                            <p>La mensajería automática sobre los turnos reservados reduce olvidos, mejora la asistencia
                                y fideliza a tus clientes, garantizando un uso óptimo de tus canchas.</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3 appear-animation" data-appear-animation="fadeInLeftShorter"
                    data-appear-animation-delay="200">
                    <div class="featured-box mb-lg-0">
                        <div class="box-content px-lg-1 px-xl-5">
                            <i class="icon-featured icons icon-organization text-color-primary text-11"></i>
                            <h4 class="font-weight-bold text-5 mb-3">Planifica tus reservas</h4>
                            <p>
                                Planificar tus reservas de manera efectiva maximiza el uso de tus canchas, evita
                                conflictos de horarios, aumenta tus ingresos y mejora la experiencia de tus clientes.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3 appear-animation" data-appear-animation="fadeInRightShorter"
                    data-appear-animation-delay="200">
                    <div class="featured-box mb-sm-0">
                        <div class="box-content px-lg-1 px-xl-5">
                            <i class="icon-featured icons icon-cup text-color-primary text-11"></i>
                            <h4 class="font-weight-bold text-5 mb-3">Sistemas Personalizados</h4>
                            <p>Destaca tu marca con tu logo e imágenes adaptadas a tu estilo.</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3 appear-animation" data-appear-animation="fadeInRightShorter"
                    data-appear-animation-delay="400">
                    <div class="featured-box mb-0">
                        <div class="box-content px-lg-1 px-xl-5">
                            <i class="icon-featured icons icon-wrench text-color-primary text-11"></i>
                            <h4 class="font-weight-bold text-5 mb-3">Asistencia Continua</h4>
                            <p>Siempre a tu lado con soporte continuo a través de WhatsApp.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección con efecto parallax -->
        <section class="section section-parallax bg-color-grey-scale-1 border-0 m-0 appear-animation"
            data-appear-animation="fadeIn" data-plugin-parallax
            data-plugin-options="{'speed': 1.5, 'parallaxHeight': '116%'}"
            data-image-src="config/dist/img/parallax/parallax-corporate-14-1.jpg">
            <div class="container container-lg">
                <div class="row justify-content-between align-items-center">
                    <div class="col-md-7 order-2 order-md-1 appear-animation" data-appear-animation="fadeInRightShorter"
                        data-appear-animation-delay="200">
                        <span class="font-weight-bold text-color-dark opacity-8 text-4">SISTEMA WEB Y APP</span>
                        <h2 class="font-weight-bold text-9 mb-4">Aplicación móvil para que lleves el control a todos
                            lados</h2>
                        <ul class="list list-icons pb-2 mb-4">
                            <li><i class="fas fa-caret-right top-6"></i> <span class="text-4">Llevá el control de tus
                                    canchas a donde vayas con nuestra aplicación móvil.</span></li>
                            <li><i class="fas fa-caret-right top-6"></i> <span class="text-4">Tomá reservas desde
                                    cualquier lugar, en cualquier momento.</span></li>
                            <li><i class="fas fa-caret-right top-6"></i> <span class="text-4">Consultá turnos en tiempo
                                    real y evitá confusiones.</span></li>
                            <li><i class="fas fa-caret-right top-6"></i> <span class="text-4">Accedé a estadísticas
                                    detalladas sobre facturación y ocupación.</span></li>
                            <li><i class="fas fa-caret-right top-6"></i> <span class="text-4">Optimizá la gestión de tu
                                    negocio desde la palma de tu mano.</span></li>
                        </ul>
                    </div>
                    <!-- Segunda imagen del smartphone -->
                    <div class="col-md-4 text-center text-md-left order-1 order-md-2 mb-5 mb-md-0 appear-animation"
                        data-appear-animation="fadeInRightShorter" data-appear-animation-delay="400">
                        <img src="img/smartphone-corporate-14-1.png" class="img-fluid" alt="Aplicación móvil" />
                    </div>
                </div>
            </div>
        </section>


        <!-- Sección adicional con efecto parallax -->
        <section class="section section-height-3 section-parallax bg-color-light border-0 m-0" data-plugin-parallax
            data-plugin-options="{'speed': 1.5, 'parallaxHeight': '100%', 'offset': 70}"
            data-image-src="config/dist/img/parallax/parallax-corporate-14-2.jpg">
            <div class="container container-lg">
                <div class="row align-items-center">
                    <!-- Imagen del smartphone -->
                    <div class="col-md-6 col-lg-5 col-xl-6 text-center pr-5 mb-5 mb-md-0 appear-animation"
                        data-appear-animation="fadeInLeftShorter" data-appear-animation-delay="400">
                        <img src="config/dist/img/smartphone-corporate-14-2.png" class="img-fluid"
                            alt="Aplicación móvil" />
                    </div>
                    <!-- Contenido textual -->
                    <div class="col-md-6 col-lg-7 col-xl-6 appear-animation" data-appear-animation="fadeInLeftShorter"
                        data-appear-animation-delay="200">
                        <span class="font-weight-bold text-color-dark opacity-8 text-4">EXCLUSIVO</span>
                        <h2 class="font-weight-bold text-9 mb-4">Cambiá el estilo a tu manera</h2>
                        <ul class="list list-icons pb-2 mb-4">
                            <li><i class="fas fa-caret-right top-6"></i> <span class="text-4">Personalizá el estilo como
                                    más te guste.</span></li>
                            <li><i class="fas fa-caret-right top-6"></i> <span class="text-4">Subí tu logo e imágenes de
                                    tu cancha.</span></li>
                            <li><i class="fas fa-caret-right top-6"></i> <span class="text-4">Elegí los colores que
                                    representen a tu marca.</span></li>
                            <li><i class="fas fa-caret-right top-6"></i> <span class="text-4">Creá promociones y ofertas
                                    personalizadas.</span></li>
                            <li><i class="fas fa-caret-right top-6"></i> <span class="text-4">Actualizá fácilmente la
                                    información de tus servicios.</span></li>
                        </ul>
                        <a href="#" class="btn btn-primary font-weight-semibold rounded-0 btn-px-5 py-3 text-2">MÁS
                            INFORMACIÓN</a>
                    </div>
                </div>
            </div>
        </section>



        <!-- Sección de blog -->
        <div class="container container-lg py-5 my-5">
            <div class="row mb-3">
                <div class="col text-center">
                    <span class="font-weight-bold text-color-dark opacity-8 text-4">EFEGENE</span>
                    <h2 class="font-weight-semibold text-9 mb-3">Ellos nos Recomiendan</h2>
                    <p class="text-4">Comentar algo mas o sacar</p>
                </div>
            </div>
            <div class="row">
                <!-- Entrada de blog 1 -->
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0">
                        <div class="card-body px-0 py-5">
                            <h4 class="font-weight-semibold text-5 line-height-3 ls-0 mb-3"><a href="#"
                                    class="text-dark text-decoration-none">Mejora en la Ocupacion de Turnos</a></h4>
                            <p class="mb-4">Gracias a la visualizacion en linea de turnos disponibles logramos ocupar en
                                un 90% nuestros turnos</p>
                            <div class="d-flex align-items-center">
                                <img src="img/team/team-1.jpg" class="img-fluid rounded-circle mr-2" width="25"
                                    alt="" />
                                <strong class="text-color-dark text-2">por Estadio 22</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Entrada de blog 2 -->
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0">
                        <div class="card-body px-0 py-5">
                            <h4 class="font-weight-semibold text-5 line-height-3 ls-0 mb-3"><a href="#"
                                    class="text-dark text-decoration-none">Lorem ipsum dolor sit amet, consectetur</a>
                            </h4>
                            <p class="mb-4">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed eget risus
                                porta...</p>
                            <div class="d-flex align-items-center">
                                <img src="img/team/team-2.jpg" class="img-fluid rounded-circle mr-2" width="25"
                                    alt="" />
                                <strong class="text-color-dark text-2">por El Adriatico</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Entrada de blog 3 -->
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0">
                        <div class="card-body px-0 py-5">
                            <h4 class="font-weight-semibold text-5 line-height-3 ls-0 mb-3"><a href="#"
                                    class="text-dark text-decoration-none">Lorem ipsum dolor sit amet, consectetur</a>
                            </h4>
                            <p class="mb-4">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed eget risus
                                porta...</p>
                            <div class="d-flex align-items-center">
                                <img src="img/team/team-3.jpg" class="img-fluid rounded-circle mr-2" width="25"
                                    alt="" />
                                <strong class="text-color-dark text-2">by John Doe</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Entrada de blog 4 -->
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0">
                        <div class="card-body px-0 py-5">
                            <h4 class="font-weight-semibold text-5 line-height-3 ls-0 mb-3"><a href="#"
                                    class="text-dark text-decoration-none">Lorem ipsum dolor sit amet, consectetur</a>
                            </h4>
                            <p class="mb-4">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed eget risus
                                porta...</p>
                            <div class="d-flex align-items-center">
                                <img src="img/team/team-4.jpg" class="img-fluid rounded-circle mr-2" width="25"
                                    alt="" />
                                <strong class="text-color-dark text-2">by Jennifer Doe</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Pie de página -->
    <footer id="footer" class="mt-0">
        <div class="container container-lg my-4">
            <div class="row py-5">
                <!-- Columna de descripción del pie de página -->
                <div class="col-lg-4 mb-5 mb-lg-0 text-center text-lg-left pt-3">
                    <h5 class="text-5 text-transform-none font-weight-semibold text-color-light mb-4">Porto Template
                    </h5>
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
                    <p class="text-4 mb-2"><span class="text-color-light">Address:</span> 1234 Street Name, City Name,
                        USA</p>
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
    <script src="config/pluggins/vendor/jquery/jquery.min.js"></script>
    <script src="config/pluggins/vendor/jquery.appear/jquery.appear.min.js"></script>
    <script src="config/pluggins/vendor/jquery.easing/jquery.easing.min.js"></script>
    <script src="config/pluggins/vendor/jquery.cookie/jquery.cookie.min.js"></script>
    <script src="config/pluggins/vendor/popper/umd/popper.min.js"></script>
    <script src="config/pluggins/vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="config/pluggins/vendor/common/common.min.js"></script>
    <script src="config/pluggins/vendor/jquery.validation/jquery.validate.min.js"></script>
    <script src="config/pluggins/vendor/jquery.easy-pie-chart/jquery.easypiechart.min.js"></script>
    <script src="config/pluggins/vendor/jquery.gmap/jquery.gmap.min.js"></script>
    <script src="config/pluggins/vendor/jquery.lazyload/jquery.lazyload.min.js"></script>
    <script src="config/pluggins/vendor/isotope/jquery.isotope.min.js"></script>
    <script src="config/pluggins/vendor/owl.carousel/owl.carousel.min.js"></script>
    <script src="config/pluggins/vendor/magnific-popup/jquery.magnific-popup.min.js"></script>
    <script src="config/pluggins/vendor/vide/jquery.vide.min.js"></script>
    <script src="config/pluggins/vendor/vivus/vivus.min.js"></script>

    <!-- Scripts del tema -->
    <script src="config/dist/js/theme.js"></script>

    <!-- Scripts específicos de la página -->
    <script src="config/pluggins/vendor/rs-plugin/js/jquery.themepunch.tools.min.js"></script>
    <script src="config/pluggins/vendor/rs-plugin/js/jquery.themepunch.revolution.min.js"></script>

    <!-- Complemento del Slider Revolution para efectos de máquina de escribir -->
    <!-- Asegúrate de que este archivo existe en tu proyecto -->
    <script src="config/pluggins/vendor/rs-plugin/revolution-addons/typewriter/js/revolution.addon.typewriter.min.js">
    </script>

    <!-- Scripts personalizados -->
    <script src="config/dist/js/custom.js"></script>

    <!-- Inicialización del tema -->
    <script src="config/dist/js/theme.init.js"></script>

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
</body>

</html>
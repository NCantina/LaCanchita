<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
?>
	<!-- Contenido principal del sitio -->
	<div class="body">
		<!-- Cabecera (header) del sitio -->
		<header id="header" class="header-transparent header-semi-transparent header-semi-transparent-dark" data-plugin-options="{'stickyEnabled': true, 'stickyEnableOnBoxed': true, 'stickyEnableOnMobile': true, 'stickyChangeLogo': false, 'stickyStartAt': 53, 'stickySetTop': '-53px'}">
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
												<span class="pl-0"><i class="far fa-dot-circle text-4 text-color-primary" style="top: 1px;"></i> Calle de tu cancha</span>
											</li>
											<!-- Teléfono -->
											<li class="nav-item nav-item-borders py-2">
												<a href="tel:123-456-7890"><i class="fab fa-whatsapp text-4 text-color-primary" style="top: 0;"></i> Telefono de tu cancha</a>
											</li>
											<!-- Correo electrónico -->
											<li class="nav-item nav-item-borders py-2 d-none d-md-inline-flex">
												<a href="mailto:mail@domain.com"><i class="far fa-envelope text-4 text-color-primary" style="top: 1px;"></i> Mail de contacto opcional</a>
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
												<a href="#">EFEGENE|DesarrollosWeb</a>
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
										<img alt="Lacanchita" width="110" height="70" src="../../config/dist/img/loguito_lacanchita.WEBP">
									</a>
								</div>
							</div>
						</div>
						<!-- Columna derecha del header (navegación) -->
						<div class="header-column justify-content-end">
							<div class="header-row">
								<!-- Menú de navegación principal -->
								<div class="header-nav header-nav-links header-nav-dropdowns-dark header-nav-light-text order-2 order-lg-1">
									<div class="header-nav-main header-nav-main-mobile-dark header-nav-main-square header-nav-main-dropdown-no-borders header-nav-main-effect-2 header-nav-main-sub-effect-1">
										<nav class="collapse">
											<ul class="nav nav-pills" id="mainNav">
												<!-- Menú "Home" -->
												<li class="dropdown">
													<a class="dropdown-item dropdown-toggle" href="LaCanchitaCliente.php">
														Inicio
													</a>
												</li>
												<!-- Menú "Ingresar" -->
												<li class="dropdown">
													<a class="dropdown-item dropdown-toggle" href="#" data-toggle="modal" data-target="#modalIngresar">
														Ingresar
													</a>
												</li>
												<!-- Menú "Registrarse" -->
												<li class="dropdown">
													<a class="dropdown-item dropdown-toggle" href="#" data-toggle="modal" data-target="#registerModal">
														Registrarse
													</a>
												</li>
											</ul>
										</nav>
									</div>
									<!-- Botón de colapso para navegación móvil -->
									<button class="btn header-btn-collapse-nav" data-toggle="collapse" data-target=".header-nav-main nav">
										<i class="fas fa-bars"></i>
									</button>
								</div>
								<!-- Funciones adicionales del header (usuario, soporte) -->
								<div class="header-nav-features header-nav-features-light header-nav-features-no-border header-nav-features-lg-show-border order-1 order-lg-2">
									<!-- Función de Usuario -->
									<div class="header-nav-feature header-nav-features-user d-inline-flex">
										<a href="#" class="header-nav-features-toggle" data-focus="headerUser">
											<i class="fas fa-user header-nav-top-icon"></i>
										</a>
										<div class="header-nav-features-dropdown header-nav-features-dropdown-mobile-fixed" id="headerUserDropdown" style="background-color:ghostwhite;">
											<p class="text-center font-weight-bold mt-2 mb-1 text-dark"><?= htmlspecialchars($usuario_nombre) ?></p>
											<div class="d-flex justify-content-around p-2">
												<!-- Botón de Perfil -->
												<button class="btn btn-primary d-flex align-items-center mr-2" style="border-radius: 25px; padding: 10px 20px;">
													<i class="fas fa-user-circle mr-1"></i> Perfil
												</button>

												<!-- Botón de Home (Estadísticas) -->
												<a href="HomeCliente.php" class="btn btn-success d-flex align-items-center mr-2" style="border-radius: 25px; padding: 10px 20px;">
													<i class="fas fa-chart-line mr-1"></i> Home
												</a>

												<!-- Botón de Cerrar Sesión -->
												<a href="../../logout.php" class="btn btn-danger d-flex align-items-center" style="border-radius: 25px; padding: 10px 20px;">
													<i class="fas fa-sign-out-alt mr-1"></i> Cerrar Sesión
												</a>
											</div>
										</div>
									</div>


									<!-- Función de Soporte/Configuración -->
									<div class="header-nav-feature header-nav-features-config d-inline-flex ml-2">
										<a href="../SoporteGenericoCliente.php" class="header-nav-features-toggle" onclick="window.location.href='SoporteGenericoCliente.php'">
											<i class="fas fa-cog header-nav-top-icon"></i>
										</a>
									</div>

									<!-- Función de carrito (reserva de canchas) -->
									<div class="header-nav-feature header-nav-features-cart d-inline-flex ml-2">
										<a href="#" class="header-nav-features-toggle">
											<img src="../../config/dist/img/icons/icon-cart-light.svg" width="14" alt="" class="header-nav-top-icon-img">
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
														<a href="#" title="Remove This Item" class="btn-remove"><i class="fas fa-times"></i></a>
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
												<a class="btn btn-primary" href="#">Siguiente <i class="fas fa-arrow-right ml-1"></i></a>
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
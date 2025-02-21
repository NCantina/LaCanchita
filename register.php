<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Usuario - La Canchita</title>

    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="config/pluggins/vendor/bootstrap/css/bootstrap.min.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="config/css/custom.css">

    <!-- Carga los iconos desde el proyecto -->
    <link rel="stylesheet" href="config/pluggins/vendor/fontawesome-free/css/all.min.css">

    <style>
    /* Estilos personalizados */
    body,
    html {
        height: 100%;
        margin: 0;
    }

    /* Imagen de fondo del estadio */
    .bg {
        background-image: url('config/dist/img/ESTADIO.webp');
        height: 100%;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        position: relative;
    }

    /* Capa oscura sobre la imagen */
    .bg-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        /* Oscurece la imagen */
    }

    /* Ajustes de estilo para la imagen del logo */
    .logo-canchita {
        max-width: 150px;
        height: auto;
    }

    /* Estilos para el formulario */
    .register-form {
        background-color: rgba(255, 255, 255, 0.85);
        /* Fondo semitransparente */
        padding: 30px;
        border-radius: 10px;
        position: relative;
        /* Para estar encima de la capa oscura */
        z-index: 2;
    }
    </style>
</head>

<body>

    <!-- Fondo del estadio -->
    <div class="bg">
        <!-- Capa oscura para opacar la imagen -->
        <div class="bg-overlay"></div>

        <!-- Contenedor principal del formulario de registro -->
        <div class="container d-flex align-items-center justify-content-center" style="height: 100%;">
            <div class="row justify-content-center">
                <!-- Imagen centrada del logo -->
                <div class="col-12 text-center my-4">
                    <img src="config/dist/img/loguito_lacanchita.webp" alt="La Canchita" class="logo-canchita">
                </div>

                <!-- Card para el formulario de registro -->
                <div class="col-md-12">
                    <div class="card register-form">
                        <div class="card-body">
                            <h3 class="text-center mb-4">Registrar Usuario</h3>
                            <form action="procesar_registro.php" method="post">
                                <div class="form-row">
                                    <!-- Campo de nombre de usuario -->
                                    <div class="form-group col-md-6">
                                        <label for="username">Nombre de Usuario</label>
                                        <input type="text" class="form-control" id="username" name="username"
                                            placeholder="Ingresa tu nombre de usuario" required>
                                    </div>

                                    <!-- Campo de apellido -->
                                    <div class="form-group col-md-6">
                                        <label for="apellido">Apellido</label>
                                        <input type="text" class="form-control" id="apellido" name="apellido"
                                            placeholder="Ingresa tu apellido" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <!-- Campo de nombre -->
                                    <div class="form-group col-md-6">
                                        <label for="nombre">Nombre</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre"
                                            placeholder="Ingresa tu nombre" required>
                                    </div>

                                    <!-- Campo de DNI -->
                                    <div class="form-group col-md-6">
                                        <label for="dni">DNI</label>
                                        <input type="text" class="form-control" id="dni" name="dni"
                                            placeholder="Ingresa tu DNI" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <!-- Campo de Email -->
                                    <div class="form-group col-md-6">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            placeholder="Ingresa tu correo electrónico" required>
                                    </div>

                                    <!-- Campo de teléfono -->
                                    <div class="form-group col-md-6">
                                        <label for="telefono">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono"
                                            placeholder="Ingresa tu teléfono" required>
                                    </div>
                                </div>

                                <!-- Botón para registrarse -->
                                <div class="form-group">
                                    <button type="submit" class="btn btn-success w-100">Registrarse</button>
                                </div>

                                <!-- Botón para regresar al menú principal con flecha -->
                                <div class="form-group">
                                    <a href="index.php" class="btn btn-primary w-100">
                                        <i class="fas fa-arrow-left mr-2"></i> Regresar al menú principal
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS y dependencias -->
    <script src="config/pluggins/vendor/jquery/jquery.min.js"></script>
    <script src="config/pluggins/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>
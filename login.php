<?php
session_start();
$error  = $_SESSION['login_error'] ?? null;
$ok     = $_SESSION['registro_ok'] ?? null;
unset($_SESSION['login_error'], $_SESSION['registro_ok']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - La Canchita</title>

    <!-- Bootstrap CSS -->
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
    .login-form {
        background-color: rgba(255, 255, 255, 0.85);
        /* Fondo semitransparente */
        padding: 30px;
        border-radius: 10px;
        position: relative;
        /* Para estar encima de la capa oscura */
        z-index: 2;
    }

    /* Estilo para la fila de links */
    .links-row {
        display: flex;
        justify-content: space-between;
    }

    @media (max-width: 576px) {

        /* Ajustes para pantallas pequeñas */
        .login-form {
            padding: 20px;
        }

        /* Centra la fila de enlaces en pantallas pequeñas */
        .links-row {
            flex-direction: column;
            align-items: center;
        }

        .links-row div {
            margin-bottom: 10px;
        }
    }

    @media (max-width: 768px) {

        /* Ajustes para pantallas medianas */
        .col-md-12 {
            max-width: 90%;
            margin: auto;
        }
    }

    /* Estilo para el icono de la flecha */
    .btn-primary i {
        margin-right: 5px;
    }
    </style>
</head>

<body>

    <!-- Fondo del estadio -->
    <div class="bg">
        <!-- Capa oscura para opacar la imagen -->
        <div class="bg-overlay"></div>

        <!-- Contenedor principal del formulario de login -->
        <div class="container d-flex align-items-center justify-content-center" style="height: 100%;">
            <div class="row justify-content-center">
                <!-- Imagen centrada del logo -->
                <div class="col-12 text-center my-4">
                    <img src="config/dist/img/loguito_lacanchita.webp" alt="La Canchita" class="logo-canchita">
                </div>

                <!-- Card para el formulario -->
                <div class="col-md-12 col-lg-12">
                    <div class="card login-form">
                        <div class="card-body">
                            <h3 class="text-center mb-4">Iniciar Sesión</h3>
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>
                            <?php if ($ok): ?>
                                <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
                            <?php endif; ?>
                            <form action="procesar_login.php" method="post">
                                <!-- Campo de Usuario o Email -->
                                <div class="form-group">
                                    <label for="username">Usuario o Email</label>
                                    <input type="text" class="form-control" id="username" name="username"
                                        placeholder="Ingresa tu usuario o email" required>
                                </div>

                                <!-- Campo de Contraseña -->
                                <div class="form-group">
                                    <label for="password">Contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password"
                                        placeholder="Ingresa tu contraseña" required>
                                </div>

                                <!-- Campo de Recordarme -->
                                <div class="form-group form-check">
                                    <input type="checkbox" class="form-check-input" id="rememberme" name="rememberme">
                                    <label class="form-check-label" for="rememberme">Recordarme</label>
                                </div>

                                <!-- Botón para iniciar sesión -->
                                <div class="form-group">
                                    <button type="submit" class="btn btn-success w-100">Ingresar</button>
                                </div>

                                <!-- Fila con enlaces "Olvidé mi contraseña" y "Registrar usuario" -->
                                <div class="links-row">
                                    <div>
                                        <a href="register.php">Registrar usuario</a>
                                    </div>
                                    <div>
                                        <a href="recuperar_contraseña.php">¿Olvidaste tu contraseña?</a>
                                    </div>
                                </div>

                                <!-- Separador -->
                                <hr>

                                <!-- Botones para iniciar sesión con redes sociales -->
                                <div class="text-center mt-3">
                                    <p>O ingresa con</p>
                                    <button type="button" class="btn btn-danger btn-block mb-2" id="btnGoogle">
                                        <i class="fab fa-google mr-2"></i> Google
                                    </button>
                                    <button type="button" class="btn btn-primary btn-block" id="btnFacebook"
                                        style="background-color: #3b5998; border-color: #3b5998;">
                                        <i class="fab fa-facebook-f mr-2"></i> Facebook
                                    </button>
                                </div>

                                <!-- Botón para regresar al menú principal con flecha -->
                                <div class="form-group mt-3">
                                    <a href="index.php" class="btn btn-primary w-100">
                                        <i class="fas fa-arrow-left"></i> Regresar al menú principal
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
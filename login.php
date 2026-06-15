<?php
session_start();
$error = $_SESSION['login_error'] ?? null;
$ok    = $_SESSION['registro_ok'] ?? null;
unset($_SESSION['login_error'], $_SESSION['registro_ok']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Iniciar Sesión - La Canchita</title>
    <link rel="shortcut icon" href="config/dist/img/loguito_lacanchita.WEBP" type="image/webp">
    <link rel="stylesheet" href="config/pluggins/vendor/fontawesome-free/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #0a0a0a;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Fondo animado */
        .bg {
            position: fixed;
            inset: 0;
            background: url('config/dist/img/ESTADIO.webp') center/cover no-repeat;
            transform: scale(1.05);
            animation: bgZoom 20s ease-in-out infinite alternate;
        }
        @keyframes bgZoom {
            from { transform: scale(1.05); }
            to   { transform: scale(1.12); }
        }
        .bg-overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.82) 0%, rgba(0,40,10,0.75) 100%);
        }

        /* Partículas decorativas */
        .particles { position: fixed; inset: 0; pointer-events: none; overflow: hidden; }
        .particle {
            position: absolute;
            width: 3px; height: 3px;
            background: rgba(76,217,100,0.6);
            border-radius: 50%;
            animation: float linear infinite;
        }
        @keyframes float {
            0%   { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            100% { transform: translateY(-10vh) rotate(720deg); opacity: 0; }
        }

        /* Contenedor principal */
        .page {
            position: relative;
            z-index: 10;
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        /* Logo */
        .logo-wrap {
            margin-bottom: 28px;
            text-align: center;
            animation: slideDown 0.6s ease both;
        }
        .logo-wrap img { width: 110px; filter: drop-shadow(0 4px 20px rgba(76,217,100,0.4)); }
        .logo-wrap p {
            color: rgba(255,255,255,0.55);
            font-size: 12px;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 8px;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Card */
        .card {
            width: 100%;
            max-width: 420px;
            background: rgba(255,255,255,0.07);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 20px;
            padding: 36px 32px;
            animation: slideUp 0.6s ease 0.1s both;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.1);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: #fff;
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 6px;
        }
        .subtitle {
            text-align: center;
            color: rgba(255,255,255,0.45);
            font-size: 13px;
            margin-bottom: 28px;
        }

        /* Alertas */
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .alert-danger  { background: rgba(220,53,69,0.15); border: 1px solid rgba(220,53,69,0.35); color: #ff8190; }
        .alert-success { background: rgba(76,217,100,0.12); border: 1px solid rgba(76,217,100,0.3); color: #6ddf8a; }
        .alert i { margin-top: 1px; flex-shrink: 0; }

        /* Floating label inputs */
        .field {
            position: relative;
            margin-bottom: 20px;
        }
        .field input {
            width: 100%;
            padding: 18px 44px 6px 16px;
            background: rgba(255,255,255,0.06);
            border: 1.5px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            transition: border-color 0.25s, background 0.25s, box-shadow 0.25s;
            outline: none;
            -webkit-appearance: none;
        }
        .field input:focus {
            border-color: #4cd964;
            background: rgba(76,217,100,0.06);
            box-shadow: 0 0 0 3px rgba(76,217,100,0.12);
        }
        .field input:focus + label,
        .field input:not(:placeholder-shown) + label {
            transform: translateY(-10px) scale(0.78);
            color: #4cd964;
        }
        .field label {
            position: absolute;
            left: 16px;
            top: 14px;
            color: rgba(255,255,255,0.4);
            font-size: 14px;
            pointer-events: none;
            transition: transform 0.2s ease, color 0.2s ease;
            transform-origin: left top;
        }
        .field .icon-right {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.3);
            cursor: pointer;
            font-size: 14px;
            transition: color 0.2s;
            background: none;
            border: none;
            padding: 4px;
        }
        .field .icon-right:hover { color: #4cd964; }

        /* Checkbox */
        .check-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }
        .check-row input[type="checkbox"] { display: none; }
        .check-box {
            width: 18px; height: 18px;
            border: 1.5px solid rgba(255,255,255,0.25);
            border-radius: 5px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .check-row input:checked ~ .check-label .check-box,
        .check-box.checked {
            background: #4cd964;
            border-color: #4cd964;
        }
        .check-box i { color: #fff; font-size: 10px; display: none; }
        .check-box.checked i { display: block; }
        .check-label {
            display: flex; align-items: center; gap: 8px;
            color: rgba(255,255,255,0.55);
            font-size: 13px;
            cursor: pointer;
            user-select: none;
        }

        /* Botón principal */
        .btn-primary {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #4cd964, #34c759);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }
        .btn-primary::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(76,217,100,0.4); }
        .btn-primary:hover::after { opacity: 1; }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary.loading { opacity: 0.7; pointer-events: none; }

        /* Divisor */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0;
            color: rgba(255,255,255,0.25);
            font-size: 12px;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.1);
        }

        /* Botones sociales */
        .social-row { display: flex; gap: 10px; margin-bottom: 22px; }
        .btn-social {
            flex: 1;
            padding: 11px;
            border-radius: 10px;
            border: 1.5px solid rgba(255,255,255,0.12);
            background: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.7);
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: all 0.2s;
            font-weight: 500;
        }
        .btn-social:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.25); color: #fff; }
        .btn-social .fab { font-size: 15px; }
        .btn-social.google .fab  { color: #ea4335; }
        .btn-social.facebook .fab { color: #1877f2; }

        /* Footer del card */
        .card-footer {
            text-align: center;
            color: rgba(255,255,255,0.4);
            font-size: 13px;
            margin-top: 4px;
        }
        .card-footer a {
            color: #4cd964;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s;
        }
        .card-footer a:hover { opacity: 0.8; }

        .forgot {
            text-align: right;
            margin-top: -12px;
            margin-bottom: 20px;
        }
        .forgot a {
            color: rgba(255,255,255,0.4);
            font-size: 12px;
            text-decoration: none;
            transition: color 0.2s;
        }
        .forgot a:hover { color: #4cd964; }

        .back-link {
            margin-top: 20px;
            text-align: center;
        }
        .back-link a {
            color: rgba(255,255,255,0.35);
            font-size: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        .back-link a:hover { color: rgba(255,255,255,0.7); }

        /* Responsive */
        @media (max-width: 480px) {
            .card { padding: 28px 20px; border-radius: 16px; }
            h2 { font-size: 20px; }
            .social-row { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="bg"></div>
<div class="bg-overlay"></div>

<!-- Partículas -->
<div class="particles" id="particles"></div>

<div class="page">
    <div class="logo-wrap">
        <img src="config/dist/img/loguito_lacanchita.webp" alt="La Canchita">
        <p>Sistema de gestión deportiva</p>
    </div>

    <div class="card">
        <h2>Bienvenido de vuelta</h2>
        <p class="subtitle">Ingresá con tu cuenta para continuar</p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($ok): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($ok) ?></span>
            </div>
        <?php endif; ?>

        <form action="procesar_login.php" method="post" id="loginForm">

            <div class="field">
                <input type="text" id="username" name="username" placeholder=" " required autocomplete="username">
                <label for="username">Usuario o Email</label>
                <i class="fas fa-user icon-right"></i>
            </div>

            <div class="field">
                <input type="password" id="password" name="password" placeholder=" " required autocomplete="current-password">
                <label for="password">Contraseña</label>
                <button type="button" class="icon-right" id="togglePass" aria-label="Mostrar contraseña">
                    <i class="fas fa-eye" id="eyeIcon"></i>
                </button>
            </div>

            <div class="forgot">
                <a href="recuperar_contrasena.php">¿Olvidaste tu contraseña?</a>
            </div>

            <label class="check-label" for="rememberme">
                <div class="check-box" id="checkBox"><i class="fas fa-check"></i></div>
                Recordarme
            </label>
            <input type="checkbox" id="rememberme" name="rememberme">

            <br><br>

            <button type="submit" class="btn-primary" id="submitBtn">
                <span id="btnText">Ingresar</span>
            </button>
        </form>

        <div class="divider">o ingresá con</div>

        <div class="social-row">
            <button class="btn-social google" type="button">
                <i class="fab fa-google"></i> Google
            </button>
            <button class="btn-social facebook" type="button">
                <i class="fab fa-facebook-f"></i> Facebook
            </button>
        </div>

        <div class="card-footer">
            ¿No tenés cuenta? <a href="register.php">Registrate</a>
        </div>
    </div>

    <div class="back-link">
        <a href="index.php"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
    </div>
</div>

<script>
    // Partículas
    const container = document.getElementById('particles');
    for (let i = 0; i < 18; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.left = Math.random() * 100 + '%';
        p.style.width = p.style.height = (Math.random() * 3 + 1) + 'px';
        p.style.animationDuration = (Math.random() * 15 + 10) + 's';
        p.style.animationDelay = (Math.random() * 10) + 's';
        container.appendChild(p);
    }

    // Toggle password
    const togglePass = document.getElementById('togglePass');
    const passInput  = document.getElementById('password');
    const eyeIcon    = document.getElementById('eyeIcon');
    togglePass.addEventListener('click', () => {
        const visible = passInput.type === 'text';
        passInput.type = visible ? 'password' : 'text';
        eyeIcon.className = visible ? 'fas fa-eye' : 'fas fa-eye-slash';
    });

    // Checkbox custom
    const checkBox = document.getElementById('checkBox');
    const checkbox = document.getElementById('rememberme');
    checkBox.addEventListener('click', () => {
        checkbox.checked = !checkbox.checked;
        checkBox.classList.toggle('checked', checkbox.checked);
    });

    // Loading state en submit
    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        const txt = document.getElementById('btnText');
        btn.classList.add('loading');
        txt.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ingresando...';
    });
</script>

</body>
</html>

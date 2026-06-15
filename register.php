<?php
session_start();
$error = $_SESSION['registro_error'] ?? null;
$datos = $_SESSION['registro_data']  ?? [];
unset($_SESSION['registro_error'], $_SESSION['registro_data']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Registrarse - La Canchita</title>
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
        }

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
            background: linear-gradient(135deg, rgba(0,0,0,0.85) 0%, rgba(0,40,10,0.78) 100%);
        }

        .particles { position: fixed; inset: 0; pointer-events: none; overflow: hidden; }
        .particle {
            position: absolute;
            width: 3px; height: 3px;
            background: rgba(76,217,100,0.6);
            border-radius: 50%;
            animation: float linear infinite;
        }
        @keyframes float {
            0%   { transform: translateY(100vh); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            100% { transform: translateY(-10vh); opacity: 0; }
        }

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

        .logo-wrap {
            margin-bottom: 24px;
            text-align: center;
            animation: slideDown 0.6s ease both;
        }
        .logo-wrap img { width: 90px; filter: drop-shadow(0 4px 20px rgba(76,217,100,0.4)); }
        .logo-wrap p { color: rgba(255,255,255,0.5); font-size: 11px; letter-spacing: 2px; text-transform: uppercase; margin-top: 6px; }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .card {
            width: 100%;
            max-width: 500px;
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

        h2 { color: #fff; font-size: 22px; font-weight: 700; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: rgba(255,255,255,0.4); font-size: 13px; margin-bottom: 24px; }

        /* Pasos */
        .steps {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 28px;
        }
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            flex: 1;
            max-width: 100px;
        }
        .step-circle {
            width: 32px; height: 32px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700;
            color: rgba(255,255,255,0.35);
            transition: all 0.3s;
        }
        .step.active .step-circle { border-color: #4cd964; color: #4cd964; background: rgba(76,217,100,0.1); }
        .step.done .step-circle   { border-color: #4cd964; background: #4cd964; color: #fff; }
        .step-label { font-size: 10px; color: rgba(255,255,255,0.3); text-align: center; }
        .step.active .step-label, .step.done .step-label { color: rgba(255,255,255,0.6); }
        .step-line { flex: 1; height: 1px; background: rgba(255,255,255,0.1); align-self: center; margin-bottom: 18px; }
        .step-line.done { background: #4cd964; }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alert-danger { background: rgba(220,53,69,0.15); border: 1px solid rgba(220,53,69,0.35); color: #ff8190; }
        .alert i { margin-top: 1px; flex-shrink: 0; }

        /* Paneles de pasos */
        .step-panel { display: none; }
        .step-panel.active { display: block; animation: fadeStep 0.3s ease; }
        @keyframes fadeStep { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }

        /* Grid 2 columnas */
        .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        .field {
            position: relative;
            margin-bottom: 16px;
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
        .field input.error { border-color: #ff4455; box-shadow: 0 0 0 3px rgba(255,68,85,0.12); }
        .field input:focus + label,
        .field input:not(:placeholder-shown) + label {
            transform: translateY(-10px) scale(0.78);
            color: #4cd964;
        }
        .field input.error:focus + label,
        .field input.error:not(:placeholder-shown) + label { color: #ff4455; }
        .field label {
            position: absolute;
            left: 16px;
            top: 14px;
            color: rgba(255,255,255,0.4);
            font-size: 14px;
            pointer-events: none;
            transition: transform 0.2s, color 0.2s;
            transform-origin: left top;
        }
        .field .icon-right {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.3);
            font-size: 14px;
            cursor: pointer;
            background: none;
            border: none;
            padding: 4px;
            transition: color 0.2s;
        }
        .field .icon-right:hover { color: #4cd964; }
        .field-hint { font-size: 11px; color: rgba(255,255,255,0.3); margin-top: -10px; margin-bottom: 10px; padding-left: 4px; }

        /* Indicador de fuerza de contraseña */
        .pass-strength { margin-top: -10px; margin-bottom: 14px; }
        .strength-bar { height: 3px; border-radius: 3px; background: rgba(255,255,255,0.1); margin-bottom: 4px; overflow: hidden; }
        .strength-fill { height: 100%; width: 0; border-radius: 3px; transition: width 0.3s, background 0.3s; }
        .strength-text { font-size: 11px; color: rgba(255,255,255,0.35); }

        /* Botones navegación */
        .btn-row { display: flex; gap: 10px; margin-top: 8px; }
        .btn-primary {
            flex: 1;
            padding: 14px;
            background: linear-gradient(135deg, #4cd964, #34c759);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
            position: relative; overflow: hidden;
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(76,217,100,0.4); }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary.loading { opacity: 0.7; pointer-events: none; }
        .btn-back {
            padding: 14px 20px;
            background: rgba(255,255,255,0.07);
            border: 1.5px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            color: rgba(255,255,255,0.6);
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-back:hover { background: rgba(255,255,255,0.12); color: #fff; }

        .card-footer { text-align: center; color: rgba(255,255,255,0.4); font-size: 13px; margin-top: 20px; }
        .card-footer a { color: #4cd964; text-decoration: none; font-weight: 600; }
        .card-footer a:hover { opacity: 0.8; }

        .back-link { margin-top: 20px; text-align: center; }
        .back-link a { color: rgba(255,255,255,0.35); font-size: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: color 0.2s; }
        .back-link a:hover { color: rgba(255,255,255,0.7); }

        @media (max-width: 480px) {
            .card { padding: 24px 18px; border-radius: 16px; }
            .row-2 { grid-template-columns: 1fr; gap: 0; }
            h2 { font-size: 19px; }
        }
    </style>
</head>
<body>

<div class="bg"></div>
<div class="bg-overlay"></div>
<div class="particles" id="particles"></div>

<div class="page">
    <div class="logo-wrap">
        <img src="config/dist/img/loguito_lacanchita.webp" alt="La Canchita">
        <p>Sistema de gestión deportiva</p>
    </div>

    <div class="card">
        <h2>Crear cuenta</h2>
        <p class="subtitle">Completá tus datos para registrarte</p>

        <!-- Indicador de pasos -->
        <div class="steps">
            <div class="step active" id="s1">
                <div class="step-circle" id="sc1">1</div>
                <div class="step-label">Personal</div>
            </div>
            <div class="step-line" id="line1"></div>
            <div class="step" id="s2">
                <div class="step-circle" id="sc2">2</div>
                <div class="step-label">Contacto</div>
            </div>
            <div class="step-line" id="line2"></div>
            <div class="step" id="s3">
                <div class="step-circle" id="sc3">3</div>
                <div class="step-label">Acceso</div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= $error ?></div>
            </div>
        <?php endif; ?>

        <form action="procesar_registro.php" method="post" id="regForm">

            <!-- Paso 1: Datos personales -->
            <div class="step-panel active" id="panel1">
                <div class="row-2">
                    <div class="field">
                        <input type="text" id="nombre" name="nombre" placeholder=" " required
                               value="<?= htmlspecialchars($datos['nombre'] ?? '') ?>">
                        <label for="nombre">Nombre</label>
                        <i class="fas fa-user icon-right"></i>
                    </div>
                    <div class="field">
                        <input type="text" id="apellido" name="apellido" placeholder=" " required
                               value="<?= htmlspecialchars($datos['apellido'] ?? '') ?>">
                        <label for="apellido">Apellido</label>
                        <i class="fas fa-user icon-right"></i>
                    </div>
                </div>
                <div class="field">
                    <input type="text" id="dni" name="dni" placeholder=" " required
                           inputmode="numeric" maxlength="8"
                           value="<?= htmlspecialchars($datos['dni'] ?? '') ?>">
                    <label for="dni">DNI</label>
                    <i class="fas fa-id-card icon-right"></i>
                </div>
                <p class="field-hint">Solo números, sin puntos.</p>
                <div class="btn-row">
                    <button type="button" class="btn-primary" onclick="goStep(2)">
                        Continuar <i class="fas fa-arrow-right" style="margin-left:6px"></i>
                    </button>
                </div>
            </div>

            <!-- Paso 2: Contacto -->
            <div class="step-panel" id="panel2">
                <div class="field">
                    <input type="email" id="email" name="email" placeholder=" " required
                           value="<?= htmlspecialchars($datos['email'] ?? '') ?>">
                    <label for="email">Email</label>
                    <i class="fas fa-envelope icon-right"></i>
                </div>
                <div class="field">
                    <input type="tel" id="telefono" name="telefono" placeholder=" " required
                           inputmode="numeric"
                           value="<?= htmlspecialchars($datos['telefono'] ?? '') ?>">
                    <label for="telefono">Teléfono</label>
                    <i class="fas fa-phone icon-right"></i>
                </div>
                <p class="field-hint">Con código de área, sin 0 ni 15.</p>
                <div class="btn-row">
                    <button type="button" class="btn-back" onclick="goStep(1)">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <button type="button" class="btn-primary" onclick="goStep(3)">
                        Continuar <i class="fas fa-arrow-right" style="margin-left:6px"></i>
                    </button>
                </div>
            </div>

            <!-- Paso 3: Contraseña -->
            <div class="step-panel" id="panel3">
                <div class="field">
                    <input type="password" id="password" name="password" placeholder=" " required autocomplete="new-password">
                    <label for="password">Contraseña</label>
                    <button type="button" class="icon-right" id="togglePass1" aria-label="Mostrar">
                        <i class="fas fa-eye" id="eye1"></i>
                    </button>
                </div>
                <div class="pass-strength">
                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                    <span class="strength-text" id="strengthText">Mínimo 6 caracteres</span>
                </div>
                <div class="field">
                    <input type="password" id="password2" name="password2" placeholder=" " required autocomplete="new-password">
                    <label for="password2">Repetir contraseña</label>
                    <button type="button" class="icon-right" id="togglePass2" aria-label="Mostrar">
                        <i class="fas fa-eye" id="eye2"></i>
                    </button>
                </div>
                <div class="btn-row">
                    <button type="button" class="btn-back" onclick="goStep(2)">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <button type="submit" class="btn-primary" id="submitBtn">
                        <span id="btnText"><i class="fas fa-user-plus" style="margin-right:6px"></i>Registrarme</span>
                    </button>
                </div>
            </div>

        </form>

        <div class="card-footer">
            ¿Ya tenés cuenta? <a href="login.php">Iniciá sesión</a>
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

    // Navegación de pasos
    let currentStep = 1;

    function goStep(n) {
        if (n > currentStep && !validateStep(currentStep)) return;

        document.getElementById('panel' + currentStep).classList.remove('active');
        document.getElementById('s' + currentStep).classList.remove('active');
        if (n > currentStep) document.getElementById('s' + currentStep).classList.add('done');

        currentStep = n;
        document.getElementById('panel' + n).classList.add('active');
        document.getElementById('s' + n).classList.add('active');
        document.getElementById('s' + n).classList.remove('done');

        // Líneas
        if (currentStep >= 2) document.getElementById('line1').classList.add('done');
        else document.getElementById('line1').classList.remove('done');
        if (currentStep >= 3) document.getElementById('line2').classList.add('done');
        else document.getElementById('line2').classList.remove('done');

        // Pasos anteriores como done
        for (let i = 1; i < n; i++) {
            document.getElementById('s' + i).classList.add('done');
            document.getElementById('s' + i).classList.remove('active');
            document.getElementById('sc' + i).innerHTML = '<i class="fas fa-check"></i>';
        }
        document.getElementById('sc' + n).textContent = n;
    }

    function validateStep(step) {
        if (step === 1) {
            const nombre   = document.getElementById('nombre').value.trim();
            const apellido = document.getElementById('apellido').value.trim();
            const dni      = document.getElementById('dni').value.trim();
            if (!nombre)   { shake('nombre');   return false; }
            if (!apellido) { shake('apellido'); return false; }
            if (!dni || !/^\d{7,8}$/.test(dni)) { shake('dni'); return false; }
        }
        if (step === 2) {
            const email    = document.getElementById('email').value.trim();
            const telefono = document.getElementById('telefono').value.trim();
            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { shake('email');    return false; }
            if (!telefono) { shake('telefono'); return false; }
        }
        return true;
    }

    function shake(id) {
        const el = document.getElementById(id);
        el.classList.add('error');
        el.animate([
            { transform: 'translateX(0)' },
            { transform: 'translateX(-6px)' },
            { transform: 'translateX(6px)' },
            { transform: 'translateX(-4px)' },
            { transform: 'translateX(0)' }
        ], { duration: 300 });
        el.addEventListener('input', () => el.classList.remove('error'), { once: true });
    }

    // Toggle passwords
    function togglePassword(inputId, btnId, iconId) {
        document.getElementById(btnId).addEventListener('click', () => {
            const inp = document.getElementById(inputId);
            const ico = document.getElementById(iconId);
            const visible = inp.type === 'text';
            inp.type = visible ? 'password' : 'text';
            ico.className = visible ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    }
    togglePassword('password',  'togglePass1', 'eye1');
    togglePassword('password2', 'togglePass2', 'eye2');

    // Indicador de fuerza
    document.getElementById('password').addEventListener('input', function() {
        const val = this.value;
        const fill = document.getElementById('strengthFill');
        const text = document.getElementById('strengthText');
        let strength = 0;
        if (val.length >= 6)  strength++;
        if (val.length >= 10) strength++;
        if (/[A-Z]/.test(val)) strength++;
        if (/[0-9]/.test(val)) strength++;
        if (/[^A-Za-z0-9]/.test(val)) strength++;

        const pct   = ['0%','25%','50%','75%','100%'][strength] || '0%';
        const color = ['#ff4455','#ff9500','#ffcc00','#4cd964','#34c759'][strength - 1] || '#ff4455';
        const label = ['','Muy débil','Débil','Buena','Fuerte','Muy fuerte'][strength] || '';

        fill.style.width = pct;
        fill.style.background = color;
        text.textContent = val.length === 0 ? 'Mínimo 6 caracteres' : label;
        text.style.color = val.length === 0 ? 'rgba(255,255,255,0.35)' : color;
    });

    // Loading en submit
    document.getElementById('regForm').addEventListener('submit', function(e) {
        const pass  = document.getElementById('password').value;
        const pass2 = document.getElementById('password2').value;
        if (pass !== pass2) {
            e.preventDefault();
            shake('password2');
            return;
        }
        const btn = document.getElementById('submitBtn');
        btn.classList.add('loading');
        document.getElementById('btnText').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
    });

    // Si hay error del servidor, ir al paso correcto
    <?php if ($error): ?>
    const errMsg = <?= json_encode($error) ?>;
    if (errMsg.includes('EMAIL') || errMsg.includes('email')) {
        goStep(2);
    } else if (errMsg.includes('DNI') || errMsg.includes('dni')) {
        goStep(1);
    } else if (errMsg.includes('contraseña') || errMsg.includes('password')) {
        goStep(3);
    }
    <?php endif; ?>
</script>

</body>
</html>

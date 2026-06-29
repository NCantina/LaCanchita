<?php
session_start();
require_once 'config/dist/script/php/conn.php';
require_once 'config/dist/script/php/mailer.php';

// Respaldo en dev: asegurar la tabla aunque no se haya corrido la migración
mysqli_query($link,
    "CREATE TABLE IF NOT EXISTS password_reset (
        RESET_ID    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        USUARIOS_ID INT UNSIGNED NOT NULL,
        TOKEN_HASH  CHAR(64) NOT NULL,
        EXPIRA      DATETIME NOT NULL,
        USADO       TINYINT(1) NOT NULL DEFAULT 0,
        CREATED_AT  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_token (TOKEN_HASH),
        INDEX idx_usuario (USUARIOS_ID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$enviado = false;
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Ingresá un email válido.';
    } else {
        // Buscar usuario activo con ese email
        $stmt = mysqli_prepare($link,
            "SELECT USUARIOS_ID, USUARIOS_NOMBRE, USUARIOS_EMAIL
             FROM usuarios WHERE USUARIOS_EMAIL = ? AND ACTIVO = 1 LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($user) {
            $uid = (int)$user['USUARIOS_ID'];
            // Invalidar tokens previos del usuario
            mysqli_query($link, "DELETE FROM password_reset WHERE USUARIOS_ID = $uid");

            $token = bin2hex(random_bytes(32));
            $hash  = hash('sha256', $token);
            $stmt2 = mysqli_prepare($link,
                "INSERT INTO password_reset (USUARIOS_ID, TOKEN_HASH, EXPIRA)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
            mysqli_stmt_bind_param($stmt2, 'is', $uid, $hash);
            mysqli_stmt_execute($stmt2);

            // Link absoluto (tolerante a subdirectorio)
            $scheme = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
            $url    = "$scheme://$host$dir/restablecer_contrasena.php?token=$token";

            enviarEmailReset($user['USUARIOS_EMAIL'], $user['USUARIOS_NOMBRE'], $url);
        }
        // Respuesta genérica: no revelar si el email existe
        $enviado = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar contraseña - La Canchita</title>
<link rel="shortcut icon" href="config/dist/img/loguito_lacanchita.WEBP" type="image/webp">
<link rel="stylesheet" href="config/pluggins/vendor/fontawesome-free/css/all.min.css">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{--green:#4cd964;--bg:#0a0a0a;--card:rgba(20,20,26,.92);--bdr:rgba(255,255,255,.1);--txt:#f0f0f5;--muted:rgba(255,255,255,.5)}
  body{min-height:100vh;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;color:var(--txt);
    display:flex;align-items:center;justify-content:center;padding:20px;
    background:linear-gradient(135deg,rgba(0,0,0,.86),rgba(0,40,10,.78)),url('config/dist/img/ESTADIO.webp') center/cover no-repeat fixed}
  .card{width:100%;max-width:420px;background:var(--card);border:1px solid var(--bdr);border-radius:20px;
    padding:36px 30px;backdrop-filter:blur(12px);box-shadow:0 20px 60px rgba(0,0,0,.5);animation:up .4s ease}
  @keyframes up{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
  .brand{text-align:center;font-size:22px;font-weight:800;color:var(--green);letter-spacing:-.4px;margin-bottom:6px}
  .icon{text-align:center;font-size:34px;margin-bottom:10px}
  h1{font-size:19px;font-weight:800;text-align:center;margin-bottom:6px}
  p.sub{font-size:13px;color:var(--muted);text-align:center;line-height:1.5;margin-bottom:22px}
  label{display:block;font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px}
  input{width:100%;padding:13px 15px;background:rgba(255,255,255,.05);border:1px solid var(--bdr);border-radius:11px;
    color:var(--txt);font-size:14px;outline:none;transition:border-color .2s}
  input:focus{border-color:var(--green)}
  .btn{width:100%;margin-top:18px;padding:14px;border:0;border-radius:12px;background:var(--green);color:#0a0a0a;
    font-size:15px;font-weight:800;cursor:pointer;transition:opacity .15s}
  .btn:hover{opacity:.9}
  .back{display:block;text-align:center;margin-top:18px;font-size:13px;color:var(--muted);text-decoration:none}
  .back:hover{color:var(--green)}
  .alert{padding:12px 14px;border-radius:11px;font-size:13px;margin-bottom:18px;line-height:1.5}
  .alert.err{background:rgba(231,76,60,.12);border:1px solid rgba(231,76,60,.3);color:#ff6b5e}
  .alert.ok{background:rgba(76,217,100,.1);border:1px solid rgba(76,217,100,.3);color:var(--green)}
</style>
</head>
<body>
  <div class="card">
    <div class="brand">La Canchita</div>
    <?php if ($enviado): ?>
      <div class="icon">📬</div>
      <h1>Revisá tu correo</h1>
      <p class="sub">Si el email pertenece a una cuenta, te enviamos un link para crear una nueva contraseña. Vence en 1 hora.</p>
      <a class="back" href="login.php"><i class="fas fa-arrow-left"></i> Volver al inicio de sesión</a>
    <?php else: ?>
      <div class="icon">🔑</div>
      <h1>¿Olvidaste tu contraseña?</h1>
      <p class="sub">Ingresá el email de tu cuenta y te mandamos un link para restablecerla.</p>
      <?php if ($errorMsg): ?><div class="alert err"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>
      <form method="post" autocomplete="off">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" placeholder="tu@email.com" required autofocus
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <button class="btn" type="submit">Enviar link de recuperación</button>
      </form>
      <a class="back" href="login.php"><i class="fas fa-arrow-left"></i> Volver al inicio de sesión</a>
    <?php endif; ?>
  </div>
</body>
</html>

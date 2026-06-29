<?php
session_start();
require_once 'config/dist/script/php/conn.php';

function tokenValido($link, string $token) {
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
    $hash = hash('sha256', $token);
    $stmt = mysqli_prepare($link,
        "SELECT pr.RESET_ID, pr.USUARIOS_ID
         FROM password_reset pr
         WHERE pr.TOKEN_HASH = ? AND pr.USADO = 0 AND pr.EXPIRA > NOW()
         LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $hash);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));  // null si inválido
}

$token    = $_POST['token'] ?? $_GET['token'] ?? '';
$row      = tokenValido($link, $token);   // null si el token es inválido o expiró
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass  = $_POST['password']  ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (!$row) {
        $errorMsg = 'El link de recuperación es inválido o expiró. Pedí uno nuevo.';
    } elseif (strlen($pass) < 6) {
        $errorMsg = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($pass !== $pass2) {
        $errorMsg = 'Las contraseñas no coinciden.';
    } else {
        $uid  = (int)$row['USUARIOS_ID'];
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($link, "UPDATE usuarios SET USUARIOS_PASSWORD = ? WHERE USUARIOS_ID = ?");
        mysqli_stmt_bind_param($stmt, 'si', $hash, $uid);
        mysqli_stmt_execute($stmt);
        // Invalidar todos los tokens del usuario
        mysqli_query($link, "UPDATE password_reset SET USADO = 1 WHERE USUARIOS_ID = $uid");
        $_SESSION['registro_ok'] = 'Tu contraseña fue actualizada. Ya podés iniciar sesión.';
        header('Location: login.php');
        exit;
    }
}

// El token es válido salvo que sea POST con token inválido/expirado
$tokenOk = (bool) $row;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nueva contraseña - La Canchita</title>
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
  label{display:block;font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;margin-top:14px}
  input{width:100%;padding:13px 15px;background:rgba(255,255,255,.05);border:1px solid var(--bdr);border-radius:11px;
    color:var(--txt);font-size:14px;outline:none;transition:border-color .2s}
  input:focus{border-color:var(--green)}
  .btn{width:100%;margin-top:20px;padding:14px;border:0;border-radius:12px;background:var(--green);color:#0a0a0a;
    font-size:15px;font-weight:800;cursor:pointer;transition:opacity .15s}
  .btn:hover{opacity:.9}
  .back{display:block;text-align:center;margin-top:18px;font-size:13px;color:var(--muted);text-decoration:none}
  .back:hover{color:var(--green)}
  .alert{padding:12px 14px;border-radius:11px;font-size:13px;margin-bottom:8px;line-height:1.5}
  .alert.err{background:rgba(231,76,60,.12);border:1px solid rgba(231,76,60,.3);color:#ff6b5e}
</style>
</head>
<body>
  <div class="card">
    <div class="brand">La Canchita</div>
    <?php if (!$tokenOk): ?>
      <div class="icon">⚠️</div>
      <h1>Link inválido o vencido</h1>
      <p class="sub"><?= htmlspecialchars($errorMsg ?: 'Este link de recuperación no es válido o ya expiró.') ?></p>
      <a class="back" href="recuperar_contrasena.php"><i class="fas fa-redo"></i> Pedir un link nuevo</a>
    <?php else: ?>
      <div class="icon">🔒</div>
      <h1>Creá tu nueva contraseña</h1>
      <p class="sub">Elegí una contraseña de al menos 6 caracteres.</p>
      <?php if ($errorMsg): ?><div class="alert err"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>
      <form method="post" autocomplete="off">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <label for="password">Nueva contraseña</label>
        <input id="password" name="password" type="password" placeholder="••••••••" required autofocus minlength="6">
        <label for="password2">Repetir contraseña</label>
        <input id="password2" name="password2" type="password" placeholder="••••••••" required minlength="6">
        <button class="btn" type="submit">Guardar contraseña</button>
      </form>
      <a class="back" href="login.php"><i class="fas fa-arrow-left"></i> Volver al inicio de sesión</a>
    <?php endif; ?>
  </div>
</body>
</html>

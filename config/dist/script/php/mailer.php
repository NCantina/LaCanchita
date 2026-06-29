<?php
require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Envía un email de notificación de reserva.
 *
 * @param string $tipo    'pendiente' | 'confirmada' | 'cancelada'
 * @param array  $datos   Datos de la reserva y del usuario
 */
function enviarEmailReserva(string $tipo, array $datos): bool {
    if (!defined('MAIL_ENABLED') || !MAIL_ENABLED) return false;

    $nombre   = trim(($datos['nombre'] ?? '') . ' ' . ($datos['apellido'] ?? ''));
    $email    = $datos['email'] ?? '';
    $cancha   = $datos['cancha']   ?? '';
    $complejo = $datos['complejo'] ?? '';
    $fecha    = $datos['fecha']    ?? '';
    $horaIni  = $datos['hora_ini'] ?? '';
    $horaFin  = $datos['hora_fin'] ?? '';
    $precio   = $datos['precio']   ?? 0;
    $reservaId = $datos['reserva_id'] ?? '';

    if (!$email) return false;

    // Formatear fecha
    $fechaFmt = '';
    if ($fecha) {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        if ($d) {
            $dias   = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
            $meses  = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
            $fechaFmt = $dias[(int)$d->format('w')] . ', ' . $d->format('j') . ' de ' . $meses[(int)$d->format('n')-1] . ' de ' . $d->format('Y');
        }
    }

    $precio_fmt = '$' . number_format((float)$precio, 0, ',', '.');

    // Asunto y color/estado según tipo
    $cfg = [
        'pendiente'  => ['asunto' => '✅ Reserva recibida — La Canchita', 'color' => '#ff9500', 'icono' => '⏳', 'titulo' => '¡Reserva enviada!',    'subtitulo' => 'El predio la revisará y confirmará pronto.'],
        'confirmada' => ['asunto' => '🎉 Reserva confirmada — La Canchita', 'color' => '#4cd964', 'icono' => '✅', 'titulo' => '¡Reserva confirmada!','subtitulo' => 'Ya podés ir preparando los botines.'],
        'cancelada'  => ['asunto' => 'Reserva cancelada — La Canchita',   'color' => '#e74c3c', 'icono' => '❌', 'titulo' => 'Reserva cancelada',    'subtitulo' => 'Tu reserva fue cancelada.'],
    ];
    $c = $cfg[$tipo] ?? $cfg['pendiente'];

    $html = "<!DOCTYPE html>
<html lang='es'>
<head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
<title>{$c['asunto']}</title></head>
<body style='margin:0;padding:0;background:#f4f4f4;font-family:Segoe UI,Arial,sans-serif'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f4f4;padding:32px 0'>
  <tr><td align='center'>
    <table width='560' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:16px;overflow:hidden;max-width:95%'>
      <!-- Header -->
      <tr><td style='background:#0d0d0d;padding:28px 36px;text-align:center'>
        <div style='font-size:28px;margin-bottom:6px'>{$c['icono']}</div>
        <div style='color:#4cd964;font-size:22px;font-weight:800;letter-spacing:-0.5px'>La Canchita</div>
        <div style='color:rgba(255,255,255,.5);font-size:13px;margin-top:2px'>Sistema de reservas deportivas</div>
      </td></tr>
      <!-- Status banner -->
      <tr><td style='background:{$c['color']}18;border-bottom:3px solid {$c['color']};padding:20px 36px;text-align:center'>
        <div style='font-size:20px;font-weight:800;color:#1a1a1a'>{$c['titulo']}</div>
        <div style='font-size:14px;color:#555;margin-top:4px'>{$c['subtitulo']}</div>
      </td></tr>
      <!-- Saludo -->
      <tr><td style='padding:28px 36px 0'>
        <p style='margin:0;font-size:15px;color:#333'>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
      </td></tr>
      <!-- Datos reserva -->
      <tr><td style='padding:20px 36px'>
        <table width='100%' cellpadding='0' cellspacing='0' style='background:#f8f9fa;border-radius:12px;overflow:hidden'>
          <tr><td colspan='2' style='background:#1a1a1a;padding:12px 18px;font-size:12px;font-weight:700;color:#4cd964;text-transform:uppercase;letter-spacing:.06em'>Detalle de la reserva</td></tr>
          <tr style='border-bottom:1px solid #eee'>
            <td style='padding:12px 18px;font-size:13px;color:#888;width:40%'>🏟️ Complejo</td>
            <td style='padding:12px 18px;font-size:13px;font-weight:600;color:#333'>" . htmlspecialchars($complejo) . "</td>
          </tr>
          <tr style='border-bottom:1px solid #eee'>
            <td style='padding:12px 18px;font-size:13px;color:#888'>🎯 Cancha</td>
            <td style='padding:12px 18px;font-size:13px;font-weight:600;color:#333'>" . htmlspecialchars($cancha) . "</td>
          </tr>
          <tr style='border-bottom:1px solid #eee'>
            <td style='padding:12px 18px;font-size:13px;color:#888'>📅 Fecha</td>
            <td style='padding:12px 18px;font-size:13px;font-weight:600;color:#333'>" . htmlspecialchars(ucfirst($fechaFmt)) . "</td>
          </tr>
          <tr style='border-bottom:1px solid #eee'>
            <td style='padding:12px 18px;font-size:13px;color:#888'>🕐 Horario</td>
            <td style='padding:12px 18px;font-size:13px;font-weight:600;color:#333'>" . htmlspecialchars(substr($horaIni,0,5)) . " – " . htmlspecialchars(substr($horaFin,0,5)) . " hs</td>
          </tr>
          <tr>
            <td style='padding:12px 18px;font-size:13px;color:#888'>💰 Precio</td>
            <td style='padding:12px 18px;font-size:14px;font-weight:800;color:{$c['color']}'>{$precio_fmt}</td>
          </tr>
        </table>
        " . ($reservaId ? "<p style='margin:12px 0 0;font-size:12px;color:#aaa;text-align:right'>Reserva #" . htmlspecialchars((string)$reservaId) . "</p>" : '') . "
      </td></tr>
      <!-- Footer -->
      <tr><td style='padding:20px 36px 28px;text-align:center;border-top:1px solid #eee'>
        <p style='margin:0;font-size:12px;color:#aaa'>La Canchita · Sistema de reservas deportivas<br>Este email fue generado automáticamente.</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($email, $nombre);
        $mail->isHTML(true);
        $mail->Subject = $c['asunto'];
        $mail->Body    = $html;
        $mail->AltBody = "{$c['titulo']} | {$complejo} | {$cancha} | {$fechaFmt} | {$horaIni}–{$horaFin}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Envía un recordatorio de cobro pendiente al cliente (dueño de predio).
 */
function enviarRecordatorioCobro(array $datos): bool {
    if (!defined('MAIL_ENABLED') || !MAIL_ENABLED) return false;

    $nombre  = trim(($datos['nombre'] ?? '') . ' ' . ($datos['apellido'] ?? ''));
    $email   = $datos['email']        ?? '';
    $plan    = $datos['plan_nombre']  ?? 'Estándar';
    $precio  = (float)($datos['plan_precio'] ?? 0);
    $prox    = $datos['proximo_cobro'] ?? '';

    if (!$email) return false;

    $precio_fmt = '$' . number_format($precio, 0, ',', '.');

    $prox_fmt = '';
    if ($prox) {
        $d = DateTime::createFromFormat('Y-m-d', $prox);
        if ($d) {
            $dias  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
            $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
            $prox_fmt = $dias[(int)$d->format('w')] . ', ' . $d->format('j') . ' de ' . $meses[(int)$d->format('n')-1] . ' de ' . $d->format('Y');
        }
    }

    $html = "<!DOCTYPE html>
<html lang='es'>
<head><meta charset='utf-8'><title>Recordatorio de cobro</title></head>
<body style='margin:0;padding:0;background:#f4f4f4;font-family:Segoe UI,Arial,sans-serif'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f4f4;padding:32px 0'>
  <tr><td align='center'>
    <table width='560' cellpadding='0' cellspacing='0' style='background:#fff;border-radius:16px;overflow:hidden;max-width:95%'>
      <tr><td style='background:#0d0d0d;padding:28px 36px;text-align:center'>
        <div style='color:#4cd964;font-size:22px;font-weight:800'>La Canchita</div>
        <div style='color:rgba(255,255,255,.5);font-size:13px;margin-top:2px'>Sistema de reservas deportivas</div>
      </td></tr>
      <tr><td style='background:#ff950018;border-bottom:3px solid #ff9500;padding:20px 36px;text-align:center'>
        <div style='font-size:20px;font-weight:800;color:#1a1a1a'>⏰ Recordatorio de pago</div>
        <div style='font-size:14px;color:#555;margin-top:4px'>Tenés un pago pendiente de tu suscripción.</div>
      </td></tr>
      <tr><td style='padding:28px 36px 0'>
        <p style='margin:0;font-size:15px;color:#333'>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
        <p style='margin:12px 0 0;font-size:14px;color:#555'>Te recordamos que tenés un pago pendiente de tu suscripción a La Canchita.</p>
      </td></tr>
      <tr><td style='padding:20px 36px'>
        <table width='100%' cellpadding='0' cellspacing='0' style='background:#f8f9fa;border-radius:12px;overflow:hidden'>
          <tr><td colspan='2' style='background:#1a1a1a;padding:12px 18px;font-size:12px;font-weight:700;color:#4cd964;text-transform:uppercase;letter-spacing:.06em'>Detalle de tu suscripción</td></tr>
          <tr style='border-bottom:1px solid #eee'>
            <td style='padding:12px 18px;font-size:13px;color:#888;width:40%'>📦 Plan</td>
            <td style='padding:12px 18px;font-size:13px;font-weight:600;color:#333'>" . htmlspecialchars($plan) . "</td>
          </tr>
          <tr style='border-bottom:1px solid #eee'>
            <td style='padding:12px 18px;font-size:13px;color:#888'>💰 Monto</td>
            <td style='padding:12px 18px;font-size:14px;font-weight:800;color:#ff9500'>{$precio_fmt}</td>
          </tr>
          " . ($prox_fmt ? "<tr><td style='padding:12px 18px;font-size:13px;color:#888'>📅 Vence</td><td style='padding:12px 18px;font-size:13px;font-weight:600;color:#e74c3c'>" . htmlspecialchars(ucfirst($prox_fmt)) . "</td></tr>" : '') . "
        </table>
      </td></tr>
      <tr><td style='padding:0 36px 28px;text-align:center'>
        <p style='margin:0;font-size:13px;color:#555'>Para consultas o realizar el pago, contactate con el equipo de La Canchita.</p>
      </td></tr>
      <tr><td style='padding:20px 36px 28px;text-align:center;border-top:1px solid #eee'>
        <p style='margin:0;font-size:12px;color:#aaa'>La Canchita · Sistema de reservas deportivas<br>Este email fue generado automáticamente.</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($email, $nombre);
        $mail->isHTML(true);
        $mail->Subject = '⏰ Recordatorio de pago — La Canchita';
        $mail->Body    = $html;
        $mail->AltBody = "Recordatorio de pago | Plan: $plan | Monto: $precio_fmt" . ($prox_fmt ? " | Vence: $prox_fmt" : '');
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer recordatorio error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Envía un recordatorio al desarrollador (cantonnico2@gmail.com)
 * sobre un cobro próximo de un cliente.
 */
function enviarRecordatorioDesarrollador(array $datos): bool {
    if (!defined('MAIL_ENABLED') || !MAIL_ENABLED) return false;

    $nombre       = trim(($datos['USUARIOS_NOMBRE'] ?? '') . ' ' . ($datos['USUARIOS_APELLIDO'] ?? ''));
    $clienteEmail = $datos['USUARIOS_EMAIL'] ?? '';
    $plan         = $datos['PLAN_NOMBRE']    ?? 'Sin plan';
    $precio       = (float)($datos['PLAN_PRECIO'] ?? 0);
    $prox         = $datos['PROXIMO_COBRO']  ?? '';
    $desc         = $datos['DESCRIPCION']    ?? '';

    $precio_fmt = '$' . number_format($precio, 0, ',', '.');

    $prox_fmt = $prox ?: 'Sin definir';
    if ($prox) {
        $d = DateTime::createFromFormat('Y-m-d', $prox);
        if ($d) {
            $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
            $prox_fmt = $d->format('j') . ' de ' . $meses[(int)$d->format('n')-1] . ' de ' . $d->format('Y');
        }
    }

    $html = "<!DOCTYPE html>
<html lang='es'>
<head><meta charset='utf-8'><title>Recordatorio de cobro</title></head>
<body style='margin:0;padding:0;background:#0d0d15;font-family:Segoe UI,Arial,sans-serif'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#0d0d15;padding:32px 0'>
  <tr><td align='center'>
    <table width='520' cellpadding='0' cellspacing='0' style='background:#101018;border:1px solid #2a2a3a;border-radius:16px;overflow:hidden;max-width:95%'>
      <tr><td style='background:#101018;padding:24px 32px;border-bottom:1px solid #2a2a3a'>
        <div style='font-size:13px;font-weight:800;color:#4cd964;letter-spacing:.06em;text-transform:uppercase'>🔔 La Canchita · Dev Panel</div>
      </td></tr>
      <tr><td style='padding:28px 32px 0'>
        <div style='font-size:22px;font-weight:800;color:#fff;line-height:1.2'>Recordatorio de cobro</div>
        <div style='font-size:14px;color:#888;margin-top:6px'>" . htmlspecialchars($desc) . "</div>
      </td></tr>
      <tr><td style='padding:20px 32px'>
        <table width='100%' cellpadding='0' cellspacing='0' style='background:#16161f;border-radius:12px;overflow:hidden;border:1px solid #2a2a3a'>
          <tr><td colspan='2' style='padding:12px 18px;font-size:11px;font-weight:700;color:#4cd964;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #2a2a3a'>Cliente</td></tr>
          <tr style='border-bottom:1px solid #1d1d28'>
            <td style='padding:12px 18px;font-size:13px;color:#666;width:40%'>Nombre</td>
            <td style='padding:12px 18px;font-size:13px;font-weight:700;color:#fff'>" . htmlspecialchars($nombre) . "</td>
          </tr>
          <tr style='border-bottom:1px solid #1d1d28'>
            <td style='padding:12px 18px;font-size:13px;color:#666'>Email</td>
            <td style='padding:12px 18px;font-size:13px;color:#aaa'>" . htmlspecialchars($clienteEmail) . "</td>
          </tr>
          <tr style='border-bottom:1px solid #1d1d28'>
            <td style='padding:12px 18px;font-size:13px;color:#666'>Plan</td>
            <td style='padding:12px 18px;font-size:13px;font-weight:600;color:#fff'>" . htmlspecialchars($plan) . "</td>
          </tr>
          <tr style='border-bottom:1px solid #1d1d28'>
            <td style='padding:12px 18px;font-size:13px;color:#666'>Monto</td>
            <td style='padding:12px 18px;font-size:16px;font-weight:800;color:#4cd964'>{$precio_fmt}</td>
          </tr>
          <tr>
            <td style='padding:12px 18px;font-size:13px;color:#666'>Próximo cobro</td>
            <td style='padding:12px 18px;font-size:13px;font-weight:700;color:#ff9500'>" . htmlspecialchars(ucfirst($prox_fmt)) . "</td>
          </tr>
        </table>
      </td></tr>
      <tr><td style='padding:0 32px 28px;text-align:center'>
        <p style='margin:0;font-size:12px;color:#444'>Recordatorio automático · La Canchita Dev Panel</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress('cantonnico2@gmail.com', 'Nico - La Canchita');
        $mail->isHTML(true);
        $mail->Subject = "⏰ Cobrar a {$nombre} — {$prox_fmt}";
        $mail->Body    = $html;
        $mail->AltBody = "Recordatorio: cobrar a {$nombre} | {$plan} | {$precio_fmt} | Vence: {$prox_fmt}";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer recordatorio dev error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Envía el link de recuperación de contraseña.
 *
 * @param string $email  destinatario
 * @param string $nombre nombre del usuario
 * @param string $url    link absoluto con el token de reset
 */
function enviarEmailReset(string $email, string $nombre, string $url): bool {
    if (!defined('MAIL_ENABLED') || !MAIL_ENABLED) return false;
    if (!$email) return false;

    $urlSafe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $html = "<!DOCTYPE html>
<html lang='es'>
<head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
<title>Recuperá tu contraseña — La Canchita</title></head>
<body style='margin:0;padding:0;background:#f4f4f4;font-family:Segoe UI,Arial,sans-serif'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f4f4;padding:32px 0'>
  <tr><td align='center'>
    <table width='560' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:16px;overflow:hidden;max-width:95%'>
      <tr><td style='background:#0d0d0d;padding:28px 36px;text-align:center'>
        <div style='font-size:28px;margin-bottom:6px'>🔑</div>
        <div style='color:#4cd964;font-size:22px;font-weight:800;letter-spacing:-0.5px'>La Canchita</div>
      </td></tr>
      <tr><td style='background:#4cd96418;border-bottom:3px solid #4cd964;padding:20px 36px;text-align:center'>
        <div style='font-size:20px;font-weight:800;color:#1a1a1a'>Recuperá tu contraseña</div>
      </td></tr>
      <tr><td style='padding:28px 36px 8px'>
        <p style='margin:0;font-size:15px;color:#333'>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
        <p style='margin:12px 0 0;font-size:14px;color:#555'>Recibimos un pedido para restablecer tu contraseña. Hacé clic en el botón para crear una nueva. Este link vence en 1 hora.</p>
      </td></tr>
      <tr><td style='padding:20px 36px;text-align:center'>
        <a href='{$urlSafe}' style='display:inline-block;background:#4cd964;color:#0d0d0d;font-weight:800;font-size:15px;text-decoration:none;padding:14px 32px;border-radius:10px'>Crear nueva contraseña</a>
      </td></tr>
      <tr><td style='padding:0 36px 8px'>
        <p style='margin:0;font-size:12px;color:#888'>Si el botón no funciona, copiá y pegá este link en tu navegador:</p>
        <p style='margin:6px 0 0;font-size:12px;color:#4cd964;word-break:break-all'>{$urlSafe}</p>
      </td></tr>
      <tr><td style='padding:16px 36px 28px;text-align:center;border-top:1px solid #eee'>
        <p style='margin:0;font-size:12px;color:#aaa'>Si no pediste esto, ignorá este email: tu contraseña no cambia.<br>La Canchita · Sistema de reservas deportivas</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($email, $nombre);
        $mail->isHTML(true);
        $mail->Subject = '🔑 Recuperá tu contraseña — La Canchita';
        $mail->Body    = $html;
        $mail->AltBody = "Recuperá tu contraseña en La Canchita. Abrí este link (vence en 1 hora): $url";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer reset error: ' . $mail->ErrorInfo);
        return false;
    }
}

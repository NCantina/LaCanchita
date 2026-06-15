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
        // Silencioso: no romper el flujo de reserva por un error de email
        error_log('Mailer error: ' . $mail->ErrorInfo);
        return false;
    }
}

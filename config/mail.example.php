<?php
/*
 * Plantilla de configuración SMTP.
 * Copiá este archivo a config/mail.php (gitignoreado) y completá los valores.
 * Para Gmail: activá "Contraseñas de aplicación" en tu cuenta de Google
 * y usá esa contraseña de 16 caracteres en MAIL_PASS.
 *
 *   cp config/mail.example.php config/mail.php
 *
 * Si config/mail.php no existe, el envío de emails queda deshabilitado
 * (MAIL_ENABLED=false) sin romper ningún flujo.
 */
define('MAIL_ENABLED',  true);
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USER',     'tucuenta@gmail.com');   // Tu email remitente
define('MAIL_PASS',     'xxxx xxxx xxxx xxxx');  // Contraseña de aplicación
define('MAIL_FROM',     'tucuenta@gmail.com');   // Igual a MAIL_USER en Gmail
define('MAIL_FROM_NAME','La Canchita');

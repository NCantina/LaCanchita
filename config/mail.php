<?php
// ── CONFIGURACIÓN DE CORREO ────────────────────────────────────────────────
// Completá con los datos de tu servidor SMTP o cuenta de Gmail.
// Para Gmail: activá "Contraseñas de aplicación" en tu cuenta de Google
// y usá esa contraseña de 16 caracteres en MAIL_PASS.

define('MAIL_HOST',     'smtp.gmail.com');   // Host SMTP
define('MAIL_PORT',     587);                // 587 = TLS | 465 = SSL
define('MAIL_USER',     'tucuenta@gmail.com');   // Tu email remitente
define('MAIL_PASS',     'xxxx xxxx xxxx xxxx');  // Contraseña de aplicación
define('MAIL_FROM',     'tucuenta@gmail.com');   // Igual a MAIL_USER en Gmail
define('MAIL_FROM_NAME','La Canchita');
define('MAIL_ENABLED',  false);   // ← Cambiar a true cuando configures las credenciales

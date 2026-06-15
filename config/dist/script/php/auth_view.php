<?php
/**
 * auth_view.php — Guard de redireccionamiento para páginas HTML del panel.
 *
 * Diferencia con tenancy.php: ese responde JSON (para APIs).
 * Este redirige al panel correcto (para vistas PHP que renderizan HTML).
 *
 * Uso (desde cualquier vista, todas están 2 niveles desde la raíz):
 *   require_once '../../config/dist/script/php/auth_view.php';
 *   require_view(1, 2);   // solo superadmin y dueño
 *   require_view(3, 4);   // solo encargado y empleado
 *   require_view(5, 5);   // solo cliente
 */

if (session_status() === PHP_SESSION_NONE) session_start();

function require_view(int $min, int $max): void {
    $p = (int)($_SESSION['usuario_perfil'] ?? 0);

    if ($p === 0) {
        header('Location: ../../login.php');
        exit;
    }

    if ($p >= $min && $p <= $max) return; // perfil autorizado, continuar

    // Redirigir al panel que le corresponde según su perfil
    if ($p === 5)    header('Location: ../maquetaCliente/LaCanchitaCliente.php');
    elseif ($p <= 2) header('Location: ../maquetaAdmin/Dashboard.php');
    else             header('Location: ../maquetaEncargado/PanelEncargado.php');
    exit;
}

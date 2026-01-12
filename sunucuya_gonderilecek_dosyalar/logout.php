<?php
/**
 * Logout Script
 * Tüm kullanıcı tiplerini destekler
 */

// Session'ı başlat
session_start();

// Kullanıcı türüne göre yönlendirme URL'sini belirle
$redirect_url = '/';

if (isset($_SESSION['user_type'])) {
    switch ($_SESSION['user_type']) {
        case 'admin':
            $redirect_url = 'index.php';
            break;
        case 'kullanici':
            if (isset($_SESSION['user_rol'])) {
                if (in_array($_SESSION['user_rol'], ['super_admin', 'firma_sahibi', 'broker'])) {
                    $redirect_url = 'index.php';
                } else {
                    $redirect_url = 'index.php';
                }
            }
            break;
        default:
            $redirect_url = '/';
    }
}

// Session'ı temizle
$_SESSION = [];

// Session cookie'yi sil
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session'ı yok et
session_destroy();

// Yönlendir
header("Location: $redirect_url");
exit;
<?php

/**

 * Kimlik Doğrulama ve Yetkilendirme Sistemi

 */



// Oturum başlat (henüz başlatılmamışsa)

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}



/**

 * Kullanıcı giriş kontrolü

 */

function isLoggedIn()
{

    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);

}



/**

 * Yönetici kontrolü

 */

function isAdmin()
{

    return isLoggedIn() && $_SESSION['user_type'] === 'admin';

}



/**

 * Firma sahibi kontrolü

 */

function isFirmaSahibi()
{

    return isLoggedIn() && in_array($_SESSION['user_rol'], ['super_admin', 'firma_sahibi', 'broker']);

}



/**

 * Danışman kontrolü

 */

function isDanisman()
{

    return isLoggedIn() && $_SESSION['user_rol'] === 'danisman';

}



/**

 * Giriş zorunluluğu kontrolü

 */

function requireLogin($redirect = '/login.php')
{

    if (!isLoggedIn()) {

        header("Location: $redirect");

        exit;

    }

}



/**

 * Yönetici yetkisi kontrolü

 */

function requireAdmin()
{

    if (!isAdmin()) {

        header("Location: /index.php");

        exit;

    }

}



/**

 * Firma yetkisi kontrolü

 */

function requireFirma()
{

    if (!isFirmaSahibi()) {

        header("Location: /index.php");

        exit;

    }

}



/**

 * Kullanıcı girişi yap

 */

function login($user_id, $user_type, $user_data = [])
{
    if (isset($_SESSION['admin_id']))
        unset($_SESSION['admin_id']);

    session_regenerate_id(true); // Session fixation saldırılarına karşı



    $_SESSION['user_id'] = $user_id;

    $_SESSION['user_type'] = $user_type;

    $_SESSION['last_activity'] = time();



    // Kullanıcı verisini kaydet

    foreach ($user_data as $key => $value) {

        $_SESSION[$key] = $value;

    }



    // Denetim kaydı

    logActivity($user_id, 'Giriş yapıldı', ['user_type' => $user_type]);

}



/**

 * Kullanıcı çıkışı yap

 */

function logout()
{

    if (isLoggedIn()) {

        logActivity($_SESSION['user_id'], 'Çıkış yapıldı');

    }



    $_SESSION = [];



    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,

            $params["path"],
            $params["domain"],

            $params["secure"],
            $params["httponly"]

        );

    }



    session_destroy();

}



/**

 * Oturum zaman aşımı kontrolü (30 dakika)

 */

function checkSessionTimeout($timeout = 1800)
{

    if (isLoggedIn() && isset($_SESSION['last_activity'])) {

        if (time() - $_SESSION['last_activity'] > $timeout) {

            logout();

            return false;

        }

        $_SESSION['last_activity'] = time();

    }

    return true;

}



/**

 * CSRF Token oluştur

 */

function generateCSRFToken()
{

    if (!isset($_SESSION['csrf_token'])) {

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    }

    return $_SESSION['csrf_token'];

}



/**

 * CSRF Token doğrula

 */

function verifyCSRFToken($token)
{

    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);

}



/**

 * Güvenli parola hash'le

 */

function hashPassword($password)
{

    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

}



/**

 * Parola doğrula

 */

function verifyPassword($password, $hash)
{

    return password_verify($password, $hash);

}



/**

 * Denetim kaydı oluştur

 */

function logActivity($kullanici_id, $islem, $meta = [], $firma_id = null)
{

    try {

        $db = getDB();



        if ($firma_id === null && isset($_SESSION['firma_id'])) {

            $firma_id = $_SESSION['firma_id'];

        }



        // Kullanıcı ID kontrolü - 0 veya null ise NULL yap

        $kullanici_id = ($kullanici_id && $kullanici_id > 0) ? $kullanici_id : null;



        $sql = "INSERT INTO denetim_kayitlari (firma_id, kullanici_id, islem, meta, ip, tarih) 

                VALUES (:firma_id, :kullanici_id, :islem, :meta, :ip, NOW())";



        $stmt = $db->prepare($sql);

        $stmt->execute([

            ':firma_id' => $firma_id,

            ':kullanici_id' => $kullanici_id,

            ':islem' => $islem,

            ':meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),

            ':ip' => getUserIP()

        ]);

    } catch (Exception $e) {

        error_log("Denetim kaydı hatası: " . $e->getMessage());

        // Sessizce devam et, denetim kaydı hatası sistemi durdurmasın

    }

}



/**

 * Kullanıcı IP adresini al

 */

function getUserIP()
{

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {

        return $_SERVER['HTTP_CLIENT_IP'];

    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

        return $_SERVER['HTTP_X_FORWARDED_FOR'];

    } else {

        return $_SERVER['REMOTE_ADDR'];

    }

}



/**

 * Güvenli input temizleme

 */

function sanitizeInput($data)
{

    $data = trim($data);

    $data = stripslashes($data);

    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    return $data;

}



/**

 * Email validasyonu

 */

function validateEmail($email)
{

    return filter_var($email, FILTER_VALIDATE_EMAIL);

}



/**

 * Telefon validasyonu (Türkiye formatı)

 */

function validatePhone($phone)
{

    // 05XX XXX XX XX formatı

    $phone = preg_replace('/[^0-9]/', '', $phone);

    return preg_match('/^(05)([0-9]{9})$/', $phone);

}



// Oturum zaman aşımı kontrolü (her sayfa yüklendiğinde)

checkSessionTimeout();
<?php

/**
 * Ana Yapılandırma Dosyası
 * emlakimza.com
 */

// Hata raporlama (Üretimde kapatın!)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Özel hata loglama
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-errors.log');

// Timezone
date_default_timezone_set('Europe/Istanbul');

// Site ayarları
define('SITE_NAME', 'emlakimza.com');
define('SITE_URL', 'https://' . $_SERVER['HTTP_HOST']);
define('SITE_EMAIL', 'noreply@' . $_SERVER['HTTP_HOST']);

// Güvenlik ayarları
define('SESSION_TIMEOUT', 1800); // 30 dakika
define('TOKEN_EXPIRY', 86400); // 24 saat (WhatsApp linkleri için)
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 dakika

// Dosya yükleme ayarları
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', ['pdf', 'docx', 'doc']);
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');

// PDF ayarları
define('PDF_PATH', __DIR__ . '/../assets/uploads/sozlesmeler/');
define('PDF_DPI', 96);


// E-posta ayarları (PHPMailer için) - Veritabanından okunur
// Varsayılan değerler (veritabanı yoksa)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', SITE_EMAIL);
define('SMTP_FROM_NAME', SITE_NAME);

// Veritabanından SMTP ayarlarını oku ve define et

// SMS/WhatsApp API (İsteğe bağlı)
define('WHATSAPP_API_KEY', ''); // Kurumsal API için
define('WHATSAPP_API_URL', ''); // Kurumsal API için

// Pagination
define('ITEMS_PER_PAGE', 20);

// Cache ayarları
define('CACHE_ENABLED', false);
define('CACHE_TIME', 3600); // 1 saat

// Session ayarları
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Sadece HTTPS için
ini_set('session.cookie_samesite', 'Strict');

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone kontrolü
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Europe/Istanbul');
}

// Log klasörünü oluştur
$log_dir = __DIR__ . '/../logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Upload klasörlerini oluştur
$upload_dirs = [
    UPLOAD_PATH . 'sablonlar/',
    UPLOAD_PATH . 'sozlesmeler/',
    UPLOAD_PATH . 'temp/'
];

foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

/**
 * Sistem mesajları
 */
define('MSG_SUCCESS', 'success');
define('MSG_ERROR', 'danger');
define('MSG_WARNING', 'warning');
define('MSG_INFO', 'info');

/**
 * Kullanıcı rolleri
 */
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_FIRMA_SAHIBI', 'firma_sahibi');
define('ROLE_BROKER', 'broker');
define('ROLE_DANISMAN', 'danisman');
define('ROLE_DESTEK', 'destek');

/**
 * Sözleşme durumları
 */
define('STATUS_PENDING', 'PENDING');
define('STATUS_SIGNED_MAIN', 'SIGNED_MAIN');
define('PLAN_ENTERPRISE', 'enterprise');
/**
 * Paket Yapılandırması
 */
const PACKAGES = [
    'free' => [
        'name' => 'Ücretsiz',
        'price' => 0,
        'doc_limit' => 3,
        'user_limit' => 1
    ],
    'starter' => [
        'name' => 'Başlangıç',
        'price' => 250,
        'doc_limit' => 50,
        'user_limit' => 3
    ],
    'pro' => [
        'name' => 'Profesyonel',
        'price' => 500,
        'doc_limit' => 999999,
        'user_limit' => 10
    ]
];

// Shopier API (Varsayılan/Fallback)
if (!defined('SHOPIER_API_KEY'))
    define('SHOPIER_API_KEY', '');
if (!defined('SHOPIER_SECRET'))
    define('SHOPIER_SECRET', '');

/**
 * Debug mod (Üretimde false yapın!)
 */
define('DEBUG_MODE', false);

define('STATUS_COMPLETED', 'COMPLETED');

define('STATUS_CANCELLED', 'CANCELLED');

/**
 * Log fonksiyonu
 */
function logError($message, $context = [])
{
    $log_file = __DIR__ . '/../logs/app-errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $log_message = "[$timestamp] $message $context_str\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}


/**
 * Debug fonksiyonu
 */
function debugLog($data, $label = 'DEBUG')
{
    if (DEBUG_MODE) {
        $log_file = __DIR__ . '/../logs/debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $data_str = is_array($data) || is_object($data)
            ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : $data;
        $log_message = "[$timestamp] [$label]\n$data_str\n\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}


/**
 * Ortam kontrol
 */
function isProduction()
{
    return $_SERVER['HTTP_HOST'] !== 'localhost' && !DEBUG_MODE;
}


/**
 * HTTPS kontrolü
 */
function forceHTTPS()
{
    if (isProduction() && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Üretim ortamında HTTPS'i zorla
if (isProduction()) {
    forceHTTPS();
}

<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Güvenlik & Log ayarları
ini_set('display_errors', 0);
$log_file = __DIR__ . '/payment_callback.log';

function log_callback($msg)
{
    global $log_file;
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

log_callback("Callback başladı.");

// Gelen verileri al
$status = $_POST['status'] ?? null;
$platform_order_id = $_POST['platform_order_id'] ?? null;
$random_nr = $_POST['random_nr'] ?? null;
$signature = $_POST['signature'] ?? null;

if (!$status || !$platform_order_id || !$random_nr || !$signature) {
    log_callback("Eksik parametre.");
    die("Eksik veri.");
}

// İmza Doğrulama
$db = getDB();
$stmt = $db->query("SELECT setting_value FROM site_ayarlari WHERE setting_key = 'shopier_secret'");
$db_secret = $stmt->fetchColumn();

$api_secret = $db_secret ?: SHOPIER_SECRET;
$data = $random_nr . $platform_order_id;
$expected_signature = base64_encode(hash_hmac('SHA256', $data, $api_secret, true));

if ($signature !== $expected_signature) {
    log_callback("İmza hatası! Beklenen: $expected_signature, Gelen: $signature");
    die("Gecersiz imza.");
}

// Sipariş ID'sini çözümle (FirmaID_Paket_Zaman)
$parts = explode('_', $platform_order_id);
if (count($parts) < 3) {
    log_callback("Geçersiz sipariş ID formatı: $platform_order_id");
    die("Gecersiz ID.");
}

$firma_id = $parts[0];
$package_key = $parts[1];

if (!isset(PACKAGES[$package_key])) {
    log_callback("Tanımsız paket: $package_key");
    die("Gecersiz paket.");
}

$package = PACKAGES[$package_key];
$db = getDB();

if ($status === 'success') {
    $days = $package['duration_days'];
    $doc_limit = $package['document_limit'];
    $user_limit = $package['user_limit'];

    // Bitiş tarihini hesapla (Mevcut bitiş tarihinin üzerine ekle veya bugünden başlat)
    // Önce firmayı çekelim
    $stmt = $db->prepare("SELECT uyelik_bitis FROM firmalar WHERE id = ?");
    $stmt->execute([$firma_id]);
    $firma = $stmt->fetch();

    $current_expiry = $firma['uyelik_bitis'] ? strtotime($firma['uyelik_bitis']) : 0;
    $now = time();

    // Eğer süresi zaten varsa ve gelecekteyse, onun üzerine ekle. Yoksa bugünden başla.
    if ($current_expiry > $now) {
        $new_expiry = date('Y-m-d H:i:s', strtotime("+$days days", $current_expiry));
    } else {
        $new_expiry = date('Y-m-d H:i:s', strtotime("+$days days"));
    }

    $now_str = date('Y-m-d H:i:s');

    // Firmayı güncelle
    $updateSql = "UPDATE firmalar SET 
                  plan = ?, 
                  uyelik_baslangic = COALESCE(uyelik_baslangic, ?), 
                  uyelik_bitis = ?, 
                  belge_limiti = ?, 
                  kullanici_limiti = ? 
                  WHERE id = ?";

    $stmt = $db->prepare($updateSql);
    $stmt->execute([$package_key, $now_str, $new_expiry, $doc_limit, $user_limit, $firma_id]);

    log_callback("Firma #$firma_id paketi güncellendi: $package_key. Bitiş: $new_expiry");

    // Ödeme tablosuna kaydet
    $logSql = "INSERT INTO payments (firma_id, order_id, package_name, amount, status) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($logSql);
    $stmt->execute([$firma_id, $platform_order_id, $package_key, $package['price'], 'success']);

    echo "OK";
} else {
    // Başarısız işlem
    log_callback("Ödeme başarısız. Status: $status");
    $logSql = "INSERT INTO payments (firma_id, order_id, package_name, amount, status) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($logSql);
    $stmt->execute([$firma_id, $platform_order_id, $package_key, $package['price'], 'failed']);
    echo "OK";
}

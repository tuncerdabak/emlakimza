<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = getDB();

// Paket seçimi
$package_key = $_GET['plan'] ?? '';

// Giriş kontrolü (Firma ID lazım)
if (!isset($_SESSION['firma_id'])) {
    // Giriş yapmamışsa login'e yönlendir, dönüşte paketi hatırla
    $_SESSION['redirect_after_login'] = 'payment/pay.php?plan=' . $package_key;
    header('Location: ../login.php');
    exit;
}

$firma_id = $_SESSION['firma_id'];

// Paket Kontrolü
if (!array_key_exists($package_key, PACKAGES)) {
    die("Geçersiz paket seçimi.");
}

$package = PACKAGES[$package_key];
$price = $package['price'];

// Shopier API Bilgilerini Veritabanından Çek
$stmt = $db->query("SELECT setting_key, setting_value FROM site_ayarlari WHERE setting_key IN ('shopier_api_key', 'shopier_secret')");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$api_key = $settings['shopier_api_key'] ?? SHOPIER_API_KEY;
$api_secret = $settings['shopier_secret'] ?? SHOPIER_SECRET;

if (empty($api_key) || empty($api_secret)) {
    die("Ödeme sistemi yapılandırılmamış. Lütfen yönetici ile iletişime geçin.");
}

// Shopier Nesnesi (Bu sınıfın tanımlı olduğu varsayılmaktadır)
// require_once 'path/to/Shopier.php'; // Gerekirse Shopier sınıfını dahil edin
// $shopier = new Shopier($api_key, $api_secret);

// Ücretsiz paket seçildiyse direkt tanımla
if ($price <= 0) {
    // Burada ücretsiz paket mantığı işletilebilir (örn: kullanım hakkı sıfırla)
    // Ancak genelde ücretsiz paket otomatik tanımlıdır.
    // Şimdilik ana sayfaya yönlendiriyoruz.
    header('Location: ../index.php');
    exit;
}

// Firma bilgilerini çek
$stmt = $db->prepare("SELECT * FROM firmalar WHERE id = ?");
$stmt->execute([$firma_id]);
$firma = $stmt->fetch();

if (!$firma) {
    die("Firma bulunamadı.");
}

// API Key kontrolü
if (strpos($api_key, 'BURAYA') !== false) {
    die("Ödeme sistemi yapılandırılmamış (API Key eksik). Yönetici ile görüşün.");
}

// API Key temizliği
$api_key = trim($api_key);
$api_secret = trim($api_secret);

$random_nr = rand(100000, 999999);
$platform_order_id = $firma_id . '_' . $package_key . '_' . time();
$price_formatted = number_format($price, 2, '.', '');

// Zorunlu alan kontrolleri ve varsayılanlar
$buyer_name = !empty($firma['yetkili_adi']) ? substr($firma['yetkili_adi'], 0, 49) : 'Musteri';
$buyer_surname = 'Yetkilisi'; // Soyad ayrı değilse veya kurumsalsa sabit kalabilir
$buyer_email = !empty($firma['email']) ? $firma['email'] : 'info@' . $_SERVER['HTTP_HOST'];
$buyer_phone = !empty($firma['telefon']) ? $firma['telefon'] : '05555555555';
$billing_address = !empty($firma['adres']) ? substr($firma['adres'], 0, 250) : 'Adres Girilmemis';

// Shopier Parametreleri
$args = [
    'API_key' => $api_key,
    'website_index' => 1,
    'use_adress' => 0,
    'platform_order_id' => $platform_order_id,
    'total_order_value' => $price_formatted,
    'currency' => 0, // 0 = TL
    'product_name' => substr($package['name'], 0, 99),
    'product_type' => 1, // 1 = Dijital Ürün
    'buyer_name' => $buyer_name,
    'buyer_surname' => $buyer_surname,
    'buyer_email' => $buyer_email,
    'buyer_account_age' => 0,
    'buyer_id_nr' => $firma['id'],
    'buyer_phone' => $buyer_phone,
    'billing_address' => $billing_address,
    'billing_city' => 'Istanbul',
    'billing_country' => 'TR',
    'billing_postcode' => '34000',
    'shipping_address' => $billing_address,
    'shipping_city' => 'Istanbul',
    'shipping_country' => 'TR',
    'shipping_postcode' => '34000',
    'platform' => 0,
    'is_in_frame' => 0,
    'current_language' => 0,
    'modul_version' => '2.0.0',
    'random_nr' => $random_nr,
    'product_info' => json_encode([
        [
            'name' => $package['name'],
            'product_id' => $package_key,
            'product_type' => 1, // Dijital
            'quantity' => 1,
            'price' => $price_formatted
        ]
    ], JSON_UNESCAPED_UNICODE),
    // Genel bilgiler (Zorunlu)
    'general_info' => json_encode([
        'total' => $price_formatted,
        'order_key' => $platform_order_id,
    ], JSON_UNESCAPED_UNICODE)
];

// İmza Oluşturma
$data = $args["random_nr"] . $args["platform_order_id"] . $args["total_order_value"] . $args["currency"];
$signature = hash_hmac('SHA256', $data, $api_secret, true);
$signature = base64_encode($signature);
$args['signature'] = $signature;

$shopier_endpoint = "https://www.shopier.com/ShowProduct/api_pay4.php";

?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <title>Ödeme Yönlendiriliyor...</title>
</head>

<body onload="document.getElementById('shopier_form').submit();">
    <div style="text-align: center; margin-top: 100px; font-family: sans-serif;">
        <h3>Ödeme Sayfasına Yönlendiriliyorsunuz...</h3>
        <p>Lütfen bekleyiniz.</p>
    </div>

    <form method="post" action="<?php echo $shopier_endpoint; ?>" id="shopier_form">
        <?php foreach ($args as $key => $value): ?>
            <input type="hidden" name="<?php echo htmlspecialchars($key); ?>"
                value="<?php echo htmlspecialchars($value); ?>">
        <?php endforeach; ?>
    </form>
</body>

</html>
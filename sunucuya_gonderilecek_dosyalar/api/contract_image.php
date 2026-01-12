<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/ContractGenerator.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$sozlesme_data = verifyToken($token);

if (!$sozlesme_data) {
    // Belki token kullanıldı ama admin/danışman görüntülemek istiyor?
    // Şimdilik sadece aktif session ve geçerli token varsa gösterelim
    // Veya direkt 404
    header("HTTP/1.0 404 Not Found");
    die("Geçersiz Link");
}

$db = getDB();
$sozlesme_id = $sozlesme_data['sozlesme_id'];

// Ek bilgileri al (Danışman adı vb.)
// Ek bilgileri ve Şablon bilgilerini al
$sql = "SELECT s.*, 
               k.isim as danisman_adi, k.telefon as danisman_telefon,
               f.firma_adi, f.adres as firma_adres, f.telefon as firma_telefon, f.yetki_belge_no,
               sab.dosya_yolu, sab.sahalar
        FROM sozlesmeler s
        LEFT JOIN kullanicilar k ON s.danisman_id = k.id
        LEFT JOIN firmalar f ON s.firma_id = f.id
        LEFT JOIN sozlesme_sablonlari sab ON s.sablon_id = sab.id
        WHERE s.id = :id";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $sozlesme_id]);
$fullData = $stmt->fetch();

if (!$fullData) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$generator = new ContractGenerator($fullData['dosya_yolu'], $fullData['sahalar']);
$image = $generator->generatePreview($fullData);

if ($image) {
    $generator->outputImage($image);
} else {
    header("HTTP/1.0 500 Internal Server Error");
    echo "Resim oluşturulamadı";
}

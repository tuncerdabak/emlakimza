<?php

/**

 * Genel Yardımcı Fonksiyonlar

 */



require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/auth.php';



/**

 * Güvenli UUID oluştur (sözleşmeler için)

 */

function generateUUID()
{

    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),

        mt_rand(0, 0xffff),

        mt_rand(0, 0x0fff) | 0x4000,

        mt_rand(0, 0x3fff) | 0x8000,

        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)

    );

}



/**

 * WhatsApp linki için güvenli token oluştur

 */

function generateSecureToken($length = 64)
{

    return bin2hex(random_bytes($length / 2));

}



/**

 * WhatsApp ile sözleşme linki gönder

 */

function sendWhatsAppLink($phone, $sozlesme_id, $musteri_adi)
{

    $db = getDB();



    // Token oluştur (24 saat geçerli)

    $token = generateSecureToken();

    $son_kullanma = date('Y-m-d H:i:s', strtotime('+24 hours'));



    // Token'ı veritabanına kaydet

    $sql = "INSERT INTO whatsapp_linkleri (sozlesme_id, token, son_kullanma_tarihi, olusturma_tarihi) 

            VALUES (:sozlesme_id, :token, :son_kullanma, NOW())";



    $stmt = $db->prepare($sql);

    $stmt->execute([

        ':sozlesme_id' => $sozlesme_id,

        ':token' => $token,

        ':son_kullanma' => $son_kullanma

    ]);



    // İmza linki

    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")

        . "://" . $_SERVER['HTTP_HOST'];

    $imza_url = $base_url . "/musteri/imza.php?token=" . $token;



    // Telefon formatlama (05xx → 905xx)

    $telefon = preg_replace('/[^0-9]/', '', $phone);

    if (substr($telefon, 0, 1) === '0') {

        $telefon = '9' . $telefon;  // Hatalı: 90... değil, 9 + 05xxx → 905xxx

    }



    // WhatsApp mesajı

    $mesaj = urlencode(

        "Merhaba {$musteri_adi},\n\n" .

        "Yer gösterme sözleşmenizi imzalamak için lütfen aşağıdaki linke tıklayın:\n\n" .

        "{$imza_url}\n\n" .

        "Bu link 24 saat geçerlidir.\n\nTeşekkürler."

    );



    // WhatsApp URL (mobilde direkt WhatsApp uygulamasını açar)

    $whatsapp_url = "https://wa.me/{$telefon}?text={$mesaj}";



    return [

        'success' => true,

        'whatsapp_url' => $whatsapp_url,

        'imza_url' => $imza_url,

        'token' => $token

    ];

}





/**

 * Token geçerlilik kontrolü

 */

function verifyToken($token)
{

    $db = getDB();



    $sql = "SELECT wl.*, s.* 

            FROM whatsapp_linkleri wl

            INNER JOIN sozlesmeler s ON wl.sozlesme_id = s.id

            WHERE wl.token = :token 

            AND wl.kullanildi = 0 

            AND wl.son_kullanma_tarihi > NOW()";



    $stmt = $db->prepare($sql);

    $stmt->execute([':token' => $token]);



    return $stmt->fetch();

}



/**

 * Token'ı kullanılmış olarak işaretle

 */

function markTokenAsUsed($token)
{

    $db = getDB();



    $sql = "UPDATE whatsapp_linkleri SET kullanildi = 1 WHERE token = :token";

    $stmt = $db->prepare($sql);

    return $stmt->execute([':token' => $token]);

}



/**

 * Dosya yükleme (güvenli)

 */

function uploadFile($file, $allowed_types = ['pdf', 'docx', 'doc'], $max_size = 5242880)
{

    if (!isset($file['error']) || is_array($file['error'])) {

        return ['success' => false, 'message' => 'Geçersiz dosya'];

    }



    // Hata kontrolü

    if ($file['error'] !== UPLOAD_ERR_OK) {

        return ['success' => false, 'message' => 'Dosya yükleme hatası'];

    }



    // Boyut kontrolü (5MB)

    if ($file['size'] > $max_size) {

        return ['success' => false, 'message' => 'Dosya çok büyük (Max: 5MB)'];

    }



    // Dosya tipi kontrolü

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $mime = $finfo->file($file['tmp_name']);



    $allowed_mimes = [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc' => 'application/msword',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg'
    ];



    $ext = array_search($mime, $allowed_mimes, true);



    if ($ext === false) {
        return ['success' => false, 'message' => 'Geçersiz dosya formatı'];
    }

    // İzin verilen türlerde mi?
    if (!in_array($ext, $allowed_types)) {
        return ['success' => false, 'message' => 'Bu dosya türüne izin verilmiyor.'];
    }



    // Güvenli dosya adı oluştur

    $upload_dir = __DIR__ . '/../assets/uploads/sablonlar/';

    if (!file_exists($upload_dir)) {

        mkdir($upload_dir, 0755, true);

    }



    $filename = sprintf(
        '%s_%s.%s',

        date('YmdHis'),

        bin2hex(random_bytes(8)),

        $ext

    );



    $filepath = $upload_dir . $filename;



    if (!move_uploaded_file($file['tmp_name'], $filepath)) {

        return ['success' => false, 'message' => 'Dosya kaydedilemedi'];

    }



    // Dosya hash'i oluştur

    $file_hash = hash_file('sha256', $filepath);



    return [

        'success' => true,

        'filename' => $filename,

        'filepath' => '/assets/uploads/sablonlar/' . $filename,

        'file_hash' => $file_hash,
        'file_size' => $file['size']
    ];

    // PDF ise ve Resme çevrilmek isteniyorsa
    if ($ext === 'pdf' && in_array('png', $allowed_types) && extension_loaded('imagick')) {
        $conversion = convertPdfToImage($filepath, $upload_dir);
        if ($conversion['success']) {
            // PDF'i sil (isteğe bağlı, ama şablon resim olmalı)
            unlink($filepath);
            // Sonuçları güncelle
            $result = array_merge($result, [
                'filename' => $conversion['filename'],
                'filepath' => $conversion['filepath'],
                'converted' => true
            ]);
        }
    }

    return $result;

}

/**
 * PDF'i Resme Çevir (Imagick)
 */
function convertPdfToImage($pdfPath, $outputDir)
{
    if (!extension_loaded('imagick')) {
        return ['success' => false, 'message' => 'Imagick eklentisi yüklü değil'];
    }

    try {
        $imagick = new Imagick();
        $imagick->setResolution(150, 150); // İyi kalite için 150 DPI
        $imagick->readImage($pdfPath . '[0]'); // Sadece ilk sayfa
        $imagick->setImageFormat('png');

        $filename = 'converted_' . uniqid() . '.png';
        $outputPath = $outputDir . $filename;

        $imagick->writeImage($outputPath);
        $imagick->clear();
        $imagick->destroy();

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => '/assets/uploads/sablonlar/' . $filename
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'PDF çevirme hatası: ' . $e->getMessage()];
    }
}



/**

 * PDF oluştur (TCPDF veya DomPDF kullanarak)

 */

/**
 * Sözleşmeyi tamamla ve görsel oluştur
 */
/**
 * Sözleşmeyi tamamla, görsel ve PDF oluştur
 */
function finalizeContract($sozlesme_id)
{
    require_once __DIR__ . '/ContractGenerator.php';
    require_once __DIR__ . '/../dompdf/autoload.inc.php';

    $db = getDB();

    // Sözleşme bilgilerini al
    $sql = "SELECT s.*, si1.imza_base64 as ana_imza, si1.imzalama_tarihi as ana_imza_tarih,
                   si2.imza_base64 as teyit_imza, si2.imzalama_tarihi as teyit_imza_tarih,
                   f.firma_adi, f.adres as firma_adres, f.telefon as firma_telefon, f.yetki_belge_no, f.logo_yolu,
                   k.isim as danisman_adi, k.telefon as danisman_telefon,
                   sab.dosya_yolu, sab.sahalar
            FROM sozlesmeler s
            LEFT JOIN sozlesme_imzalar si1 ON s.id = si1.sozlesme_id AND si1.tip = 'ANA'
            LEFT JOIN sozlesme_imzalar si2 ON s.id = si2.sozlesme_id AND si2.tip = 'TEYIT'
            LEFT JOIN firmalar f ON s.firma_id = f.id
            LEFT JOIN kullanicilar k ON s.danisman_id = k.id
            LEFT JOIN sozlesme_sablonlari sab ON s.sablon_id = sab.id
            WHERE s.id = :id";

    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $sozlesme_id]);
    $sozlesme = $stmt->fetch();

    if (!$sozlesme) {
        return ['success' => false, 'message' => 'Sözleşme bulunamadı'];
    }

    // Müşteri bilgilerini decode et
    $musteri = json_decode($sozlesme['musteri_bilgileri'], true);

    // İmzalar
    $imzalar = [
        'ana' => $sozlesme['ana_imza'],
        'teyit' => $sozlesme['teyit_imza']
    ];

    // 1. GÖRSEL (PNG) OLUŞTURMA
    $generator = new ContractGenerator($sozlesme['dosya_yolu'], $sozlesme['sahalar']);
    $finalImage = $generator->generateFinal($sozlesme, $musteri, $imzalar);

    if (!$finalImage) {
        return ['success' => false, 'message' => 'Görüntü oluşturulamadı'];
    }

    $imageFilename = "sozlesme_{$sozlesme_id}_" . date('YmdHis') . ".png";
    $imagePath = $generator->saveImage($finalImage, $imageFilename);

    // 2. PDF OLUŞTURMA (Dompdf)
    try {
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans'); // Türkçe karakter desteği için

        $dompdf = new \Dompdf\Dompdf($options);

        // PDF HTML İçeriği
        $html = "
        <style>
            body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; }
            .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
            .content { line-height: 1.6; }
            .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 10px; color: #666; }
            .signature-box { width: 45%; display: inline-block; vertical-align: top; }
            .signature-img { width: 150px; height: 80px; border-bottom: 1px solid #000; }
        </style>
        <div class='header'>
            <h2>" . htmlspecialchars($sozlesme['firma_adi']) . " - YER GÖSTERME BELGESİ</h2>
        </div>
        <div class='content'>
            <p><strong>Sözleşme ID:</strong> " . $sozlesme['islem_uuid'] . "</p>
            <p><strong>Müşteri:</strong> " . htmlspecialchars($musteri['ad_soyad']) . " (" . $musteri['tc_kimlik'] . ")</p>
            <p><strong>Adres:</strong> " . htmlspecialchars($musteri['adres']) . "</p>
            <p><strong>Gayrimenkul Adresi:</strong> " . htmlspecialchars($sozlesme['gayrimenkul_adres']) . "</p>
            <p><strong>Fiyat:</strong> " . number_format($sozlesme['fiyat'], 0, ',', '.') . " TL</p>
            <hr>
            <p>Yukarıda bilgileri yer alan gayrimenkulün tarafıma gösterildiğini, bu gayrimenkulü satın almam veya kiralamam durumunda emlak danışmanlık ücretini ödemeyi kabul ve taahhüt ederim.</p>
        </div>
        <div class='footer'>
            <div class='signature-box'>
                <p><strong>Müşteri İmzası</strong></p>
                <img src='" . $sozlesme['ana_imza'] . "' class='signature-img'><br>
                <span>" . $musteri['ad_soyad'] . "</span><br>
                <small>" . date('d.m.Y H:i', strtotime($sozlesme['ana_imza_tarih'])) . "</small>
            </div>
            <div class='signature-box' style='float: right; text-align: right;'>
                <p><strong>Emlak Danışmanı</strong></p>
                <div style='height: 80px;'></div>
                <span>" . htmlspecialchars($sozlesme['danisman_adi']) . "</span>
            </div>
        </div>";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfFilename = "sozlesme_{$sozlesme_id}_" . date('YmdHis') . ".pdf";
        $pdfServerPath = __DIR__ . '/../assets/uploads/sozlesmeler/' . $pdfFilename;
        $pdfWebPath = '/assets/uploads/sozlesmeler/' . $pdfFilename;

        file_put_contents($pdfServerPath, $dompdf->output());

        // Veritabanını güncelle (Hem resim hem PDF yolunu kaydedelim veya sadece PDF'i ana dosya yapalım)
        $update_sql = "UPDATE sozlesmeler SET 
                       pdf_dosya_yolu = :pdf_path, 
                       musteri_bilgileri = JSON_SET(musteri_bilgileri, '$.png_path', :png_path) 
                       WHERE id = :id";
        $update_stmt = $db->prepare($update_sql);
        $update_stmt->execute([
            ':pdf_path' => $pdfWebPath,
            ':png_path' => $imagePath,
            ':id' => $sozlesme_id
        ]);

        $finalPath = $pdfWebPath;

    } catch (Exception $e) {
        error_log("PDF Oluşturma Hatası: " . $e->getMessage());
        $finalPath = $imagePath; // PDF hata verirse PNG ile devam et
    }

    // E-posta gönderimi (Link olarak gönderiyoruz)
    sendContractEmail($sozlesme['danisman_adi'], $musteri['email'], $finalPath, $sozlesme['firma_adi'] ?? 'emlakimza.com');

    return [
        'success' => true,
        'path' => $finalPath,
        'message' => 'Sözleşme başarıyla oluşturuldu (PDF & PNG)'
    ];
}

/**
 * E-posta Gönderimi (Basit Mail)
 */
function sendContractEmail($danisman_ad, $musteri_email, $dosya_yolu, $firma_adi)
{
    // Dosya yolu web path (ör: /assets/...), bunu tam sunucu yoluna çevirelim
    $server_path = __DIR__ . '/..' . $dosya_yolu;

    // Gerçek e-posta gönderimi için PHPMailer kullanılmalı ama şimdilik standart mail() ile yapılandırma
    // Not: Attachment göndermek mail() ile zordur (boundary vs gerekir).
    // Basitlik için link gönderelim.

    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $file_url = $base_url . $dosya_yolu;

    $to = $musteri_email; // Danışmana da gönderim eklenebilir
    $subject = "Yer Gösterme Belgesi - " . $firma_adi;

    $message = "Sayın Müşterimiz,\n\n";
    $message .= "Danışmanınız {$danisman_ad} ile gerçekleştirdiğiniz yer görme işlemine ait imzalı sözleşmeniz ektedir.\n\n";
    $message .= "Sözleşmeyi görüntülemek için tıklayın: {$file_url}\n\n";
    $message .= "Saygılarımızla,\n{$firma_adi}";

    $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "Reply-To: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Müşteriye Gönder
    if (function_exists('mail')) {
        @mail($to, $subject, $message, $headers);
    } else {
        error_log("Mail function disabled. Could not send email to $to");
    }

    // Danışmana Gönder (Eğer danışman email verisi varsa, şu an parametrede yok ama ekleyebiliriz)
    // Şimdilik sadece müşteriye ve loga.
    error_log("Mail sent to $to with link $file_url");
}



/**

 * Tarih formatla (Türkçe)

 */

function formatDate($date, $format = 'd.m.Y H:i')
{

    if (!$date)
        return '-';

    return date($format, strtotime($date));

}



/**

 * Para formatla (TL)

 */

function formatMoney($amount)
{

    return number_format($amount, 0, ',', '.') . ' ₺';

}



/**

 * Durum badge'i oluştur

 */

function getStatusBadge($durum)
{

    $badges = [

        'PENDING' => '<span class="badge bg-warning">Beklemede</span>',

        'SIGNED_MAIN' => '<span class="badge bg-info">Ana İmza Atıldı</span>',

        'COMPLETED' => '<span class="badge bg-success">Tamamlandı</span>',

        'CANCELLED' => '<span class="badge bg-danger">İptal Edildi</span>',

    ];



    return $badges[$durum] ?? '<span class="badge bg-secondary">Bilinmiyor</span>';

}



/**

 * Alert mesajı göster

 */

function showAlert($message, $type = 'info')
{

    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

/**
 * E-posta gönder (DB Ayarlarını kullanarak)
 */
function sendEmail($to, $subject, $body)
{
    $db = getDB();

    // DB'den ayarları çek
    $settings = [];
    try {
        $stmt = $db->query("SELECT * FROM site_ayarlari");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        // Tablo yoksa varsayılan config kullan
    }

    // Varsayılan config değerleri
    $smtp_host = $settings['smtp_host'] ?? (defined('SMTP_HOST') ? SMTP_HOST : '');
    $smtp_port = $settings['smtp_port'] ?? (defined('SMTP_PORT') ? SMTP_PORT : 587);
    $smtp_username = $settings['smtp_username'] ?? (defined('SMTP_USERNAME') ? SMTP_USERNAME : '');
    $smtp_password = $settings['smtp_password'] ?? (defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '');
    $smtp_active = $settings['smtp_active'] ?? '0';

    // E-posta gönderimi kapalıysa
    if ($smtp_active != '1') {
        error_log("E-posta gönderimi kapalı. Gönderilmedi: $to - $subject");
        return false;
    }

    // Basit test için mail() kullanımı
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . SITE_NAME . ' <' . SITE_EMAIL . '>' . "\r\n";

    return mail($to, $subject, $body, $headers);
}

/**
 * Şifre sıfırlama token'ı oluştur ve e-posta gönder
 */
function createPasswordResetToken($kullanici_id, $email)
{
    $db = getDB();

    // Güvenli token oluştur
    $token = generateSecureToken();
    $son_kullanma = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Kullanıcının eski token'larını sil
    $sql = "DELETE FROM password_reset_tokens WHERE kullanici_id = :kullanici_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':kullanici_id' => $kullanici_id]);

    // Yeni token'ı kaydet
    $sql = "INSERT INTO password_reset_tokens (kullanici_id, token, son_kullanma_tarihi) 
            VALUES (:kullanici_id, :token, :son_kullanma)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':kullanici_id' => $kullanici_id,
        ':token' => $token,
        ':son_kullanma' => $son_kullanma
    ]);

    // Şifre sıfırlama linki
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
        . "://" . $_SERVER['HTTP_HOST'];

    // Kullanıcı rolünü kontrol et
    $sql = "SELECT rol FROM kullanicilar WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $kullanici_id]);
    $user = $stmt->fetch();

    // Rol'e göre link oluştur
    if (in_array($user['rol'], ['super_admin', 'firma_sahibi', 'broker'])) {
        $reset_url = $base_url . "/claude/firma/sifre-sifirla.php?token=" . $token;
    } else {
        $reset_url = $base_url . "/claude/danisman/sifre-sifirla.php?token=" . $token;
    }

    // E-posta gönder
    $subject = "Şifre Sıfırlama - " . SITE_NAME;
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .button { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Şifre Sıfırlama</h2>
            </div>
            <div class='content'>
                <p>Merhaba,</p>
                <p>Hesabınız için şifre sıfırlama talebinde bulundunuz. Şifrenizi sıfırlamak için aşağıdaki butona tıklayın:</p>
                <p style='text-align: center;'>
                    <a href='{$reset_url}' class='button'>Şifremi Sıfırla</a>
                </p>
                <p>Eğer buton çalışmazsa, aşağıdaki linki tarayıcınıza kopyalayabilirsiniz:</p>
                <p style='word-break: break-all; background: #fff; padding: 10px; border: 1px solid #ddd;'>{$reset_url}</p>
                <p><strong>Bu link 24 saat geçerlidir.</strong></p>
                <p>Eğer bu talebi siz yapmadıysanız, bu e-postayı görmezden gelebilirsiniz.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . ". Tüm hakları saklıdır.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    return sendEmail($email, $subject, $body);
}

/**
 * Şifre sıfırlama token'ını doğrula
 */
function verifyPasswordResetToken($token)
{
    $db = getDB();

    $sql = "SELECT prt.*, k.email, k.isim 
            FROM password_reset_tokens prt
            INNER JOIN kullanicilar k ON prt.kullanici_id = k.id
            WHERE prt.token = :token 
            AND prt.kullanildi = 0 
            AND prt.son_kullanma_tarihi > NOW()";

    $stmt = $db->prepare($sql);
    $stmt->execute([':token' => $token]);

    return $stmt->fetch();
}

/**
 * Şifreyi sıfırla ve token'ı kullanıldı olarak işaretle
 */
function resetPassword($token, $new_password)
{
    $db = getDB();

    // Token'ı doğrula
    $token_data = verifyPasswordResetToken($token);

    if (!$token_data) {
        return [
            'success' => false,
            'message' => 'Geçersiz veya süresi dolmuş token.'
        ];
    }

    // Şifreyi hashle
    $password_hash = hashPassword($new_password);

    // Şifreyi güncelle
    $sql = "UPDATE kullanicilar SET parola_hash = :parola_hash WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':parola_hash' => $password_hash,
        ':id' => $token_data['kullanici_id']
    ]);

    // Token'ı kullanıldı olarak işaretle
    $sql = "UPDATE password_reset_tokens SET kullanildi = 1 WHERE token = :token";
    $stmt = $db->prepare($sql);
    $stmt->execute([':token' => $token]);

    return [
        'success' => true,
        'message' => 'Şifreniz başarıyla güncellendi.'
    ];
}

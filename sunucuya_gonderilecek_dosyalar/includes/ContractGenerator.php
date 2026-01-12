<?php

class ContractGenerator
{
    private $fontPath;
    private $outputDir;
    private $sablonPath;
    private $fields = [];

    public function __construct(?string $sablonPath = null, ?string $fieldsJson = null)
    {
        // Şablon yolu verildiyse onu kullan, yoksa varsayılan (eski uyumluluk için)
        if ($sablonPath) {
            $this->sablonPath = __DIR__ . '/../' . $sablonPath; // dosya_yolu relative geliyor
        } else {
            $this->sablonPath = __DIR__ . '/../sozlesme.png';
        }

        // Alanları yükle
        if ($fieldsJson) {
            $this->fields = json_decode($fieldsJson, true) ?? [];
        }

        $this->fontPath = __DIR__ . '/../assets/fonts/arial.ttf';
        $this->outputDir = __DIR__ . '/../assets/uploads/sozlesmeler/';

        if (!file_exists($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    /**
     * Önizleme için resmi oluşturur
     */
    public function generatePreview($data)
    {
        $image = $this->createImage();
        if (!$image)
            return false;

        $black = imagecolorallocate($image, 0, 0, 0);

        // Veri Setini Hazırla
        $map = $this->prepareDataMap($data);

        // Dinamik Alanları Yaz
        foreach ($this->fields as $field) {
            $type = $field['type'];

            // İmzaları önizlemede gösterme
            if (strpos($type, 'imza') !== false)
                continue;

            $coords = [$field['x'], $field['y']];
            $width = $field['w'] ?? 0;
            $height = $field['h'] ?? 0;
            $align = $field['align'] ?? 'L';

            if ($type === 'firma_logo') {
                // Logo Önizleme (Varsa)
                if (!empty($map['firma_logo'])) {
                    $this->addLogo($image, $map['firma_logo'], $coords, $width, $height);
                }
            } else {
                $text = $map[$type] ?? '';
                $fontSize = $field['fontSize'] ?? 12;
                $this->writeText($image, $text, $coords, $black, $fontSize, $align, $width);
            }
        }

        return $image;
    }

    /**
     * Final imzalı resmi oluşturur
     */
    public function generateFinal($sozlesmeData, $musteriData, $imzalar)
    {
        $image = $this->createImage();
        if (!$image)
            return false;

        $black = imagecolorallocate($image, 0, 0, 0);
        $blue = imagecolorallocate($image, 0, 0, 150);

        // Tüm verileri birleştir
        $fullData = array_merge($sozlesmeData, $musteriData);
        $map = $this->prepareDataMap($fullData);

        // Alanları İşle
        foreach ($this->fields as $field) {
            $type = $field['type'];
            $coords = [$field['x'], $field['y']];
            $width = $field['w'] ?? 0;
            $height = $field['h'] ?? 0;
            $align = $field['align'] ?? 'L';

            if ($type === 'firma_logo') {
                if (!empty($map['firma_logo'])) {
                    $this->addLogo($image, $map['firma_logo'], $coords, $width, $height);
                }
            } elseif ($type === 'imza_yer_gosterme' && !empty($imzalar['ana'])) {
                $this->addSignature($image, $imzalar['ana'], $coords, $width, $height);
            } elseif ($type === 'imza_teyit' && !empty($imzalar['teyit'])) {
                $this->addSignature($image, $imzalar['teyit'], $coords, $width, $height);
            } else {
                // Normal Metin
                $color = (strpos($type, 'musteri') !== false) ? $blue : $black;
                $text = $map[$type] ?? '';
                $fontSize = $field['fontSize'] ?? 12;
                $this->writeText($image, $text, $coords, $color, $fontSize, $align, $width);
            }
        }

        // Doğrulama Mührü Ekle
        if (!empty($sozlesmeData['islem_uuid'])) {
            $this->addVerificationStamp($image, $sozlesmeData['islem_uuid']);
        }

        return $image;
    }

    /**
     * Belgenin altına profesyonel doğrulama mührü ekler
     */
    private function addVerificationStamp($image, $uuid)
    {
        $w = imagesx($image);
        $h = imagesy($image);

        $rectColor = imagecolorallocate($image, 240, 240, 240);
        $textColor = imagecolorallocate($image, 100, 100, 100);
        $brandColor = imagecolorallocate($image, 14, 165, 233);

        // Alt kısma küçük bir info bandı
        imagefilledrectangle($image, 0, $h - 60, $w, $h, $rectColor);

        $stampText = "Bu belge emlakimza.com üzerinden dijital olarak imzalanmış ve doğrulanmıştır.";
        $uuidText = "Dogrulama Kodu: " . substr($uuid, 0, 18) . "... | Tarih: " . date('d.m.Y H:i');

        $this->writeText($image, $stampText, [20, $h - 50], $brandColor, 10);
        $this->writeText($image, $uuidText, [20, $h - 30], $textColor, 9);

        // QR Kod Alanı (Simbolik)
        $qrSize = 40;
        imagerectangle($image, $w - 60, $h - 50, $w - 20, $h - 10, $brandColor);
        $this->writeText($image, "QR", [$w - 52, $h - 40], $brandColor, 8);
    }


    private function prepareDataMap($data)
    {
        // Gayrimenkul JSON verisini çöz
        $emlak = [];
        if (!empty($data['gayrimenkul_detaylari'])) {
            $emlak = json_decode($data['gayrimenkul_detaylari'], true) ?? [];
        }

        return [
            // Genel
            'tarih' => date('d.m.Y'),

            // Firma
            'firma_logo' => $data['logo_yolu'] ?? '',
            'ticari_unvan' => $data['firma_adi'] ?? '',
            'firma_adres' => $data['firma_adres'] ?? '',
            'firma_telefon' => $data['firma_telefon'] ?? '',
            'yetki_belge_no' => $data['yetki_belge_no'] ?? '',

            // Danışman
            'danisman_ad' => $data['danisman_adi'] ?? '',
            'danisman_telefon' => $data['danisman_telefon'] ?? ($data['firma_telefon'] ?? ''),

            // Gayrimenkul (Detaylı)
            'il' => $emlak['il'] ?? '',
            'ilce' => $emlak['ilce'] ?? '',
            'mahalle' => $emlak['mahalle'] ?? '',
            'ada' => $emlak['ada'] ?? '',
            'parsel' => $emlak['parsel'] ?? '',
            'bagimsiz_bolum' => $emlak['bagimsiz_bolum'] ?? '',
            'nitelik' => $emlak['nitelik'] ?? '',
            'tam_adres' => $emlak['adres'] ?? ($data['gayrimenkul_adres'] ?? ''),
            'fiyat' => isset($data['fiyat']) ? number_format($data['fiyat'], 0, '', '.') . ' TL' : '',
            'hizmet_bedeli' => $emlak['hizmet_bedeli'] ?? '',

            // Müşteri
            'musteri_ad' => $data['ad_soyad'] ?? '', // musteriData['ad_soyad'] olarak gelir
            'musteri_tc' => $data['tc_kimlik'] ?? '',
            'musteri_telefon' => $data['telefon'] ?? '',
            'musteri_adres' => $data['adres'] ?? '',
            'musteri_email' => $data['email'] ?? '',
        ];
    }

    public function saveImage($image, $filename)
    {
        $path = $this->outputDir . $filename;
        imagepng($image, $path);
        imagedestroy($image);
        return '/assets/uploads/sozlesmeler/' . $filename;
    }

    public function outputImage($image)
    {
        header('Content-Type: image/png');
        imagepng($image);
        imagedestroy($image);
    }

    private function createImage()
    {
        if (!file_exists($this->sablonPath)) {
            error_log("Şablon dosya bulunamadı: " . $this->sablonPath);
            // Fallback: Beyaz sayfa
            $im = imagecreatetruecolor(800, 1200);
            $white = imagecolorallocate($im, 255, 255, 255);
            imagefill($im, 0, 0, $white);
            return $im;
        }

        // Uzantıya göre yükle
        $ext = strtolower(pathinfo($this->sablonPath, PATHINFO_EXTENSION));
        if ($ext === 'jpg' || $ext === 'jpeg') {
            return imagecreatefromjpeg($this->sablonPath);
        } elseif ($ext === 'png') {
            return imagecreatefrompng($this->sablonPath);
        }

        return imagecreatefromstring(file_get_contents($this->sablonPath));
    }

    private function writeText($image, $text, $coords, $color, $fontSize = 12, $align = 'L', $boxWidth = 0)
    {
        if (empty($text))
            return;

        $x = $coords[0];
        $y = $coords[1];

        if (file_exists($this->fontPath)) {
            $y += $fontSize; // TTF font baselines are bottom-left

            if ($align !== 'L' && $boxWidth > 0) {
                $bbox = imagettfbbox($fontSize, 0, $this->fontPath, $text);
                $textWidth = $bbox[2] - $bbox[0];

                if ($align === 'C') {
                    $x += ($boxWidth - $textWidth) / 2;
                } elseif ($align === 'R') {
                    $x += $boxWidth - $textWidth;
                }
            }

            imagettftext($image, $fontSize, 0, $x, $y, $color, $this->fontPath, $text);
        } else {
            // GD font fallback (Not dealing with pixel precise alignment for GD fonts roughly)
            imagestring($image, 5, $x, $y, $text, $color);
        }
    }

    private function addLogo($image, $logoPath, $coords, $width, $height)
    {
        $fullPath = __DIR__ . '/..' . $logoPath;
        if (!file_exists($fullPath))
            return;

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if ($ext == 'jpg' || $ext == 'jpeg')
            $logo = imagecreatefromjpeg($fullPath);
        elseif ($ext == 'png')
            $logo = imagecreatefrompng($fullPath);
        else
            return;

        $origW = imagesx($logo);
        $origH = imagesy($logo);

        // Oran koruma
        $ratio = $origW / $origH;
        if ($width / $height > $ratio) {
            $newW = $height * $ratio;
            $newH = $height;
        } else {
            $newH = $width / $ratio;
            $newW = $width;
        }

        // Resample ve kopyala
        // Logolar şeffaf olabilir (PNG)
        if ($ext == 'png') {
            imagealphablending($logo, true);
            imagesavealpha($logo, true);
        }

        imagecopyresampled($image, $logo, $coords[0], $coords[1], 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($logo);
    }

    private function addSignature($image, $base64Sign, $coords, $width, $height)
    {
        if (empty($base64Sign))
            return;

        $base64Sign = str_replace('data:image/png;base64,', '', $base64Sign);
        $base64Sign = str_replace(' ', '+', $base64Sign);
        $signData = base64_decode($base64Sign);
        if (!$signData)
            return;

        $signImage = imagecreatefromstring($signData);
        if (!$signImage)
            return;

        imagealphablending($signImage, true);
        imagesavealpha($signImage, true);

        // Orantılı boyutlandır
        $origWidth = imagesx($signImage);
        $origHeight = imagesy($signImage);

        // Hedef boyutlar (Editörden gelen w/h)
        $newWidth = $width;
        $newHeight = $height;

        $resizedSign = imagecreatetruecolor($newWidth, $newHeight);

        imagealphablending($resizedSign, false);
        imagesavealpha($resizedSign, true);
        $transparent = imagecolorallocatealpha($resizedSign, 255, 255, 255, 127);
        imagefilledrectangle($resizedSign, 0, 0, $newWidth, $newHeight, $transparent);

        imagecopyresampled($resizedSign, $signImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        imagecopy($image, $resizedSign, $coords[0], $coords[1], 0, 0, $newWidth, $newHeight);

        imagedestroy($signImage);
        imagedestroy($resizedSign);
    }
}
?>
<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Token kontrolü
$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Geçersiz erişim!");
}

$sozlesme_data = verifyToken($token);

if (!$sozlesme_data) {
    die("Bu link geçersiz veya süresi dolmuş!");
}

$db = getDB();
$sozlesme_id = $sozlesme_data['sozlesme_id'];

// Görüntüleme Kaydı (Foundation Enhancement)
$view_sql = "UPDATE sozlesmeler SET view_count = view_count + 1, last_view_date = NOW() WHERE id = :id";
$view_stmt = $db->prepare($view_sql);
$view_stmt->execute([':id' => $sozlesme_id]);

// Şablon bilgisini al
$sablon_sql = "SELECT * FROM sozlesme_sablonlari WHERE id = :sablon_id";
$sablon_stmt = $db->prepare($sablon_sql);
$sablon_stmt->execute([':sablon_id' => $sozlesme_data['sablon_id']]);
$sablon = $sablon_stmt->fetch();

// Form gönderimi
// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. ONAYLAMA İŞLEMİ
    if (isset($_POST['onay'])) {
        $ad_soyad = sanitizeInput($_POST['ad_soyad'] ?? '');
        $tc_kimlik = sanitizeInput($_POST['tc_kimlik'] ?? '');
        $telefon = sanitizeInput($_POST['telefon'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $adres = sanitizeInput($_POST['adres'] ?? '');
        $ana_imza = $_POST['ana_imza'] ?? '';
        $teyit_imza = $_POST['teyit_imza'] ?? '';

        if (empty($ad_soyad) || empty($tc_kimlik) || empty($telefon) || empty($adres) || empty($ana_imza) || empty($teyit_imza)) {
            $error = "Lütfen tüm zorunlu alanları doldurun ve her iki imzayı da atın!";
        } else {
            try {
                $db->beginTransaction();

                // Müşteri bilgilerini güncelle
                $musteri_bilgileri = json_encode([
                    'ad_soyad' => $ad_soyad,
                    'tc_kimlik' => $tc_kimlik,
                    'telefon' => $telefon,
                    'email' => $email,
                    'adres' => $adres
                ], JSON_UNESCAPED_UNICODE);

                // Sözleşmeyi tamamlandı olarak güncelle
                $update_sql = "UPDATE sozlesmeler 
                              SET musteri_bilgileri = :musteri_bilgileri,
                                  musteri_email = :email,
                                  durum = 'COMPLETED',
                                  guncelleme_tarihi = NOW()
                              WHERE id = :id";

                $update_stmt = $db->prepare($update_sql);
                $update_stmt->execute([
                    ':musteri_bilgileri' => $musteri_bilgileri,
                    ':email' => $email,
                    ':id' => $sozlesme_id
                ]);

                // Ana imzayı kaydet
                $imza_sql = "INSERT INTO sozlesme_imzalar 
                            (sozlesme_id, tip, imzalama_tarihi, imzalayan_ip, imza_base64, user_agent) 
                            VALUES 
                            (:sozlesme_id, 'ANA', NOW(), :ip, :imza, :user_agent)";

                $imza_stmt = $db->prepare($imza_sql);
                $imza_stmt->execute([
                    ':sozlesme_id' => $sozlesme_id,
                    ':ip' => getUserIP(),
                    ':imza' => $ana_imza,
                    ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);

                // Teyit imzasını kaydet
                $teyit_sql = "INSERT INTO sozlesme_imzalar 
                            (sozlesme_id, tip, imzalama_tarihi, imzalayan_ip, imza_base64, user_agent) 
                            VALUES 
                            (:sozlesme_id, 'TEYIT', NOW(), :ip, :imza, :user_agent)";

                $teyit_stmt = $db->prepare($teyit_sql);
                $teyit_stmt->execute([
                    ':sozlesme_id' => $sozlesme_id,
                    ':ip' => getUserIP(),
                    ':imza' => $teyit_imza,
                    ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);

                // Sözleşmeyi tamamla ve görsel oluştur
                $pdf_result = finalizeContract($sozlesme_id);

                // Token'ı kullanılmış işaretle
                markTokenAsUsed($token);

                $db->commit();

                // Başarı sayfasına yönlendir
                header("Location: basarili.php?token=" . $token);
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                $error = "Hata oluştu. Lütfen tekrar deneyin.";
                error_log("İmza kaydetme hatası: " . $e->getMessage());
            }
        }
    }
    // 2. REDDETME İŞLEMİ (reject_reason post edildiğinde)
    elseif (isset($_POST['reject_reason'])) {
        $reason = sanitizeInput($_POST['reject_reason']);

        $log_data = json_encode([
            'ip' => $ip_address,
            'browser' => $browser_info,
            'action' => 'reject',
            'reason' => $reason
        ], JSON_UNESCAPED_UNICODE);

        try {
            $db->beginTransaction();

            $imza_stmt->execute([
                ':sozlesme_id' => $sozlesme_id,
                ':ip' => getUserIP(),
                ':imza' => $ana_imza,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);

            // Teyit imzasını kaydet
            $teyit_sql = "INSERT INTO sozlesme_imzalar 
                        (sozlesme_id, tip, imzalama_tarihi, imzalayan_ip, imza_base64, user_agent) 
                        VALUES 
                        (:sozlesme_id, 'TEYIT', NOW(), :ip, :imza, :user_agent)";

            $teyit_stmt = $db->prepare($teyit_sql);
            $teyit_stmt->execute([
                ':sozlesme_id' => $sozlesme_id,
                ':ip' => getUserIP(),
                ':imza' => $teyit_imza,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);

            // Sözleşmeyi tamamla ve görsel oluştur
            $pdf_result = finalizeContract($sozlesme_id);

            // Token'ı kullanılmış işaretle
            markTokenAsUsed($token);

            $db->commit();

            // Başarı sayfasına (reddedildi mesajıyla) yönlendir
            header("Location: basarili.php?token=" . $token . "&status=rejected");
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $error = "İşlem sırasında bir hata oluştu: " . $e->getMessage();
            error_log("Sözleşme reddetme hatası: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sözleşme İmzalama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 10px;
        }

        .contract-container {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            margin: 0 auto;
        }

        .contract-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #667eea;
        }

        .contract-content {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            max-height: 300px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .signature-pad {
            border: 2px dashed #667eea;
            border-radius: 10px;
            background: #f8f9fa;
            margin: 15px 0;
            touch-action: none;
            width: 100%;
            height: 200px;
            /* Reduced height slightly to fit two */
            display: block;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px;
            margin-bottom: 15px;
        }

        .btn-sign {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 15px;
            color: white;
            font-weight: 600;
            width: 100%;
        }

        .btn-clear {
            background: #dc3545;
            border: none;
            border-radius: 10px;
            padding: 5px 15px;
            color: white;
            font-size: 0.9rem;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .required::after {
            content: " *";
            color: red;
        }
    </style>
</head>

<body>
    <div class="contract-container">
        <div class="contract-header">
            <h4><i class="bi bi-file-text"></i> Yer Gösterme Sözleşmesi</h4>
            <small class="text-muted">Sözleşme No: <?php echo htmlspecialchars($sozlesme_data['islem_uuid']); ?></small>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="info-box">
            <i class="bi bi-info-circle"></i>
            <strong>Önemli:</strong> Lütfen sözleşmeyi dikkatlice okuyun, bilgilerinizi doldurun ve
            her iki imza alanını da parmağınızla imzalayın.
        </div>

        <!-- Sözleşme İçeriği (Resim) -->
        <div class="contract-content" style="padding: 0; overflow: hidden;">
            <img src="../api/contract_image.php?token=<?php echo $token; ?>" alt="Sözleşme Önizleme"
                style="width: 100%; height: auto; display: block;">
        </div>

        <div class="text-center mb-4">
            <a href="../api/contract_image.php?token=<?php echo $token; ?>" target="_blank"
                class="btn btn-outline-primary btn-sm">
                <i class="bi bi-zoom-in"></i> Resmi Büyüt
            </a>
        </div>

        <!-- Müşteri Bilgileri Formu -->
        <form method="POST" id="contractForm">
            <h6 class="mb-3">Kişisel Bilgileriniz</h6>

            <div class="mb-3">
                <label class="form-label required">Ad Soyad</label>
                <input type="text" class="form-control" name="ad_soyad" required placeholder="Adınız ve soyadınız">
            </div>

            <div class="mb-3">
                <label class="form-label required">TC Kimlik No</label>
                <input type="text" class="form-control" name="tc_kimlik" required maxlength="11" pattern="[0-9]{11}"
                    placeholder="11 haneli TC No">
            </div>

            <div class="mb-3">
                <label class="form-label required">Telefon</label>
                <input type="tel" class="form-control" name="telefon" required pattern="[0-9]{11}"
                    placeholder="05XX XXX XX XX">
            </div>

            <div class="mb-3">
                <label class="form-label required">E-posta</label>
                <input type="email" class="form-control" name="email" required placeholder="ornek@email.com">
            </div>

            <div class="mb-3">
                <label class="form-label required">Adres</label>
                <textarea class="form-control" name="adres" required rows="2" placeholder="İkamet adresiniz"></textarea>
            </div>

            <!-- İmza Alanı 1 -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">1. Müşteri İmzası <span class="text-danger">*</span></h6>
                    <button type="button" class="btn btn-clear" onclick="clearSignature('signaturePad1', 'anaImza')">
                        <i class="bi bi-eraser"></i> Temizle
                    </button>
                </div>
                <small class="text-muted d-block mb-2">Lütfen aşağıya imzanızı atın</small>
                <canvas id="signaturePad1" class="signature-pad"></canvas>
                <input type="hidden" name="ana_imza" id="anaImza" required>
            </div>

            <!-- İmza Alanı 2 / Teyit -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">2. Müşteri İmzası (Teyit) <span class="text-danger">*</span></h6>
                    <button type="button" class="btn btn-clear" onclick="clearSignature('signaturePad2', 'teyitImza')">
                        <i class="bi bi-eraser"></i> Temizle
                    </button>
                </div>
                <small class="text-muted d-block mb-2">Lütfen teyit için tekrar imzalayın</small>
                <canvas id="signaturePad2" class="signature-pad"></canvas>
                <input type="hidden" name="teyit_imza" id="teyitImza" required>
            </div>

            <!-- Onay Checkbox -->
            <div class="form-check mt-3 mb-4">
                <input class="form-check-input" type="checkbox" id="sozlesmeOnay" required>
                <label class="form-check-label" for="sozlesmeOnay">
                    Sözleşmeyi okudum, anladım ve kabul ediyorum.
                </label>
            </div>

            <button type="submit" name="onay" class="btn-sign">
                <i class="bi bi-check-circle"></i> Sözleşmeyi Onayla ve Tamamla
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Signature Pad Yöneticisi
        class SignaturePad {
            constructor(canvasId, inputId) {
                this.canvas = document.getElementById(canvasId);
                this.input = document.getElementById(inputId);
                this.ctx = this.canvas.getContext('2d');
                this.isDrawing = false;
                this.lastX = 0;
                this.lastY = 0;
                this.hasSignature = false;

                this.init();
            }

            init() {
                this.resizeCanvas();
                window.addEventListener('orientationchange', () => setTimeout(() => this.resizeCanvas(), 200));

                // Event Listeners
                ['mousedown', 'touchstart'].forEach(evt =>
                    this.canvas.addEventListener(evt, (e) => this.startDrawing(e), { passive: false })
                );
                ['mousemove', 'touchmove'].forEach(evt =>
                    this.canvas.addEventListener(evt, (e) => this.draw(e), { passive: false })
                );
                ['mouseup', 'mouseout', 'touchend'].forEach(evt =>
                    this.canvas.addEventListener(evt, (e) => this.stopDrawing(e))
                );
            }

            resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                this.canvas.width = this.canvas.offsetWidth * ratio;
                this.canvas.height = this.canvas.offsetHeight * ratio;
                this.canvas.getContext('2d').scale(ratio, ratio);

                this.ctx.strokeStyle = '#000';
                this.ctx.lineWidth = 2.5;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';

                this.hasSignature = false;
            }

            getCoordinates(e) {
                const rect = this.canvas.getBoundingClientRect();
                const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;
                return {
                    x: clientX - rect.left,
                    y: clientY - rect.top
                };
            }

            startDrawing(e) {
                if (e.cancelable) e.preventDefault();
                this.isDrawing = true;
                const coords = this.getCoordinates(e);
                this.lastX = coords.x;
                this.lastY = coords.y;

                // Nokta koymak için
                this.ctx.beginPath();
                this.ctx.arc(this.lastX, this.lastY, 1, 0, Math.PI * 2);
                this.ctx.fill();
                this.hasSignature = true;
            }

            draw(e) {
                if (!this.isDrawing) return;
                if (e.cancelable) e.preventDefault();

                const coords = this.getCoordinates(e);
                this.ctx.beginPath();
                this.ctx.moveTo(this.lastX, this.lastY);
                this.ctx.lineTo(coords.x, coords.y);
                this.ctx.stroke();

                this.lastX = coords.x;
                this.lastY = coords.y;
                this.hasSignature = true;
            }

            stopDrawing(e) {
                if (this.isDrawing) {
                    if (e.cancelable) e.preventDefault();
                    this.isDrawing = false;
                    this.updateInput();
                }
            }

            clear() {
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                this.input.value = '';
                this.hasSignature = false;
            }

            updateInput() {
                if (this.hasSignature) {
                    this.input.value = this.canvas.toDataURL('image/png');
                }
            }
        }

        // 2 İmza alanı için başlatma
        const sig1 = new SignaturePad('signaturePad1', 'anaImza');
        const sig2 = new SignaturePad('signaturePad2', 'teyitImza');

        // Global temizleme fonksiyonu (HTML'den çağrılan)
        window.clearSignature = function (padId, inputId) {
            if (padId === 'signaturePad1') sig1.clear();
            if (padId === 'signaturePad2') sig2.clear();
        };

        // Form Gönderimi
        document.getElementById('contractForm').addEventListener('submit', function (e) {
            if (!sig1.hasSignature || !sig2.hasSignature) {
                e.preventDefault();
                alert('Lütfen her iki imza alanını da doldurun!');
                return false;
            }
        });

        // Input Formatlama
        document.querySelector('input[name="tc_kimlik"]').addEventListener('input', function (e) {
            this.value = this.value.replace(/\D/g, '').substr(0, 11);
        });

        document.querySelector('input[name="telefon"]').addEventListener('input', function (e) {
            this.value = this.value.replace(/\D/g, '').substr(0, 11);
        });
    </script>
</body>

</html>
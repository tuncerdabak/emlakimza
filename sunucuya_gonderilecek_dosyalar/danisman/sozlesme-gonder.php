<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('../danisman/login.php');

if (!isDanisman()) {
    header("Location: ../index.php");
    exit;
}

$db = getDB();
$danisman_id = $_SESSION['user_id'];
$firma_id = $_SESSION['firma_id'];

// Portföyden veri çekme (Eğer ID ve tablo varsa)
$selected_portfoy = null;
$error_db = false;

try {
    $db->query("SELECT 1 FROM portfoyler LIMIT 1");
    // Tablo var, portfoyleri çek
    $p_stmt = $db->prepare("SELECT * FROM portfoyler WHERE danisman_id = ? AND durum = 'yayinda' ORDER BY baslik");
    $p_stmt->execute([$danisman_id]);
    $my_portfolios = $p_stmt->fetchAll();

    if (isset($_GET['portfoy_id'])) {
        $pid = (int) $_GET['portfoy_id'];
        $stmt_p = $db->prepare("SELECT * FROM portfoyler WHERE id = ? AND danisman_id = ?");
        $stmt_p->execute([$pid, $danisman_id]);
        $selected_portfoy = $stmt_p->fetch();
    }
} catch (PDOException $e) {
    // Tablo henüz yoksa boş dizi
    $my_portfolios = [];
}

$message = '';
$message_type = '';

// Aktif şablonları getir
$sablon_sql = "SELECT * FROM sozlesme_sablonlari WHERE firma_id = :firma_id AND aktif = 1 ORDER BY ad";
$sablon_stmt = $db->prepare($sablon_sql);
$sablon_stmt->execute([':firma_id' => $firma_id]);
$sablonlar = $sablon_stmt->fetchAll();

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Güvenlik hatası. Lütfen tekrar deneyin.';
        $message_type = 'danger';
    } else {
        $sablon_id = $_POST['sablon_id'] ?? null;
        $musteri_adi = sanitizeInput($_POST['musteri_adi'] ?? '');
        $musteri_telefon = sanitizeInput($_POST['musteri_telefon'] ?? '');
        $musteri_email = sanitizeInput($_POST['musteri_email'] ?? '');

        // Detaylı Gayrimenkul Bilgileri
        $gayrimenkul_il = sanitizeInput($_POST['gayrimenkul_il'] ?? '');
        $gayrimenkul_ilce = sanitizeInput($_POST['gayrimenkul_ilce'] ?? '');
        $gayrimenkul_mahalle = sanitizeInput($_POST['gayrimenkul_mahalle'] ?? '');
        $gayrimenkul_ada = sanitizeInput($_POST['gayrimenkul_ada'] ?? '');
        $gayrimenkul_parsel = sanitizeInput($_POST['gayrimenkul_parsel'] ?? '');
        $gayrimenkul_bagimsiz = sanitizeInput($_POST['gayrimenkul_bagimsiz'] ?? '');
        $gayrimenkul_nitelik = sanitizeInput($_POST['gayrimenkul_nitelik'] ?? '');
        $gayrimenkul_adres = sanitizeInput($_POST['gayrimenkul_adres'] ?? '');
        $fiyat = sanitizeInput($_POST['fiyat'] ?? '');
        $hizmet_bedeli = sanitizeInput($_POST['hizmet_bedeli'] ?? '');

        if (empty($sablon_id) || empty($musteri_adi) || empty($musteri_telefon) || empty($gayrimenkul_il)) {
            $message = 'Lütfen zorunlu alanları doldurun.';
            $message_type = 'warning';
        } elseif (!validatePhone($musteri_telefon)) {
            $message = 'Geçersiz telefon numarası. (05XX XXX XXX XX)';
            $message_type = 'warning';
        } else {
            // Limit kontrolü
            require_once '../includes/PlanManager.php';
            $planManager = new PlanManager();
            if (!$planManager->canCreateDocument($firma_id)) {
                $message = 'Paketinizin belge oluşturma limitine ulaştınız. Lütfen paketinizi yükseltin.';
                $message_type = 'danger';
            } else {
                try {
                    // ... (rest of the try block)
                    $islem_uuid = generateUUID();

                    // Müşteri bilgileri JSON
                    $musteri_bilgileri = json_encode([
                        'ad_soyad' => $musteri_adi,
                        'telefon' => $musteri_telefon,
                        'email' => $musteri_email
                    ], JSON_UNESCAPED_UNICODE);

                    // Gayrimenkul detayları JSON
                    $gayrimenkul_detaylari = json_encode([
                        'il' => $gayrimenkul_il,
                        'ilce' => $gayrimenkul_ilce,
                        'mahalle' => $gayrimenkul_mahalle,
                        'ada' => $gayrimenkul_ada,
                        'parsel' => $gayrimenkul_parsel,
                        'bagimsiz_bolum' => $gayrimenkul_bagimsiz,
                        'nitelik' => $gayrimenkul_nitelik,
                        'adres' => $gayrimenkul_adres, // Tam adres de burada dursun
                        'fiyat' => $fiyat,
                        'hizmet_bedeli' => $hizmet_bedeli
                    ], JSON_UNESCAPED_UNICODE);

                    // SQL'e "gayrimenkul_detaylari" ve "portfoy_id" kolonunu ekledik
                    $insert_sql = "INSERT INTO sozlesmeler 
                               (firma_id, danisman_id, sablon_id, islem_uuid, gayrimenkul_adres, 
                                fiyat, musteri_email, durum, musteri_bilgileri, gayrimenkul_detaylari, portfoy_id, olusturma_tarihi) 
                               VALUES 
                               (:firma_id, :danisman_id, :sablon_id, :islem_uuid, :gayrimenkul_adres, 
                                :fiyat, :musteri_email, 'PENDING', :musteri_bilgileri, :gayrimenkul_detaylari, :portfoy_id, NOW())";

                    $insert_stmt = $db->prepare($insert_sql);
                    $insert_stmt->execute([
                        ':firma_id' => $firma_id,
                        ':danisman_id' => $danisman_id,
                        ':sablon_id' => $sablon_id,
                        ':islem_uuid' => $islem_uuid,
                        ':gayrimenkul_adres' => $gayrimenkul_adres,
                        ':fiyat' => str_replace(['.', ','], '', $fiyat),
                        ':musteri_email' => $musteri_email,
                        ':musteri_bilgileri' => $musteri_bilgileri,
                        ':gayrimenkul_detaylari' => $gayrimenkul_detaylari,
                        ':portfoy_id' => !empty($_GET['portfoy_id']) ? $_GET['portfoy_id'] : null
                    ]);

                    $sozlesme_id = $db->lastInsertId();
                    $whatsapp_result = sendWhatsAppLink($musteri_telefon, $sozlesme_id, $musteri_adi);

                    logActivity($danisman_id, 'Sözleşme oluşturuldu', ['sozlesme_id' => $sozlesme_id, 'uuid' => $islem_uuid], $firma_id);

                    if ($whatsapp_result['success']) {
                        header("Location: " . $whatsapp_result['whatsapp_url']);
                        exit;
                    }
                } catch (Exception $e) {
                    $message = 'Hata: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            } // End of limit check else block
        }
    }
}
$page_title = 'Sözleşme Gönder - emlakimza.com';
$body_class = 'danisman-theme';
$extra_css = '
<style>
    .form-container {
        background: white;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .form-control,
    .form-select {
        border-radius: 10px;
        padding: 10px 15px;
    }

    .btn-submit {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 10px;
        padding: 15px;
        color: white;
        width: 100%;
        font-weight: 600;
        margin-top: 20px;
    }

    .section-title {
        color: #667eea;
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 10px;
    }
</style>';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Header for Page -->
        <div class="d-flex justify-content-between align-items-center mb-4 text-white">
            <h2 class="fw-bold mb-0"><i class="bi bi-send"></i> Yeni Sözleşme Gönder</h2>
            <a href="portfoy.php" class="btn btn-outline-light"><i class="bi bi-list"></i> Portföylerim</a>
        </div>

        <div class="form-container">
            <?php if ($message): ?>
                <?php echo showAlert($message, $message_type); ?>
            <?php endif; ?>

            <!-- Hızlı Seçim -->
            <?php if (!empty($my_portfolios)): ?>
                <div class="mb-4 p-3 bg-light rounded border">
                    <label class="form-label text-primary"><i class="bi bi-lightning-charge"></i> Portföyden Hızlı
                        Seç</label>
                    <select class="form-select" onchange="window.location.href='?portfoy_id='+this.value">
                        <option value="">Portföy Seçiniz...</option>
                        <?php foreach ($my_portfolios as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo (isset($selected_portfoy['id']) && $selected_portfoy['id'] == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['baslik']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="row">
                    <!-- Şablon Seçimi -->
                    <div class="col-12 mb-4">
                        <label class="form-label required fw-bold">1. Sözleşme Şablonu</label>
                        <select class="form-select form-select-lg" name="sablon_id" required>
                            <option value="">Şablon Seçin</option>
                            <?php foreach ($sablonlar as $sablon): ?>
                                <option value="<?php echo $sablon['id']; ?>">
                                    <?php echo htmlspecialchars($sablon['ad']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Müşteri Bilgileri -->
                    <div class="col-md-6 mb-4">
                        <div class="section-title"><i class="bi bi-person"></i> Müşteri Bilgileri</div>
                        <div class="mb-3">
                            <label class="form-label required">Ad Soyad</label>
                            <input type="text" class="form-control" name="musteri_adi" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Telefon</label>
                            <input type="tel" class="form-control" id="musteri_telefon" name="musteri_telefon"
                                placeholder="05XX XXX XX XX" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" class="form-control" name="musteri_email">
                        </div>
                    </div>

                    <!-- Gayrimenkul Bilgileri -->
                    <div class="col-md-6 mb-4">
                        <div class="section-title"><i class="bi bi-house"></i> Gayrimenkul Bilgileri</div>

                        <div class="row g-2">
                            <div class="col-6 mb-2">
                                <label class="form-label required">İl</label>
                                <input type="text" class="form-control" name="gayrimenkul_il"
                                    value="<?php echo $selected_portfoy['il'] ?? ''; ?>" required>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label">İlçe</label>
                                <input type="text" class="form-control" name="gayrimenkul_ilce"
                                    value="<?php echo $selected_portfoy['ilce'] ?? ''; ?>">
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label">Mahalle</label>
                                <input type="text" class="form-control" name="gayrimenkul_mahalle"
                                    value="<?php echo $selected_portfoy['mahalle'] ?? ''; ?>">
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label">Niteliği</label>
                                <input type="text" class="form-control" name="gayrimenkul_nitelik"
                                    placeholder="Daire, Arsa..."
                                    value="<?php echo $selected_portfoy['nitelik'] ?? ''; ?>">
                            </div>
                            <div class="col-4 mb-2">
                                <label class="form-label">Ada</label>
                                <input type="text" class="form-control" name="gayrimenkul_ada"
                                    value="<?php echo $selected_portfoy['ada'] ?? ''; ?>">
                            </div>
                            <div class="col-4 mb-2">
                                <label class="form-label">Parsel</label>
                                <input type="text" class="form-control" name="gayrimenkul_parsel"
                                    value="<?php echo $selected_portfoy['parsel'] ?? ''; ?>">
                            </div>
                            <div class="col-4 mb-2">
                                <label class="form-label">Bağ. Bölüm</label>
                                <input type="text" class="form-control" name="gayrimenkul_bagimsiz"
                                    value="<?php echo $selected_portfoy['bagimsiz_bolum'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="mb-2 mt-2">
                            <label class="form-label">Açık Adres</label>
                            <textarea class="form-control" name="gayrimenkul_adres"
                                rows="2"><?php echo $selected_portfoy['adres'] ?? ''; ?></textarea>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Fiyat</label>
                                <input type="text" class="form-control" id="fiyat" name="fiyat"
                                    value="<?php echo $selected_portfoy ? number_format($selected_portfoy['fiyat'], 0, '', '.') : ''; ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Hizmet Bedeli</label>
                                <input type="text" class="form-control" name="hizmet_bedeli"
                                    placeholder="Tutar Yazınız !">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info py-2 small">
                    <i class="bi bi-info-circle"></i> Bu bilgiler sözleşmede ilgili alanlara otomatik
                    yerleştirilecektir.
                </div>

                <button type="submit" name="submit" class="btn-submit">
                    <i class="bi bi-whatsapp"></i> Sözleşmeyi Oluştur ve Gönder
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$extra_js = '
<script>
    document.getElementById("musteri_telefon").addEventListener("input", function (e) {
        let value = e.target.value.replace(/\D/g, "");
        if (value.length > 11) value = value.substr(0, 11);
        e.target.value = value;
    });

    document.getElementById("fiyat").addEventListener("keyup", function (e) {
        let value = this.value.replace(/[^\d]/g, "");
        if (value !== "") this.value = parseInt(value).toLocaleString("tr-TR");
    });
</script>
';
include '../includes/footer.php';
?>
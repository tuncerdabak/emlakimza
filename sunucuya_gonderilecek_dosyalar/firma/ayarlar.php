<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isFirmaSahibi()) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$firma_id = $_SESSION['firma_id'];
$message = '';
$message_type = '';

// Şema Kontrolü (Kolonlar yoksa ekle)
try {
    $db->query("SELECT logo_yolu FROM firmalar LIMIT 1");
} catch (PDOException $e) {
    // logo_yolu yok, ekleyelim
    $db->exec("ALTER TABLE firmalar ADD COLUMN logo_yolu VARCHAR(255) DEFAULT NULL");
    $db->exec("ALTER TABLE firmalar ADD COLUMN yetki_belge_no VARCHAR(100) DEFAULT NULL");
}

// yetkili_adi kontrolü
try {
    $db->query("SELECT yetkili_adi FROM firmalar LIMIT 1");
} catch (PDOException $e) {
    $db->exec("ALTER TABLE firmalar ADD COLUMN yetkili_adi VARCHAR(255) DEFAULT NULL");
}

// telefon kontrolü
try {
    $db->query("SELECT telefon FROM firmalar LIMIT 1");
} catch (PDOException $e) {
    $db->exec("ALTER TABLE firmalar ADD COLUMN telefon VARCHAR(20) DEFAULT NULL");
}

// adres kontrolü
try {
    $db->query("SELECT adres FROM firmalar LIMIT 1");
} catch (PDOException $e) {
    $db->exec("ALTER TABLE firmalar ADD COLUMN adres TEXT DEFAULT NULL");
}

// Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['guncelle'])) {
        $firma_adi = sanitizeInput($_POST['firma_adi']);
        $yetkili_adi = sanitizeInput($_POST['yetkili_adi']);
        $telefon = sanitizeInput($_POST['telefon']);
        $adres = sanitizeInput($_POST['adres']);
        $yetki_belge_no = sanitizeInput($_POST['yetki_belge_no']);

        $logo_path = null;

        // Logo Yükleme
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $upload_dir = __DIR__ . '/../assets/uploads/logos/';
                if (!file_exists($upload_dir))
                    mkdir($upload_dir, 0755, true);

                $new_name = 'logo_' . $firma_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $new_name)) {
                    $logo_path = '/assets/uploads/logos/' . $new_name;
                }
            } else {
                $message = 'Geçersiz logo formatı!';
                $message_type = 'danger';
            }
        }

        if (!$message) {
            try {
                $sql = "UPDATE firmalar SET 
                        firma_adi = :firma_adi, 
                        yetkili_adi = :yetkili_adi, 
                        telefon = :telefon, 
                        adres = :adres,
                        yetki_belge_no = :yetki_belge_no" .
                    ($logo_path ? ", logo_yolu = :logo_yolu" : "") .
                    " WHERE id = :id";

                $params = [
                    ':firma_adi' => $firma_adi,
                    ':yetkili_adi' => $yetkili_adi,
                    ':telefon' => $telefon,
                    ':adres' => $adres,
                    ':yetki_belge_no' => $yetki_belge_no,
                    ':id' => $firma_id
                ];

                if ($logo_path) {
                    $params[':logo_yolu'] = $logo_path;
                }

                $stmt = $db->prepare($sql);
                $stmt->execute($params);

                $message = 'Bilgiler güncellendi.';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Hata: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    } elseif (isset($_POST['sifre_degistir'])) {
        $mevcut = $_POST['mevcut_sifre'];
        $yeni = $_POST['yeni_sifre'];
        $tekrar = $_POST['yeni_sifre_tekrar'];

        if ($yeni !== $tekrar) {
            $message = 'Yeni şifreler eşleşmiyor!';
            $message_type = 'danger';
        } else {
            $user_id = $_SESSION['user_id'];
            $stmt = $db->prepare("SELECT sifre FROM kullanicilar WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (password_verify($mevcut, $user['sifre'])) {
                $new_hash = password_hash($yeni, PASSWORD_DEFAULT);
                $update = $db->prepare("UPDATE kullanicilar SET sifre = ? WHERE id = ?");
                $update->execute([$new_hash, $user_id]);

                $message = 'Şifre başarıyla değiştirildi.';
                $message_type = 'success';
            } else {
                $message = 'Mevcut şifre hatalı!';
                $message_type = 'danger';
            }
        }
    }
}

// Bilgileri getir
$stmt = $db->prepare("SELECT * FROM firmalar WHERE id = ?");
$stmt->execute([$firma_id]);
$firma = $stmt->fetch();

// Şablon sayısı (Sidebar badge için)
$sablon_count_sql = "SELECT COUNT(*) as sayi FROM sozlesme_sablonlari WHERE firma_id = :firma_id AND aktif = 1";

$sablon_stmt = $db->prepare($sablon_count_sql);
$sablon_stmt->execute([':firma_id' => $firma_id]);
$sablon_count = $sablon_stmt->fetch()['sayi'];

$page_title = 'Firma Ayarları';
$body_class = 'firma-theme';
$extra_css = '
<style>
    .card {
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .logo-preview {
        width: 150px;
        height: 150px;
        object-fit: contain;
        border: 2px dashed #ddd;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        background: #f9f9f9;
        overflow: hidden;
    }

    .logo-preview img {
        max-width: 100%;
        max-height: 100%;
    }
</style>';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 text-white">
            <h3><i class="bi bi-gear"></i> Ayarlar</h3>
        </div>

        <?php if ($message): ?>
            <?php echo showAlert($message, $message_type); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Genel Bilgiler -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Firma Bilgileri</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="guncelle" value="1">

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Firma Logosu</label>
                                    <div class="logo-preview">
                                        <?php if (!empty($firma['logo_yolu'])): ?>
                                            <img src="..<?php echo htmlspecialchars($firma['logo_yolu']); ?>" alt="Logo">
                                        <?php else: ?>
                                            <span class="text-muted">Logo Yok</span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" name="logo" class="form-control form-control-sm"
                                        accept="image/*">
                                </div>
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Firma Adı</label>
                                        <input type="text" name="firma_adi" class="form-control"
                                            value="<?php echo htmlspecialchars($firma['firma_adi']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Yetkili Adı</label>
                                        <input type="text" name="yetkili_adi" class="form-control"
                                            value="<?php echo htmlspecialchars($firma['yetkili_adi'] ?? ''); ?>"
                                            required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Telefon</label>
                                    <input type="text" name="telefon" class="form-control"
                                        value="<?php echo htmlspecialchars($firma['telefon'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Yetki Belge No</label>
                                    <input type="text" name="yetki_belge_no" class="form-control"
                                        value="<?php echo htmlspecialchars($firma['yetki_belge_no'] ?? ''); ?>"
                                        placeholder="Örn: 3400000">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Adres</label>
                                <textarea name="adres" class="form-control"
                                    rows="3"><?php echo htmlspecialchars($firma['adres'] ?? ''); ?></textarea>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Güncelle
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Şifre Değiştir -->
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Şifre Değiştir</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="sifre_degistir" value="1">
                            <div class="mb-3">
                                <label class="form-label">Mevcut Şifre</label>
                                <input type="password" name="mevcut_sifre" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Yeni Şifre</label>
                                <input type="password" name="yeni_sifre" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Yeni Şifre (Tekrar)</label>
                                <input type="password" name="yeni_sifre_tekrar" class="form-control" required>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-warning text-white">
                                    <i class="bi bi-key"></i> Değiştir
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Abonelik ve Paketler -->
                <div class="card mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Abonelik Paketleri</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <?php
                            $planKey = $firma['plan'];
                            $planName = isset(PACKAGES[$planKey]) ? PACKAGES[$planKey]['name'] : strtoupper($planKey);
                            ?>
                            Mevcut Paketiniz: <strong><?php echo $planName; ?></strong>
                            <br>
                            Bitiş Tarihi:
                            <?php echo $firma['uyelik_bitis'] ? date('d.m.Y', strtotime($firma['uyelik_bitis'])) : 'Süresiz'; ?>
                        </div>

                        <div class="row g-3">
                            <!-- Free -->
                            <div class="col-md-4">
                                <div
                                    class="border rounded p-3 text-center h-100 <?php echo $firma['plan'] == 'free' ? 'bg-light border-primary' : ''; ?>">
                                    <h6>Ücretsiz</h6>
                                    <h3 class="my-3">₺0</h3>
                                    <ul class="list-unstyled small text-start mx-auto" style="max-width: 150px;">
                                        <li><i class="bi bi-check"></i> 3 Belge/Ay</li>
                                        <li><i class="bi bi-check"></i> 1 Kullanıcı</li>
                                    </ul>
                                    <?php if ($firma['plan'] == 'free'): ?>
                                        <button class="btn btn-secondary btn-sm w-100" disabled>Mevcut Paket</button>
                                    <?php else: ?>
                                        <button class="btn btn-outline-primary btn-sm w-100" disabled>Seçilemez</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Starter -->
                            <div class="col-md-4">
                                <div
                                    class="border rounded p-3 text-center h-100 <?php echo $firma['plan'] == 'starter' ? 'bg-light border-primary' : ''; ?>">
                                    <h6>Başlangıç</h6>
                                    <h3 class="my-3">₺250<small class="fs-6 text-muted">/ay</small></h3>
                                    <ul class="list-unstyled small text-start mx-auto" style="max-width: 150px;">
                                        <li><i class="bi bi-check"></i> 50 Belge/Ay</li>
                                        <li><i class="bi bi-check"></i> 3 Kullanıcı</li>
                                    </ul>
                                    <?php if ($firma['plan'] == 'starter'): ?>
                                        <button class="btn btn-success btn-sm w-100" disabled>Mevcut Paket</button>
                                    <?php else: ?>
                                        <a href="../payment/pay.php?plan=starter" class="btn btn-primary btn-sm w-100">Satın
                                            Al</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Pro -->
                            <div class="col-md-4">
                                <div
                                    class="border rounded p-3 text-center h-100 <?php echo $firma['plan'] == 'pro' ? 'bg-light border-primary' : ''; ?>">
                                    <h6>Profesyonel</h6>
                                    <h3 class="my-3">₺500<small class="fs-6 text-muted">/ay</small></h3>
                                    <ul class="list-unstyled small text-start mx-auto" style="max-width: 150px;">
                                        <li><i class="bi bi-check"></i> Sınırsız Belge</li>
                                        <li><i class="bi bi-check"></i> 10 Kullanıcı</li>
                                    </ul>
                                    <?php if ($firma['plan'] == 'pro'): ?>
                                        <button class="btn btn-success btn-sm w-100" disabled>Mevcut Paket</button>
                                    <?php else: ?>
                                        <a href="../payment/pay.php?plan=pro" class="btn btn-primary btn-sm w-100">Satın
                                            Al</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
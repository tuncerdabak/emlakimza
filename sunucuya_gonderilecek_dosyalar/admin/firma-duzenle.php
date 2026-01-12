<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$message = '';
$message_type = '';

if ($id === 0) {
    header("Location: firmalar.php");
    exit;
}

// Güncelleme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firma_adi = sanitizeInput($_POST['firma_adi']);
    $yetkili_adi = sanitizeInput($_POST['yetkili_adi']);
    $email = sanitizeInput($_POST['email']);
    $telefon = sanitizeInput($_POST['telefon']);
    $adres = sanitizeInput($_POST['adres']);
    $plan = $_POST['plan'];
    $durum = isset($_POST['durum']) ? 1 : 0;

    // Limitler ve Üyelik Tarihleri
    $belge_limiti = (int) $_POST['belge_limiti'];
    $kullanici_limiti = (int) $_POST['kullanici_limiti'];
    $uyelik_bitis = !empty($_POST['uyelik_bitis']) ? $_POST['uyelik_bitis'] : null;

    $sql = "UPDATE firmalar SET 
            firma_adi = ?, yetkili_adi = ?, email = ?, telefon = ?, adres = ?, 
            plan = ?, durum = ?, belge_limiti = ?, kullanici_limiti = ?, uyelik_bitis = ?
            WHERE id = ?";

    $stmt = $db->prepare($sql);
    if ($stmt->execute([$firma_adi, $yetkili_adi, $email, $telefon, $adres, $plan, $durum, $belge_limiti, $kullanici_limiti, $uyelik_bitis, $id])) {
        $message = 'Firma bilgileri güncellendi.';
        $message_type = 'success';
    } else {
        $message = 'Güncelleme hatası.';
        $message_type = 'danger';
    }
}

// Firma Bilgilerini Getir
$stmt = $db->prepare("SELECT * FROM firmalar WHERE id = ?");
$stmt->execute([$id]);
$firma = $stmt->fetch();

if (!$firma) {
    echo "Firma bulunamadı.";
    exit;
}

$page_title = 'Firma Düzenle - ' . $firma['firma_adi'];
$body_class = 'admin-theme';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 text-white">
            <h2 class="fw-bold mb-0">Firma Düzenle</h2>
            <a href="firmalar.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Geri Dön</a>
        </div>

        <?php if ($message): ?>
            <?php echo showAlert($message, $message_type); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Firma Adı</label>
                            <input type="text" name="firma_adi" class="form-control"
                                value="<?php echo htmlspecialchars($firma['firma_adi']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Yetkili Adı</label>
                            <input type="text" name="yetkili_adi" class="form-control"
                                value="<?php echo htmlspecialchars($firma['yetkili_adi']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="email" class="form-control"
                                value="<?php echo htmlspecialchars($firma['email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="telefon" class="form-control"
                                value="<?php echo htmlspecialchars($firma['telefon']); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Adres</label>
                            <textarea name="adres" class="form-control"
                                rows="2"><?php echo htmlspecialchars($firma['adres']); ?></textarea>
                        </div>

                        <div class="col-12">
                            <hr>
                        </div>
                        <h5 class="mb-3">Üyelik ve Limit Ayarları</h5>

                        <div class="col-md-4">
                            <label class="form-label">Plan</label>
                            <select name="plan" class="form-select">
                                <option value="free" <?php echo $firma['plan'] == 'free' ? 'selected' : ''; ?>>Ücretsiz
                                </option>
                                <option value="starter" <?php echo $firma['plan'] == 'starter' ? 'selected' : ''; ?>>
                                    Başlangıç</option>
                                <option value="pro" <?php echo $firma['plan'] == 'pro' ? 'selected' : ''; ?>>Profesyonel
                                </option>
                                <option value="enterprise" <?php echo $firma['plan'] == 'enterprise' ? 'selected' : ''; ?>>Kurumsal</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Belge Limiti</label>
                            <input type="number" name="belge_limiti" class="form-control"
                                value="<?php echo $firma['belge_limiti']; ?>">
                            <small class="text-muted">999999+ sınırsız sayılır</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kullanıcı Limiti</label>
                            <input type="number" name="kullanici_limiti" class="form-control"
                                value="<?php echo $firma['kullanici_limiti']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Üyelik Bitiş Tarihi</label>
                            <input type="datetime-local" name="uyelik_bitis" class="form-control"
                                value="<?php echo $firma['uyelik_bitis']; ?>">
                            <small class="text-muted">Boş bırakılırsa süresiz olur.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-block">Durum</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="durum" <?php echo $firma['durum'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">Hesap Aktif</label>
                            </div>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Değişiklikleri Kaydet</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
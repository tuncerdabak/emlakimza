<?php
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

// Aktif/Pasif Toggle
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Güvenlik: Sadece kendi firmanin danismani mi?
    $check = $db->prepare("SELECT id, aktif FROM kullanicilar WHERE id = ? AND firma_id = ?");
    $check->execute([$id, $firma_id]);
    $user = $check->fetch();

    if ($user) {
        $new_status = $user['aktif'] ? 0 : 1;
        $update = $db->prepare("UPDATE kullanicilar SET aktif = ? WHERE id = ?");
        $update->execute([$new_status, $id]);

        $message = 'Danışman durumu güncellendi.';
        $message_type = 'success';
    }
}

// Şifre Değiştir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $id = (int) $_POST['user_id'];
    $new_pass = $_POST['new_password'];

    // Güvenlik
    $check = $db->prepare("SELECT id FROM kullanicilar WHERE id = ? AND firma_id = ?");
    $check->execute([$id, $firma_id]);

    if ($check->rowCount() > 0) {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE kullanicilar SET sifre = ? WHERE id = ?");
        $update->execute([$hash, $id]);

        $message = 'Danışman şifresi güncellendi.';
        $message_type = 'success';
    } else {
        $message = 'Yetkisiz işlem!';
        $message_type = 'danger';
    }
}

// Danışman Ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ekle'])) {
    $isim = sanitizeInput($_POST['isim']);
    $email = sanitizeInput($_POST['email']);
    $telefon = sanitizeInput($_POST['telefon']);
    $sifre = $_POST['sifre'];

    // Email kontrolü
    $check = $db->prepare("SELECT id FROM kullanicilar WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        $message = 'Bu email adresi zaten kayıtlı!';
        $message_type = 'danger';
    } else {
        try {
            $sql = "INSERT INTO kullanicilar (firma_id, isim, email, telefon, sifre, rol, aktif) 
                    VALUES (:firma_id, :isim, :email, :telefon, :sifre, 'danisman', 1)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':firma_id' => $firma_id,
                ':isim' => $isim,
                ':email' => $email,
                ':telefon' => $telefon,
                ':sifre' => password_hash($sifre, PASSWORD_DEFAULT)
            ]);

            $message = 'Danışman başarıyla eklendi!';
            $message_type = 'success';

            logActivity($_SESSION['user_id'], 'Danışman eklendi', ['isim' => $isim], $firma_id);
        } catch (Exception $e) {
            $message = 'Hata: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Danışmanları Getir
$sql = "SELECT k.*, 
        (SELECT COUNT(*) FROM sozlesmeler WHERE danisman_id = k.id) as sozlesme_sayisi 
        FROM kullanicilar k 
        WHERE k.firma_id = :firma_id AND k.rol = 'danisman' 
        ORDER BY k.isim ASC";
$stmt = $db->prepare($sql);
$stmt->execute([':firma_id' => $firma_id]);
$danismanlar = $stmt->fetchAll();

// Şablon sayısı (Sidebar badge için)
$sablon_count_sql = "SELECT COUNT(*) as sayi FROM sozlesme_sablonlari WHERE firma_id = :firma_id AND aktif = 1";

$sablon_stmt = $db->prepare($sablon_count_sql);
$sablon_stmt->execute([':firma_id' => $firma_id]);
$sablon_count = $sablon_stmt->fetch()['sayi'];

$page_title = 'Danışmanlar - Firma Paneli';
$body_class = 'firma-theme';
$extra_css = '
<style>
    .custom-card {
        border-radius: 10px;
        background: white;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }
</style>';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 text-white">
            <h3><i class="bi bi-people"></i> Danışman Yönetimi</h3>
        </div>

        <?php if ($message): ?>
            <?php echo showAlert($message, $message_type); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Danışman Ekleme -->
            <div class="col-lg-4">
                <div class="card custom-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Yeni Danışman Ekle</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="ekle" value="1">
                            <div class="mb-3">
                                <label class="form-label">Ad Soyad</label>
                                <input type="text" name="isim" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">E-posta</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telefon</label>
                                <input type="text" name="telefon" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Şifre</label>
                                <input type="password" name="sifre" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-lg"></i> Kaydet
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Danışman Listesi -->
            <div class="col-lg-8">
                <div class="card custom-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Danışman Listesi</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Ad Soyad</th>
                                        <th>İletişim</th>
                                        <th class="text-center">Sözleşme</th>
                                        <th class="text-center">Durum</th>
                                        <th class="text-end">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($danismanlar) > 0): ?>
                                        <?php foreach ($danismanlar as $d): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($d['isim']); ?></div>
                                                    <small class="text-muted">Kayıt:
                                                        <?php echo formatDate($d['olusturma_tarihi']); ?></small>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($d['email']); ?></div>
                                                    <small
                                                        class="text-muted"><?php echo htmlspecialchars($d['telefon']); ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-info rounded-pill"><?php echo $d['sozlesme_sayisi']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="?toggle_status=1&id=<?php echo $d['id']; ?>"
                                                        class="badge text-decoration-none <?php echo $d['aktif'] ? 'bg-success' : 'bg-danger'; ?>"
                                                        onclick="return confirm('Durumu değiştirmek istediğinize emin misiniz?')">
                                                        <?php echo $d['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                                    </a>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-warning"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#passModal<?php echo $d['id']; ?>"
                                                            title="Şifre Değiştir">
                                                            <i class="bi bi-key"></i>
                                                        </button>
                                                        <!-- Danışman düzenleme sayfası da yapılabilir, şimdilik placeholder -->
                                                        <!-- <a href="danisman-duzenle.php?id=<?php echo $d['id']; ?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i></a> -->
                                                        <button class="btn btn-outline-danger"
                                                            onclick="alert('Silme işlemi henüz aktif değil.')"><i
                                                                class="bi bi-trash"></i></button>
                                                    </div>

                                                    <!-- Şifre Modal -->
                                                    <div class="modal fade" id="passModal<?php echo $d['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Şifre Değiştir:
                                                                        <?php echo htmlspecialchars($d['isim']); ?>
                                                                    </h5>
                                                                    <button type="button" class="btn-close"
                                                                        data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body text-start">
                                                                    <form method="POST">
                                                                        <input type="hidden" name="change_password" value="1">
                                                                        <input type="hidden" name="user_id"
                                                                            value="<?php echo $d['id']; ?>">
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Yeni Şifre</label>
                                                                            <input type="password" name="new_password"
                                                                                class="form-control" required>
                                                                        </div>
                                                                        <button type="submit"
                                                                            class="btn btn-warning w-100">Güncelle</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Henüz danışman
                                                eklenmemiş.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
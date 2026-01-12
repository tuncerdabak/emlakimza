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

// Dosya yükleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $ad = sanitizeInput($_POST['ad'] ?? '');

    if (empty($ad)) {
        $message = 'Şablon adı gerekli!';
        $message_type = 'danger';
    } elseif (!isset($_FILES['dosya'])) {
        $message = 'Dosya seçilmedi!';
        $message_type = 'danger';
    } else {
        $upload_result = uploadFile($_FILES['dosya'], ['png', 'jpg', 'jpeg', 'pdf']);

        if ($upload_result['success']) {
            try {
                $sql = "INSERT INTO sozlesme_sablonlari (firma_id, ad, dosya_yolu, aktif) 
                        VALUES (:firma_id, :ad, :dosya_yolu, 1)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':firma_id' => $firma_id,
                    ':ad' => $ad,
                    ':dosya_yolu' => $upload_result['filepath']
                ]);

                $message = 'Şablon başarıyla yüklendi!';
                $message_type = 'success';

                logActivity($_SESSION['user_id'], 'Şablon eklendi', ['sablon' => $ad], $firma_id);
            } catch (Exception $e) {
                $message = 'Kayıt hatası: ' . $e->getMessage();
                $message_type = 'danger';
            }
        } else {
            $message = $upload_result['message'];
            $message_type = 'danger';
        }
    }
}

// Durum değiştirme
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $toggle_sql = "UPDATE sozlesme_sablonlari SET aktif = NOT aktif WHERE id = :id AND firma_id = :firma_id";
    $toggle_stmt = $db->prepare($toggle_sql);
    $toggle_stmt->execute([':id' => $id, ':firma_id' => $firma_id]);
    header("Location: sablonlar.php");
    exit;
}

// Silme
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    // Önce dosyayı sil
    $file_sql = "SELECT dosya_yolu FROM sozlesme_sablonlari WHERE id = :id AND firma_id = :firma_id";
    $file_stmt = $db->prepare($file_sql);
    $file_stmt->execute([':id' => $id, ':firma_id' => $firma_id]);
    $file = $file_stmt->fetch();

    if ($file) {
        $filepath = __DIR__ . '/../' . $file['dosya_yolu'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        $delete_sql = "DELETE FROM sozlesme_sablonlari WHERE id = :id AND firma_id = :firma_id";
        $delete_stmt = $db->prepare($delete_sql);
        $delete_stmt->execute([':id' => $id, ':firma_id' => $firma_id]);

        $message = 'Şablon silindi!';
        $message_type = 'success';
    }
}

// Şablonları getir
$sablonlar_sql = "SELECT * FROM sozlesme_sablonlari WHERE firma_id = :firma_id ORDER BY olusturma_tarihi DESC";
$sablonlar_stmt = $db->prepare($sablonlar_sql);
$sablonlar_stmt->execute([':firma_id' => $firma_id]);
$sablonlar = $sablonlar_stmt->fetchAll();

// Şablon sayısı (Sidebar badge için)
$sablon_count = count($sablonlar);
$page_title = 'Şablon Yönetimi';
$body_class = 'firma-theme';
$extra_css = '
<style>
    .sablon-item {
        background: white;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s;
    }

    .sablon-item:hover {
        transform: translateY(-2px);
    }

    .custom-card {
        border-radius: 10px;
        background: white;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
</style>';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-4 text-white">
            <div>
                <h3><i class="bi bi-file-earmark-text"></i> Şablon Yönetimi</h3>
            </div>
        </div>

        <div class="custom-card">
            <?php if ($message): ?>
                <?php echo showAlert($message, $message_type); ?>
            <?php endif; ?>

            <!-- Yükleme Formu -->
            <div class="card bg-light border-0 mb-4">
                <div class="card-body">
                    <h6 class="mb-3">Yeni Şablon Yükle</h6>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" name="ad" class="form-control" placeholder="Şablon Adı" required>
                            </div>
                            <div class="col-md-5">
                                <input type="file" name="dosya" class="form-control" accept=".png,.jpg,.jpeg,.pdf"
                                    required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" name="upload" class="btn btn-primary w-100">
                                    <i class="bi bi-upload"></i> Yükle
                                </button>
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">Lütfen şablonu resim (PNG, JPG) veya PDF formatında
                            yükleyiniz. PDF'ler otomatik resme çevrilir.</small>
                    </form>
                </div>
            </div>

            <!-- Şablon Listesi -->
            <h6 class="border-bottom pb-2 mb-3">Mevcut Şablonlar</h6>

            <?php if (count($sablonlar) > 0): ?>
                <?php foreach ($sablonlar as $s): ?>
                    <div class="sablon-item">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($s['ad']); ?></h6>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($s['aktif']): ?>
                                        <span class="badge bg-success" style="font-size: 0.7rem">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary" style="font-size: 0.7rem">Pasif</span>
                                    <?php endif; ?>
                                    <small class="text-muted" style="font-size: 0.8rem">
                                        <i class="bi bi-calendar"></i> <?php echo formatDate($s['olusturma_tarihi']); ?>
                                    </small>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="sablon-duzenle.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary"
                                    title="Düzenle">
                                    <i class="bi bi-pencil-square"></i> <span class="d-none d-md-inline">Düzenle</span>
                                </a>
                                <a href="?toggle=1&id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-warning"
                                    title="Aktif/Pasif">
                                    <i class="bi bi-toggle-<?php echo $s['aktif'] ? 'on' : 'off'; ?>"></i>
                                </a>
                                <a href="../<?php echo $s['dosya_yolu']; ?>" class="btn btn-sm btn-outline-info" target="_blank"
                                    title="İndir">
                                    <i class="bi bi-download"></i>
                                </a>
                                <a href="?delete=1&id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Silmek istediğinize emin misiniz?')" title="Sil">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Henüz şablon yüklenmemiş.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
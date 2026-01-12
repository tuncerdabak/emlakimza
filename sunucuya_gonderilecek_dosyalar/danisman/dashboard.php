<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Giriş kontrolü
requireLogin('../danisman/login.php');

// Sadece danışmanlar erişebilir
if (!isDanisman()) {
    header("Location: ../index.php");
    exit;
}

$db = getDB();
$danisman_id = $_SESSION['user_id'];
$firma_id = $_SESSION['firma_id'];

// İstatistikler
$stats_sql = "SELECT 
                COUNT(*) as toplam_sozlesme,
                SUM(CASE WHEN durum = 'COMPLETED' THEN 1 ELSE 0 END) as tamamlanan,
                SUM(CASE WHEN durum = 'PENDING' THEN 1 ELSE 0 END) as bekleyen,
                SUM(CASE WHEN durum = 'CANCELLED' THEN 1 ELSE 0 END) as iptal
              FROM sozlesmeler 
              WHERE danisman_id = :danisman_id";

$stats_stmt = $db->prepare($stats_sql);
$stats_stmt->execute([':danisman_id' => $danisman_id]);
$stats = $stats_stmt->fetch();

// Son sözleşmeler (Durumlar: PENDING -> COMPLETED)
$sozlesme_sql = "SELECT s.*, 
                 CASE 
                    WHEN s.durum = 'COMPLETED' THEN 'Tamamlandı'
                    WHEN s.durum = 'PENDING' THEN 'Beklemede'
                    WHEN s.durum = 'CANCELLED' THEN 'İptal Edildi'
                    ELSE 'Bilinmiyor'
                 END as durum_text
                 FROM sozlesmeler s
                 WHERE s.danisman_id = :danisman_id
                 ORDER BY s.olusturma_tarihi DESC
                 LIMIT 10";

$sozlesme_stmt = $db->prepare($sozlesme_sql);
$sozlesme_stmt->execute([':danisman_id' => $danisman_id]);
$sozlesmeler = $sozlesme_stmt->fetchAll();


$page_title = 'Danışman Panel - emlakimza.com';
$body_class = 'danisman-theme';
$extra_css = '
<style>
    .custom-card {
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border-radius: 12px;
        padding: 20px;
        background: white;
        margin-bottom: 20px;
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 15px;
    }
</style>';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 text-white">
            <div>
                <h4 class="mb-0">Hoş Geldiniz</h4>
                <small class="opacity-75"><?php echo htmlspecialchars($_SESSION['user_isim']); ?></small>
            </div>
            <div>
                <a href="portfoy.php" class="btn btn-light text-primary fw-bold">
                    <i class="bi bi-plus-lg"></i> Portföylerim
                </a>
            </div>
            <div>
                <a href="sozlesme-gonder.php" class="btn btn-light text-primary fw-bold">
                    <i class="bi bi-plus-lg"></i> Yeni Sözleşme
                </a>
            </div>
        </div>

        <!-- İstatistik Kartları -->
        <div class="row">
            <div class="col-6 col-md-6 col-lg-3">
                <div class="custom-card stat-card">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-file-text"></i>
                    </div>
                    <h3 class="mb-0"><?php echo $stats['toplam_sozlesme']; ?></h3>
                    <p class="text-muted mb-0">Toplam</p>
                </div>
            </div>
            <div class="col-6 col-md-6 col-lg-3">
                <div class="custom-card stat-card">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h3 class="mb-0"><?php echo $stats['tamamlanan']; ?></h3>
                    <p class="text-muted mb-0">Tamamlanan</p>
                </div>
            </div>
            <div class="col-6 col-md-6 col-lg-3">
                <div class="custom-card stat-card">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-clock"></i>
                    </div>
                    <h3 class="mb-0"><?php echo $stats['bekleyen']; ?></h3>
                    <p class="text-muted mb-0">Bekleyen</p>
                </div>
            </div>
            <div class="col-6 col-md-6 col-lg-3">
                <div class="custom-card stat-card">
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <h3 class="mb-0"><?php echo $stats['iptal']; ?></h3>
                    <p class="text-muted mb-0">İptal</p>
                </div>
            </div>
        </div>

        <!-- Son Sözleşmeler -->
        <div class="custom-card">
            <h6 class="mb-3"><i class="bi bi-clock-history"></i> Son Sözleşmeler</h6>
            <?php if (count($sozlesmeler) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Adres</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th>Fiyat</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sozlesmeler as $sozlesme): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold">
                                            <?php echo htmlspecialchars(substr($sozlesme['islem_uuid'], 0, 8)); ?>...
                                        </div>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars(substr($sozlesme['gayrimenkul_adres'], 0, 30)); ?>...
                                        </small>
                                    </td>
                                    <td><?php echo getStatusBadge($sozlesme['durum']); ?></td>
                                    <td>
                                        <small><i class="bi bi-calendar"></i>
                                            <?php echo formatDate($sozlesme['olusturma_tarihi']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($sozlesme['fiyat']): ?>
                                            <span class="text-success fw-bold"><?php echo formatMoney($sozlesme['fiyat']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($sozlesme['durum'] === 'COMPLETED' && !empty($sozlesme['pdf_dosya_yolu'])): ?>
                                            <a href="../<?php echo ltrim($sozlesme['pdf_dosya_yolu'], '/'); ?>" target="_blank"
                                                class="btn btn-sm btn-outline-success border-0" title="Sözleşmeyi Gör">
                                                <i class="bi bi-eye-fill fs-5"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="sozlesme-gonder.php?id=<?php echo $sozlesme['id']; ?>"
                                                class="btn btn-sm btn-outline-primary border-0" title="Tekrar Gönder">
                                                <i class="bi bi-arrow-repeat fs-5"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info border-0 bg-light text-center py-4">
                    <i class="bi bi-info-circle display-4 text-primary d-block mb-3"></i>
                    <p>Henüz sözleşme bulunmuyor.</p>
                    <a href="sozlesme-gonder.php" class="btn btn-primary mt-2">İlk Sözleşmeni Oluştur</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
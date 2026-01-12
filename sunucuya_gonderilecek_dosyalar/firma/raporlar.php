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

// İstatistikler - Tarih Aralığı (Varsayılan: Son 30 gün)
$baslangic = $_GET['baslangic'] ?? date('Y-m-d', strtotime('-30 days'));
$bitis = $_GET['bitis'] ?? date('Y-m-d');

// Genel Özet
$summary_sql = "SELECT 
    COUNT(*) as toplam,
    SUM(CASE WHEN durum = 'COMPLETED' THEN 1 ELSE 0 END) as tamamlanan,
    SUM(CASE WHEN durum = 'PENDING' THEN 1 ELSE 0 END) as bekleyen,
    SUM(CASE WHEN durum = 'CANCELLED' THEN 1 ELSE 0 END) as iptal
    FROM sozlesmeler 
    WHERE firma_id = :firma_id 
    AND olusturma_tarihi BETWEEN :start AND :end";

$summary_stmt = $db->prepare($summary_sql);
$summary_stmt->execute([
    ':firma_id' => $firma_id,
    ':start' => $baslangic . ' 00:00:00',
    ':end' => $bitis . ' 23:59:59'
]);
$ozet = $summary_stmt->fetch();

// Danışman Performansı
$danisman_sql = "SELECT k.isim, 
    COUNT(s.id) as toplam_sozlesme,
    SUM(CASE WHEN s.durum = 'COMPLETED' THEN 1 ELSE 0 END) as tamamlanan
    FROM kullanicilar k
    LEFT JOIN sozlesmeler s ON k.id = s.danisman_id 
        AND s.olusturma_tarihi BETWEEN :start AND :end
    WHERE k.firma_id = :firma_id AND k.rol = 'danisman'
    GROUP BY k.id
    ORDER BY tamamlanan DESC";

$danisman_stmt = $db->prepare($danisman_sql);
$danisman_stmt->execute([
    ':firma_id' => $firma_id,
    ':start' => $baslangic . ' 00:00:00',
    ':end' => $bitis . ' 23:59:59'
]);
$performans = $danisman_stmt->fetchAll();


// Son Sözleşmeler
$last_sql = "SELECT s.*, k.isim as danisman_adi 
    FROM sozlesmeler s
    LEFT JOIN kullanicilar k ON s.danisman_id = k.id
    WHERE s.firma_id = :firma_id
    ORDER BY s.olusturma_tarihi DESC LIMIT 10";
$last_contracts = $db->prepare($last_sql);
$last_contracts->execute([':firma_id' => $firma_id]);
$son_sozlesmeler = $last_contracts->fetchAll();

// Şablon sayısı (Sidebar badge için)
$sablon_count_sql = "SELECT COUNT(*) as sayi FROM sozlesme_sablonlari WHERE firma_id = :firma_id AND aktif = 1";
$sablon_stmt = $db->prepare($sablon_count_sql);
$sablon_stmt->execute([':firma_id' => $firma_id]);
$sablon_count = $sablon_stmt->fetch()['sayi'];

$page_title = 'Raporlar - Firma Paneli';
$body_class = 'firma-theme';
$extra_css = '
<style>
    .stat-card {
        text-align: center;
        padding: 20px;
        border-radius: 10px;
        color: white;
    }
    .card {
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            <h3><i class="bi bi-graph-up"></i> Raporlar ve İstatistikler</h3>
            <!-- Geri butonu dashboard'a gidiyor, zaten sidebar var ama kalsın -->
            <!-- <a href="dashboard.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Geri</a> -->
        </div>

        <!-- Filtre -->
        <div class="card mb-4">
            <div class="card-body">
                <form class="row align-items-end g-3">
                    <div class="col-md-4">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="date" name="baslangic" class="form-control" value="<?php echo $baslangic; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" name="bitis" class="form-control" value="<?php echo $bitis; ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> Filtrele
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- İstatistik Kartları -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary">
                    <h3><?php echo $ozet['toplam']; ?></h3>
                    <div>Toplam Sözleşme</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success">
                    <h3><?php echo $ozet['tamamlanan']; ?></h3>
                    <div>Tamamlanan</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning text-dark">
                    <h3><?php echo $ozet['bekleyen']; ?></h3>
                    <div>Bekleyen</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-danger">
                    <h3><?php echo $ozet['iptal']; ?></h3>
                    <div>İptal/Red</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Danışman Performansı -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Danışman Performansı</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Danışman</th>
                                        <th class="text-center">Toplam</th>
                                        <th class="text-center">Tamamlanan</th>
                                        <th class="text-center">Başarı %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($performans as $p): ?>
                                        <?php
                                        $basari = $p['toplam_sozlesme'] > 0
                                            ? round(($p['tamamlanan'] / $p['toplam_sozlesme']) * 100)
                                            : 0;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($p['isim']); ?></td>
                                            <td class="text-center"><?php echo $p['toplam_sozlesme']; ?></td>
                                            <td class="text-center"><?php echo $p['tamamlanan']; ?></td>
                                            <td class="text-center">
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success" role="progressbar"
                                                        style="width: <?php echo $basari; ?>%">
                                                        <?php echo $basari; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Son Sözleşmeler -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Son Sözleşmeler</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Danışman</th>
                                        <th>Durum</th>
                                        <th>Tarih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($son_sozlesmeler as $s): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($s['danisman_adi']); ?></td>
                                            <td><?php echo getStatusBadge($s['durum']); ?></td>
                                            <td><small><?php echo formatDate($s['olusturma_tarihi']); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
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
<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('../firma/login.php');
requireFirma();

$db = getDB();
$firma_id = $_SESSION['firma_id'];

// PlanManager dahil et
require_once '../includes/PlanManager.php';
$planManager = new PlanManager();
$usage = $planManager->getUsageStats($firma_id);

// Firma istatistikleri (PlanManager ile)
$doc_limit_display = $usage['docs']['limit'] > 999000 ? 'Sınırsız' : $usage['docs']['limit'];
$user_limit_display = $usage['users']['limit'] > 999000 ? 'Sınırsız' : $usage['users']['limit'];

$stats_sql = "SELECT 
                COUNT(DISTINCT s.id) as toplam_sozlesme,
                COUNT(DISTINCT s.danisman_id) as aktif_danisman,
                SUM(CASE WHEN s.durum = 'COMPLETED' THEN 1 ELSE 0 END) as tamamlanan,
                SUM(CASE WHEN s.durum = 'PENDING' THEN 1 ELSE 0 END) as bekleyen
              FROM sozlesmeler s
              WHERE s.firma_id = :firma_id";

$stats_stmt = $db->prepare($stats_sql);
$stats_stmt->execute([':firma_id' => $firma_id]);
$stats = $stats_stmt->fetch();

// Danışman performansı
$danisman_sql = "SELECT k.id, k.isim, k.email,
                 COUNT(s.id) as toplam_sozlesme,
                 SUM(CASE WHEN s.durum = 'COMPLETED' THEN 1 ELSE 0 END) as basarili
                 FROM kullanicilar k
                 LEFT JOIN sozlesmeler s ON k.id = s.danisman_id
                 WHERE k.firma_id = :firma_id AND k.rol = 'danisman' AND k.aktif = 1
                 GROUP BY k.id
                 ORDER BY basarili DESC";

$danisman_stmt = $db->prepare($danisman_sql);
$danisman_stmt->execute([':firma_id' => $firma_id]);
$danismanlar = $danisman_stmt->fetchAll();

// Son sözleşmeler
$recent_sql = "SELECT s.*, k.isim as danisman_adi
               FROM sozlesmeler s
               LEFT JOIN kullanicilar k ON s.danisman_id = k.id
               WHERE s.firma_id = :firma_id
               ORDER BY s.olusturma_tarihi DESC
               LIMIT 10";

$recent_stmt = $db->prepare($recent_sql);
$recent_stmt->execute([':firma_id' => $firma_id]);
$recent_contracts = $recent_stmt->fetchAll();

// Şablon sayısı
$sablon_count_sql = "SELECT COUNT(*) as sayi FROM sozlesme_sablonlari WHERE firma_id = :firma_id AND aktif = 1";
$sablon_stmt = $db->prepare($sablon_count_sql);
$sablon_stmt->execute([':firma_id' => $firma_id]);
$sablon_count = $sablon_stmt->fetch()['sayi'];

$page_title = 'Firma Yönetim Paneli';
$body_class = 'firma-theme';
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
                <span class="badge bg-white text-primary">Son 30 Gün</span>
            </div>
        </div>

        <!-- Paket Durumu Kartı -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="custom-card border-0 shadow-sm bg-gradient-primary text-white"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-1"><i class="bi bi-star-fill text-warning me-2"></i>
                                <?php
                                $planKey = strtolower($usage['plan']);
                                $planName = isset(PACKAGES[$planKey]) ? PACKAGES[$planKey]['name'] : strtoupper($planKey);
                                echo $planName;
                                ?>
                                PAKET
                            </h5>
                            <small class="text-white-50">
                                Bitiş:
                                <?php echo $usage['expires_at'] ? date('d.m.Y', strtotime($usage['expires_at'])) : 'Süresiz'; ?>
                            </small>
                        </div>
                        <?php if ($usage['plan'] == 'free'): ?>
                            <a href="../index.php#packages" class="btn btn-light btn-sm fw-bold text-primary">Paket
                                Yükselt</a>
                        <?php endif; ?>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between mb-1">
                                <small>Belge Kullanımı</small>
                                <small><?php echo $usage['docs']['used']; ?> / <?php echo $doc_limit_display; ?></small>
                            </div>
                            <div class="progress bg-white bg-opacity-25" style="height: 6px;">
                                <?php
                                $doc_percent = $usage['docs']['limit'] > 0 ? min(100, ($usage['docs']['used'] / $usage['docs']['limit']) * 100) : 0;
                                $doc_percent = $usage['docs']['limit'] > 999000 ? 0 : $doc_percent; // Sınırsız ise bar boş kalsın veya full
                                ?>
                                <div class="progress-bar bg-white" role="progressbar"
                                    style="width: <?php echo $doc_percent; ?>%"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between mb-1">
                                <small>Kullanıcı Kullanımı</small>
                                <small><?php echo $usage['users']['used']; ?> /
                                    <?php echo $user_limit_display; ?></small>
                            </div>
                            <div class="progress bg-white bg-opacity-25" style="height: 6px;">
                                <?php
                                $user_percent = $usage['users']['limit'] > 0 ? min(100, ($usage['users']['used'] / $usage['users']['limit']) * 100) : 0;
                                $user_percent = $usage['users']['limit'] > 999000 ? 0 : $user_percent;
                                ?>
                                <div class="progress-bar bg-white" role="progressbar"
                                    style="width: <?php echo $user_percent; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- İstatistik Kartları -->
        <div class="row">
            <div class="col-md-6 col-lg-3">
                <div class="custom-card stat-card">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-file-text"></i>
                    </div>
                    <h3 class="mb-0"><?php echo $stats['toplam_sozlesme']; ?></h3>
                    <p class="text-muted mb-0">Toplam Sözleşme</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="custom-card stat-card">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h3 class="mb-0"><?php echo $stats['tamamlanan']; ?></h3>
                    <p class="text-muted mb-0">Tamamlanan</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="custom-card stat-card">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-clock"></i>
                    </div>
                    <h3 class="mb-0"><?php echo $stats['bekleyen']; ?></h3>
                    <p class="text-muted mb-0">Bekleyen</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="custom-card stat-card">
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3 class="mb-0"><?php echo $stats['aktif_danisman']; ?></h3>
                    <p class="text-muted mb-0">Aktif Danışman</p>
                </div>
            </div>
        </div>

        <!-- Danışman Performansı -->
        <div class="custom-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="bi bi-trophy"></i> Danışman Performansı</h5>
                <a href="danismanlar.php" class="btn btn-sm btn-outline-primary">
                    Tümünü Gör
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Danışman</th>
                            <th>E-posta</th>
                            <th class="text-center">Toplam</th>
                            <th class="text-center">Başarılı</th>
                            <th class="text-center">Başarı Oranı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($danismanlar as $danisman): ?>
                            <?php
                            $oran = $danisman['toplam_sozlesme'] > 0
                                ? round(($danisman['basarili'] / $danisman['toplam_sozlesme']) * 100)
                                : 0;
                            ?>
                            <tr>
                                <td>
                                    <i class="bi bi-person-circle"></i>
                                    <?php echo htmlspecialchars($danisman['isim']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($danisman['email']); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?php echo $danisman['toplam_sozlesme']; ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?php echo $danisman['basarili']; ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" role="progressbar"
                                            style="width: <?php echo $oran; ?>%">
                                            <?php echo $oran; ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Son Sözleşmeler -->
        <div class="custom-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="bi bi-clock-history"></i> Son Sözleşmeler</h5>
                <a href="raporlar.php" class="btn btn-sm btn-outline-primary">
                    Tüm Raporlar
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>UUID</th>
                            <th>Danışman</th>
                            <th>Adres</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_contracts as $contract): ?>
                            <tr>
                                <td>
                                    <small class="font-monospace">
                                        <?php echo substr($contract['islem_uuid'], 0, 13); ?>...
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($contract['danisman_adi'] ?? 'Belirtilmemiş'); ?></td>
                                <td>
                                    <small>
                                        <?php echo htmlspecialchars(substr($contract['gayrimenkul_adres'] ?? '', 0, 30)); ?>...
                                    </small>
                                </td>
                                <td><?php echo getStatusBadge($contract['durum']); ?></td>
                                <td><small><?php echo formatDate($contract['olusturma_tarihi']); ?></small></td>
                                <td>
                                    <?php if ($contract['durum'] === 'COMPLETED' && !empty($contract['pdf_dosya_yolu'])): ?>
                                        <a href="../<?php echo ltrim($contract['pdf_dosya_yolu'], '/'); ?>" target="_blank"
                                            class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-eye"></i> Görüntüle
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
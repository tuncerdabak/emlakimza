<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$message = '';
$message_type = '';

// Firma Silme İşlemi
if (isset($_POST['delete_firma'])) {
    $firma_id = (int) $_POST['firma_id'];

    // İlişkili verileri kontrol et
    $check_stmt = $db->prepare("SELECT 
        (SELECT COUNT(*) FROM kullanicilar WHERE firma_id = ?) as kullanici,
        (SELECT COUNT(*) FROM sozlesmeler WHERE firma_id = ?) as sozlesme");
    $check_stmt->execute([$firma_id, $firma_id]);
    $counts = $check_stmt->fetch();

    if ($counts['kullanici'] > 0 || $counts['sozlesme'] > 0) {
        // Soft delete - sadece durumu pasif yap
        $stmt = $db->prepare("UPDATE firmalar SET durum = 0 WHERE id = ?");
        $stmt->execute([$firma_id]);
        $message = "Firma pasif duruma alındı (ilişkili veriler mevcut).";
        $message_type = "warning";
    } else {
        // Hard delete - tamamen sil
        $stmt = $db->prepare("DELETE FROM firmalar WHERE id = ?");
        $stmt->execute([$firma_id]);
        $message = "Firma başarıyla silindi!";
        $message_type = "success";
    }
}

// CSV Dışa Aktarma
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $export_sql = "SELECT f.id, f.firma_adi, f.yetkili_adi, f.email, f.telefon, f.adres, 
                   f.plan, f.durum, f.uyelik_bitis, f.olusturma_tarihi,
                   (SELECT COUNT(*) FROM kullanicilar WHERE firma_id = f.id) as kullanici_sayisi,
                   (SELECT COUNT(*) FROM sozlesmeler WHERE firma_id = f.id) as sozlesme_sayisi
                   FROM firmalar f ORDER BY f.id";
    $export_stmt = $db->query($export_sql);
    $export_data = $export_stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="firmalar_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    // BOM for UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Header
    fputcsv($output, ['ID', 'Firma Adı', 'Yetkili', 'E-posta', 'Telefon', 'Adres', 'Plan', 'Durum', 'Üyelik Bitiş', 'Kayıt Tarihi', 'Kullanıcı Sayısı', 'Sözleşme Sayısı']);

    // Data
    foreach ($export_data as $row) {
        fputcsv($output, [
            $row['id'],
            $row['firma_adi'],
            $row['yetkili_adi'],
            $row['email'],
            $row['telefon'],
            $row['adres'],
            strtoupper($row['plan']),
            $row['durum'] ? 'Aktif' : 'Pasif',
            $row['uyelik_bitis'] ? date('d.m.Y', strtotime($row['uyelik_bitis'])) : 'Süresiz',
            date('d.m.Y', strtotime($row['olusturma_tarihi'])),
            $row['kullanici_sayisi'],
            $row['sozlesme_sayisi']
        ]);
    }
    fclose($output);
    exit;
}

// Filtreleme
$search = $_GET['search'] ?? '';
$plan_filter = $_GET['plan'] ?? '';
$durum_filter = $_GET['durum'] ?? '';

$sql = "SELECT f.*, 
        (SELECT COUNT(*) FROM kullanicilar WHERE firma_id = f.id) as kullanici_sayisi,
        (SELECT COUNT(*) FROM sozlesmeler WHERE firma_id = f.id) as sozlesme_sayisi
        FROM firmalar f
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (f.firma_adi LIKE :search OR f.yetkili_adi LIKE :search2 OR f.email LIKE :search3 OR f.telefon LIKE :search4)";
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
    $params[':search3'] = "%$search%";
    $params[':search4'] = "%$search%";
}

if ($plan_filter) {
    $sql .= " AND f.plan = :plan";
    $params[':plan'] = $plan_filter;
}

if ($durum_filter !== '') {
    $sql .= " AND f.durum = :durum";
    $params[':durum'] = (int) $durum_filter;
}

$sql .= " ORDER BY f.olusturma_tarihi DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$firmalar = $stmt->fetchAll();

$page_title = 'Firmalar - Süper Admin';
$body_class = 'admin-theme';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 text-white">
            <h2 class="fw-bold mb-0"><i class="bi bi-building"></i> Firmalar</h2>
            <div class="d-flex gap-2">
                <a href="?export=csv" class="btn btn-success">
                    <i class="bi bi-file-earmark-spreadsheet"></i> CSV İndir
                </a>
                <button class="btn btn-light text-primary fw-bold" data-bs-toggle="modal"
                    data-bs-target="#yeniFirmaModal">
                    <i class="bi bi-plus-lg"></i> Yeni Firma Ekle
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <?php echo showAlert($message, $message_type); ?>
        <?php endif; ?>

        <!-- Arama ve Filtreleme -->
        <div class="custom-card mb-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Arama</label>
                    <input type="text" name="search" class="form-control"
                        placeholder="Firma adı, yetkili, email, telefon..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Plan</label>
                    <select name="plan" class="form-select">
                        <option value="">Tümü</option>
                        <option value="free" <?php echo $plan_filter == 'free' ? 'selected' : ''; ?>>Ücretsiz</option>
                        <option value="starter" <?php echo $plan_filter == 'starter' ? 'selected' : ''; ?>>Başlangıç
                        </option>
                        <option value="pro" <?php echo $plan_filter == 'pro' ? 'selected' : ''; ?>>Profesyonel</option>
                        <option value="enterprise" <?php echo $plan_filter == 'enterprise' ? 'selected' : ''; ?>>Kurumsal
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Durum</label>
                    <select name="durum" class="form-select">
                        <option value="">Tümü</option>
                        <option value="1" <?php echo $durum_filter === '1' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="0" <?php echo $durum_filter === '0' ? 'selected' : ''; ?>>Pasif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filtrele
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="firmalar.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-circle"></i> Temizle
                    </a>
                </div>
            </form>
        </div>

        <div class="custom-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Firma Adı</th>
                            <th>Yetkili</th>
                            <th>Paket / Bitiş</th>
                            <th class="text-center">Belge</th>
                            <th class="text-center">Kullanıcı</th>
                            <th class="text-center">Durum</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($firmalar as $firma): ?>
                            <?php
                            $is_expired = $firma['uyelik_bitis'] && strtotime($firma['uyelik_bitis']) < time();
                            $days_left = $firma['uyelik_bitis'] ? ceil((strtotime($firma['uyelik_bitis']) - time()) / 86400) : 0;
                            ?>
                            <tr>
                                <td><?php echo $firma['id']; ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($firma['firma_adi']); ?></div>
                                    <small
                                        class="text-muted"><?php echo htmlspecialchars($firma['domain'] ?? '-'); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($firma['yetkili_adi'] ?? '-'); ?><br>
                                    <small
                                        class="text-muted"><?php echo htmlspecialchars($firma['telefon'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $planKey = strtolower($firma['plan']);
                                    $planName = isset(PACKAGES[$planKey]) ? PACKAGES[$planKey]['name'] : strtoupper($planKey);
                                    ?>
                                    <span class="badge bg-info text-dark mb-1"><?php echo $planName; ?></span>
                                    <br>
                                    <?php if ($firma['plan'] != 'free'): ?>
                                        <small class="<?php echo $is_expired ? 'text-danger fw-bold' : 'text-success'; ?>">
                                            <?php echo $firma['uyelik_bitis'] ? date('d.m.Y', strtotime($firma['uyelik_bitis'])) : 'Süresiz'; ?>
                                            <?php if ($days_left > 0 && $days_left < 7): ?>
                                                (<?php echo $days_left; ?> gün)
                                            <?php endif; ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">Süresiz</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo $firma['sozlesme_sayisi']; ?> /
                                    <?php
                                    $docLimit = (isset(PACKAGES[strtolower($firma['plan'])])) ? PACKAGES[strtolower($firma['plan'])]['doc_limit'] : $firma['belge_limiti'];
                                    ?>
                                    <span class="fw-bold"><?php echo $docLimit > 999000 ? '∞' : $docLimit; ?></span>
                                </td>
                                <td class="text-center">
                                    <?php echo $firma['kullanici_sayisi']; ?> /
                                    <?php
                                    $userLimit = (isset(PACKAGES[strtolower($firma['plan'])])) ? PACKAGES[strtolower($firma['plan'])]['user_limit'] : $firma['kullanici_limiti'];
                                    ?>
                                    <span class="fw-bold"><?php echo $userLimit > 999000 ? '∞' : $userLimit; ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($firma['durum']): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="firma-duzenle.php?id=<?php echo $firma['id']; ?>"
                                            class="btn btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" style="display: inline;"
                                            onsubmit="return confirm('Bu firmayı silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="firma_id" value="<?php echo $firma['id']; ?>">
                                            <button type="submit" name="delete_firma" class="btn btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
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

<!-- Yeni Firma Modal -->
<div class="modal fade" id="yeniFirmaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Firma Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="api/firma-ekle.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Firma Adı</label>
                        <input type="text" name="firma_adi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yetkili Adı</label>
                        <input type="text" name="yetkili_adi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
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
                    <div class="mb-3">
                        <label class="form-label">Plan</label>
                        <select name="plan" class="form-select">
                            <option value="free">Ücretsiz</option>
                            <option value="pro">Pro</option>
                            <option value="enterprise">Enterprise</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Kaydet</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
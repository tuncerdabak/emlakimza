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

// Tüm sözleşmeleri getir
$sql = "SELECT musteri_bilgileri, olusturma_tarihi FROM sozlesmeler 
        WHERE danisman_id = :danisman_id 
        ORDER BY olusturma_tarihi DESC";
$stmt = $db->prepare($sql);
$stmt->execute([':danisman_id' => $danisman_id]);
$sozlesmeler = $stmt->fetchAll();

// Müşterileri ayıkla (Telefon numarasına göre benzersiz)
$musteriler = [];
foreach ($sozlesmeler as $sozlesme) {
    $bilgi = json_decode($sozlesme['musteri_bilgileri'], true);
    if ($bilgi && isset($bilgi['telefon'])) {
        $tel = $bilgi['telefon'];
        if (!isset($musteriler[$tel])) {
            $musteriler[$tel] = [
                'ad_soyad' => $bilgi['ad_soyad'],
                'telefon' => $tel,
                'email' => $bilgi['email'] ?? '-',
                'ilk_islem' => $sozlesme['olusturma_tarihi'],
                'son_islem' => $sozlesme['olusturma_tarihi'],
                'islem_sayisi' => 1
            ];
        } else {
            $musteriler[$tel]['islem_sayisi']++;
            // Tarih karşılaştırma
            if ($sozlesme['olusturma_tarihi'] > $musteriler[$tel]['son_islem']) {
                $musteriler[$tel]['son_islem'] = $sozlesme['olusturma_tarihi'];
            }
        }
    }
}
$page_title = 'Müşterilerim - Danışman Paneli';
$body_class = 'danisman-theme';
$extra_css = '
<style>
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
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 text-white">
            <h2 class="fw-bold mb-0"><i class="bi bi-people"></i> Müşterilerim</h2>
        </div>

        <div class="custom-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Ad Soyad</th>
                            <th>Telefon</th>
                            <th>Email</th>
                            <th class="text-center">İşlem Sayısı</th>
                            <th>Son İşlem</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($musteriler)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">Henüz kayıtlı müşteri bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($musteriler as $m): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($m['ad_soyad']); ?></div>
                                    </td>
                                    <td>
                                        <a href="tel:<?php echo $m['telefon']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($m['telefon']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($m['email']); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary rounded-pill"><?php echo $m['islem_sayisi']; ?></span>
                                    </td>
                                    <td><?php echo formatDate($m['son_islem']); ?></td>
                                    <td class="text-end">
                                        <a href="https://wa.me/90<?php echo substr(preg_replace('/[^0-9]/', '', $m['telefon']), -10); ?>"
                                            target="_blank" class="btn btn-sm btn-success" title="WhatsApp">
                                            <i class="bi bi-whatsapp"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$db = getDB();

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Toplam kayıt sayısı
$total_sql = "SELECT COUNT(*) FROM payments";
$total_rows = $db->query($total_sql)->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Ödemeleri getir
$sql = "SELECT p.*, f.firma_adi, f.yetkili_adi 
        FROM payments p 
        LEFT JOIN firmalar f ON p.firma_id = f.id 
        ORDER BY p.created_at DESC 
        LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$payments = $stmt->fetchAll();

$page_title = 'Ödeme Geçmişi - Süper Admin';
$body_class = 'admin-theme';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 text-white">
            <h2 class="fw-bold mb-0"><i class="bi bi-credit-card"></i> Ödeme Geçmişi</h2>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Firma</th>
                                <th>Paket</th>
                                <th>Tutar</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th>Sipariş No</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $pay): ?>
                                <tr>
                                    <td><?php echo $pay['id']; ?></td>
                                    <td>
                                        <div class="fw-bold">
                                            <?php echo htmlspecialchars($pay['firma_adi'] ?? 'Silinmiş Firma'); ?></div>
                                        <small
                                            class="text-muted"><?php echo htmlspecialchars($pay['yetkili_adi'] ?? ''); ?></small>
                                    </td>
                                    <td><span
                                            class="badge bg-info text-dark"><?php echo strtoupper($pay['package_name']); ?></span>
                                    </td>
                                    <td><span class="fw-bold text-success"><?php echo number_format($pay['amount'], 2); ?>
                                            TL</span></td>
                                    <td>
                                        <?php if ($pay['status'] == 'success'): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Başarılı</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="bi bi-x-circle"></i>
                                                <?php echo $pay['status']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($pay['created_at'])); ?></td>
                                    <td><small class="text-muted font-monospace"><?php echo $pay['order_id']; ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
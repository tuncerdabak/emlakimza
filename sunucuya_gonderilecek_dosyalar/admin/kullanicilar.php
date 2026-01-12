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

// Telefon numaralarını CSV olarak dışa aktar
if (isset($_GET['export']) && $_GET['export'] == 'phones') {
    $export_sql = "SELECT k.isim, k.telefon, k.email, k.rol, f.firma_adi 
                   FROM kullanicilar k 
                   LEFT JOIN firmalar f ON k.firma_id = f.id 
                   WHERE k.telefon IS NOT NULL AND k.telefon != ''
                   ORDER BY f.firma_adi, k.isim";
    $export_stmt = $db->query($export_sql);
    $export_data = $export_stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="telefon_listesi_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    // BOM for UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Header
    fputcsv($output, ['Ad Soyad', 'Telefon', 'E-posta', 'Rol', 'Firma']);

    // Data
    foreach ($export_data as $row) {
        fputcsv($output, [
            $row['isim'],
            $row['telefon'],
            $row['email'],
            $row['rol'] == 'firma_sahibi' ? 'Firma Sahibi' : 'Danışman',
            $row['firma_adi'] ?? '-'
        ]);
    }
    fclose($output);
    exit;
}

// Tüm kullanıcıları CSV olarak dışa aktar
if (isset($_GET['export']) && $_GET['export'] == 'all') {
    $export_sql = "SELECT k.id, k.isim, k.email, k.telefon, k.rol, k.aktif, k.olusturma_tarihi, f.firma_adi 
                   FROM kullanicilar k 
                   LEFT JOIN firmalar f ON k.firma_id = f.id 
                   ORDER BY k.id";
    $export_stmt = $db->query($export_sql);
    $export_data = $export_stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="kullanicilar_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, ['ID', 'Ad Soyad', 'E-posta', 'Telefon', 'Rol', 'Durum', 'Kayıt Tarihi', 'Firma']);

    foreach ($export_data as $row) {
        fputcsv($output, [
            $row['id'],
            $row['isim'],
            $row['email'],
            $row['telefon'],
            $row['rol'] == 'firma_sahibi' ? 'Firma Sahibi' : 'Danışman',
            $row['aktif'] ? 'Aktif' : 'Pasif',
            date('d.m.Y', strtotime($row['olusturma_tarihi'])),
            $row['firma_adi'] ?? '-'
        ]);
    }
    fclose($output);
    exit;
}

// Filtreler
$firma_id = $_GET['firma_id'] ?? null;
$rol = $_GET['rol'] ?? null;

// Sorgu
$sql = "SELECT k.*, f.firma_adi 
        FROM kullanicilar k 
        LEFT JOIN firmalar f ON k.firma_id = f.id 
        WHERE 1=1";
$params = [];

if ($firma_id) {
    $sql .= " AND k.firma_id = :firma_id";
    $params[':firma_id'] = $firma_id;
}

if ($rol) {
    $sql .= " AND k.rol = :rol";
    $params[':rol'] = $rol;
}

$sql .= " ORDER BY k.olusturma_tarihi DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$kullanicilar = $stmt->fetchAll();

// Firmalar listesi (Filtre için)
$firmalar = $db->query("SELECT id, firma_adi FROM firmalar ORDER BY firma_adi")->fetchAll();

$page_title = 'Kullanıcılar - Süper Admin';
$body_class = 'admin-theme';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0 text-white">Kullanıcılar</h2>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-download"></i> Dışa Aktar
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?export=phones"><i class="bi bi-telephone"></i> Telefon Listesi</a></li>
                        <li><a class="dropdown-item" href="?export=all"><i class="bi bi-people"></i> Tüm Kullanıcılar</a></li>
                    </ul>
                </div>
                <form class="d-flex gap-2" method="GET">
                    <select name="firma_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Tüm Firmalar</option>
                        <?php foreach ($firmalar as $f): ?>
                            <option value="<?php echo $f['id']; ?>" <?php echo $firma_id == $f['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($f['firma_adi']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="rol" class="form-select" onchange="this.form.submit()">
                        <option value="">Tüm Roller</option>
                        <option value="firma_sahibi" <?php echo $rol == 'firma_sahibi' ? 'selected' : ''; ?>>Firma Sahibi
                        </option>
                        <option value="danisman" <?php echo $rol == 'danisman' ? 'selected' : ''; ?>>Danışman</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="custom-card">
                <div class="accordion" id="firmaAccordion">
                    <?php
                    // Tüm firmaları getir
                    $firmalar_sql = "SELECT * FROM firmalar ORDER BY firma_adi ASC";
                    $firmalar = $db->query($firmalar_sql)->fetchAll();

                    // Kullanıcıları firmaya göre grupla
                    $users_sql = "SELECT * FROM kullanicilar ORDER BY isim ASC";
                    $all_users = $db->query($users_sql)->fetchAll();
                    $users_by_firm = [];
                    foreach ($all_users as $u) {
                        $users_by_firm[$u['firma_id']][] = $u;
                    }

                    // Admin kullanıcıları (firma_id = 0 veya null)
                    if (isset($users_by_firm[0])) {
                        $admin_users = $users_by_firm[0];
                        unset($users_by_firm[0]);
                    } else {
                        $admin_users = [];
                    }
                    ?>

                    <!-- Admin Kullanıcılar -->
                    <div class="accordion-item mb-3 border rounded">
                        <h2 class="accordion-header">
                            <button class="accordion-button bg-light fw-bold text-danger" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapseAdmin">
                                <i class="bi bi-shield-lock me-2"></i> Sistem Yöneticileri
                                (<?php echo count($admin_users); ?>)
                            </button>
                        </h2>
                        <div id="collapseAdmin" class="accordion-collapse collapse show"
                            data-bs-parent="#firmaAccordion">
                            <div class="accordion-body p-0">
                                <?php if (empty($admin_users)): ?>
                                    <div class="p-3 text-muted">Kayıtlı yönetici yok.</div>
                                <?php else: ?>
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>İsim</th>
                                                <th>Email</th>
                                                <th>Rol</th>
                                                <th>Durum</th>
                                                <th class="text-end">İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($admin_users as $user): ?>
                                                <tr>
                                                    <td><?php echo $user['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($user['isim']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td><span class="badge bg-danger">Admin</span></td>
                                                    <td>
                                                        <span
                                                            class="badge bg-<?php echo $user['aktif'] ? 'success' : 'secondary'; ?>">
                                                            <?php echo $user['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="kullanici-duzenle.php?id=<?php echo $user['id']; ?>"
                                                            class="btn btn-sm btn-outline-primary"><i
                                                                class="bi bi-pencil"></i></a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Firmalar -->
                    <?php foreach ($firmalar as $index => $firma): ?>
                        <?php
                        $firma_users = $users_by_firm[$firma['id']] ?? [];
                        $collapseId = "collapseFirma" . $firma['id'];
                        ?>
                        <div class="accordion-item mb-2 border rounded">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#<?php echo $collapseId; ?>">
                                    <div class="d-flex justify-content-between w-100 me-3 align-items-center">
                                        <span class="fw-bold text-primary">
                                            <i class="bi bi-building me-2"></i>
                                            <?php echo htmlspecialchars($firma['firma_adi']); ?>
                                        </span>
                                        <span class="badge bg-secondary rounded-pill"><?php echo count($firma_users); ?>
                                            Kullanıcı</span>
                                    </div>
                                </button>
                            </h2>
                            <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse"
                                data-bs-parent="#firmaAccordion">
                                <div class="accordion-body p-0">
                                    <?php if (empty($firma_users)): ?>
                                        <div class="p-3 text-muted">Bu firmaya bağlı kullanıcı bulunamadı.</div>
                                    <?php else: ?>
                                        <table class="table table-hover mb-0 align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>İsim</th>
                                                    <th>Email</th>
                                                    <th>Telefon</th>
                                                    <th>Rol</th>
                                                    <th>Durum</th>
                                                    <th class="text-end">İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($firma_users as $user): ?>
                                                    <tr>
                                                        <td><?php echo $user['id']; ?></td>
                                                        <td>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($user['isim']); ?>
                                                            </div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['telefon']); ?></td>
                                                        <td>
                                                            <?php if ($user['rol'] == 'firma_sahibi'): ?>
                                                                <span class="badge bg-primary">Firma Sahibi</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-info text-dark">Danışman</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="badge bg-<?php echo $user['aktif'] ? 'success' : 'danger'; ?>">
                                                                <?php echo $user['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-end">
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="kullanici-duzenle.php?id=<?php echo $user['id']; ?>"
                                                                    class="btn btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
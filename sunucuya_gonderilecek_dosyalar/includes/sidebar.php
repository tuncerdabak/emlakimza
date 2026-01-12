<?php
// Mevcut sayfa adını al
$current_page = basename($_SERVER['PHP_SELF']);

// Rol belirle (admin, firma, danisman)
$role = '';
$user_name = '';
$user_title = '';
$logout_path = 'logout.php'; // Default relative logout

if (isset($_SESSION['admin_id']) && strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    $role = 'admin';
    $user_name = $_SESSION['admin_adsoyad'];
    $user_title = 'Süper Admin';
    $sidebar_title = 'Admin Panel';
    $sidebar_icon = 'bi-shield-check';
    $menu_items = [
        ['url' => 'dashboard.php', 'icon' => 'bi-speedometer2', 'text' => 'Dashboard'],
        ['url' => 'firmalar.php', 'icon' => 'bi-building', 'text' => 'Firmalar'],
        ['url' => 'kullanicilar.php', 'icon' => 'bi-people', 'text' => 'Kullanıcılar'],
        ['url' => 'payments.php', 'icon' => 'bi-credit-card', 'text' => 'Ödeme Geçmişi'],
        ['url' => 'error-logs.php', 'icon' => 'bi-bug', 'text' => 'Hata Logları'],
        ['url' => 'sistem-ayarlari.php', 'icon' => 'bi-gear', 'text' => 'Sistem Ayarları']
    ];
}
// Firma Sahibi veya Broker kontrolü
// Check both 'user_rol' (new) and 'rol' (legacy) keys
elseif (
    (isset($_SESSION['user_rol']) && in_array($_SESSION['user_rol'], ['firma_sahibi', 'broker', 'firma'])) ||
    (isset($_SESSION['rol']) && $_SESSION['rol'] == 'firma')
) {
    if (strpos($_SERVER['PHP_SELF'], '/firma/') !== false) {
        $role = 'firma';
        $user_name = $_SESSION['user_isim'] ?? $_SESSION['user_name'] ?? 'Firma Yetkilisi';
        $user_title = 'Firma Yöneticisi';
        $sidebar_title = 'Firma Panel';
        $sidebar_icon = 'bi-building';
        $logout_path = '../logout.php';

        $badge_html = '';
        if (isset($sablon_count)) {
            $badge_html = '<span class="badge bg-primary ms-auto">' . $sablon_count . '</span>';
        }

        $menu_items = [
            ['url' => 'dashboard.php', 'icon' => 'bi-speedometer2', 'text' => 'Dashboard'],
            ['url' => 'sablonlar.php', 'icon' => 'bi-file-earmark-text', 'text' => 'Şablonlar', 'extra_html' => $badge_html],
            ['url' => 'danismanlar.php', 'icon' => 'bi-people', 'text' => 'Danışmanlar'],
            ['url' => 'raporlar.php', 'icon' => 'bi-graph-up', 'text' => 'Raporlar'],
            ['url' => 'ayarlar.php', 'icon' => 'bi-gear', 'text' => 'Ayarlar']
        ];
    }
}
// Danışman kontrolü
elseif (
    (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 'danisman') ||
    (isset($_SESSION['rol']) && $_SESSION['rol'] == 'danisman')
) {
    if (strpos($_SERVER['PHP_SELF'], '/danisman/') !== false) {
        $role = 'danisman';
        $user_name = $_SESSION['user_isim'] ?? 'Danışman';
        $user_title = 'Gayrimenkul Danışmanı';
        $sidebar_title = 'Danışman Panel';
        $sidebar_icon = 'bi-briefcase';
        $logout_path = '../logout.php';

        $menu_items = [
            ['url' => 'dashboard.php', 'icon' => 'bi-speedometer2', 'text' => 'Dashboard'],
            ['url' => 'sozlesme-gonder.php', 'icon' => 'bi-send', 'text' => 'Sözleşme Gönder'],
            ['url' => 'sozlesmeler.php', 'icon' => 'bi-file-text', 'text' => 'Sözleşmelerim'],
            ['url' => 'musteriler.php', 'icon' => 'bi-people', 'text' => 'Müşterilerim'],
            ['url' => 'portfoy.php', 'icon' => 'bi-building', 'text' => 'Portföyüm'],
            ['url' => 'profil.php', 'icon' => 'bi-person', 'text' => 'Profilim']
        ];
    }
}

?>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="#" class="sidebar-brand">
            <i class="bi <?php echo $sidebar_icon; ?>"></i>
            <span><?php echo $sidebar_title; ?></span>
        </a>
    </div>
    <div class="sidebar-menu">
        <nav class="nav flex-column">
            <?php foreach ($menu_items as $item): ?>
                <a class="nav-link <?php echo ($current_page == $item['url']) ? 'active' : ''; ?>"
                    href="<?php echo $item['url']; ?>">
                    <i class="bi <?php echo $item['icon']; ?>"></i> <?php echo $item['text']; ?>
                    <?php if (isset($item['extra_html']))
                        echo $item['extra_html']; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between">
            <div class="small">
                <div class="fw-bold"><?php echo htmlspecialchars($user_name); ?></div>
                <div class="text-muted" style="font-size: 0.8rem"><?php echo $user_title; ?></div>
            </div>
            <a href="<?php echo $logout_path; ?>" class="text-danger">
                <i class="bi bi-box-arrow-right fs-5"></i>
            </a>
        </div>
    </div>
</div>
<?php
// Sayfa başlığı varsayılanı
if (!isset($page_title)) {
    $page_title = 'emlakimza.com';
}

// Body class varsayılanı
if (!isset($body_class)) {
    $body_class = '';
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-H9LZ3EQXVJ"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'G-H9LZ3EQXVJ');
    </script>
    <link rel="icon" type="image/png" href="../favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <?php if (isset($extra_css))
        echo $extra_css; ?>
</head>

<body class="<?php echo htmlspecialchars($body_class); ?>">

    <!-- Mobile Toggle -->
    <button class="mobile-toggle d-md-none" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>
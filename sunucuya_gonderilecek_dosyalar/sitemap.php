<?php
header("Content-Type: application/xml; charset=utf-8");

$base_url = "https://emlakimza.com";
$current_date = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

// Statik Sayfalar
$pages = [
    ['url' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
    ['url' => '/giris', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['url' => '/kayit', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['url' => '/hukuki-gecerlilik', 'priority' => '0.7', 'changefreq' => 'monthly'],
];

foreach ($pages as $page) {
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . $base_url . $page['url'] . '</loc>' . PHP_EOL;
    echo '    <lastmod>' . $current_date . '</lastmod>' . PHP_EOL;
    echo '    <changefreq>' . $page['changefreq'] . '</changefreq>' . PHP_EOL;
    echo '    <priority>' . $page['priority'] . '</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

echo '</urlset>';
?>
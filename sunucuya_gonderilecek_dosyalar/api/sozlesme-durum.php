<?php
/**
 * API: Sözleşme Durumu Sorgulama
 * Danışmanların sözleşme durumunu kontrol etmesi için
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// API Token kontrolü (Bearer token)
$headers = apache_request_headers();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

// Response fonksiyonu
function jsonResponse($success, $data = [], $message = '', $http_code = 200) {
    http_response_code($http_code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Rate limiting kontrolü (basit versiyon)
function checkRateLimit($identifier, $max_requests = 60, $time_window = 60) {
    $cache_file = __DIR__ . '/../cache/ratelimit_' . md5($identifier) . '.json';
    
    if (!file_exists(dirname($cache_file))) {
        mkdir(dirname($cache_file), 0755, true);
    }
    
    $current_time = time();
    $requests = [];
    
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        $requests = array_filter($data['requests'], function($timestamp) use ($current_time, $time_window) {
            return ($current_time - $timestamp) < $time_window;
        });
    }
    
    if (count($requests) >= $max_requests) {
        return false;
    }
    
    $requests[] = $current_time;
    file_put_contents($cache_file, json_encode(['requests' => $requests]));
    
    return true;
}

// IP bazlı rate limiting
$ip = getUserIP();
if (!checkRateLimit($ip, 60, 60)) {
    jsonResponse(false, [], 'Rate limit exceeded. Try again later.', 429);
}

// Method kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Method not allowed', 405);
}

try {
    $db = getDB();
    
    // GET: UUID ile sözleşme durumu sorgula
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $uuid = sanitizeInput($_GET['uuid'] ?? '');
        
        if (empty($uuid)) {
            jsonResponse(false, [], 'UUID parametresi gerekli', 400);
        }
        
        // Sözleşmeyi bul
        $sql = "SELECT s.id, s.islem_uuid, s.durum, s.olusturma_tarihi, s.guncelleme_tarihi,
                       s.gayrimenkul_adres, s.fiyat,
                       COUNT(si.id) as imza_sayisi
                FROM sozlesmeler s
                LEFT JOIN sozlesme_imzalar si ON s.id = si.sozlesme_id
                WHERE s.islem_uuid = :uuid
                GROUP BY s.id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':uuid' => $uuid]);
        $sozlesme = $stmt->fetch();
        
        if (!$sozlesme) {
            jsonResponse(false, [], 'Sözleşme bulunamadı', 404);
        }
        
        // Duruma göre Türkçe açıklama
        $durum_aciklama = [
            'PENDING' => 'Müşteri imzası bekleniyor',
            'SIGNED_MAIN' => 'Ana imza atıldı, teyit imzası bekleniyor',
            'COMPLETED' => 'Sözleşme tamamlandı',
            'CANCELLED' => 'Sözleşme iptal edildi'
        ];
        
        jsonResponse(true, [
            'uuid' => $sozlesme['islem_uuid'],
            'durum' => $sozlesme['durum'],
            'durum_aciklama' => $durum_aciklama[$sozlesme['durum']] ?? 'Bilinmiyor',
            'imza_sayisi' => (int)$sozlesme['imza_sayisi'],
            'olusturma_tarihi' => $sozlesme['olusturma_tarihi'],
            'guncelleme_tarihi' => $sozlesme['guncelleme_tarihi'],
            'adres' => $sozlesme['gayrimenkul_adres'],
            'fiyat' => $sozlesme['fiyat'] ? formatMoney($sozlesme['fiyat']) : null
        ], 'Sözleşme bilgileri başarıyla alındı');
    }
    
    // POST: Toplu sözleşme durumu sorgula (Danışman için)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Session kontrolü - sadece giriş yapmış danışmanlar
        if (!isLoggedIn() || !isDanisman()) {
            jsonResponse(false, [], 'Yetkisiz erişim', 401);
        }
        
        $danisman_id = $_SESSION['user_id'];
        
        // Danışmanın son sözleşmelerini getir
        $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $sql = "SELECT s.id, s.islem_uuid, s.durum, s.olusturma_tarihi,
                       s.gayrimenkul_adres, s.musteri_email,
                       COUNT(si.id) as imza_sayisi
                FROM sozlesmeler s
                LEFT JOIN sozlesme_imzalar si ON s.id = si.sozlesme_id
                WHERE s.danisman_id = :danisman_id
                GROUP BY s.id
                ORDER BY s.olusturma_tarihi DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':danisman_id', $danisman_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $sozlesmeler = $stmt->fetchAll();
        
        // Toplam sayı
        $count_sql = "SELECT COUNT(*) as total FROM sozlesmeler WHERE danisman_id = :danisman_id";
        $count_stmt = $db->prepare($count_sql);
        $count_stmt->execute([':danisman_id' => $danisman_id]);
        $total = $count_stmt->fetch()['total'];
        
        $result = [];
        foreach ($sozlesmeler as $s) {
            $result[] = [
                'uuid' => $s['islem_uuid'],
                'durum' => $s['durum'],
                'adres' => $s['gayrimenkul_adres'],
                'musteri_email' => $s['musteri_email'],
                'imza_sayisi' => (int)$s['imza_sayisi'],
                'tarih' => $s['olusturma_tarihi']
            ];
        }
        
        jsonResponse(true, [
            'sozlesmeler' => $result,
            'pagination' => [
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total
            ]
        ], 'Sözleşmeler başarıyla listelendi');
    }
    
} catch (Exception $e) {
    logError('API Error: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => $e->getLine()
    ]);
    jsonResponse(false, [], 'Sunucu hatası', 500);
}
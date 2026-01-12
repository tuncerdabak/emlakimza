<?php
require_once __DIR__ . '/../config/config.php';

class PlanManager
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Firmanın limitlerini kontrol et
     * @param int $firma_id
     * @return array
     */
    public function getUsageStats($firma_id)
    {
        // Firmayı çek
        $stmt = $this->db->prepare("SELECT plan, uyelik_bitis, belge_limiti, kullanici_limiti FROM firmalar WHERE id = ?");
        $stmt->execute([$firma_id]);
        $firma = $stmt->fetch();

        if (!$firma) {
            return ['error' => 'Firma bulunamadı'];
        }

        // Limit belirleme (Config öncelikli, yoksa DB fallback)
        $docLimit = $firma['belge_limiti'];
        $userLimit = $firma['kullanici_limiti'];

        // Plan adını normalize et (küçük harf ve boşlukları temizle)
        $planKey = strtolower(trim($firma['plan']));

        if (defined('PACKAGES') && isset(PACKAGES[$planKey])) {
            $docLimit = PACKAGES[$planKey]['doc_limit'];
            $userLimit = PACKAGES[$planKey]['user_limit'];
        }

        // Mevcut kullanım (Bu ay)
        $startOfMonth = date('Y-m-01 00:00:00');

        // Belge sayısı
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM sozlesmeler WHERE firma_id = ? AND olusturma_tarihi >= ?");
        $stmt->execute([$firma_id, $startOfMonth]);
        $usedDocs = $stmt->fetchColumn();

        // Kullanıcı sayısı
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM kullanicilar WHERE firma_id = ?");
        $stmt->execute([$firma_id]);
        $usedUsers = $stmt->fetchColumn();

        return [
            'plan' => $firma['plan'],
            'expires_at' => $firma['uyelik_bitis'],
            'is_expired' => ($firma['uyelik_bitis'] && strtotime($firma['uyelik_bitis']) < time()),
            'docs' => [
                'used' => $usedDocs,
                'limit' => $docLimit,
                'remaining' => max(0, $docLimit - $usedDocs)
            ],
            'users' => [
                'used' => $usedUsers,
                'limit' => $userLimit,
                'remaining' => max(0, $userLimit - $usedUsers)
            ]
        ];
    }

    /**
     * Belge oluşturabilir mi?
     */
    public function canCreateDocument($firma_id)
    {
        $stats = $this->getUsageStats($firma_id);

        // Admin veya Limitsiz ise
        if ($stats['docs']['limit'] > 999000)
            return true;

        // Süre kontrolü (Eğer Free değilse ve süresi dolmuşsa)
        if ($stats['plan'] !== 'free' && $stats['is_expired']) {
            return false;
        }

        return $stats['docs']['used'] < $stats['docs']['limit'];
    } /** * Kullanıcı ekleyebilir mi? */
    public function
        canAddUser(
        $firma_id
    ) {
        $stats = $this->getUsageStats($firma_id);

        if ($stats['users']['limit'] > 999000)
            return true;

        return $stats['users']['used'] < $stats['users']['limit'];
    }
}